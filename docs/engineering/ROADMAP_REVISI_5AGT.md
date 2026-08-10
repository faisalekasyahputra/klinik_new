# Roadmap Perbaikan — Butir "Jelas" Revisi Dinas 5 Agt 2026

> Turunan dari konfirmasi 23 butir revisi rapat dinas (Bu Zunita & tim, 5 Agt 2026).
> Yang masuk ke sini **hanya butir yang tidak menunggu jawaban siapa pun** — bisa
> dikerjakan sekarang tanpa risiko dibongkar ulang kalau dinas menjawab lain.
>
> 16 butir sisanya (10 perlu jawaban, 2 berbenturan, 2 menunggu berkas, 2 perlu data
> baru) **sengaja tidak dijadwalkan**. Mengerjakannya sebelum ada jawaban berarti
> menebak, dan dua di antaranya (A7 penentu program, A11 pengaman Cek RTLH) menyangkut
> kebijakan — bukan wewenang kita.

## Ringkas

| Gelombang | Butir | Sifat | Risiko | Keadaan |
|---|---|---|---|---|
| **R1** | A4, C3, D1, D3 | Label saja, nol logika | Sangat rendah | ✅ tayang `4b8eebf` |
| **R2** | A8, C2 | Tata letak | Rendah–sedang | ✅ tayang `81594e4` |
| **R3** | A5 | Logika lintas berkas | **Sedang — ada sisi keamanan** | ✅ tayang `95473f8` |
| **R4** | A9, A10a, A11b, E1 | Buang isian, lepas syarat, ganti nama | Rendah | ✅ tayang `7fd7478` |
| **R5** | A7, A10c, B1, C1, C4/D4 | Aturan kelayakan, migrasi, fitur baru | **Sedang–tinggi** | ✅ tayang `ea700cf` |

**R5 paling berat sejauh ini, dan bukan karena banyaknya.** A7 mengubah aturan
kelayakan program — salah di situ berarti warga diarahkan ke bantuan yang salah,
dan itu tidak menghasilkan galat apa pun yang kelihatan. B1 butuh migrasi.
C1 mengubah aturan validasi laporan yang sudah berjalan. Kerjakan satu per satu
dengan penjaganya masing-masing, jangan digabung jadi satu commit.

**Selesai satu per satu seperti niatnya, lima commit + satu fitur susulan:** A7
`91a0e34`, C1 `85f131d`, etalase program yang bisa diurus dari layar (migrasi
`036`) `548a928`, A10c `93ca324`, C4/D4 `6bfc19a`, B1 (migrasi `037`) `ea700cf`.

> 🔻 **Dua butir R5 ternyata membawa bug yang sudah berjalan, dan itu bagian
> paling berharga dari gelombang ini.** A7 mendapati separuh penyaringnya sudah
> ada; yang hilang justru status kepemilikan **di atas** desil. B1 mendapati
> `Admin_Srp2::save()` merakit payload penuh padahal form baris tidak punya
> isian `sosmed_lainnya` — jadi setiap "Simpan" menge-NULL-kannya diam-diam,
> **nol dari 67 baris** terisi. Kalau kolom tanggal ditambahkan tanpa
> membetulkan akarnya, tanggal sertifikat akan lenyap dengan cara yang sama.
> Pelajarannya: sebelum menambah kolom, periksa dulu bagaimana kolom tetangganya
> diperlakukan penyimpannya.

Urutannya **berdasarkan risiko, bukan nilai**. A5 justru yang paling dirasakan dinas
("*soalnya bingung*"), tapi ia yang paling bisa merusak kalau tergesa. Kalau user mau
membalik urutan, R3 boleh naik — asal penjaganya tetap dibuat lebih dulu.

---

## R1 — Perbaikan label (4 butir, satu commit)

Empat-empatnya perubahan teks tanpa efek samping. Digabung jadi satu commit karena
memecahnya jadi empat commit justru menyulitkan pembacaan riwayat.

| Butir | Berkas | Sekarang | Jadi |
|---|---|---|---|
| **A4** | `views/pages/golek_omah/index.php:47` | `(Rumah Swadaya)` | `(Rumah swadaya / bangun sendiri)` |
| **D1** | `config/dashboard_modules.php:155` | `'label' => 'Kawasan'` | `'label' => 'Kawasan Permukiman'` |
| **D3** | `views/admin/rekam/kawasan_input.php:241` | `Keterangan sumber (opsional)` | `Keterangan (opsional)` |
| **C3** | `views/admin/rekam/perumahan_capaian.php:183` | `Tabel Unit Realisasi` | `Tabel Unit Realisasi TW <n> <tahun>` |

### Catatan per butir

**D1** — label menu ini juga dipakai sebagai teks tautan di sidebar. Panjangnya
bertambah dari 7 ke 18 karakter; sidebar admin lebarnya tetap, jadi **ukur apakah
teksnya terpotong atau membungkus** sebelum dianggap selesai.

**D3** — di layar yang sama ada label lain, `Sumber`, yang menampilkan sumber anggaran
(`kawasan_input.php:180`). **Itu bukan yang diminta dan tidak disentuh.** Sudah
ditanyakan balik ke dinas di dokumen konfirmasi; kalau ternyata itu yang dimaksud,
butir ini dikerjakan ulang.

**C3** — tabel kumulatif tepat di bawahnya (`:187`) sudah memakai pola
`'... s.d. ' . $nama_tw[...] . ' ' . $tahun`. **Pakai sumber yang sama**, jangan
merangkai string triwulan sendiri — kalau nanti penamaan TW berubah, satu tempat saja
yang perlu disunting.

### Penjaga

Tambahkan ke `docs/engineering/uji_regresi_tampilan.php`.

> ⚠️ **Jebakan yang sudah empat kali kena di proyek ini: pencocokan substring
> se-halaman.** "Bidang Tujuan" pernah lolos karena cocok dengan FAQ di footer;
> `warga/pendataan` cocok dengan komentar HTML. **Jangan** grep teks di seluruh
> keluaran halaman. Ikat asersinya ke berkas + posisi DOM yang benar, dan **buktikan
> tiap penjaga bisa merah** lewat mutasi sebelum dianggap jadi.

---

## R2 — Tata letak (2 butir)

### A8 — Ukuran tanah jadi satu kesatuan

`views/pages/warga/pendataan.php:118`

Isian `land_length_m` dan `land_width_m` **sudah bersebelahan** dalam grid
`sm:grid-cols-2`. Yang membingungkan bukan letaknya, melainkan labelnya:

```
Ukuran Tanah — Panjang (m)      Ukuran Tanah — Lebar (m)
```

Frasa "Ukuran Tanah" diulang dua kali, sehingga terbaca seperti dua hal berbeda.
Perbaikannya: satu judul kelompok `Ukuran tanah`, di bawahnya dua isian berlabel
`Panjang (m)` dan `Lebar (m)` saja.

> **Catatan lokasi:** dinas menyebut ini ada di "menu diagnosa". Isian tanahnya
> sebenarnya di **wizard pendataan warga**, bukan `pages/program/diagnosa.php`.
> Alur diagnosa memang diarahkan ke wizard sejak 1 Agu 2026, jadi kemungkinan besar
> memang layar ini — tapi **konfirmasi ke dinas sebelum dikerjakan**, karena kalau
> keliru, yang diperbaiki bukan layar yang mereka lihat.

Yang **tidak** diubah: nama medan, aturan validasi (`min="0.01"`), dan urutan langkah
wizard. Ini murni penataan label.

### C2 — Rencana & realisasi dijejerkan

`views/admin/rekam/perumahan_capaian.php`

Sekarang ada satu closure `$tabel($judul, $sisi, $data)` yang menggambar **satu matriks
untuk satu sisi angka**, dipanggil tiga kali:

```php
$tabel('Tabel Unit Rencana',   'rencana',   $matriks);
$tabel('Tabel Unit Realisasi', 'realisasi', $matriks);   // ← $matriks yang SAMA
$tabel('Kumulatif Realisasi …','realisasi', $kumulatif);
```

Dua panggilan pertama membaca `$matriks` yang sama, cuma beda sisi. Jadi menggabungnya
tidak perlu menyentuh sumber datanya sama sekali.

**Rancangan paling hemat:** ubah parameter kedua dari satu sisi menjadi daftar sisi —
`['rencana','realisasi']` untuk tabel utama, `['realisasi']` untuk kumulatif. Satu
closure, dua pola panggil, nol duplikasi. Tabel kumulatif tetap satu sisi dan **tidak
ikut berubah**.

> 🔻 **Yang harus diukur, bukan diasumsikan: lebar tabel.** Tabel ini sudah
> `min-w-[1100px]`. Menjejerkan dua sisi berarti kolom angkanya **berlipat dua**, dan
> tiap sisi punya unit + rupiah. Ada peluang nyata tabelnya jadi terlalu lebar untuk
> dibaca meski wadahnya bergulir.
>
> Ukur `scrollWidth − clientWidth` di 1440px **sesudah** perubahan (konvensi §17.6).
> Kalau terlalu lebar, opsi mundurnya — urut dari yang paling murah:
> 1. unit dan rupiah ditumpuk dalam satu sel, bukan dua kolom;
> 2. kolom rupiah disembunyikan di layar sempit;
> 3. batal dijejerkan, cukup dirapatkan agar terbaca berpasangan.
>
> **Jangan diam-diam memilih opsi 3 lalu melaporkannya sebagai "sudah dijejerkan".**

### Penjaga R2

Suite `uji_wizard_rekam_perumahan.php` sudah membaca layar capaian. Tambahkan:
angka rencana dan realisasi untuk satu program berada **dalam satu baris `<tr>` yang
sama** — asersi ini merah kalau tabelnya kembali terpisah, dan itu memang yang mau
dijaga.

---

## R3 — Kembali ke halaman asal sesudah login (A5)

**Ini satu-satunya butir dengan logika, dan cakupannya lebih luas dari dugaan awal.**

### Keadaan sekarang

Mekanismenya **sudah ada dan sudah benar**: `Auth::_redirect_after_login()` membaca
`intended_url` dari sesi dan mengarah ke sana. Masalahnya bukan di situ.

Masalahnya **21 gerbang memanggil `redirect('Auth/login')` telanjang** — tanpa
menyimpan asal halaman lebih dulu — tersebar di enam berkas:

```
application/core/MY_Controller.php      ← 6 gerbang, ini induknya
application/controllers/Auth.php
application/controllers/Cek_Rtlh.php
application/controllers/KemitraanPortal.php
application/controllers/Pengaturan.php
application/controllers/Umum.php
```

Hanya `Pengembang.php` dan `KemitraanPortal.php` yang mengisi `intended_url`, dan
keduanya melakukannya sendiri-sendiri. Itu sebabnya sebagian halaman balik dengan
benar dan sebagian melempar ke dashboard — persis yang dilaporkan dinas.

### Perbaikan akar, bukan gejala

Satu penolong di `MY_Controller` — induk yang dilewati semua gerbang — lalu 21 titik
panggil diarahkan ke sana. Menambal satu per satu di tiap controller berarti pola yang
sama disalin 21 kali dan gerbang berikutnya lupa lagi.

> 🔴 **Sisi keamanan — jangan dilewat.** Menyimpan "asal halaman" lalu mengarahkan ke
> sana sesudah login **adalah pola open redirect**. Kalau alamat tujuan diambil mentah,
> penyerang bisa mengirim tautan yang tampak sah, korban login, lalu terlempar ke situs
> palsu yang sudah dalam keadaan "baru saja login".
>
> **`Auth::sanitize_redirect()` sudah ada dan sudah menolak URL eksternal — WAJIB
> dipakai ulang.** Jangan menulis penyaring baru.
>
> Tiga hal yang harus ditolak, dan tiap-tiapnya dibuktikan bisa merah lewat mutasi:
> 1. URL berhost lain (`https://jahat.example/…`);
> 2. URL berskema aneh (`javascript:`, `//jahat.example`);
> 3. alamat yang datang dari **query string**, bukan dari sesi — sumbernya harus sesi
>    yang kita isi sendiri di sisi server.

### Penjaga R3

Suite baru atau tambahan di harness perjalanan yang sudah ada:

1. Anonim membuka halaman ber-gerbang → diarahkan ke login → login berhasil →
   **mendarat kembali di halaman semula**, bukan dashboard.
2. `intended_url` berisi URL eksternal → **diabaikan**, mendarat di dashboard.
3. Sesudah dipakai, `intended_url` **dihapus** dari sesi — supaya login berikutnya
   tidak melompat ke tempat lama.

---

## Keputusan yang sudah diambil — 5 Agt 2026, oleh user

> Bagian ini menggantikan daftar "menunggu jawaban" yang ada di sini sebelumnya.
> Dua belas butir sudah punya keputusan; alasannya ikut dicatat, karena
> keputusan tanpa alasan akan dipertanyakan ulang enam bulan lagi.

| Butir | Keputusan | Kenapa begitu |
|---|---|---|
| **A7** | Status rumah **menyaring DI ATAS** desil, tidak menggantikannya | Menggantikan desil berarti mengubah sasaran subsidi — warga desil 9 berumah tidak layak akan masuk RTLH. Itu keputusan kebijakan, bukan tampilan. ✅ R5 |
| **A9** | Buang dari formulir, **berkas lama tetap tersimpan** | Foto orang & bukti kepemilikan; menghapusnya tidak bisa dibatalkan dan pengajuan lama kehilangan lampirannya. ✅ R4 |
| **A10a** | Sapuan istilah | ✅ **ternyata sudah selesai 3 Agt** — nol pekerjaan |
| **A10c** | PDF **cetakan informatif** + peringatan tegas | Berkop resmi gampang dianggap keputusan, padahal isinya hitungan otomatis yang belum diverifikasi siapa pun. Mencegah warga datang merasa sudah berhak. ✅ R5 — dikerjakan sebagai **cetak browser**, bukan pustaka PDF baru |
| **A11a** | Tanggal lahir **DIPERTAHANKAN** | Pengaman anti-penelusuran. Tanpa itu, siapa pun yang tahu NIK bisa memeriksa status kemiskinan orang lain. Nol pekerjaan |
| **A11b** | Nama jadi **"Cek Data Rumah"** — BUKAN "Cek Backlog" | Backlog & RTLH indikator berbeda, dan API-nya mengembalikan RTLH. Menamainya backlog menjanjikan angka yang tidak ada. ✅ R4 |
| **B1** | Kolom tanggal ditambah, **dikosongkan**, diisi admin bertahap | Menghitung otomatis dari tanggal daftar = tanggal karangan, dan masa berlaku sertifikat adalah hal yang orang percayai. ✅ R5 — migrasi `037`, 68 baris production semuanya `NULL` |
| **C1** | BNBA wajib untuk laporan **baru**; laporan lama tetap sah | Tidak adil menuntut kabupaten/kota melengkapi aturan yang belum berlaku saat mereka mengisi. ✅ R5 |
| **C4/D4** | Export **Excel**, **rekap saja — BNBA tidak ikut** | BNBA berisi nama & NIK; begitu jadi berkas Excel ia berpindah tangan tanpa jejak. ✅ R5 |
| **D2** | Isian **berjenjang dari daftar** | Ketik bebas membuat "PK RTLH"/"pk rtlh"/"Peningkatan Kualitas RTLH" terhitung tiga hal. **Butuh daftar resmi dari dinas** |
| **E1** | Syarat "harus ditanggapi dulu" **dilepas** | Justru syarat itu yang membuat dinas tidak pernah melihat tombolnya. ✅ R4 |
| **F1** | Posisi **DI DALAM** bidang, tidak menggantikan | Bidang tetap tulang punggung kuota; mesin slot & 70 cek ujinya tidak perlu dibongkar. **Butuh daftar posisi dari dinas** |

### Masih benar-benar terhenti

| Apa | Menunggu | Catatan |
|---|---|---|
| **B2** | Keterangan | Layar mana, akun apa (peran + kabupaten), data warga seperti apa yang terlihat. **Didahulukan begitu keterangannya ada** — kalau benar, ini batas kewenangan bocor, bukan cacat tampilan |
| **A3** | Contoh nyata | Angka "3 atau 6" tidak cocok dengan pengaturan mana pun (batasnya 9 per muat). Butuh: halaman mana, kabupaten apa, penyaring apa |
| A1, A2, A6 | Berkas & teks | 5 foto program, kalimat definisi subsidi/non-subsidi, persetujuan rumusan desil |
| D2, F1 | Daftar | Nomenklatur program–kegiatan–sub kegiatan; daftar posisi magang |
| Butir 4 lama | Daftar | Keterangan 11 formulir SRP2 yang belum terisi |
| Butir 8b lama | Berkas | Buku panduan + leaflet KKN pengganti juknis |

---

## Definisi selesai

Berlaku untuk tiap gelombang, mengikuti konvensi proyek:

1. Suite penuh `php docs/engineering/jalankan_semua.php` hijau — **0 merah, 0 bisu**.
2. Tiap penjaga baru **dibuktikan bisa merah** lewat mutasi sebelum dianggap jadi.
   Penjaga yang tidak pernah dilihat merah belum tentu menjaga apa pun.
3. Untuk yang mengubah tabel/tata letak: diukur di peramban, bukan disimpulkan dari
   kode — `scrollWidth − clientWidth === 0` di 1440px, dan diperiksa juga di 375px.
4. Sesudah tayang: diverifikasi **dari halaman production**, bukan dari `git ls-remote`.
