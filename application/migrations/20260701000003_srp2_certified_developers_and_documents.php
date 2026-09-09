<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Srp2_certified_developers_and_documents extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `srp2_certified_developers` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nama_perusahaan` VARCHAR(180) NOT NULL,
              `alamat_kantor` TEXT NULL,
              `website` VARCHAR(255) NULL,
              `instagram` VARCHAR(255) NULL,
              `sosmed_lainnya` VARCHAR(255) NULL,
              `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_srp2_certified_developers_name` (`nama_perusahaan`),
              KEY `idx_srp2_certified_developers_active` (`status_aktif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `srp2_documents` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `registration_id` INT UNSIGNED NOT NULL,
              `document_key` VARCHAR(40) NOT NULL,
              `original_name` VARCHAR(255) NOT NULL,
              `stored_name` VARCHAR(80) NOT NULL,
              `mime_type` VARCHAR(100) NOT NULL,
              `file_size` INT UNSIGNED NOT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_srp2_registration_document` (`registration_id`, `document_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->db->query("
            INSERT IGNORE INTO `srp2_certified_developers` (`nama_perusahaan`) VALUES
            ('PT. PROPERTINDO PETRACHO ABADI'),('PT. AJISAKA (ALAM JATI SEMESTA KARUNIA AGUNG)'),('PT. AYODYA PURI NUGRAHA'),('PT. WARNA ALAM INDONESIA'),('PT. SANTOSO CIPTA SEJAHTERA'),('PT. BAROKAH SURAJAYA ABADI'),('PT. PUSAKA LAWU INDONESIA'),('PT AKASALAND PROPERTY DEVELOPMENT'),('PT NAFARO MUKTI ROBINA'),('PT. IRGI KARYA AMANAH'),('PT ABYUDAYA MULYA AGUNG'),('PT. RAFADA PUTRA MAHKOTA'),('PT BRAYO CIPTA SEJAHTERA'),('PT. BUMI SARI RAKSA'),('PT. BUMI BAROKAH MAKMUR'),('PT. RAHARJO SEJAHTERA NUSANTARA'),('PT. YURIS PRATAMA SEJAHTERA'),('PT. RAHARJO SEJAHTERA PROPERTI'),('PT. GRIYA PANTURA MANDIRI'),('PT. PUTRA REDJO MANDIRI'),('PT HAMPARAN KARUNIA ALAM SEMESTA'),('PT. DHIYO ABADI LAND'),('PT. CAHAYA TIGA BERLIAN KALIWUNGU'),('PT. NINDYA KARYA UTAMA'),('PT. CEMERLANG CAHAYA INTERNASIONAL'),('PT. TIGA CIPTA GRAHA'),('PT. SHANKARA ABIRAMA PROPERTI'),('PT. JAZIDHA SEJAHTERA'),('PT. ADIKA CATUR KARYA'),('PT. IZA PUTRA ADIKA'),('PT NITI BUANA SEJAHTERA'),('PT. MAMA SEJAHTERA'),('PT. ANUGERAH PUTERA JAYA'),('PT. ANUGRAH JAYA LAND'),('PT. INDIFA JAYA KONSTRUKSI'),('PT. SOFIYAN JAYA PRIMA'),('PT. HAMPARAN GRIYA PERSADA'),('PT. GRIYA UTAMA SEJAHTERA'),('PT. GRAHA FEA MANDIRI'),('PT. PUTRA TUNGGAL DEVELOPMENT'),('PT. OMAH AGENG SENTOSA'),('PT. AJI BUMI SENTANU'),('PT. KALIMASODO JAYA BERSAMA'),('PT. NUSANTARA MAJU PROPERTY'),('PT TIGA MITRA ADHIJAYA'),('PT. SAKA ASHTA MANUNGGAL'),('PT. SONGOLAS JAYA ABADI'),('PT. CITRA BERKAH NUSANTARA'),('PT. KAVLING RUMAH SYARIAH INDAH'),('PT ANGKASA PUTRA INDOTAMA'),('PT WAHANA PANDERMA KARYA'),('PT. BINA PROPERTY INDONESIA'),('PT JAGO BANGUN PERSADA'),('PT. RIZKI GROUP PROPERTYNDO'),('PT. DE WASTU JAYA'),('PT. TANOSA JAYA NUSANTARA'),('PT. ALIMA KARUNIA UTAMA'),('PT KOTA SATRIA DEVELOPER'),('PT. MAHKOTA CAHAYA MULIA ABADI'),('PT. PERMATA GRUP DEVELOPMENT'),('PT. RIZKY MEYSHA UTAMA'),('PT. DELTA MAHARDIKA REALTINDO'),('PT KUKUH RASTIKA JAYA'),('PT MITRA MUDA SANJAYA PROPERTINDO'),('PT. ARSHAKA GRIYA AYANA'),('PT SEMUA BISA PUNYA RUMAH INDONESIA');
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `srp2_documents`;");
        $this->db->query("DROP TABLE IF EXISTS `srp2_certified_developers`;");
    }
}
