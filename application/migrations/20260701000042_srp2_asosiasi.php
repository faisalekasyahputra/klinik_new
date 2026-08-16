<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar asosiasi pengembang jadi DATA, bukan lagi daftar mati di kode.
 *
 * Permintaan user 14 Agt 2026: "apakah di bagian admin sudah ada CRUD untuk
 * mengelola data asosiasi, jika belum ada buatkan". Sebelumnya kelimanya
 * ditulis di `srp2_daftar_asosiasi()` (application/helpers/srp2_helper.php),
 * sehingga menambah satu asosiasi baru berarti mengubah kode dan menunggu
 * rilis - padahal ini murni data milik dinas.
 *
 * SEED LIMA BARIS, DAN ITU BUKAN MENGARANG - beda dari migrasi 038 (posisi
 * magang) yang sengaja nol seed. Kelima kode ini SUDAH dipakai aplikasi hari
 * ini: formulir pengembang di /akun/profil memvalidasi tepat ke daftar ini,
 * dan migrasi 001 sudah menuliskannya sebagai komentar kolom
 * `srp2_registrations.asosiasi` ('rei|himperra|apersi|pi|lainnya'). Tidak
 * men-seed justru MERUSAK: formulir yang sudah jalan mendadak kehilangan
 * seluruh pilihannya.
 *
 * `kode` yang jadi kunci, BUKAN id. Dua kolom yang sudah ada
 * (`srp2_certified_developers.asosiasi` varchar(100) dan
 * `srp2_registrations.asosiasi` varchar(30)) menyimpan STRING kode itu, bukan
 * angka. Mengubahnya jadi FK berarti migrasi data dua tabel plus menyentuh
 * setiap tempat yang baru saja dirapikan - biaya besar untuk hasil yang sama.
 * Karena itu tabel ini adalah DAFTAR SAH-nya, dan `kode` diperlakukan sebagai
 * nilai yang tidak boleh berubah setelah dibuat (ditegakkan di
 * Admin_Asosiasi::simpan(), lihat komentarnya).
 */
class Migration_Srp2_asosiasi extends CI_Migration {

    const TABEL = 'srp2_asosiasi';

    public function up()
    {
        if ($this->db->table_exists(self::TABEL)) {
            log_message('debug', 'Migrasi 042: ' . self::TABEL . ' sudah ada, dilewati.');
            return;
        }

        /* COLLATION `kode` DIBACA dari kolom yang merujuknya, bukan ditebak -
           peringatan yang sama sudah tertulis di migrasi 038 dan tetap saja
           terlanggar sekali di sini sebelum diperbaiki: menyalin
           utf8mb4_general_ci begitu saja membuat JOIN ke
           srp2_certified_developers.asosiasi gagal errno 1267 (illegal mix of
           collations).

           ⚠️ Kedua kolom perujuk TIDAK SERAGAM, dan itu keadaan LAMA (bukan
           dibuat migrasi ini): `srp2_certified_developers.asosiasi` =
           utf8mb4_unicode_ci, `srp2_registrations.asosiasi` =
           utf8mb4_general_ci. Tidak ada satu pilihan yang cocok untuk
           keduanya, jadi dipilih yang sama dengan DIREKTORI - tabel itu yang
           menyuplai halaman publik dan yang paling mungkin di-JOIN untuk
           rekap. JOIN ke srp2_registrations tetap perlu COLLATE eksplisit.

           Aplikasi sendiri TIDAK terpengaruh: perbandingannya lewat parameter
           (`where('asosiasi', $kode)`), bukan kolom-ke-kolom, dan literal
           mengikuti collation kolomnya. */
        $this->dbforge->add_field([
            'id'   => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'kode' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => FALSE,
                       'collation' => 'utf8mb4_unicode_ci',
                       'comment' => 'Nilai yang tersimpan di kolom asosiasi tabel lain; tidak boleh diubah'],
            'nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => FALSE,
                       'comment' => 'Yang dibaca orang'],
            'aktif'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 1,
                             'comment' => '0 = tidak ditawarkan lagi di formulir, data lama tetap terbaca'],
            'urutan'     => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => TRUE,
                             'null' => FALSE, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => TRUE],
            'updated_at' => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table(self::TABEL, TRUE, [
            'ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci',
        ]);

        /* UNIQUE dipasang terpisah dan kegagalannya DICATAT - `db_debug` mati
           di production, ALTER yang gagal tidak bersuara sementara migrasinya
           tetap tercatat sukses (riwayat 031 & 040 di repo ini). */
        $ok = $this->db->query('ALTER TABLE `' . self::TABEL . '` ADD UNIQUE KEY `uq_srp2_asosiasi_kode` (`kode`)');
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 042: UNIQUE uq_srp2_asosiasi_kode GAGAL dipasang.');
        }

        $sekarang = date('Y-m-d H:i:s');
        $seed = [
            ['kode' => 'rei',      'nama' => 'REI',      'urutan' => 10],
            ['kode' => 'himperra', 'nama' => 'HIMPERRA', 'urutan' => 20],
            ['kode' => 'apersi',   'nama' => 'APERSI',   'urutan' => 30],
            ['kode' => 'pi',       'nama' => 'PI',       'urutan' => 40],
            ['kode' => 'lainnya',  'nama' => 'Lainnya',  'urutan' => 99],
        ];
        foreach ($seed as $baris) {
            $this->db->insert(self::TABEL, $baris + [
                'aktif' => 1, 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ]);
        }
    }

    public function down()
    {
        if ($this->db->table_exists(self::TABEL)) {
            $this->dbforge->drop_table(self::TABEL, TRUE);
        }
    }
}
