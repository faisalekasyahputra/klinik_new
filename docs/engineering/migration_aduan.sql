-- Tabel aduan/pertanyaan warga. Bidang tujuan dideteksi otomatis dari
-- kata kunci pada judul+pesan (lihat Aduan_model::detect_bidang()) -
-- jatuh ke 'umum' kalau tidak ada kata kunci yang cocok.
-- Belum ada dashboard admin per bidang untuk membaca/memproses aduan ini
-- (menyusul setelah peran/role per bidang ditentukan).

CREATE TABLE IF NOT EXISTS `aduan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL COMMENT 'FK ke usr_users.id, nullable untuk tamu',
  `nama` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `bidang` VARCHAR(30) NOT NULL DEFAULT 'umum' COMMENT 'perumahan|kawasan|pertanahan|pengembang|umum, dideteksi otomatis',
  `lampiran` VARCHAR(255) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Baru' COMMENT 'Baru|Diproses|Selesai',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_bidang` (`bidang`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
