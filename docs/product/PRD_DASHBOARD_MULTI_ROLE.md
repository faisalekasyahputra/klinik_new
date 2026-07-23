# PRD Dashboard Multi-Role Klinik PKP

**Status:** Draft berbasis implementasi saat ini
**Tanggal:** 22 Juli 2026
**Pemilik:** Tim Klinik PKP / Disperakim Jawa Tengah

## 1. Ringkasan

Klinik PKP memiliki satu akun pengguna dengan konteks akses berbeda. Setiap pengguna harus dapat masuk ke dashboard sesuai role dan melihat tugas, data, serta fitur yang relevan tanpa membuka data milik role lain.

Role produk yang ditetapkan dalam PRD ini:

1. `warga` — pengguna layanan perumahan dan pengajuan program.
2. `pengembang` — perusahaan/pelaku pengembang dan pendaftar SRP2.
3. `mahasiswa` — pengguna magang atau penelitian.
4. `admin` — operator internal yang memvalidasi dan mengelola sistem.

`vendor` masih ada di onboarding dan backend sebagai role lama. Statusnya harus diputuskan sebelum implementasi perubahan role; PRD ini tidak menganggap vendor sebagai role produk final.

## 2. Temuan Implementasi Saat Ini

| Area | Kondisi saat ini | Status |
|---|---|---|
| Login dan session | `Auth` menyimpan `user_id`, `role`, identitas, dan `is_logged` ke session | Ada |
| Onboarding | Pilihan UI/backend: warga, pengembang, vendor, mahasiswa | Parsial; vendor perlu keputusan |
| Dashboard admin | `Admin_Controller` hanya menerima role `admin`; tersedia dashboard, antrean, konten, user, SRP2, settings, profil | Ada |
| Dashboard warga | Belum bernama dashboard; fitur akun dan riwayat tersedia di `/akun` | Parsial |
| Dashboard pengembang | Masih menempel pada `/akun`; status SRP2 dan edit profil tersedia | Parsial |
| Dashboard mahasiswa | Belum ada route/controller/view khusus | Belum ada |
| Akses SRP2 | Formulir login-gated, tetapi belum dibatasi role `pengembang` | Gap akses |
| Publikasi pengembang | Login-gated, tetapi belum dibatasi role `pengembang` | Gap akses |
| Hak akses dinamis | Tabel `sys_menu`/`sys_multi` ada, tetapi guard utama masih hardcoded | Parsial |

## 3. Tujuan dan Non-Tujuan

### Tujuan

- Menyediakan satu pintu dashboard setelah login.
- Menampilkan menu berdasarkan role dan status data pengguna.
- Menjaga backend tetap menjadi sumber kebenaran hak akses.
- Memisahkan data pribadi, data pengajuan, dokumen, dan data administrasi.
- Membuat status fitur mudah diverifikasi: aktif, parsial, atau belum tersedia.

### Non-tujuan fase awal

- Membuat role-permission engine generik sebelum kebutuhan nyata muncul.
- Mengubah seluruh modul publik menjadi dashboard.
- Menambah tabel baru untuk data yang sudah tersedia di `usr_users`, `usr_documents`, `sf_housing_queue`, dan SRP2.
- Membuat generator sertifikat digital; tombol saat ini memang belum tersedia.

## 4. Prinsip Akses

1. Role disimpan sebagai nilai kanonis lowercase: `warga`, `pengembang`, `mahasiswa`, `admin`.
2. `admin` tidak boleh dipilih dari onboarding publik; pembuatan/perubahan admin dilakukan melalui proses internal yang diaudit.
3. Identitas pemilik data selalu berasal dari session, bukan `user_id` dari POST/GET.
4. Menyembunyikan menu bukan pengganti guard controller.
5. PII (NIK, alamat, dokumen) hanya tampil kepada pemilik yang berwenang dan admin sesuai tugas.
6. Pengajuan guest tetap dapat memiliki `user_id = NULL`; dashboard hanya menampilkan pengajuan dengan `user_id` session.

## 5. Peta Dashboard Produk

| Role | Dashboard utama | Fitur wajib | Data yang boleh dikelola |
|---|---|---|---|
| Warga | Ringkasan akun, status pengajuan, aksi layanan | Profil, Smart Filter, riwayat tiket, cek status, forum, informasi rumah | Profil sendiri dan pengajuan sendiri |
| Pengembang | Status SRP2, kelengkapan dokumen, profil publikasi | Profil perusahaan, formulir SRP2, unggah dokumen, status/verifikasi, profil publik, publikasi | Data SRP2 milik sendiri; publikasi milik/yang diizinkan |
| Mahasiswa | Status profil dan dokumen | Profil, KTM, surat magang/pengantar, informasi program magang/penelitian, pengumuman | Profil dan dokumen sendiri |
| Admin | KPI operasional dan antrean kerja | Validasi antrean, kelola pengguna, konten, SRP2, pengaturan sistem, profil admin | Data operasional sesuai kewenangan; bukan perubahan PII tanpa kebutuhan |

## 6. Kebutuhan Fungsional

### FR-1 — Dispatcher dashboard

- Setelah onboarding selesai, login mengarahkan user ke dashboard role-nya.
- Role `admin` menuju layout admin.
- Role publik menuju layout portal dengan menu yang sesuai.
- URL dashboard tidak boleh menjadi satu-satunya pengaman; setiap endpoint memeriksa session dan role.

### FR-2 — Dashboard warga

- Menampilkan profil ringkas dan status kelengkapan akun.
- Menampilkan riwayat `sf_housing_queue` milik user: tiket, program, status, waktu perubahan.
- Menyediakan tautan ke Smart Filter dan cek status pengajuan.
- Menampilkan akses forum; membuat topik, komentar, like, dan laporan tetap mengikuti guard forum.
- Tidak menampilkan NIK penuh, alamat terenkripsi, atau data pengajuan warga lain.

### FR-3 — Dashboard pengembang

- Menampilkan satu atau lebih pengajuan `srp2_registrations` milik user.
- Menampilkan status `Pending`, `Diterima`, atau `Ditolak` beserta catatan admin jika ada.
- Menampilkan kelengkapan `srp2_documents` dan tautan unggah untuk pemilik pengajuan.
- Mengizinkan perubahan profil perusahaan melalui `akun/update_pengembang` hanya untuk role `pengembang` dan `user_id` session.
- Menampilkan profil publik hanya bila status verifikasi `Diterima`.
- Mengunci aksi SRP2 dan publikasi dari warga/mahasiswa.

### FR-4 — Dashboard mahasiswa

- Menampilkan profil dasar dan status dokumen `ktm` serta `surat_magang`.
- Menyediakan informasi program magang/penelitian yang dikelola admin.
- Menampilkan status permohonan/komunikasi mahasiswa jika modul tersebut sudah tersedia.
- Tidak boleh mendapatkan akses ke data antrean warga, data SRP2, atau menu admin.

### FR-5 — Dashboard admin

- Menampilkan total user, antrean pending, dan diskusi sebagai KPI awal.
- Menampilkan dan memproses antrean `sf_housing_queue` menjadi `approved` atau `rejected` dengan catatan.
- Mengelola konten melalui `sys_settings`.
- Mengelola daftar pengembang tersertifikasi melalui `srp2_certified_developers`.
- Menyediakan manajemen user yang nyata; halaman saat ini masih placeholder.
- Mencatat aktor dan waktu perubahan status pada fase audit berikutnya.

### FR-6 — Profil dan dokumen bersama

- Semua role dapat mengubah nama, username, dan telepon sesuai aturan validasi.
- Email login tidak dapat diubah melalui profil biasa.
- Dokumen dihubungkan ke `usr_users.id` dan tidak boleh dapat ditebak melalui URL publik.
- Penghapusan akun mempertahankan anonimisasi forum sesuai perilaku saat ini.

## 7. Matriks Hak Akses

| Modul | Guest | Warga | Pengembang | Mahasiswa | Admin |
|---|---:|---:|---:|---:|---:|
| Portal publik dan pencarian rumah | Baca | Baca | Baca | Baca | Baca |
| Smart Filter / cek tiket | Ya | Ya | Ya | Ya | Ya |
| Riwayat pengajuan akun | Tidak | Milik sendiri | Milik sendiri bila ada | Milik sendiri bila ada | Semua sesuai tugas |
| Forum baca | Ya | Ya | Ya | Ya | Ya |
| Forum tulis/like | Tidak | Ya | Ya | Ya | Ya |
| Moderasi forum | Tidak | Tidak | Tidak | Tidak | Ya |
| Formulir SRP2 | Tidak | Tidak | Ya | Tidak | Kelola/verifikasi |
| Dokumen SRP2 | Tidak | Tidak | Milik sendiri | Tidak | Kelola/verifikasi |
| Publikasi pengembang | Tidak | Tidak | Ya | Tidak | Kelola bila diperlukan |
| Dokumen mahasiswa | Tidak | Tidak | Tidak | Milik sendiri | Kelola/verifikasi |
| Kelola antrean | Tidak | Tidak | Tidak | Tidak | Ya |
| Kelola user/konten/settings | Tidak | Tidak | Tidak | Tidak | Ya |

## 8. Kebutuhan Non-Fungsional

- **Keamanan:** CSRF, anti-IDOR, session regeneration, guard server-side, PII terenkripsi.
- **Privasi:** dashboard hanya mengembalikan field yang diperlukan; dokumen privat tidak melalui URL publik.
- **Konsistensi role:** satu daftar nilai kanonis; label tampilan boleh diterjemahkan.
- **Aksesibilitas:** setiap menu dan status memiliki label teks, fokus keyboard, dan state kosong/error yang jelas.
- **Auditabilitas:** perubahan status antrean, SRP2, dan role harus memiliki aktor dan timestamp pada fase implementasi audit.
- **Kinerja:** dashboard memakai query ringkas dan pagination; tidak memuat seluruh data operasional sekaligus.

## 9. Rencana Implementasi Minimum

### Fase A — Normalisasi akses

- Tetapkan role kanonis dan putuskan nasib `vendor`.
- Buat dispatcher dashboard minimal berdasarkan session role.
- Pisahkan guard `admin`, `pengembang`, dan `mahasiswa` di backend.
- Perbaiki akses SRP2/publikasi agar tidak hanya login-gated.

### Fase B — Dashboard warga dan pengembang

- Pindahkan ringkasan `/akun` menjadi dashboard warga.
- Jadikan ringkasan SRP2 sebagai dashboard pengembang.
- Tambahkan daftar dokumen dan status yang bersumber dari tabel yang sudah ada.

### Fase C — Dashboard mahasiswa

- Pakai `usr_documents` yang sudah ada untuk KTM dan surat magang.
- Tambahkan hanya data proses yang benar-benar dibutuhkan setelah alur bisnis magang disepakati.

### Fase D — Penyelesaian admin

- Hubungkan manajemen user ke `usr_users`.
- Lengkapi validasi SRP2 dari registration ke certified developer.
- Tambahkan audit perubahan status dan pagination antrean.

## 10. Kriteria Penerimaan Utama

- User warga tidak dapat membuka URL admin, SRP2, atau dokumen mahasiswa.
- User pengembang tidak dapat mengubah SRP2 milik user lain meskipun mengganti ID URL.
- User mahasiswa melihat dashboard khusus dan hanya dokumen miliknya.
- Admin dapat memproses antrean dan hasilnya terlihat di dashboard pemilik serta cek tiket.
- Logout menghapus session dan akses dashboard berikutnya mengarah ke login.
- Semua menu yang tampil memiliki endpoint yang benar-benar memiliki guard.

## 11. Keputusan yang Masih Dibutuhkan

1. Apakah `vendor` dihapus dari produk final atau menjadi role kelima?
2. Apakah mahasiswa memerlukan workflow persetujuan magang/penelitian, atau cukup repositori dokumen?
3. Apakah satu pengembang boleh memiliki beberapa pengajuan SRP2 aktif?
4. Apakah admin akan tetap satu role atau nanti membutuhkan permission seperti `admin_srp2` dan `admin_antrean`?
5. Apakah publikasi pengembang dimoderasi admin sebelum tampil?

## 12. Acuan Implementasi

- `application/controllers/Auth.php` — login, onboarding, role, upload dokumen.
- `application/core/MY_Controller.php` — base controller dan guard admin.
- `application/controllers/Pengaturan.php` — akun, riwayat pengajuan, profil pengembang.
- `application/controllers/Pengembang.php` — SRP2, dokumen, profil publik, publikasi.
- `application/controllers/Program.php` — Smart Filter, antrean, tiket.
- `application/controllers/Admin.php` dan `Admin_Dashboard.php` — operasi admin saat ini.
- `docs/product/DESAIN_STATUS_TIKET_PENGAJUAN.md` — keputusan status tiket.
- `docs/engineering/ROLE_DATA_RELATION_MAP.md` — peta relasi data.
