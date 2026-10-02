<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fase 3 normalisasi langkah 3 (keputusan pemilik produk 2 Okt 2026): kunci asing yang selama
 * ini hanya diandaikan kode, plus perapian indeks usr_users.
 *
 * PRA-CEK 2 Okt 2026 (lokal dan production, hanya hitungan): nol baris yatim di ke-13 kolom,
 * nol string kosong di kolom asosiasi/bidang, semua nilai asosiasi berupa KODE (bukan nama),
 * panjang asosiasi terpanjang 3 karakter, nol username kembar. up() mengulang pra-cek ini dan
 * MENOLAK (melempar, sebelum DDL apa pun) bila ada yatim/string kosong/kembar; tidak ada data
 * yang diubah diam-diam, jadi down() tidak perlu memulihkan nilai.
 *
 * ATURAN ON DELETE per FK (dari cara kode menghapus):
 * - forum_komentar.id_diskusi CASCADE: komentar tak bermakna tanpa topiknya; kode hanya
 *   menghapus topik lewat soft delete (is_deleted), hard delete hanya di skrip uji yang
 *   memang menghapus komentar dulu. Sama dengan fk_jt_diskusi (forum_janji_temu).
 * - forum_komentar.user_id, forum_diskusi.user_id SET NULL: isi forum bertahan sesudah akun
 *   dihapus; User_model::delete_user_account() sudah menganonimkan (user_id NULL, nama/surel
 *   disamarkan) sebelum DELETE, FK ini penjaga cadangannya.
 * - forum_likes.user_id CASCADE: delete_user_account() memang menghapus tanda suka akun.
 * - usr_users.kabupaten_id, usr_users.bidang_kode RESTRICT: kolom cakupan wewenang staf;
 *   menjadikannya NULL diam-diam mengubah arti hak akses. Tidak ada kode yang menghapus baris
 *   kabupaten/bidang (Admin_Struktur hanya mengganti nama).
 * - aduan.bidang SET NULL: NULL sudah berarti "belum ditriase", jadi aduan kembali ke antrean
 *   triase, bukan hilang. Sama dengan fk_kkn_bidang.
 * - srp2_certified_developers.kabupaten_id SET NULL: NULL = "belum tercatat"; sama dengan
 *   fk_psu_kabupaten.
 * - asosiasi (srp2_registrations, srp2_certified_developers, psu_serah_terima) RESTRICT:
 *   Admin_Asosiasi::hapus() sudah menolak asosiasi yang masih dipakai; FK ini penjaganya di
 *   DB. Kode tidak bisa diubah sesudah dibuat, jadi ON UPDATE tidak dipakai.
 *   srp2_certified_developers.asosiasi diselaraskan VARCHAR(100) -> VARCHAR(30) (= panjang
 *   srp2_asosiasi.kode; pra-cek: terpanjang 3 karakter). Komentar kolomnya ("ketik bebas")
 *   sudah basi sejak 14 Agt 2026 dan diganti.
 * - sf_data_simperum.kabupaten_id SET NULL: tipenya INT(11) SIGNED diselaraskan ke
 *   INT(10) UNSIGNED milik kabupaten.id dulu (errno 150 kalau tidak). Cermin data SIMPERUM.
 * - sf_data_simperum.snapshot_id SET NULL: Penyapu_retensi menghapus snapshot kedaluwarsa
 *   dengan satu DELETE; RESTRICT akan menggagalkan seluruh putaran sapu, CASCADE akan
 *   menghapus cermin milik akun. SET NULL = cermin tetap, rujukan asalnya lepas. Sama dengan
 *   fk_sf_assessments_snapshot.
 *
 * INDEKS:
 * - `idx_users_email` DIBUANG: UNIQUE kembar persis `email`. Tidak satu pun kode/migrasi
 *   menyebut nama mana pun; `email` dipertahankan (nama bawaan kolomnya).
 * - UNIQUE `uq_usr_users_username`: kode sudah mengecek kembar sebelum menulis (Auth,
 *   Pengaturan, generate_unique_username); ini penjaga balapan.
 * - INDEX email_token (Auth_model mencari token), role (hitungan/notifikasi per peran),
 *   kabupaten_id, bidang_kode (dipakai FK sekaligus filter cakupan), forum_komentar.id_diskusi.
 * - usr_users.google_id TIDAK diindeks: tidak pernah dipakai di WHERE (login Google mencari
 *   lewat surel). Tabel panas lain (sys_jejak_audit aksi/pelaku/waktu, sf_housing_queue
 *   kabupaten/status, aduan bidang/status, kkn_magang_pendaftaran status/bidang, rd_laporan
 *   periode) sudah punya indeks yang menutup filternya; tidak ditambah.
 *
 * down() melepas persis yang dipasang up(), memasang lagi `idx_users_email`, dan mengembalikan
 * dua tipe kolom (asosiasi VARCHAR(100) + komentar lama, kabupaten_id INT(11) signed).
 * Semua langkah idempoten: migrasi yang terputus aman dijalankan ulang.
 */
class Migration_Fk_indeks_integritas extends CI_Migration {

    /** nama FK => [tabel anak, kolom, tabel induk, kolom induk, ON DELETE]. Dibaca juga oleh Migrate::status() dan uji. */
    const FK = [
        'fk_forum_komentar_diskusi'      => ['forum_komentar', 'id_diskusi', 'forum_diskusi', 'id_diskusi', 'CASCADE'],
        'fk_forum_komentar_user'         => ['forum_komentar', 'user_id', 'usr_users', 'id', 'SET NULL'],
        'fk_forum_diskusi_user'          => ['forum_diskusi', 'user_id', 'usr_users', 'id', 'SET NULL'],
        'fk_forum_likes_user'            => ['forum_likes', 'user_id', 'usr_users', 'id', 'CASCADE'],
        'fk_usr_users_kabupaten'         => ['usr_users', 'kabupaten_id', 'kabupaten', 'id', 'RESTRICT'],
        'fk_usr_users_bidang'            => ['usr_users', 'bidang_kode', 'bidang', 'kode', 'RESTRICT'],
        'fk_aduan_bidang'                => ['aduan', 'bidang', 'bidang', 'kode', 'SET NULL'],
        'fk_srp2_direktori_kabupaten'    => ['srp2_certified_developers', 'kabupaten_id', 'kabupaten', 'id', 'SET NULL'],
        'fk_srp2_direktori_asosiasi'     => ['srp2_certified_developers', 'asosiasi', 'srp2_asosiasi', 'kode', 'RESTRICT'],
        'fk_srp2_registrations_asosiasi' => ['srp2_registrations', 'asosiasi', 'srp2_asosiasi', 'kode', 'RESTRICT'],
        'fk_psu_asosiasi'                => ['psu_serah_terima', 'asosiasi', 'srp2_asosiasi', 'kode', 'RESTRICT'],
        'fk_sf_data_simperum_kabupaten'  => ['sf_data_simperum', 'kabupaten_id', 'kabupaten', 'id', 'SET NULL'],
        'fk_sf_data_simperum_snapshot'   => ['sf_data_simperum', 'snapshot_id', 'sf_rekaman_simperum', 'id', 'SET NULL'],
    ];

    /** Indeks baru: nama => [tabel, definisi]. Yang lain dipakai ulang oleh FK (lihat kepala berkas). */
    const INDEKS = [
        'idx_forum_komentar_diskusi'      => ['forum_komentar', 'KEY `%s` (`id_diskusi`)'],
        'uq_usr_users_username'           => ['usr_users', 'UNIQUE KEY `%s` (`username`)'],
        'idx_usr_users_email_token'       => ['usr_users', 'KEY `%s` (`email_token`)'],
        'idx_usr_users_role'              => ['usr_users', 'KEY `%s` (`role`)'],
        'idx_usr_users_kabupaten'         => ['usr_users', 'KEY `%s` (`kabupaten_id`)'],
        'idx_usr_users_bidang'            => ['usr_users', 'KEY `%s` (`bidang_kode`)'],
        'idx_srp2_direktori_kabupaten'    => ['srp2_certified_developers', 'KEY `%s` (`kabupaten_id`)'],
        'idx_srp2_direktori_asosiasi'     => ['srp2_certified_developers', 'KEY `%s` (`asosiasi`)'],
        'idx_srp2_registrations_asosiasi' => ['srp2_registrations', 'KEY `%s` (`asosiasi`)'],
        'idx_psu_asosiasi'                => ['psu_serah_terima', 'KEY `%s` (`asosiasi`)'],
        'idx_sf_data_simperum_snapshot'   => ['sf_data_simperum', 'KEY `%s` (`snapshot_id`)'],
    ];

    /** Kolom yang tipenya diubah: [tabel, kolom, definisi up, definisi down]. */
    const TIPE = [
        ['srp2_certified_developers', 'asosiasi',
            "VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kode srp2_asosiasi.kode (FK, migrasi 069)'",
            "VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Butir 12; ketik bebas sampai dinas mengirim daftar resmi'"],
        ['sf_data_simperum', 'kabupaten_id', 'INT(10) UNSIGNED DEFAULT NULL', 'INT(11) DEFAULT NULL'],
    ];

    const INDEKS_KEMBAR = ['usr_users', 'idx_users_email', 'UNIQUE KEY `%s` (`email`)'];

    public function up()
    {
        $this->sesi(function () {
            $this->pra_cek();
            [$tabel, $nama] = self::INDEKS_KEMBAR;
            if ($this->ada_indeks($tabel, $nama)) { $this->wajib('ALTER TABLE `' . $tabel . '` DROP INDEX `' . $nama . '`'); }
            foreach (self::TIPE as [$t, $k, $def]) { $this->wajib('ALTER TABLE `' . $t . '` MODIFY `' . $k . '` ' . $def); }
            foreach (self::INDEKS as $nama => [$t, $def]) {
                if ( ! $this->ada_indeks($t, $nama)) { $this->wajib('ALTER TABLE `' . $t . '` ADD ' . sprintf($def, $nama)); }
            }
            foreach (self::FK as $nama => [$t, $k, $induk, $ki, $hapus]) {
                if ( ! $this->ada_fk($t, $nama)) {
                    $this->wajib('ALTER TABLE `' . $t . '` ADD CONSTRAINT `' . $nama . '` FOREIGN KEY (`' . $k . '`) REFERENCES `'
                        . $induk . '` (`' . $ki . '`) ON DELETE ' . $hapus);
                }
            }
        });
    }

    public function down()
    {
        $this->sesi(function () {
            foreach (self::FK as $nama => [$t]) {
                if ($this->ada_fk($t, $nama)) { $this->wajib('ALTER TABLE `' . $t . '` DROP FOREIGN KEY `' . $nama . '`'); }
            }
            foreach (self::INDEKS as $nama => [$t]) {
                if ($this->ada_indeks($t, $nama)) { $this->wajib('ALTER TABLE `' . $t . '` DROP INDEX `' . $nama . '`'); }
            }
            foreach (self::TIPE as [$t, $k, , $def]) { $this->wajib('ALTER TABLE `' . $t . '` MODIFY `' . $k . '` ' . $def); }
            [$tabel, $nama, $def] = self::INDEKS_KEMBAR;
            if ( ! $this->ada_indeks($tabel, $nama)) { $this->wajib('ALTER TABLE `' . $tabel . '` ADD ' . sprintf($def, $nama)); }
        });
    }

    /** Hitungan yang wajib nol sebelum DDL; hanya angka yang disebut di pesan galat. */
    private function pra_cek()
    {
        $masalah = [];
        foreach (self::FK as $nama => [$t, $k, $induk, $ki]) {
            $n = (int) $this->db->query("SELECT COUNT(*) n FROM `$t` a LEFT JOIN `$induk` b ON b.`$ki` = a.`$k`
                WHERE a.`$k` IS NOT NULL AND b.`$ki` IS NULL")->row('n');
            if ($n) { $masalah[] = "$t.$k: $n baris yatim/kosong"; }
        }
        $n = (int) $this->db->query('SELECT COUNT(*) n FROM (SELECT username FROM usr_users WHERE username IS NOT NULL
            GROUP BY username HAVING COUNT(*) > 1) x')->row('n');
        if ($n) { $masalah[] = "usr_users.username: $n grup kembar"; }
        $n = (int) $this->db->query('SELECT COUNT(*) n FROM srp2_certified_developers WHERE CHAR_LENGTH(asosiasi) > 30')->row('n');
        if ($n) { $masalah[] = "srp2_certified_developers.asosiasi: $n baris lebih dari 30 karakter"; }
        if ($masalah) {
            throw new RuntimeException('Migrasi 069 ditolak, rapikan datanya dulu: ' . implode('; ', $masalah));
        }
    }

    /** db_debug mati + STRICT_ALL_TABLES (MODIFY yang memotong data GAGAL), dipulihkan walau gagal. */
    private function sesi(callable $kerja)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->wajib('SET @kpkp_mode_069 = @@SESSION.sql_mode');
        try {
            $this->wajib("SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@SESSION.sql_mode, ''), 'STRICT_ALL_TABLES')");
            $kerja();
        } finally {
            $this->db->query('SET SESSION sql_mode = @kpkp_mode_069');
            $this->db->db_debug = $debug;
        }
    }

    private function ada_indeks($tabel, $nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$tabel, $nama])->row('n') > 0;
    }

    private function ada_fk($tabel, $nama)
    {
        return (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$tabel, $nama])->row('n') > 0;
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 069 gagal: ' . $sql . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
