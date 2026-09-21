<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyapu retensi: menghapus informasi yang sudah kedaluwarsa (form keamanan poin 7.3).
 *
 * Sebelum ini "Retensi belum ada penyapunya": snapshot SIMPERUM (terenkripsi, berisi profil RTLH
 * per NIK) punya expires_at tetapi tidak ada pekerjaan yang menghapusnya, sehingga menumpuk selamanya.
 * Kebijakan (hari) ada di config/data_lifecycle.php. Tidak bergantung pada CodeIgniter selain
 * pengambilan koneksi DB bawaan, jadi dapat diuji dengan adaptor DB (tests/data_lifecycle_db_test.php).
 *
 * Yang disapu:
 *   - snapshot SIMPERUM yang sudah lewat expires_at + masa tenggang, KECUALI yang dirujuk penilaian
 *     yang sudah dikirim (bukan draf): itu bukti asal data sebuah arsip;
 *   - penghitung batas laju lama, token surel yang sudah kedaluwarsa, langganan push yang dinonaktifkan;
 *   - log aplikasi lebih tua dari batas, dan jejak audit lebih tua dari batas (5 tahun; sengaja lama).
 * Tidak disapu (sengaja): cache respons layanan luar (data publik, dipakai sebagai cadangan saat layanan
 * itu mati), sesi (dikelola PHP/hosting), dan berkas unggahan (dimiliki akun; dihapus lewat hapus akun).
 *
 * Dijalankan sekali per interval: dipicu permintaan web SESUDAH respons terkirim (MY_Controller), atau
 * `php index.php retensi jalankan [kering]` dari CLI/cron. Mode kering hanya menghitung.
 */
class Penyapu_retensi {

    private $db;
    private $policy;
    private $app;

    /** @param array $params db (adaptor: query(), affected_rows()), policy (isi 'retensi'), app (akar application/ berakhiran pemisah) */
    public function __construct(array $params = [])
    {
        if (isset($params['policy'])) {
            $this->policy = $params['policy'];
        } else {
            $config = [];
            require dirname(__DIR__) . '/config/data_lifecycle.php';
            $this->policy = $config['data_lifecycle']['retensi'];
        }
        $this->db = $params['db'] ?? (function_exists('get_instance') ? get_instance()->db : NULL);
        $this->app = rtrim($params['app'] ?? (defined('APPPATH') ? APPPATH : dirname(__DIR__) . '/'), '/\\') . DIRECTORY_SEPARATOR;
    }

    public function interval() { return (int) $this->policy['interval_detik']; }

    /** TRUE bila penanda terakhir jalan sudah lebih tua dari interval (atau belum ada). */
    public static function jatuh_tempo($marker, $interval, $sekarang = NULL)
    {
        $sekarang = $sekarang ?? time();
        clearstatcache(true, $marker);
        return ! is_file($marker) || (int) @filemtime($marker) <= $sekarang - (int) $interval;
    }

    /**
     * @return array {kering:bool, tugas:array<string,array{jumlah:int, galat:?string}>, total:int}
     */
    public function jalankan($kering = FALSE)
    {
        $p = $this->policy;
        $sn = (int) $p['snapshot_simperum_lewat_hari'];
        $tugas = [
            'snapshot_simperum' => [
                "FROM sf_rekaman_simperum WHERE expires_at < (NOW() - INTERVAL {$sn} DAY)
                    AND id NOT IN (SELECT simperum_snapshot_id FROM sf_penilaian_perumahan
                                   WHERE simperum_snapshot_id IS NOT NULL AND status <> 'draft')", 'DELETE'],
            'rate_limit' => ['FROM sys_rate_limits WHERE window_started_at < (NOW() - INTERVAL ' . (int) $p['rate_limit_hari'] . ' DAY)', 'DELETE'],
            'langganan_push_nonaktif' => ['FROM sys_push_subscriptions WHERE aktif = 0 AND updated_at < (NOW() - INTERVAL ' . (int) $p['langganan_push_nonaktif_hari'] . ' DAY)', 'DELETE'],
            'jejak_audit' => ['FROM sys_jejak_audit WHERE created_at < (NOW() - INTERVAL ' . (int) $p['jejak_audit_hari'] . ' DAY)', 'DELETE'],
        ];
        $hasil = [];
        foreach ($tugas as $nama => [$dari, $jenis]) {
            $hasil[$nama] = $this->sql($dari, $kering);
        }
        $tk = (int) $p['token_surel_lewat_hari'];
        $hasil['token_surel'] = $this->sql_ubah(
            "FROM usr_users WHERE email_token IS NOT NULL AND email_token_expiry < (NOW() - INTERVAL {$tk} DAY)",
            'UPDATE usr_users SET email_token = NULL, email_token_expiry = NULL WHERE email_token IS NOT NULL AND email_token_expiry < (NOW() - INTERVAL ' . $tk . ' DAY)',
            $kering);
        $hasil['log_aplikasi'] = $this->sapu_log((int) $p['log_aplikasi_hari'], $kering);

        $total = 0;
        foreach ($hasil as $h) { $total += (int) $h['jumlah']; }
        return ['kering' => (bool) $kering, 'tugas' => $hasil, 'total' => $total];
    }

    /** Catat hasil satu putaran (bukan mode kering) di jejak audit; hanya jumlah, tanpa isi data. */
    public function catat(array $hasil, $ip = 'sistem')
    {
        if ($hasil['kering']) { return FALSE; }
        $ringkas = [];
        foreach ($hasil['tugas'] as $nama => $h) { $ringkas[$nama] = $h['galat'] === NULL ? $h['jumlah'] : 'galat'; }
        try {
            return (bool) $this->db->query(
                'INSERT INTO sys_jejak_audit (actor_id, actor_email, actor_role, aksi, objek_tipe, objek_id, ringkasan, detail_json, ip, created_at)
                 VALUES (NULL, NULL, ?, ?, ?, NULL, ?, ?, ?, NOW())',
                ['sistem', 'retensi_dijalankan', 'retensi', 'Penyapu retensi: ' . $hasil['total'] . ' entri kedaluwarsa dihapus/dibersihkan', json_encode($ringkas), substr((string) $ip, 0, 45)]
            );
        } catch (Throwable $e) {
            if (function_exists('log_message')) { log_message('error', 'Retensi: gagal mencatat audit: ' . $e->getMessage()); }
            return FALSE;
        }
    }

    // ------------------------------------------------------------------
    private function sql($dari, $kering)
    {
        try {
            if ($kering) {
                $r = $this->db->query('SELECT COUNT(*) AS n ' . $dari);
                $baris = $r ? $r->row_array() : NULL;
                return ['jumlah' => (int) ($baris['n'] ?? 0), 'galat' => NULL];
            }
            $ok = $this->db->query('DELETE ' . $dari);
            return ['jumlah' => $ok ? (int) $this->db->affected_rows() : 0, 'galat' => $ok ? NULL : 'kueri gagal'];
        } catch (Throwable $e) {
            return ['jumlah' => 0, 'galat' => get_class($e)];
        }
    }

    private function sql_ubah($dari_hitung, $ubah, $kering)
    {
        try {
            if ($kering) {
                $r = $this->db->query('SELECT COUNT(*) AS n ' . $dari_hitung);
                $baris = $r ? $r->row_array() : NULL;
                return ['jumlah' => (int) ($baris['n'] ?? 0), 'galat' => NULL];
            }
            $ok = $this->db->query($ubah);
            return ['jumlah' => $ok ? (int) $this->db->affected_rows() : 0, 'galat' => $ok ? NULL : 'kueri gagal'];
        } catch (Throwable $e) {
            return ['jumlah' => 0, 'galat' => get_class($e)];
        }
    }

    private function sapu_log($hari, $kering)
    {
        $dir = $this->app . 'logs' . DIRECTORY_SEPARATOR;
        if ( ! is_dir($dir)) { return ['jumlah' => 0, 'galat' => NULL]; }
        $batas = time() - $hari * 86400;
        $n = 0;
        foreach ((array) glob($dir . 'log-*.php') as $f) {
            if ( ! preg_match('/log-\d{4}-\d{2}-\d{2}\.php$/', $f) || (int) @filemtime($f) >= $batas) { continue; }
            if ($kering || @unlink($f)) { $n++; }
        }
        return ['jumlah' => $n, 'galat' => NULL];
    }
}
