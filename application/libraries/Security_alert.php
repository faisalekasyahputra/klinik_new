<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peringatan keamanan ke administrator (form keamanan poin 10.5).
 *
 * Sebelum ini, serangan otomatis yang terdeteksi hanya menjadi satu baris log
 * TERENKRIPSI (dan header X-Security-Warning untuk si penyerang sendiri): tidak ada
 * administrator yang bisa melihatnya kecuali membuka dan mendekripsi berkas log. Kini:
 *
 *   1. dicatat di jejak audit pusat (`sys_jejak_audit`, aksi `peringatan_keamanan`), yang
 *      sudah dapat dibaca dan disaring superadmin di layar Jejak Audit,
 *   2. diringkas di dasbor superadmin (banner jumlah peringatan 24 jam terakhir),
 *   3. untuk tingkat `tinggi` (dan eskalasi), dikirim proaktif sebagai Web Push ke
 *      superadmin yang mengaktifkan notifikasi.
 *
 * Yang dijaga agar administrator tidak dibanjiri: peringatan yang sama dari IP yang sama
 * ditekan selama 30 menit (kebijakan `alert_dedupe`), dan tiga peringatan berbeda dari satu IP
 * dalam satu jam dieskalasi SEKALI menjadi peringatan `tinggi`. Semuanya GAGAL DIAM-DIAM:
 * pengamat tidak boleh menggagalkan permintaan yang sedang dijaga.
 */
class Security_alert {

    private $CI;
    private $cfg;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('anti_automation');
        $this->cfg = anti_automation_config('security_alert', []);
    }

    /**
     * @param string $tipe      Kode stabil: batas_laju, pemindai_ua, jalur_jebakan, bot_form,
     *                          login_beruntun, akun_terkunci, eskalasi, ...
     * @param string $tingkat   rendah | sedang | tinggi
     * @param string $ringkasan Kalimat yang dibaca manusia (disimpan SAAT kejadian).
     * @param string $kunci     Kunci penekan duplikat; bersama IP menentukan "peringatan yang sama".
     * @return array {dicatat: bool, ditekan: bool, eskalasi: bool, push: bool}
     */
    public function raise($tipe, $tingkat, $ringkasan, array $detail = [], $kunci = '')
    {
        $hasil = ['dicatat' => FALSE, 'ditekan' => FALSE, 'eskalasi' => FALSE, 'push' => FALSE];
        try {
            $this->CI->load->library('Rate_limiter');
            $ip = (string) $this->CI->input->ip_address();
            $dedupe = $this->CI->rate_limiter->hit_fast('alert_dedupe', ['key' => hash('sha256', ($kunci !== '' ? $kunci : $tipe) . '|' . $ip)]);
            if ( ! empty($dedupe['success']) && (int) ($dedupe['count'] ?? 1) > 1) {
                $hasil['ditekan'] = TRUE;
                return $hasil;
            }

            $tingkat = in_array($tingkat, ['rendah', 'sedang', 'tinggi'], TRUE) ? $tingkat : 'sedang';
            $hasil['dicatat'] = $this->tulis($tipe, $tingkat, $ringkasan, $detail);
            if (in_array($tingkat, (array) ($this->cfg['push_tingkat'] ?? []), TRUE)) {
                $hasil['push'] = $this->kirim_push($tipe, $ringkasan);
            }

            // Eskalasi: tiga peringatan (tidak ditekan) dari satu IP dalam satu jam = pola, bukan kebetulan.
            $eskalasi = $this->CI->rate_limiter->hit_fast('alert_eskalasi');
            if ( ! empty($eskalasi['success']) && (int) ($eskalasi['count'] ?? 0) === 3) {
                $hasil['eskalasi'] = TRUE;
                $ringkas = 'Aktivitas otomatis berulang dari satu alamat: 3 peringatan keamanan dalam satu jam';
                $this->tulis('eskalasi', 'tinggi', $ringkas, ['peringatan_terakhir' => $tipe]);
                $hasil['push'] = $this->kirim_push('eskalasi', $ringkas) || $hasil['push'];
            }
        } catch (Throwable $e) {
            log_message('error', 'Security_alert gagal (' . $tipe . '): ' . $e->getMessage());
        }
        return $hasil;
    }

    /** Ringkasan untuk dasbor superadmin: jumlah peringatan dalam N jam terakhir. */
    public function ringkasan($jam = NULL)
    {
        $jam = (int) ($jam ?: ($this->cfg['banner_jam'] ?? 24));
        $kosong = ['jam' => $jam, 'total' => 0, 'tinggi' => 0, 'per_tipe' => [], 'terakhir' => NULL];
        try {
            if ( ! $this->CI->db->table_exists('sys_jejak_audit')) { return $kosong; }
            $aksi = $this->cfg['aksi'] ?? 'peringatan_keamanan';
            $rows = $this->CI->db->select('objek_tipe, objek_id, created_at')
                ->where('aksi', $aksi)
                ->where('created_at >=', date('Y-m-d H:i:s', time() - $jam * 3600))
                ->order_by('created_at', 'DESC')->limit(500)->get('sys_jejak_audit')->result_array();
            $hasil = $kosong;
            foreach ($rows as $r) {
                $hasil['total']++;
                if ($r['objek_id'] === 'tinggi') { $hasil['tinggi']++; }
                $hasil['per_tipe'][$r['objek_tipe']] = ($hasil['per_tipe'][$r['objek_tipe']] ?? 0) + 1;
            }
            $hasil['terakhir'] = $rows ? $rows[0]['created_at'] : NULL;
            arsort($hasil['per_tipe']);
            return $hasil;
        } catch (Throwable $e) {
            log_message('error', 'Security_alert::ringkasan gagal: ' . $e->getMessage());
            return $kosong;
        }
    }

    private function tulis($tipe, $tingkat, $ringkasan, array $detail)
    {
        if ( ! $this->CI->db->table_exists('sys_jejak_audit')) { return FALSE; }
        $sesi = isset($this->CI->session) ? $this->CI->session : NULL;
        $detail['tingkat'] = $tingkat;
        return (bool) $this->CI->db->insert('sys_jejak_audit', [
            'actor_id'    => $sesi ? ($sesi->userdata('user_id') ?: NULL) : NULL,
            'actor_email' => $sesi ? ($sesi->userdata('email') ?: NULL) : NULL,
            'actor_role'  => $sesi ? ($sesi->userdata('role') ?: NULL) : NULL,
            'aksi'        => substr((string) ($this->cfg['aksi'] ?? 'peringatan_keamanan'), 0, 40),
            'objek_tipe'  => substr((string) $tipe, 0, 40),
            'objek_id'    => $tingkat,
            'ringkasan'   => substr((string) $ringkasan, 0, 255),
            'detail_json' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'ip'          => $this->CI->input->ip_address(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Web Push ke superadmin, DITUNDA sampai respons selesai dikirim ke klien: pengiriman ke
     * layanan push membutuhkan beberapa detik dan tidak boleh memperlambat (atau menahan) respons
     * penolakan yang sedang diberikan kepada si penyerang.
     */
    private function kirim_push($tipe, $ringkasan)
    {
        try {
            $this->CI->load->library('Web_push_service');
            if ( ! $this->CI->web_push_service->configured()) { return FALSE; }
            $audience = $this->cfg['push_audience'] ?? [['role' => 'admin']];
            $url = $this->cfg['push_url'] ?? 'Admin_Audit';
            $ci = $this->CI;
            $judul = 'Peringatan keamanan Klinik PKP';
            $isi = 'Akses otomatis atau tidak biasa terdeteksi (' . $tipe . '). Buka Jejak Audit untuk rinciannya.';
            register_shutdown_function(function () use ($ci, $audience, $judul, $isi, $url) {
                if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
                elseif (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
                try { $ci->web_push_service->notify($audience, $judul, $isi, $url, 'peringatan-keamanan'); }
                catch (Throwable $e) { /* best effort */ }
            });
            return TRUE;
        } catch (Throwable $e) {
            log_message('error', 'Security_alert: Web Push gagal dijadwalkan: ' . $e->getMessage());
            return FALSE;
        }
    }
}
