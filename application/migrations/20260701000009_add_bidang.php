<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Formalisasi 5 nilai yang sudah dipakai di aduan.bidang (lihat
 * Aduan_model::bidang_label()) jadi tabel nyata + target assignment
 * admin_bidang. Kolom aduan.bidang TETAP varchar (tidak diubah ke FK)
 * supaya tidak menyentuh alur Umum::simpan_aduan() yang sudah jalan.
 */
class Migration_Add_bidang extends CI_Migration {

    private $bidang = [
        'perumahan'  => 'Bidang Perumahan',
        'kawasan'    => 'Bidang Kawasan Permukiman',
        'pertanahan' => 'Bidang Pertanahan',
        'pengembang' => 'Bidang Pengembang',
        'umum'       => 'Umum / Lainnya',
    ];

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `bidang` (
              `kode` VARCHAR(30) NOT NULL,
              `nama` VARCHAR(100) NOT NULL,
              PRIMARY KEY (`kode`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        foreach ($this->bidang as $kode => $nama)
        {
            $this->db->query("INSERT IGNORE INTO `bidang` (`kode`, `nama`) VALUES (?, ?);", [$kode, $nama]);
        }

        $this->db->query("
            ALTER TABLE `usr_users`
              ADD COLUMN IF NOT EXISTS `bidang_kode` VARCHAR(30) NULL AFTER `kabupaten_id`;
        ");

        $this->db->query("
            ALTER TABLE `aduan`
              ADD COLUMN IF NOT EXISTS `catatan_admin` TEXT NULL AFTER `status`;
        ");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `aduan` DROP COLUMN IF EXISTS `catatan_admin`;");
        $this->db->query("ALTER TABLE `usr_users` DROP COLUMN IF EXISTS `bidang_kode`;");
        $this->db->query("DROP TABLE IF EXISTS `bidang`;");
    }
}
