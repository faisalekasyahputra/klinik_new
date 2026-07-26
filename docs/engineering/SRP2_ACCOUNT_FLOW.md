# SRP2 Account Flow — Catatan Teknis

## Data dan endpoint

- `srp2_registrations`: data profil pengajuan dan status (`Draft`, `Pending`, `Diterima`, `Ditolak`).
- `srp2_documents`: metadata 14 berkas; file fisik disimpan di `private_uploads/srp2/{registration_id}`.
- `usr_users`: menyimpan peran akun dan data perusahaan onboarding.
Sisi pemohon:
- `Pengembang/syarat`: **jalur resmi** — wizard satu halaman (syarat → masuk/daftar → unggah → kirim). Draft dibuat lewat `Auth_model::ensure_srp2_draft()`, satu-satunya tempat draft dibuat.
- `Pengembang/dokumen/{id}`: unggah berkas milik sesi sendiri (fallback non-wizard untuk deep-link lama).
- `Pengembang/kirim_pengajuan/{id}`: POST-only, menghitung 14 dokumen dan mengubah draft menjadi `Pending`.
- ~~`Pengembang/formulir`~~: **diarsipkan**, sekarang cuma redirect ke `Pengembang/syarat`.

Sisi admin (ada sejak 26 Jul 2026 — sebelumnya tidak ada sama sekali):
- `Admin_Srp2/pending`: daftar pengajuan menunggu keputusan.
- `Admin_Srp2/detail/{id}`: detail + status 14 dokumen.
- `Admin_Srp2/proses/{id}`: POST-only, `status=Diterima|Ditolak`. Terima → upsert ke `srp2_certified_developers` lewat `certified_developer_id` (idempotent). Tolak → `catatan_admin` **wajib**, divalidasi server.
- `Admin_Srp2/lihat_dokumen/{id}/{document_key}`: sajikan berkas, ber-guard `Admin_Controller`.

## Migrasi

Jalankan `docs/engineering/migration_srp2_account_flow.sql` setelah migrasi SRP2 sebelumnya. Migrasi ini menambah kolom profil perusahaan pada `usr_users` dan menjadikan tiga kolom upload lama SRP2 nullable, karena unggah dipindah ke `srp2_documents`.

## Invarian keamanan

- Jangan menerima `user_id` dari request.
- Jangan menyajikan file SRP2 dari direktori publik. **Diperbarui 26 Jul 2026:** asumsi "`private_uploads/` otomatis di luar webroot" **TIDAK BENAR** di semua tata letak — di XAMPP lokal `dirname(FCPATH)` justru sama dengan DocumentRoot, dan dokumen SRP2 terbukti bisa diunduh tanpa login. Mitigasi: `.htaccess` penolak akses ditulis otomatis oleh `MY_Controller::ensure_private_uploads_protected()`. Production & staging sudah dicek aman (404 CI). Jangan pernah menyimpulkan aman dari nama direktori — **tes dengan mengaksesnya**.
- Jangan mengubah status menjadi `Pending` selain melalui pemeriksaan kelengkapan server-side.
- Jangan melewati pemeriksaan role `pengembang` pada endpoint SRP2 baru.
- Dokumen **dikunci** saat status `Pending`/`Diterima` (409 di `simpan_dokumen()`/`kirim_pengajuan()`), dan terbuka lagi saat `Ditolak` supaya pemohon bisa memperbaiki. Penegakannya di server, bukan hanya menyembunyikan tombol.
- Setiap keputusan admin wajib mencatat `reviewed_by` + `reviewed_at` dari sesi.

## Batas ukuran unggahan

**2 MB per dokumen** (PDF/JPG/PNG), divalidasi server: whitelist ekstensi + cek MIME asli via `finfo` + nama file acak. Semula 1 MB, dinaikkan karena dokumen hasil pindaian nyata umumnya 1–5 MB dan penolakannya tidak terlihat user (pesan galat cuma ada di atribut `title`). Kalau menemukan dokumen lain yang masih menyebut 1 MB, itu usang.
