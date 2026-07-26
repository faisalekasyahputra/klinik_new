# Audit Peran `pengembang` — Sistem, Keamanan, Konsistensi

**Tanggal audit:** 26 Juli 2026
**Metode:** pembacaan langsung kode (bukan asumsi dari dokumen lama) atas seluruh permukaan yang menyentuh role `pengembang`: `Pengembang.php`, `Pengaturan.php`, `Auth.php` (register/login/onboarding), `Admin_Srp2.php`, migrasi `srp2_*`, `routes.php`, `roles.php`, dan view terkait (`syarat.php`, `dokumen.php`).
**Tujuan:** jadi baseline tertulis sebelum role-role baru yang lebih kompleks dibangun, supaya pola yang benar (anti-IDOR, status lock, JSON branch) dan celah yang belum tertutup tidak perlu ditemukan ulang dari nol.

Verifikasi keamanan anti-IDOR untuk endpoint wizard (`simpan_dokumen`, `kirim_pengajuan`, `dokumen`, `result`) sudah dilakukan lewat backtest langsung (akun A vs akun B, request tanpa sesi) pada sesi sebelumnya — hasilnya semua percobaan lintas akun gagal sesuai desain. Audit ini melengkapi dengan cakupan yang lebih luas: bukan cuma endpoint wizard, tapi seluruh jalur yang bisa membuat akun berperan `pengembang`.

---

## Ringkasan Temuan

| # | Temuan | Kategori | Tingkat |
|---|---|---|---|
| 1 | Tidak ada alur admin untuk mengubah status `Pending` → `Diterima`/`Ditolak` | Fungsional (gap besar) | 🔴 Tinggi |
| 2 | Dua jalur berbeda membuat akun ber-role `pengembang`, cuma satu yang bikin `srp2_registrations` | Konsistensi + fungsional | 🟠 Sedang-Tinggi |
| 3 | Upload KTP/SIUP di jalur onboarding lama disimpan di direktori publik, bukan `private_uploads/` | Keamanan | 🟠 Sedang |
| 4 | `srp2_certified_developers` (direktori publik "Pengembang Tersertifikasi") terputus dari `srp2_registrations` (pipeline pendaftaran baru) | Konsistensi data | 🟡 Rendah-Sedang |
| 5 | `srp2_documents.registration_id` tanpa FK constraint ke `srp2_registrations.id` | Data integrity | 🟡 Rendah |
| 6 | Validasi keamanan sudah konsisten & baik di jalur wizard (anti-IDOR, status lock, CSRF, JSON branch) | — (positif, dicatat sebagai pola rujukan) | ✅ |

Detail tiap temuan di bawah.

---

## 🔴 1. Tidak ada alur admin untuk memverifikasi pengajuan SRP2

**Yang ditemukan:** `Admin_Srp2.php` cuma mengelola tabel `srp2_certified_developers` (nama perusahaan + link sosmed + toggle aktif/nonaktif) — tabel direktori publik yang datanya di-seed manual lewat migrasi (`20260701000003_srp2_certified_developers_and_documents.php`, 60+ nama PT hardcode).

Tabel `srp2_registrations` (dan `srp2_documents`) yang diisi lewat wizard SRP2 (`Pengembang::syarat()` → unggah → `kirim_pengajuan()`) **tidak punya endpoint admin apa pun** yang bisa mengubah `status_verifikasi` dari `Pending` ke `Diterima`/`Ditolak`, atau mengisi `catatan_admin`. Sudah di-grep di seluruh `application/controllers/` — cuma tiga tempat yang menyentuh `status_verifikasi`: `Pengembang.php` (set `Draft`→`Pending` oleh pengembang sendiri), `Pengaturan.php` (baca saja), dan `Auth.php` (set `Draft` saat draft dibuat).

**Dampak:** pengguna yang menyelesaikan wizard sampai "Kirim Pengajuan" akan macet permanen di status `Pending` — dashboard `/akun` menampilkan "Dalam Peninjauan" dan wizard menampilkan "Sedang Ditinjau Admin" (read-only), tapi tidak ada cara sistem mengubahnya kecuali `UPDATE` manual ke database. Ini bukan bug kode yang salah, tapi **fitur yang belum dibangun** — sisi admin dari alur SRP2 belum ada sama sekali.

**Rekomendasi:** sebelum role-role baru ditambahkan, prioritaskan `Admin_Srp2::index()` (atau controller baru, mis. `Admin_Srp2_Verifikasi.php`) untuk:
- daftar pengajuan `srp2_registrations` dengan `status_verifikasi = 'Pending'`, link ke detail + dokumen yang diunggah (baca dari `private_uploads/srp2/{id}/` via endpoint terautentikasi admin, jangan expose path langsung).
- aksi terima (set `Diterima`, opsional insert ke `srp2_certified_developers` biar konsisten dengan temuan #4) dan tolak (set `Ditolak` + wajib isi `catatan_admin`).
- WHERE clause harus tetap pakai `id` dari request tapi **role admin di-cek di base controller** (`Admin_Controller` sudah begitu) — bukan celah anti-IDOR karena admin memang berwenang lintas user, beda dengan role scoped (`admin_kabkota`/`admin_bidang`) yang butuh WHERE ganda.

---

## 🟠 2. Dua jalur pembuatan akun `pengembang`, tidak konsisten

**Jalur A — wizard SRP2 ("daftar cepat"):** `Pengembang::syarat()` step 2 → `Auth::do_register()` dengan `srp2_pengembang=1`. Ini langsung: set `role='pengembang'`, isi `nama_perusahaan`, **dan bikin baris `srp2_registrations` berstatus `Draft`** (`Auth.php:280-284`).

**Jalur B — onboarding umum:** `Auth::onboarding()` (dipakai user Google OAuth atau siapa pun yang profilnya belum lengkap) menampilkan kartu pilihan role termasuk "Pengembang" (`application/views/pages/auth/onboarding.php:97-101`), lalu `Auth::save_onboarding()` (`Auth.php:369-488`) set `role='pengembang'` + `nama_perusahaan`/`alamat_kantor`/`telp_kantor` ke `usr_users`, plus upload `file_ktp`/`file_siup` (lihat temuan #3) — **tapi TIDAK pernah insert ke `srp2_registrations`**.

**Dampak konkret:** user yang jadi `pengembang` lewat Jalur B, begitu buka `/akun` (`Pengaturan::index()`), tidak akan melihat item SRP2 apa pun di daftar (karena `$sp2 = ... get('srp2_registrations')->row()` mengembalikan NULL, `if ($sp2)` gagal — lihat `Pengaturan.php:59-77`). Baru kalau dia (atau link lain) membawanya ke `Pengembang/syarat`, controller itu yang punya logika auto-create draft (`Pengembang.php:38-48`) — jadi dashboard dan wizard "tidak sinkron" sampai user kebetulan mengunjungi wizard.

**Rekomendasi:** pilih salah satu, jangan biarkan dua sumber kebenaran untuk "kapan draft SRP2 dibuat":
- **Opsi termudah (disarankan):** pindahkan logika auto-create draft dari `Pengembang::syarat()` ke helper yang juga dipanggil `Pengaturan::index()` saat `role === 'pengembang'` dan belum ada baris — atau lebih sederhana lagi, panggil auto-create itu juga di `Auth::save_onboarding()` persis seperti di `do_register()`/`do_login()` AJAX branch, supaya SEMUA jalur menuju role `pengembang` konsisten langsung punya draft. Pola kodenya sudah ada 3x (duplikat) di `Auth.php` (do_login AJAX, do_register) dan `Pengembang.php` (syarat) — layak dijadikan satu method di `Auth_model` (mis. `ensure_srp2_draft($user_id)`), bukan disalin lagi untuk jalur ke-4.

---

## 🟠 3. Upload KTP/SIUP di jalur onboarding tersimpan di direktori publik

**Yang ditemukan:** `Auth::_handle_uploads()` (`Auth.php:772-813`) untuk role `pengembang` mengunggah `file_ktp`/`file_siup` ke:
```php
$upload_path = FCPATH . 'uploads/documents/' . $user_id . '/';
```
`FCPATH` adalah direktori webroot (tempat `index.php` ada) — path ini secara default **bisa diakses langsung lewat HTTP** kalau nama file tertebak, karena tidak ada `.htaccess` di `uploads/` (dicek: folder ini bahkan belum ada di repo, dibuat runtime saat upload pertama, tanpa proteksi apa pun). Nama file di-randomize (`encrypt_name => TRUE` di CI3 upload lib), jadi ini "security by obscurity" — aman selama nama file acak tidak bocor (log, screenshot, error message, dsb), tapi bukan kontrol akses sungguhan.

Bandingkan dengan pola SRP2 yang benar di `Pengembang::simpan_dokumen()`:
```php
$path = dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . ...
```
— satu level **di atas** webroot, tidak bisa diakses HTTP sama sekali, ini pola yang didokumentasikan sebagai kontrak keamanan di `AGENTS.md` §9 dan `SRP2_ACCOUNT_FLOW.md` ("Jangan menyajikan file SRP2 dari direktori publik").

**Dampak:** KTP adalah dokumen kependudukan (NIK + data pribadi) yang termasuk cakupan UU PDP — level sensitivitasnya sama dengan data yang sudah dienkripsi AES-256-GCM di tempat lain (`Encryption_lib`, dipakai untuk NIK di `usr_users.nik`). Menyimpan scan KTP di direktori publik adalah inkonsistensi kebijakan keamanan: NIK sebagai teks dienkripsi kuat, tapi scan fisik KTP (yang juga memuat NIK + foto + tanda tangan) hanya diberi nama file acak di folder publik.

**Catatan cakupan:** ini kode **lama** (bagian dari onboarding generik, dipakai role `pengembang`/`vendor`/`mahasiswa`), bukan kode baru sesi SRP2. Tapi karena diaudit dalam konteks role `pengembang`, tetap dilaporkan — terutama karena Temuan #2 berarti jalur ini masih aktif dipakai untuk menjadi `pengembang`.

**Rekomendasi:** pindahkan `$upload_path` ke pola yang sama dengan `private_uploads/` (di luar `FCPATH`), dan sajikan lewat endpoint terautentikasi (baca `usr_documents` → cek `user_id` sesi → `readfile()`) kalau file itu perlu ditampilkan lagi. Berlaku untuk ketiga role yang lewat `_handle_uploads()` (`pengembang`, `vendor`, `mahasiswa`), bukan cuma `pengembang` — kalau mau scope minimal sesi berikutnya, boleh cuma pengembang dulu tapi catat vendor/mahasiswa masih py punya gap yang sama.

---

## 🟡 4. `srp2_certified_developers` terputus dari `srp2_registrations`

**Yang ditemukan:** ada dua tabel yang secara konsep sama-sama merepresentasikan "pengembang yang terverifikasi", tapi terhubung cuma lewat *string match* nama perusahaan, bukan FK:

- `Pengembang::sertifikasi()` (landing publik SRP2) baca dari `srp2_certified_developers` (kalau tabel ada) sebagai daftar utama.
- `Pengembang::profil($id)` (halaman detail publik) baca `srp2_certified_developers` dulu, lalu **coba lengkapi** field kosong (`asosiasi`, `no_keanggotaan`, dst) dengan `SELECT ... FROM srp2_registrations WHERE nama_perusahaan = ? AND status_verifikasi = 'Diterima'` (`Pengembang.php:100-104`) — pencocokan berbasis nama string, rawan gagal kalau ada perbedaan penulisan (spasi, "PT." vs "PT", dst).
- Kalau `srp2_certified_developers` tidak ada, fallback ke `srp2_registrations` langsung (`Pengembang.php:105`) — jalur ini konsisten sendiri, tapi berarti ada 2 mode operasi berbeda tergantung tabel mana yang exists, bukan satu sumber kebenaran.

**Dampak:** kalau Temuan #1 (alur admin approve) dibangun dan admin menyetujui pengajuan baru di `srp2_registrations`, developer itu **tidak otomatis muncul** di direktori publik `srp2_certified_developers` kecuali ditambahkan manual lagi lewat `Admin_Srp2::save()`. Ini pekerjaan ganda dan rawan lupa/tidak sinkron (nama beda dikit = data tidak nyambung di `profil($id)`).

**Rekomendasi:** saat membangun fitur approve (#1), sekalian putuskan salah satu:
- (a) hapus `srp2_certified_developers` sebagai tabel terpisah, jadikan `srp2_registrations WHERE status_verifikasi='Diterima'` satu-satunya sumber direktori publik (butuh migrasi data 60+ nama lama masuk ke `srp2_registrations` dengan `user_id NULL` atau akun dummy), **atau**
- (b) saat admin approve, insert/update otomatis ke `srp2_certified_developers` dari data `srp2_registrations` yang baru saja diterima (by `user_id`, bukan nama), supaya link antar tabel jadi ID-based bukan string-based.

---

## 🟡 5. `srp2_documents.registration_id` tanpa FK constraint

**Yang ditemukan:** migrasi `20260701000003_srp2_certified_developers_and_documents.php` mendefinisikan `srp2_documents.registration_id` sebagai `INT UNSIGNED NOT NULL` tanpa `FOREIGN KEY REFERENCES srp2_registrations(id)`. Konsistensi dijaga sepenuhnya di level aplikasi (semua query sudah benar scoped by `user_id`+`id` — lihat verifikasi anti-IDOR sebelumnya), jadi ini bukan celah keamanan, tapi:

**Dampak:** kalau suatu saat ada fitur "hapus akun pengembang" atau "hapus pengajuan" yang menghapus baris `srp2_registrations` tanpa ikut menghapus `srp2_documents` terkait, akan tersisa baris yatim (orphan rows) + file fisik yatim di `private_uploads/srp2/{id}/` selamanya. Cek: `Pengaturan::delete_account()` → `User_model::delete_user_account()` — perlu diverifikasi apakah method itu ikut membersihkan `srp2_registrations`/`srp2_documents`/`private_uploads` milik user yang dihapus (di luar cakupan file yang sudah dibaca sesi ini, layak dicek terpisah).

**Rekomendasi:** tambah FK `ON DELETE CASCADE` di migrasi baru (migrasi lama tidak diubah, tambah migrasi baru sesuai `AGENTS.md` §8), atau minimal audit `delete_user_account()` untuk pembersihan manual yang eksplisit + hapus file fisik.

---

## ✅ Pola yang sudah benar (dijadikan rujukan untuk role baru)

Bagian wizard SRP2 (dibangun sesi-sesi sebelumnya) sudah solid dan layak jadi **template** untuk role kompleks berikutnya:

1. **Anti-IDOR ganda:** semua query registrasi/dokumen pakai `WHERE id = ? AND user_id = <session>` bersamaan — diverifikasi lewat backtest langsung (login akun B, coba akses/tulis data akun A → 100% gagal dengan respons yang jujur, bukan silent fail).
2. **Status lock di server, bukan cuma UI:** `simpan_dokumen()`/`kirim_pengajuan()` menolak (409) perubahan saat status `Pending`/`Diterima`, bukan cuma menyembunyikan tombol di Alpine (`readOnly` getter di `syarat.php` cuma UX, backend yang jadi penegak sebenarnya).
3. **Branch JSON eksplisit tanpa redirect diam-diam:** `akses_pengembang()` balas 401/403 JSON untuk request AJAX — kalau ini redirect biasa, `fetch()` akan menelan HTML halaman lain sebagai "sukses". Pola ini wajib diulang untuk wizard role lain yang dibangun mirip pola ini.
4. **File di luar webroot:** `private_uploads/` (bandingkan dengan Temuan #3 yang justru melanggar pola ini di kode lama).
5. **Upload validation berlapis:** whitelist ekstensi + cek MIME asli via `finfo` (bukan cuma percaya `Content-Type` dari browser) + nama file acak + cap ukuran.

---

## Rekomendasi Urutan Pengerjaan (kalau diminta lanjut)

1. **#1 (admin approve)** — paling mendesak, tanpa ini seluruh pipeline SRP2 dead-end secara fungsional.
2. **#2 (satukan pembuatan draft)** — kecil tapi mencegah bug dashboard kosong yang membingungkan user.
3. **#3 (pindah upload onboarding ke private)** — keamanan, tidak mendesak (fitur lama, jarang dipakai untuk pengembang sejak wizard ada) tapi seharusnya tidak dibiarkan lama.
4. **#4, #5** — cleanup data integrity, bisa nunggu momentum yang sama dengan #1.

Tidak ada satu pun dari temuan ini yang dieksekusi (perbaikan kode) dalam audit ini — dokumen ini murni observasi berbasis pembacaan kode, menunggu arahan lanjutan.
