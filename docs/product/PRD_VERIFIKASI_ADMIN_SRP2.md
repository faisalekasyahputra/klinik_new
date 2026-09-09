# PRD - Verifikasi Admin untuk Pengajuan SRP2 (+ Prinsip Relasi Role↔Admin)

**Status:** ✅ **SELESAI SEMUA (Fase 0-4), 26 Juli 2026.** Ringkas per fase:
- **Fase 0** - kolom link `certified_developer_id` (migrasi `20260701000014`), opsi (b) dipakai sesuai rekomendasi.
- **Fase 1** - `Admin_Srp2::pending()/detail()/proses()/lihat_dokumen()`. *Menyimpang dari rencana:* satu endpoint `proses($id)` berfield `status`, bukan `terima()`/`tolak()` terpisah - mengikuti pola `Admin_Kemitraan` yang sudah terbukti supaya komponen `review_form` benar-benar dipakai ulang. Semua aturan (approve idempotent + auto-link, tolak wajib catatan divalidasi server, dokumen lewat endpoint ber-guard) tetap dipenuhi.
- **Fase 2** - `Auth_model::ensure_srp2_draft()` jadi satu-satunya tempat draft dibuat; lima pemanggil diarahkan ke sana, termasuk `Auth::save_onboarding()` yang dulu tidak membuatnya sama sekali. Terverifikasi: user yang jadi pengembang lewat onboarding langsung melihat item SRP2 di `/akun` tanpa mampir wizard; buka wizard 3x tetap 1 draft.
- **Fase 3** - upload onboarding pindah ke penyimpanan privat, ikut terselesaikan bersama Pola A audit. Endpoint baca sengaja tidak dibuat karena tidak ada UI yang membaca `usr_documents`.
- **Fase 4** - FK `srp2_documents` (migrasi `20260701000013`) + `delete_user_account()` kini `unlink` file fisik sebelum baris ter-CASCADE.

> ⚠️ Terkait FR-11 (dokumen tidak lewat path publik): saat verifikasi Pola A ditemukan bahwa `private_uploads/` **tidak selalu di luar webroot** - lihat catatan di [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](../engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md). Sudah dimitigasi `.htaccess`, tapi belum diverifikasi di production.

Dokumen di bawah dipertahankan sebagai rencana aslinya. Menindaklanjuti [`AUDIT_ROLE_PENGEMBANG.md`](../engineering/AUDIT_ROLE_PENGEMBANG.md) (26 Jul 2026), temuan #1-#5.
**Konteks:** `PRD_SRP2_AKUN_PENGEMBANG.md` (dokumen lama) sudah menyebut "Admin menentukan Diterima atau Ditolak" sebagai bagian dari alur - tapi itu **belum pernah dibangun**. PRD ini menutup gap tersebut, sekaligus merapikan efek sampingnya (dua sumber data pengembang tersertifikasi, dua jalur pembuatan draft, upload di luar pola aman) supaya tidak diwarisi role-role baru yang lebih kompleks.

---

## Tujuan

1. Memberi admin cara nyata untuk memverifikasi pengajuan SRP2 (`Pending` → `Diterima`/`Ditolak`) - tanpa ini, seluruh pipeline wizard SRP2 dead-end di database.
2. Menyatukan `srp2_certified_developers` (direktori publik) dengan `srp2_registrations` (data pengajuan) jadi satu sumber kebenaran berbasis ID, bukan string-match nama.
3. Menetapkan **prinsip baku**: setiap role baru yang menghasilkan data untuk ditinjau pihak lain wajib merancang sisi admin/reviewer-nya di fase yang sama - bukan susulan.

## Pengguna

- **Pengembang** (pemohon): mengirim pengajuan, menunggu keputusan, membaca `catatan_admin` kalau ditolak, memperbaiki dan mengirim ulang.
- **Admin SRP2** (superadmin, lewat `Admin_Srp2.php` - tidak perlu role baru, `admin` sudah cukup lingkupnya untuk domain ini): meninjau dokumen, memutuskan, menulis catatan.

## Alur Produk (target akhir)

```
Pengembang: Draft → [unggah 14 dokumen] → Kirim Pengajuan → Pending
                                                                │
                                        Admin buka daftar Pending, lihat dokumen
                                                                │
                                        ┌───────────────────────┴───────────────────────┐
                                        ▼                                               ▼
                                    Diterima                                        Ditolak
                            (masuk direktori publik                        (catatan_admin wajib diisi,
                             srp2_certified_developers                      pengembang bisa unggah ulang
                             otomatis, bukan input manual)                  dokumen & kirim lagi)
```

## Fase Pengerjaan (urutan wajib, jangan dibalik)

### Fase 0 - Keputusan data model (prasyarat, tidak ada kode)

Sebelum Fase 1 dibangun, putuskan relasi `srp2_certified_developers` ↔ `srp2_registrations` (Temuan #4). Rekomendasi: **opsi (b)** dari audit - pertahankan `srp2_certified_developers` sebagai tabel direktori publik (sudah berisi 60+ nama historis yang tidak semuanya berasal dari `srp2_registrations`), tapi tambah kolom `srp2_registrations.certified_developer_id` (nullable, FK) yang diisi otomatis saat admin approve. Ini menghindari migrasi data besar (opsi a) sambil tetap membuat link berbasis ID untuk pengajuan baru ke depan. Data lama yang cuma ada di `srp2_certified_developers` (tanpa pengajuan) tetap valid apa adanya.

**Keluaran Fase 0:** satu paragraf keputusan ditulis di sini (update PRD ini) sebelum Fase 1 mulai dikerjakan, supaya tidak ada agent yang mulai coding dengan asumsi berbeda.

### Fase 1 - Antarmuka verifikasi admin (menutup Temuan #1, sekaligus #4)

- `Admin_Srp2::pending()` (atau controller/method baru) - daftar `srp2_registrations WHERE status_verifikasi = 'Pending'`, urut `updated_at` (bukan `created_at`, konsisten dengan `Pengaturan.php`).
- `Admin_Srp2::detail($id)` - tampilkan data pengajuan + 14 dokumen. Dokumen **tidak boleh** disajikan lewat path publik - endpoint baru (mis. `Admin_Srp2::lihat_dokumen($id, $document_key)`) yang: cek role admin (sudah dijamin `Admin_Controller`), ambil `stored_name` dari `srp2_documents WHERE registration_id = ?`, `readfile()` dari `private_uploads/srp2/{id}/`, set `Content-Type` dari `mime_type` tersimpan.
- `Admin_Srp2::terima($id)` (POST) - set `status_verifikasi = 'Diterima'`, `catatan_admin = NULL`, **dan** upsert ke `srp2_certified_developers` (insert kalau `certified_developer_id` masih NULL, isi datanya dari `srp2_registrations`; update kalau sudah pernah ditolak lalu diterima ulang) + simpan `certified_developer_id` baliknya ke `srp2_registrations`.
- `Admin_Srp2::tolak($id)` (POST) - wajib `catatan_admin` diisi (validasi server, jangan biarkan kosong - pengembang butuh tahu apa yang harus diperbaiki), set `status_verifikasi = 'Ditolak'`.
- Setelah `Ditolak`, status lock di `Pengembang::simpan_dokumen()`/`kirim_pengajuan()` (yang sudah ada - lihat kode existing `in_array($status, ['Pending','Diterima'])`) otomatis membuka lagi karena `Ditolak` tidak ada di daftar itu - **tidak perlu kode tambahan di sisi pengembang**, sudah didesain begitu sejak sesi sebelumnya.

**Kriteria penerimaan Fase 1:**
1. Admin bisa melihat daftar Pending, membuka tiap dokumen tanpa URL publik yang bisa ditebak.
2. Approve otomatis membuat/mengaitkan baris `srp2_certified_developers` - tidak ada input manual ganda.
3. Reject tanpa catatan ditolak oleh server (bukan cuma validasi HTML `required`).
4. Pengembang yang statusnya baru berubah bisa langsung unggah ulang dokumen kalau `Ditolak` (verifikasi manual: cek `readOnly` getter di `syarat.php` sudah menganggap `Ditolak` sebagai editable - ini sudah benar dari kode existing, tinggal dipastikan tidak regresi).

### Fase 2 - Satukan pembuatan draft SRP2 (menutup Temuan #2)

- Ekstrak logika "cari draft user, kalau tidak ada bikin baru" (saat ini terduplikasi di `Auth::do_login()` AJAX branch, `Auth::do_register()`, dan `Pengembang::syarat()`) jadi satu method, mis. `Auth_model::ensure_srp2_draft($user_id)`.
- Panggil method itu juga di `Auth::save_onboarding()` setelah `role === 'pengembang'` berhasil disimpan - supaya jalur onboarding umum ikut membuat draft, konsisten dengan jalur wizard cepat.
- **Kriteria penerimaan:** user yang jadi `pengembang` lewat `Auth/onboarding` langsung melihat item SRP2 di `/akun` tanpa harus mampir ke `Pengembang/syarat` dulu.

### Fase 3 - Pindahkan upload KTP/SIUP onboarding ke direktori privat (menutup Temuan #3)

- Ubah `Auth::_handle_uploads()`: `$upload_path` dari `FCPATH . 'uploads/documents/...'` menjadi `dirname(FCPATH) . '/private_uploads/onboarding/' . $user_id . '/'` - pola sama persis dengan `Pengembang::simpan_dokumen()`.
- Tambah endpoint terautentikasi untuk menampilkan ulang dokumen ini kalau memang ada UI yang membutuhkannya (cek dulu apakah `usr_documents` pernah ditampilkan ke user mana pun saat ini - kalau tidak ada UI yang baca `get_user_documents()`, cukup pindah lokasi simpan saja tanpa bikin endpoint baru).
- Berlaku untuk `pengembang`, `vendor`, `mahasiswa` sekaligus (satu method yang sama) - tidak perlu dipisah per role.

### Fase 4 - Data integrity cleanup (menutup Temuan #5)

- Migrasi baru: tambah `FOREIGN KEY (registration_id) REFERENCES srp2_registrations(id) ON DELETE CASCADE` di `srp2_documents`.
- Audit `User_model::delete_user_account()` - pastikan menghapus `srp2_registrations` + file fisik `private_uploads/srp2/{id}/` milik user yang dihapus akunnya (kalau belum, tambahkan).

---

## Kebutuhan Fungsional (tambahan di luar yang sudah ada)

- FR-08: hanya `role = 'admin'` yang bisa mengakses `Admin_Srp2::pending()/detail()/terima()/tolak()/lihat_dokumen()` (sudah otomatis lewat `Admin_Controller`, dicatat di sini supaya eksplisit sebagai kontrak).
- FR-09: `tolak()` menolak request tanpa `catatan_admin` non-kosong (validasi server).
- FR-10: `terima()`/`tolak()` idempotent terhadap status yang sudah final - kalau pengajuan sudah `Diterima`, `terima()` lagi tidak boleh dobel-insert ke `srp2_certified_developers` (cek `certified_developer_id` dulu).
- FR-11: dokumen SRP2 hanya bisa dibuka lewat endpoint admin yang memverifikasi role, tidak pernah lewat path/URL yang bisa diakses tanpa autentikasi.

## Keamanan dan Batasan

- Semua aksi admin tetap POST-only untuk perubahan status (konsisten dengan pola `Admin_Srp2::save()/delete()` yang sudah ada).
- Endpoint lihat dokumen **wajib** cek role admin di setiap request (bukan cuma di constructor kalau ada method lain yang bisa dipanggil tanpa lewat constructor - tapi karena `Admin_Controller::__construct()` sudah redirect kalau bukan admin, ini otomatis aman selama method baru ditaruh di class yang extend `Admin_Controller`).
- Tidak mengubah validasi upload yang sudah ada (whitelist ekstensi, cek MIME asli, cap ukuran) - hanya menambah cara *membaca* file yang sudah tersimpan.
- `catatan_admin` ditampilkan apa adanya ke pengembang (sudah begitu di `syarat.php` step 2 kartu "Ditolak") - pastikan input admin di-escape saat render (cek `htmlspecialchars` di view, bukan tanggung jawab controller).

## Kriteria Penerimaan Keseluruhan

1. Pengajuan `Pending` bisa diputuskan admin tanpa `UPDATE` manual ke database.
2. Direktori publik pengembang tersertifikasi dan data pengajuan tidak lagi dua sumber kebenaran terpisah untuk pengajuan baru.
3. Jalur onboarding umum dan wizard cepat sama-sama langsung punya draft SRP2.
4. Tidak ada dokumen sensitif (KTP, SIUP, dokumen SRP2) yang tersimpan di direktori yang bisa diakses HTTP langsung.

---

## Prinsip Umum untuk Role Baru (dari diskusi audit, berlaku ke depan)

Sebelum menambah role baru yang menghasilkan data (pengajuan, unggahan, permintaan apa pun), jawab dulu:

1. **Apakah data ini perlu status/keputusan dari pihak lain?** Kalau ya, sisi admin/reviewer WAJIB dirancang di PRD yang sama - jangan dijadwalkan "nanti", karena riwayat proyek ini menunjukkan "nanti" itu tidak pernah datang sampai diaudit ulang (persis kasus SRP2 ini).
2. **Siapa yang berwenang meninjau?** Superadmin (`admin`), atau role admin ter-scope (`admin_kabkota`/`admin_bidang`) kalau datanya perlu dibatasi wilayah/bidang - tentukan dari awal, karena base controller-nya beda (`Admin_Controller` vs `Admin_Kabkota_Controller`/`Admin_Bidang_Controller`).
3. **Di mana file/dokumen disimpan?** Selalu `private_uploads/{fitur}/{id}/` di luar webroot, disajikan lewat endpoint terautentikasi - tidak ada pengecualian, termasuk untuk role yang "kelihatannya sepele".
4. **Apakah ada dua jalur yang bisa MEMBUAT role/data yang sama, ATAU dua jalur yang bisa MENULIS data yang sama?** Dua pertanyaan berbeda, keduanya wajib dicek - roadmap T2/T3 pengembang menjawab yang pertama dengan benar (`ensure_srp2_draft()` satu fungsi dipanggil dari semua jalur pembuatan) tapi TIDAK menyadari yang kedua sampai T3: jalur AJAX wizard dan jalur non-AJAX `dokumen.php` sama-sama bisa MENULIS baris dokumen yang sama, dan itu akar dari tiga temuan sekaligus (id salah-normalisasi, penulisan DB gagal-sebagian, berkas lama tidak ter-`unlink()`). Kalau ya untuk salah satu, pastikan logikanya satu fungsi yang dipanggil dari semua jalur - atau, kalau salah satu jalur ternyata sudah tidak dipakai, hapus jalur itu (lebih sedikit kode daripada memperbaiki dua jalur yang sama).
5. **Apakah tabel baru perlu FK ke tabel induk?** Kalau ada relasi "milik satu pengajuan/akun", tambahkan FK constraint (`ON DELETE CASCADE` kalau memang harus ikut terhapus) sejak migrasi pertama - jangan ditunda sampai audit menemukan orphan row.

Checklist ini bisa disalin ke PRD role baru sebagai bagian "Relasi Admin" wajib diisi sebelum implementasi dimulai.
