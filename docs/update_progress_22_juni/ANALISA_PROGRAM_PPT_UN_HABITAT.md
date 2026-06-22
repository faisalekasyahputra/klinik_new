# Analisa Kebutuhan & Data Intervensi Perumahan
*(Berdasarkan Ekstraksi Data PPT UN HABITAT 2026)*

Dokumen ini adalah ekstraksi logis dari PPT UN HABITAT yang berfungsi sebagai landasan logika perhitungan, parameter filter, dan basis data (*knowledge base*) untuk fitur **Smart Filter & Etalase Program** di Klinik PKP.

---

## 1. Parameter "Smart Filter" Berdasarkan Desil Pendapatan
Logika utama sistem saat pendaftar memasukkan data penghasilan (Berdasarkan Gambar Piramida Desil):

| Desil | Range Pendapatan / Bulan | Klasifikasi | Rekomendasi Program / Intervensi |
|---|---|---|---|
| **Desil 1** | Rp 0 - 1.500.000 | Miskin Terbawah | Bansos PB Pembangunan Baru, Peningkatan Kualitas RTLH |
| **Desil 2** | Rp 1.500.000 - 2.200.000 | MBR Non-Fixed Income | Bansos PB Pembangunan Baru, Peningkatan Kualitas RTLH |
| **Desil 3** | Rp 2.200.000 - 2.800.000 | MBR Non-Fixed Income | Bansos PB Pembangunan Baru, Peningkatan Kualitas RTLH |
| **Desil 4** | Rp 2.800.000 - 3.400.000 | MBR Non-Fixed Income | **Omah Sekeng**, Bansos PB Pembangunan Baru |
| **Desil 5** | Rp 3.400.000 - 4.100.000 | MBR Fixed Income | KPR-FLPP Subsidi, Oemah Lestari Subsidi |
| **Desil 6** | Rp 4.100.000 - 5.000.000 | MBR Fixed Income | KPR-FLPP Subsidi, Oemah Lestari Subsidi |
| **Desil 7** | Rp 5.000.000 - 6.200.000 | MBR Fixed Income | KPR-FLPP Subsidi, Oemah Lestari Subsidi |
| **Desil 8** | Rp 6.200.000 - 6.700.000 | MBR Fixed Income | KPR-FLPP Subsidi, Oemah Lestari Subsidi |
| **Desil 9** | Rp 8.100.000 - 12.700.000 | MBM & MBA | Oemah Lestari Non-Subsidi |
| **Desil 10** | > Rp 12.700.000 | MBM & MBA | Oemah Lestari Non-Subsidi |

*(Catatan Logika Filter: Pengguna di Desil 1-4 difokuskan pada skema bantuan hibah/sosial, sedangkan pengguna Desil 5-8 diarahkan ke skema pembiayaan bersubsidi).*

---

## 2. Definisi Etalase 5 Program Utama (Ngopeni Omah Nglakoni Sesarengan)

Data ini akan menjadi konten *copywriting* utama pada 5 Card Program di halaman Beranda:

### A. Program KPR-FLPP Rumah Subsidi
- **Definisi:** Skema pembiayaan perumahan subsidi bagi MBR (Masyarakat Berpenghasilan Rendah).
- **Syarat Utama:** Bunga *flat* 5%, Uang Muka (DP) mulai 1%, Tenor panjang hingga 20 tahun.

### B. Program Oemah Lestari
- **Definisi:** Program fasilitas pembiayaan rumah murah bagi MBR hasil kolaborasi dengan "BPR-BKK".
- **Syarat Utama:** Bunga ringan (8% *flat*), Tenor 15 tahun.
- **Nilai Tambah:** Memenuhi kaidah "Bangunan Hijau" (Amanat SDG).

### C. Peningkatan Kualitas RTLH
- **Definisi:** Program perbaikan atau renovasi Rumah Tidak Layak Huni bagi masyarakat miskin.
- **Kriteria Penerima:** Masuk dalam Database Kemiskinan (DTKS / BDT).
- **Fokus Perbaikan:** Komponen Atap, Lantai, dan Dinding (Aladin).

### D. Bantuan Stimulan Pembangunan Rumah Baru
Program ini memiliki 3 sub-langkah strategis:
1. **PB Backlog:** Bantuan rumah bagi warga yang numpang di lahan lebih dari 1 KK, namun memiliki lahan sah.
2. **PB Relokasi:** Bantuan stimulan bagi masyarakat terdampak relokasi program pemerintah.
3. **PB Bencana:** Bantuan stimulan pembangunan rumah bagi masyarakat terdampak bencana (kerusakan berat).

### E. Program Rumah Apung
- **Definisi:** Pembangunan rumah adaptif sebagai solusi inovatif mengatasi permasalahan banjir rob permanen.
- **Target Wilayah:** Wilayah pesisir (Contoh utama: Timbulsloko, Sayung, Kab. Demak).

---

## 3. Matriks Sumber Pendanaan & Kolaborasi (Data Backend)

Untuk keperluan pencatatan dan pendataan kuota di dalam database, sistem harus memisahkan program berdasarkan sumber dananya:
1. **APBN:** KPR FLPP, BSPS.
2. **APBD Provinsi:** Bansos PB Relokasi, Bansos PB Bencana, PB Backlog, Omah Sekeng, Bankeupemdes RTLH.
3. **APBD Kabupaten/Kota:** PB, RTLH.
4. **CSR (Corporate Social Responsibility):** Bank Jateng, BAZNAS, Budha Tzu Chi, Astra, Djarum.

---
*Dokumen dirangkum dari ekstrasi teks PPT UN HABITAT (Hal 1-123) dan analisis manual infografis visual.*
