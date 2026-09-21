<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembatas laju bersama berbasis fixed window.
 *
 * Nilai dimensi tidak pernah disimpan. limit_key adalah SHA-256 atas policy,
 * nama dimensi, dan nilai yang sudah dinormalisasi. NIK terlebih dahulu di-HMAC
 * memakai KPKP_DATA_PEPPER melalui Encryption_lib.
 */
class Rate_limiter {

    private $CI;
    private $policies = [];
    private $held_locks = [];
    private $shutdown_registered = FALSE;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->config->load('rate_limits', TRUE);
        $this->policies = $this->CI->config->item('rate_limit_policies', 'rate_limits') ?: [];
    }

    /**
     * Periksa tanpa menambah penghitung. Dipakai untuk policy yang hanya
     * mencatat kegagalan, seperti pencarian tiket publik.
     */
    public function inspect($policy_name, array $context = [])
    {
        $resolved = $this->resolve($policy_name, $context);
        if (empty($resolved['success'])) {
            return $resolved;
        }

        $retry_after = 0;
        foreach ($resolved['keys'] as $key) {
            $row = $this->CI->db
                ->select(
                    'failed_attempts, GREATEST(1, ' . $resolved['window']
                    . ' - TIMESTAMPDIFF(SECOND, window_started_at, NOW())) AS retry_after',
                    FALSE
                )
                ->where('limit_key', $key)
                ->where(
                    'window_started_at > DATE_SUB(NOW(), INTERVAL '
                    . $resolved['window'] . ' SECOND)',
                    NULL,
                    FALSE
                )
                ->get('sys_rate_limits')
                ->row_array();
            if ($row && (int) $row['failed_attempts'] >= $resolved['limit']) {
                $retry_after = max($retry_after, (int) $row['retry_after']);
            }
        }

        return [
            'success' => TRUE,
            'allowed' => $retry_after === 0,
            'retry_after' => $retry_after,
        ];
    }

    /**
     * Tambah seluruh penghitung secara atomik per dimensi lalu kembalikan
     * apakah percobaan ini masih berada di dalam batas.
     */
    public function hit($policy_name, array $context = [])
    {
        $resolved = $this->resolve($policy_name, $context);
        if (empty($resolved['success'])) {
            return $resolved;
        }

        $blocked = FALSE;
        $warning_triggered = FALSE;
        $retry_after = 0;
        foreach ($resolved['keys'] as $key) {
            $ok = $this->CI->db->query(
                'INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts)
                 VALUES (?, NOW(), 1)
                 ON DUPLICATE KEY UPDATE
                    failed_attempts = IF(
                        window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $resolved['window'] . ' SECOND),
                        1,
                        LEAST(255, failed_attempts + 1)
                    ),
                    window_started_at = IF(
                        window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $resolved['window'] . ' SECOND),
                        NOW(),
                        window_started_at
                    )',
                [$key]
            );
            if ( ! $ok) {
                return $this->failure('Penyimpanan pembatas laju gagal.');
            }
            $row = $this->CI->db
                ->select(
                    'failed_attempts, GREATEST(1, ' . $resolved['window']
                    . ' - TIMESTAMPDIFF(SECOND, window_started_at, NOW())) AS retry_after',
                    FALSE
                )
                ->where('limit_key', $key)
                ->get('sys_rate_limits')
                ->row_array();
            if ($row && (int) $row['failed_attempts'] > $resolved['limit']) {
                $blocked = TRUE;
                $warning_triggered = $warning_triggered || (int) $row['failed_attempts'] === $resolved['limit'] + 1;
                $retry_after = max($retry_after, (int) $row['retry_after']);
            }
        }

        $warning_type = $blocked ? ($resolved['window'] <= 60 ? 'concurrent_burst' : 'continuous_access') : NULL;
        if ( ! $blocked && ! empty($resolved['concurrent_dimension'])) {
            $slot = $this->acquire_concurrent_slot($policy_name, $resolved['concurrent_dimension'], $context);
            if (empty($slot['success'])) { return $slot; }
            if (empty($slot['allowed'])) {
                $blocked = TRUE;
                $warning_triggered = TRUE;
                $retry_after = 1;
                $warning_type = 'concurrent_access';
            }
        }
        if ($warning_triggered && empty($this->policies[$policy_name]['senyap'])) {
            $route = strtolower((string) $this->CI->router->fetch_class()) . '/'
                . strtolower((string) $this->CI->router->fetch_method());
            log_message('error', 'SECURITY_WARNING automated_attack_suspected policy=' . $policy_name
                . ' type=' . $warning_type . ' function=' . $route
                . ' ip_hash=' . hash_hmac('sha256', (string) $this->CI->input->ip_address(),
                    (string) $this->CI->config->item('encryption_key'))
                . ' limit=' . $resolved['limit'] . ' window=' . $resolved['window']
                . ' retry_after=' . $retry_after);
            $this->raise_alert($policy_name, $warning_type, $resolved['limit'], $resolved['window']);
        }

        return [
            'success' => TRUE,
            'allowed' => ! $blocked,
            'retry_after' => $retry_after,
            'warning_type' => $warning_type,
            'policy' => $policy_name,
        ];
    }

    /**
     * Versi hit() untuk jalur panas (dipanggil pada SETIAP permintaan oleh kontrol
     * anti-otomatisasi global): SATU kueri atomik per dimensi, bukan tiga.
     *
     * Penghitungnya naik lewat LAST_INSERT_ID(ekspresi) di dalam ON DUPLICATE KEY UPDATE,
     * sehingga nilai barunya dibaca kembali dari koneksi yang sama (insert_id) tanpa
     * SELECT dan tanpa celah antara "naikkan" dan "baca". Dua permintaan bersamaan pada
     * kunci yang sama SELALU mendapat angka berbeda (tidak ada pembaruan yang hilang);
     * tests/anti_automation_db_test.php membuktikannya dengan proses paralel sungguhan.
     * Baris BARU tidak mengubah insert_id (kolom kunci bukan AUTO_INCREMENT), yaitu 0,
     * dan itu berarti hitungan 1. Tidak memakai kunci advisory (concurrent_dimension).
     *
     * Kegagalan penyimpanan dikembalikan sebagai success=FALSE; pemanggil jalur global
     * memilih FAIL-OPEN (lihat Anti_automation), berbeda dari hit() yang fail-closed.
     */
    public function hit_fast($policy_name, array $context = [])
    {
        $resolved = $this->resolve($policy_name, $context);
        if (empty($resolved['success'])) {
            return $resolved;
        }

        $window = (int) $resolved['window'];
        $limit = (int) $resolved['limit'];
        $count = 0;
        $blocked = FALSE;
        $first_excess = FALSE;
        $blocked_keys = [];
        foreach ($resolved['keys'] as $key) {
            $ok = $this->CI->db->query(
                'INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts)
                 VALUES (?, NOW(), 1)
                 ON DUPLICATE KEY UPDATE
                    failed_attempts = LAST_INSERT_ID(IF(
                        window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $window . ' SECOND),
                        1,
                        LEAST(255, failed_attempts + 1)
                    )),
                    window_started_at = IF(
                        window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $window . ' SECOND),
                        NOW(),
                        window_started_at
                    )',
                [$key]
            );
            if ( ! $ok) {
                return $this->failure('Penyimpanan pembatas laju gagal.');
            }
            $n = (int) $this->CI->db->insert_id();
            if ($n < 1) { $n = 1; }
            $count = max($count, $n);
            if ($n > $limit) {
                $blocked = TRUE;
                $blocked_keys[] = $key;
                $first_excess = $first_excess || $n === $limit + 1;
            }
        }

        $retry_after = 0;
        if ($blocked) {
            foreach ($blocked_keys as $key) {
                $row = $this->CI->db->query(
                    'SELECT GREATEST(1, ' . $window . ' - TIMESTAMPDIFF(SECOND, window_started_at, NOW())) AS retry_after
                     FROM sys_rate_limits WHERE limit_key = ?',
                    [$key]
                );
                $r = $row ? $row->row_array() : NULL;
                $retry_after = max($retry_after, (int) ($r['retry_after'] ?? $window));
            }
        }
        $warning_type = $blocked ? ($window <= 60 ? 'concurrent_burst' : 'continuous_access') : NULL;
        if ($first_excess && empty($this->policies[$policy_name]['senyap'])) {
            $route = strtolower((string) $this->CI->router->fetch_class()) . '/'
                . strtolower((string) $this->CI->router->fetch_method());
            log_message('error', 'SECURITY_WARNING automated_attack_suspected policy=' . $policy_name
                . ' type=' . $warning_type . ' function=' . $route
                . ' ip_hash=' . hash_hmac('sha256', (string) $this->CI->input->ip_address(),
                    (string) $this->CI->config->item('encryption_key'))
                . ' limit=' . $limit . ' window=' . $window . ' retry_after=' . $retry_after);
            $this->raise_alert($policy_name, $warning_type, $limit, $window);
        }

        return [
            'success' => TRUE,
            'allowed' => ! $blocked,
            'retry_after' => $retry_after,
            'warning_type' => $warning_type,
            'policy' => $policy_name,
            'count' => $count,
            'limit' => $limit,
        ];
    }

    /**
     * Teruskan pelampauan PERTAMA dalam satu jendela sebagai peringatan ke administrator
     * (form keamanan poin 10.5). Sebelum ini pelampauan hanya menjadi satu baris log
     * terenkripsi yang tidak pernah dibaca siapa pun. Policy bertanda `senyap` (yang dipakai
     * peringatan itu sendiri) tidak memicu apa pun. Gagal diam-diam: pengamat tidak boleh
     * menggagalkan permintaan yang sedang dijaga.
     */
    private function raise_alert($policy_name, $warning_type, $limit, $window)
    {
        if ( ! empty($this->policies[$policy_name]['senyap'])) { return; }
        try {
            $this->CI->load->library('Security_alert');
            $this->CI->security_alert->raise(
                'batas_laju',
                $warning_type === 'concurrent_access' ? 'tinggi' : 'sedang',
                "Batas laju '{$policy_name}' terlampaui ({$warning_type}, {$limit} per {$window} detik)",
                ['policy' => $policy_name, 'warning_type' => $warning_type, 'limit' => $limit, 'window' => $window,
                 'route' => strtolower((string) $this->CI->router->fetch_class()) . '/' . strtolower((string) $this->CI->router->fetch_method())],
                'rl:' . $policy_name
            );
        } catch (Throwable $e) {
            log_message('error', 'Rate_limiter: peringatan keamanan gagal dikirim: ' . $e->getMessage());
        }
    }

    /**
     * Policy yang menghitung semua percobaan: permintaan ke-1..limit boleh,
     * permintaan berikutnya ditolak sampai fixed window berakhir.
     */
    public function consume($policy_name, array $context = [])
    {
        return $this->hit($policy_name, $context);
    }

    private function resolve($policy_name, array $context)
    {
        if (empty($this->policies[$policy_name])) {
            log_message('error', 'Rate_limiter: policy tidak terdaftar: ' . $policy_name);
            return $this->failure('Policy pembatas laju tidak tersedia.');
        }

        $policy = $this->policies[$policy_name];
        $limit = (int) ($policy['limit'] ?? 0);
        $window = (int) ($policy['window'] ?? 0);
        if ($limit < 1 || $limit > 255 || $window < 1) {
            log_message('error', 'Rate_limiter: konfigurasi policy tidak valid: ' . $policy_name);
            return $this->failure('Konfigurasi pembatas laju tidak valid.');
        }

        $keys = [];
        foreach (($policy['dimensions'] ?? []) as $dimension) {
            $value = $this->dimension_value($dimension, $context);
            if ($value === NULL || $value === '') {
                log_message(
                    'error',
                    'Rate_limiter: dimensi ' . $dimension . ' tidak tersedia untuk policy ' . $policy_name
                );
                return $this->failure('Dimensi pembatas laju tidak lengkap.');
            }
            $keys[] = hash('sha256', $policy_name . ':' . $dimension . ':' . $value);
        }
        if ( ! $keys) {
            log_message('error', 'Rate_limiter: tidak ada dimensi terisi untuk policy ' . $policy_name);
            return $this->failure('Identitas pembatas laju tidak tersedia.');
        }

        return [
            'success' => TRUE,
            'limit' => $limit,
            'window' => $window,
            'keys' => array_values(array_unique($keys)),
            'concurrent_dimension' => $policy['concurrent_dimension'] ?? NULL,
        ];
    }

    /** A MySQL advisory lock lives until this request ends or its connection closes. */
    private function acquire_concurrent_slot($policy_name, $dimension, array $context)
    {
        $value = $this->dimension_value($dimension, $context);
        if ($value === NULL || $value === '') { return $this->failure('Dimensi akses bersamaan tidak lengkap.'); }
        $name = 'kpkp:' . substr(hash('sha256', $policy_name . ':' . $dimension . ':' . $value), 0, 58);
        if (isset($this->held_locks[$name])) { return ['success' => TRUE, 'allowed' => TRUE]; }
        $query = $this->CI->db->query('SELECT GET_LOCK(?, 0) AS acquired', [$name]);
        if ( ! $query) { return $this->failure('Pemeriksaan akses bersamaan gagal.'); }
        $row = $query->row_array();
        if ((int) ($row['acquired'] ?? -1) === 1) {
            $this->held_locks[$name] = TRUE;
            if ( ! $this->shutdown_registered) {
                register_shutdown_function([$this, 'release_concurrent_locks']);
                $this->shutdown_registered = TRUE;
            }
            return ['success' => TRUE, 'allowed' => TRUE];
        }
        if ((int) ($row['acquired'] ?? -1) === 0) {
            return ['success' => TRUE, 'allowed' => FALSE];
        }
        return $this->failure('Pemeriksaan akses bersamaan tidak tersedia.');
    }

    public function release_concurrent_locks()
    {
        foreach (array_keys($this->held_locks) as $name) {
            try { $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$name]); }
            catch (Throwable $ignored) { /* Connection closure also releases advisory locks. */ }
            unset($this->held_locks[$name]);
        }
    }
    private function dimension_value($dimension, array $context)
    {
        if ($dimension === 'ip') {
            return (string) $this->CI->input->ip_address();
        }
        if ($dimension === 'account') {
            return isset($context['account_id']) ? (string) (int) $context['account_id'] : NULL;
        }
        if ($dimension === 'object') {
            return isset($context['object_id']) ? (string) (int) $context['object_id'] : NULL;
        }
        if ($dimension === 'key') {
            // Kunci bebas dari pemanggil internal (mis. penekan duplikat peringatan); tidak pernah dari masukan pengguna.
            return isset($context['key']) && $context['key'] !== '' ? (string) $context['key'] : NULL;
        }
        if ($dimension === 'nik') {
            $nik = preg_replace('/\D+/', '', (string) ($context['nik'] ?? ''));
            if ($nik === '') {
                return NULL;
            }
            if (empty(getenv('KPKP_DATA_PEPPER'))) {
                log_message('error', 'Rate_limiter: KPKP_DATA_PEPPER tidak tersedia untuk dimensi NIK.');
                return NULL;
            }
            $this->CI->load->library('Encryption_lib');
            return $this->CI->encryption_lib->deterministic_hash($nik);
        }

        log_message('error', 'Rate_limiter: dimensi tidak dikenal: ' . $dimension);
        return NULL;
    }

    private function failure($message)
    {
        return [
            'success' => FALSE,
            'allowed' => FALSE,
            'retry_after' => 0,
            'message' => $message,
        ];
    }
}
