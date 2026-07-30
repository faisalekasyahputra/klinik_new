# Roadmap — Wizard Rekam Data Perumahan

**Ditulis 30 Juli 2026.** Membangun ulang modul Perumahan mengikuti rancangan di
`new_flow/rekamdata/` (9 frame), menggantikan bentuk lama yang dibangun dari
Google Form dinas.

Bentuk lama beserta alasannya terekam di
[`REKAM_DATA_PERUMAHAN_SEBELUM_WIZARD.md`](../engineering/REKAM_DATA_PERUMAHAN_SEBELUM_WIZARD.md).
**Baca §4 dokumen itu sebelum menulis kode** — di sana ada daftar hal yang ikut
pindah dan tiga hal yang berisiko hilang tanpa disadari.

---

## 0. Sumber kebenaran

| Acuan | Statusnya |
|---|---|
| `new_flow/rekamdata/` — 9 frame | **rancangan yang dipakai** |
| Google Form dinas | instrumen yang berlaku hari ini; sumber daftar sumber dana, program, dan BNBA — **bukan** rancangan layar |
| Modul lama | sumber aturan domain yang sudah teruji; lihat §4 dokumen bentuk-lama |

Pemetaan frame → layar:

| Frame | Layar |
|---|---|
| 002 | Sambutan: "Selamat Datang / Kabupaten X / Pelaporan Tahun Y" + 3 menu — **SUDAH ADA** (`fb44f76`) |
| 003 | Langkah 1 — Periode Pelaporan: tahun + TW I–IV + status + `[LANJUT]` |
| 004 | Langkah 2 — Program yang akan dilaporkan: 6 checkbox + `[LANJUT]` |
| "Sebelum ada data" | Langkah 3 — per program: "Belum ada data" + `＋ Tambah Sumber Dana` |
| "Tambah Sumber Dana" | Modal isian: sumber dana ▼, Rencana (unit, anggaran), Realisasi (unit, anggaran), `[SIMPAN]` |
| "…Jika CSR di Pilih" | Modal yang sama + field **Nama Perusahaan** |
| "Setelah ada Data" | Langkah 3 terisi: daftar sumber dana, tiap baris punya **✏️ Edit** |
| 001 / `tabel_design` | Layar Capaian: **TABEL UNIT RENCANA** + **TABEL UNIT REALISASI** + tombol input |

---

## 1. Keputusan yang sudah diambil

| # | Keputusan | Tanggal |
|---|---|---|
| K1 | **Simpan per triwulan, tampilkan kumulatif.** Dari per-triwulan kumulatif selalu bisa dihitung; sebaliknya tidak selalu bisa dipulihkan — butuh triwulan sebelumnya ada dan benar | 30 Jul |
| K2 | **12 sumber dana** — gabungan Google Form (10) + sketsa (APBD Provinsi, BAZNAS Provinsi). Menambah nilai ENUM tidak merusak baris lama; membuang bersifat destruktif | 30 Jul, migrasi `…023` |
| K3 | **Bangun ulang penuh** butir 3–5 (gerbang per program, tambah/edit sumber dana, Rencana+Realisasi) | 30 Jul |
| K4 | Kartu KAWASAN KUMUH tetap placeholder; Sikaper tidak dihidupkan | 30 Jul |
| K5 | Layar Capaian menampilkan **tabel dulu**, tombol Input di bawahnya | 30 Jul, sudah ada |

| K6 | **BNBA dipertahankan utuh**, dipasang sebagai langkah **opsional** di wizard — tidak memblokir Kirim. Persis statusnya di Google Form: satu-satunya field opsional dari 150. Tabel `rd_perumahan_bnba` dan kedua endpointnya tidak disentuh migrasi | 30 Jul |
| K7 | **Kawasan ikut triwulanan — satu jalur, satu bentuk.** Keputusan user: "kawasan juga samakan, itu 1 jalur". Menyederhanakan skema: `periode_jenis` **tidak jadi dibuat**, cukup satu kolom `triwulan` | 30 Jul |

### Terbuka

Nol yang memblokir W1.

---

## 2. Skema sasaran

### 2a. Periode — satu kolom, satu arti

Kedua domain triwulanan (K7), jadi tidak perlu kolom penanda jenis. `bulan`
di-`RENAME` menjadi `triwulan`, nilainya 1–4.

```
rd_laporan
  triwulan     TINYINT UNSIGNED  1-4
  UNIQUE (domain, kabupaten_id, tahun, triwulan)   -- menggantikan uq_rd_laporan_periode
  current_step VARCHAR(24) NOT NULL DEFAULT 'periode'
```

**Konversi baris lama** memakai `CEIL(bulan/3)`.

⚠️ **Klaim "production nol baris" TIDAK BENAR — dikoreksi 30 Jul.** Production
punya **2 baris** `rd_laporan`: `#1 perumahan` dan `#2 kawasan`, keduanya kab
3374, 2026-7, **draft kosong tanpa satu pun baris angka dan tanpa pengirim**.

Asalnya bukan pelaporan sungguhan: keduanya lahir 29 Jul 23:01 dan 23:04 UTC,
di tengah sapuan bersesi yang saya jalankan dan saya sebut "read-only". Sapuan
itu memang hanya GET, tetapi di kode yang tayang `Rekam_Perumahan::index()`
memanggil `ambil_atau_buat_draft()` — **satu GET menulis baris**. Cacat itu
sudah diperbaiki di lokal lewat `laporan_periode()` (commit `fb44f76`) tetapi
belum dirilis.

Konsekuensinya untuk migrasi: konversi `CEIL(bulan/3)` aman di sini karena
kedua baris itu kosong, jadi tidak ada arti angka yang berubah. **Tetapi
verifikasi ulang sebelum rilis** — kalau sudah ada laporan berisi, memetakan
"kumulatif s.d. Juli" menjadi "TW III" mengubah arti angkanya, bukan labelnya.

Dua baris artefak itu sebaiknya dihapus sebelum rilis, dengan izin user.

`current_step` mengikuti idiom wizard Warga: langkah disimpan **di baris**, bukan
di sesi atau URL, supaya bisa dilanjutkan setelah keluar-masuk.

### 2b. Gerbang — program jadi induk

```
rd_perumahan_program            (ganti nama rd_perumahan_bagian)
  PRIMARY KEY (laporan_id, program)
  dilaporkan   TINYINT(1) NOT NULL DEFAULT 0
  FK laporan_id -> rd_laporan(id) ON DELETE CASCADE

rd_perumahan_baris
  UNIQUE (laporan_id, program, sumber_dana)
  rencana_unit        INT UNSIGNED    NOT NULL DEFAULT 0
  rencana_anggaran    BIGINT UNSIGNED NOT NULL DEFAULT 0
  realisasi_unit      INT UNSIGNED    NOT NULL DEFAULT 0
  realisasi_anggaran  BIGINT UNSIGNED NOT NULL DEFAULT 0
  keterangan          VARCHAR(150)    NOT NULL DEFAULT ''
  FK (laporan_id, program) -> rd_perumahan_program(laporan_id, program) ON DELETE CASCADE
```

Nama tabel ikut berubah karena isinya berubah: `bagian` dulu berarti "bagian
sumber dana". Menyimpan nama lama untuk isi baru adalah jenis kebohongan yang
paling lama bertahan.

**FK gabungan tetap dipertahankan**, hanya kunci induknya yang dibalik. Yang
dijaga tetap sama: angka tidak boleh ada tanpa gerbangnya, dan mencabut centang
program menyapu barisnya lewat CASCADE — bukan lewat JavaScript.

### 2c. Pemindahan data lama

| Lama | Baru |
|---|---|
| `rd_perumahan_bagian(laporan_id, sumber_dana, ada)` | dibuang; gerbang baru diturunkan dari program yang punya baris |
| `rd_perumahan_baris.unit` / `.anggaran` | → `realisasi_unit` / `realisasi_anggaran` |
| — | `rencana_*` = 0 (rencana memang belum pernah diisi siapa pun) |
| `bulan` | → `periode`, `periode_jenis='bulan'` |

Laporan perumahan lama **tetap `periode_jenis='bulan'`** dan tidak dikonversi ke
triwulan. Mengarang pemetaan bulan→triwulan berarti mengarang angka.

---

## 3. Alur wizard

Mengikuti idiom Warga: satu endpoint, GET merender, POST menyimpan lalu
memindahkan `current_step`.

```
Sambutan (sudah ada)
   └─ Input Capaian
        L1  Periode Pelaporan    tahun + TW I-IV + status  → [LANJUT]
        L2  Program dilaporkan   6 checkbox                → [LANJUT]
        L3  Isian per program    daftar sumber dana + Tambah/Edit
        L4  BNBA                 (menunggu T1)
        L5  Review & Kirim       ringkasan + tombol Kirim
```

**L3 adalah inti wizard.** Satu layar per program yang dicentang, dilewati satu
per satu. Tiap layar: daftar sumber dana yang sudah diisi (Rencana, Realisasi,
✏️ Edit) plus `＋ Tambah Sumber Dana`. Modal tambah/edit memuat dropdown sumber
dana, empat angka, dan — bila sumbernya `csr`, `apbn_kl_lain`, atau
`dana_lainnya` — field penyalur.

### Aturan tampilan yang wajib, bukan opsional

1. **Label anti-kumulatif.** Di kolom angkanya, bukan catatan kecil di bawah:
   *"Isi capaian TRIWULAN INI saja, bukan kumulatif sejak Januari."* Google Form
   melatih petugas mengisi kumulatif bertahun-tahun; kolom per-triwulan yang
   diisi kumulatif menggelembung tanpa tanda apa pun.
2. **Dua angka berdampingan** di layar Capaian: triwulan ini dan kumulatif s.d.
   triwulan ini. Petugas tetap melihat angka yang ia kenal, yang tersimpan tetap
   fakta terkecil.
3. **Keadaan kosong jujur.** `—`, bukan `0`. Nol yang dikarang tidak bisa
   dibedakan dari nol yang dilaporkan.

---

## 4. Tahapan

Tiap tahap **satu commit** minimal, dan tidak boleh dinyatakan selesai tanpa
bukti yang bisa gagal.

| Tahap | Isi | Selesai bila |
|---|---|---|
| **W0** | Dokumen ini + dokumen bentuk lama | ✅ selesai |
| **W1** | Migrasi `…024`: rename `bulan`→`periode`, tambah `periode_jenis` & `current_step`, balik gerbang ke program, tambah 4 kolom angka, pindahkan data lama | `migrate` naik & turun bersih di DB salinan; FK gabungan terpasang; data lama utuh dengan `unit`→`realisasi_unit`; `down()` menolak bila akan menghilangkan data |
| **W2** | Model: gerbang program, tambah/ubah/hapus baris, perhitungan kumulatif turunan, **cabut `warisi()` untuk perumahan** | Uji model CLI hijau; aturan lama (`unit_kosong`, scope, transisi) masih ditegakkan pada bentuk baru |
| **W3** | Wizard L1–L3 + modal tambah/edit | Lintasan penuh di browser: periode → program → 2 sumber dana → edit satu → nilai benar tersimpan |
| **W4** | L4 (BNBA, bila T1 ya) + L5 review & kirim + siklus peninjauan bidang | Kirim ditolak saat masih ada program tercentang tanpa satu pun baris; peninjau bisa minta perbaikan dan laporan kembali terbuka |
| **W5** | Layar Capaian dua tabel + kolom kumulatif | Tabel Rencana dan Realisasi terisi benar; kumulatif TW II = TW I + TW II, dan tidak pernah dobel |
| **W6** | Harness + mutation proof | Balikkan satu perbaikan → skrip MERAH di titik yang diramalkan → hijau lagi setelah dipulihkan |

---

## 5. Jebakan yang sudah memakan korban — jangan diulang

Dikutip dari pengalaman modul ini dan sesi 30 Jul:

1. **`warisi()` harus DICABUT untuk perumahan.** Mewarisi masuk akal saat angka
   kumulatif. Dengan penyimpanan per-triwulan, mewarisi **menggandakan capaian**
   dan hasilnya terlihat wajar.
2. **URL controller peka huruf besar-kecil di Linux.** `/Rekam_Perumahan` → 307,
   `/rekam_perumahan` → 404. Di XAMPP Windows keduanya jalan.
3. **Halaman admin tersuntik ke shell publik.** Loader portal menangkap semua
   tautan internal; `render_user_dashboard()` hanya melepas shell untuk
   `X-Shell: admin`. Jangan andalkan daftar jalur di `footer.php`.
4. **Kelas Tailwind baru wajib dipanen.** `php docs/engineering/panen_tailwind.php admin`,
   lalu **hapus** `uji_harvest_*` di root proyek — keduanya tidak ada di
   `.gitignore`, dan salah satunya PHP penerima POST.
5. **`bg-brand-primary` tanpa `dark:` = lime di latar putih.** Konvensi admin:
   `bg-blue-600 dark:bg-brand-primary` + `text-white dark:text-brand-dark`.
6. **Dialek `rekam`, bukan `dashboard`:** `space-y-4`, `p-5`, tanpa `shadow`,
   `dark:border-white/10`.
7. **Mengukur warna tepat setelah menambah kelas `dark` membaca nilai AWAL
   transisi**, bukan akhir. Tunggu transisi selesai sebelum mengukur.
8. **Membuka layar baca tidak boleh melahirkan draft.** Sudah dijaga di
   `laporan_periode()`; jangan diganti `ambil_atau_buat_draft()` saat menulis
   ulang.

---

## 6. Yang tidak berubah

`rd_laporan` (selain kolom periode & `current_step`), `rd_perumahan_bnba`,
`rd_kawasan_*`, dan seluruh aplikasi di luar Rekam Data. Tab Perumahan di
portal publik **nol sentuhan** ke tabel `rd_*`.

> 🔻 **Koreksi 30 Jul 2026 — baris ini semula menulis "seluruh modul Kawasan,
> peninjauan bidang" ikut tidak berubah. Itu tidak benar, dan ketidakbenarannya
> baru ketahuan saat mengaudit harness yang merah.** Dua hal berubah karena
> keduanya memakai kode bersama, bukan karena diputuskan:
>
> 1. **Kawasan kehilangan pewarisan antar periode.** `warisi()` dibuang di W1
>    demi Perumahan, dan Kawasan memakai `ambil_atau_buat_draft()` yang sama.
>    Petugas Kawasan yang dulu melihat daftar intervensi terbawa kini mengisi
>    dari kosong tiap triwulan. **Keputusan user 30 Jul 2026: ini DIPERTAHANKAN**
>    — Kawasan per-triwulan tanpa pewarisan, konsisten dengan K7. Spanduk
>    "N intervensi diwarisi" yang sudah mustahil muncul sudah dihapus, dan
>    kunci `diwarisi` dibuang dari nilai balik model.
> 2. **Layar peninjauan bidang menyatakan angkanya kumulatif** — kebalikan dari
>    kenyataan sejak W1, di layar tempat provinsi memutuskan terima atau minta
>    perbaikan. Sudah diperbaiki, bersama dua kalimat sejenis di
>    `kawasan_rekap.php` dan `riwayat.php`.
>
> Pelajarannya bukan tentang Kawasan: **"modul X tidak berubah" tidak bisa
> disimpulkan dari "saya tidak menyentuh berkas X"** selama X memakai fungsi
> bersama. Yang menentukan bukan berkas yang diedit, tapi pemanggil yang ikut
> terbawa.
