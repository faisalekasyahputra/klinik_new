# Catatan Perbaikan UAT Klinik PKP

Sumber: [User Acceptance Test - Klinik PKP 2026](https://docs.google.com/spreadsheets/d/1_J5CNB8KaPl7vrNIuQzF3Kl0l8OmhQoOeOTGyLYwAwo/edit).

Tabel memakai 10 kolom asli dan hanya mencatat butir yang telah diperbaiki. Actual Result berisi hasil pengujian setelah perbaikan; lingkungan uji dan status deploy dicatat pada Keterangan. File sumber tidak diubah.

## no login

| No | Menu | Sub Menu | Sub Sub Menu | Tested Function | How to Test | Expected Result | Actual Result | Status | Keterangan |
|---|---|---|---|---|---|---|---|---|---|
| 9 | Sertifikasi Pengembang | | | | | Muncul Menu Pilihan<br>1. Daftar Pengembang Tersertifikasi<br>2. Syarat dan Ketentuan SRP2<br>3. Formulisr Pendaftaran SRP2 | Klik kartu Sertifikasi Pengembang di beranda membuka menu Daftar Pengembang Tersertifikasi, Syarat & Ketentuan SRP2, dan Formulir Pendaftaran SRP2. | Pass | 10 Sep 2026: diuji di localhost lewat browser tanpa login. Tujuan kartu diperbaiki ke tab/pengembang. PHP lint dan git diff --check lolos. Deploy 10 Sep 2026, commit 87d8a20: HEAD dan working tree server terverifikasi lewat SSH; halaman/menu production lolos uji HTTP. |
| 14 | Rekam Data | | | | Klik Lihat | Muncul Permintaan Login | Pengunjung tanpa sesi diarahkan ke Auth/login dan mendapat formulir login serta pesan Silakan masuk terlebih dahulu untuk membuka Rekam Data. | Pass | 10 Sep 2026: perbaikan sudah ada pada commit a651f8d; diverifikasi ulang di localhost melalui sesi HTTP tamu terpisah. Navigasi langsung dan request AJAX keduanya menuju login HTTP 200; redirect awal 307. Tidak ada perubahan kode tambahan. Klik modal di browser belum diuji karena browser sedang login sebagai pengembang. Production diverifikasi 10 Sep 2026 pada commit 87d8a20: akses tamu menuju formulir login HTTP 200. |
| 15 | Konsultasi | | | | Klik Lihat | Muncul Permintaan Login | Klik Konsultasi dari beranda tanpa login menampilkan modal login. Akses URL langsung diarahkan ke halaman login dengan pesan Silakan masuk terlebih dahulu untuk membuka Konsultasi. | Pass | 10 Sep 2026: ditambahkan gerbang login di Umum::forum(). Uji browser sebagai tamu membuktikan modal muncul; akses langsung juga membuka login. Uji HTTP navigasi langsung dan AJAX: 2 gagal sebelum perbaikan, 2 lulus sesudahnya (uji_konsultasi_tamu.php). PHP lint lolos. Deploy 10 Sep 2026, commit 87d8a20: HEAD server terverifikasi lewat SSH; rute production lolos uji HTTP. Uji klik browser pada baris ini dilakukan di lokal. |
| 16 | KKN dan Magang | | | | Klik Lihat | Muncul Permintaan Login | Klik KKN dan Magang dari beranda tanpa login menampilkan modal login. Akses langsung dan AJAX diarahkan ke formulir login dengan pesan Silakan masuk terlebih dahulu untuk membuka KKN dan Magang. | Pass | 10 Sep 2026: ditambahkan gerbang login di KemitraanPortal::index() dan petunjuk halaman pilihan disesuaikan. Uji browser tamu membuktikan modal muncul. Dua uji HTTP KKN/Magang gagal sebelum perbaikan dan lulus sesudahnya; dua uji Konsultasi tetap lulus. PHP lint lolos. Deploy 10 Sep 2026, commit 87d8a20: HEAD server terverifikasi lewat SSH; rute production lolos uji HTTP. Uji klik browser pada baris ini dilakukan di lokal. |
| 17 | Bank Data | Daftar Bank Data | List Bank Data | Menampilkan Bank Data | Klik Lihat | Muncul<br>1. Card untuk buku data<br>2. Card untuk Inforgrasis<br>3. dll<br>setelah di klik baru muncul flip | Bank Data menampilkan kartu Buku Data dan Statistik & Infografis. Klik Buku Data membuka flipbook; halaman pertama tergambar dan tombol Berikutnya menampilkan halaman kedua. Kartu Statistik & Infografis membuka halaman statistik yang tersedia. | Pass | 10 Sep 2026: uji browser lokal tanpa login. Kartu sudah ada; perbaikan menghapus viewer tersembunyi yang menghasilkan halaman kosong dan mengarahkan Buku Data ke Dokumen. Tombol kembali mengembalikan kartu. PHP lint lolos. PDF masih contoh 6 halaman; statistik masih simulasi, belum data resmi. Deploy 10 Sep 2026, commit 87d8a20: HEAD server terverifikasi lewat SSH; rute production lolos uji HTTP. Uji klik browser pada baris ini dilakukan di lokal. |

## Posisi pekerjaan

Tertunda: sheet no login, No. 12 — menunggu informasi status publik dari dinas. Belum diperbaiki atau ditandai Pass.

Catatan keputusan user, 10 Sep 2026:

- Kolom Wilayah Pengembang tetap ditampilkan.
- Tidak ada informasi tentang label dan susunan status publik yang benar; patut ditanyakan ke dinas. Jangan menganggap tampilan saat ini sudah disetujui.
- Pertanyaan untuk dinas: kolom Status harus menunjukkan tahapan sertifikasi atau Berlaku/Tidak berlaku? Kolom Masa Berlaku harus berisi tanggal, penanda Berlaku/Tidak berlaku, atau keduanya? Bagaimana menampilkan sertifikat yang tanggal masa berlakunya belum tercatat?
- Temuan kode untuk ditinjau setelah ketentuan jelas: tanggal akhir kosong saat ini dapat menghasilkan label Berlaku. Ini belum dinyatakan selesai diperbaiki.

Butir berikutnya yang dapat diperiksa: sheet no login, No. 18 — hasil Cek Data Rumah tanpa login dan efek select.

Setiap butir berikutnya ditambahkan ke tabel sheet terkait sesudah diperbaiki dan diuji. Butir yang belum dikerjakan tetap mengacu ke spreadsheet sumber.
