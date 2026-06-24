-- --------------------------------------------------------
-- Skema Database Tambahan untuk Fase 9 (Klinik PKP)
-- Tabel: program_kategori, programs, housing_queue
-- --------------------------------------------------------

-- 1. Tabel Kategori Program
CREATE TABLE IF NOT EXISTS `program_kategori` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `program_kategori` (`nama_kategori`) VALUES 
('Perumahan Subsidi / KPR'),
('Peningkatan Kualitas (RTLH)'),
('Bantuan Pembangunan Baru'),
('Kawasan Pesisir & Khusus');

-- 2. Tabel Master Programs
CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_kategori` INT NOT NULL,
  `kode_program` VARCHAR(50) UNIQUE NOT NULL,
  `nama_program` VARCHAR(255) NOT NULL,
  `deskripsi_singkat` TEXT NOT NULL,
  `batas_penghasilan_max` DECIMAL(15,2) DEFAULT NULL, -- Max penghasilan untuk filter kelayakan
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_kategori`) REFERENCES `program_kategori`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `programs` (`id_kategori`, `kode_program`, `nama_program`, `deskripsi_singkat`, `batas_penghasilan_max`) VALUES 
(1, 'flpp', 'KPR-FLPP Rumah Subsidi', 'Skema pembiayaan perumahan subsidi bagi MBR.', 8000000),
(1, 'oemah_lestari', 'Oemah Lestari', 'Kredit kolaborasi BPR-BRK dengan bunga ringan.', 10000000),
(2, 'rtlh', 'Peningkatan Kualitas RTLH', 'Bantuan perbaikan rumah bagi masyarakat miskin.', 3000000),
(3, 'pb', 'Stimulan Pembangunan Baru', 'Bantuan material untuk pembangunan rumah baru.', 4000000),
(4, 'rumah_apung', 'Program Rumah Apung', 'Rumah adaptif untuk kawasan pesisir (rob).', 4000000);

-- 3. Tabel Housing Queue (Antrean Perumahan)
CREATE TABLE IF NOT EXISTS `housing_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL, -- Jika login
  `program_id` INT NOT NULL,
  `nik_pengaju` VARCHAR(255) NOT NULL, -- Dienkripsi jika memungkinkan atau plain sementara
  `nama_lengkap` VARCHAR(255) NOT NULL,
  `data_simperum_json` TEXT NULL, -- Menyimpan JSON raw dari SIMPERUM
  `data_survey_json` TEXT NULL, -- Menyimpan jawaban UI Wizard (penghasilan, dll)
  `status_antrean` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `catatan_admin` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`program_id`) REFERENCES `programs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Settings (Pengaturan Web Dinamis)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `key_value` text,
  `type` varchar(50) DEFAULT 'text',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
