<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kontrol anti-otomatisasi GLOBAL (form keamanan poin 10.4).
 *
 * Dipanggil dari MY_Controller::__construct(), jadi berlaku untuk SEMUA endpoint PHP
 * (45 controller), tidak hanya yang memanggil pembatas laju sendiri. Urutan pemeriksaan:
 *   1. batas global per identitas (akun bila login, IP bila anonim),
 *   2. batas tulis untuk POST/PUT/PATCH/DELETE (logika bisnis berlebihan),
 *   3. batas per kelas rute (pencarian, API, ekspor/unduh: eksfiltrasi data dan beban mahal),
 *   4. batas unggahan (permintaan yang membawa berkas).
 * Kebijakannya di config/rate_limits.php; pola kelas di config/anti_automation.php.
 *
 * FAIL-OPEN: kalau penyimpanan pembatas laju gagal, permintaan DILANJUTKAN dan kegagalannya
 * dicatat. Ini disengaja dan berbeda dari limiter per-endpoint (yang fail-closed untuk
 * aksi sensitif): jalur ini dilalui SETIAP halaman, jadi gangguan DB sesaat tidak boleh
 * menjadi pemadaman seluruh situs oleh pengamatnya sendiri.
 */
class Anti_automation {

    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('anti_automation');
    }

    /**
     * @param array $req ip, account_id (int|null), controller, method, http_method, has_files (bool)
     * @return array {blocked: bool, result: array|null, policy: string|null}
     */
    public function guard(array $req)
    {
        $this->CI->load->library('Rate_limiter');
        $akun = ! empty($req['account_id']) ? (int) $req['account_id'] : 0;
        $suffix = $akun > 0 ? 'akun' : 'ip';
        $ctx = $akun > 0 ? ['account_id' => $akun] : [];

        $daftar = [($akun > 0 ? 'global_akun' : 'global_anon')];
        if (anti_automation_is_write($req['http_method'] ?? 'GET')) {
            $daftar[] = $akun > 0 ? 'tulis_akun' : 'tulis_anon';
        }
        foreach (anti_automation_route_classes($req['controller'] ?? '', $req['method'] ?? '') as $kelas) {
            $daftar[] = 'kelas_' . $kelas . '_' . $suffix;
        }
        if ( ! empty($req['has_files'])) {
            $daftar[] = 'unggah_' . $suffix;
        }

        foreach ($daftar as $policy) {
            $r = $this->CI->rate_limiter->hit_fast($policy, $ctx);
            if (empty($r['success'])) {
                log_message('error', 'Anti_automation: pembatas laju tidak tersedia (' . $policy . '), permintaan dilanjutkan (fail-open).');
                return ['blocked' => FALSE, 'result' => NULL, 'policy' => NULL, 'fail_open' => TRUE];
            }
            if (empty($r['allowed'])) {
                return ['blocked' => TRUE, 'result' => $r, 'policy' => $policy];
            }
        }
        return ['blocked' => FALSE, 'result' => NULL, 'policy' => NULL];
    }
}
