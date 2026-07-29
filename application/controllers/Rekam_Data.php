<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — pintu masuk modul ("BANK Data" di sketsa Menu Utama).
 *
 * Murni pengarah: dua tombol, Perumahan dan Kawasan. TIDAK menyentuh model
 * dan tidak membuat draft — membuka pintu tidak boleh melahirkan baris. Draft
 * baru lahir di `Rekam_Perumahan::index()`/`Rekam_Kawasan::index()`, dan itu
 * disengaja: satu tempat yang menulis, bukan dua.
 *
 * Kartu REKAM DATA di beranda publik menunjuk ke sini. Pengunjung tanpa sesi
 * diarahkan ke login oleh Admin_Kabkota_Controller, bukan oleh kelas ini.
 *
 * Acuan: sketsa Menu Utama -> BANK Data -> Capaian Perumahan.
 */
class Rekam_Data extends Admin_Kabkota_Controller {

    public function index()
    {
        $this->render_scoped_admin('admin/rekam/pintu', [
            'title'       => 'Rekam Data',
            'scope_label' => $this->db->where('id', $this->my_kabupaten_id)
                ->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
        ]);
    }
}
