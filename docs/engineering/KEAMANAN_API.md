# Keamanan API dan Layanan Web - validasi skema dan kontrol anti-otomatisasi: kebijakan, prosedur, dan hasil

Menjawab dua butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 12 "Keamanan API dan Web Service"):

- **12.5** Menggunakan validasi skema dan verifikasi sebelum menerima input.
- **12.7** Menerapkan kontrol anti otomatisasi.

Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0). Kontrol laju dasarnya ada di [`ANTI_OTOMATISASI.md`](ANTI_OTOMATISASI.md); dokumen ini hanya mencakup yang ditambahkan untuk API.

## 1. Keadaan sebelum (21 Sep 2026)

| Temuan | Dampak |
|---|---|
| Satu-satunya validasi sebelum controller adalah `Input_guard`: allowlist nama field GLOBAL dan format berdasarkan NAMA field (mis. semua field bernama `id` harus angka) | Tidak ada gagasan "field ini wajib untuk endpoint ini", "endpoint ini hanya menerima POST/XHR", atau "field itu tidak berlaku di sini". Field yang sah di endpoint A lolos di endpoint B |
| Validasi tipe dan rentang tersebar di badan tiap handler dengan gaya berbeda-beda (ada yang 422 JSON, ada yang `echo json_encode`, ada yang `exit`) | Tidak seragam dan mudah terlewat pada endpoint baru; tidak ada tempat tunggal untuk diaudit |
| Objek JSON bersarang (langganan Web Push) diperiksa hanya di model, sesudah controller berjalan | Field bersarang tidak divalidasi pada pintu masuk |
| Kelas batas laju `api` hanya berlaku untuk pola `program/api_*` dan `program/cek_tiket` yang ditulis tangan | Endpoint JSON lain (push, forum, login/registrasi XHR, dokumen pengembang, verifikasi email) hanya kena batas global; endpoint API baru bisa lolos dengan lupa menambah pola |
| Penolakan batas laju untuk endpoint yang dipanggil tanpa header XHR berbentuk teks polos | Klien API yang tidak mengirim `X-Requested-With` menerima 429 yang bukan JSON |

## 2. Yang dibangun

| Bagian | Isi | Butir |
|---|---|---|
| Registri skema | `config/api_schemas.php`: satu deklarasi per endpoint API (17 endpoint): metode yang diizinkan, XHR bila wajib, sumber masukan (form/query/json), daftar field dengan tipe (`int`, `float`, `bool`, `enum`, `date`, `url`, `string`, `object`, `array`), wajib/opsional, rentang, panjang, pola, nilai enum, objek JSON bersarang, segmen URI, dan nama kolom berkas | 12.5 |
| Mesin validasi | `libraries/Api_schema.php` (tanpa ketergantungan CodeIgniter). Menolak: metode salah (405 + `Allow`), non-XHR di endpoint XHR-saja (400), Content-Type tak didukung (415), field wajib hilang, tipe/rentang/panjang/pola/enum salah, **field yang tidak dideklarasikan untuk endpoint itu**, **larik atau objek di tempat nilai tunggal** (kebingungan tipe), objek JSON bersarang yang tidak sesuai skema turunannya, segmen URI berlebih atau bukan angka, dan kolom berkas asing. Pesan galat menyebut nama field dan aturannya, **tidak pernah nilai yang dikirim** | 12.5 |
| Titik pemaksaan | `MY_Controller::enforce_api_schema()` dipanggil di konstruktor, sesudah `Input_guard` dan kontrol anti-otomatisasi tetapi SEBELUM metode controller dan logika sesi/akun. Endpoint yang belum terdaftar tidak terpengaruh. **Fail-closed**: galat pada validator menolak permintaan endpoint terdaftar | 12.5 |
| Respons seragam | JSON `{status:"error", code, message, errors:{field:alasan}}` dengan kode 405/400/415/422. Formulir peramban yang punya tujuan pengalihan sendiri (pencarian sertifikat KKN) tetap dialihkan dengan pesan, bukan halaman galat | 12.5 |
| Peringatan | Pelanggaran STRUKTURAL (field asing, larik di tempat skalar, metode/Content-Type salah) dicatat sebagai peringatan `skema_tidak_valid` di Jejak Audit (tingkat rendah, penekan duplikat 30 menit); salah isi biasa seperti tanggal ngawur tidak, agar salah ketik pengguna tidak membanjiri admin | 12.5 |
| Penjaga cakupan | `tests/api_schema_test.php` memindai semua controller: SETIAP metode publik yang menghasilkan JSON harus terdaftar di skema atau di `api_schema_exempt` dengan alasan tertulis; endpoint API baru yang lupa didaftarkan menggagalkan tes | 12.5, 12.7 |
| Kelas laju API dari registri | `anti_automation_route_classes()` kini juga membaca `class` dari registri skema: SETIAP endpoint terdaftar otomatis kena batas per IP dan per akun untuk kelasnya (`api`: 40/menit; `cari`: 60/menit), di atas batas global dan batas tulis, tanpa menambah pola di dua tempat | 12.7 |
| Respons penolakan JSON | Penolakan laju endpoint API terdaftar berbentuk JSON (`code: automated_attack_warning` / `rate_limit_error`, `Retry-After`) walau klien tidak mengirim header XHR | 12.7 |

### Endpoint terdaftar

`program/api_cek_simperum`, `program/api_kalkulasi_program`, `program/cek_tiket`, `push/config`, `push/subscribe`, `push/unsubscribe`, `umum/toggle_like`, `umum/report_komentar`, `auth/do_login`, `auth/do_register`, `auth/do_verify_email`, `pengembang/simpan_dokumen`, `pengembang/kirim_pengajuan`, `kemitraanportal/cek_sertifikat_kkn`, `index/cari_wil`, `admin/update_status`, `admin_kabkota/update_status`.
Dikecualikan dengan alasan tertulis: `auth/google` (pengalihan OAuth), `index/panduan_desain` (halaman HTML), `pengaturan/export_account_data` (unduhan berkas dengan kata sandi + batas laju khusus), dan tiga metode `migrate/*` (hanya CLI/loopback).

## 3. Sikap kegagalan (sengaja)

- **Validasi skema fail-closed** untuk endpoint terdaftar. Endpoint yang tidak terdaftar tetap hanya melewati `Input_guard`.
- **Kontrol laju tetap fail-open** (lihat `ANTI_OTOMATISASI.md`): jalur global tidak boleh menjadi titik gagal seluruh situs.
- Skema sengaja ketat pada NAMA field (field tak dikenal ditolak) dan longgar pada NILAI yang tidak punya batas alami (mis. pola pencarian `sort`/`searchBy` hanya huruf/strip, tidak dibatasi pada daftar tertutup), supaya klien yang ada tidak rusak.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/api_schema_test.php` (offline, 320 pemeriksaan) | Setiap jenis aturan dan batasnya; regresi khusus (`\d` tanpa `/D` menerima baris baru, panjang dihitung per karakter); objek JSON bersarang (wajib, aturan dalam, field asing, JSON rusak/terlalu dalam/terlalu besar); pesan galat tidak memantulkan nilai (diuji untuk semua jenis aturan); metode/XHR/Content-Type/segmen/berkas; contoh sah dan cacat untuk tiap endpoint penting; konsistensi registri (controller dan metode ada, kelas laju punya kebijakan, semua field ada di allowlist `Input_guard`); penjaga cakupan endpoint JSON; urutan pemanggilan di konstruktor. **Uji mutasi:** menghapus skema, menghapus pemanggilan `enforce_api_schema`, mematikan penolakan field asing, dan membuat pesan memantulkan nilai masing-masing menggagalkan tes |
| Uji HTTP lokal (klien nyata) | Semua endpoint diuji dengan permintaan sah dan cacat: SIMPERUM (sah lolos; NIK pendek, tanggal 31 Feb, field asing, larik, field hilang, GET, non-XHR ditolak dengan kode yang tepat), kalkulasi program, Web Push (JSON bersarang), forum, login sah/salah lewat form dan XHR (tetap berfungsi), unggah dokumen SRP2 (sah diterima; id bukan angka, segmen berlebih, kolom berkas asing ditolak), pencarian wilayah, sertifikat KKN |
| Uji anti-otomatisasi API | Dari alamat non-loopback, 70 permintaan paralel ke endpoint API terdaftar: 40 diproses (dijawab skema 405) lalu 30 dijawab 429 berbentuk JSON dengan `Retry-After`, tanpa header XHR; peringatan `batas_laju` dan `skema_tidak_valid` tercatat |
| Suite harness | Suite yang menyentuh endpoint terdaftar dibandingkan dengan baseline tanpa perubahan ini (lihat catatan rilis) |

## 5. Prosedur operasional

1. **Menambah endpoint API**: daftarkan di `config/api_schemas.php` (metode, field, `class`, `json`). Tes menggagalkan bila endpoint JSON tidak terdaftar atau field-nya belum masuk allowlist `Input_guard` (`config/input_validation.php`).
2. **Klien sah ditolak 422**: baca `errors` pada respons (menyebut field dan aturan). Bila aturannya terlalu ketat untuk penggunaan sah, ubah skema dan jalankan `tests/api_schema_test.php`.
3. **Melihat percobaan yang dirakit**: Jejak Audit `?aksi=peringatan_keamanan`, jenis `skema_tidak_valid`.
4. **Menyetel batas API**: `config/rate_limits.php` (`kelas_api_ip`, `kelas_api_akun`); untuk kantor/kampus yang berbagi satu IP, tambahkan IP ke `ANTI_OTOMATISASI_IP_DIIZINKAN` (lihat `ANTI_OTOMATISASI.md`).

## 6. Batas yang diakui

- **Skema mencakup 17 endpoint API/JSON dan formulir XHR yang dikenal**, bukan seluruh ~45 controller. Endpoint HTML lain tetap hanya lewat `Input_guard` (allowlist nama dan format berdasarkan nama field). Cakupan tidak bisa diam-diam menyusut: tes menggagalkan endpoint JSON baru yang tidak terdaftar, tetapi endpoint HTML baru tidak dipaksa punya skema.
- **Skema memvalidasi bentuk, bukan makna bisnis.** Aturan seperti "status antrean ini boleh berpindah ke status itu" atau "NIK ini milik pemohon" tetap ada di handler dan model.
- **Nilai tertentu sengaja tidak dibatasi pada daftar tertutup** (nilai `status` antrean admin, parameter pencarian Sikumbang) karena daftarnya milik model atau layanan luar; yang dibatasi panjang dan karakternya.
- **Tidak ada dokumen skema publik (OpenAPI).** Aplikasi ini tidak menyediakan API untuk pihak ketiga; skema adalah kontrol internal antara halaman dan servernya.
- Batas laju per IP dapat mengenai pengguna yang berbagi satu IP publik (lihat `ANTI_OTOMATISASI.md`); kelas `api` 40 permintaan per menit per IP juga berlaku untuk login/registrasi XHR.
