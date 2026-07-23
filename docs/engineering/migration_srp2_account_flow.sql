-- SRP2 akun pengembang: profil disimpan lebih dulu sebagai Draft,
-- lalu 14 dokumen diunggah sebelum pengajuan dikirim ke admin.
ALTER TABLE `usr_users`
  ADD COLUMN IF NOT EXISTS `nama_perusahaan` VARCHAR(150) NULL AFTER `alamat`,
  ADD COLUMN IF NOT EXISTS `alamat_kantor` TEXT NULL AFTER `nama_perusahaan`,
  ADD COLUMN IF NOT EXISTS `telp_kantor` VARCHAR(20) NULL AFTER `alamat_kantor`;

ALTER TABLE `srp2_registrations`
  MODIFY `nama_peserta` VARCHAR(150) NULL,
  MODIFY `nik_ktp` VARCHAR(16) NULL,
  MODIFY `jabatan` VARCHAR(30) NULL,
  MODIFY `no_whatsapp` VARCHAR(15) NULL,
  MODIFY `email` VARCHAR(100) NULL,
  MODIFY `nama_perusahaan` VARCHAR(150) NULL,
  MODIFY `nib` VARCHAR(13) NULL,
  MODIFY `asosiasi` VARCHAR(30) NULL,
  MODIFY `no_keanggotaan` VARCHAR(50) NULL,
  MODIFY `alamat_kantor` TEXT NULL,
  MODIFY `file_ktp` VARCHAR(255) NULL,
  MODIFY `file_kta` VARCHAR(255) NULL,
  MODIFY `file_nib` VARCHAR(255) NULL;
