# SRP2 Account Flow — Catatan Teknis

## Data dan endpoint

- `srp2_registrations`: data profil pengajuan dan status (`Draft`, `Pending`, `Diterima`, `Ditolak`).
- `srp2_documents`: metadata 14 berkas; file fisik disimpan di `private_uploads/srp2/{registration_id}`.
- `usr_users`: menyimpan peran akun dan data perusahaan onboarding.
- `Pengembang/formulir`: membuat draft.
- `Pengembang/dokumen/{id}`: unggah berkas milik sesi sendiri.
- `Pengembang/kirim_pengajuan/{id}`: POST-only, menghitung 14 dokumen dan mengubah draft menjadi pending.

## Migrasi

Jalankan `docs/engineering/migration_srp2_account_flow.sql` setelah migrasi SRP2 sebelumnya. Migrasi ini menambah kolom profil perusahaan pada `usr_users` dan menjadikan tiga kolom upload lama SRP2 nullable, karena unggah dipindah ke `srp2_documents`.

## Invarian keamanan

- Jangan menerima `user_id` dari request.
- Jangan menyajikan file SRP2 dari direktori publik.
- Jangan mengubah status menjadi `Pending` selain melalui pemeriksaan kelengkapan server-side.
- Jangan melewati pemeriksaan role `pengembang` pada endpoint SRP2 baru.
