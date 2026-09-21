# Kontrol Anti-Otomatisasi dan Peringatan Serangan - kebijakan, prosedur, dan hasil

Menjawab dua butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 10):

- **10.4** Membantu dalam kontrol anti otomatisasi.
- **10.5** Memberikan peringatan ketika terjadi serangan otomatis atau aktivitas yang tidak biasa.

Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Keadaan sebelum (21 Sep 2026)

| Temuan | Dampak |
|---|---|
| Pembatas laju hanya dipanggil oleh endpoint yang dipilih satu per satu (login, pencarian tiket, beberapa aksi admin) | Sebagian besar dari 45 controller tidak punya batas apa pun: pengambilan data massal, pencarian, ekspor, dan API dapat dipanggil tanpa batas |
| Verifikasi Google reCAPTCHA di login/registrasi dilewati bila kunci situs kosong, dan di production kuncinya kosong | Login dan registrasi tidak punya tantangan bot sama sekali, hanya batas laju |
| Pelampauan batas hanya menghasilkan satu baris log TERENKRIPSI dan header untuk si penyerang | Tidak ada administrator yang tahu ada serangan kecuali membuka dan mendekripsi berkas log |
| Tidak ada pendeteksi alat pemindai, jalur jebakan, atau ringkasan aktivitas mencurigakan | Pemindaian kerentanan dan tebak-jalur tidak terlihat |

## 2. Yang dibangun

| Bagian | Isi | Butir |
|---|---|---|
| Kontrol global | `MY_Controller::__construct()` memanggil `enforce_anti_automation()`; seluruh 45 controller berada di bawah `MY_Controller`, jadi SEMUA endpoint PHP terkena. Urutan: (1) batas global per identitas (akun bila login, IP bila anonim), (2) batas tulis untuk POST/PUT/PATCH/DELETE, (3) batas per kelas rute (pencarian, API, ekspor/unduh), (4) batas unggahan. Kebijakan: `config/rate_limits.php`; pola kelas: `config/anti_automation.php` | 10.4 |
| Pencacah atomik | `Rate_limiter::hit_fast()`: satu kueri per dimensi (`INSERT ... ON DUPLICATE KEY UPDATE` dengan `LAST_INSERT_ID(ekspresi)`), tanpa celah antara "naikkan" dan "baca"; dua permintaan bersamaan tidak pernah mendapat angka yang sama | 10.4 |
| Blok alat serangan | User-Agent alat serangan yang dikenal (sqlmap, nikto, nmap, gobuster, wpscan, dsb) dijawab 403 dan dicatat. Sengaja sempit: curl, python-requests, dan bot mesin pencari TIDAK diblokir | 10.4, 10.5 |
| Jalur jebakan | Alamat yang tak pernah dipakai aplikasi tetapi selalu dicoba pemindai (`wp-login.php`, `phpmyadmin`, `xmlrpc.php`, dst.) dipetakan ke `Jebakan::index`: dicatat sebagai peringatan, dijawab 404 biasa | 10.4, 10.5 |
| Tantangan bot untuk formulir | `Bot_guard`: kolom honeypot tersembunyi + token HMAC berwaktu pada login dan registrasi (lima formulir). Tidak bergantung layanan pihak ketiga. Honeypot ditegakkan di semua lingkungan; token (wajib ada, usia maksimum 6 jam, minimum 1 detik) hanya di production supaya 49 suite uji HTTP proyek yang mengirim formulir seketika tetap jalan | 10.4 |
| Peringatan ke admin | `Security_alert`: setiap peringatan masuk `sys_jejak_audit` (aksi `peringatan_keamanan`; terbaca dan tersaring di layar Jejak Audit superadmin), diringkas dalam banner di dasbor superadmin (24 jam terakhir), dan yang bertingkat `tinggi` dikirim proaktif sebagai Web Push ke admin | 10.5 |
| Pengendali kebisingan | Peringatan yang sama dari IP yang sama ditekan 30 menit (`alert_dedupe`); tiga peringatan berbeda dari satu IP dalam satu jam dieskalasi SEKALI menjadi peringatan `tinggi` (`alert_eskalasi`) | 10.5 |
| Sumber peringatan | pelampauan batas laju (pertama dalam jendela), User-Agent pemindai, jalur jebakan, honeypot/token formulir, penguncian akun akibat gagal login beruntun, eskalasi | 10.5 |

Batas bawaan (per menit, karena kolom penghitung hanya menampung 255): global 240 per identitas; tulis 40 per IP anonim / 120 per akun; pencarian 60; API 40; unduhan 40; unggahan 20 per 10 menit per IP / 40 per akun.

## 3. Sikap kegagalan (sengaja)

- **Jalur global fail-open**: bila penyimpanan pembatas laju gagal, permintaan dilanjutkan dan kegagalannya dicatat. Jalur ini dilalui setiap halaman; gangguan DB sesaat tidak boleh menjadi pemadaman seluruh situs oleh pengamatnya sendiri.
- Pembatas per-endpoint lama (login, aksi sensitif) tetap **fail-closed**.
- Semua pengiriman peringatan gagal diam-diam: pengamat tidak boleh menggagalkan permintaan yang sedang dijaga. Web Push ditunda sampai respons selesai dikirim.
- **Pengecualian**: loopback dan IP di env `ANTI_OTOMATISASI_IP_DIIZINKAN` (dipisah koma, IP persis) tidak terkena kontrol global. Untuk kantor/kampus yang berbagi satu IP publik; keputusan operasional yang dicatat, bukan bawaan.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/anti_automation_test.php` (offline, 162 pemeriksaan) | Deteksi pemindai (klien sah lolos), kelas rute, pengecualian IP, konsistensi kebijakan (limit 1..255, tiap kelas punya `_ip` dan `_akun`), semua cabang `Bot_guard` termasuk token dipalsukan/kedaluwarsa/terlalu cepat/formulir salah, tiap jalur jebakan punya rute, pemasangan di `MY_Controller`/`Auth`/lima formulir/dasbor, dan bahwa 45 controller berada di rantai `MY_Controller`. Diuji dengan mutasi: menghapus pemanggilan di konstruktor membuatnya merah |
| `tests/anti_automation_db_test.php` (MySQL nyata) | Semantik hitungan, penolakan di atas batas, reset jendela, tutup di 255, dan **konkurensi: 8 proses PHP sungguhan x 15 permintaan serentak pada satu kunci menghasilkan persis permutasi 1..120**. Pencacah "baca lalu tulis" yang naif dijalankan dengan cara sama dan terbukti menghasilkan hitungan ganda (uji ini bisa merah). Diuji dengan mutasi: menghapus `LAST_INSERT_ID` membuat hitungan `1,1,1,1,1` dan uji merah |
| Uji HTTP lokal dari alamat LAN (bukan loopback) | Burst 90 GET paralel ke rute kelas API: 40 diterima, 50 dijawab 429 dengan `Retry-After` dan `X-Security-Warning`, dan tercatat di jejak audit. User-Agent sqlmap: 403. `/wp-login.php`, `/phpmyadmin`, `/xmlrpc.php`: 404 + peringatan. Formulir login dengan honeypot terisi ditolak + peringatan. Tiga peringatan berbeda menghasilkan satu eskalasi `tinggi`. curl biasa dan UA peramban: 200 |
| Situs live sesudah rilis (21 Sep 2026) | Burst 70 GET paralel ke kelas API: 42 diterima, 28 dijawab 429 dengan `Retry-After` dan `X-Security-Warning`; `/wp-login.php` dan `/phpmyadmin`: 404; login tanpa token ditolak (`token_hilang`), login dengan token sah dan jeda 3 detik diproses normal; server mencatat 4 peringatan di jejak audit (jalur jebakan, batas laju, formulir, satu eskalasi `tinggi`); pemindai `deploy` bersih, `uji_tls_situs.php` 21 lulus 0 gagal, kedua tes offline hijau di server. **Catatan:** permintaan ber-User-Agent sqlmap ke situs live dijawab 403 oleh platform hosting sebelum mencapai aplikasi (halaman 403 milik platform), jadi blok UA milik aplikasi hanya terbukti di lokal |
| Pemindai kode berbahaya (`pindai_kode_berbahaya.php`) | Bersih; `register_shutdown_function` di `Security_alert` ditinjau dan dimasukkan ke daftar izin dengan alasan |

## 5. Prosedur operasional

1. **Melihat peringatan**: dasbor superadmin menampilkan banner bila ada peringatan 24 jam terakhir; rinciannya di Jejak Audit (`?aksi=peringatan_keamanan`), kolom Objek = jenis peringatan, ID objek = tingkat.
2. **Bila pengguna sah terkena batas** (mis. satu kantor di belakang satu IP): tambahkan IP publik kantor itu ke env `ANTI_OTOMATISASI_IP_DIIZINKAN`; jangan menaikkan batas global untuk semua orang.
3. **Menyetel batas**: ubah `config/rate_limits.php` (limit maksimum 255, jendela per menit untuk kebijakan global) lalu jalankan kedua tes di atas.
4. **Menambah jalur jebakan atau tanda tangan pemindai**: `config/anti_automation.php`; tes memastikan tiap jalur punya rute.
5. **Endpoint atau controller baru** otomatis dijaga bila mewarisi `MY_Controller` (tes menggagalkan bila tidak).

## 6. Batas yang diakui

- **Bukan pertahanan DDoS.** Batas di lapisan aplikasi menahan penyalahgunaan dan pengambilan data otomatis; banjir volumetrik harus ditahan di depan aplikasi (jaringan hosting/CDN).
- **Penghitung per IP.** Pengguna sah yang berbagi satu IP publik dapat terkena batas bersama; jalan keluarnya daftar IP izin (bagian 3). Penyerang dengan banyak IP tidak tertahan oleh batas per IP (batas per akun tetap berlaku bagi yang login).
- **Kolom penghitung 255** membatasi batas maksimum dan memaksa jendela per menit; batas per jam/hari hanya dipakai kebijakan lama yang batasnya kecil.
- **Honeypot dan token waktu menahan skrip sederhana**, bukan peramban otomatis yang lengkap (headless browser yang memuat halaman, menunggu, lalu mengirim). Google reCAPTCHA tetap tidak aktif di production selama kunci situsnya kosong; bila kunci diisi, verifikasinya di `Auth` langsung aktif dan berlaku berdampingan.
- **"Aktivitas tidak biasa" dideteksi untuk pola yang terdefinisi** (lonjakan permintaan, alat pemindai, jalur jebakan, isian honeypot, penguncian akun, pengulangan dari satu IP). Tidak ada pembelajaran anomali umum.
- **Peringatan Web Push hanya sampai ke admin yang mengaktifkan notifikasi** di peramban mereka; jejak audit dan banner dasbor tetap ada untuk yang tidak. Belum ada kanal email/SMS.
- **Deteksi berbasis User-Agent bisa dielakkan** (penyerang mengganti UA); ia menangkap alat yang dijalankan tanpa penyesuaian, bukan penyerang yang gigih. Lapisan lain (batas laju, jalur jebakan) tidak bergantung pada UA.
- Peringatan yang sama dari IP yang sama ditekan 30 menit; jumlah kejadian sebenarnya dalam periode itu tidak dihitung ulang di jejak audit.
