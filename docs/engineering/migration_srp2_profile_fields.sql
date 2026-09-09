ALTER TABLE `srp2_certified_developers`
  ADD COLUMN IF NOT EXISTS `alamat_kantor` TEXT NULL AFTER `nama_perusahaan`,
  ADD COLUMN IF NOT EXISTS `website` VARCHAR(255) NULL AFTER `alamat_kantor`,
  ADD COLUMN IF NOT EXISTS `instagram` VARCHAR(255) NULL AFTER `website`,
  ADD COLUMN IF NOT EXISTS `sosmed_lainnya` VARCHAR(255) NULL AFTER `instagram`;
