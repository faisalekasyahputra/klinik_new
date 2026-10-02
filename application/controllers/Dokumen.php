<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Viewer dokumen PDF bergaya flipbook - permintaan user 23 Agt 2026,
 * kartu "Data Lainnya" di tab Bank Data (sebelumnya "Segera Hadir",
 * nonaktif) sekarang mengarah ke sini.
 *
 * SATU dokumen untuk sekarang, jalur berkasnya HARDCODE - bukan
 * arsitektur "banyak dokumen" karena belum ada kebutuhan untuk itu.
 * Kalau nanti ada lebih dari satu dokumen, method ini yang pertama
 * perlu diperluas (terima parameter/slug), bukan ditambah controller baru.
 */
class Dokumen extends Public_Controller
{
    public function index()
    {
        $path = 'assets/dokumen/contoh_bank_data.pdf';

        /* CONTOH TAMPILAN 23 Agt 2026 - dokumen resmi BELUM diterima dari
           user ("File pdf nya bisa kamu minta nanti"). File ini SENGAJA
           dibuat (6 halaman, watermark "CONTOH TAMPILAN" di tiap halaman)
           supaya seluruh navigasi flipbook (halaman pertama/sebelum/
           sesudah/terakhir) bisa dicoba SEKARANG, bukan dibiarkan kosong
           menunggu berkas asli. Saat dokumen resmi datang: TIMPA file di
           $path ini (nama file sama) - tidak perlu ubah kode apa pun di
           sini. Ganti nama file lain, ganti juga baris di atas.
           $data['contoh'] mengontrol notice kuning di view; hapus
           baris ini (biarkan default FALSE) begitu file asli terpasang. */
        $data['judul']  = 'Bank Data - Dokumen';
        $data['pdf_url'] = base_url($path);
        $data['contoh'] = TRUE;

        $this->render('pages/data_spasial/dokumen', $data);
    }

    /** Dokumen Bank Data unggahan admin (migrasi 063). Hanya yang aktif; id lain 404. */
    public function lihat($id = NULL)
    {
        if ( ! ctype_digit((string) $id)) { show_404(); }
        $row = $this->db->get_where('sf_bank_data_dokumen', ['id' => (int) $id, 'aktif' => 1])->row();
        if ( ! $row || ! is_file(FCPATH . $row->berkas)) { show_404(); }
        $data['judul']   = 'Bank Data - ' . $row->judul;
        $data['pdf_url'] = base_url($row->berkas);
        $data['contoh']  = FALSE;
        $this->render('pages/data_spasial/dokumen', $data);
    }
}
