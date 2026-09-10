<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data - pintu masuk modul ("BANK Data" di sketsa Menu Utama).
 *
 * PUBLIK dan berada di shell portal, bukan di shell admin. Kartu REKAM DATA
 * ada di beranda publik; sebelum ini kelas ini meng-extend
 * `Admin_Kabkota_Controller`, sehingga satu klik dari beranda melempar
 * pengunjung ke layar login dengan pesan "Akses ditolak. Anda bukan Admin
 * Kabupaten/Kota." - menuduh orang yang cuma menekan kartu menu.
 *
 * Yang dijaga tetap dijaga: pengisiannya ada di `Rekam_Perumahan` dan
 * `Rekam_Kawasan`, keduanya `Admin_Kabkota_Controller`. Halaman ini hanya
 * pengarah, jadi tidak ada yang perlu dilindungi di sini.
 *
 * Nol sentuhan ke model. Membuka pintu tidak boleh melahirkan baris di
 * `rd_laporan`; draft lahir di `Rekam_Perumahan::index()`/`Rekam_Kawasan::index()`
 * saja - satu tempat yang menulis, bukan tiga.
 */
class Rekam_Data extends MY_Controller {

    public function index()
    {
        if ($this->has_role('warga') || $this->has_role('pengembang')) {
            $this->session->set_flashdata('error', 'Rekam Data memerlukan akun petugas. Silakan masuk dengan akun yang sesuai.');
            $this->load->view('pages/auth/login', ['recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']);
            return;
        }
        if ( ! $this->is_logged_in()) {
            $this->session->set_flashdata('error', 'Silakan masuk terlebih dahulu untuk membuka Rekam Data.');
            $this->gerbang_login('Rekam_Data');
            return;
        }
        // Sudah masuk sebagai Admin Kab/Kota -> layar sambutan (frame 002).
        // Nama wilayah dan tahun pelaporan disebut di muka SEBELUM menyentuh
        // angka: modul ini ter-scope satu kabupaten dan satu periode, dan
        // kekeliruan termahal di sini adalah mengisi ke wilayah atau tahun
        // yang salah tanpa sadar.
        if ($this->session->userdata('is_logged')
            && $this->session->userdata('role') === 'admin_kabkota') {

            $kabupaten_id = (int) $this->session->userdata('kabupaten_id');
            $this->render_user_dashboard('admin/rekam/sambutan', [
                'title'        => 'Rekam Data',
                'nama_wilayah' => $this->db->where('id', $kabupaten_id)
                    ->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
                'tahun'        => (int) date('Y'),
            ]);
            return;
        }

        // Peran lain dan pengunjung tanpa sesi tetap mendapat halaman portal.
        // JANGAN mengembalikan gerbang Admin_Kabkota_Controller di sini: kartu
        // REKAM DATA ada di beranda publik, dan menolaknya berarti menuduh
        // orang yang cuma menekan satu kartu menu sebagai salah peran.
        $this->render('pages/rekam/pintu', [
            'title' => 'Rekam Data',
        ]);
    }
}
