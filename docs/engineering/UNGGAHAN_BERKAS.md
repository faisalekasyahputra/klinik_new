# Keamanan Berkas Unggahan - kuota dan pemindaian: kebijakan, prosedur, dan hasil

Menjawab dua butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 11 "Fungsi File"):

- **11.1** Mengatur jumlah file untuk setiap pengguna dan kuota ukuran file yang diunggah.
- **11.4** Melakukan pemindaian file yang diperoleh dari sumber yang tidak dipercaya.

(11.2 validasi tipe konten, 11.3 pelindungan metadata, dan 11.5 unduhan sesuai ekstensi sudah dijawab sebelumnya: whitelist ekstensi + MIME asli lewat `finfo`, nama acak, penyimpanan privat di luar webroot, pelucutan metadata gambar.)
Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Keadaan sebelum (21 Sep 2026)

| Temuan | Dampak |
|---|---|
| Hanya ada batas ukuran PER BERKAS (5 MB; SRP2 2 MB) dan batas laju permintaan bermuatan berkas | Satu pengguna dapat mengunggah berkas sebanyak-banyaknya dalam waktu panjang; disk hosting bersama dapat penuh |
| Isi berkas tidak pernah dibaca: hanya ekstensi dan MIME dari `finfo` | PDF dengan JavaScript/lampiran tertanam, gambar dengan kode PHP ditempel di ujungnya, XLSX bermakro atau bom zip lolos selama tipenya "benar" |
| Tidak ada antivirus di hosting (dicek: `clamscan`/`clamdscan` tidak ada) | Tidak ada pemindai eksternal yang bisa dipanggil |
| Impor Excel (peserta KKN, PSU) dan gambar beranda/katalog punya jalur unggah sendiri | Pemindaian di satu tempat saja akan meninggalkan jalur lain terbuka |
| **Temuan sampingan:** kolom berkas dokumen SRP2 (`form_1` ... `form_13`) tidak ada di allowlist `Input_guard` | Unggah dokumen pengembang SRP2 ditolak 400 (terbukti di lokal; allowlist tidak memuat kolom itu sejak `1ee0a0e`). Diperbaiki di rilis ini (allowlist + tes regresi) |

## 2. Yang dibangun

| Bagian | Isi | Butir |
|---|---|---|
| Kebijakan | `config/upload_policy.php`: kuota per peran, batas pemindaian | 11.1, 11.4 |
| Kuota per pengguna | `libraries/Upload_quota.php`. Kuota TOTAL (jumlah berkas dan total byte) per pengguna, lintas seluruh domain unggahan privat. Peran: warga 60 berkas/150 MB, mahasiswa 40/100 MB, pengembang 60/150 MB, admin 300/500 MB, bawaan 20/50 MB; **tamu** (aduan publik, diikat ke IP yang di-hash) 10 berkas/30 MB per 24 jam | 11.1 |
| Pencatatan tanpa migrasi | Buku kuota berupa penanda kosong per berkas di `private_uploads/_pemilik/{pengguna}/` yang menunjuk ke berkas sungguhan. **Pemakaian dihitung dari keadaan disk**: penanda yang berkasnya sudah tidak ada dibuang saat dihitung, sehingga tidak perlu kait di puluhan titik hapus (admin, ganti berkas, hapus akun, dsb). Ukuran yang dihitung adalah ukuran nyata di disk, bukan yang dilaporkan | 11.1 |
| Anti-balapan | Pemesanan jatah dilindungi `flock` per pengguna, dibuat SEBELUM berkas dipindah dan dilepas bila pemindahan gagal; dua unggahan bersamaan tidak sama-sama lolos melewati batas | 11.1 |
| Pemindai bawaan | `libraries/Upload_scanner.php` (tanpa ketergantungan CodeIgniter). Semua jenis: tanda tangan kode PHP, skrip HTML, pemanggilan fungsi eksekusi dinamis, string uji antivirus EICAR, shebang. **PDF**: penanda `%PDF-`, konten aktif (`/JavaScript`, `/JS`, `/Launch`, `/EmbeddedFile`, `/RichMedia`, `/XFA`, `/SubmitForm`, `/AA` dst.) termasuk nama yang disamarkan `#XX` dan yang disembunyikan di ObjStm terkompresi, PDF terenkripsi (tak dapat dipindai) ditolak. **XLSX/XLS**: makro VBA, objek OLE tertanam, tautan/relasi luar berbahaya, DOCTYPE/ENTITY XML, entri berjalur `../`, entri `.php`/`.exe`/dst., bom zip (total dan rasio). **Gambar**: tipe sebenarnya cocok dengan ekstensi, bom dimensi | 11.4 |
| ClamAV opsional | Bila env `CLAMD_ADDRESS` diisi (`unix:///...` atau `tcp://host:port`), berkas juga dikirim ke clamd (protokol INSTREAM). Bila diisi tetapi tidak terjangkau atau membalas tak dikenal, berkas **ditolak** (fail-closed). **Tidak aktif di production** karena hosting tidak menyediakan ClamAV | 11.4 |
| Peringatan | Berkas yang ditolak dicatat sebagai peringatan keamanan (`berkas_berbahaya`: jejak audit, banner admin; kode PHP/EICAR/antivirus = tingkat tinggi) dengan SHA-256 dan jenis temuan, tanpa isi berkas ([`ANTI_OTOMATISASI.md`](ANTI_OTOMATISASI.md)) | 11.4 |

### Titik unggah yang dipasangi

| Titik | Pemindaian | Kuota |
|---|---|---|
| `MY_Controller::store_private_upload()` (onboarding KTP/KTM/SIUP, kemitraan, penilaian warga, aduan, BNBA, surat balasan, laporan) | ya | ya |
| `Pengembang::simpan_dokumen()` (dokumen SRP2, banyak berkas per permintaan; jatah dipesan untuk SEMUA berkas sebelum satu pun dipindah) | ya | ya |
| Impor peserta KKN dan impor PSU (Excel; tidak disimpan) | ya | tidak relevan |
| Gambar katalog program dan hero beranda (admin, disimpan di webroot) | ya | tidak (hanya admin) |

`tests/upload_security_test.php` menggagalkan bila ada controller penerima unggahan yang tidak memakai pemindaian.

## 3. Sikap kegagalan (sengaja)

- **Pemindaian fail-closed**: galat pemindai, clamd tak terjangkau, PDF/ObjStm yang tidak dapat dipindai, dan PDF terenkripsi semuanya berarti berkas ditolak.
- **Kuota fail-closed**: buku kuota yang tidak dapat ditulis menolak unggahan (berbeda dari kontrol laju global yang fail-open, karena unggahan adalah aksi yang jarang dan berisiko, bukan jalur yang dilalui tiap halaman).
- Berkas yang ditolak tidak disimpan; hanya SHA-256 dan jenis temuannya yang dicatat.

## 4. Bukti

| Uji | Hasil |
|---|---|
| `tests/upload_security_test.php` (offline, 100 pemeriksaan) | Berkas sah lolos (JPEG, PNG, PDF biasa, XLSX, hyperlink eksternal biasa). **Tanpa positif palsu**: 25 kali data biner acak 2 MB ditempel ke JPEG sah, semuanya lolos (uji ini menangkap bug nyata saat penulisan: pola `<?=` polos menolak ~18% berkas biner 3 MB). Ditolak: kode PHP/short tag/skrip di gambar, EICAR, PDF berskrip (polos, disamarkan `#XX`, tersembunyi di ObjStm), PDF terenkripsi/berlampiran/ObjStm tak terbaca, ketidakcocokan tipe, XLSX bermakro/OLE/tautan luar/XXE/`../`/`.php`, XLS bermakro, bom zip dan bom dimensi. ClamAV: server clamd palsu di 127.0.0.1 (bersih, temuan, berkas > 64 KB dalam banyak potongan, balasan sampah, tak terjangkau, alamat tidak valid). Kuota: batas jumlah dan ukuran, ukuran nyata di disk, swa-pulih saat berkas dihapus, jendela tamu, jalur `../`, pembersihan akun, dan **konkurensi: 8 proses serentak x 5 percobaan pada batas 10 berkas meloloskan tepat 10 dari 40** |
| Uji mutasi | Menghapus `flock` membuat tes merah (lolos 13, bukan 10); membuang `JavaScript` dari daftar PDF aktif membuat tes merah; membuang pemindaian dari `store_private_upload` membuat tes merah |
| Uji HTTP lokal (akun uji) | Aduan dengan JPEG sah diterima dan tercatat di buku kuota; aduan dengan PDF berskrip: aduan tetap tersimpan, lampiran ditolak dengan pesan yang menjelaskan, peringatan `berkas_berbahaya` tercatat. Unggah dokumen pengembang: dua PDF sah dalam satu permintaan diterima, PDF berskrip ditolak, dan sesudah buku kuota diisi sampai 60 berkas, unggahan ke-61 ditolak dengan pesan kuota dan tidak ada berkas tersimpan |
| Pemindai kode berbahaya | Bersih; soket clamd di `Upload_scanner` ditinjau dan dimasukkan ke daftar izin dengan alasan (hanya membuka koneksi bila `CLAMD_ADDRESS` diisi) |
| Suite harness lain | Suite yang gagal di lokal (login, data Sikumbang, dsb.) dibandingkan dengan baseline tanpa perubahan ini: hasilnya sama (bukan disebabkan rilis ini) |

## 5. Prosedur operasional

1. **Menyetel kuota**: ubah `config/upload_policy.php` lalu jalankan `tests/upload_security_test.php`. Pengguna yang penuh diberi tahu lewat pesan pada unggahan berikutnya; berkas yang dihapus membebaskan kuota otomatis (paling lambat sesudah masa pemesanan 2 menit).
2. **Mengaktifkan ClamAV** (bila hosting/VPS kelak menyediakannya): pasang clamd, set env `CLAMD_ADDRESS`, lalu unggah berkas uji EICAR lewat formulir dan pastikan ditolak dengan peringatan. Tanpa itu pemindai bawaan saja yang berjalan.
3. **Berkas sah ditolak**: pesan ke pengguna menjelaskan penyebabnya (mis. PDF berkata sandi, Excel bermakro). Jenis temuan ada di detail peringatan di Jejak Audit (`?aksi=peringatan_keamanan`).
4. **Titik unggah baru** wajib lewat `store_private_upload()` (otomatis dipindai dan dikuota) atau memanggil `scan_uploaded_file()`; tes menggagalkan bila tidak.

## 6. Batas yang diakui

- **Ini bukan antivirus berbasis tanda tangan yang menyeluruh.** Pemindai bawaan menangkap kelas serangan yang relevan bagi aplikasi ini (kode/skrip yang diselundupkan, konten aktif di PDF dan Excel, bom kompresi), bukan semua malware. Malware biner yang berbentuk gambar/PDF yang sah secara struktur tidak akan dikenali. Lapis ClamAV tidak aktif di production.
- **PDF terenkripsi dan PDF dengan ObjStm berfilter selain Flate ditolak** karena tidak dapat dipastikan aman; pengguna sah yang membawa PDF seperti itu harus mencetak ulang menjadi PDF biasa.
- **Kuota berlaku untuk unggahan baru.** Berkas yang diunggah sebelum rilis ini tidak punya penanda dan belum dihitung; kuota terisi seiring unggahan baru.
- **Kuota tamu diikat ke IP**, sehingga pengguna yang berbagi satu IP publik berbagi jatah; jendelanya 24 jam supaya tidak menutup akses selamanya.
- Gambar katalog dan beranda (admin) dipindai tetapi tidak dikuota; hanya admin yang dapat mengunggahnya.
- **Jumlah berkas per permintaan** dibatasi PHP (`max_file_uploads`), bukan oleh kebijakan ini; kuota total per pengguna yang menjadi batas efektifnya.
- Peringatan penolakan yang sama dari IP yang sama ditekan 30 menit (perilaku `Security_alert`), jadi satu peringatan dapat mewakili banyak percobaan.
- Berkas uji EICAR tidak dapat diuji lewat HTTP di mesin dev yang antivirusnya menghapus berkas itu; pengujian EICAR dilakukan di tingkat pustaka.
