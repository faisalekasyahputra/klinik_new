-- Tabel dummy SIMPERUM - permintaan user 23 Agt 2026, dipakai untuk
-- tes lokal (Postman/manual) karena API SIMPERUM sungguhan sedang
-- tidak bisa diakses. Nama kolom snake_case dari field PERSIS yang
-- tertulis di "SIMPERUM API.pdf" (GetDataRTLH + field tambahan khusus
-- SaveDataRTLH: Kabupaten/Kecamatan/Kelurahan/KondisiAtap/KondisiLantai/
-- KondisiDinding/RumahDepan/RumahSamping) - satu tabel mencakup field
-- dari KEDUA endpoint sekaligus supaya jadi satu rujukan lengkap.
--
-- INI BUKAN tabel produksi/migrasi resmi aplikasi - sengaja di luar
-- sistem migrasi CI3 (application/migrations/), murni skrip lokal
-- untuk kebutuhan tes pribadi. Prefiks "dummy_" menandainya jelas.

DROP TABLE IF EXISTS `dummy_simperum_rtlh`;

CREATE TABLE `dummy_simperum_rtlh` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- --- Field GetDataRTLH (Pengambilan Data Berdasarkan NIK/KodeDagri) ---
  `idbdt` VARCHAR(20) NULL,
  `tahun_intervensi` VARCHAR(4) NULL,
  `sumber_dana_id` VARCHAR(5) NULL COMMENT 'lihat SUMBER DANA di PDF: 1 APBN/BSPS, 2 APBD Provinsi, 3 APBD Kab/Kota, 4 CSR, 5 Lainnya, 7 Dana Desa, 9 BSPS-KL',
  `sumber_dana` VARCHAR(50) NULL,
  `nik` VARCHAR(16) NOT NULL,
  `nama` VARCHAR(150) NOT NULL,
  `alamat` VARCHAR(255) NULL,
  `kode_dagri` VARCHAR(15) NULL COMMENT 'kode wilayah Kemendagri, 4 digit pertama = kabupaten.id di app ini',
  `atap_id` VARCHAR(5) NULL COMMENT 'lihat ATAP di PDF: 1 Beton .. 10 Lainnya',
  `lantai_id` VARCHAR(5) NULL COMMENT 'lihat LANTAI di PDF: 1 Marmer/granit .. 10 Lainnya',
  `dinding_id` VARCHAR(5) NULL COMMENT 'lihat DINDING di PDF: 1 Tembok .. 7 Lainnya',
  `geo_lat` VARCHAR(20) NULL,
  `geo_lng` VARCHAR(20) NULL,
  `jenis_kelamin` CHAR(1) NULL COMMENT 'L Laki-Laki, P Perempuan',
  `tahun_lahir` VARCHAR(4) NULL,
  `pendidikan` VARCHAR(5) NULL COMMENT '0 Tidak Punya Ijazah .. 6 S2/S3',
  `pekerjaan` VARCHAR(5) NULL COMMENT '1 Petani .. 99 Tidak Bekerja (lihat daftar lengkap di PDF)',
  `penghasilan` VARCHAR(5) NULL COMMENT '1 <1.8jt .. 7 >4.2jt',
  `bantuan_perumahan` VARCHAR(50) NULL,
  `kawasan_perumahan` VARCHAR(5) NULL COMMENT '1 Kekeringan, 6 Kumuh, 10 Rawan Bencana, 11 Bantaran Sungai, 12 Bantaran Rel KA, 98 Kawasan Buruk Lain, 99 Kawasan Baik',
  `kepemilikan_lahan` VARCHAR(5) NULL COMMENT '1 Sertifikat, 2 Letter C, 3 Letter D, 4 Surat Keterangan Desa',
  `kepemilikan_rumah` VARCHAR(5) NULL COMMENT '1 Milik Sendiri, 2 Kontrak/Sewa, 3 Bebas Sewa, 4 Dinas, 5 Lainnya',
  `tanah_lain` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `rumah_lain` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `luas_rumah` VARCHAR(10) NULL,
  `jml_penghuni` VARCHAR(5) NULL,
  `jml_kk` VARCHAR(5) NULL,
  `ada_pondasi` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `kondisi_kolom` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `kondisi_balok` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `kondisi_rangka` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `ada_jendela` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `ada_ventilasi` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `sumber_air` VARCHAR(5) NULL COMMENT '1 Air kemasan .. 6 Air Hujan',
  `penerangan` VARCHAR(5) NULL COMMENT '1 PLN, 2 PLN non meteran, 3 Non PLN, 4 Bukan Listrik',
  `letak_sanitasi` VARCHAR(50) NULL,
  `kamar_mandi` VARCHAR(5) NULL COMMENT '0 Tidak Ada, 1 Ada',
  `jarak_septic_tank` VARCHAR(5) NULL COMMENT '0 <10m, 1 >=10m',
  `mampu_swadaya` VARCHAR(5) NULL COMMENT '0 Tidak Mampu, 1 Mampu',

  -- --- Field tambahan yang HANYA ada di body SaveDataRTLH (bukan di
  -- hasil kembalian GetDataRTLH) - ikut disediakan supaya tabel ini
  -- juga cukup untuk menyusun body tes POST SaveDataRTLH. ---
  `kabupaten` VARCHAR(100) NULL,
  `kecamatan` VARCHAR(100) NULL,
  `kelurahan` VARCHAR(100) NULL,
  `kondisi_atap` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `kondisi_lantai` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `kondisi_dinding` VARCHAR(5) NULL COMMENT '1 Baik, 2 Rusak Ringan, 3 Rusak Sedang, 4 Rusak Berat',
  `rumah_depan` VARCHAR(255) NULL COMMENT 'url foto, lihat catatan FOTO di PDF',
  `rumah_samping` VARCHAR(255) NULL COMMENT 'url foto, lihat catatan FOTO di PDF',

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dummy_simperum_nik` (`nik`),
  KEY `idx_dummy_simperum_kode_dagri` (`kode_dagri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Baris 1 = PERSIS contoh dari PDF halaman 2 ("ABU WARDI") - supaya ada
-- satu baris yang bisa dicocokkan langsung dengan contoh Hasil Kembalian
-- di dokumen aslinya, kalau perlu membandingkan.
INSERT INTO `dummy_simperum_rtlh`
(`idbdt`,`tahun_intervensi`,`sumber_dana_id`,`sumber_dana`,`nik`,`nama`,`alamat`,`kode_dagri`,
 `atap_id`,`lantai_id`,`dinding_id`,`geo_lat`,`geo_lng`,`jenis_kelamin`,`tahun_lahir`,`pendidikan`,
 `pekerjaan`,`penghasilan`,`bantuan_perumahan`,`kawasan_perumahan`,`kepemilikan_lahan`,`kepemilikan_rumah`,
 `tanah_lain`,`rumah_lain`,`luas_rumah`,`jml_penghuni`,`jml_kk`,`ada_pondasi`,`kondisi_kolom`,
 `kondisi_balok`,`kondisi_rangka`,`ada_jendela`,`ada_ventilasi`,`sumber_air`,`penerangan`,
 `letak_sanitasi`,`kamar_mandi`,`jarak_septic_tank`,`mampu_swadaya`,
 `kabupaten`,`kecamatan`,`kelurahan`,`kondisi_atap`,`kondisi_lantai`,`kondisi_dinding`,
 `rumah_depan`,`rumah_samping`)
VALUES
-- 1. Persis contoh resmi di PDF (NIK/Nama/Alamat/KodeDagri/dst asli dari dokumen)
('68222','2020',NULL,NULL,'3301011506480004','ABU WARDI','DUSUN TAMBAKREJA DUSUN TAMBAKREJA RW 07 RT 003','3301012001',
 '4','9','1','-7.54853540','108.77494670','L',NULL,NULL,
 NULL,NULL,NULL,NULL,NULL,NULL,
 NULL,NULL,NULL,NULL,NULL,NULL,NULL,
 NULL,NULL,NULL,NULL,NULL,NULL,
 NULL,NULL,NULL,NULL,
 'CILACAP','CILACAP SELATAN','TAMBAKREJA',NULL,NULL,NULL,
 NULL,NULL),

-- 2-6. Dummy tambahan, field lebih lengkap terisi (bukan null semua)
-- supaya ada variasi kode untuk dicoba - NIK sengaja pola 33xxxx supaya
-- valid untuk cakupan Jawa Tengah di app ini (lihat kabupaten.id).
('10001','2023','1','APBN/BSPS','3301010101010001','SUPARNO','JL. MERDEKA NO 12 RT 01 RW 03','3301010001',
 '6','9','7','-7.70110000','109.01450000','L','1975','1',
 '1','1','BSPS','6','4','1',
 '0','0','36','4','1','1','2',
 '2','3','0','0','4','1',
 'Kurang dari 10 meter dari sumur','0','0','0',
 'CILACAP','CILACAP TENGAH','SIDANEGARA','4','3','4',
 'https://simperum.disperakim.jatengprov.go.id/foto/dummy_depan_1.jpg','https://simperum.disperakim.jatengprov.go.id/foto/dummy_samping_1.jpg'),

('10002','2022','2','APBD Provinsi','3302020202020002','SITI ROHMAH','DUSUN KRAJAN RT 02 RW 01','3302020002',
 '9','7','6','-7.65200000','109.20300000','P','1988','2',
 '99','2','APBD','1','1','1',
 '1','0','28','3','1','0','3',
 '3','3','0','1','4','2',
 'Lebih dari 10 meter','1','1','0',
 'BANYUMAS','SOKARAJA','SOKARAJA KULON','3','3','3',
 NULL,NULL),

('10003','2024','4','CSR','3303030303030003','AGUS SETIAWAN','KAMPUNG BARU RT 05 RW 02','3303030003',
 '5','6','2','-7.41800000','109.23100000','L','1990','3',
 '11','3',NULL,'99','1','1',
 '0','0','45','5','1','1','1',
 '1','2','1','1','3','1',
 'Kurang dari 10 meter dari sumur','1','0','1',
 'PURBALINGGA','PURBALINGGA','PURBALINGGA WETAN','2','2','2',
 NULL,NULL),

('10004','2021','9','BSPS-KL','3374040404040004','WARSITO','JL. DIPONEGORO GANG 3 NO 7','3374040004',
 '4','9','1','-6.98600000','110.42030000','L','1965','0',
 '9','1',NULL,'6','2','1',
 '0','0','24','6','2','1','4',
 '4','4','0','0','4','2',
 'Kurang dari 10 meter dari sumur','0','0','0',
 'KOTA SEMARANG','SEMARANG TENGAH','SEKAYU','4','4','4',
 'https://simperum.disperakim.jatengprov.go.id/foto/dummy_depan_4.jpg',NULL),

('10005','2023','5','Lainnya','3373050505050005','WAHYU NINGSIH','RT 03 RW 04 DESA SUKAMAJU','3373050005',
 '2','2','1','-7.05500000','110.44200000','P','1995','5',
 '17','5',NULL,'99','1','1',
 '0','1','60','3','1','1','1',
 '1','1','1','1','3','1',
 'Lebih dari 10 meter','1','1','1',
 'KOTA SALATIGA','SIDOREJO','BLOTONGAN','1','1','1',
 NULL,NULL);
