# Ringkasan Audit Sistem Role Multi-Peran — Pola Lintas-Role

**Tanggal audit:** 26 Juli 2026
**Cakupan:** kelima role non-superadmin di `application/config/roles.php` — `pengembang`, `warga`, `mahasiswa`, `admin_kabkota`, `admin_bidang` — diaudit satu per satu (paralel untuk 4 role terakhir), hasil lengkap di dokumen masing-masing:

- [`AUDIT_ROLE_PENGEMBANG.md`](AUDIT_ROLE_PENGEMBANG.md)
- [`AUDIT_ROLE_WARGA.md`](AUDIT_ROLE_WARGA.md)
- [`AUDIT_ROLE_MAHASISWA.md`](AUDIT_ROLE_MAHASISWA.md)
- [`AUDIT_ROLE_ADMIN_SCOPED.md`](AUDIT_ROLE_ADMIN_SCOPED.md) (`admin_kabkota` + `admin_bidang`)

Dokumen ini **bukan** pengulangan isi keempatnya — isinya cuma pola yang muncul berulang di lebih dari satu role, yang lebih efisien diperbaiki sekali secara terpusat daripada ditambal satu-satu per controller.

---

## Pola A — Upload disimpan di direktori publik (3 lokasi berbeda, gejala sama)

Ditemukan berulang di:
1. `Auth::_handle_uploads()` — KTP/SIUP (`pengembang`/`vendor`), KTM/surat magang (`mahasiswa`) → `FCPATH . 'uploads/documents/' . $user_id`.
2. `Umum::simpan_aduan()` — lampiran aduan (`warga`) → `.assets/uploads/`.
3. `KemitraanPortal::simpan()` — surat pengantar KKN/Magang (`mahasiswa`) → `.assets/uploads/`.

Ketiganya sama-sama di dalam webroot (bisa diakses HTTP langsung kalau nama file acak bocor), berbeda dari pola aman yang sudah dipakai `Pengembang::simpan_dokumen()` (`private_uploads/`, satu level di atas webroot). Ini **pola paling berdampak untuk diperbaiki sekali secara terpusat** — bukan tiga perbaikan terpisah.

**Rekomendasi konsolidasi:** buat satu helper (mis. `MY_Controller::store_private_upload($file, $subfolder, $allowed_ext)` atau method di model baru `Upload_model`) yang menangani: validasi ekstensi + `finfo` MIME asli + cap ukuran + nama acak + simpan ke `private_uploads/{subfolder}/{id}/`. Panggil dari ketiga lokasi di atas, plus jadikan pola wajib untuk role baru ke depan (lihat checklist di `docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md` bagian "Prinsip Umum untuk Role Baru", poin 3). Selesaikan ini SEBELUM menambah endpoint upload baru untuk role lain — kalau tidak, kemungkinan besar jadi lokasi keempat dengan bug yang sama.

---

### Status Pola A: SUDAH DIPERBAIKI (26 Jul 2026) — plus temuan yang lebih besar di baliknya

Ketiga jalur unggah kini lewat satu helper `MY_Controller::store_private_upload()`
(validasi ekstensi + MIME asli via `finfo` + batas ukuran + nama acak) dan
disajikan lewat endpoint ber-guard: `Admin_Kemitraan::lihat_dokumen()`,
`Admin_Bidang::lihat_lampiran()` (anti-IDOR WHERE ganda), `Admin_Aduan::lihat_lampiran()`.
Dokumen onboarding tidak dibuatkan endpoint karena memang tidak ada UI yang
membacanya (`get_user_documents()` tidak pernah dipanggil). Tidak ada migrasi
data: saat perbaikan, direktori publik kosong dan nol baris DB menunjuk ke sana.

Efek samping yang ikut tertutup: admin KKN/Magang dan admin bidang kini bisa
membuka berkas pendukung — sebelumnya memutuskan terima/tolak tanpa bisa
melihat buktinya sama sekali (AUDIT_ROLE_MAHASISWA.md #4).

**🔴 Temuan lebih besar yang muncul saat verifikasi:** asumsi "`private_uploads/`
ada di luar webroot" — yang jadi dasar kontrak keamanan di `AGENTS.md` §9 dan
`SRP2_ACCOUNT_FLOW.md` — **tidak benar di layout ini**. `dirname(FCPATH)` ternyata
sama dengan DocumentRoot Apache (`C:/xampp/htdocs`), jadi seluruh isi
`private_uploads/` tersaji lewat HTTP. Dibuktikan langsung: dokumen SRP2 milik
pengembang (14 berkas persyaratan, ada sejak sebelum sesi ini) bisa diunduh
tanpa login. Ini luput dari SEMUA audit sebelumnya karena audit membaca kode dan
mempercayai nama direktorinya, bukan mencoba mengaksesnya lewat HTTP.

Mitigasi terpasang: `ensure_private_uploads_protected()` menulis `.htaccess`
penolak akses di akar `private_uploads/`, dipanggil dari setiap jalur yang
membuat direktori. Ditulis oleh kode karena direktori itu di luar repo git.
Terverifikasi: yang tadinya 200 kini 403, sementara endpoint ber-guard tetap 200.

**Yang masih terbuka soal ini:**
- **Belum diverifikasi di production.** Di Hostinger `FCPATH` kemungkinan
  `.../public_html/` sehingga `dirname()`-nya aman — tapi itu dugaan, bukan fakta.
- `.htaccess` hanya dipatuhi Apache/LiteSpeed. Pindah ke nginx = proteksi hilang.
- Perbaikan yang benar-benar kokoh: jadikan lokasi penyimpanan variabel `.env`
  yang menunjuk direktori pasti di luar DocumentRoot, bukan hasil `dirname(FCPATH)`.

## Pola B — `delete_user_account()` tidak membersihkan semua tabel turunan role

`User_model::delete_user_account()` saat ini eksplisit menangani `forum_komentar`/`forum_diskusi`/`forum_likes`, tapi **tidak** menyentuh:
- `aduan.user_id` (role `warga`) — tidak ada FK, jadi orphan reference senyap.
- `kkn_magang_pendaftaran` (role `mahasiswa`) — tidak dihapus/dianonimkan, dan file fisik terkait (lihat Pola A) tetap ada selamanya, termasuk setelah akun "dihapus".
- `srp2_registrations`/`srp2_documents` (role `pengembang`) — belum terverifikasi eksplisit di audit awal (dicatat sebagai follow-up di `AUDIT_ROLE_PENGEMBANG.md` Temuan #5), kemungkinan besar gap yang sama berdasarkan pola yang sekarang terlihat di 2 role lain.

**Dampak gabungan:** ini bukan tiga bug independen, ini satu method yang dari awal ditulis cuma untuk fitur forum dan tidak pernah diperbarui saat tabel-tabel role baru ditambahkan. Setiap kali migrasi baru menambah tabel `*.user_id`, method ini harus ikut diperbarui — sampai sekarang itu tidak pernah terjadi secara konsisten.

**Rekomendasi konsolidasi:** audit `delete_user_account()` sekali untuk SEMUA tabel yang punya kolom `user_id` mengacu ke akun (grep `user_id` di seluruh file migrasi untuk daftar lengkap), putuskan per tabel apakah perlu **hapus baris** (kalau datanya milik pribadi murni, mis. `kkn_magang_pendaftaran`) atau **anonim-kan** (`user_id = NULL`, kalau datanya juga relevan untuk pihak lain, mis. `aduan` yang mungkin masih dalam proses ditangani admin_bidang). Untuk yang "hapus baris", pastikan file fisik terkait ikut di-`unlink()` — FK `ON DELETE CASCADE` di level DB tidak bisa menghapus file di disk.

---

## Pola C — Kunci "wilayah/scope" pengajuan yang dipercaya dari input klien

Ditemukan di `Program::submit_antrean()`: `kabupaten_id` — nilai yang jadi dasar filter `WHERE` di seluruh dashboard `Admin_Kabkota` — diambil mentah dari `$_POST` tanpa validasi terhadap domisili user atau wilayah program (`AUDIT_ROLE_ADMIN_SCOPED.md` Temuan #1). Ini kelas masalah yang beda dari IDOR biasa (bukan "user lain bisa baca/tulis data saya"), tapi "user bisa memalsukan atribut yang menentukan **siapa yang berwenang meninjau** datanya" — sama pentingnya untuk role ter-scope (`admin_kabkota`/`admin_bidang`) karena seluruh model kepercayaan mereka bertumpu pada kolom itu.

**Relevansi untuk role baru:** kalau role admin ter-scope baru dibuat ke depan (mis. per-kecamatan, per-jenis-layanan), pastikan kolom scoping-nya **selalu diturunkan dari data yang sudah terverifikasi** (profil user, hasil lookup lain), bukan field yang bisa dipilih bebas di form yang sama dengan pengajuannya.

---

## Pola D — Prinsip "relasi ke admin" sudah tervalidasi, SRP2 adalah pengecualian

Diskusi sebelum audit paralel ini mempertanyakan: apakah setiap role baru butuh relasi eksplisit ke admin? Hasil audit 4 role tambahan mengonfirmasi jawabannya: **hampir semua role di sistem ini SUDAH mengikuti prinsip itu dengan benar** —

| Role (penghasil data) | Admin/reviewer | Status |
|---|---|---|
| `warga` (antrean) | `Admin_Kabkota` | ✅ Fungsional penuh |
| `warga`/semua role (aduan) | `Admin_Bidang` | ✅ Fungsional penuh (dengan gap kecil: superadmin tidak lihat lintas bidang, Pola A/B di atas) |
| `mahasiswa` (KKN/Magang) | `Admin_Kemitraan` | ✅ Fungsional penuh |
| `pengembang` (SRP2) | *(tidak ada)* | 🔴 Satu-satunya yang belum dibangun |

Jadi SRP2 bukan representasi kondisi umum sistem — itu pengecualian yang kebetulan jadi fokus audit pertama. Ini memperkuat rekomendasi di `PRD_VERIFIKASI_ADMIN_SRP2.md`: menutup gap SRP2 itu sendiri sudah cukup untuk menyamakan SRP2 dengan standar yang sudah dipegang role lain, bukan pola baru yang perlu diciptakan dari nol.

---

## Rekomendasi Urutan Pengerjaan Gabungan

Kalau sumber daya terbatas dan harus pilih urutan lintas semua role sekaligus (bukan per-dokumen), urutan yang disarankan:

1. **`AUDIT_ROLE_ADMIN_SCOPED.md` #1** (kabupaten_id dari request mentah) — 🔴 satu-satunya temuan tingkat Tinggi di seluruh rangkaian audit ini, menyangkut integritas model scoping admin.
2. **`AUDIT_ROLE_PENGEMBANG.md` #1** (admin approve SRP2 belum ada) — pipeline dead-end fungsional, sudah punya PRD tersendiri (`PRD_VERIFIKASI_ADMIN_SRP2.md`).
3. **Pola A di atas** (konsolidasi upload ke `private_uploads/`) — satu perbaikan menutup 3 gap keamanan sekaligus.
4. **Pola B di atas** (audit `delete_user_account()` menyeluruh) — satu perbaikan menutup gap data-integrity/privasi di minimal 2-3 role.
5. Sisanya (temuan 🟡 per-role) — bisa dikerjakan bertahap sesuai urutan yang tercantum di tiap dokumen audit masing-masing.

Tidak ada kode yang diubah dalam audit ini maupun ringkasan ini — murni observasi dan konsolidasi berbasis pembacaan kode.
