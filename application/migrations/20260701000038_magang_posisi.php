<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Posisi/lowongan magang — butir F1 revisi dinas.
 *
 * Dinas minta papan magang menampilkan DAFTAR POSISI (programmer, arsitek,
 * pengelola data, drafter, content creator), bukan nama bidang.
 *
 * POSISI MASUK KE DALAM BIDANG, TIDAK MENGGANTIKANNYA — keputusan user 5 Agt
 * 2026. Seluruh mesin magang bersandar pada bidang: kuota disimpan per bidang,
 * sisa slot dihitung per bidang, formulir memilih bidang, dan 70 cek uji
 * menjaganya. Menggantikan bidang dengan posisi berarti membongkar semua itu
 * untuk perubahan yang pada dasarnya soal TAMPILAN.
 *
 * Karena itu tabel ini KETERANGAN, bukan mesin: `kuota` di sini menerangkan
 * berapa orang yang dicari untuk posisi tersebut, tetapi yang MENGIKAT tetap
 * `kkn_magang_bidang.kuota`. `periksa_slot()` tidak disentuh sama sekali.
 *
 * NOL SEED. Daftar lima posisi yang disebut dinas masih berupa CONTOH dalam
 * kalimat rapat, bukan daftar resmi — menuliskannya sebagai seed membuat
 * tebakan kita terlihat seperti keputusan dinas, dan mahasiswa melamar posisi
 * yang mungkin tidak ada. Admin mengisinya sendiri lewat layar barunya.
 *
 * Charset & tipe kolom FK DIBACA dari kolom induknya (varchar(30) utf8mb4
 * general_ci, InnoDB) — bukan ditebak. Menebak menghasilkan errno 150/1253,
 * dan itu sudah pernah terjadi di proyek ini.
 */
class Migration_Magang_posisi extends CI_Migration {

    const TABEL = 'kkn_magang_posisi';

    public function up()
    {
        if ($this->db->table_exists(self::TABEL)) {
            log_message('debug', 'Migrasi 038: ' . self::TABEL . ' sudah ada, dilewati.');
            return;
        }
        if ( ! $this->db->table_exists('bidang')) {
            log_message('error', 'Migrasi 038: tabel bidang tidak ada, FK tidak bisa dipasang.');
            return;
        }

        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'bidang_kode' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => FALSE],
            'nama_posisi' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => FALSE],
            'keterangan'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE,
                              'comment' => 'Keahlian/tugas singkat; NULL = tidak diisi'],
            'kuota'       => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => TRUE,
                              'null' => FALSE, 'default' => 1,
                              'comment' => 'KETERANGAN saja; yang mengikat kkn_magang_bidang.kuota'],
            'aktif'       => ['type' => 'TINYINT', 'constraint' => 1, 'null' => FALSE, 'default' => 1],
            'urutan'      => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => TRUE,
                              'null' => FALSE, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => TRUE],
            'updated_at'  => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->add_key('bidang_kode');
        $this->dbforge->create_table(self::TABEL, TRUE, [
            'ENGINE' => 'InnoDB', 'CHARACTER SET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci',
        ]);

        /* FK dipasang terpisah, dan kegagalannya DICATAT. `db_debug` mati di
           production: ALTER yang gagal tidak bersuara sementara migrasinya
           tetap tercatat sukses (riwayat 031). Bentuk akhirnya diperiksa
           `Migrate::status()`, bukan dipercaya dari sini. */
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL . '`
             ADD CONSTRAINT `fk_magang_posisi_bidang`
             FOREIGN KEY (`bidang_kode`) REFERENCES `bidang` (`kode`)
             ON DELETE CASCADE ON UPDATE CASCADE'
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 038: FK fk_magang_posisi_bidang GAGAL dipasang.');
        }
    }

    public function down()
    {
        if ($this->db->table_exists(self::TABEL)) {
            $this->dbforge->drop_table(self::TABEL, TRUE);
        }
    }
}
