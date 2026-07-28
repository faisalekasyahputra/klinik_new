# Audit Peran `warga` — Sistem, Keamanan, Konsistensi

**Tanggal audit:** 26 Juli 2026
**Metode:** pembacaan langsung kode atas seluruh permukaan yang menyentuh role `warga`: `Pengaturan.php` (`index()`, `profil()`), `Program.php` (`submit_antrean`, `ajukan_solusi`, `cek_tiket`, `cek_status_pengajuan`), `Umum.php` (`simpan_aduan`), `Admin_Kabkota.php`/`Admin_Bidang.php` + `MY_Controller.php`, migrasi `20260701000005..000009`, `User_model::delete_user_account()`, `config.php` (CSRF), dan view-view form terkait. Dokumen historis (`DESAIN_STATUS_TIKET_PENGAJUAN.md`, `ROLE_DATA_RELATION_MAP.md`, `AGENTS.md §11` lama) diverifikasi terhadap kode nyata, bukan dipercaya langsung — lihat Temuan #1.

> **Status 27 Juli 2026:** empat temuan lama sudah ditangani. Upload aduan sudah privat, FK/cleanup user sudah dinormalisasi, dan kedua jalur antrean kini memakai satu gerbang yang mewajibkan wilayah. Pekerjaan terbaru beserta aturan transisi dan bukti HTTP ada di [`PRD_WARGA_ADMIN_KABKOTA.md`](../product/PRD_WARGA_ADMIN_KABKOTA.md) dan [`uji_perjalanan_warga.php`](uji_perjalanan_warga.php). Dokumen ini tetap dipertahankan sebagai catatan audit historis.

Bagian dari rangkaian audit multi-role, lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) untuk pola lintas-role dan [`AUDIT_ROLE_PENGEMBANG.md`](AUDIT_ROLE_PENGEMBANG.md) untuk audit role pertama yang jadi rujukan format.

---

## Ringkasan Temuan

| # | Temuan | Kategori | Tingkat |
|---|---|---|---|
| 1 | `AGENTS.md §11`/`DESAIN_STATUS_TIKET_PENGAJUAN.md` bilang admin dashboard antrean "Fase 10 belum dimulai" — usang, `Admin_Kabkota`/`Admin_Bidang` sudah ada dan fungsional penuh | Dokumentasi usang (bukan bug kode) | 🟡 Rendah *(sudah diperbaiki di AGENTS.md — lihat catatan di bawah)* |
| 2 | `Umum::simpan_aduan()` menyimpan lampiran ke `.assets/uploads/` — path relatif di dalam webroot, bukan `private_uploads/` | Keamanan | 🟠 Sedang |
| 3 | `User_model::delete_user_account()` tidak membersihkan/anonim-kan `aduan.user_id` milik user yang dihapus — beda perlakuan dengan `sf_housing_queue` yang punya FK `ON DELETE SET NULL` | Data integrity | 🟡 Rendah-Sedang |
| 4 | `Program::ajukan_solusi()` masih tidak mengisi `kabupaten_id` (beda dengan `submit_antrean()`) — klaim `AGENTS.md §16` terverifikasi masih benar | Konsistensi data | 🟠 Sedang |
| 5 | Anti-IDOR, CSRF, dan double-WHERE scope pada `Admin_Kabkota`/`Admin_Bidang` sudah benar dan konsisten | — (positif) | ✅ |
| 6 | `Program::cek_tiket()` tidak membocorkan PII, rate limit `sys_ticket_lookup_limits` terpasang dan berfungsi | — (positif) | ✅ |
| 7 | CSRF token hadir di semua form POST warga yang diperiksa | — (positif) | ✅ |

---

## 🟡 1. Dokumen "Fase 10 belum dimulai" sudah usang terhadap kode aktual

`AGENTS.md §11` (sebelum diperbaiki) menyatakan Fase 10 (admin dashboard validasi antrean) "belum dimulai". Faktanya `Admin_Kabkota.php` dan `Admin_Bidang.php` sudah ada, fungsional penuh: `index()` (list ter-scope) dan `update_status()` (aksi ubah status + catatan admin), dipasang lewat `Admin_Kabkota_Controller`/`Admin_Bidang_Controller` yang mem-blok akses berdasarkan role session dan scope. Ini juga sudah tercermin di `AGENTS.md §16` sendiri — jadi §11 dan `docs/product/DESAIN_STATUS_TIKET_PENGAJUAN.md` adalah bagian yang tertinggal, bukan representasi state kode saat ini.

**Dampak:** developer berikutnya yang baca §11 saja bisa membangun ulang admin dashboard yang sudah ada, atau salah menyimpulkan role `warga` "tidak punya penanggung jawab" — padahal ini justru salah satu bagian **paling matang** di sistem role (kontras dengan gap SRP2 di `AUDIT_ROLE_PENGEMBANG.md`).

**Status:** klaim usang di `AGENTS.md §11` sudah diperbaiki sebagai bagian dari audit ini (lihat commit/edit terkait). `docs/product/DESAIN_STATUS_TIKET_PENGAJUAN.md` dan `docs/engineering/ROLE_DATA_RELATION_MAP.md` masih perlu diperbarui terpisah (di luar cakupan edit otomatis audit ini, dicatat sebagai follow-up).

---

## 🟠 2. Lampiran aduan disimpan di direktori publik, bukan `private_uploads/`

`Umum::simpan_aduan()`:
```php
$upload_path = '.assets/uploads/';
if (!is_dir($upload_path)) { mkdir($upload_path, 0755, TRUE); }
$config['upload_path']   = $upload_path;
$config['allowed_types'] = 'jpg|jpeg|png|pdf';
$config['max_size']      = 5120;
$config['encrypt_name']  = TRUE;
```
Path ini **relatif** (bukan `FCPATH`/`dirname(FCPATH)` eksplisit), resolve terhadap CWD proses PHP — pada request web normal itu direktori webroot, sama level dengan `assets/` publik. `.assets/uploads/` (nama diawali titik) tetap **di dalam webroot** — server web standar tidak otomatis memblokir akses ke direktori berawalan titik kecuali dikonfigurasi eksplisit, dan repo ini tidak terlihat punya proteksi semacam itu untuk path ini. Validasi hanya whitelist ekstensi + nama file acak (`encrypt_name`) — tidak ada pengecekan MIME asli via `finfo` seperti pola rujukan SRP2.

**Dampak:** lampiran aduan warga (berpotensi memuat PII sesuai isi form) berisiko diakses langsung lewat HTTP kalau nama file acak bocor — pola risiko yang sama dengan Temuan #3 `AUDIT_ROLE_PENGEMBANG.md`, cuma di controller berbeda. Lihat juga [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) — ini kejadian ketiga dari pola yang sama di tiga controller berbeda.

**Rekomendasi:** pindahkan ke `private_uploads/aduan/...`, sajikan lewat endpoint terautentikasi, tambah cek MIME asli via `finfo`.

---

## 🟡 3. `delete_user_account()` tidak membersihkan `aduan.user_id`

`sf_housing_queue.user_id` punya `FOREIGN KEY ... ON DELETE SET NULL` — kalau user dihapus, baris antrean otomatis anonim. Tapi `aduan.user_id` **tidak punya FK constraint sama sekali** — cuma `KEY idx_user_id` biasa. `User_model::delete_user_account()` eksplisit membersihkan `forum_komentar`/`forum_diskusi`/`forum_likes`, tapi **tidak menyentuh `aduan`**.

**Dampak:** setelah warga hapus akun, baris `aduan` miliknya tetap ada dengan `user_id` yang menunjuk ID yang sudah tidak ada — orphan reference senyap, bukan crash (tidak ada FK yang menegakkan). Belum ada endpoint yang dieksploitasi lewat ini, tapi risiko laten kalau ada fitur baru yang query `aduan` berasumsi `user_id` selalu valid.

**Rekomendasi:** tambahkan `aduan.user_id = NULL` di `delete_user_account()`, konsisten dengan pola forum. Lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md) — pola cleanup tidak lengkap ini berulang di `mahasiswa` (`kkn_magang_pendaftaran`) juga.

---

## 🟠 4. `Program::ajukan_solusi()` masih tidak mengisi `kabupaten_id`

Dua jalur beda membuat baris `sf_housing_queue`: `submit_antrean()` mengisi `kabupaten_id` dari POST, `ajukan_solusi()` **tidak** — persis seperti dicatat di `AGENTS.md §16` ("Diketahui belum lengkap"), terverifikasi masih benar di kode saat ini.

**Dampak:** pengajuan lewat alur "Solusi Pembiayaan" akan punya `kabupaten_id = NULL`, sehingga **tidak pernah muncul** di dashboard `Admin_Kabkota` manapun (filter `WHERE kabupaten_id = <scope>` tidak match NULL) — dead-end fungsional untuk sisi verifikasi admin, meski warga sendiri tetap melihat statusnya di `/akun` tanpa sadar pengajuannya "hilang" dari radar admin.

**Rekomendasi:** tambahkan field kabupaten ke alur `ajukan_solusi()` (dari form diagnosa awal atau diminta eksplisit sebelum submit). Pertimbangkan konsolidasi `submit_antrean()`/`ajukan_solusi()` ke satu helper `Program_model::insert_housing_queue()` yang mewajibkan `kabupaten_id` sebagai parameter — supaya kelalaian field seperti ini terdeteksi di level tipe, bukan cuma review manual.

---

## ✅ Pola yang sudah benar

1. **Anti-IDOR ganda di sisi admin ter-scope:** `Admin_Kabkota::update_status()`/`Admin_Bidang::update_status()` pakai `WHERE id = ? AND kabupaten_id/bidang = ?` (dari session), cek `affected_rows()` untuk bedakan sukses vs scope salah. Scope selalu dari session, kosong → redirect paksa, bukan fallback diam-diam.
2. **`Pengaturan::index()` selalu `WHERE user_id = <session>`** untuk semua tabel (`sf_housing_queue`, `aduan`, `srp2_registrations`, `kkn_magang_pendaftaran`).
3. **`Program::cek_tiket()` tidak membocorkan PII** — cuma `status_antrean`, `created_at`, `updated_at` yang di-return.
4. **Rate limiting tiket publik terpasang dan berfungsi** — `sys_ticket_lookup_limits`, IP di-hash SHA-256, limit 5 gagal/menit, HTTP 429 + `Retry-After` jujur.
5. **CSRF global aktif**, tidak ada endpoint warga yang di-exclude, semua form yang diperiksa menyertakan token.
6. **Kode tiket tidak sekuensial/tidak bisa ditebak** — `random_int` dari alphabet 32 karakter, dicek unik ke DB.

## Catatan tambahan

`Program::submit_antrean()` menerima `nik`/`nama_lengkap` langsung dari POST tanpa verifikasi identitas server-side — ini **bukan** IDOR (data self-report yang memang menunggu verifikasi manual admin), tapi dicatat supaya tidak ada asumsi keliru "NIK di `sf_housing_queue` sudah terverifikasi" untuk jalur ini.

## Rekomendasi Urutan Pengerjaan

1. **#4** — paling berdampak fungsional (pengajuan warga dead-end di sisi admin).
2. **#2** — keamanan, sejalan dengan perbaikan serupa di role lain (lihat ringkasan lintas-role).
3. **#3** — data integrity, murah untuk dikerjakan sekalian dengan #2.
4. **#1** — sudah diperbaiki sebagian (AGENTS.md), sisanya dokumentasi non-teknis.

Tidak ada kode yang diubah dalam audit ini — murni observasi berbasis pembacaan kode.
