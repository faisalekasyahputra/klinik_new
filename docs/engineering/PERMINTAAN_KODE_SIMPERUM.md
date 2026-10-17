# Permintaan Daftar Kode SIMPERUM

**Tanggal:** 25 Agustus 2026
**Status:** draft versi PDF sudah DIKIRIM ke dinas 31 Agustus 2026, dan **dua
butir sudah DIJAWAB hari itu juga** lewat WhatsApp: daftar `SumberAir` 1-12 dan
`SumberDana` 0-15. Keduanya sudah diterapkan ke kode. Dinas juga menegaskan
"kalau ada data yang kosong berarti itu data lama, anggap saja null", yang
menutup pertanyaan soal banyaknya medan kosong. Sisanya masih menunggu.
**Pembaruan 17 Oktober 2026:** butir 7 (desil) tertutup oleh data, dan sisa
butir kode dicocokkan dengan foto form asli SIMPERUM. Hasilnya di bagian E:
satu kode terjawab dengan bukti kuat, dua tetap terbuka, dan tiga pertanyaan
baru muncul. Semua masih dugaan sampai dinas mengonfirmasi; belum ada yang
diterapkan ke kode.
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
| ~~1~~ | ~~`SumberAir` 7-12~~ | ✅ **DIJAWAB 31 Agt 2026.** Daftar resmi 1-12 diterima dan diterapkan. Ternyata bukan cuma kurang: tiga kode lama kami SALAH (4 dibaca sumur padahal Leding eceran, 5 dibaca mata air padahal Sumur, 6 dibaca air hujan padahal Sumur terlindung), dan kode 12 "Lainnya / Tidak Layak" tidak dipetakan sehingga pemicu sanitasi kritis tidak pernah menyala | - |
| ~~2~~ | ~~`SumberDanaID` 6, 8, 10, 15~~ | ✅ **DIJAWAB 31 Agt 2026.** Yang benar-benar sumber dana ditambahkan (12 BANKAB, 13 BAZNAS). Kode 0, 6, 8, 10, 11, 15 ternyata keterangan DISPOSISI (Sudah Layak Huni, Diluar Prioritas, Meninggal, Salah/Double Data, Pindah), bukan sumber dana, jadi sengaja tidak dipetakan ke sana | - |
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
| ~~7~~ | ~~Sumber **desil / kelompok kesejahteraan**~~ | ✅ **TERJAWAB OLEH DATA 13 Okt 2026.** Anggapan "tidak disediakan API" ternyata salah: balikan `GetDataRTLH` membawa isian `Desil`, dan gateway membacanya sejak itu (`Simperum_gateway::desil_sah()`). Form asli SIMPERUM juga menampilkan Desil di samping Penghasilan. Tidak perlu ditanyakan lagi |
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

## E. Bukti dari form asli SIMPERUM (dicocokkan 17 Oktober 2026)

Sumbernya 44 foto layar form "Validasi Detail" SIMPERUM (23 Juli 2026), jalur
`/Main/RTLH/PBDT_edit/` dan `/Main/Backlog/ValidasiData`, yang label dan
pilihannya sudah disalin di `AUDIT_AKURASI_FORM_WARGA_R3.md`. Yang baru di sini
adalah pencocokannya dengan tabel kode resmi di `Simperum_gateway.php`, yang
baru lengkap sesudah jawaban dinas 31 Agustus. Data pribadi pada foto tidak
disalin ke mana pun.

**Pola yang teruji.** Pada katalog berpilihan banyak, urutan pilihan di
dropdown sama dengan urutan kode resmi. Sembilan katalog cocok tanpa satu pun
pengecualian: Pendidikan (0-6), Bahan Lantai (1-10), Bahan Dinding (1-7),
Bahan Atap (4-6 yang terlihat), Penerangan (1-4), Jarak Septik (0-1), lalu
`SumberAir`, `SumberDanaID`, dan Kawasan yang dropdown-nya hanya memuat
sebagian kode tetapi tetap berurutan naik. Form tidak memperlihatkan angka
kodenya, jadi pola ini bukti, bukan kepastian.

**Pola ini TIDAK berlaku untuk pilihan dua nilai** (Ada/Tidak Ada,
Memiliki/Tidak Memiliki, Mampu/Tidak Mampu): urutan tampilannya tidak bisa
dipakai menebak mana 0 dan mana 1.

### Butir lama yang terbantu

| No | Butir | Yang terlihat di form | Kesimpulan |
|---|---|---|---|
| 3 | `AdaPondasi` 0-4 | Isian "Pondasi*" memakai empat pilihan yang SAMA dengan Kondisi Kolom/Balok: Baik, Rusak Ringan (Permukaan), Rusak Sedang (Material), Rusak Berat (Struktur/Tdk Ada), ditambah "-" sebagai isian kosong | **Dugaan kuat:** 1-4 adalah skala kondisi seperti `KondisiKolom`, dan 0 berarti belum diisi. Ini sejalan dengan jawaban dinas 31 Agt bahwa `SumberDanaID` 0 berlabel "-" |
| 4 | `RumahLain` 4 | Hanya dua pilihan: Memiliki, Tidak Memiliki | Form tidak menjelaskan nilai 4. **Tetap terbuka** |
| 5a | `Pekerjaan` 23 | Urutan dropdown: ... TNI/POLRI (22), **Pegawai Swasta, PHL/PTT**, Pensiunan (98), Tidak Bekerja (99). Lima belas pilihan lain yang terbaca juga berurutan sesuai kode resminya | **Dugaan kuat:** 23 = Pegawai Swasta, 24 = PHL/PTT |
| 5b | `KepemilikanLahan` 0 | Tujuh pilihan (Sertifikat HM, Sertifikat HGB, Letter C, Letter D, SuKet Desa, Akta Notaris, Lainnya), sedangkan dokumen API hanya mengenal 1-4 | Katalog form dan dokumen API berbeda, jadi form tidak memutuskannya. Paling mungkin 0 = belum diisi, seperti `SumberDanaID` 0. **Tetap terbuka** |
| 6 | `KondisiAtap/Lantai/Dinding` | Ketiganya isian WAJIB (bertanda *) di form RTLH | Datanya ada di sisi dinas, hanya tidak dikirim `GetDataRTLH`. Permintaannya kini lebih tajam: mohon ketiganya ditambahkan ke balasan |
| 8 | `TahunLahir` | Form RTLH memakai Tanggal Lahir lengkap (wajib); pada rekaman contoh isiannya kosong. Form Backlog memakai Umur | Kosongnya tampak berasal dari data, bukan dari API. Kemungkinan sudah tercakup pernyataan dinas 31 Agt ("data kosong berarti data lama, anggap null") |

### Pertanyaan baru dari pencocokan ini

| No | Medan | Yang tidak cocok | Yang perlu dikonfirmasi |
|---|---|---|---|
| 12 | `KepemilikanRumah` | Form punya enam pilihan: Milik Sendiri, Kontrak/Sewa, Bebas Sewa, Dinas, **Menumpang**, Lainnya. Gateway memetakan 1-5 dengan 5 = `other`, dan kode 6 tidak dipetakan. Wizard warga kita sendiri sudah membedakan `staying` (menumpang) dari `other` | Apakah 5 = Menumpang dan 6 = Lainnya. Kalau ya, warga menumpang kini tercatat sebagai "lainnya" |
| 13 | `Penghasilan` | Form punya sembilan rentang, tiga teratas 4,2-6 jt, 6-8 jt, dan > 8 jt. Gateway berhenti di 7 = "> 4,2 jt", tanpa 8 dan 9. Wizard warga sudah mengenal ketiga rentang itu | Apakah 7 = 4,2-6 jt, 8 = 6-8 jt, 9 = > 8 jt |
| 14 | `MampuSwadaya`, `AdaJendela`, `AdaVentilasi`, `TanahLain`, `RumahLain` | Pilihan dua nilai; urutan tampilan tidak membuktikan apa pun | Konfirmasi tertulis bahwa 1 = Mampu/Ada/Memiliki dan 0 = sebaliknya, seperti yang dipakai gateway sekarang |

### Risiko yang sudah berjalan, menunggu keputusan

Gateway saat ini memetakan `AdaPondasi` 0 ke `absent`, lalu menjadikannya
`kondisi_pondasi = severe_damage_or_absent`, dan nilai itu ikut terbawa sebagai
isian awal penilaian warga. Di `Warga_ruleset.php`, untuk warga desil 1-3 di
jalur rumah eksisting, satu isian kondisi bernilai itu sudah cukup untuk hasil
**layak RTLH** (`SIM_RTLH_DAMAGE`). Kalau dugaan butir 3 benar, rekaman yang
isian pondasinya sekadar belum diisi ikut dinilai rusak berat. Sebaliknya,
nilai 2-4 kini jatuh ke `unmapped_codes`, sehingga pondasi yang benar-benar
rusak sedang atau berat justru tidak terbaca. Kode BELUM diubah: perubahan ini menggeser hasil kelayakan,
sehingga menunggu konfirmasi dinas atau keputusan pemilik produk.
