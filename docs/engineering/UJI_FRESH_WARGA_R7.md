# Uji DB Fresh Warga R7

Jalankan hanya di lokal:

```powershell
C:\xampp\php\php.exe docs\engineering\uji_warga_fresh_r7.php
```

Runner membuat database sementara bernama
`klinikpkp_uji_warga_r7_<timestamp>_<acak>`, mengimpor
`schema_klinikpkp.sql`, mengalihkan `DB_NAME` lokal selama pengujian, lalu
menjalankan:

1. migrasi baseline sampai `20260701000020` dan pemeriksaan status;
2. check R1 `18/18`;
3. gateway R2 `5/5`;
4. wizard R3 `16/16`;
5. evidence R4 `44/44`;
6. ruleset R5 `59/59`;
7. submit/revisi/admin R6 `58/58`;
8. perjalanan antrean legacy `19/19`;
9. keamanan/rate limit R7 `5/5`.

Hasil final 28 Juli 2026: **224/224 hijau**, exit `0`. Runner memulihkan byte `.env`
asli dan menghapus database sementara, termasuk melalui shutdown cleanup bila
check berhenti. Sesudah run, tidak ada database berawalan
`klinikpkp_uji_warga_r7_`, DB lokal kembali tercatat pada migrasi
`20260701000020`, dan `/Auth/login` merespons HTTP 200.

Run pertama menemukan migrasi 19 memakai cache daftar tabel CodeIgniter.
Akibatnya migrasi 1→20 dalam satu proses melewatkan rename tabel dan migrasi 20
gagal membuat FK. Migrasi 19 kini memeriksa `information_schema` langsung;
run fresh berikutnya hijau.
