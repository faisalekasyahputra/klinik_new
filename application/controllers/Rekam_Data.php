<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — pintu masuk modul ("BANK Data" di sketsa Menu Utama).
 *
 * PUBLIK dan berada di shell portal, bukan di shell admin. Kartu REKAM DATA
 * ada di beranda publik; sebelum ini kelas ini meng-extend
 * `Admin_Kabkota_Controller`, sehingga satu klik dari beranda melempar
 * pengunjung ke layar login dengan pesan "Akses ditolak. Anda bukan Admin
 * Kabupaten/Kota." — menuduh orang yang cuma menekan kartu menu.
 *
 * Yang dijaga tetap dijaga: pengisiannya ada di `Rekam_Perumahan` dan
 * `Rekam_Kawasan`, keduanya `Admin_Kabkota_Controller`. Halaman ini hanya
 * pengarah, jadi tidak ada yang perlu dilindungi di sini.
 *
 * Nol sentuhan ke model. Membuka pintu tidak boleh melahirkan baris di
 * `rd_laporan`; draft lahir di `Rekam_Perumahan::index()`/`Rekam_Kawasan::index()`
 * saja — satu tempat yang menulis, bukan tiga.
 */
class Rekam_Data extends MY_Controller {

    public function index()
    {
        $this->render('pages/rekam/pintu', [
            'title' => 'Rekam Data',
        ]);
    }
}
