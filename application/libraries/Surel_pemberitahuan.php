<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email pemberitahuan umum (6 Okt 2026), dipakai pertama kali untuk hasil permintaan reset dan klaim NIK.
 * Saluran SMTP, pengirim, dan gaya sama dengan OTP pendaftaran (Otp_pendaftaran::antar): tata letak tabel
 * dengan gaya inline, logo ditanam (CID). Isinya TIDAK pernah memuat NIK atau data pribadi lain.
 *
 * Di luar production, alamat *.test atau lingkungan tanpa SMTP_HOST TIDAK dikirimi email; isinya ditulis
 * ke application/cache/surel_uji/<sha1 email>.json (dibaca suite uji). Kegagalan kirim hanya dicatat di
 * log: email ini pelengkap, keputusan yang memicunya sudah tersimpan.
 */
class Surel_pemberitahuan {

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /**
     * @param string $email   tujuan
     * @param string $subjek  baris subjek
     * @param string $judul   judul di badan email
     * @param array  $paragraf kalimat-kalimat isi (teks biasa)
     * @param string $catatan  catatan petugas (opsional, ditampilkan dalam kotak)
     * @param array  $tombol   ['label' => ..., 'rute' => 'akun/profil'] (opsional)
     * @return bool terkirim (atau tertulis di mode uji)
     */
    public function kirim($email, $subjek, $judul, array $paragraf, $catatan = '', array $tombol = []) {
        $email = trim((string) $email);
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) { return FALSE; }
        $tautan = ! empty($tombol['rute']) ? base_url($tombol['rute']) : '';
        $smtp = (string) (getenv('SMTP_HOST') ?: '');

        if (ENVIRONMENT !== 'production' && ($smtp === '' || preg_match('/\.test$/i', $email))) {
            $dir = APPPATH . 'cache/surel_uji/';
            if ( ! is_dir($dir)) { @mkdir($dir, 0700, TRUE); }
            $berkas = $dir . sha1(strtolower($email)) . '.json';
            $daftar = json_decode((string) @file_get_contents($berkas), TRUE) ?: [];
            $daftar[] = ['subjek' => $subjek, 'judul' => $judul, 'paragraf' => $paragraf, 'catatan' => (string) $catatan,
                'tautan' => $tautan, 'waktu' => date('Y-m-d H:i:s')];
            return @file_put_contents($berkas, json_encode($daftar, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== FALSE;
        }
        if ($smtp === '') {
            log_message('error', 'Surel pemberitahuan: SMTP_HOST kosong, email tidak dapat dikirim.');
            return FALSE;
        }

        $this->CI->load->library('email'); // setelan: config/email.php
        $this->CI->email->clear(TRUE);
        $gambar = [];
        $logo = FCPATH . 'assets/img/email/logo-jateng.png';
        if (is_file($logo) && $this->CI->email->attach($logo, 'inline')) {
            $gambar['logo'] = 'cid:' . $this->CI->email->attachment_cid($logo);
        }
        $this->CI->email->from((string) (getenv('SMTP_FROM') ?: getenv('SMTP_USER')), 'Klinik PKP Jawa Tengah');
        $this->CI->email->to($email);
        $this->CI->email->subject($subjek);
        $this->CI->email->set_mailtype('html');
        $this->CI->email->message($this->CI->load->view('email/pemberitahuan', [
            'judul' => $judul, 'paragraf' => $paragraf, 'catatan' => (string) $catatan,
            'tombol_label' => (string) ($tombol['label'] ?? ''), 'tautan' => $tautan, 'gambar' => $gambar,
        ], TRUE));
        $this->CI->email->set_alt_message($judul . "\r\n\r\n" . implode("\r\n\r\n", $paragraf)
            . ((string) $catatan !== '' ? "\r\n\r\nCatatan petugas: " . $catatan : '')
            . ($tautan !== '' ? "\r\n\r\n" . ($tombol['label'] ?? 'Buka') . ': ' . $tautan : '')
            . "\r\n\r\nEmail ini dikirim otomatis, mohon tidak dibalas.\r\n");
        if ($this->CI->email->send()) { return TRUE; }
        // Tanpa print_debugger(): keluarannya bisa memuat percakapan SMTP.
        log_message('error', 'Surel pemberitahuan: pengiriman gagal lewat ' . $smtp . '.');
        return FALSE;
    }
}
