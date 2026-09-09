<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serah Terima PSU (Prasarana, Sarana, Utilitas) perumahan - halaman publik.
 *
 * Mengaktifkan kartu "PSU" di beranda yang sebelumnya "Segera Hadir"
 * (pages/home/awal.php, permintaan user 14 Agt 2026). Datanya dikelola lewat
 * Admin_Psu - lihat migrasi 043 untuk rancangan skemanya.
 *
 * PUBLIK, TANPA LOGIN - sengaja, sama seperti Pengembang::sertifikasi()
 * (Direktori SRP2 publik). Status serah terima PSU bukan data pribadi warga;
 * ini status administratif proyek perumahan, memang dimaksudkan terbuka
 * supaya warga tahu perumahan mana yang PSU-nya sudah/belum diserahkan ke
 * pemda sebelum membeli unit di situ.
 */
class Psu extends MY_Controller {

    public function index()
    {
        $data['judul'] = '';

        /* Hanya baris status_aktif=1 yang tampil - kolom yang SAMA dipakai
           Admin_Srp2 utk "Tampilkan di publik" (srp2_certified_developers).
           Label status & asosiasi dari SATU sumber yang sama dengan admin
           (psu_label_status(), srp2_label_asosiasi()) supaya kalimat yang
           dibaca warga tidak pernah menyimpang dari yang diketik admin. */
        $this->load->helper('srp2');
        $this->load->helper('psu');
        $this->db->from('psu_serah_terima')->where('status_aktif', 1);
        $data['daftar_psu'] = $this->db->order_by('nama_perumahan', 'ASC')->get()->result();

        $kabupaten = $this->db->select('id, nama')->get('kabupaten')->result();
        $data['nama_kabupaten'] = [];
        foreach ($kabupaten as $kb) { $data['nama_kabupaten'][(int) $kb->id] = $kb->nama; }

        $this->render('pages/psu/index', $data);
    }
}
