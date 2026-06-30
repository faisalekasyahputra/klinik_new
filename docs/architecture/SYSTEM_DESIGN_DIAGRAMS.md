# Desain Sistem: Flowchart & ERD Klinik PKP v3.0

Dokumen ini merumuskan secara visual bagaimana sistem baru (Smart Filter & Housing Queue) bekerja secara integratif dengan antarmuka Wizard 4 Langkah (Alpine.js) dan arsitektur database modular.

## 1. Flowchart: Alur Pengguna (Onboarding Journey)

Berikut adalah _flowchart_ perjalanan masyarakat dari mendaftar hingga dievaluasi menggunakan matriks kelayakan (UN HABITAT) dan masuk ke dalam antrean (Housing Queue).

```mermaid
graph TD
    A(["Masyarakat Kunjungi Web"]) --> B["Pilih Layanan Perumahan"]
    B --> C["Lihat Etalase Program (RTLH/PB/KPR)"]
    C --> D{"Tertarik Mengajukan?"}
    
    D -->|"Ya"| E["Klik Daftar / Cek Kelayakan"]
    D -->|"Tidak"| C
    
    E --> F["Mulai Wizard Onboarding 4 Langkah (Alpine.js)"]
    F --> G["Langkah 1: Input NIK & Cek API SIMPERUM"]
    
    G --> H{"NIK Terdaftar di SIMPERUM?"}
    H -->|"Ya"| I["Autofill Data Diri (Langkah 2)"]
    H -->|"Tidak"| J["Isi Manual Data Diri (Langkah 2)"]
    
    I --> K["Langkah 3: Input Kondisi Rumah & Sosial Ekonomi"]
    J --> K
    
    K --> L["Langkah 4: Review Data & Submit"]
    L --> M["Backend: Kalkulasi Matriks Kelayakan UN HABITAT"]
    
    M --> N{"Lolos Syarat Matriks (Smart Filter)?"}
    N -->|"Ya"| O["Simpan ke sf_housing_queue (Status: Pending)"]
    N -->|"Tidak"| P["Tolak Otomatis / Tawarkan Program Lain"]
    
    O --> Q[["Validasi Manual oleh ASN/Admin (Dashboard)"]]
    Q --> R{"Disetujui Admin?"}
    
    R -->|"Ya"| S(["Masuk Antrean Realisasi SIMPERUM / SP2"])
    R -->|"Tolak"| T(["Pengajuan Dibatalkan / Masuk Arsip"])
```

---

## 2. ERD (Entity Relationship Diagram) Database Modular (v3.0)

Karena adanya pergeseran fokus dari "Forum" menjadi "Layanan Smart Filter", database telah direstrukturisasi menggunakan *prefix* (`usr_`, `sf_`, `forum_`, `sys_`). Berikut adalah relasi terbarunya:

```mermaid
erDiagram
    usr_users ||--o{ sf_housing_queue : "mengajukan"
    sf_programs ||--o{ sf_housing_queue : "diajukan_ke"
    sf_program_kategori ||--|{ sf_programs : "membawahi"

    usr_users {
        int id PK
        string nik "UK (Encrypted AES-GCM)"
        string name
        string alamat "Encrypted"
        string phone
        string kategori
        datetime created_at
    }

    sf_program_kategori {
        int id PK
        string nama_kategori
    }

    sf_programs {
        int id PK
        int id_kategori FK
        string kode_program
        string nama_program
        decimal batas_penghasilan_max "Parameter Smart Filter"
        boolean is_active
    }

    sf_housing_queue {
        int id PK
        int user_id FK
        int program_id FK
        string status_antrean "pending, approved, rejected"
        text catatan_admin
        text data_simperum_json "Data dari API"
        text data_survey_json "Data dari Wizard"
        datetime created_at
    }
```

### Penjelasan Tabel Utama:

1. **`usr_users`**: Menggunakan enkripsi `AES-256-GCM` untuk melindungi privasi NIK dan Alamat (Kepatuhan UU PDP).
2. **`sf_program_kategori` & `sf_programs`**: Memisahkan antara kategori (Pembangunan Baru) dengan program spesifik. Nilai kolom `batas_penghasilan_max` pada `sf_programs` digunakan secara dinamis oleh backend saat memproses matriks kelayakan pelamar.
3. **`sf_housing_queue`**: Menampung hasil pendaftaran yang lolos seleksi mesin. Kolom `data_survey_json` menyimpan input riil matriks kelayakan dari *Wizard*, dan `status_antrean` mengakomodasi kebutuhan Validasi Manual ASN (Fase 10).
