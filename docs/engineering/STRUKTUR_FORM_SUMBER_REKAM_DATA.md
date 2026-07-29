# Struktur Form Sumber — Rekam Data (BUKTI MENTAH)

> **Kenapa dokumen ini ada:** form Perumahan **wajib login Google**. Agent berikutnya
> tidak akan bisa membacanya sendiri. Isi di bawah adalah hasil ekstraksi struktur
> mentahnya, disimpan supaya tidak ada yang perlu menebak atau mengandalkan ingatan.
>
> **Diekstrak 29 Juli 2026.** Kalau form-nya berubah setelah tanggal itu, dokumen ini
> jadi usang — ulangi metode di §1 sebelum mempercayainya.

## 1. Metode ekstraksi (dapat diulang)

Google Forms menaruh seluruh definisi form di satu variabel JS bernama
`FB_PUBLIC_LOAD_DATA_` pada halaman `viewform`. Itu bukan API, tapi stabil dan lengkap —
memuat halaman yang tidak akan pernah tampil kalau form diisi normal.

**Form publik** (Kawasan) — tanpa login:

```bash
curl -s 'https://docs.google.com/forms/d/e/<ID>/viewform' | grep -o 'FB_PUBLIC_LOAD_DATA_ = .*;'
```

**Form terkunci** (Perumahan) — wajib sesi Google, jalankan di konsol browser yang sudah login:

```bash
copy(JSON.stringify(FB_PUBLIC_LOAD_DATA_))
```

Bentuk datanya: `FB_PUBLIC_LOAD_DATA_[1][1]` = larik item. Tiap item
`[id, judul, deskripsi, tipe, [[entryId, opsi, wajib, …, validasi]], …]`.

| Kode tipe | Arti | | Kode navigasi opsi | Arti |
|---|---|---|---|---|
| 0 | isian pendek | | `-1` | lanjut |
| 1 | paragraf | | `-2` | lanjut ke bagian berikutnya |
| 2 | pilihan tunggal (radio) | | `-3` | **kirim form** |
| 3 | dropdown | | *angka lain* | id bagian tujuan |
| 4 | kotak centang | | | |
| 8 | **pemisah halaman** | | | |
| 13 | unggah berkas | | | |

Validasi angka muncul sebagai `[[1,2,["0"],"pesan"]]` → tipe 1 = NUMBER, 2 = ≥.

## 2. Form A — Perumahan

| | |
|---|---|
| Judul | Realisasi Penanganan Backlog Perumahan (Kepemilikan dan Kelayakan) Kabupaten/Kota di Jawa Tengah 2026 |
| ID | `1FAIpQLSf0x8O9PDtwtfwO0P_AHT1xNyQ0jB-Or6vUvDXuOwZkSBLZ4g` |
| Akses | 🔒 **wajib login Google** |
| Deskripsi | "Silakan isi form ini, pengisian dapat dilakukan berkala/disimpan menjadi draf sebelum dikirim" |
| Item | 170 = 150 pertanyaan + 20 pemisah halaman |
| Halaman | 21 |
| Wajib | 149 dari 150 (hanya unggah BNBA yang opsional) |
| Rincian tipe | 2 dropdown · 10 radio · 118 isian angka (≥0) · 19 isian teks · 1 unggah |

### 2a. Halaman 1 — gerbang

| idx | Field | Tipe |
|---|---|---|
| 0 | Data Kumulatif Sampai Dengan Bulan | dropdown 12 (Januari…Desember) |
| 1 | Kabupaten/Kota | dropdown 35 |
| 2 | APBD KABUPATEN KOTA | radio Ada/Tidak Ada |

### 2b. Sepuluh bagian sumber dana

Pola tiap bagian: **halaman gerbang** (berisi radio Ada/Tidak Ada) → **halaman isian**.
Pengecualian: gerbang `APBD KABUPATEN KOTA` menumpang di halaman 1.

| # | Nama bagian (verbatim) | idx isian | Field | Program | Field keterangan |
|---|---|---|---|---|---|
| 1 | `APBD KABUPATEN KOTA` | 4–15 | 12 | 6 | — |
| 2 | `APBN BSPS (dari Kementerian PKP)` | 19–30 | 12 | 6 | — |
| 3 | `APBN DAK` | 34–45 | 12 | 6 | — |
| 4 | `APBN Kemensos` | 49–60 | 12 | 6 | — |
| 5 | `APBN DANA DESA` | 64–75 | 12 | 6 | — |
| 6 | `APBN dari Kementerian/Lembaga Lain` | 79–97 | **19** | 6 | 7 ⚠️ *(seharusnya 6)* |
| 7 | `BAZNAS RI` | 101–112 | 12 | 6 | — |
| 8 | `BAZNAS Kab/Kota` | 116–125 | **10** | **5** ⚠️ | — |
| 9 | `CSR` | 129–146 | 18 | 6 | 6 |
| 10 | `Dana Lainnya` | 150–167 | 18 | 6 | 6 |

**Program (6, urutan tetap di semua bagian):**
`PK RTLH` · `PB RTLH` · `PB BACKLOG` · `PK BENCANA` · `PB BENCANA` · `PB RELOKASI`

**Pola label per program:** `<NAMA BAGIAN>  <PROGRAM> (unit)` dan
`<NAMA BAGIAN>  <PROGRAM> Anggaran (Rp.)`. Keduanya angka ≥ 0, teks bantu
*Isikan "0" bila tidak ada*.

**Field keterangan** (hanya tiga bagian), satu per program:

| Bagian | Pola label | Deskripsi di form |
|---|---|---|
| K/L Lain | `Kementerian Sumber <PROGRAM>` | — |
| CSR | `Perusahaan CSR <PROGRAM>` | "Sebutkan nama perusahan penyalur bantuan CSR" |
| Dana Lainnya | `Sumber Anggaran Dana Lainnya <PROGRAM>` | "sebutkan sumber penyalur dana lainnya" |

Deskripsi bagian Dana Lainnya: *"Dana Lainnya merupakan anggaran di luar kategori sumber
anggaran yang disebutkan sebelumnya, contoh : gotong royong, swadaya, dll"*

### 2c. Halaman 21 — unggahan

| idx | Field | Tipe | Wajib |
|---|---|---|---|
| 169 | `UPLOAD BNBA Realisasi Penangan Perumahan 2026 Sesuai Format Berikut -> Download` | unggah berkas | **opsional** |

BNBA = By Name By Address. Ada tautan template unduhan di label.

### 2d. Peta percabangan (verbatim dari data)

```
[2]   APBD KABUPATEN KOTA  :: Ada→halaman isian APBD  | Tidak Ada→gerbang APBN BSPS
[17]  APBN BSPS            :: Ada→halaman isian BSPS  | Tidak Ada→gerbang APBN DAK
[32]  APBN DAK             :: Ada→lanjut              | Tidak Ada→gerbang APBN Kemensos
[47]  APBN Kemensos        :: Ada→lanjut              | Tidak Ada→gerbang APBN DANA DESA
[62]  APBN DANA DESA       :: Ada→lanjut              | Tidak Ada→gerbang K/L Lain
[77]  K/L Lain             :: Ada→lanjut              | Tidak Ada→gerbang BAZNAS RI
[99]  BAZNAS RI            :: Ada→lanjut              | Tidak Ada→gerbang BAZNAS Kab/Kota
[114] BAZNAS Kab/Kota      :: Ada→lanjut              | Tidak Ada→gerbang CSR
[127] CSR                  :: Ada→lanjut              | Tidak Ada→gerbang Dana Lainnya
[148] Dana Lainnya         :: Ada→lanjut              | Tidak Ada→lanjut   ⚠️ CACAT
```

## 3. Form B — Kawasan

| | |
|---|---|
| Judul | Realisasi Penanganan Kawasan Permukiman Kabupaten/Kota di Jawa Tengah 2026 |
| ID | `1FAIpQLSe6ryq9aQxK8vCoS997z5nAd2-FmvbQQ7x9hwxP02I0i4x9Tw` |
| Akses | 🌐 **publik, tanpa login** |
| Item | 178 = 157 pertanyaan + 21 pemisah halaman |
| Halaman | 22 |
| Wajib | 155 dari 157 |

### 3a. Halaman 1–2

| idx | Field | Tipe | Wajib | Validasi |
|---|---|---|---|---|
| 0 | Kabupaten/Kota | dropdown 35 | ✔ | — |
| 1 | Penanganan Kawasan Permukiman Kumuh | radio Ada/Tidak Ada | ✔ | — |
| 3 | Data Kumulatif Sampai Dengan Bulan | dropdown 12 | ✔ | — |
| 4 | Apakah terdapat progres realisasi? | radio Ya/Tidak | ✔ | — |
| 5 | Bila "Tidak", jelaskan progres kegiatan saat ini | paragraf | ✗ | — |
| 6 | Total Luas Penanganan Kawasan Permukiman Kumuh (Ha) | isian pendek | ✔ | **tidak ada** ⚠️ |
| 7 | Total Anggaran Penanganan Kawasan Permukiman Kumuh (Rp.) | isian pendek | ✔ | **tidak ada** ⚠️ |
| 8 | Sumber Anggaran Penanganan Kawasan Permukiman Kumuh | paragraf | ✔ | — |

Deskripsi idx 8: *"Silakan diisi sumber penangannya dari mana saja, selanjutnya untuk
detail ada pada section berikutnya"* → menegaskan field ini **duplikat** dari dropdown
terstruktur per intervensi.

Percabangan: `[1] Tidak Ada → KIRIM` · `[4] Tidak → KIRIM`

### 3b. Halaman 3–22 — blok intervensi, diulang 20×

Tiap blok, urutan tetap:

| Field | Tipe | Validasi |
|---|---|---|
| Indikator Penanganan | dropdown 7 | — |
| Nama Kegiatan/Program | paragraf | — |
| Lokasi Kegiatan/Program (RT, RW, Desa/Kelurahan, Kecamatan) | paragraf | — |
| Sumber Anggaran | dropdown 7 | — |
| Volume Penanganan | isian pendek | angka ≥ 0 |
| Nilai Anggaran (Rp.) | isian pendek | angka ≥ 0 |
| Nilai Padat Karya (Rp.) | isian pendek | angka ≥ 0 |
| *Apakah ada penanganan kawasan kumuh lainnya?* | radio Ya/Tidak | **hanya blok 1–9** ⚠️ |

Indeks blok: 1=`10–17`, 2=`19–26`, 3=`28–35`, 4=`37–44`, 5=`46–53`, 6=`55–62`, 7=`64–71`,
8=`73–80`, 9=`82–89`, **10=`91–97` (tanpa gerbang)**, 11=`99–105`, 12=`107–113`,
13=`115–121`, 14=`123–129`, 15=`131–137`, 16=`139–145`, 17=`147–153`, 18=`155–161`,
19=`163–169`, 20=`171–177`.

Deskripsi halaman blok 1: *"Bila ada lebih dari 1 penanganan, di pertanyaan terakhir
"Apakah ada penanganan kawasan kumuh lainnya?" silakan pilih jawaban "Ya""*

Percabangan blok 1–9: `Ya → blok berikutnya` · `Tidak → KIRIM`

## 4. Daftar nilai, verbatim

### Kabupaten/Kota (35) — identik di kedua form

```
Banjarnegara, Banyumas, Batang, Blora, Boyolali, Brebes, Cilacap, Demak, Grobogan,
Jepara, Karanganyar, Kebumen, Kendal, Klaten, Kudus, Magelang, Pati, Pekalongan,
Pemalang, Purbalingga, Purworejo, Rembang, Semarang, Sragen, Sukoharjo, Tegal,
Temanggung, Wonogiri, Wonosobo, Kota Magelang, Kota Pekaloongan, Kota Salatiga,
Kota Semarang, Kota Surakarta, Kota Tegal
```

⚠️ Tanpa prefiks "Kabupaten"; `Kota Pekaloongan` salah ketik. Tabel `kabupaten` di aplikasi
memakai "Kabupaten Banjarnegara" + kode Kemendagri → **butuh tabel pemetaan saat impor**.

### Sumber dana — DUA DAFTAR BERBEDA

| Perumahan (10) | Kawasan (7) |
|---|---|
| APBD Kabupaten Kota | APBD Kab/Kota |
| APBN BSPS (dari Kementerian PKP) | APBN |
| APBN DAK | APBD Provinsi |
| APBN Kemensos | Dana Desa |
| APBN Dana Desa | CSR |
| APBN dari Kementerian/Lembaga Lain | Baznas |
| BAZNAS RI | Dana Lainnya |
| BAZNAS Kab/Kota | |
| CSR | |
| Dana Lainnya | |

> **Tidak ada pemetaan resmi.** `APBN` Kawasan kira-kira mencakup BSPS+DAK+Kemensos+K/L
> Lain; `Baznas` tunggal vs BAZNAS RI + BAZNAS Kab/Kota. Sampai dinas menetapkannya,
> **angka dua domain tidak boleh dijumlahkan.**

### Indikator Penanganan Kawasan (7)

```
Bangunan Gedung unit)   ← salah ketik, kurung buka hilang
Jalan Lingkungan (m')
Air Minum (KK)
Drainase (m')
Air Limbah (KK)
Persampahan (KK)
Proteksi Kebakaran      ← satu-satunya tanpa satuan
```

### Bulan (12) — identik di kedua form

`Januari` … `Desember`

## 5. Cacat form — daftar lengkap dengan lokasi

| # | Form | idx | Cacat | Akibat nyata |
|---|---|---|---|---|
| 1 | Kawasan | 91–97 | Blok intervensi **10 tanpa** pertanyaan "Apakah ada penanganan lainnya?"; pemisah halaman blok 11 bernilai `null` (lanjut otomatis) | Kabupaten dengan ≥10 intervensi terjebak di blok 11–20: **70 field wajib tanpa tombol kirim**. Tidak bisa submit tanpa mengarang data |
| 2 | Perumahan | 148 | `Dana Lainnya` opsi "Tidak Ada" bernilai *lanjut*, bukan *lompat* | Menjawab "Tidak Ada" tetap masuk 18 field wajib |
| 3 | Perumahan | 116–125 | `BAZNAS Kab/Kota` hanya 5 program — **PB BENCANA hilang** | Bantuan bencana Baznas kabupaten tidak pernah bisa dilaporkan |
| 4 | Perumahan | 87, 88 | `Kementerian Sumber PB BACKLOG` **dobel**, keduanya wajib | Satu program kehilangan field keterangannya; jawaban sama diketik dua kali |
| 5 | Kawasan | 6, 7 | Total Luas (Ha) & Total Anggaran (Rp.) **tanpa validasi angka** | Terkumpul sebagai teks bebas |
| 6 | Kawasan | 4→6,7 | "Tidak ada progres" tetap mewajibkan Total Luas & Total Anggaran | Nilai palsu ("0"/"-") masuk rekap |
| 7 | Kawasan | 8 | "Sumber Anggaran" paragraf bebas duplikat dari dropdown per intervensi | Dua sumber kebenaran yang bisa bertentangan |
| 8 | Kawasan | 7 | Total Anggaran diketik tangan, padahal = Σ Nilai Anggaran intervensi | Total pasti berselisih dengan penjumlahannya |

**Salah ketik:** `Kota Pekaloongan` (kedua form) · `Intevensi` (kedua form, ×20 di Kawasan) ·
`Bangunan Gedung unit)` · `CSR PB RELOKASI` idx 144 kehilangan label `(unit)` ·
`UPLOAD BNBA Realisasi Penangan Perumahan` (kurang "Penanganan").

## 6. Sifat operasional yang ikut terbaca

| Sifat | Perumahan | Kawasan |
|---|---|---|
| Perlu login | ✔ ya | ✘ **tidak — anonim** |
| Kabupaten dipilih manual dari dropdown | ✔ | ✔ |
| Ritme | bulanan kumulatif | bulanan kumulatif |
| Mengumpulkan target/rencana | ✘ | ✘ |
| Status / penguncian / review | ✘ | ✘ |
| Batas jumlah entri | 10 sumber dana tetap | **20 intervensi** |
| Unggahan bukti | BNBA (opsional) | ✘ |

> **Konsekuensi keamanan:** siapa pun yang punya tautan form Kawasan dapat mengirim
> laporan atas nama kabupaten mana pun, tanpa jejak identitas. Ini hilang sendirinya
> begitu pindah ke aplikasi (wilayah diambil dari sesi `admin_kabkota`).

## 7. Yang TIDAK ada di form mana pun

Dicatat supaya tidak dicari-cari:

- **`TABEL UNIT RENCANA`** — ada di spreadsheet dinas (`new_flow/rekamdata/tabel_design.png`),
  tapi **tidak dikumpulkan lewat form mana pun**. Sumbernya belum diketahui.
- **`APBD Provinsi` dan `Baznas Prov`** — ada di spreadsheet, tidak ada di form Perumahan.
- **`APBN DAK` dan `APBN Kemensos`** — ada di form, tidak ada di spreadsheet.
- **Nama perusahaan CSR di Kawasan** — form Perumahan punya, Kawasan tidak. Semua CSR
  di data Kawasan anonim.

## 8. Berkas terkait

| Berkas | Isi |
|---|---|
| [`PRD_REKAM_DATA.md`](../product/PRD_REKAM_DATA.md) | Ruang lingkup, keputusan user bertanggal, alasan tiap keputusan skema |
| [`SKEMA_DATA_REKAM_DATA.md`](../architecture/SKEMA_DATA_REKAM_DATA.md) | ERD, analisis normalisasi, DDL terpasang, pemetaan field→kolom |
| [`ROADMAP_REKAM_DATA.md`](../product/ROADMAP_REKAM_DATA.md) | Tahapan D0–D6, flowchart, definisi selesai, protokol handoff |
| `new_flow/rekamdata/*.png` | Sketsa alur dari dinas — **bukan spesifikasi form**, lihat PRD §1 |
