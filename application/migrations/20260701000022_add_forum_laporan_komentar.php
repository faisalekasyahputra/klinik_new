<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * B3 — ledger pelapor komentar forum.
 *
 * Sebelum ini `Forum_model::report_komentar()` hanya menaikkan `report_count`
 * tanpa mencatat SIAPA yang melapor, sehingga lima kali klik dari satu orang
 * bernilai sama dengan lima orang berbeda. Endpoint-nya pun anonim, jadi satu
 * pengunjung bisa menyensor komentar siapa pun sendirian.
 *
 * Bentuknya menyalin `forum_likes` — "satu aksi per pengguna per objek" yang
 * sudah terbukti dipakai `Forum_model::toggle_like()`, bukan pola baru.
 *
 * KEDUA kolom FK di sini INT **SIGNED**, bukan UNSIGNED: `usr_users.id`
 * maupun `forum_komentar.id_komentar` sama-sama `int(11)` signed. Percobaan
 * pertama migrasi ini memakai UNSIGNED untuk `id_komentar` dan langsung
 * ditolak errno 150 — jebakan yang sudah tercatat di AGENTS.md §0e, dan tetap
 * memakan korban karena saya hanya memeriksanya untuk satu dari dua kolom.
 * Periksa tipe kolom rujukan, jangan menirukan gaya penulisan tabel lain.
 */
class Migration_Add_forum_laporan_komentar extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `forum_laporan_komentar` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_komentar` INT NOT NULL,
            `user_id` INT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_forum_laporan_pelapor` (`id_komentar`,`user_id`),
            KEY `idx_forum_laporan_user` (`user_id`),
            CONSTRAINT `fk_forum_laporan_komentar` FOREIGN KEY (`id_komentar`)
                REFERENCES `forum_komentar` (`id_komentar`) ON DELETE CASCADE,
            CONSTRAINT `fk_forum_laporan_user` FOREIGN KEY (`user_id`)
                REFERENCES `usr_users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `forum_laporan_komentar`");
    }
}
