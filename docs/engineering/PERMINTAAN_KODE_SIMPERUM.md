# Permintaan Daftar Kode SIMPERUM

**Tanggal:** 25 Agustus 2026
**Status endpoint:** `GetDataRTLH?NIK=` sudah DIPERBAIKI. Sebelumnya 114 detik dan
kosong, sekarang di bawah 1 detik dan mengembalikan 1 baris yang NIK-nya cocok.
Diuji dengan 3 NIK, semuanya berhasil.

**Status integrasi kami:** sejak 31 Agustus 2026 aplikasi Klinik PKP sudah
berjalan pada mode `api` di production dan memanggil SIMPERUM secara langsung
per orang, satu NIK per pencarian. Daftar di bawah adalah yang MASIH kami
butuhkan; tidak ada yang memblokir peluncuran, tetapi setiap butir yang belum
dijawab membuat medan terkait tampil kosong kepada warga.

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

---

# Daftar yang Masih Kami Butuhkan dari SIMPERUM

Delapan butir, dan tidak satu pun soal hak tulis: lihat bagian C.

Diurut dari yang paling berdampak.

## A. Wajib, supaya data tidak tampil kosong

| No | Yang diminta | Kenapa | Dampak sekarang |
|---|---|---|---|
| 1 | Daftar kode **`SumberAir`** 7 sampai 12 | Dokumen hanya memuat 1-6 | 371 rekaman, dan kode 8 serta 9 justru dua nilai terbanyak. Sumber air tampil kosong |
| 2 | Daftar kode **`SumberDanaID`** 6, 8, 10, 15 | Dokumen memuat 1,2,3,4,5,7,9 | 177 rekaman. Sumber dana bantuan tampil kosong |
| 3 | Arti nilai **`AdaPondasi`** 0 sampai 4 | Namanya menyiratkan ada/tidak ada, datanya berisi 0-4 seperti skala kondisi | 40 rekaman. Kondisi pondasi tidak terbaca |
| 4 | Arti **`RumahLain`** nilai 4 | Didokumentasikan hanya 0/1 | 23 rekaman |
| 5 | Arti **`Pekerjaan`** kode 23 dan **`KepemilikanLahan`** kode 0 | Di luar katalog | 2 rekaman |

Selama belum dijawab, nilai yang tidak dikenal kami jadikan `null` dan kami
catat, tidak pernah ditebak. Menebak arti kode akan mengubah hasil kelayakan
warga tanpa ada yang menyadarinya.

## B. Kelengkapan balasan

| No | Yang diminta | Keterangan |
|---|---|---|
| 6 | Cara mengambil **`KondisiAtap`, `KondisiLantai`, `KondisiDinding`** | Ketiganya ada di payload `SaveDataRTLH` tetapi tidak pernah muncul di balasan `GetDataRTLH`, sehingga kondisi atap, lantai, dan dinding kosong pada 100 persen rekaman |
| 7 | Sumber **desil / kelompok kesejahteraan** | Tidak disediakan API. Kami tidak menampilkan desil dari SIMPERUM karena memang tidak ada, bukan karena gagal ambil |
| 8 | **`TahunLahir`** | Kosong pada rekaman yang kami uji |

## C. Penulisan balik: TIDAK diminta, keputusan kami sendiri

`SaveDataRTLH` membalas `Tidak Memiliki Akses` (ErrCode 401), dan **kami tidak
meminta hak tulis itu dibuka.** Keputusan user 31 Agustus 2026: hasil penarikan
dari SIMPERUM disimpan di basis data Klinik PKP sendiri, per orang, seiring
sistem berjalan. Tidak ada data yang kami kirim balik.

Bentuk penyimpanannya: satu baris terenkripsi per NIK di tabel snapshot kami,
dikunci sidik NIK (bukan NIK polos), berlaku 30 hari lalu ditarik ulang bila
dibutuhkan lagi. Jadi satu warga menghasilkan satu panggilan, bukan penarikan
massal per wilayah.

Butir ini ditulis di sini hanya supaya jelas bahwa 401 tersebut **bukan
masalah yang menunggu penyelesaian**, dan tidak perlu ditindaklanjuti siapa pun.

## D. Mutu data, sekadar laporan

| No | Temuan | Angka |
|---|---|---|
| 9 | NIK bukan 16 digit di basis data | 56 dari 2.606 rekaman pada satu desa |
| 10 | Pencarian NIK berpadding nol mengembalikan baris yang salah | `NIK=0000000000000001` membalas baris yang kolom NIK-nya hanya 1 karakter. Kemungkinan perbandingan masih numerik. Kami menahannya di sisi kami, tetapi mohon diperiksa |
| 11 | Sebagian besar medan kosong | Di desa sampel, 92 persen rekaman kosong untuk pekerjaan, penghasilan, pendidikan, jendela, dan ventilasi |

Butir 9 dan 10 tidak menghalangi kami. Butir 11 bukan cacat API, hanya perlu
diketahui bersama: pengisian manual oleh warga tetap menjadi jalur utama, bukan
cadangan.
