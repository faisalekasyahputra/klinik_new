<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Masa berlaku sertifikat SRP2 — dua kolom tanggal, DIKOSONGKAN.
 *
 * Revisi dinas butir B1: daftar pengembang diberi keterangan masa berlaku,
 * mulai dari tanggal terbit sampai tanggal akhir sertifikat.
 *
 * NOL UPDATE, dan itu keputusan user 5 Agt 2026 — bukan kemalasan migrasi.
 * Menghitung otomatis dari `created_at` akan mengisi 67 baris sekaligus dengan
 * tanggal KARANGAN, dan masa berlaku sertifikat adalah hal yang orang percayai:
 * pengembang bisa menunjukkannya ke calon pembeli, dinas bisa memakainya
 * memutuskan. Tanggal yang tidak pernah dilihat manusia tidak boleh lahir dari
 * migrasi. Semua baris lama bernilai NULL sampai admin mengisinya satu per satu.
 *
 * Konteks yang membuat itu bukan sekadar prinsip: dari 67 baris direktori, 66
 * adalah seed literal migrasi 003 yang TIDAK punya pengajuan di belakangnya
 * (hanya 1 `srp2_registrations` berstatus Diterima). Jadi memang tidak ada
 * sumber data untuk di-backfill, bahkan kalau kita mau.
 *
 * ADITIF MURNI: hanya ADD COLUMN. Kode lama tetap jalan sesudah migrasi ini,
 * jadi urutan amannya "migrasi dulu, kode menyusul" (AGENTS.md §51).
 */
class Migration_Srp2_masa_berlaku extends CI_Migration {

    private $kolom = ['sertifikat_terbit', 'sertifikat_berakhir'];

    public function up()
    {
        if ( ! $this->db->table_exists('srp2_certified_developers')) {
            log_message('error', 'Migrasi 037: tabel srp2_certified_developers tidak ada.');
            return;
        }

        /* Diperiksa satu per satu sebelum ditambah. `add_column()` yang menabrak
           kolom eksisting GAGAL, dan dengan `db_debug` mati di production
           kegagalan itu tidak bersuara sementara migrasinya tetap tercatat
           sukses (riwayat 031). Memeriksa lebih dulu membuatnya aman diulang. */
        foreach ($this->kolom as $nama) {
            if ($this->db->field_exists($nama, 'srp2_certified_developers')) { continue; }
            $this->dbforge->add_column('srp2_certified_developers', [
                $nama => [
                    'type' => 'DATE',
                    'null' => TRUE,
                    'comment' => $nama === 'sertifikat_terbit'
                        ? 'Tanggal terbit sertifikat; NULL = belum tercatat'
                        : 'Tanggal akhir masa berlaku; NULL = belum tercatat',
                ],
            ]);
        }
    }

    public function down()
    {
        foreach ($this->kolom as $nama) {
            if ($this->db->field_exists($nama, 'srp2_certified_developers')) {
                $this->dbforge->drop_column('srp2_certified_developers', $nama);
            }
        }
    }
}
