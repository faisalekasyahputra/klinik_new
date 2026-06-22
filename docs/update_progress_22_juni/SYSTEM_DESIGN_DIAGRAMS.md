# Desain Sistem: Flowchart & ERD Klinik PKP v2

Dokumen ini merumuskan secara visual bagaimana sistem baru (Onboarding & Housing Queue) akan bekerja dan bagaimana struktur databasenya akan saling terhubung.

## 1. Flowchart: Alur Pengguna (Onboarding Journey)

Berikut adalah _flowchart_ yang menggambarkan perjalanan masyarakat dari melihat "iklan" di beranda hingga data mereka masuk ke dalam antrean (Housing Queue).

```mermaid
graph TD
    A(["Masyarakat Kunjungi Web"]) --> B["Lihat Hero Slider & Fungsi Utama PKP"]
    B --> C{"Pilih Layanan Utama?"}

    C -->|"Perumahan"| D["Tampil Etalase Program Perumahan"]
    C -->|"Kawasan"| E["Tampil Data Spasial"]
    C -->|"Pertanahan"| F["Halaman Pertanahan"]

    D --> G["Lihat Syarat & Kriteria Program (RTLH / PB / KPR)"]
    G --> H{"Tertarik Mengajukan?"}

    H -->|"Ya"| I["Klik Daftar / Cek Kelayakan"]
    H -->|"Tidak"| D

    I --> J["Form Input NIK"]
    J --> K[["Proses API SIMPERUM"]]

    K --> L{"NIK Ditemukan & Valid?"}
    L -->|"Ya, Ambil Data SIMPERUM"| M["Form Data Diri Terisi Otomatis Sebagian"]
    L -->|"Tidak, Data Baru"| N["Form Data Diri Kosong"]

    M --> O["Pengguna Melengkapi Data Kurang"]
    N --> O

    O --> P["Sistem Melakukan Smart Filter Kelayakan Dasar"]
    P --> Q{"Lolos Syarat Dasar?"}

    Q -->|"Ya"| R["Simpan sebagai Status Housing Queue"]
    Q -->|"Tidak"| S["Sistem Menawarkan Program Lain yang Relevan"]

    R --> T[["Validasi Manual oleh ASN/Admin (Audit)"]]
    T --> U{"Disetujui?"}

    U -->|"Ya"| V(["Masuk Antrean Realisasi SIMPERUM"])
    U -->|"Tolak / Kuota Penuh"| W(["Tertahan di Housing Queue Tahun Depan"])
```

---

## 2. ERD (Entity Relationship Diagram) Database Baru

Karena ada konsep _Housing Queue_ dan _Smart Filter_, kita perlu memperbarui desain database. Berikut adalah relasi antar tabel (ERD) utamanya:

```mermaid
erDiagram
    USERS ||--o{ HOUSING_QUEUE : "mengajukan"
    PROGRAMS ||--o{ HOUSING_QUEUE : "diajukan_ke"
    PROGRAM_KATEGORI ||--|{ PROGRAMS : "membawahi"

    USERS {
        int id PK
        string nik UK
        string nama_lengkap
        string alamat
        string no_hp
        string role
        datetime created_at
    }

    PROGRAM_KATEGORI {
        int id PK
        string nama_kategori
        string deskripsi
    }

    PROGRAMS {
        int id PK
        int kategori_id FK
        string nama_program
        text syarat_kriteria
        string foto_before_after
        boolean is_active
    }

    HOUSING_QUEUE {
        int id PK
        int user_id FK
        int program_id FK
        string status_antrean
        string catatan_admin
        int tahun_pengajuan
        datetime created_at
    }

    SP2_PENGEMBANG {
        int id PK
        string nama_pt
        string no_sertifikat
        string status_sp2
    }
```

### Penjelasan Tabel Utama:

1. **`USERS`**: Menggunakan `nik` (Unique Key / UK) sebagai entitas paling krusial untuk sinkronisasi dengan API SIMPERUM.
2. **`PROGRAM_KATEGORI` & `PROGRAMS`**: Memisahkan antara "Kategori" (Pembangunan Baru) dengan "Program Spesifik" (Rumah Relokasi, RTLH). Hal ini membuat _dashboard/etalase iklan_ menjadi sangat dinamis. Admin bisa menambah program baru tanpa perlu _hard-code_.
3. **`HOUSING_QUEUE`**: Ini adalah "keranjang" antrean. Berisi riwayat pengajuan masyarakat. Kolom `status_antrean` mengakomodasi kebutuhan **Validasi Manual ASN** (bisa _pending_, _disetujui_, atau _ditolak_).
