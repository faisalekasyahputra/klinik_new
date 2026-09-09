<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboard KKN universitas - permintaan user 21 Agt 2026, menggantikan
 * formulir pendaftaran KKN satu-kali (KemitraanPortal/daftar/kkn) yang
 * disederhanakan lebih dulu di sesi yang sama. Satu akun universitas
 * sekarang mengelola BANYAK KKN dari waktu ke waktu lewat dashboard, bukan
 * mendaftar sekali.
 *
 * DUA PERUBAHAN, ADITIF MURNI:
 *
 * 1. `kkn_magang_pendaftaran.file_surat_simperum` - surat permohonan
 *    mendapatkan akun SIMPERUM, TERPISAH dari `file_surat_pengantar` yang
 *    sekarang bermakna "surat permohonan menjadi mitra". Universitas
 *    mengunggah DUA surat berbeda untuk satu KKN, admin meninjau keduanya
 *    sebelum menyetujui - satu kolom lama tidak cukup untuk dua dokumen
 *    dengan tujuan berbeda.
 *
 * 2. Tabel baru `kkn_peserta` - roster mahasiswa per KKN, diisi dari unggahan
 *    Excel (NIM + Nama), bukan diketik satu-satu. Terikat ke
 *    `kkn_magang_pendaftaran.id` (SATU baris KKN = SATU periode/batch),
 *    bukan ke `usr_users.id` langsung - "milik universitas mana" diturunkan
 *    LEWAT baris KKN-nya (`kkn_magang_pendaftaran.user_id`), sama seperti
 *    berkas mengikuti baris pendaftaran, bukan akun. "Jumlah Peserta" di
 *    tabel dashboard DIHITUNG dari COUNT baris ini, TIDAK disimpan sebagai
 *    kolom tersendiri - angka yang disimpan terpisah dari sumbernya cepat
 *    atau lambat menyimpang begitu ada baris peserta yang dihapus/ditambah
 *    di luar jalur normal.
 *
 * Kolom `divisi_atau_tema`, `periode_mulai`, `periode_selesai` untuk KKN
 * TIDAK dihidupkan lagi lewat migrasi ini - keduanya kolom LAMA yang sudah
 * ada sejak awal (bukan dihapus, cuma dilepas dari formulir sesi lalu),
 * dipakai ULANG di sini sebagai "Keterangan" dan "Periode" batch KKN. Tidak
 * ada ALTER yang diperlukan untuk itu.
 *
 * ADITIF MURNI: hanya ADD COLUMN + CREATE TABLE. Kode lama (jalur Magang,
 * dan baris KKN lama yang sudah ada) tetap jalan sesudah migrasi ini -
 * urutan amannya "migrasi dulu, kode menyusul" (AGENTS.md §51).
 */
class Migration_Kkn_dashboard_universitas extends CI_Migration {

    const TABEL_INDUK   = 'kkn_magang_pendaftaran';
    const TABEL_PESERTA = 'kkn_peserta';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL_INDUK)) {
            log_message('error', 'Migrasi 044: tabel ' . self::TABEL_INDUK . ' tidak ada.');
            return;
        }

        // Diperiksa dulu, bukan diasumsikan kosong - add_column() yang
        // menabrak kolom eksisting gagal SENYAP saat db_debug mati di
        // production (riwayat 031), migrasinya tetap tercatat sukses.
        if ( ! $this->db->field_exists('file_surat_simperum', self::TABEL_INDUK)) {
            $this->dbforge->add_column(self::TABEL_INDUK, [
                'file_surat_simperum' => [
                    'type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE,
                    'after' => 'file_surat_pengantar',
                    'comment' => 'Surat permohonan akun SIMPERUM (KKN). NULL = belum diunggah.',
                ],
            ]);
        }

        if ($this->db->table_exists(self::TABEL_PESERTA)) {
            log_message('debug', 'Migrasi 044: ' . self::TABEL_PESERTA . ' sudah ada, dilewati.');
            return;
        }

        $this->dbforge->add_field([
            'id'             => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            // FK ke SATU baris KKN (bukan ke usr_users langsung) - lihat
            // alasan lengkap di komentar kepala berkas.
            'pendaftaran_id' => ['type' => 'INT', 'unsigned' => TRUE, 'null' => FALSE],
            'nim'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => FALSE],
            'nama'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => FALSE],
            'created_at'     => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('pendaftaran_id');
        $this->dbforge->create_table(self::TABEL_PESERTA, TRUE, [
            'ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci',
        ]);

        /* FK WAJIB (bukan opsional seperti pengembang_id di migrasi 043) -
           roster peserta TIDAK PUNYA ARTI tanpa induk KKN-nya, beda dari PSU
           yang boleh menunjuk pengembang yang belum tercatat. ON DELETE
           CASCADE: menghapus baris KKN (Admin_Kemitraan::hapus()) ikut
           menyapu roster pesertanya - tidak ada NIM/nama mahasiswa yang
           tertinggal yatim tanpa satu pun KKN yang menaunginya. */
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL_PESERTA . '`
             ADD CONSTRAINT `fk_kkn_peserta_pendaftaran` FOREIGN KEY (`pendaftaran_id`)
             REFERENCES `' . self::TABEL_INDUK . '` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 044: FK fk_kkn_peserta_pendaftaran GAGAL dipasang.');
        }
    }

    public function down()
    {
        if ($this->db->table_exists(self::TABEL_PESERTA)) {
            $this->dbforge->drop_table(self::TABEL_PESERTA, TRUE);
        }
        if ($this->db->table_exists(self::TABEL_INDUK)
            && $this->db->field_exists('file_surat_simperum', self::TABEL_INDUK)) {
            $this->dbforge->drop_column(self::TABEL_INDUK, 'file_surat_simperum');
        }
    }
}
