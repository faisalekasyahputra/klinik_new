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
                $retry_after = max($retry_after, (int) $row['retry_after']);
            }
        }

        return [
            'success' => TRUE,
            'allowed' => ! $blocked,
            'retry_after' => $retry_after,
        ];
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
        ];
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
