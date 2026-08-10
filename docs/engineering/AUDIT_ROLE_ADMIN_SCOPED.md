# Audit Peran `admin_kabkota` & `admin_bidang` - Sistem, Keamanan, Konsistensi

**Tanggal audit:** 26 Juli 2026
**Metode:** pembacaan langsung `MY_Controller.php` (`Admin_Kabkota_Controller`/`Admin_Bidang_Controller`), `Admin_Kabkota.php`, `Admin_Bidang.php`, `Admin_Users.php` (provisioning), `Auth.php` (login/onboarding/Google OAuth), `Program.php` (sumber `sf_housing_queue`), `Umum.php` (sumber `aduan`), `Admin.php`/`Admin_model.php` (jalur superadmin lama), `roles.php`, migrasi kabupaten/bidang/ticket, view dashboard scoped.

Kedua role digabung dalam satu dokumen karena strukturnya identik (role admin ter-scope, base controller sejenis, pola anti-IDOR yang sama harus diverifikasi terpisah). Bagian dari rangkaian audit multi-role, lihat [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](AUDIT_SISTEM_ROLE_RINGKASAN.md).

---

## Ringkasan Temuan

| # | Role | Temuan | Kategori | Tingkat |
|---|---|---|---|---|
| 1 | `admin_kabkota` | `sf_housing_queue.kabupaten_id` - kunci scoping seluruh model reviewer ini - diisi langsung dari `$_POST` warga saat submit, tanpa validasi terhadap domisili/wilayah program manapun | Keamanan (trust boundary) | 🔴 Tinggi |
| 2 | `admin_kabkota` | Tidak ada kolom aktor (`reviewed_by`/`admin_id`) di `sf_housing_queue` - keputusan admin_kabkota tidak tercatat siapa yang memutuskan | Data-integrity / Akuntabilitas | 🟠 Sedang |
| 3 | `admin_kabkota` | Dua controller berbeda (`Admin::update_status` lama tanpa scope, `Admin_Kabkota::update_status` baru ter-scope) menulis kolom `status_antrean` yang sama tanpa koordinasi | Konsistensi | 🟡 Rendah-Sedang |
| 4 | `admin_kabkota` | Baris `kabupaten_id = NULL` (data lama/hasil Temuan #1) tidak pernah muncul di dashboard admin_kabkota manapun - cuma terlihat lewat jalur superadmin lama | Data-integrity / Fungsional | 🟡 Rendah |
| 5 | `admin_bidang` | Superadmin tidak punya visibilitas maupun override sama sekali atas `aduan` - tidak ada satu pun controller superadmin yang menyentuh tabel ini | Fungsional | 🟠 Sedang |
| 6 | `admin_bidang` | `affected_rows() > 0` salah menganggap update no-op (status/catatan sama persis dengan yang tersimpan) sebagai "data tidak ditemukan di bidang Anda" - kemungkinan pola sama di `Admin_Kabkota.php` | Fungsional (UX/false-negative) | 🟡 Rendah-Sedang |
| 7 | `admin_bidang` | Pelapor aduan tidak pernah melihat `catatan_admin` yang diisi admin_bidang - beda dengan pola SRP2 (`catatan_admin` pengembang ditampilkan ke pengembang) | Konsistensi + fungsional | 🟡 Rendah |
| 8 | Keduanya | Anti-IDOR ganda (`WHERE id AND kabupaten_id`/`bidang`), differensiasi `affected_rows()`, sumber scope dari session, guard provisioning tertutup, dan CSRF - semua benar dan konsisten satu sama lain | - (positif) | ✅ |

---

## 🔴 1. `kabupaten_id` pada `sf_housing_queue` diisi mentah dari request warga

`Program::submit_antrean()` menerima `kabupaten_id` langsung dari body POST tanpa validasi apa pun: tidak dicek terhadap kabupaten domisili user yang login, tidak dicek terhadap wilayah cakupan program yang dipilih, tidak dicek keberadaannya di tabel `kabupaten`.

Sisi *reviewer* (`Admin_Kabkota.php`) sudah benar memakai `kabupaten_id` dari session, bukan dari request. Tapi kolom yang di-*WHERE*-kan itu sendiri, nilainya di sisi *penulisan awal*, sepenuhnya dikontrol klien. Efeknya:
- Warga bisa memilih kabupaten mana pun (termasuk yang bukan domisilinya) supaya pengajuannya masuk ke antrean admin_kabkota tertentu - model "satu kabupaten satu reviewer" jadi self-declared, bukan diverifikasi sistem.
- Warga bisa mengirim `kabupaten_id` kosong/tidak valid sehingga baris jadi tidak terlihat oleh admin_kabkota manapun (lihat Temuan #4) - potensi menghindari review daerah sama sekali.

**Dampak:** bukan IDOR klasik (warga tidak membaca/menulis data warga lain), tapi melanggar model kepercayaan yang jadi premis seluruh desain `admin_kabkota` - "admin kabupaten A hanya menangani wilayahnya" mengasumsikan `kabupaten_id` baris itu benar mencerminkan wilayah pemohon, padahal nilainya bisa dipalsukan si pemohon sendiri.

**Rekomendasi:** turunkan `kabupaten_id` dari sumber tepercaya - domisili user (`usr_users`, bila login) atau field wilayah program (`sf_programs`), bukan `$_POST`. Kalau memang perlu dipilih user, minimal validasi terhadap tabel `kabupaten` sebelum insert.

---

## 🟠 2. Tidak ada jejak aktor yang memproses antrean

Migrasi `sf_housing_queue` tidak menambah kolom `reviewed_by`/`processed_by`. `Admin_Kabkota::update_status()` hanya menulis `status_antrean` dan `catatan_admin` - tidak ada kolom untuk mencatat *siapa* admin_kabkota yang membuat keputusan.

**Dampak:** superadmin tidak sepenuhnya black-box (bisa lihat status/catatan lewat `Admin::index()`, lihat Temuan #3), tapi tidak bisa tahu admin_kabkota mana yang memutuskan. Kalau ada sengketa atau admin_kabkota nakal, tidak ada cara audit ke akun spesifik.

**Rekomendasi:** tambah kolom `processed_by INT UNSIGNED NULL` (FK `usr_users.id`, `ON DELETE SET NULL`) via migrasi baru, isi dari session di `update_status()`.

---

## 🟡 3. Dua jalur tulis berbeda ke `sf_housing_queue.status_antrean`

Jalur lama superadmin tanpa scope (`Admin::update_status()` → `Admin_model::update_queue_status()`, `WHERE id = ?` saja - wajar untuk superadmin, bukan bug) dan jalur baru ter-scope (`Admin_Kabkota::update_status()`) sama-sama menulis kolom yang sama tanpa koordinasi - tidak ada status lock, tidak ada indikasi "sedang diproses pihak lain". Beda dari pola SRP2 yang sudah punya server-side status lock.

**Dampak:** race/tumpang-tindih rendah risiko, tapi silent overwrite (last-write-wins tanpa optimistic lock) mungkin terjadi kalau superadmin dan admin_kabkota membuka baris sama nyaris bersamaan.

**Rekomendasi:** evaluasi apakah jalur superadmin lama masih relevan sekarang `admin_kabkota` sudah ada - pertimbangkan dibuat read-only + override eksplisit, bukan tombol proses paralel.

---

## 🟡 4. Baris `kabupaten_id = NULL` tidak pernah terlihat admin_kabkota manapun

`WHERE kabupaten_id = <scope>` tidak pernah match `NULL` - ini **perilaku aman** (tidak "match semua" saat NULL), tapi konsekuensinya baris lama/hasil Temuan #1 jadi orphan dari sudut pandang admin_kabkota, cuma terlihat lewat `Admin::index()` superadmin (unscoped).

**Rekomendasi:** setelah Temuan #1 diperbaiki, backfill data lama kalau ada, atau tampilkan badge "Belum Terpetakan Wilayah" di dashboard superadmin untuk baris `kabupaten_id IS NULL`.

---

## 🟠 5. Superadmin tanpa visibilitas/override atas `aduan`

Hanya tiga tempat menyentuh tabel `aduan`: `Umum.php` (kirim, milik pelapor), `Admin_Bidang.php` (kelola, scoped), `Pengaturan.php` (baca status milik sendiri). **Tidak ada satu pun controller superadmin** yang membaca atau menulis `aduan`.

**Dampak:** kalau admin bidang salah menutup aduan, atau ada bidang tanpa admin_bidang ter-assign, aduan itu praktis tidak terlihat siapa pun di sisi admin - superadmin tidak punya daftar/laporan agregat, apalagi kemampuan override.

**Rekomendasi:** tambah view read-only ringkas di panel superadmin (lintas bidang + status) untuk audit/eskalasi, tanpa perlu endpoint tulis (override tetap lewat kewenangan admin_bidang).

---

## 🟡 6. False-negative pada `affected_rows()` untuk update no-op

```php
$this->db->where('id', (int) $id)->where('bidang', $this->my_bidang_kode)
    ->update('aduan', ['status' => $status, 'catatan_admin' => trim(...)]);
if ($this->db->affected_rows() > 0) { /* sukses */ } else { /* "Data tidak ditemukan di bidang Anda" */ }
```
`affected_rows()` MySQLi mengembalikan `0` bukan cuma saat `WHERE` tidak match (percobaan lintas-scope), tapi juga saat `WHERE` match tapi nilai kolom yang di-`UPDATE` persis sama dengan yang sudah tersimpan (mis. admin klik "Simpan" tanpa mengubah apa pun). Kedua kasus menghasilkan pesan identik - padahal yang kedua sebenarnya sukses.

**Dampak:** bukan celah keamanan (WHERE ganda tetap benar), tapi membingungkan admin - pesan error menyiratkan seolah mencoba akses di luar scope padahal cuma resubmit nilai identik. Kemungkinan pola sama berlaku di `Admin_Kabkota.php` (tidak diverifikasi eksplisit di audit ini, layak dicek terpisah).

**Rekomendasi:** cek keberadaan baris di scope lewat `SELECT`/`num_rows()` sebelum `UPDATE`, bedakan pesan "berhasil (tidak ada perubahan)" dari "tidak ditemukan di bidang Anda".

---

## 🟡 7. `catatan_admin` aduan tidak pernah tampil ke pelapor

SRP2 punya pola tampil-ke-pemohon yang jelas (`catatan_admin` ditampilkan kalau `status_verifikasi == 'Ditolak'`). Untuk `aduan`, `catatan_admin` hanya dibaca+ditampilkan di sisi admin (`admin_bidang/dashboard.php`) - `Pengaturan::index()` yang menampilkan status ke pelapor cuma select `id, judul, bidang, status, created_at`, tidak menyertakan `catatan_admin`.

**Dampak:** pelapor hanya melihat status berubah tanpa tahu alasannya kalau admin menambahkan catatan penjelasan.

**Rekomendasi:** tambahkan `catatan_admin` ke select `Pengaturan.php` dan tampilkan di view status kalau tidak kosong.

---

## ✅ 8. Pola yang sudah benar (kedua role)

1. **Anti-IDOR ganda + differensiasi affected_rows** (di luar nuansa Temuan #6) - `WHERE id = ? AND kabupaten_id/bidang = ?`, scope dari session; percobaan lintas-scope mendapat respons jujur berbeda dari sukses, tanpa membocorkan apakah ID itu eksis di scope lain.
2. **Scope (`my_kabupaten_id`/`my_bidang_kode`) murni dari session** - satu-satunya penulis adalah `Admin_Users::create_staff()`/`update_role()` (superadmin-only) dan `Auth::do_login()`/Google OAuth callback (baca dari DB, bukan dari request).
3. **Guard kosong-scope aman** - session kosong → flashdata error + redirect ke login, bukan lanjut dengan query berpotensi salah filter.
4. **Provisioning tertutup dari pendaftaran publik** - `admin_kabkota`/`admin_bidang` tidak ada di `Auth::onboarding()` `$valid_roles`; satu-satunya jalan lewat `Admin_Users` oleh superadmin, dengan validasi `kabupaten_id`/`bidang_kode` terhadap tabel induk sebelum insert/update.
5. **CSRF aktif dan tidak dikecualikan** untuk endpoint kedua role ini.
6. **`bidang` bukan free-text meski kolomnya varchar** - validasi `in_list[...]` di `Umum::simpan_aduan()` + dropdown hardcode 5 opsi + tabel `bidang` diseed 5 kode identik. Risiko "aduan orphan karena typo bidang" **tidak terjadi** - beda dengan kekhawatiran awal di brief audit ini.
7. **Superadmin punya jalur override untuk antrean** (`Admin.php`/`Admin_model.php`) - bisa lihat dan ubah seluruh baris `sf_housing_queue` lintas kabupaten (lihat Temuan #2 untuk gap akuntabilitas). Untuk `aduan`, jalur setara ini **tidak ada** (Temuan #5) - asimetri antara dua role admin ter-scope ini dicatat sebagai temuan tersendiri.

## Rekomendasi Urutan Pengerjaan

1. **#1** - paling kritis: akar dari model kepercayaan seluruh role `admin_kabkota`.
2. **#2** - murah dikerjakan sekalian dengan #1 (sama-sama menyentuh skema `sf_housing_queue`).
3. **#5** - kesenjangan struktural (superadmin buta terhadap `aduan`), tidak butuh perubahan skema besar (cukup view read-only).
4. **#6** - perbaikan UX kecil, murah.
5. **#3, #4, #7** - cleanup konsistensi, bisa menunggu momentum yang sama.

Tidak ada kode yang diubah dalam audit ini - murni observasi berbasis pembacaan kode.
