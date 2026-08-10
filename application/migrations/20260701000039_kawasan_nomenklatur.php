<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nomenklatur kegiatan kawasan dirinci — butir D2 revisi dinas.
 *
 * Dinas minta "nama program / kegiatan / sub kegiatan / pekerjaan" dirinci jadi
 * isian TERPISAH; dikonfirmasi user 10 Agt 2026: empat isian terpisah benar.
 *
 * `nama_kegiatan` yang lama TIDAK dibuang dan TIDAK dipecah otomatis. Ia tetap
 * kolom wajib dan tetap berisi apa adanya untuk baris lama.
 *
 * MEMECAH TEKS LAMA SECARA OTOMATIS ADALAH GODAAN YANG HARUS DITOLAK. Isinya
 * ditulis bebas oleh 35 kabupaten/kota tanpa aturan pemisah; memecahnya dengan
 * tebakan menghasilkan "program" yang sebenarnya potongan nama pekerjaan, dan
 * kesalahannya menyebar ke rekap yang dibaca sebagai angka resmi. Baris lama
 * membiarkan tiga kolom baru NULL, dan itu jawaban yang jujur: nomenklaturnya
 * memang tidak pernah direkam terpisah.
 *
 * KETIGANYA NULL-able, dan itu disengaja. Menjadikannya wajib akan menolak
 * laporan yang sedang berjalan hari ini — perubahan bentuk isian tidak boleh
 * menghentikan pekerjaan orang. `nama_kegiatan` tetap satu-satunya yang wajib,
 * persis seperti sebelum migrasi ini.
 *
 * ADITIF MURNI: hanya ADD COLUMN, nol UPDATE. Kode lama tetap jalan sesudahnya,
 * jadi urutan amannya "migrasi dulu, kode menyusul" (AGENTS.md §51).
 *
 * Isian masih ketik-bebas. Pilihan berjenjang dari daftar resmi menunggu daftar
 * program–kegiatan–sub kegiatan dari dinas; kolom-kolom ini sudah menyiapkan
 * tempatnya, sehingga nanti yang berubah cuma cara mengisinya, bukan skemanya.
 */
class Migration_Kawasan_nomenklatur extends CI_Migration {

    private $kolom = ['nama_program', 'nama_sub_kegiatan', 'nama_pekerjaan'];

    public function up()
    {
        if ( ! $this->db->table_exists('rd_kawasan_intervensi')) {
            log_message('error', 'Migrasi 039: tabel rd_kawasan_intervensi tidak ada.');
            return;
        }

        /* Diperiksa satu per satu. `add_column()` yang menabrak kolom eksisting
           GAGAL, dan dengan db_debug mati kegagalan itu tidak bersuara sementara
           migrasinya tetap tercatat sukses (riwayat 031). */
        foreach ($this->kolom as $nama) {
            if ($this->db->field_exists($nama, 'rd_kawasan_intervensi')) { continue; }
            $this->dbforge->add_column('rd_kawasan_intervensi', [
                $nama => [
                    'type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE,
                    'comment' => 'Butir D2; NULL = belum direkam terpisah',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach ($this->kolom as $nama) {
            if ($this->db->field_exists($nama, 'rd_kawasan_intervensi')) {
                $this->dbforge->drop_column('rd_kawasan_intervensi', $nama);
            }
        }
    }
}
