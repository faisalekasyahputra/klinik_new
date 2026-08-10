<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Satu NIK satu akun warga. Butir 8 dan 21 putaran 2.
 *
 * Kolom `usr_users.nik` dan `usr_users.nik_lookup_hash` SUDAH ADA sejak lama,
 * tetapi tidak pernah ada isian NIK di halaman Profil Saya (butir 21) dan
 * TIDAK ADA INDEKS UNIK di sidik pencariannya. Tanpa indeks itu, dua akun bisa
 * mengaku NIK yang sama, dan justru itulah yang diminta butir 8: NIK adalah
 * kunci identitas warga.
 *
 * MENGAPA INDEKS, BUKAN PEMERIKSAAN DI KODE SAJA. Pemeriksaan "sudah dipakai
 * belum" di aplikasi selalu punya celah waktu: dua permintaan yang datang
 * bersamaan sama-sama membaca "belum dipakai", lalu dua-duanya menyimpan.
 * Indeks unik menutup celah itu di lapisan yang tidak bisa dibalap. Kodenya
 * tetap memeriksa lebih dulu, tapi supaya pesannya ramah, bukan supaya aman.
 *
 * NULL dibiarkan berulang. MySQL tidak menganggap dua NULL kembar, dan itu yang
 * membuat 27 akun tanpa NIK tetap sah. Hanya yang MENGISI yang terikat.
 *
 * Diperiksa duplikatnya lebih dulu, dan kalau ada, migrasi ini BERHENTI tanpa
 * memasang indeks. Memaksa indeks unik ke atas data yang sudah bentrok akan
 * gagal di tengah jalan; dengan `db_debug` mati di production kegagalan itu
 * tidak bersuara sementara migrasinya tetap tercatat sukses (riwayat 031).
 * Berhenti sambil mencatat alasannya jauh lebih baik daripada sukses palsu.
 */
class Migration_Nik_akun_unik extends CI_Migration {

    const INDEKS = 'uq_usr_nik_lookup';

    public function up()
    {
        if ( ! $this->db->table_exists('usr_users')
            || ! $this->db->field_exists('nik_lookup_hash', 'usr_users')) {
            log_message('error', 'Migrasi 041: usr_users.nik_lookup_hash tidak ada.');
            return;
        }

        $sudah = (int) $this->db->query(
            "SELECT COUNT(*) n FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usr_users'
                AND INDEX_NAME = ?", [self::INDEKS]
        )->row('n');
        if ($sudah) { return; }

        $bentrok = $this->db->query(
            'SELECT nik_lookup_hash h, COUNT(*) n FROM usr_users
              WHERE nik_lookup_hash IS NOT NULL AND nik_lookup_hash <> ""
              GROUP BY h HAVING n > 1'
        )->num_rows();

        if ($bentrok > 0) {
            log_message('error', 'Migrasi 041 BERHENTI: ada ' . $bentrok
                . ' NIK dipakai lebih dari satu akun. Selesaikan dulu sebelum indeks unik dipasang.');
            return;
        }

        $ok = $this->db->query(
            'ALTER TABLE `usr_users` ADD UNIQUE KEY `' . self::INDEKS . '` (`nik_lookup_hash`)'
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 041: indeks ' . self::INDEKS . ' GAGAL dipasang.');
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `usr_users` DROP INDEX `' . self::INDEKS . '`');
    }
}
