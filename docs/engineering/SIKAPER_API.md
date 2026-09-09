# API Sikaper Jateng - hasil eksplorasi 1 Sep 2026

Sumber: koleksi Postman publik dari dinas
(https://documenter.getpostman.com/view/5692341/2sBXwqqAYk, terbit 6 Jun 2026)
dan uji langsung ke server produksi. Web: https://sikaper.disperakim.jatengprov.go.id

## Endpoint (5 buah, semua Basic Auth)

Base URL: **`https://egov.phicos.co.id/jateng/sikaper_new/api/v2/`**

> 🔻 **DIKOREKSI 9 Sep 2026.** Dokumen ini semula menulis
> `https://sikaper.disperakim.jatengprov.go.id/api/v2/`. Host itu memang milik
> dinas dan situs publiknya hidup, TAPI API-nya menolak kredensial yang sama
> dengan `401` - diuji ulang 9 Sep, masih 401. Yang melayani API adalah host
> `egov.phicos.co.id`, dan di sana kredensial yang sama membalas 200 dalam
> ~0,3 detik. **401 yang lama bukan soal kredensial, melainkan soal alamat** -
> dan itu mustahil disimpulkan dari pesannya, karena 401-nya identik dengan
> dan tanpa auth.

| Method | Path | Body (urlencoded) | Isi |
|---|---|---|---|
| GET | `hari_habitat/info` | - | Informasi Lomba Habitat |
| POST | `hari_habitat/jadwal` | `id_lomba` (base64, contoh `MTAxNDI3Nw`) | Timeline/jadwal lomba |
| POST | `hari_habitat/detail_peserta` | `id_lomba` (base64) | Daftar peserta per kabupaten. ⚠️ memuat `pic` dan `no_hp` - data kontak orang |
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

## Status autentikasi: TEMBUS (9 Sep 2026)

Kredensial dari dinas (`sikaper` + password di `.env`, kunci `SIKAPER_PASSWORD`)
**berhasil** terhadap host `egov.phicos.co.id`. Keenam endpoint diverifikasi
hidup pada hari yang sama:

| Endpoint | Hasil |
|---|---|
| `hari_habitat/info` | 200, 4,6 KB |
| `hari_habitat/jadwal` | 200 |
| `hari_habitat/detail_peserta` | 200, ~13 KB |
| `data_kawasan/kawasan` | 200, 292 KB, 756 kawasan untuk 2024 |
| `data_kawasan/detail_kawasan` | 200 |
| `data_kawasan/data_rtrw` | 200 |

Cacah kawasan per tahun, dihitung dari API bukan ditebak: 2020=725, 2021=340,
2022=297, 2023=437, 2024=756, 2025=655, 2026=8.

**Yang dulu disangka sebab, dan ketiganya meleset.** Catatan 1 Sep menduga
(a) kredensial mati, (b) whitelist IP, atau (c) header `Authorization`
di-drop server. Tidak satu pun benar; yang salah alamatnya. Pelajaran yang
layak dibawa: kalau 401 sebuah API identik dengan dan tanpa auth, **uji dulu
apakah host-nya memang yang melayani API**, sebelum menghabiskan waktu pada
variasi cara mengirim kredensial.

## TLS dan kredensial

- Verifikasi TLS **dinyalakan penuh** (`VERIFYPEER` + `VERIFYHOST`). Sertifikat
  host-nya sah (Google Trust Services, `CN=phicos.co.id`) dan diuji 200 dengan
  verifikasi aktif dari lokal maupun dari PHP di server production. Utang B4
  lunas.
- Kredensial **tidak lagi ditulis di `config/sikaper.php`**; dibaca dari `.env`.
  🔴 Password lama sudah masuk riwayat git dan tidak bisa ditarik - yang
  dibutuhkan **rotasi di sisi dinas**, memindah ke `.env` saja tidak cukup.
- Penjaganya `docs/engineering/uji_sikaper_api.php` (lapis statis, selalu jalan,
  nol jaringan). Dibuktikan bisa merah: password literal ditulis lagi dan TLS
  dimatikan, empat asersi merah persis di titik itu, hijau lagi setelah
  dipulihkan.

## Bentuk respons yang mudah salah baca

Tiga tingkat pembungkus, dan tiap endpoint berhenti di tingkat berbeda:

- `detail_kawasan` -> `data` berisi **objek kawasan langsung**.
- `data_rtrw` -> `data` berisi **objek** `{kawasan, rtrw}`; daftarnya di
  `data.rtrw`.
- `kawasan` -> `data` berisi **list** kawasan.

Salah satu tingkat saja membuat halaman menampilkan "0 RT/RW" untuk kawasan
yang API-nya jelas mengembalikan dua - salah yang diam, bukan galat.

## Yang sudah dipakai di aplikasi

`Kawasan_kumuh.php` + dua view di `pages/data_spasial/`, rute `/kawasan_kumuh`.
Publik tanpa login (keputusan user 9 Sep) karena isinya statistik wilayah, nol
data pribadi - dan situs dinas sendiri menyajikannya tanpa login.

⚠️ `hari_habitat/detail_peserta` **sengaja tidak dipakai di halaman publik**:
responsnya memuat nama PIC dan nomor HP.
