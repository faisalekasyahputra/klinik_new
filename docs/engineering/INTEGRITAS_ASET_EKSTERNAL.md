# Integritas aset yang diakses dari luar - inventaris, perbaikan, dan penjaga

Menjawab satu butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 13 "Konfigurasi"):

- **13.4** Memvalidasi integritas aset jika aset aplikasi diakses secara eksternal.

Aset eksternal = skrip, stylesheet, atau font yang peramban ambil dari asal lain saat halaman kita dibuka. Bila CDN-nya dibobol atau dibajak, isi berubah tanpa kita tahu dan langsung berjalan di halaman warga. Pembelaannya: **Subresource Integrity (SRI)** - peramban menghitung hash isi yang diterima dan menolaknya bila berbeda dari `integrity="..."` di view - atau tidak memuat dari luar sama sekali.

## 1. Inventaris (21 Sep 2026)

Manifest lengkap: [`aset_eksternal_manifest.json`](aset_eksternal_manifest.json).

| Kelompok | Jumlah | Perlakuan |
|---|---|---|
| Aset CDN (jsDelivr, unpkg, jQuery, cdnjs): Alpine, Chart.js, Swiper, Font Awesome, Phosphor, jQuery, AOS, Leaflet | **13** | Versi dipatok tepat, `integrity` (sha256/384) + `crossorigin="anonymous"` |
| Font Google (Merriweather, Plus Jakarta Sans) | dulu 2 permintaan ke Google (CSS berhash tak stabil, dikecualikan dari SRI) | **Dihosting sendiri**: 6 berkas woff2 di `assets/fonts/` (subset latin dan latin-ext), `assets/css/fonts.css`; nol permintaan ke Google Fonts |
| reCAPTCHA (`google.com/recaptcha/api.js`) | 1 | **Tanpa SRI, diakui**: Google melarang SRI dan mengubah skripnya tanpa pemberitahuan. Kini dimuat **hanya bila site key terisi** (sebelumnya selalu dimuat di halaman masuk/daftar). |

## 2. Yang diperbaiki

| Temuan | Perbaikan |
|---|---|
| Hash SRI di view tak pernah dibuktikan benar; hash yang salah membuat halaman rusak diam-diam, hash dari berkas yang sudah dibobol memberi rasa aman semu | `docs/engineering/verifikasi_aset_eksternal.php` mengunduh **setiap** aset, menghitung ulang hash, dan membandingkannya dengan view dan manifest. Hasil 21 Sep 2026: **13 dari 13 cocok**; 6 dari 6 font lokal identik dengan sumbernya. |
| Google Fonts dikecualikan dari SRI dan memberi Google kebocoran alamat pengunjung | Font dihosting sendiri; pengecualian Google Fonts dihapus dari `config/content_security.php` |
| reCAPTCHA selalu dimuat walau site key kosong | Dimuat bersyarat di lima tempat (login, daftar, modal login, masuk pengembang, syarat pengembang) |
| Perubahan hash tak terlihat di tinjauan | Manifest membuat setiap perubahan hash/versi menjadi keputusan yang terlihat di diff |

## 3. Penjaga

`tests/external_assets_test.php` (offline) menggagalkan bila: ada aset eksternal non-HTTPS; hash di view berbeda dari manifest, hash memakai algoritma lemah, atau manifest usang; `integrity` tanpa `crossorigin`; ada aset eksternal tanpa SRI selain reCAPTCHA (dan yang tidak tercatat di `sri_pengecualian` beserta alasannya); Google Fonts kembali di view, CSS, atau daftar pengecualian; CSS memuat sumber luar lewat `@import`/`url(http...)`; skrip reCAPTCHA tak bersyarat; berkas font lokal berubah dari hash manifest, tidak bertanda tangan woff2, tak tercatat, atau tak dipakai.
**Uji mutasi:** pengecualian Google Fonts kembali, aset CDN tanpa SRI, font lokal diubah satu bita, dan reCAPTCHA tanpa syarat masing-masing menggagalkan tes.

## 4. Prosedur

1. Menambah/menaikkan aset CDN: pakai versi tepat, hitung hash dengan `php docs/engineering/verifikasi_aset_eksternal.php --tulis` **setelah** memasang atributnya, lalu jalankan tanpa argumen (butuh internet) untuk membuktikan hash cocok dengan isi CDN, dan tinjau diff manifest.
2. Sebelum rilis: jalankan verifikasi online; kegagalan berarti isi CDN berubah dari yang kita kunci.
3. Tidak menambah pengecualian SRI baru tanpa alasan tertulis di `sri_pengecualian`.

## 5. Batas yang diakui

- **Verifikasi hash terhadap CDN adalah pemeriksaan sesaat.** Ia membuktikan isi CDN hari ini sama dengan hash; tidak membuktikan isi itu tidak berbahaya sejak awal (kepercayaan awal pada penerbit paket).
- **reCAPTCHA tetap dimuat tanpa SRI** dan berjalan dengan wewenang skrip penuh di halaman login/daftar (`script-src` mengizinkan `www.google.com` dan `www.gstatic.com`). Menghilangkannya berarti mengganti CAPTCHA.
- **Berkas font lokal adalah salinan** dari fonts.gstatic.com pada 21 Sep 2026; pembaruan font di sumbernya tidak ikut otomatis (skrip verifikasi memperingatkan, tidak menggagalkan).
- Pustaka yang dipaketkan sendiri di `assets/js` (mis. pdf.js, Tailwind) sudah lokal dan dijaga `integritas_manifest.json` / pemindai kode berbahaya, bukan dokumen ini.
- Tidak ada `Cross-Origin-Embedder-Policy`; tidak diperlukan untuk SRI dan akan memutus tile peta dan CDN.
