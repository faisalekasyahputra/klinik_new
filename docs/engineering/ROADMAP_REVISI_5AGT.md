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

| Gelombang | Butir | Sifat | Risiko |
|---|---|---|---|
| **R1** | A4, C3, D1, D3 | Label saja, nol logika | Sangat rendah |
| **R2** | A8, C2 | Tata letak | Rendah–sedang |
| **R3** | A5 | Logika lintas berkas | **Sedang — ada sisi keamanan** |

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

## Yang sengaja TIDAK masuk roadmap ini

| Butir | Alasan |
|---|---|
| A1, A2 | Menunggu berkas foto & kalimat definisi dari dinas |
| A3, A9, B2, C1, C4, D2, D4, E1 | Menunggu jawaban — mengerjakannya sekarang berarti menebak |
| A6, A10 | Sebagian bisa jalan (lihat di bawah), sebagian menunggu |
| A7, A11 | **Berbenturan** — menyangkut aturan kelayakan & pengaman data, keputusan kebijakan |
| B1, F1 | Perlu data baru + kepastian siapa yang mengisi |

### Dua pekerjaan yang bisa ikut tanpa menunggu siapa pun

Keduanya bagian dari butir yang secara keseluruhan berstatus "perlu jawaban", tapi
bagian ini berdiri sendiri. **Belum dijadwalkan — perlu keputusan user dulu.**

- **A10a — sapuan istilah** "usulan/pengusulan" → "rekomendasi". Sapuan pertama sudah
  dilakukan 3 Agt (tombol jadi "Simpan Data"); sisanya tinggal susulan. Tidak
  bergantung pada pertanyaan desil di A10.
- **A10c — unduh PDF hasil rekomendasi.** Fitur baru, belum ada apa pun. Isinya
  bergantung pada program yang direkomendasikan — jadi kalau aturan desil nanti diubah
  (pertanyaan terbuka di A10), tampilan PDF-nya ikut berubah, **tapi kerangkanya tidak**.
  Aman dibangun lebih dulu.

---

## Definisi selesai

Berlaku untuk tiap gelombang, mengikuti konvensi proyek:

1. Suite penuh `php docs/engineering/jalankan_semua.php` hijau — **0 merah, 0 bisu**.
2. Tiap penjaga baru **dibuktikan bisa merah** lewat mutasi sebelum dianggap jadi.
   Penjaga yang tidak pernah dilihat merah belum tentu menjaga apa pun.
3. Untuk yang mengubah tabel/tata letak: diukur di peramban, bukan disimpulkan dari
   kode — `scrollWidth − clientWidth === 0` di 1440px, dan diperiksa juga di 375px.
4. Sesudah tayang: diverifikasi **dari halaman production**, bukan dari `git ls-remote`.
