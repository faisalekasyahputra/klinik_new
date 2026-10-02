# URI API dan Metode HTTP - kebijakan, prosedur, dan hasil

Menjawab dua butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 12 "Keamanan API dan Web Service"):

- **12.2** Memverifikasi uniform resource identifier (URI) API tidak menampilkan informasi yang berpotensi sebagai celah keamanan.
- **12.4** Menampilkan metode RESTful HTTP apabila input pengguna dinyatakan valid.

Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0). Validasi skema dan kontrol laju API ada di [`KEAMANAN_API.md`](KEAMANAN_API.md).

## 1. Keadaan sebelum (21 Sep 2026)

| Temuan | Dampak |
|---|---|
| **Diuji di situs live:** `PUT`, `DELETE`, `PATCH`, `PROPFIND`, dan `OPTIONS` ke halaman utama dijawab `200` dengan isi halaman biasa | Aplikasi memperlakukan metode apa pun seperti GET: tidak ada gagasan "metode ini bukan pilihan yang valid", tidak ada `Allow`, dan penerowongan metode (`X-HTTP-Method-Override`, `_method`) tidak dicegah |
| Endpoint yang mengubah keadaan tidak dibatasi POST secara terpusat; sebagian hanya mengandalkan penjaga di dalam handler. `Auth::do_verify_email` menandai surel terverifikasi untuk siapa pun yang login **dan dapat dipicu lewat GET** | Metode aman (GET/HEAD) berefek samping, padahal CSRF hanya melindungi POST |
| Tidak ada larangan data sensitif di URI: nama seperti `nik`, `password`, `token`, `email`, `nim` boleh berada di query string (semuanya ada di allowlist `Input_guard`), dan endpoint POST menerima query string tanpa peduli | URI masuk ke log server, riwayat peramban, header `Referer`, dan proxy |
| Kunci API Gemini berada di query string (`...:generateContent?key=...`) pada `Chat::api_bot` (jalur dikarantina, tetapi kodenya ada) | Kunci akan tercatat di log dan pesan galat bila jalur itu dihidupkan |
| Setiap respons PHP membawa `X-Powered-By: PHP/8.3.x` | Mengumumkan teknologi dan versi ke setiap pemindai |
| Tidak ada penjaga yang menemukan URI berparameter sensitif atau metode penulis yang melayani GET | Pelanggaran baru tidak akan ketahuan |

Yang sudah baik dan **diverifikasi, bukan diubah**: pencarian NIM sertifikat KKN memakai POST (NIM tidak pernah di URL), tidak ada controller yang membaca `nik`/`email`/`token` dari `$_GET`, tidak ada tautan atau formulir GET yang membawa kolom sensitif, `Referrer-Policy: strict-origin-when-cross-origin` (lintas situs hanya asal, bukan URI penuh), `TRACE` ditolak 405 oleh lapisan tepi hosting, dan halaman galat/404 tidak memuat jalur server (diuji dengan URI teknis, traversal, dan `%00`).

## 2. Yang dibangun

| Bagian | Isi | Butir |
|---|---|---|
| Kebijakan | `config/http_policy.php`: metode yang dilayani, daftar penimpa metode, daftar nama query sensitif, pola jalur sensitif, registri `post_only` (26 endpoint) dan `get_write_exempt` (dengan alasan) | 12.2, 12.4 |
| Pemeriksa | `libraries/Http_policy.php` (tanpa ketergantungan CodeIgniter), dipanggil `MY_Controller::enforce_http_policy()` sesudah anti-otomatisasi dan sebelum validasi skema, SESUDAH rute teresolusi. **Fail-closed** | 12.2, 12.4 |
| Hanya metode yang valid | Hanya `GET`, `HEAD`, `POST` yang dilayani. `PUT`, `DELETE`, `PATCH`, `PROPFIND` dst. = 405 dengan `Allow` milik rute itu. `X-HTTP-Method-Override`, `X-HTTP-Method`, `X-Method-Override`, dan parameter `_method` = 400 (penerowongan metode dapat melewati pemeriksaan metode dan CSRF) | 12.4 |
| Metode aman tanpa efek samping | 26 endpoint yang mengubah keadaan hanya melayani POST; `GET`/`HEAD` = 405 dengan `Allow: POST`. `do_verify_email` tidak lagi dapat dipicu lewat GET | 12.4 |
| `Allow` hanya untuk rute nyata | `OPTIONS` dijawab `204` dengan metode milik rute itu saja (mis. `POST, OPTIONS` untuk login; `GET, HEAD, OPTIONS` untuk pencarian). Karena pemeriksaan berjalan sesudah rute teresolusi, alamat asal-asalan tidak pernah mendapat `Allow` (mereka 404) | 12.4 |
| Lapisan server | `.htaccess`: metode selain GET/HEAD/POST/OPTIONS ditolak 405 untuk semua jalur termasuk berkas statis | 12.4 |
| Data sensitif tidak boleh di URI | Nama query terlarang (`nik`, `password`, `token`, `email`, `nim`, `tgl_lahir`, `no_kk`, `api_key`, dst., tanpa membedakan huruf besar/kecil) = 400. Jalur URI yang memuat 16 digit berurutan (NIK/KK) atau `@` (surel) = 400. Endpoint POST tidak menerima query string sama sekali (`Api_schema`) | 12.2 |
| Rahasia tidak di URI | Kunci API Gemini dipindah dari query string ke header `x-goog-api-key` | 12.2 |
| Tanpa penanda teknologi | `X-Powered-By` dibuang dari respons PHP (`header_remove`) dan dari berkas statis (`.htaccess`) | 12.2 |
| Peringatan | Pelanggaran struktural (metode aneh, penimpaan metode, data sensitif di URI) menjadi peringatan `kebijakan_http` di Jejak Audit (rendah, dedupe 30 menit); GET salah alamat ke endpoint POST-only bukan pelanggaran struktural | 12.2, 12.4 |
| Penjaga | `tests/http_uri_test.php` (lihat bagian 4): memindai semua controller dan seluruh view/JS | 12.2, 12.4 |

## 3. Sikap kegagalan (sengaja)

- Pemeriksa kebijakan HTTP **fail-closed**: galatnya menolak permintaan (500), tidak meloloskannya.
- Metode publik yang menulis tetapi melayani GET dengan sengaja dicatat di `get_write_exempt` dengan alasan (OAuth Google dan callback-nya, logout lewat tautan, unduhan ekspor yang hanya membaca, penyaji foto, jalur jebakan, dan skrip `Migrate` yang hanya CLI/loopback). Penjaga menggagalkan metode penulis lain yang tidak terdaftar.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/http_uri_test.php` (offline, 270 pemeriksaan) | Metode (PUT/DELETE/PATCH/TRACE/CONNECT/PROPFIND dan variasi huruf = 405 dengan `Allow` milik rute), tiga header dan satu parameter penimpa, OPTIONS per jenis rute, 26 endpoint POST-only (GET/HEAD = 405, POST lolos), semua nama query terlarang, NIK/surel di jalur (termasuk terenkode `%40`) dan angka biasa yang tidak boleh salah tolak, endpoint POST menolak query string, konsistensi registri dengan kode, **tidak ada tautan GET ke endpoint POST-only di 169 berkas view/JS**, **penjaga: setiap metode publik penulis tanpa penjaga metode harus terdaftar (78 metode diperiksa)**, audit statis URI berparameter sensitif dan formulir GET berkolom sensitif, kunci di URI, Referrer-Policy, `X-Powered-By`, `.htaccess`, dan urutan pemanggilan. **Uji mutasi:** menghapus satu entri POST-only, menyisipkan `?nik=` di view, menghapus pemanggilan di konstruktor, mengizinkan PUT, dan mengabaikan header penimpa masing-masing menggagalkan tes |
| Uji HTTP lokal (klien nyata) | PUT/DELETE/PATCH = 405 `Allow: GET, HEAD, POST`; OPTIONS = 204 dengan Allow per rute; header penimpa dan `_method` = 400; GET ke `do_verify_email`/`do_login`/`buka_kunci` = 405; `?nik=`, `?email=`, `?token=`, NIK dan surel di jalur = 400; POST sah dan halaman biasa tetap 200 |
| Suite harness | Suite yang menyentuh endpoint terkait dibandingkan dengan baseline (lihat catatan rilis) |

## 5. Prosedur operasional

1. **Endpoint baru yang mengubah data** wajib masuk `post_only` (atau, bila sengaja GET, `get_write_exempt` dengan alasan); tes menggagalkan bila tidak. Jangan membuat tautan `<a href>` ke endpoint POST-only: pakai formulir POST + CSRF.
2. **Data pribadi atau rahasia** hanya di badan POST, tidak pernah di query string atau jalur. Bila layanan pihak ketiga menuntutnya (lihat batas), catat di `outbound_sensitive_uri_exempt`.
3. **Klien sah ditolak 405/400**: baca `Allow` dan pesan pada respons; jangan melonggarkan `allowed_methods` global.

## 6. Batas yang diakui

- **NIK di URI keluar ke SIMPERUM.** API SIMPERUM Disperakim menuntut `GetDataRTLH?NIK=<nik>` di query string. Dikirim lewat HTTPS dan tidak dicatat aplikasi ini, tetapi masuk log sisi SIMPERUM. Aplikasi tidak dapat mengubah kontrak itu; bila SIMPERUM kelak menyediakan POST, pindahkan. Dicatat sebagai risiko yang diterima.
- **`Auth::logout` tetap GET** (tautan). CSRF logout hanya mengakhiri sesi dan cookie sesi `SameSite=Lax`; mengubahnya menuntut mengganti setiap tautan keluar menjadi formulir POST.
- **`TRACE` di Apache mandiri** tetap dilayani inti server walau ada aturan `.htaccess` (harus `TraceEnable off` di konfigurasi server). Di hosting produksi TRACE sudah ditolak 405 oleh lapisan tepi.
- **ID berurutan di jalur** (mis. `/Pengembang/simpan_dokumen/{id}`) masih dapat ditebak. Keamanannya bertumpu pada penjagaan kepemilikan di handler (`WHERE user_id` dari sesi), bukan pada kerahasiaan ID; ID buram (UUID) tidak diterapkan.
- **`/index.php/...` tetap dilayani** (mengungkap bahwa ini CodeIgniter); tidak mengungkap data. Header `Server` dan alamat tepi milik platform hosting dan tidak dapat diubah dari aplikasi.
- Pemindaian statis mencari pola URI di kode dan view, bukan URI yang dirakit dinamis di JavaScript dari data runtime; penegakan runtime (`sensitive_in_uri`) yang menutup celah itu untuk permintaan yang masuk.
- Larangan nama query berbasis daftar: nama sensitif baru harus ditambahkan ke `sensitive_query` secara sadar.
