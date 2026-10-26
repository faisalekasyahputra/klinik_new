<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Label isian pendataan warga untuk layar petugas: Detail Penilaian Warga (admin/antrean/detail.php) dan unduhan
 * Excel antrean (Admin::unduh). Satu sumber sejak 26 Okt 2026 (dulu salinan di view detail). Teksnya sama dengan
 * formulir warga (pages/warga/pendataan.php), supaya petugas tidak membaca kode mentah seperti "hm" atau "pdam".
 * Kode yang tidak dikenal tetap ditampilkan apa adanya, bukan disembunyikan.
 */
$kondisi = ['good' => 'Baik', 'minor_damage' => 'Rusak Ringan (Permukaan)', 'moderate_damage' => 'Rusak Sedang (Material)', 'severe_damage_or_absent' => 'Rusak Berat (Struktur/Tdk Ada)'];
$sertifikat = ['certificate_unspecified' => 'Sertifikat (jenis tidak disebut SIMPERUM)', 'hm' => 'Sertifikat HM', 'hgb' => 'Sertifikat HGB', 'letter_c' => 'Letter C', 'letter_d' => 'Letter D', 'village_letter' => 'Suket Desa', 'notarial_deed' => 'Akta Notaris', 'other' => 'Lainnya'];
$ya_tidak = ['1' => 'Ya', '0' => 'Tidak'];

// Urutan = urutan kolom Excel dan urutan tampil di detail.
$config['label_pendataan_kolom'] = [
    'kepemilikan_rumah' => 'Status rumah', 'kepemilikan_lahan' => 'Status lahan', 'tanah_lain' => 'Memiliki tanah lain', 'rumah_lain' => 'Memiliki rumah lain',
    'luas_rumah' => 'Luas rumah (m²)', 'jml_penghuni' => 'Jumlah penghuni', 'jml_kk' => 'Jumlah keluarga', 'kawasan_perumahan' => 'Kawasan',
    'bantuan_perumahan' => 'Sumber bantuan sebelumnya', 'tahun_intervensi' => 'Tahun bantuan',
    'punya_lahan_calon' => 'Memiliki calon lahan', 'candidate_land_address' => 'Alamat calon lahan', 'status_lahan_calon' => 'Sertifikat calon lahan',
    'asal_lahan_calon' => 'Asal tanah', 'hubungan_pemilik_lahan' => 'Hubungan dengan pemilik', 'panjang_lahan_m' => 'Panjang tanah (m)', 'lebar_lahan_m' => 'Lebar tanah (m)',
    'kondisi_pondasi' => 'Pondasi', 'kondisi_kolom' => 'Kolom', 'kondisi_balok' => 'Balok', 'kondisi_sloof' => 'Sloof', 'kondisi_rangka' => 'Rangka atap',
    'bahan_lantai' => 'Bahan lantai', 'kondisi_lantai' => 'Kondisi lantai', 'bahan_dinding' => 'Bahan dinding', 'kondisi_dinding' => 'Kondisi dinding',
    'bahan_atap' => 'Bahan atap', 'kondisi_atap' => 'Kondisi atap', 'ada_jendela' => 'Jendela', 'ada_ventilasi' => 'Ventilasi',
    'penggunaan_kamar_mandi' => 'Kamar mandi', 'sumber_air' => 'Sumber air', 'jenis_kloset' => 'Jenis jamban',
    'pembuangan_tinja' => 'Jenis TPA', 'jarak_septic_tank' => 'Jarak septik', 'penerangan' => 'Penerangan', 'bahan_bakar_masak' => 'Bahan bakar memasak',
];

$config['label_pendataan_nilai'] = [
    'kepemilikan_rumah' => ['owned' => 'Milik Sendiri', 'rent' => 'Sewa/Kontrak', 'rent_free' => 'Bebas Sewa', 'official' => 'Rumah Dinas', 'staying' => 'Menumpang', 'other' => 'Bukan milik sendiri'],
    'kepemilikan_lahan' => $sertifikat, 'status_lahan_calon' => $sertifikat,
    'tanah_lain' => $ya_tidak, 'rumah_lain' => $ya_tidak, 'punya_lahan_calon' => $ya_tidak,
    'kawasan_perumahan' => ['drought' => 'Kekeringan', 'slum' => 'Kumuh', 'disaster_prone' => 'Rawan bencana', 'riverbank' => 'Bantaran sungai', 'railway' => 'Bantaran rel KA', 'poor_other' => 'Kawasan buruk lain', 'good' => 'Kawasan baik'],
    'bantuan_perumahan' => ['apbn_bsps' => 'APBN/BSPS (data SIMPERUM)', 'apbn' => 'APBN', 'apbd_prov' => 'APBD Provinsi', 'apbd_kab' => 'APBD Kab/Kota', 'csr' => 'CSR',
        'village_fund' => 'Dana Desa', 'bsps_kl' => 'BSPS-KL', 'bankab' => 'BANKAB', 'baznas' => 'BAZNAS', 'already_habitable' => 'Sudah Layak Huni', 'other' => 'Sumber lainnya'],
    'asal_lahan_calon' => ['owned' => 'Milik Sendiri', 'inheritance' => 'Warisan', 'grant' => 'Hibah', 'purchase' => 'Jual Beli'],
    'hubungan_pemilik_lahan' => ['parent' => 'Orang Tua', 'other' => 'Orang Lain'],
    'kondisi_pondasi' => $kondisi, 'kondisi_kolom' => $kondisi, 'kondisi_balok' => $kondisi, 'kondisi_sloof' => $kondisi, 'kondisi_rangka' => $kondisi,
    'kondisi_lantai' => $kondisi, 'kondisi_dinding' => $kondisi, 'kondisi_atap' => $kondisi,
    'bahan_lantai' => ['marble_granite' => 'Marmer/Granit', 'ceramic' => 'Keramik', 'parquet_vinyl_carpet' => 'Parket/Vinil/Permadani', 'tile_terrazzo' => 'Ubin/Tegel/Teraso', 'high_quality_wood' => 'Kayu/Papan Kualitas Tinggi', 'cement_plaster' => 'Semen/Plesteran', 'bamboo' => 'Bambu', 'low_quality_wood' => 'Kayu/Papan Kualitas Rendah', 'soil' => 'Tanah', 'other' => 'Lainnya'],
    'bahan_dinding' => ['wall' => 'Tembok', 'plaster_grc' => 'Plesteran/GRC', 'wood' => 'Kayu', 'woven_bamboo' => 'Anyaman Bambu', 'log' => 'Batang Kayu', 'bamboo' => 'Bambu', 'other' => 'Lainnya'],
    'bahan_atap' => ['concrete' => 'Beton', 'ceramic' => 'Keramik', 'metal' => 'Metal', 'clay_tile' => 'Genteng/Tanah Liat', 'asbestos' => 'Asbes', 'zinc' => 'Seng', 'shingle' => 'Sirap', 'bamboo' => 'Bambu', 'thatch' => 'Jerami/Ijuk/Daun/Rumbia', 'other' => 'Lainnya'],
    'ada_jendela' => ['1' => 'Ada Jendela', '0' => 'Tidak Ada'], 'ada_ventilasi' => ['1' => 'Ada Ventilasi', '0' => 'Tidak Ada'],
    'penggunaan_kamar_mandi' => ['own' => 'Sendiri', 'shared' => 'Bersama', 'none' => 'Tidak Punya'],
    'sumber_air' => ['bottled' => 'Air Kemasan Bermerek', 'refill' => 'Air Isi Ulang', 'piped' => 'Ledeng (jenis tidak disebut SIMPERUM)', 'pdam' => 'PDAM', 'retail_piped' => 'Leding Eceran', 'well' => 'Sumur', 'well_protected' => 'Sumur Terlindung', 'well_unprotected' => 'Sumur Tak Terlindung', 'spring' => 'Mata Air', 'spring_unprotected' => 'Mata Air Tak Terlindung', 'surface_water' => 'Air Sungai/Danau/Waduk', 'rain' => 'Air Hujan', 'other_unfit' => 'Lainnya/Tidak Layak'],
    'jenis_kloset' => ['swan_neck' => 'Leher Angsa', 'plengsengan' => 'Plengsengan', 'pit' => 'Cemplung/Cubluk', 'none' => 'Tidak Punya'],
    'pembuangan_tinja' => ['septic_tank' => 'Tangki Septik', 'ipal' => 'IPAL', 'water_body' => 'Kolam/Sawah/Sungai', 'ground_hole' => 'Lubang Tanah', 'open_land' => 'Pantai/Tanah Lapang/Kebun'],
    'jarak_septic_tank' => ['lt_10' => '<10 m', 'gte_10' => '>=10 m'],
    'penerangan' => ['pln' => 'PLN', 'pln_unmetered' => 'PLN Non Meteran', 'non_pln' => 'Non PLN', 'none' => 'Bukan Listrik'],
    'bahan_bakar_masak' => ['electric_gas' => 'Listrik/Gas', 'kerosene' => 'Minyak Tanah', 'charcoal_wood' => 'Arang/Kayu', 'other' => 'Lainnya'],
];

// Ditampilkan sebagai angka berkoma ("7,5"), bukan dicocokkan ke label.
$config['label_pendataan_angka'] = ['jml_penghuni', 'jml_kk', 'luas_rumah', 'panjang_lahan_m', 'lebar_lahan_m'];
