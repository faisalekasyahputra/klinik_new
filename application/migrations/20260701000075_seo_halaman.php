<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Timpaan SEO per halaman yang diatur Super Admin di menu SEO Halaman (Admin_Seo, 6 Okt 2026).
 * Satu baris = satu halaman; kolom NULL berarti ikut bawaan (config/seo.php, data halaman, kartu OG).
 * `kunci` = alamat halaman berhuruf rute asli, '' untuk beranda. `noindex` NULL = bawaan, 0/1 = paksa.
 * `gambar` = berkas unggahan di assets/img/og/unggahan/ (dikodekan ulang ke JPG 1200x630).
 */
class Migration_Seo_halaman extends CI_Migration {

    public function up()
    {
        if ($this->db->table_exists('seo_halaman')) { return; }
        $this->db->query("CREATE TABLE seo_halaman (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            kunci VARCHAR(120) NOT NULL,
            judul VARCHAR(70) NULL,
            deskripsi VARCHAR(170) NULL,
            gambar VARCHAR(255) NULL,
            noindex TINYINT(1) NULL,
            diubah_oleh INT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_seo_halaman_kunci (kunci),
            CONSTRAINT fk_seo_halaman_pengubah FOREIGN KEY (diubah_oleh) REFERENCES usr_akun (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='Timpaan SEO per halaman portal (judul, deskripsi, gambar OG, indeks) dari menu SEO Halaman'");
    }

    public function down()
    {
        if ($this->db->table_exists('seo_halaman')) {
            // Berkas unggahan sengaja dibiarkan: down() hanya urusan skema.
            $this->dbforge->drop_table('seo_halaman');
        }
    }
}
