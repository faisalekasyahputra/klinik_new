<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perintah akun dari CLI (pola Retensi/Migrate). Lewat web dijawab 404.
 *
 *   php index.php akun buat_superadmin                     lalu ketik email, Enter
 *   echo "<email>" | php index.php akun buat_superadmin    (sama, lewat pipa)
 *
 * Email dibaca dari STDIN, BUKAN dari segmen URI: penyaring permitted_uri_chars CodeIgniter menolak
 * "@" di segmen (juga di CLI) sebelum controller berjalan, dan penyaring itu sengaja tidak dilonggarkan.
 *
 * Membuat akun Super Admin TANPA kata sandi (kata_sandi NULL, email_verified_at NULL, google_id NULL).
 * Pemiliknya masuk dengan "Masuk dengan Google" memakai akun Google beralamat email itu:
 * User_model::check_google_user() menautkan akun lewat email terverifikasi Google pada penautan
 * pertama, lalu gerbang kedaluwarsa sandi memaksa membuat sandi di Profil Saya. Aman karena hanya
 * pemilik email yang terbukti di Google yang bisa menautkannya; tidak ada sandi yang bisa ditebak.
 *
 * Email yang sudah punya akun DITOLAK (tidak dinaikkan diam-diam jadi Super Admin): ubah perannya
 * lewat layar Pengguna oleh Super Admin yang ada, atau putuskan manual.
 */
class Akun extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if ( ! $this->input->is_cli_request()) { show_404(); exit; }
    }

    public function buat_superadmin()
    {
        fwrite(STDERR, 'Email Super Admin baru: ');
        $email = strtolower(trim((string) fgets(STDIN)));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            echo "Email tidak valid. Pakai: echo \"<email>\" | php index.php akun buat_superadmin\n";
            exit(1);
        }
        if ($this->db->where('email', $email)->count_all_results('usr_akun') > 0) {
            echo "DITOLAK: email itu sudah punya akun. Akun yang ada tidak dinaikkan jadi Super Admin lewat perintah ini;\n"
                . "ubah perannya dari layar Pengguna oleh Super Admin lain, atau pakai email lain.\n";
            exit(1);
        }

        $this->load->model('auth_model');
        $sekarang = date('Y-m-d H:i:s');
        $ok = $this->db->insert('usr_akun', [
            'email' => $email,
            'nama' => 'Super Admin',
            'nama_pengguna' => $this->auth_model->generate_unique_username(strstr($email, '@', TRUE)),
            'peran' => 'admin',
            'status' => 'active',
            'profil_lengkap' => 1,
            'kata_sandi' => NULL,
            'google_id' => NULL,
            'email_verified_at' => NULL,
            // Kedaluwarsa sekarang: begitu tertaut Google, gerbang sandi memaksa membuat sandi pertama.
            'sandi_diganti_at' => NULL,
            'sandi_kedaluwarsa_at' => $sekarang,
            'created_at' => $sekarang,
        ]);
        $id = $ok ? (int) $this->db->insert_id() : 0;
        if ($id < 1) {
            echo "GAGAL: akun belum dibuat (" . ($this->db->error()['message'] ?? 'galat DB') . ").\n";
            exit(1);
        }
        $this->catat_audit('superadmin_dibuat_cli', 'Akun Super Admin tanpa sandi dibuat lewat CLI; ditautkan pemiliknya lewat Google',
            'usr_akun', (string) $id, ['email' => $email]);

        echo "Akun Super Admin dibuat (id $id) tanpa kata sandi.\n"
            . "Langkah berikutnya: pemilik email membuka halaman Masuk, memilih \"Masuk dengan Google\" dengan akun\n"
            . "Google beralamat email itu, lalu membuat kata sandi di Profil Saya.\n";
    }
}
