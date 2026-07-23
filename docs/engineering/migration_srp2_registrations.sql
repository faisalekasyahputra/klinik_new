-- ==============================================================
-- Migrasi: tabel srp2_registrations (Sertifikasi Pengembang Perumahan)
-- ==============================================================
-- Tabel ini SEBELUMNYA dipakai oleh Pengembang::simpan() tapi TIDAK
-- PERNAH ada baik di docs/engineering/schema_klinikpkp.sql maupun
-- di database staging & production yang berjalan (dikonfirmasi via
-- DESCRIBE langsung 2026-07-20 — keduanya "table doesn't exist").
-- Artinya alur pendaftaran SRP2 belum pernah benar-benar berfungsi
-- end-to-end di lingkungan manapun, bukan cuma soal view yang hilang.
--
-- Kolom user_id, instagram, website, sosmed_lainnya adalah tambahan
-- baru untuk fitur "Cek Pengajuan" (dashboard role pengembang) dan
-- halaman Profil Pengembang — tidak ada di kode asli sebelumnya.
-- ==============================================================

CREATE TABLE IF NOT EXISTS `srp2_registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL COMMENT 'FK ke usr_users.id, nullable untuk kompatibilitas data lama',
  `nama_peserta` VARCHAR(150) NULL,
  `nik_ktp` VARCHAR(16) NULL,
  `jabatan` VARCHAR(30) NULL COMMENT 'direktur_utama|manajer_proyek|penanggung_jawab|staf_legal',
  `no_whatsapp` VARCHAR(15) NULL,
  `email` VARCHAR(100) NULL,
  `nama_perusahaan` VARCHAR(150) NULL,
  `nib` VARCHAR(13) NULL,
  `asosiasi` VARCHAR(30) NULL COMMENT 'rei|himperra|apersi|pi|lainnya',
  `no_keanggotaan` VARCHAR(50) NULL,
  `alamat_kantor` TEXT NULL,
  `file_ktp` VARCHAR(255) NULL,
  `file_kta` VARCHAR(255) NULL,
  `file_nib` VARCHAR(255) NULL,
  `file_surat_tugas` VARCHAR(255) NULL,
  `status_verifikasi` VARCHAR(20) NOT NULL DEFAULT 'Pending' COMMENT 'Pending|Diterima|Ditolak',
  `catatan_admin` TEXT NULL COMMENT 'Alasan penolakan dari admin, ditampilkan di halaman resi',
  `instagram` VARCHAR(255) NULL,
  `website` VARCHAR(255) NULL,
  `sosmed_lainnya` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nik_ktp` (`nik_ktp`),
  UNIQUE KEY `uq_nib` (`nib`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status_verifikasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
