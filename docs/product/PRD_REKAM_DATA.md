# PRD — Rekam Data (Pelaporan Capaian Kabupaten/Kota)

> Ditulis 29 Juli 2026. Sumber kebenaran: **dua Google Form yang dipakai dinas hari ini**,
> dibaca langsung dari struktur mentahnya (`FB_PUBLIC_LOAD_DATA_`), bukan dari deskripsi lisan.
> Mockup di `new_flow/rekamdata/` adalah **sketsa alur dari dinas, bukan spesifikasi form** —
> ditegaskan user 29 Jul 2026 setelah form aktualnya diperiksa.

## 1. Ruang lingkup

Satu menu **Rekam Data** dengan dua domain pelaporan, diisi oleh Admin Kab/Kota,
ditinjau oleh Admin Bidang di provinsi:

| Domain | Menggantikan | Peninjau |
|---|---|---|
| Perumahan | Form "Realisasi Penanganan Backlog Perumahan (Kepemilikan dan Kelayakan) … 2026" | `admin_bidang` · `bidang_kode='perumahan'` |
| Kawasan | Form "Realisasi Penanganan Kawasan Permukiman … 2026" | `admin_bidang` · `bidang_kode='kawasan'` |

Di luar ruang lingkup fase ini: rekap tingkat provinsi lintas-domain, dan penggantian
angka dummy di `Statistika.php` (lihat §7).

## 2. Keputusan user — 29 Juli 2026

Diambil setelah kedua form aktual dibedah, dan **membatalkan tiga keputusan awal**
yang dibuat saat acuannya masih mockup:

| Keputusan | Nilai | Catatan |
|---|---|---|
| Ritme | **Bulanan kumulatif** ("Data Kumulatif Sampai Dengan Bulan") | Membatalkan rencana awal "triwulan" untuk Perumahan |
| Target/Rencana | **Tidak dikumpulkan** | Membatalkan "rencana sekali setahun". `TABEL UNIT RENCANA` di spreadsheet dinas tidak pernah diisi lewat form mana pun — ditunda, bukan dibuang |
| Isi angka | Realisasi kumulatif sejak Januari | Membatalkan "capaian periode itu saja" |
| Siklus status | **Draft → Terkirim (terkunci) → Perlu Perbaikan** | Tidak ada di Google Form. Meniru pola SRP2 & Warga R6 |
| Pengisi | Satu akun `admin_kabkota` untuk kedua domain | 35 akun, bukan 70 |
| Daftar sumber dana Perumahan | **Ikut form (10)** | `APBD Provinsi` dan `Baznas Prov` di spreadsheet sengaja tidak diadakan |
| Nama penyumbang | **Satu per (sumber dana × program)** | Setara form, impor data lama lurus |

Konsekuensi yang mengikat implementasi: **laporan bulan berikutnya wajib mewarisi isi bulan
sebelumnya.** Karena angkanya kumulatif, memaksa petugas mengetik ulang seluruh daftar akan
membuat angka menyusut diam-diam. Ini syarat, bukan fitur tambahan.

## 3. Struktur form sumber

### 3a. Perumahan — 170 item, 21 halaman, 149 dari 150 pertanyaan wajib

- **Hal. 1** — Data Kumulatif Sampai Dengan Bulan (dropdown 12) · Kabupaten/Kota (dropdown 35) ·
  gerbang `APBD KABUPATEN KOTA` (Ada/Tidak Ada)
- **Hal. 2–20** — sepuluh sumber dana, tiap satu: halaman gerbang (Ada/Tidak Ada) + halaman isian.
  `Ada` → halaman isian · `Tidak Ada` → lompat ke gerbang sumber dana berikutnya
- **Hal. 21** — unggah BNBA (berkas, **satu-satunya field opsional**, ada template unduhan)

Sumber dana (10): `APBD Kabupaten Kota` · `APBN BSPS (dari Kementerian PKP)` · `APBN DAK` ·
`APBN Kemensos` · `APBN Dana Desa` · `APBN dari Kementerian/Lembaga Lain` · `BAZNAS RI` ·
`BAZNAS Kab/Kota` · `CSR` · `Dana Lainnya`

Program (6, sama dengan spreadsheet): `PK RTLH` · `PB RTLH` · `PB BACKLOG` · `PK BENCANA` ·
`PB BENCANA` · `PB RELOKASI`

Tiap (sumber × program) = `(unit)` + `Anggaran (Rp.)`, keduanya angka ≥ 0 dengan petunjuk
*Isikan "0" bila tidak ada*. Tiga sumber dana punya field keterangan tambahan per program:
Kementerian/Lembaga Lain → `Kementerian Sumber …`, CSR → `Perusahaan CSR …`,
Dana Lainnya → `Sumber Anggaran …`.

### 3b. Kawasan — 178 item, 22 halaman, 155 dari 157 pertanyaan wajib

- **Hal. 1** — Kabupaten/Kota · gerbang `Penanganan Kawasan Permukiman Kumuh` (Ada → lanjut, Tidak Ada → kirim)
- **Hal. 2** — Data Kumulatif Sampai Dengan Bulan · `Apakah terdapat progres realisasi?`
  (Tidak → kirim) · penjelasan bila tidak (opsional) · Total Luas (Ha) · Total Anggaran (Rp.) ·
  Sumber Anggaran (paragraf bebas)
- **Hal. 3–22** — blok intervensi diulang **20×**: Indikator Penanganan (7 opsi) · Nama Kegiatan/Program ·
  Lokasi (RT/RW/Desa/Kecamatan) · Sumber Anggaran (7 opsi) · Volume · Nilai Anggaran · Nilai Padat Karya

Indikator (7): `Bangunan Gedung (unit)` · `Jalan Lingkungan (m')` · `Air Minum (KK)` ·
`Drainase (m')` · `Air Limbah (KK)` · `Persampahan (KK)` · `Proteksi Kebakaran` *(tanpa satuan)*

Sumber anggaran Kawasan (7, **berbeda dari Perumahan**): `APBN` · `APBD Provinsi` ·
`APBD Kab/Kota` · `Dana Desa` · `CSR` · `Baznas` · `Dana Lainnya`

> ⚠️ Dua daftar sumber dana ini **tidak bisa dijumlahkan** satu sama lain sampai dinas
> menetapkan pemetaan resmi (`APBN` Kawasan ≈ `BSPS`+`DAK`+`Kemensos`+`K/L Lain` Perumahan;
> `Baznas` tunggal vs `BAZNAS RI`+`BAZNAS Kab/Kota`). Rekap provinsi menampilkan
> dua domain berdampingan, jangan digabung.

## 4. Cacat form sumber yang ditemukan

Dicatat karena menjelaskan kenapa data historis bisa terlihat aneh, dan karena
semuanya hilang sendirinya begitu pindah ke aplikasi:

| # | Form | Cacat | Akibat |
|---|---|---|---|
| 1 | Kawasan | Blok intervensi 10 **tidak punya** pertanyaan "Apakah ada penanganan lainnya?"; halaman 11 lanjut otomatis | Kabupaten dengan ≥10 intervensi terjebak di 10 halaman berikutnya, **70 field wajib tanpa tombol kirim**. Tidak bisa submit tanpa mengarang data |
| 2 | Perumahan | `Dana Lainnya` → "Tidak Ada" bernilai *lanjut*, bukan *lompat* | Menjawab "Tidak Ada" tetap masuk 18 field wajib Dana Lainnya |
| 3 | Perumahan | `BAZNAS Kab/Kota` kehilangan program **PB BENCANA** (5 program, bukan 6) | Bantuan bencana dari Baznas kabupaten tidak pernah bisa dilaporkan |
| 4 | Perumahan | `Kementerian Sumber PB BACKLOG` muncul **dua kali**, keduanya wajib | Satu program kehilangan field keterangannya; petugas mengetik jawaban sama dua kali |
| 5 | Kawasan | `Total Luas (Ha)` & `Total Anggaran (Rp.)` **tanpa validasi angka** | Terkumpul sebagai teks bebas |
| 6 | Kawasan | Jawaban "Tidak ada progres" tetap mewajibkan Total Luas & Total Anggaran | Nilai palsu ("0"/"-") masuk rekap |

Salah ketik yang ikut terbawa: `Kota Pekaloongan` (dropdown Kawasan disalin ke Perumahan),
`Intevensi` (kedua form), `Bangunan Gedung unit)`, `CSR PB RELOKASI` tanpa label `(unit)`.

Perbaikan yang ikut dibawa ke aplikasi: BAZNAS Kab/Kota mendapat PB BENCANA, dan
Kawasan mendapat `keterangan_sumber` untuk CSR (form aslinya tidak punya — semua CSR anonim).

## 5. Skema data

Lima tabel. Siklus status ditulis sekali di amplop bersama, dipakai kedua domain.

```
rd_laporan                 domain ENUM('perumahan','kawasan'), kabupaten_id, tahun,
                           bulan TINYINT (1-12), bagian_ada, status,
                           submitted_at/by, reviewed_at/by, catatan_admin
                           UNIQUE(domain, kabupaten_id, tahun, bulan)

rd_perumahan_baris         laporan_id, sumber_dana, program,
                           unit, anggaran, keterangan
                           UNIQUE(laporan_id, sumber_dana, program)

rd_perumahan_bnba          laporan_id, private_path, mime_type, ukuran, uploaded_at/by

rd_kawasan_ringkasan       laporan_id PK, ada_penanganan, ada_progres,
                           catatan_progres, total_luas_ha

rd_kawasan_intervensi      laporan_id, urutan, indikator, nama_kegiatan, lokasi_teks,
                           sumber_anggaran, keterangan_sumber, volume,
                           nilai_anggaran, nilai_padat_karya
```

Keputusan skema yang disengaja:

- **`bagian_ada`** membedakan "sumber dana ini nihil" dari "belum diisi". Tanpa itu, nol dan
  kosong tidak terbedakan di rekap.
- **Satuan tidak disimpan** — diturunkan dari `indikator`. Menyimpannya mengundang
  satuan yang bertentangan dengan indikatornya.
- **Total anggaran Kawasan tidak disimpan** — `SUM(nilai_anggaran)`. Angka yang diketik tangan
  pasti akan berselisih dengan penjumlahannya.
- **`Sumber Anggaran` paragraf bebas di hal. 2 Kawasan tidak dipindah** — sudah terstruktur
  per intervensi, menyimpan keduanya berarti dua sumber kebenaran.
- **Batas 20 intervensi tidak dibawa** — itu batasan Google Form, bukan batasan domain.
- FK ke `usr_users(id)` harus `INT` **signed** — kolomnya `int(11)` signed, `UNSIGNED` gagal errno 150 (AGENTS.md §0e).
- Uang `BIGINT UNSIGNED` rupiah penuh. Luas `DECIMAL(12,2)`. Volume `DECIMAL(14,2)` (m' dan Ha pecahan).

## 6. Yang membaik dibanding Google Form

| | Google Form | Aplikasi |
|---|---|---|
| Identitas pengisi | Kawasan **anonim** — siapa pun bisa mengisi atas nama kabupaten mana pun | Ter-scope `admin_kabkota` dari sesi |
| Kabupaten | dropdown 35, bisa salah pilih | dari sesi, tidak bisa diketik |
| Batas intervensi | 20 | tanpa batas |
| Status & penguncian | tidak ada | Draft / Terkirim / Perlu Perbaikan |
| Umpan balik provinsi | tidak ada | catatan admin + resubmit |
| Riwayat antar periode | terpisah, tidak saling tahu | diwarisi dari bulan sebelumnya |
| Validasi | sebagian; Ha & Total Anggaran teks bebas | seluruhnya di server |

## 7. Yang sengaja belum dikerjakan

1. **Rekap tingkat provinsi lintas-domain** — menunggu pemetaan resmi sumber dana (§3b).
2. **Rencana/Target** — tidak dikumpulkan form mana pun; ditunda sampai dinas memastikan
   `TABEL UNIT RENCANA` memang dipakai.
3. **Mengganti angka dummy di `Statistika.php`** — seluruh isi `$data['stats']` di sana masih
   karangan. Modul ini sumber angka nyata pertamanya, tapi penggantiannya pekerjaan terpisah.
4. **Impor data historis** dari respons Google Form — butuh pemetaan nama kabupaten
   (`Banjarnegara` → `Kabupaten Banjarnegara` + kode Kemendagri, plus salah ketik `Kota Pekaloongan`).
5. **Provisioning 35 akun `admin_kabkota`** — hari ini baru ada satu akun demo (Kota Semarang).
6. **BPHTB & PBG — DILUAR RUANG LINGKUP, keputusan user 29 Jul 2026.** Diperiksa dan sengaja
   tidak diambil ke fase 1. Bentuknya beda total dari dua menu di atas: bukan agregat
   sumber-dana × program, melainkan **data mentah tingkat baris** — satu transaksi BPHTB
   (NIK + alamat pihak pengalih DAN penerima, penghasilan, NJOP, nilai SSPD) atau satu
   penerima PBG (NIK, nama, alamat, kecamatan, kelurahan).
   Sumber: spreadsheet "Format BPHTB dan PBG", 4 sheet (2 template + 2 daftar tautan).
   > **Kalau nanti diangkat, ini alasannya layak didahulukan:** provinsi tidak mengumpulkan
   > datanya, melainkan **tautan** ke 35 spreadsheet milik kabupaten. Perlindungan NIK
   > bergantung pada 35 orang berbeda yang mengatur setelan berbagi dengan benar — sheet-nya
   > sendiri memuat instruksi agar kabupaten mengubah akses jadi *restricted*. Per 29 Jul 2026
   > **nol dari 35 tautan terisi**, jadi belum ada data historis yang perlu dipindah dan belum
   > ada yang bisa bocor; itu justru waktu termurah untuk menggantinya. Aplikasi ini sudah punya
   > persis yang dibutuhkannya: NIK terenkripsi AES-256-GCM (`KPKP_DATA_KEY`), berkas privat
   > di luar webroot, dan scope wilayah per `admin_kabkota`.
   > Catatan terpisah: sheet `CONTOH FORMAT` memuat nama + alamat lengkap orang sungguhan
   > sebagai contoh (NIK disamarkan, sisanya tidak) — sebaiknya diganti data karangan.
