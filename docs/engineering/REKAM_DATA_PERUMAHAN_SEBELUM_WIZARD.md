# Rekam Data — Perumahan: bentuk sebelum wizard

**Ditulis 30 Juli 2026, sebelum pembangunan ulang mengikuti `new_flow/rekamdata/`.**

Dokumen ini merekam modul Perumahan **sebagaimana ia berdiri hari ini**, lengkap
dengan alasan tiap keputusan. Gunanya satu: begitu migrasi membalik kunci,
bentuk lama tidak bisa lagi dibaca dari kode, dan aturan yang mahal ditemukan
akan ikut hilang kalau tidak ditulis sekarang.

Bagian §4 adalah yang paling berguna untuk pekerjaan berikutnya — pemetaan
eksplisit mana yang **ikut pindah**, mana yang **diganti**, dan mana yang
**berisiko hilang tanpa disadari**.

---

## 1. Dari mana bentuk ini berasal

Dibangun dari **Google Form dinas**, bukan dari `new_flow/rekamdata/`:

| | |
|---|---|
| Judul form | *Realisasi Penanganan Backlog Perumahan (Kepemilikan dan Kelayakan) Kabupaten/Kota di Jawa Tengah 2026* |
| Field pertama | "Data Kumulatif Sampai Dengan **Bulan**" — dropdown 12 bulan |
| Struktur | 21 halaman, 150 pertanyaan, **149 wajib** |
| Percabangan | per **sumber dana**: "Ada" → halaman isian, "Tidak Ada" → gerbang berikutnya |
| Halaman terakhir | unggah **BNBA** (By Name By Address), satu-satunya field opsional |
| Rencana | **tidak ada sama sekali** — kata "Realisasi" ada di judulnya |

Ekstraksi mentahnya: [`STRUKTUR_FORM_SUMBER_REKAM_DATA.md`](STRUKTUR_FORM_SUMBER_REKAM_DATA.md).

Form itu adalah **instrumen yang dipakai dinas hari ini**, bukan rancangan
aplikasi. Rancangan aplikasinya ada di `new_flow/rekamdata/` (9 frame) dan
berbeda pada tiga sumbu: triwulan bukan bulan, gerbang per program bukan per
sumber dana, dan Rencana di samping Realisasi.

---

## 2. Skema

```
rd_laporan                     amplop bersama perumahan & kawasan
  id
  domain          ENUM(perumahan, kawasan)
  kabupaten_id    FK kabupaten(id)
  tahun           SMALLINT
  bulan           TINYINT      1-12, "kumulatif sampai dengan bulan ini"
  status          ENUM(draft, terkirim, perlu_perbaikan)
  submitted_at / submitted_by  FK usr_users(id) ON DELETE SET NULL
  reviewed_at  / reviewed_by   FK usr_users(id) ON DELETE SET NULL
  catatan_admin   TEXT         catatan peninjau saat minta perbaikan

rd_perumahan_bagian            gerbang "Ada / Tidak Ada" per SUMBER DANA
  PRIMARY KEY (laporan_id, sumber_dana)
  ada             TINYINT(1)
  FK laporan_id -> rd_laporan(id) ON DELETE CASCADE

rd_perumahan_baris             angka per (sumber dana x program)
  UNIQUE (laporan_id, sumber_dana, program)
  unit            INT UNSIGNED
  anggaran        BIGINT UNSIGNED     rupiah penuh
  keterangan      VARCHAR(150)        penyalur, hanya 3 sumber tertentu
  FK (laporan_id, sumber_dana) -> rd_perumahan_bagian(laporan_id, sumber_dana) ON DELETE CASCADE

rd_perumahan_bnba              satu berkas per laporan
  laporan_id UNIQUE, private_path, mime_type, ukuran, uploaded_by
```

**12 sumber dana** (`Rekam_data_model::SUMBER_PERUMAHAN`): `apbd_provinsi`,
`apbd_kabkota`, `apbn_bsps`, `apbn_dak`, `apbn_kemensos`, `apbn_dana_desa`,
`apbn_kl_lain`, `baznas_ri`, `baznas_provinsi`, `baznas_kabkota`, `csr`,
`dana_lainnya`. Sepuluh dari Google Form; `apbd_provinsi` dan `baznas_provinsi`
ditambahkan 30 Jul dari sketsa user (migrasi `…023`).

**6 program**: `pk_rtlh`, `pb_rtlh`, `pb_backlog`, `pk_bencana`, `pb_bencana`,
`pb_relokasi`.

**3 sumber berketerangan**: `apbn_kl_lain`, `csr`, `dana_lainnya` — di form
aslinya bernama beda (Kementerian Sumber / Perusahaan CSR / Sumber Anggaran
Dana Lainnya) tetapi sama peran, jadi disatukan ke satu kolom.

### Kenapa FK-nya gabungan, bukan langsung ke laporan

`rd_perumahan_baris` menunjuk `rd_perumahan_bagian`, **bukan** `rd_laporan`.
Akibatnya angka hanya boleh ada kalau sumber dananya sudah dinyatakan "Ada",
dan membatalkan centang sumber dana **menyapu angkanya lewat CASCADE** — bukan
lewat JavaScript, bukan lewat pembersihan terjadwal. Ini yang membuat "nihil"
dan "belum diisi" tidak pernah tertukar di rekap.

Inilah kunci yang akan dibalik oleh wizard: di sana **program** yang jadi induk.

---

## 3. Aturan yang ditegakkan model

`Rekam_data_model` adalah **satu-satunya pintu tulis**. Controller tidak pernah
menyentuh tabel `rd_*` langsung.

| Kode gagal | Aturan |
|---|---|
| `periode_invalid` | tahun 2020–2100, bulan 1–12, kabupaten ≥ 1 |
| `sumber_invalid` / `program_invalid` | hanya nilai dari konstanta model |
| `bagian_belum_ada` | angka ditolak bila sumber dananya belum dinyatakan "Ada" |
| `angka_invalid` | unit & anggaran wajib bulat tidak negatif |
| **`unit_kosong`** | **anggaran > 0 tetapi unit = 0 ditolak** |
| `luar_scope` | laporan di luar kabupaten pemanggil tidak ditemukan — gerbang, bukan penyaring tampilan |
| `transisi_invalid` | hanya transisi yang terdaftar |
| `catatan_wajib` | minta perbaikan tanpa catatan ditolak |
| `belum_lengkap` | kirim ditolak bila masih ada sumber dana belum dijawab |

### Siklus status

```
draft ──kirim──> terkirim ──minta_perbaikan──> perlu_perbaikan ──kirim──> terkirim
                     └──terima──> (selesai)
```

`STATUS_TERBUKA = [draft, perlu_perbaikan]` — hanya keduanya bisa ditulis.
`terkirim` terkunci sampai peninjau mengembalikannya.

### Dua aturan yang paling mudah dilanggar ulang

1. **Nol `SUM()` antar periode.** Angkanya kumulatif; menjumlahkan bulan
   melipatgandakan capaian. Rekap SELALU menyebut periodenya eksplisit dan
   tidak pernah menggabungkan dua periode.
2. **Nol tabel nol.** Rekap tidak merender tabel berisi nol saat tidak ada
   laporan terkirim; sumber tanpa baris ditampilkan `—`, bukan `0`. Nol yang
   dikarang tidak bisa dibedakan dari nol yang benar-benar dilaporkan.

### Pewarisan antar periode

`warisi()` — draft baru menyalin isi laporan **`terkirim` terakhir pada tahun
yang sama dengan bulan lebih kecil**. Alasannya angkanya kumulatif: mengetik
ulang dari nol tiap bulan membuat capaian menyusut. Layar menampilkan berapa
baris yang diwarisi supaya pewarisan itu terlihat, bukan diam-diam.

---

## 4. Pemetaan ke wizard baru

### Ikut pindah — jangan dibangun ulang dari nol

| Hal | Catatan |
|---|---|
| 12 sumber dana & 6 program | daftar sah, sudah termigrasi |
| `keterangan` penyalur untuk 3 sumber | setara "Nama Perusahaan" di frame CSR |
| Siklus draft → terkirim → perlu_perbaikan | frame 003 memajang "Status: Draft", jadi memang dipakai |
| Scope kabupaten dari sesi sebagai **gerbang** | tidak ada dropdown wilayah di layar mana pun |
| `unit_kosong` | anggaran tanpa unit tetap tidak masuk akal di bentuk apa pun |
| Larangan `SUM()` antar periode | **makin penting** — lihat catatan kumulatif di bawah |
| Keadaan kosong yang jujur (`—` bukan `0`) | |
| `rd_laporan`, `rd_perumahan_bnba` | tidak berubah bentuk |
| Peninjauan bidang (`Rekam_Tinjauan`) | bekerja di level laporan, bukan baris |

### Diganti

| Sekarang | Menjadi |
|---|---|
| `bulan` 1–12 kumulatif | **triwulan** 1–4, disimpan **per triwulan**, kumulatif dihitung saat tampil |
| Gerbang per sumber dana (12 Ada/Tidak Ada) | gerbang per **program** (6 checkbox) |
| `rd_perumahan_bagian (laporan_id, sumber_dana)` | `(laporan_id, program)` |
| `rd_perumahan_baris` FK ke bagian lewat `sumber_dana` | lewat `program` |
| `unit`, `anggaran` | `rencana_unit`, `rencana_anggaran`, `realisasi_unit`, `realisasi_anggaran` |
| Matriks tetap 12×6 di layar isian | daftar sumber dana yang **ditambah** per program, tiap baris bisa di-Edit |
| Satu tabel Realisasi di layar Capaian | dua tabel: RENCANA dan REALISASI |

### Berisiko hilang tanpa disadari — periksa ketiganya sesudah wizard jadi

1. **BNBA.** Ada di Google Form dan sudah terbangun, tetapi **tidak muncul di
   satu pun dari 9 frame rancangan**. Kalau wizard dibangun hanya dari frame,
   unggahan BNBA hilang dan dinas kehilangan daftar penerima by-name-by-address.
2. **Pewarisan antar periode.** Masuk akal ketika angkanya kumulatif. Begitu
   penyimpanan jadi **per triwulan**, mewarisi angka triwulan lalu justru
   **salah** — itu akan menggandakan capaian. `warisi()` harus dicabut untuk
   perumahan, bukan sekadar dibiarkan.
3. **Arti "kumulatif".** Keputusan 30 Jul: **simpan per triwulan, tampilkan
   kumulatif**. Dari per-triwulan kumulatif selalu bisa dihitung; dari kumulatif
   per-triwulan tidak selalu bisa dipulihkan — butuh triwulan sebelumnya ada dan
   benar. Layar isian WAJIB menyebut tegas *"isi capaian triwulan ini saja"*,
   karena Google Form melatih petugas mengisi kumulatif selama bertahun-tahun.

---

## 5. Permukaan HTTP hari ini

| Rute | Guna |
|---|---|
| `Rekam_Data` | publik bila tanpa sesi; layar sambutan bila Admin Kab/Kota |
| `Rekam_Perumahan` | Capaian — tabel baca-saja, **tidak membuat draft** |
| `Rekam_Perumahan/input` | form isian — **di sinilah draft lahir** |
| `Rekam_Perumahan/simpan_gerbang` | POST jawaban Ada/Tidak Ada |
| `Rekam_Perumahan/simpan_angka` | POST enam program sekaligus untuk satu sumber |
| `Rekam_Perumahan/rekap` | rekap resmi, hanya `terkirim` |
| `Rekam_Perumahan/riwayat` | daftar periode + status |
| `Rekam_Perumahan/unggah_bnba` · `/unduh_bnba/{id}` | BNBA |
| `Rekam_Perumahan/kirim` | POST kirim |
| `Rekam_Tinjauan` · `/detail/{id}` · `/terima` · `/minta_perbaikan` | peninjau bidang |

**URL peka huruf besar-kecil di Linux.** `/Rekam_Perumahan` → 307,
`/rekam_perumahan` → 404. CI3 hanya `ucfirst()` segmen URI, jadi controller
bernama majemuk hanya cocok pada kapitalisasi persis. Di XAMPP Windows keduanya
jalan, sehingga beda ini tidak pernah terlihat lokal.

---

## 6. Bukti yang sudah ada

Harness lama tetap berguna sebagai acuan bentuk uji, meskipun isinya akan
berubah: [`uji_rekam_data_d2.php`](uji_rekam_data_d2.php) … `d6`, plus
[`uji_rekam_data_fresh.php`](uji_rekam_data_fresh.php) yang menjalankan seluruh
rangkaian di DB kosong. Pemeriksaan tingkat model ada sebagai metode CLI
`Migrate::uji_rekam_data_d1()`.

Definisi selesai yang dipakai modul ini sejak awal, dan wajib dipakai lagi:
**balikkan satu perbaikan, skrip harus MERAH di titik yang diramalkan, lalu
hijau lagi setelah dipulihkan.** Skrip yang tidak pernah gagal tidak
membuktikan apa pun.
