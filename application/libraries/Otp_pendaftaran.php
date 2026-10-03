<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kode OTP email untuk pendaftaran. Akun BARU dibuat sesudah kodenya benar, jadi yang tertunda
 * (email, hash sandi, hash kode) hanya hidup di sesi server: tanpa tabel, tanpa akun sampah.
 *
 * Jalur uji: di luar production, alamat berakhiran ".test" (TLD cadangan, tidak pernah
 * terkirim) dan lingkungan tanpa SMTP_HOST TIDAK dikirimi email; kodenya ditulis ke
 * application/cache/otp_uji/<sha1 email>.txt. Membacanya butuh akses berkas server, jadi bukan
 * pintu belakang dari jaringan. Di production tanpa SMTP_HOST pengiriman gagal (fail-closed).
 * Batas yang mengikat ada di config/rate_limits.php (otp_kirim, otp_salah per email tujuan; otp_salah_ip
 * per IP): hitungan di sesi hilang begitu cookie dibuang atau email diganti, jadi hanya jadi lapis pertama.
 * Uji: docs/engineering/uji_otp_pendaftaran.php.
 */
class Otp_pendaftaran {
    const KUNCI      = 'daftar_tertunda';
    const MASA       = 600; // detik kode berlaku
    const MAKS_SALAH = 5;
    const JEDA_AWAL  = 60;  // detik sebelum boleh kirim ulang; berlipat dua tiap pengiriman (60, 120, 240, 480)
    const MAKS_KIRIM = 5;   // per pendaftaran tertunda

    private $CI;

    public function __construct() {
        $this->CI =& get_instance();
    }

    /** Simpan pendaftaran tertunda: email, hash_sandi, is_srp2, nama_perusahaan. */
    public function mulai(array $data) {
        $lama = $this->tertunda();
        $awal = ['hash_kode' => NULL, 'kedaluwarsa' => 0, 'salah' => 0, 'kirim_terakhir' => 0, 'kirim_jumlah' => 0];
        // Email yang sama mempertahankan kode dan hitungannya, supaya mengirim ulang formulir bukan jalan memutar batas.
        $sama = $lama && strtolower($lama['email']) === strtolower($data['email']);
        $this->CI->session->set_userdata(self::KUNCI, $data + ($sama ? array_intersect_key($lama, $awal) : []) + $awal);
    }

    public function tertunda() {
        $t = $this->CI->session->userdata(self::KUNCI);
        return is_array($t) && ! empty($t['email']) ? $t : NULL;
    }

    public function selesai() {
        $this->CI->session->unset_userdata(self::KUNCI);
    }

    /** Detik tersisa sebelum kode baru boleh diminta. Jeda berlipat dua tiap pengiriman, untuk meredam spam. */
    public function sisa_jeda() {
        $t = $this->tertunda();
        $jumlah = $t ? (int) $t['kirim_jumlah'] : 0;
        if ($jumlah < 1) { return 0; }
        return max(0, (int) $t['kirim_terakhir'] + self::JEDA_AWAL * (2 ** ($jumlah - 1)) - time());
    }

    public function batas_tercapai() {
        $t = $this->tertunda();
        return $t && (int) $t['kirim_jumlah'] >= self::MAKS_KIRIM;
    }

    /** Buat kode baru dan kirim. Kembalian TRUE | 'tidak_ada' | 'jeda' | 'batas' | 'gagal'. */
    public function kirim() {
        $t = $this->tertunda();
        if ( ! $t) { return 'tidak_ada'; }
        if ($this->sisa_jeda() > 0) {
            // Kode yang masih berlaku tetap bisa dipakai; yang ditahan hanya pengiriman baru.
            return ! empty($t['hash_kode']) && $t['kedaluwarsa'] > time() ? TRUE : 'jeda';
        }
        if ((int) $t['kirim_jumlah'] >= self::MAKS_KIRIM) { return 'batas'; }
        // Dihitung sebelum mengirim (atomik); penyimpanan gagal juga ditolak (fail-closed).
        $konteks = $this->konteks_laju($t['email']);
        $laju = $this->CI->rate_limiter->hit('otp_kirim', $konteks);
        if (empty($laju['success']) || empty($laju['allowed'])) { return 'batas'; }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        if ( ! $this->antar($t['email'], $kode)) { return 'gagal'; }

        $this->CI->session->set_userdata(self::KUNCI, [
            'hash_kode' => password_hash($kode, PASSWORD_BCRYPT), 'kedaluwarsa' => time() + self::MASA, 'salah' => 0,
            'kirim_terakhir' => time(), 'kirim_jumlah' => (int) $t['kirim_jumlah'] + 1,
        ] + $t);
        return TRUE;
    }

    /** Kembalian 'benar' | 'salah' | 'kedaluwarsa' | 'habis' | 'terkunci' | 'tidak_ada'. Kode benar hanya berlaku sekali. */
    public function periksa($kode) {
        $t = $this->tertunda();
        if ( ! $t || empty($t['hash_kode'])) { return 'tidak_ada'; }
        if ((int) $t['salah'] >= self::MAKS_SALAH) { return 'habis'; }
        if ($t['kedaluwarsa'] <= time()) { return 'kedaluwarsa'; }
        // Batas lintas sesi diperiksa SEBELUM kode dicocokkan: sesudah batas, kode benar pun ditolak.
        $konteks = $this->konteks_laju($t['email']);
        foreach (['otp_salah', 'otp_salah_ip'] as $policy) {
            $laju = $this->CI->rate_limiter->inspect($policy, $konteks);
            if (empty($laju['success']) || empty($laju['allowed'])) { return 'terkunci'; }
        }
        if ( ! password_verify((string) $kode, $t['hash_kode'])) {
            foreach (['otp_salah', 'otp_salah_ip'] as $policy) { $this->CI->rate_limiter->hit($policy, $konteks); }
            $t['salah'] = (int) $t['salah'] + 1;
            $this->CI->session->set_userdata(self::KUNCI, $t);
            return $t['salah'] >= self::MAKS_SALAH ? 'habis' : 'salah';
        }
        $t['hash_kode'] = NULL;
        $this->CI->session->set_userdata(self::KUNCI, $t);
        return 'benar';
    }

    /** Memuat Rate_limiter (panggil sebelum memakainya); konteksnya kunci sha256(email huruf kecil). Policy berdimensi ip mengabaikan kuncinya. */
    private function konteks_laju($email) {
        $this->CI->load->library('Rate_limiter');
        return ['key' => hash('sha256', strtolower((string) $email))];
    }

    private function antar($email, $kode) {
        $smtp = (string) (getenv('SMTP_HOST') ?: '');
        if (ENVIRONMENT !== 'production' && ($smtp === '' || preg_match('/\.test$/i', $email))) {
            $dir = APPPATH . 'cache/otp_uji/';
            if ( ! is_dir($dir)) { @mkdir($dir, 0700, TRUE); }
            return @file_put_contents($dir . sha1(strtolower($email)) . '.txt', $kode) !== FALSE;
        }
        if ($smtp === '') {
            log_message('error', 'OTP pendaftaran: SMTP_HOST kosong, email tidak dapat dikirim.');
            return FALSE;
        }
        $this->CI->load->library('email'); // setelan: config/email.php
        $dari = (string) (getenv('SMTP_FROM') ?: getenv('SMTP_USER'));
        $this->CI->email->clear(TRUE);
        // Gambar ditanam di emailnya (CID), bukan ditautkan: tidak butuh URL publik dan tidak diblokir klien email.
        // Ikon berupa PNG hasil render assets/img/email/*.svg, karena Gmail dan Outlook membuang SVG.
        $gambar = [];
        foreach (['logo' => 'email/logo-jateng.png', 'gembok' => 'email/gembok.png', 'jam' => 'email/jam.png', 'peringatan' => 'email/peringatan.png'] as $nama => $berkas) {
            $jalur = FCPATH . 'assets/img/' . $berkas;
            if (is_file($jalur) && $this->CI->email->attach($jalur, 'inline')) {
                $gambar[$nama] = 'cid:' . $this->CI->email->attachment_cid($jalur);
            }
        }
        $this->CI->email->from($dari, 'Klinik PKP Jawa Tengah');
        $this->CI->email->to($email);
        $this->CI->email->subject('Kode verifikasi pendaftaran Klinik PKP');
        $this->CI->email->set_mailtype('html');
        $this->CI->email->message($this->CI->load->view('email/otp_pendaftaran', ['kode' => $kode, 'menit' => self::MASA / 60, 'gambar' => $gambar], TRUE));
        // Versi teks untuk klien email yang tidak menampilkan HTML.
        $this->CI->email->set_alt_message(
            "Kode verifikasi pendaftaran Anda di Klinik PKP Jawa Tengah:\r\n\r\n    " . $kode . "\r\n\r\n"
            . 'Kode berlaku ' . (self::MASA / 60) . " menit dan hanya untuk satu kali pendaftaran.\r\n"
            . "Jangan berikan kode ini kepada siapa pun, termasuk petugas.\r\n\r\n"
            . "Kalau Anda tidak merasa mendaftar, abaikan email ini.\r\n"
        );
        if ($this->CI->email->send()) { return TRUE; }
        // Tanpa print_debugger(): keluarannya bisa memuat percakapan SMTP.
        log_message('error', 'OTP pendaftaran: pengiriman email gagal lewat ' . $smtp . '.');
        return FALSE;
    }
}
