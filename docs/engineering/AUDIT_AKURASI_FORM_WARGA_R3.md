# Audit Akurasi Form Warga untuk R3/R4

**Tanggal verifikasi:** 28 Juli 2026  
**Lingkup:** perbandingan visual seluruh artefak `C:\Users\ASUS\Downloads\formwarga` (5 `FORM_DESAIN` + 45 `FORM_DETAIL`) dengan PRD, skema, roadmap, dan fondasi R1–R2. Tidak ada data contoh penduduk ditranskripsikan.

## Cara membaca

- **Gambar** berarti teks/asterisk/opsi benar-benar terlihat pada artefak.
- **PRD** berarti keputusan produk yang mengikat, bukan bukti bahwa label atau opsi itu tersedia di gambar.
- **Skema/model** berarti nama kanonik yang sudah dirancang atau sudah ada di `Housing_assessment_model`; bukan bukti bahwa UI/writer R3 sudah selesai.
- `*` hanya ditulis bila tampak pada gambar. Tanpa `*` bukan berarti opsional secara bisnis.

## Ringkasan temuan

1. Dua jalur visual terkonfirmasi: `/Main/RTLH/PBDT_edit/` untuk rumah eksisting dan `/Main/Backlog/ValidasiData` untuk calon lahan/backlog. Ini sesuai DEC-WRG-005, tetapi wizard R3 harus tetap satu wizard data (DEC-WRG-018), bukan menyalin dua form lama.
2. Semua label utama dan katalog yang terbaca sudah memiliki nama kanonik pada skema. Fondasi model telah menyediakan hampir seluruh kolom non-PII rumah/struktur/sanitasi; R3 belum membangun view/writer langkahnya.
3. Tiga celah pemetaan implementasi yang harus ditutup pada R4: `candidate_land_address`, `location_lat`, dan `location_lng` ada di skema tetapi tidak ada dalam allowlist `DRAFT_FIELDS`; evidence belum punya writer di model R1–R2.
4. Jangan memperlakukan asterisk sumber sebagai kontrak final global: PRD memutuskan kewajiban berdasarkan cabang dan roadmap menunda kebijakan bukti resmi (OPEN-WRG-008).

## Audit per langkah

### 0. Temukan Data

| Label tepat pada gambar | Tipe terlihat | Wajib gambar | Opsi yang terbaca | Pemetaan | Status |
|---|---|---:|---|---|---|
| NIK | input angka + tombol `BDT` | Ya | — | `nik` (profil, ENC+hash) | Gambar + skema |
| Tanggal Lahir | input tanggal/ikon kalender | Ya (RTLH) | — | `birth_date` (profil, ENC) | Gambar + PRD faktor pendamping simulasi |
| Usulan Dari | dropdown | Tidak tampak | hanya `-` | `proposal_source_code` | Skema ada; arti/opsi **OPEN-WRG-004** |

**Catatan:** gambar Backlog menampilkan `Umur`, bukan Tanggal Lahir. Keputusan PRD tetap menyimpan tanggal lahir dan menurunkan umur; jangan menambah kolom umur tersendiri.

### 1. Data Warga dan sosial-ekonomi

| Label tepat pada gambar | Tipe terlihat | Wajib gambar | Opsi yang benar-benar terbaca | Pemetaan |
|---|---|---:|---|---|
| Nama | teks | Ya pada RTLH; tidak pada contoh Backlog | — | `full_name` (ENC) |
| No. KK | teks/angka | Ya pada RTLH; tidak pada contoh Backlog | — | `family_card_number` (ENC+hash opsional) |
| Alamat | teks | Ya pada RTLH; tidak pada contoh Backlog | — | `address` (ENC) |
| No HP | tel | Tidak | — | `phone` (ENC) |
| Jns. Kelamin | dropdown | Ya pada RTLH; tidak pada contoh Backlog | hanya `Laki-Laki` tampak sebagai nilai, bukan daftar lengkap | `gender_code`; katalog lengkap belum terkonfirmasi |
| Sts. Perkawinan | dropdown | Ya | Lajang; Menikah; Cerai | `marital_status_code` |
| Pendidikan | dropdown | Ya pada RTLH; tidak pada Backlog | Tidak Punya Ijazah; SD/sederajat; SMP/sederajat; SMA/sederajat; D1/D2/D3; D4/S1; S2/S3 | `education_code` |
| Pekerjaan | dropdown | Ya pada RTLH; tidak pada Backlog | Petani; Peternak; Pertambangan/Penggalian; Buruh Harian; Tukang Bangunan; Pedagang; Hotel & Rumah Makan; Sopir; Dokter/Bidan/Apoteker; PNS/BUMN/D; Pemulung; Lainnya; TNI/POLRI; Pegawai Swasta; PHL/PTT; Pensiunan; Tidak Bekerja | `occupation_code`; daftar dapat berlanjut, **OPEN-WRG-006** |
| No. NPWP / NPWP | teks | Tidak | — | `tax_number` (ENC) |
| Penghasilan | dropdown | Tidak pada RTLH; Ya pada Backlog | `< 1.8 jt`; `1.9 - 2.1 jt`; `2.2 - 2.6 jt`; `2.7 - 3.1 jt`; `3.2 - 3.6 jt`; `3.7 - 4.2 jt`; `4.2 - 6 jt`; `6 - 8 jt`; `> 8 jt` | `income_band_code`; batas/celah belum diputuskan |
| Desil | angka tampilan | Tidak | contoh nilai `2` | `welfare_decile`; **PRD:** sumber routing, read-only, null bukan nol |
| Memiliki Tabungan | checkbox | Tidak | dua nilai tidak terlihat | `has_savings` |
| Mampu Swadaya | dropdown | Ya pada RTLH; tidak pada Backlog | Mampu; Tidak Mampu | `self_help_capability_code` |
| Nilai Swadaya | input/angka | Tidak | — | `self_help_amount`; hanya tampak pada Backlog |

### 2. Rumah dan keluarga (RTLH)

| Label tepat pada gambar | Tipe terlihat | Wajib gambar | Opsi yang benar-benar terbaca | Pemetaan |
|---|---|---:|---|---|
| Sts. Rumah | dropdown | Ya | Milik Sendiri; Kontrak/Sewa; Bebas Sewa; Dinas; Menumpang; Lainnya | `housing_status_code` |
| Sts. Lahan | dropdown | Ya | Sertifikat HM; Sertifikat HGB; Letter C; Letter D; Suket Desa; Akta Notaris; Lainnya | `land_title_code` |
| Tanah Lain | dropdown | Tidak | Memiliki; Tidak Memiliki | `has_other_land` |
| Rumah Lain | dropdown | Tidak | Memiliki; Tidak Memiliki | `has_other_house` |
| Luas Rumah | angka | Ya | — | `house_area_m2` |
| Jml. Penghuni | angka | Ya | — | `occupant_count` |
| Jml. Keluarga | angka | Ya | — | `family_count` |
| Sumber Bantuan | dropdown | Tidak | APBN; APBD KAB; CSR; Sumber Lainnya; Sudah Layak Huni; Dana Desa; BSPS KL; Meninggal; Salah/Double Data; BANKAB; BAZNAS; Pindah | `assistance_source_code`; sumber/alasan tercampur, **OPEN-WRG-005** |
| Tahun | dropdown | Tidak | 2026; 2025; 2024; 2023; 2022 (daftar terlihat bisa berlanjut) | `assistance_year` |
| Kawasan | dropdown | Ya | Kekeringan; Kumuh; Rawan Bencana | `area_condition_code` |

### 3A. Kondisi Bangunan (hanya rumah eksisting)

Semua adalah dropdown. Label ber-asterisk pada gambar: **Pondasi, Kondisi Kolom, Kondisi Balok, Rangka Atap, Bahan Lantai, Kondisi Lantai, Bahan Dinding, Kondisi Dinding, Bahan Atap, Kondisi Atap**. `Kondisi Sloof` dan `Kondisi Plafon` tampak tanpa asterisk.

| Label | Opsi terbaca | Pemetaan |
|---|---|---|
| Pondasi; Kondisi Kolom; Kondisi Balok; Kondisi Sloof; Kondisi Plafon; Rangka Atap; Kondisi Lantai; Kondisi Dinding; Kondisi Atap | Baik; Rusak Ringan (Permukaan); Rusak Sedang (Material); Rusak Berat (Struktur/Tdk Ada) | masing-masing `*_condition_code` pada skema/model |
| Bahan Lantai | Marmer/Granit; Keramik; Parket/Vinil/Permadani; Ubin/Tegel/Teraso; Kayu/Papan Kualitas Tinggi; Semen/Plesteran; Bambu; Kayu/Papan Kualitas Rendah; Tanah; Lainnya | `floor_material_code` |
| Bahan Dinding | Tembok; Plesteran/GRC; Kayu; Anyaman Bambu; Batang Kayu; Bambu; Lainnya | `wall_material_code` |
| Bahan Atap | Genteng/Tanah Liat; Asbes; Seng | `roof_material_code`; daftar mungkin terpotong, **OPEN-WRG-006** |

### 3B. Calon Lahan / Backlog

| Label tepat pada gambar | Tipe terlihat | Wajib gambar | Opsi terbaca | Pemetaan |
|---|---|---:|---|---|
| Sts. Rumah | dropdown | Tidak | sama seperti Status Rumah | `housing_status_code` |
| Memiliki Tanah | dropdown | Ya | Tidak Memiliki; Memiliki | `owns_candidate_land` |
| Alamat Tanah | teks | Tidak | — | `candidate_land_address` (ENC) — **belum di allowlist model** |
| Sertifikat Tanah | dropdown | Ya | Sertifikat HM; Sertifikat HGB; Letter C; Letter D; Suket Desa; Akta Notaris; Lainnya | `candidate_land_title_code` |
| Asal Tanah | dropdown | Tidak | Milik Sendiri; Warisan; Hibah; Jual Beli | `candidate_land_origin_code` |
| Hub. dg Pemilik | dropdown | Tidak | Orang Tua; Orang Lain | `land_owner_relationship_code` |
| Ukuran Tanah | dua input angka `x` Lebar | Ya | — | `land_length_m`, `land_width_m`, `land_area_m2` turunan |
| Jml. Keluarga | angka | Ya | — | `family_count` |
| Jml. Penghuni | angka | Ya | — | `occupant_count` |
| Sumber Bantuan; Tahun | dropdown | Tidak | katalog sama yang terbaca pada RTLH | `assistance_source_code`, `assistance_year` |
| Kawasan | dropdown | Tidak pada Backlog | Kekeringan; Kumuh; Rawan Bencana; Bantaran Sungai; Bantaran Rel KA; Kawasan Buruk Lain; Kawasan Baik | `area_condition_code` |

### 4. Sanitasi dan utilitas (hanya rumah eksisting)

| Label tepat pada gambar | Tipe terlihat | Wajib gambar | Opsi yang terbaca | Pemetaan |
|---|---|---:|---|---|
| Jendela | dropdown | Tidak | Ada Jendela; Tidak Ada | `has_window` |
| Ventilasi | dropdown | Tidak | Ada Ventilasi; Tidak Ada | `has_ventilation` |
| Sumber Air | dropdown | Ya | Air kemasan bermerk; Air isi ulang; PDAM; Leding eceran; Sumur; Mata air; Air hujan; Lainnya / Tidak Layak | `water_source_code` |
| Kmr Mandi/Jamban | dropdown | Ya | tidak dibuka pada artefak | `has_bathroom_latrine`; opsi **OPEN-WRG-006** |
| Jenis Jamban | dropdown | Tidak | Leher angsa; Plengsengan; Cemplung/cubluk; Tidak Punya | `latrine_type_code` |
| Jenis TPA | dropdown | Tidak | Tanki Septik; Ipal; Kolam/Sawah/Sungai; Lubang Tanah; Pantai/Tanah Lapang/Kebun | `feces_disposal_code` |
| Jarak Septik Tank | dropdown | Ya | `< 10m`; `>= 10m` | `septic_distance_code` |
| Sumber Penerangan | dropdown | Ya | PLN; PLN Non Meteran; Non PLN; Bukan Listrik | `lighting_source_code` |
| BB Masak | dropdown | Ya | Listrik / Gas; Minyak Tanah; Arang / Kayu; Lainnya | `cooking_fuel_code` |

### 5. Lokasi dan bukti

| Jalur | Bukti/elemen tepat pada gambar | Tipe | Wajib gambar | Pemetaan |
|---|---|---|---:|---|
| Semua | peta dengan pin lokasi | peta/koordinat | tidak terbaca | `location_lat`, `location_lng` (ENC), `location_accuracy_m`; lat/lng belum di allowlist model |
| RTLH | FOTO DIRI; RUMAH DEPAN; RUMAH SAMPING; FOTO LAHAN; ATAP; LANTAI; DINDING; JAMBAN | kotak unggah gambar, indikator `0%` | tidak ditandai | `self_photo`, `house_front_photo`, `house_side_photo`, `land_photo`, `roof_photo`, `floor_photo`, `wall_photo`, `latrine_photo` |
| Backlog | Foto Calon Lahan; Bukti Pindah Tang...; Foto Penerima; Berkas Verval; Foto KTP; Foto KK; Foto KK Pemilik | unggah gambar, kecuali Berkas Verval PDF | tidak ditandai | `candidate_land_photo`, `land_transfer_proof`, `recipient_photo`, `verification_report`, `id_card_photo`, `family_card_photo`, `land_owner_family_card_photo` |

Gambar tidak menjelaskan bukti mana wajib, maksimum ukuran, atau kapan `Foto Lahan` RTLH relevan. Itu tetap **OPEN-WRG-008**; roadmap hanya menetapkan matriks bukti **demo**, bukan mengklaim aturan Dinas.

## Ketidakpastian yang tidak boleh diisi dengan tebakan

- Opsi lengkap `Usulan Dari`, Jenis Kelamin, Pekerjaan, Bahan Atap, Kmr Mandi/Jamban, dan rentang Penghasilan.
- Apakah seluruh daftar Tahun mundur tanpa batas dan apakah tahun hanya wajib ketika bantuan dipilih.
- Makna/normalisasi `Sumber Bantuan`, karena daftar visual mencampur sumber bantuan, kondisi, dan alasan penutupan.
- Wajib final untuk Sloof, Plafon, Jendela, Ventilasi, lokasi, dan setiap berkas; warna/asterisk sumber tidak boleh menggantikan aturan cabang PRD.
- Semantik `Kawasan` lintas cabang serta arti `Foto Lahan` pada layar RTLH.

## Checklist implementasi R4 untuk agent berikutnya

- [ ] Buat satu katalog kode ber-versi dari **hanya** opsi tabel audit; beri kode `SIM-*`/status provisional pada katalog yang belum lengkap.
- [ ] Tambahkan writer aman untuk `candidate_land_address` (enkripsi), `location_lat`, dan `location_lng` (enkripsi); jangan memasukkan PII/koordinat plaintext ke JSON/log.
- [ ] Rakit langkah kecil (5–10 pertanyaan) dari langkah R3, gunakan label di atas, dan simpan melalui `Housing_assessment_model`, bukan query controller.
- [ ] Tegakkan cabang: RTLH memakai struktur+sanitasi; Backlog melewatinya; perubahan cabang menonaktifkan nilai lama untuk validasi/scoring.
- [ ] Terapkan dependensi: sertifikat/asal/relasi/ukuran hanya bila calon lahan relevan; jarak septik hanya bila septic tank; tahun hanya bersama sumber bantuan.
- [ ] Implementasikan unggah satu-per-satu ke private storage: MIME/ukuran, strip EXIF, ownership, nama acak, lalu penggantian menghapus berkas lama setelah write baru sukses.
- [ ] Gunakan placeholder bukti sintetis berlabel SIMULASI; Berkas Verval hanya admin dan tidak menghambat submit warga demo.
- [ ] Buat check R4 roadmap: setiap label penting punya writer/reader atau dicatat nonaktif; uji ganti cabang; uji URL publik ditolak; uji ownership; uji replace file.

## Batas keputusan

Audit ini tidak mengubah keputusan PRD: wizard baru menggantikan survei/kalkulasi lama setelah NIK+tangal lahir (DEC-WRG-016), desil sumber menjadi routing (DEC-WRG-017), dan rekomendasi tetap evaluasi server-side setelah data cabang lengkap. Gambar adalah bukti label/opsi, bukan sumber aturan bisnis baru.
