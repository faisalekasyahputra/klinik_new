<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Generalkan tabel pembatas laju: `sys_ticket_lookup_limits` -> `sys_rate_limits`.
 *
 * Tabel ini dibuat migrasi 06 khusus untuk lookup tiket publik, tapi polanya
 * (kunci ter-hash + jendela waktu + penghitung) berlaku untuk endpoint publik
 * mana pun. Roadmap T0 butir 3 membutuhkannya untuk membatasi pendaftaran, dan
 * empat role berikutnya akan butuh hal yang sama.
 *
 * Menyimpan pembatas pendaftaran di tabel bernama "ticket_lookup" adalah persis
 * jenis nama menyesatkan yang berulang kali menggigit repo ini (lihat §0e), jadi
 * namanya diperbaiki sekalian alih-alih ditumpangi diam-diam.
 *
 * Kolom `ip_hash` -> `limit_key`: isinya kini sha256 dari "{scope}:{ip}", bukan
 * lagi hash IP polos. Nilai lama tetap sah sebagai baris — paling banter satu
 * jendela rate limit lookup tiket ter-reset saat migrasi jalan, tidak ada data
 * bermakna yang hilang.
 */
class Migration_Generalize_rate_limits extends CI_Migration {

    public function up()
    {
        // Idempotent: kalau instalasi bersih belum pernah punya tabel lama,
        // langsung buat yang baru. Kalau sudah ada, ganti nama + kolomnya.
        if ($this->db->table_exists('sys_ticket_lookup_limits') && ! $this->db->table_exists('sys_rate_limits')) {
            $this->db->query("RENAME TABLE `sys_ticket_lookup_limits` TO `sys_rate_limits`;");
            $this->db->query("ALTER TABLE `sys_rate_limits` CHANGE `ip_hash` `limit_key` CHAR(64) NOT NULL;");
            return;
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sys_rate_limits` (
              `limit_key` CHAR(64) NOT NULL,
              `window_started_at` DATETIME NOT NULL,
              `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`limit_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down()
    {
        if ($this->db->table_exists('sys_rate_limits') && ! $this->db->table_exists('sys_ticket_lookup_limits')) {
            $this->db->query("ALTER TABLE `sys_rate_limits` CHANGE `limit_key` `ip_hash` CHAR(64) NOT NULL;");
            $this->db->query("RENAME TABLE `sys_rate_limits` TO `sys_ticket_lookup_limits`;");
        }
    }
}
