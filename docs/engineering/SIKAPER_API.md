# API Sikaper Jateng - hasil eksplorasi 1 Sep 2026

Sumber: koleksi Postman publik dari dinas
(https://documenter.getpostman.com/view/5692341/2sBXwqqAYk, terbit 6 Jun 2026)
dan uji langsung ke server produksi. Web: https://sikaper.disperakim.jatengprov.go.id

## Endpoint (5 buah, semua Basic Auth)

Base URL: `https://sikaper.disperakim.jatengprov.go.id/api/v2/`

| Method | Path | Body (urlencoded) | Isi |
|---|---|---|---|
| GET | `hari_habitat/info` | - | Informasi Lomba Habitat |
| POST | `hari_habitat/jadwal` | `id_lomba` (base64, contoh `MTAxNDI3Nw`) | Timeline/jadwal lomba |
| POST | `data_kawasan/kawasan` | `tahun` (contoh `2024`) | Data kawasan kumuh per tahun |
| POST | `data_kawasan/detail_kawasan` | `id_kawasan` (base64, contoh `MzU0NjcyNzYx`) | Detail satu kawasan |
| POST | `data_kawasan/data_rtrw` | `id_kawasan` (base64) | Data RT/RW per kawasan |

Catatan: nilai contoh `id_lomba`/`id_kawasan` adalah angka yang di-base64-kan
(`MTAxNDI3Nw` = 1014277, `MzU0NjcyNzYx` = 354672761). ID valid kemungkinan
didapat dari respons `kawasan`/`info` dulu.

## Isi menu Kawasan Kumuh di situs publik (dipetakan 1 Sep 2026)

Situs publiknya sendiri TIDAK butuh login, dan strukturnya persis cermin 3
endpoint `data_kawasan/*` di API:

1. **Data Kawasan** (`/kawasan/kabupaten`) - rekap 35 kab/kota per tahun
   (2020-2026): nomor SK, luas kumuh awal/akhir (Ha), jumlah lokasi awal/akhir.
   Data dimuat AJAX POST ke `/kawasan/table_kabupaten` (format DataTables;
   TANPA field `search` servernya lempar warning PHP tapi JSON tetap keluar
   setelah blok error). Kolom rekap provinsi kosong semua di semua tahun -
   datanya baru terisi di level detail.
2. **Detail kabupaten** (`/kawasan/detail/<hash>?tahun=`) - daftar kawasan per
   kab: lokasi, kecamatan, jumlah RT-RW, luas kumuh awal/akhir, skor kumuh
   awal/akhir, status (TIDAK KUMUH / KUMUH RINGAN / dst), aspek utama.
   = endpoint API `data_kawasan/kawasan`.
3. **Detail kawasan** (`/detail_kawasan/form/<hash>`) - skor 7 aspek kumuh
   (bangunan gedung, jalan lingkungan, air minum, drainase, air limbah,
   persampahan, proteksi kebakaran) dengan kriteria/nilai/satuan/volume/persen,
   kumuh awal vs akhir. = endpoint API `data_kawasan/detail_kawasan`.
4. **Detail wilayah RT/RW** (`/kawasan/detail_wilayah/<hash>`)
   = endpoint API `data_kawasan/data_rtrw`.
5. **Rekap Penanganan** (`/kawasan/penanganan`) - SATU halaman server-rendered
   6,8 MB berisi ~6.900 baris kegiatan: tahun, kawasan, infrastruktur,
   kriteria, kegiatan, volume & satuan, anggaran + sumber dana. Tidak ada
   padanan endpoint API-nya.

ID di URL situs publik berupa hash 36-40 hex, sedangkan contoh di Postman
berupa angka base64 (`MzU0NjcyNzYx` = 354672761) - dua skema ID berbeda, jadi
ID untuk API harus diambil dari respons API sendiri, bukan dari URL situs.

## Status autentikasi: BELUM TEMBUS (1 Sep 2026)

Kredensial dari dinas via WA (username `sikaper`, password disimpan di luar
repo) ditolak `401 {"status":false,"code":401,"message":"Unauthorized. Valid
credentials required."}` pada SEMUA variasi yang dicoba:

- HTTP Basic Auth (curl `-u` dan header `Authorization: Basic` eksplisit)
- Kredensial sebagai form field (`username`/`password` di body)
- Kredensial sebagai header kustom dan query string

Pesan 401-nya IDENTIK dengan dan tanpa auth sama sekali, jadi belum bisa
dibedakan apakah (a) kredensialnya salah/nonaktif, (b) server butuh whitelist
IP, atau (c) header Authorization di-drop oleh server mereka. Respons cepat
(~1,5 dtk) dan berformat JSON CI REST, jadi endpoint-nya sendiri hidup -
berbeda dengan kasus endpoint NIK SIMPERUM yang timeout 105 dtk.

Tindak lanjut: konfirmasi ke kontak dinas apakah kredensial masih aktif dan
apakah ada whitelist IP, idealnya minta mereka contohkan satu curl yang
berhasil.
