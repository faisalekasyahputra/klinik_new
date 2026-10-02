# Fitur Kode Dinamis - inventaris, perbaikan, dan penjaga

Menjawab satu butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 4 "Validasi Input"):

- **4.6** Menggunakan fitur kode dinamis.

"Fitur kode dinamis" = apa pun yang membuat teks atau nilai runtime dijalankan sebagai kode, atau memilih kode yang dijalankan: `eval`, `assert`, `include` dengan jalur dinamis, `unserialize`, pemanggilan fungsi/metode/kelas lewat nama variabel, variabel-variabel, `extract`, `create_function`, dan (di sisi peramban) `eval`, `new Function`, timer berisi string, `document.write`, serta ekspresi Alpine/JS yang dirakit dari data.
Pedoman yang ditegakkan: **tidak ada di kode buatan sendiri**; yang tersisa ditinjau, terdaftar beserta alasannya, dan tidak pernah menerima masukan pengguna.
Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Inventaris (21 Sep 2026)

Pemindaian token atas seluruh 357 berkas PHP di `application/` (view arsip dikecualikan):

| Fitur | Hasil awal |
|---|---|
| `eval`, backtick, `assert`, `create_function`, keluarga `exec` | **Nol** (sudah dijaga `tests/no_dynamic_code_test.php`) |
| `unserialize`, `call_user_func*`, `forward_static_call*`, `import_request_variables`, `parse_str`, `get_defined_vars`, Reflection, `new $kelas`, `$kelas::`, variabel-variabel | **Nol** |
| `include`/`require` | 23, semuanya berjalan dari konstanta (`APPPATH`, `FCPATH`, `__DIR__`, `dirname`) atau literal; **tidak ada yang dari variabel** |
| Pemanggilan lewat variabel `$x(...)` | 489, **semuanya closure yang ditetapkan di berkas yang sama** (bukan nama fungsi dari luar) |
| `->{ekspresi}` (properti/metode dinamis) | 2, keduanya dengan nama dari konfigurasi/literal (terdaftar dengan alasan di tes) |
| `extract()` | **1, di `Buka_peta::edit_record()`**: model CRUD tabel dinamis lama (`delete_record($tabel, ...)`, `insert_data($tabel, ...)`, `extract($args)`) yang dimuat `Pengembang` tetapi **tidak punya satu pemanggil pun** |
| Pemuatan view/pustaka dengan nama dari variabel | 4 di `MY_Controller` (`render`/`render_admin`); semua pemanggilnya memberi literal |
| JavaScript buatan sendiri (`assets/js`, skrip inline, atribut Alpine) | Tidak ada `eval`, `new Function`, timer string, `document.write`, `x-html` |
| **Echo PHP di dalam skrip inline dan ekspresi Alpine** | 110 ekspresi; 32 tanpa pembungkus yang dikenali, 6 di antaranya sudah aman (ternari dua literal, JSON ber-flag HEX) dan **26 mentah** (bagian 2) |

## 2. Yang diperbaiki

| Temuan | Perbaikan |
|---|---|
| `onboarding.php`: nilai isian ulang (`role`) dicetak ke ekspresi Alpine `x-data="onboardingForm('<?= html_escape(...) ?>', ...)"`. `html_escape` TIDAK cukup: entitas `&#039;` didekode peramban sebelum Alpine mengevaluasi ekspresinya, sehingga `');...//` menjadi kode JavaScript (XSS lewat isian ulang formulir) | Nilai lewat `json_encode` lalu `htmlspecialchars` |
| `diagnosa.php` (3 tempat): kode program (nilai DB yang dikelola admin) dicetak mentah ke string JS dan ke ekspresi `x-show` | `json_encode` (dan `htmlspecialchars` di atribut) |
| `detail_perumahan.php`: **koordinat dari API pihak ketiga (Sikumbang)** dicetak mentah ke skrip (`let lat = <?= ... ?>`); data hulu yang dibobol menjadi kode di halaman kita | Cast `(float)` |
| `statistika.php`: 18 nilai statistik dan 4 hitungan publikasi dicetak mentah ke larik Chart.js | Cast `(float)`/`(int)` |
| `Buka_peta` (model mati dengan `extract` dan CRUD tabel dinamis) | **Dihapus**, beserta pemuatannya di `Pengembang` |

## 3. Penjaga

`tests/dynamic_code_test.php` (offline): menggagalkan bila kode buatan sendiri memakai `eval`/backtick/`assert`/`unserialize`/`call_user_func*`/`extract`/`parse_str`/Reflection/`preg_replace` berpengubah `/e`, `include` dengan jalur dari variabel, variabel-variabel, `new $kelas`, `$kelas::`, pemanggilan lewat variabel yang bukan closure lokal, metode/properti dinamis yang tidak terdaftar, pemuatan view/pustaka dari variabel, `eval`/`new Function`/timer string/`document.write`/`x-html` di JS atau ekspresi Alpine, echo PHP tanpa pembungkus aman di skrip inline atau ekspresi Alpine, dan bila `Buka_peta` kembali. **Uji mutasi:** `unserialize`, `include $_GET[...]`, `$$a`, `$fn(1)` tanpa closure, `new Function` di JS, kembalinya pola onboarding lama, dan echo mentah di `<script>` masing-masing menggagalkan tes.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/dynamic_code_test.php` (offline, 16 pemeriksaan) | 357 berkas PHP, 489 pemanggilan-variabel (semua closure lokal), 155 berkas JS/view, 85 echo di konteks kode, semuanya bersih; tujuh mutasi menggagalkan tes |
| Uji HTTP lokal | Halaman statistik menghasilkan larik angka yang benar; halaman diagnosa merender `"rtlh"` di `x-show` dan `formData.append('kode_program_target', "rtlh")` |
| Pemindai kode berbahaya, `no_dynamic_code_test` | Bersih |

## 5. Prosedur

1. Mencetak data ke skrip atau ekspresi Alpine: **selalu** `json_encode` (di atribut: `htmlspecialchars(json_encode(...), ENT_QUOTES)`), atau cast `(int)`/`(float)`/`(bool)`. `html_escape` saja hanya benar untuk teks HTML, bukan untuk ekspresi JS.
2. Butuh memilih kode berdasarkan nama (view, pustaka, metode)? Pakai peta tertutup (`switch`/array asosiatif dengan kunci literal), bukan nama dari variabel.
3. Menambah pengecualian di `tests/dynamic_code_test.php` hanya dengan alasan tertulis bahwa nilainya tidak berasal dari masukan pengguna.

## 6. Batas yang diakui

- **CSP masih `'unsafe-eval'`.** Alpine.js (build standar) mengevaluasi ekspresi atribut lewat `Function`, sehingga peramban harus mengizinkan eval; ini fitur kode dinamis yang tetap ada, di pustaka pihak ketiga (bukan kode buatan sendiri). Perlindungannya adalah pembungkus aman di atas (data tidak boleh menjadi kode) dan penjaga tes. Menghilangkannya menuntut pindah ke build CSP Alpine dan menulis ulang seluruh ekspresi inline, dan tidak dikerjakan di sini.
- **Pemindaian statis berbasis pola dan token**, bukan analisis aliran data: ia tidak membuktikan bahwa nilai yang dicetak ke konteks kode "aman" selain lewat pembungkusnya, dan pemeriksaan "closure lokal" adalah heuristik (variabel yang ditetapkan sebagai closure di berkas yang sama).
- Pustaka pihak ketiga (`vendor/`, Alpine, pdf.js, Tailwind, Chart.js) tidak dipindai untuk `eval`; integritasnya dijaga tersendiri (lihat [`KODE_BERBAHAYA.md`](KODE_BERBAHAYA.md)).
- **SQL dinamis (injeksi basis data) adalah butir lain** (4.8) dan tidak dicakup di sini.
- Konstruksi seperti `$this->{$metode}()` atau `$$var` di kode PHP yang di-*generate* saat runtime (mis. dari data) tidak ada; bila ditambahkan tanpa pola yang dikenali pemindai, ia bisa lolos sampai ditinjau.
