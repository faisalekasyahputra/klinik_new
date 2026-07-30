# Status Sistem Klinik PKP — 30 Juli 2026

**Semua angka dan status di dokumen ini diukur langsung hari ini** — rute diuji
ke production, suite dijalankan, tabel dihitung. Tidak ada yang disalin dari
catatan lama. Alasannya: dokumen proyek ini sudah tiga kali terbukti menyimpan
angka yang benar saat ditulis lalu salah tanpa ada yang menyadarinya.

---

## 1. Jawaban singkat: siap pakai untuk apa?

**Ya untuk portal publik dan alur pengembang SRP2.** Keduanya sudah dipakai
data nyata dan tayang stabil.

**Ya secara teknis, belum secara operasional, untuk Rekam Data.** Modulnya
lengkap dan tayang, tapi **nol laporan di production** — belum ada satu kab/kota
pun yang mengisi. Sistemnya menunggu orang, bukan menunggu kode.

**Belum untuk statistika berbasis data nyata.** Angkanya masih simulasi — dan
layarnya menyatakan itu terang-terangan, jadi tidak ada yang tersesat.

**Satu hal yang belum pernah dilakukan siapa pun: menempuh seluruh alur dengan
mata dan klik.** Bukti yang ada kuat tapi seluruhnya otomatis. Lihat §6.

---

## 2. Aktif, dipakai data nyata

| Fitur | Rute | Bukti |
|---|---|---|
| Portal publik + beranda | `/` | 200, 70 KB |
| Bank Data (tab perumahan/kawasan/pengembang) | `/tab/*` | 200 |
| Daftar & profil publik pengembang | `/pengembang`, `/Pengembang/profil/{id}` | 200; **67 pengembang bersertifikat** di DB |
| Alur sertifikasi SRP2 | `/Pengembang/sertifikasi`, `/syarat` | 200 |
| Berita / regulasi / video | `/Berita` | 200, 324 KB (API eksternal, cache 10 menit) |
| Peta & data spasial | `/Sikumbang`, `/Sikunang`, `/Siperum`, `/listkabupaten` | 200 |
| Kemitraan publik | `/KemitraanPortal` | 200 |
| Login & registrasi | `/Auth/login`, `/Auth/register` | 200 |
| Pendataan warga | `/warga/pendataan` | 307 → login (benar) |
| Dashboard admin (5 peran) | `/Admin_*`, `/akun` | semua 307 → login (benar, nol 500) |

**Peran yang dikenali:** `admin`, `admin_bidang`, `admin_kabkota`, `pengembang`,
`warga`, `mahasiswa`.

---

## 3. Aktif, tapi belum ada isinya

**Rekam Data (Perumahan + Kawasan + Peninjauan provinsi).** Tayang sejak
30 Jul 2026, skema `20260701000024`, terverifikasi dari `information_schema`.
Wizard lima langkah, gerbang per program, angka rencana + realisasi per
triwulan, unggah BNBA, peninjauan Admin Bidang, rekap & riwayat.

> **Production punya NOL laporan.** Diverifikasi dari backup pra-rilis: 12
> `INSERT`, semuanya di tabel lain, nol di `rd_*`. Modulnya siap; belum ada
> yang memakai. Konsekuensi praktisnya: rekap dan statistik apa pun yang
> bersumber dari sini akan menampilkan nol — dan nol itu **jujur**, bukan
> kerusakan.

---

## 4. Simulasi — jujur, tapi bukan data

**`/statistika`** menampilkan angka yang dihitung dari `crc32(nama_kabupaten)`,
bukan dari sistem mana pun. Layarnya menyatakannya sendiri: *"Belum ada satu pun
yang ditarik dari sistem sumbernya"*, dan tiap kartu berlabel **"Simulasi ·
rencana sumber: …"**. Satu-satunya angka nyata di sana adalah jumlah publikasi
dari API eksternal.

Sumber nyata yang sudah kita miliki untuk menggantikannya, berurutan:

1. **Kartu pengembang** ← `srp2_certified_developers` (67 baris nyata) — bisa
   sekarang, nol pipa baru.
2. **Daftar 35 kab/kota** ← tabel `kabupaten` (35 baris) — sekarang diketik
   manual di controller.
3. **Kartu perumahan & kawasan** ← `rd_*` difilter `status = 'terkirim'` lewat
   `kumulatif()`. Sumber terbaik yang kita punya, karena satu-satunya yang
   batas kepercayaannya kita kendalikan sendiri. Tunggu ada isinya (§3).
4. **Simperum/Sikumbang/Sikunang/Bank Tanah** paling akhir, dan lewat snapshot
   tersimpan — **bukan** panggilan langsung di dalam request.

---

## 5. Mati atau tertahan keputusan

| Hal | Keadaan | Menunggu |
|---|---|---|
| Chat | `/Chat` 404, kode yatim | keputusan #7 |
| Sikaper | rute tidak ada, library yatim | keputusan #5 — **rotasi kredensial dulu, TLS dinyalakan, baru dibuka** |
| `Kemitraan.php` (lama) | 200 tapi nol tautan masuk | keputusan produk: cabut atau biarkan |
| Kontrak cek tiket/NIK | — | keputusan #8 |
| Kebijakan retensi data | — | keputusan #9 (teks "Zona Berbahaya" sudah diluruskan mengikutinya) |
| Syarat bukti resmi | — | keputusan #11 |

**Dicabut 30 Jul 2026:** `Bank_desain` (500 permanen, view tidak pernah ada),
`Kabupaten` (200 nol byte), `Kabupaten|Sikumbang/tambah_intervensi` (formulir
tanpa `action` — menerima ketikan lalu membuangnya).

---

## 6. Bukti otomatis yang ada

Dijalankan 30 Jul 2026 di lokal (Apache + MariaDB nyata, bukan mock):

| Suite | Jml | Domain |
|---|---|---|
| `uji_rekam_data_d1` (via `migrate`) | 18 | pintu tulis model Kawasan |
| `uji_rekam_data_d2` | 28 | CSRF, scope tulis, rupiah penuh |
| `uji_rekam_data_d3` | 46 | kirim + **21 keamanan berkas BNBA** |
| `uji_rekam_data_d4` | 40 | Kawasan lewat HTTP |
| `uji_rekam_data_d5` | 38 | rekap & riwayat, anti-dobel-hitung |
| `uji_rekam_data_d6` | 39 | peninjauan provinsi, isolasi bidang |
| `uji_wizard_rekam_perumahan` | 38 | wizard lima langkah |
| `uji_wizard_w2` (via `migrate`) | 34 | pintu tulis model Perumahan |
| `uji_migrasi_konsisten` | 6 | konsistensi berkas migrasi |
| `uji_perjalanan_warga` | 19 | alur warga ↔ admin kab/kota |
| `uji_pendataan_warga_r3` | 16 | pendataan warga |
| `uji_pendataan_warga_r4` | 44 | pendataan warga |
| `uji_pendataan_warga_r5` | 59 | pendataan warga |
| `uji_pendataan_warga_r6` | 58 | pendataan warga, scope admin |
| `uji_keamanan_warga_r7` | 5 | keamanan alur warga |
| `uji_notifikasi` | 10 | pusat notifikasi |
| `uji_simperum_gateway` | 5 | cache + 20 lookup serentak |
| **Total** | **503** | **nol gagal** |

Suite Rekam Data **sudah dibuktikan bisa MERAH** lewat mutasi — pewarisan
dihidupkan lagi, scope wilayah dilepas, kalimat lama dikembalikan, whitelist
indikator dilepas. Tiap kali hanya uji yang seharusnya merah yang merah, dan
`git diff` kembali nol sesudahnya.

**Dua suite TIDAK bisa dijalankan hari ini, dan itu bukan kegagalan kode:**
- `uji_perjalanan_srp2` — butuh akun admin di-seed dulu (caranya di header
  berkasnya). **Artinya alur SRP2 tidak terverifikasi hari ini.**
- `uji_utang_teknis` — menolak berjalan di DB dev karena ia menulis dan
  menghapus data; butuh runner DB bersih.

---

## 7. Yang BELUM dibuktikan, dan ini yang terpenting

**Tidak ada satu pun alur yang pernah ditempuh dengan mata dan klik oleh
manusia setelah rilis hari ini.** Semua bukti di §6 adalah HTTP dan SQL. Itu
membuktikan logikanya benar; ia **tidak** membuktikan halamannya terbaca,
tombolnya terlihat, atau angkanya masuk akal bagi yang membacanya.

Dua bug nyata hari ini lolos dari seluruh pengukuran otomatis dan baru ketahuan
saat jalurnya ditempuh utuh. Rencana ujinya ada di §8.

---

## 8. Rencana uji manual — sekali jalan, ±20 menit

Login sebagai **admin kab/kota**. Tempuh dengan KLIK, jangan ketik URL.

### A. Rekam Data Perumahan (inti rilis hari ini)
1. Beranda → tombol **REKAM DATA** → layar sambutan menyebut nama wilayah & tahun
2. **Input Capaian** → pilih tahun + triwulan → **Lanjut**
3. Centang 2 program → **Lanjut** → isian muncul untuk program pertama
4. Tambah sumber dana, isi **rencana** dan **realisasi** → **Simpan**
5. Coba isi anggaran **tanpa unit** → harus DITOLAK dengan pesan yang terbaca
6. **Lanjut** ke BNBA → unggah PDF → nama berkas muncul
7. **Lanjut** ke Review → **Kirim Laporan**
8. Buka lagi wizardnya → harus **terkunci**, dan spanduknya muncul di **setiap** langkah

**Yang diperiksa:** tiap tombol berpindah **tanpa halaman berkedip putih**
(POST progresif), dan posisi gulir tidak melompat.

### B. Capaian & Riwayat
9. **Capaian** → dua tabel: Rencana dan Realisasi, plus tabel kumulatif di bawah
10. **Riwayat** → tiap baris punya tombol **Lihat capaian**; klik satu →
    mendarat di triwulan **baris itu**, bukan triwulan lain

### C. Tabel admin
11. `/Admin_Kabkota` → klik ganti tab status (Semua / Menunggu / …) →
    **hanya kartu tabel yang bertukar**; judul halaman dan posisi gulir tetap
12. Ketik di kotak cari → tekan **Cari** → sama, hanya kartunya
13. Klik **Detail** satu baris → ini **berpindah halaman** (benar)

### D. Profil
14. `/akun/profil` → isinya **memenuhi lebar layar**, tidak berhenti di tengah
15. Ubah nama → **Simpan** → pesan sukses, nilainya bertahan

### E. Peninjauan provinsi (perlu akun Admin Bidang)
16. `/Rekam_Tinjauan` → laporan terkirim muncul
17. Buka detailnya → keterangannya berbunyi **"capaian TW … saja, bukan
    kumulatif sejak Januari"** — kalau masih tertulis "kumulatif sampai dengan",
    berarti deploy belum mendarat
18. **Minta perbaikan** tanpa catatan → ditolak; dengan catatan → berhasil
19. Kembali sebagai kab/kota → catatan peninjau terlihat **sejak langkah pertama**

**Cara melaporkan:** cukup sebut nomor langkah yang janggal + apa yang kamu
lihat. Tidak perlu menebak penyebabnya.

---

## 9. Kesimpulan jujur

Sistem ini **layak dipresentasikan dan layak mulai dipakai** untuk portal
publik, SRP2, pendataan warga, dan Rekam Data. Yang belum boleh diklaim:
angka statistika sebagai data nyata, dan "sudah teruji menyeluruh" — karena
pengujian dengan mata belum pernah terjadi.

Dua hal yang menunggu tindakan dan bukan keputusan:
1. **Uji manual §8** — butuh sesi login.
2. **Rotasi password DB production** — kredensialnya pernah terkirim ke kanal
   yang tidak semestinya. Ganti di hPanel **dan** `.env` server dalam satu
   tarikan, kalau tidak situsnya mati.
