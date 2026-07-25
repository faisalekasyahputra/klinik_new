<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Formalisasi daftar kabupaten/kota Jawa Tengah jadi tabel nyata.
 * ID pakai kode wilayah Kemendagri (sama persis dengan array
 * $kabupaten_kota_jateng di Index.php) supaya konsisten dengan
 * data SIKUMBANG/BPS yang sudah dipakai di tempat lain.
 */
class Migration_Add_kabupaten extends CI_Migration {

    private $kabupaten = [
        3301 => 'Kabupaten Cilacap', 3302 => 'Kabupaten Banyumas', 3303 => 'Kabupaten Purbalingga',
        3304 => 'Kabupaten Banjarnegara', 3305 => 'Kabupaten Kebumen', 3306 => 'Kabupaten Purworejo',
        3307 => 'Kabupaten Wonosobo', 3308 => 'Kabupaten Magelang', 3309 => 'Kabupaten Boyolali',
        3310 => 'Kabupaten Klaten', 3311 => 'Kabupaten Sukoharjo', 3312 => 'Kabupaten Wonogiri',
        3313 => 'Kabupaten Karanganyar', 3314 => 'Kabupaten Sragen', 3315 => 'Kabupaten Grobogan',
        3316 => 'Kabupaten Blora', 3317 => 'Kabupaten Rembang', 3318 => 'Kabupaten Pati',
        3319 => 'Kabupaten Kudus', 3320 => 'Kabupaten Jepara', 3321 => 'Kabupaten Demak',
        3322 => 'Kabupaten Semarang', 3323 => 'Kabupaten Temanggung', 3324 => 'Kabupaten Kendal',
        3325 => 'Kabupaten Batang', 3326 => 'Kabupaten Pekalongan', 3327 => 'Kabupaten Pemalang',
        3328 => 'Kabupaten Tegal', 3329 => 'Kabupaten Brebes', 3371 => 'Kota Magelang',
        3372 => 'Kota Surakarta (Solo)', 3373 => 'Kota Salatiga', 3374 => 'Kota Semarang',
        3375 => 'Kota Pekalongan', 3376 => 'Kota Tegal',
    ];

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `kabupaten` (
              `id` INT UNSIGNED NOT NULL COMMENT 'Kode wilayah Kemendagri',
              `nama` VARCHAR(100) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        foreach ($this->kabupaten as $id => $nama)
        {
            $this->db->query("INSERT IGNORE INTO `kabupaten` (`id`, `nama`) VALUES (?, ?);", [$id, $nama]);
        }

        $this->db->query("
            ALTER TABLE `sf_housing_queue`
              ADD COLUMN IF NOT EXISTS `kabupaten_id` INT UNSIGNED NULL AFTER `ticket_code`;
        ");
        if ( ! $this->_key_exists('sf_housing_queue', 'idx_sf_housing_queue_kabupaten'))
        {
            $this->db->query("
                ALTER TABLE `sf_housing_queue`
                  ADD KEY `idx_sf_housing_queue_kabupaten` (`kabupaten_id`),
                  ADD CONSTRAINT `fk_sf_housing_queue_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id`) ON DELETE SET NULL;
            ");
        }

        $this->db->query("
            ALTER TABLE `usr_users`
              ADD COLUMN IF NOT EXISTS `kabupaten_id` INT UNSIGNED NULL AFTER `telp_kantor`;
        ");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE `usr_users` DROP COLUMN IF EXISTS `kabupaten_id`;");
        if ($this->_key_exists('sf_housing_queue', 'fk_sf_housing_queue_kabupaten'))
        {
            $this->db->query("ALTER TABLE `sf_housing_queue` DROP FOREIGN KEY `fk_sf_housing_queue_kabupaten`;");
        }
        $this->db->query("ALTER TABLE `sf_housing_queue` DROP COLUMN IF EXISTS `kabupaten_id`;");
        $this->db->query("DROP TABLE IF EXISTS `kabupaten`;");
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])->row();
        return (bool) $row;
    }
}
