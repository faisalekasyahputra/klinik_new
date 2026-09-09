-- Tambahkan tiket publik untuk pengajuan baru.
-- Jalankan satu kali pada database lokal/staging setelah backup.
-- Data antrean lama tetap ada, tetapi tidak dapat dicari tanpa ticket_code.

ALTER TABLE `sf_housing_queue`
  ADD COLUMN `ticket_code` VARCHAR(10) NULL AFTER `id`,
  ADD UNIQUE KEY `uq_sf_housing_queue_ticket_code` (`ticket_code`);
