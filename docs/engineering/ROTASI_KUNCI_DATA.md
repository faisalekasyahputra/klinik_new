# Rotasi kunci data pribadi

`KPKP_DATA_KEY` tetap diperlukan untuk membaca ciphertext `v1` lama. Jangan menggantinya langsung: data lama akan tidak terbaca. `KPKP_DATA_PEPPER` adalah rahasia terpisah untuk hash pencarian dan tidak ikut rotasi kunci enkripsi ini.

1. Cadangkan basis data dan `.env` secara aman. Pastikan cadangan dapat dipulihkan. Jangan masukkan rahasia ke Git atau log.
2. Buat kunci acak 32 byte yang baru di lingkungan aman. Tambahkan ke `KPKP_DATA_KEYS` sebagai JSON satu baris, misalnya ID `2026a`. Isi `KPKP_ACTIVE_KEY_ID=2026a`. Pertahankan `KPKP_DATA_KEY` lama.
3. Uji baca ciphertext `v1` dan tulis-baca ciphertext `v2` di lingkungan terisolasi. Tulisan baru memakai ID aktif; nilai lama tetap memakai kunci lama.
4. Untuk rotasi berikutnya, tambahkan ID baru ke keyring dan ubah ID aktif. Jangan hapus ID lama sebelum seluruh nilai yang bergantung padanya dibungkus ulang melalui `Encryption_lib::reencrypt()` dan disimpan secara atomik.
5. Inventarisasi seluruh kolom terenkripsi dan cadangan sebelum migrasi massal. Verifikasi jumlah baris serta dekripsi sampel sebelum dan sesudah. Jangan menjalankan migrasi di produksi tanpa prosedur dan persetujuan operasional.
6. Kunci lama boleh dipensiunkan hanya setelah tidak ada ciphertext yang memerlukannya di basis data maupun cadangan yang masih harus dipulihkan. Jika cadangan lama tetap disimpan, simpan kuncinya dalam penyimpanan rahasia terpisah selama masa retensi cadangan.

Format `v2` menyimpan ID kunci non-rahasia di dalam envelope yang diautentikasi AES-256-GCM. Kunci harus 64 karakter heksadesimal; ID 1–16 karakter ASCII alfanumerik, garis bawah, atau tanda hubung. Konfigurasi tidak valid, kunci yang tidak tersedia, dan autentikasi ciphertext yang gagal ditolak, bukan ditampilkan sebagai data asli.

Mengaktifkan keyring **tidak** memigrasikan data lama secara otomatis. Dokumen ini menjelaskan siklus kunci, bukan bukti bahwa rotasi basis data produksi sudah dilakukan.
