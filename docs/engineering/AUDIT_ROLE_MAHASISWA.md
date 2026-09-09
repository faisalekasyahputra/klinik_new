# Audit Peran `mahasiswa` - Sistem, Keamanan, Konsistensi

**Tanggal audit:** 26 Juli 2026
**Metode:** pembacaan langsung `KemitraanPortal.php`, `Admin_Kemitraan.php`, `Pengaturan.php` (bagian mahasiswa), `Auth.php` (`do_register`, `onboarding`, `save_onboarding`, `_handle_uploads`), migrasi `20260701000010_add_kkn_magang_pendaftaran.php`, view `daftar.php` dan `admin/kemitraan/index.php`, serta `User_model::delete_user_account()`.

Bagian dari rangkaian audit multi-role, lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) untuk pola lintas-role.

---

## Ringkasan Temuan

| # | Temuan | Kategori | Tingkat |
|---|---|---|---|
| 1 | `Admin_Kemitraan` benar-benar fungsional (Diajukan→Diterima/Ditolak, terhubung ke tabel & dashboard yang sama) - kontras positif dengan gap SRP2 | Fungsional (positif) | ✅ |
| 2 | Upload dokumen mahasiswa (`file_surat_pengantar`, `file_ktm`, `file_surat_magang`) disimpan di direktori publik (`.assets/uploads/` dan `FCPATH.'uploads/documents/'`), bukan di luar webroot | Keamanan | 🟠 Sedang |
| 3 | Upload `file_surat_pengantar` (KemitraanPortal) tidak memvalidasi MIME asli via `finfo`, cuma ekstensi + `allowed_types` bawaan CI3 | Keamanan | 🟡 Rendah-Sedang |
| 4 | Admin tidak bisa melihat/mengunduh `file_surat_pengantar` yang diunggah - tidak ditampilkan di `admin/kemitraan/index.php` | Fungsional | 🟡 Rendah-Sedang |
| 5 | Jalur pembuatan role `mahasiswa` tunggal (cuma `save_onboarding()`), tidak ada duplikasi jalur seperti kasus pengembang | Konsistensi | ✅ |
| 6 | `delete_user_account()` tidak membersihkan `kkn_magang_pendaftaran` atau file fisik terkait - orphan data + file publik tetap ada setelah akun dihapus | Data integrity + keamanan | 🟠 Sedang |
| 7 | `kkn_magang_pendaftaran.user_id` tanpa FK constraint ke `usr_users.id` | Data integrity | 🟡 Rendah |
| 8 | Tidak ada pencegahan duplikat pengajuan (user bisa submit berkali-kali untuk jenis yang sama tanpa cek status pending yang sudah ada) | Fungsional (minor) | 🟡 Rendah |
| 9 | Anti-IDOR & CSRF di jalur pendaftaran mahasiswa sudah benar | - (positif) | ✅ |

---

## ✅ 1. Admin_Kemitraan benar-benar fungsional - kontras positif dengan gap SRP2

`Admin_Kemitraan::proses($id)` menerima POST, validasi `status` whitelist (`Diterima`/`Ditolak`), lalu update `kkn_magang_pendaftaran`. View admin punya form aksi Terima/Tolak dengan CSRF, tombol cuma muncul kalau status `Diajukan`. Mahasiswa langsung melihat perubahan di `/akun` (`Pengaturan.php`, `status_map` yang sama). **Beda dari kasus SRP2** (gap #1 di `AUDIT_ROLE_PENGEMBANG.md`) - di sini pipeline admin→mahasiswa tersambung penuh, tidak ada dead-end.

---

## 🟠 2. Upload dokumen mahasiswa disimpan di direktori publik

Dua jalur, sama-sama bermasalah:
- `KemitraanPortal::simpan()`: `$upload_path = '.assets/uploads/';`
- `Auth::_handle_uploads()` (role `mahasiswa`, field `file_ktm`/`file_surat_magang`): `$upload_path = FCPATH . 'uploads/documents/' . $user_id . '/';`

Keduanya terverifikasi disajikan lewat HTTP tanpa gate autentikasi (dibuktikan lewat pemakaian `base_url('.assets/uploads/' . ...)` di view lain). Nama file di-randomize (`encrypt_name => TRUE`) - "security by obscurity", bukan kontrol akses sungguhan. Ini persis pola yang sudah diprediksi di `AUDIT_ROLE_PENGEMBANG.md` Temuan #3 ("vendor/mahasiswa masih punya gap yang sama") - sekarang terkonfirmasi, dan ternyata ada jalur ketiga (`KemitraanPortal::simpan()`) yang belum diketahui saat itu. Lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) untuk pola lintas-role ini (3 lokasi berbeda, gejala sama).

**Dampak:** surat pengantar kampus dan KTM (NIM, foto, data identitas) bisa diakses siapa pun yang mendapat nama file acak.

**Rekomendasi:** pindahkan ke `private_uploads/`, sajikan lewat endpoint terautentikasi yang cek `user_id` sesi.

---

## 🟡 3. Validasi upload `file_surat_pengantar` tidak cek MIME asli

`KemitraanPortal::simpan()` memakai CI3 `upload` library (`allowed_types => 'pdf|jpg|jpeg|png'`, `max_size => 5120`) tanpa pengecekan `finfo(FILEINFO_MIME_TYPE)` eksplisit seperti pola rujukan `Pengembang::simpan_dokumen()`. CI3 `allowed_types` bawaan sudah memverifikasi lewat mekanisme internal (bukan cuma ekstensi/`Content-Type` browser), jadi risikonya lebih rendah daripada tanpa validasi sama sekali - tapi tetap ada gap kecil dibanding pola eksplisit wizard SRP2.

**Rekomendasi:** samakan pola dengan `Pengembang::simpan_dokumen()` - whitelist ekstensi + `finfo` eksplisit - sekalian saat memindahkan direktori simpan (Temuan #2).

---

## 🟡 4. Admin tidak bisa meninjau `file_surat_pengantar` yang diunggah

`admin/kemitraan/index.php` menampilkan kolom Mahasiswa/Jenis/Instansi/Divisi/Status/Aksi, tapi **tidak ada link/tombol untuk melihat `file_surat_pengantar`** meski kolom itu ada di tabel dan sudah ikut ter-select di query. Admin memutuskan Diterima/Ditolak tanpa bisa membuka dokumen pendukung sama sekali dari UI.

**Rekomendasi:** tambah link unduh terautentikasi di baris tabel, sekalian menutup Temuan #2.

---

## ✅ 5. Jalur pembuatan role `mahasiswa` - tidak ada duplikasi seperti kasus pengembang

Role `mahasiswa` cuma bisa dibuat lewat satu jalur: `Auth::save_onboarding()`. `Auth::do_register()` tidak punya cabang setara `is_srp2` untuk mahasiswa. Karena `kkn_magang_pendaftaran` bukan "draft per-akun" melainkan record per-pengajuan yang cuma dibuat saat submit form eksplisit, tidak-adanya auto-create draft saat onboarding **bukan bug** - perilaku ini konsisten dan sesuai ekspektasi (dashboard kosong sampai user submit, bukan error).

---

## 🟠 6. `delete_user_account()` tidak membersihkan data/file KKN-Magang

`User_model::delete_user_account()` cuma menangani `forum_komentar`, `forum_diskusi`, `forum_likes`, lalu `DELETE FROM usr_users`. **Tidak menyentuh `kkn_magang_pendaftaran` sama sekali** - tidak dihapus, tidak dianonimkan, tidak ada FK `ON DELETE CASCADE` yang bisa menangkapnya otomatis (lihat Temuan #7).

**Dampak:** kalau mahasiswa menghapus akun, baris `kkn_magang_pendaftaran` miliknya jadi yatim, dan file fisik (`file_surat_pengantar`/`file_ktm`/`file_surat_magang`) di direktori publik **tetap ada selamanya** - lebih parah dari gap FK biasa karena filenya publik (Temuan #2): dokumen pribadi tetap bisa diakses lewat URL lama meski pemiliknya sudah "menghapus akun". Lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) - pola cleanup tidak lengkap yang sama juga ditemukan di `warga` (`aduan.user_id`).

**Rekomendasi:** tambahkan pembersihan eksplisit di `delete_user_account()` (hapus baris + `unlink()` file fisik sebelum delete baris), atau migrasi FK `ON DELETE CASCADE` (FK tidak bisa menghapus file di disk, jadi cleanup kode tetap wajib).

---

## 🟡 7. Tidak ada FK constraint `kkn_magang_pendaftaran.user_id` → `usr_users.id`

Migrasi mendefinisikan `user_id INT UNSIGNED NOT NULL` dengan komentar "FK ke usr_users.id" tapi tanpa `FOREIGN KEY` sungguhan - pola sama seperti `srp2_documents.registration_id` di audit pengembang. Bukan celah keamanan (query aplikasi sudah scoped benar), tapi memperparah dampak Temuan #6.

**Rekomendasi:** migrasi baru untuk FK `ON DELETE CASCADE` (jangan ubah migrasi lama).

---

## 🟡 8. Tidak ada pencegahan duplikat pengajuan

`KemitraanPortal::simpan()` tidak mengecek apakah user sudah punya pengajuan `Diajukan` untuk `jenis` yang sama sebelum insert - beda dari SRP2 yang punya status-lock server-side. Dampak rendah (bukan keamanan), tapi longgar dibanding pola status-lock yang sudah jadi standar di role lain.

**Rekomendasi:** cek `WHERE user_id=? AND jenis=? AND status='Diajukan'` sebelum insert - prioritas rendah.

---

## ✅ 9. Pola yang sudah benar

1. **Anti-IDOR konsisten** - `user_id` selalu dari `get_user_id()` sesi (dikomentari eksplisit di kode), `Pengaturan.php` baca `WHERE user_id=<sesi>`, `Admin_Kemitraan::proses()` di-gate lewat `Admin_Controller`.
2. **CSRF hadir** di form pendaftaran maupun form admin.
3. **Validasi server-side lengkap** untuk field non-file (`required`, `numeric`, `regex_match` tanggal).
4. **Role gate eksplisit** (`akses_mahasiswa()`) - cek login dulu, baru cek role, pesan error jujur.
5. **Status yang ditampilkan ke mahasiswa dan ditulis admin memakai enum yang sama persis** - tidak ada mismatch nama status.

## Rekomendasi Urutan Pengerjaan

1. **#2 + #3** - keamanan, dampak langsung ke data pribadi mahasiswa.
2. **#6** - mencegah file publik "hantu" tetap bisa diakses setelah akun dihapus.
3. **#4** - fungsional, tanpa ini admin memutuskan buta terhadap bukti pendukung.
4. **#7, #8** - cleanup minor, bisa menunggu momentum yang sama dengan #6.

Tidak ada kode yang diubah dalam audit ini - murni observasi berbasis pembacaan kode.
