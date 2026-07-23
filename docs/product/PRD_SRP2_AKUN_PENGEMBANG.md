# PRD — Pendaftaran SRP2 Berbasis Akun Pengembang

## Tujuan

Memberikan proses SRP2 yang aman, dapat dilanjutkan, dan dapat dikelola oleh perusahaan pengembang tanpa mengizinkan unggah dokumen anonim.

## Pengguna

- Calon pengembang: membuat akun, melengkapi data, mengunggah dokumen, dan mengirim pengajuan.
- Pengembang terdaftar: melihat status serta memperbarui data perusahaan.
- Admin SRP2: memverifikasi pengajuan dan mengelola daftar pengembang tersertifikasi.

## Alur Produk

`Syarat → Login/Daftar akun pengembang → Onboarding profil → Profil pengajuan SRP2 → Upload 14 dokumen → Kirim pengajuan → Status & kelola data`

1. Halaman syarat menjelaskan dokumen dan menyediakan template resmi.
   Calon pengembang dapat membuat akun singkat langsung dari halaman ini dengan email, kata sandi, dan nama perusahaan.
2. Tombol daftar meminta login. Akun baru menyelesaikan verifikasi email dan onboarding dengan peran `pengembang`.
3. Sistem mengembalikan pengguna ke formulir SRP2 yang menyimpan data sebagai `Draft`.
4. Pengguna mengunggah dokumen satu per satu, maksimum 1 MB, PDF/JPG/PNG.
5. Sistem hanya mengizinkan pengajuan dikirim jika 14 dokumen tersedia; status berubah dari `Draft` menjadi `Pending`.
6. Admin menentukan `Diterima` atau `Ditolak`; pengembang mengelola data melalui halaman akun.

## Kebutuhan Fungsional

- FR-01: akses formulir, upload, hasil, dan kirim pengajuan hanya untuk akun berperan `pengembang`.
- FR-02: redirect setelah login/onboarding kembali ke tujuan SRP2 yang diminta.
- FR-02A: pendaftaran cepat melalui SRP2 menampilkan simulasi verifikasi di halaman SRP2 sendiri, sedangkan pendaftaran umum tetap memakai halaman verifikasi akun umum.
- FR-03: data profil SRP2 disimpan sebagai draft sebelum upload.
- FR-04: setiap dokumen diunggah terpisah dan dapat diganti.
- FR-05: sistem menolak file di atas 1 MB atau MIME/ekstensi di luar PDF, JPG, JPEG, PNG.
- FR-06: pengajuan hanya dapat dikirim saat 14 dokumen lengkap.
- FR-07: dashboard akun menampilkan status pengajuan dan perubahan data milik akun sendiri.

## Status Pengajuan

| Status | Makna | Aksi pengguna |
|---|---|---|
| Draft | Profil tersimpan, dokumen belum dikirim | Lengkapi dan unggah dokumen |
| Pending | Dikirim ke admin | Menunggu verifikasi |
| Diterima | Lolos verifikasi | Kelola profil dan lihat sertifikat saat tersedia |
| Ditolak | Perlu perbaikan | Baca catatan admin lalu perbarui data/dokumen |

## Keamanan dan Batasan

- Tidak ada unggah anonim.
- Kepemilikan pengajuan selalu ditentukan dari `user_id` sesi.
- Berkas disimpan di luar webroot pada direktori privat.
- Validasi ukuran, ekstensi, dan MIME dilakukan server-side.
- CSRF CodeIgniter tetap berlaku untuk seluruh POST.

## Kriteria Penerimaan

1. Pengunjung belum login yang membuka formulir diarahkan login, lalu kembali setelah onboarding.
2. Akun non-pengembang tidak dapat membuka formulir maupun dokumen SRP2.
3. Formulir tidak lagi meminta unggah empat dokumen lama.
4. Pengajuan draft membuka halaman unggah 14 dokumen.
5. Tombol kirim ditolak di server bila dokumen belum lengkap.
