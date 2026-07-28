# Audit Keamanan Warga R7

**Tanggal verifikasi:** 28 Juli 2026  
**Lingkungan:** Apache XAMPP lokal, DB `klinikpkp` skema 20  
**Status:** hijau untuk batas minimum R7; belum di-push/production

## Hasil

- CSRF global dibuktikan lewat POST lookup yang valid secara bentuk tetapi tanpa
  token: HTTP 403 dan tidak ada profil/assessment yang berubah.
- `Warga` dan `Admin_Kabkota` tidak termasuk daftar pengecualian CSRF. Pencarian
  statis juga tidak menemukan `log_message()` yang menulis NIK, tanggal lahir,
  alamat, payload, maupun ciphertext.
- Anti-IDOR warga/admin, scope kabupaten, bukti privat, immutable submission,
  serta jalur revisi sudah ditempuh oleh `uji_pendataan_warga_r6.php`: **58/58**.
- Semua pemakai `sys_rate_limits` sekarang memakai satu registry
  `application/config/rate_limits.php` dan satu library
  `application/libraries/Rate_limiter.php`.
- Lookup warga memakai tiga penghitung independen: IP, akun, dan NIK. NIK
  di-HMAC SHA-256 dengan `KPKP_DATA_PEPPER` sebelum ikut membentuk `limit_key`;
  nilai mentah tidak ditulis ke tabel pembatas laju.
- Submit assessment, mulai revisi, dan keputusan admin kab/kota dibatasi menurut
  IP + akun + objek. Scope policy selalu ikut membentuk kunci sehingga penghitung
  registrasi tidak dapat memblokir lookup warga.
- Percobaan yang melewati ambang mendapat HTTP 429 dan `Retry-After`. Kegagalan
  policy, dimensi, atau penyimpanan bersifat fail-closed dengan HTTP 503.
- Fixed window yang kedaluwarsa di-reset, tidak diperpanjang oleh percobaan lama.

## Policy aktif

| Policy | Ambang | Dimensi |
|---|---:|---|
| `register` | 5 / 10 menit | IP |
| `simperum_lookup` | 10 / menit | IP |
| `housing_submit` | 5 / jam | IP |
| `ticket_lookup` | 5 kegagalan / menit | IP |
| `warga_lookup` | 10 / menit | IP, akun, HMAC NIK |
| `warga_submit` | 5 / jam | IP, akun, assessment |
| `warga_start_revision` | 5 / jam | IP, akun, antrean |
| `admin_queue_decision` | 30 / menit | IP, akun, antrean |

## Bukti yang dijalankan

`php docs/engineering/uji_keamanan_warga_r7.php`

```text
OK    CSRF ditolak 403 tanpa side effect
OK    Penghitung scope register tidak memblokir lookup warga
OK    Lookup ke-11 diblokir 429 dengan Retry-After
OK    Registry mencatat dimensi IP, akun, dan hash NIK tanpa PII mentah
OK    Jendela kedaluwarsa reset dan lookup dapat dilanjutkan
RINGKASAN: 5 pemeriksaan, 0 gagal
```

Regresi perjalanan R6 juga dijalankan sesudah perubahan: **58/58**, exit 0.
Perjalanan warga lama yang ikut memakai registry baru: **19/19**, exit 0.
Lint PHP terhadap sembilan berkas R7: **9/9**, tanpa error sintaks.

Harness R7 dan R6 menyimpan lalu memulihkan nilai asli setiap `limit_key` exact
yang disentuh. Urutan R7 **5/5** lalu R6 **58/58** dijalankan pada DB yang sudah
memiliki counter aktif; dump `sys_rate_limits` sebelum dan sesudah identik.
`uji_warga_fresh_r7.php` juga menjalankan check keamanan ini sebagai langkah
terakhir agar rangkaian fresh memverifikasi limiter tanpa mencemari check lain.

## Batas yang disengaja

- Pembatas forum di `application/helpers/forum_helper.php` masih memakai
  mekanisme/tabel domainnya sendiri. Forum tidak dipindahkan pada R7 karena
  berada di luar perjalanan Warga dan perubahan itu perlu audit tersendiri.
- Save langkah dan upload bukti tetap dilindungi sesi, CSRF, ownership, validasi
  state, validasi berkas, serta penyimpanan privat, tetapi tidak mendapat policy
  laju khusus pada batas minimum R7 ini.
- Baris `sys_rate_limits` lama tidak mengubah perilaku karena fixed window
  kedaluwarsa otomatis diabaikan/reset. Pembersihan fisik baris lama adalah
  pekerjaan housekeeping terpisah, bukan syarat kebenaran limiter.
