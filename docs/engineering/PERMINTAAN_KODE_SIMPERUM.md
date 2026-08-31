# Permintaan Daftar Kode SIMPERUM

**Tanggal:** 25 Agustus 2026
**Status endpoint:** `GetDataRTLH?NIK=` sudah DIPERBAIKI. Sebelumnya 114 detik dan
kosong, sekarang di bawah 1 detik dan mengembalikan 1 baris yang NIK-nya cocok.
Diuji dengan 3 NIK, semuanya berhasil.

## Yang tersisa

Integrasi sudah bisa berjalan, tetapi ada nilai kode yang muncul di data nyata
dan tidak ada di dokumentasi `SIMPERUM API.pdf`, sehingga kami tidak dapat
menerjemahkannya. Nilai yang tidak dikenal kami jadikan `null` dan dicatat,
tidak pernah ditebak.

Diukur dari 2.606 rekaman `GetDataRTLH?KodeDagri=3305012007`:

| Medan | Kode di dokumen | Kode yang muncul di data | Jumlah rekaman |
|---|---|---|---|
| `SumberAir` | 1 sampai 6 | **7, 8, 9, 10, 11, 12** | 371 |
| `SumberDanaID` | 1,2,3,4,5,7,9 | **6, 8, 10, 15** | 177 |
| `AdaPondasi` | 0, 1 | **2, 3, 4** | 40 |
| `RumahLain` | 0, 1 | **4** | 23 |
| `Pekerjaan` | 1 sampai 22, 98, 99 | **23** | 1 |
| `KepemilikanLahan` | 1 sampai 4 | **0** | 1 |

`SumberAir` yang paling berdampak: kode 8 dan 9 adalah dua nilai terbanyak
(184 dan 174 rekaman), keduanya di luar dokumen.

Catatan `AdaPondasi`: namanya menyiratkan ada/tidak ada, tetapi datanya berisi
0 sampai 4. Medan sebelahnya (`KondisiKolom`, `KondisiBalok`, `KondisiRangka`)
memakai skala 1 sampai 4. Mohon konfirmasi apakah `AdaPondasi` juga skala
kondisi, dan apa arti nilai 0.

## Tiga medan yang tidak dikirim GetDataRTLH

`KondisiAtap`, `KondisiLantai`, dan `KondisiDinding` ada di payload
`SaveDataRTLH` tetapi tidak pernah muncul di balasan `GetDataRTLH`. Akibatnya
kondisi atap, lantai, dan dinding kosong untuk 100 persen rekaman. Mohon
konfirmasi apakah memang tidak disediakan, atau ada parameter lain untuk
mengambilnya.

## Catatan lain

- `TahunLahir` kosong pada rekaman yang kami uji.
- 56 dari 2.606 rekaman memiliki NIK yang bukan 16 digit.
- `SaveDataRTLH` menjawab `Tidak Memiliki Akses` (ErrCode 401). Mohon konfirmasi
  apakah hak tulis memang belum dibuka untuk kredensial kami.
