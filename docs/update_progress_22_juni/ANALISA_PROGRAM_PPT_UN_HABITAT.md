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

*(Catatan Logika Filter: Pengguna di Desil 1-4 difokuskan pada skema bantuan hibah/sosial, sedangkan pengguna Desil 5-8 diarahkan ke skema pembiayaan bersubsidi. Desil 4 secara khusus menjadi target utama untuk program swadaya "Omah Sekeng").*

---

## 2. Definisi Etalase 5 Program Utama (Ngopeni Omah Nglakoni Sesarengan)

### A. Program KPR-FLPP Rumah Subsidi
- **Definisi:** Skema pembiayaan perumahan subsidi bagi MBR (Masyarakat Berpenghasilan Rendah).
- **Syarat & Ketentuan:** Bunga *flat* tetap sebesar 5% sepanjang tenor, Uang Muka (DP) sangat ringan mulai 1%, dengan pilihan tenor (jangka waktu angsuran) panjang hingga 20 tahun.
- **Sumber Anggaran:** APBN (Dialokasikan sekitar 5.373 unit per tahun).

### B. Program Oemah Lestari
- **Definisi:** Program fasilitas pembiayaan rumah murah khusus bagi MBR.
- **Syarat & Ketentuan:** Menggunakan skema kredit bunga ringan (8% *flat*), dengan tenor penyicilan maksimal hingga 15 tahun.
- **Nilai Tambah:** Bangunan dirancang secara khusus untuk memenuhi kaidah "Bangunan Hijau" sesuai amanat _Sustainable Development Goals_ (SDG). Terselenggara atas kolaborasi penuh dengan pembiayaan BPR-BKK.

### C. Peningkatan Kualitas RTLH (Rumah Tidak Layak Huni)
- **Definisi:** Program perbaikan atau renovasi rumah bagi masyarakat miskin yang rumahnya masuk kategori tidak layak. Melibatkan skema _Bankeupemdes RTLH_ (Bantuan Keuangan kepada Pemerintah Desa) sebesar Rp 20 Juta / penerima.
- **Kriteria Penerima Utama:** Terdaftar dalam Database Kemiskinan (DTKS / BDT / Data Kesejahteraan Sosial). 
- **Kondisi Rumah:** Dinyatakan tidak layak jika memenuhi 2 dari 3 unsur rusak: (1) Atap (seng/asbes rusak, rangka kayu jelek), (2) Lantai (tanah, kayu kualitas rendah), (3) Dinding (bilik bambu/kayu lapuk).
- **Mekanisme Eksekusi:** Swadaya Padat Karya (3 orang pekerja lokal selama 6 hari dibiayai dari dana BOP bantuan).

### D. Bantuan Stimulan Pembangunan Rumah Baru (PB)
Terbagi menjadi 3 skema penanganan strategis dengan besaran bantuan material bangunan sebesar **40 Juta Rupiah**. Berikut adalah pemetaan kondisi warga (*Decision Tree*):

1. **PB Backlog:** 
   - **Kondisi Saat Ini:** Warga yang menumpang di rumah orangtua/saudara (1 rumah dihuni >1 KK), ATAU warga yang saat ini masih sewa/kontrak rumah.
   - **Bentuk Intervensi:** Pembangunan rumah baru di lahan milik sendiri yang sah.
2. **PB Relokasi:** 
   - **Kondisi Saat Ini:** Warga yang bersedia keluar dari Rusunawa, penghuni squatter/liar (sempadan sungai, pemakaman, lahan pemerintah), warga di Kawasan Rawan Bencana (longsor, gerakan tanah), atau warga di Kawasan Kumuh & Pesisir rob.
   - **Bentuk Intervensi:** Bantuan stimulan struktur beton _precast_ RUSPIN dan arsitektur untuk dibangun di lahan aman. Bisa dieksekusi secara Komunitas maupun Mandiri.
3. **PB Bencana:** 
   - **Kondisi Saat Ini:** Korban bencana yang rumahnya mengalami kerusakan berat.
   - **Bentuk Intervensi:** Pembangunan kembali rumah korban bencana.

### E. Program Rumah Apung
- **Definisi:** Program inovasi desain pembangunan rumah adaptif untuk bertahan dari genangan air. 
- **Konteks Masalah:** Merupakan solusi *NO ONE LEFT BEHIND* (Tidak Ada yang Tertinggal) bagi masyarakat pesisir yang wilayahnya mengalami banjir Rob secara permanen dan penurunan muka tanah. Terutama jika ketinggian air rob sudah di angka ekstrem (180 cm s.d 300 cm).
- **Target Utama:** Desa Timbulsloko, Kec. Sayung, Kabupaten Demak.

---

## 3. Matriks Skema Pembiayaan Khusus: OMAH SEKENG

Berdasarkan Halaman 72 & 73, **Omah Sekeng** ("Tuku Lemah Oleh Omah") ditujukan khusus untuk pendaftar di **Desil 4**.
- **Konsep Kolaborasi:** Bansos Pemprov Jateng + BAZNAS Jateng + ESDM Prov Jateng = Total Bantuan Kolaborasi **Rp 45.000.000 (Konstan / All-Type)**.

**Tabel Estimasi Biaya Rumah & Harga Jual Akhir:**
| Type Rumah | Harga Dasar Rumah (Rp) | Bantuan CSR/Pemprov (Rp) | Harga Akhir Masyarakat (Rp) |
|---|---|---|---|
| 27/60 | 112.000.000 | 45.000.000 | **67.000.000** |
| 27/72 | 117.000.000 | 45.000.000 | **72.000.000** |
| 30/60 | 115.000.000 | 45.000.000 | **70.000.000** |
| 30/62 | 115.000.000 | 45.000.000 | **70.000.000** |
| 30/72 | 120.000.000 | 45.000.000 | **75.000.000** |

Data tabel di atas akan menjadi informasi interaktif yang krusial ketika masyarakat menekan tombol detail pada *Card Omah Sekeng* di halaman Beranda.

---
*Dokumen dirangkum dari ekstrasi teks PPT UN HABITAT (Hal 1-123) dan analisis manual visual infografis dari pengguna.*
