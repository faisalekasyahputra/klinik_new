# SECURITY DESIGN DOCUMENT (SDD)
## Klinik PKP — Super App Disperakim Provinsi Jawa Tengah
**Terakhir Diperbarui:** 9 Juni 2026

---

## 1. PENDAHULUAN & RUANG LINGKUP

Dokumen Desain Keamanan (*Security Design Document*) ini menetapkan spesifikasi arsitektur keamanan, model ancaman (*threat modeling*), dan strategi perbaikan enkripsi untuk portal **Klinik PKP** (`klinik_new`). 

Keamanan sistem difokuskan pada perlindungan data pribadi warga (kepatuhan UU PDP No. 27/2022), integritas transaksi forum, dan pengamanan pintu masuk Google OAuth 2.0.

---

## 2. MODEL ANCAMAN STRIDE (Threat Model)

Menggunakan kerangka kerja STRIDE untuk mengidentifikasi ancaman keamanan pada modul-modul aktif `klinik_new`:

| Kategori Ancaman | Risiko Spesifik pada `klinik_new` | Rencana Mitigasi Teknis |
|---|---|---|
| **S** (Spoofing / Impersonasi) | Pengguna biasa menyamar sebagai **"Staff Ahli / Petugas Disperakim"** saat mengirimkan tanggapan di forum diskusi. | Hapus dropdown `role` di form komentar. Tentukan peran pengirim secara mutlak di backend controller berdasarkan data session pengguna. |
| **T** (Tampering / Perusakan) | Peretas mengirimkan payload form palsu tanpa token valid (CSRF attack) untuk mengubah data pengguna lain. | Aktifkan `$config['csrf_protection']` di `config.php` dan validasi token CSRF pada setiap form POST. |
| **R** (Repudiation / Penyangkalan) | Aksi sensitif (seperti pendaftaran atau pembaruan profil) dilakukan tanpa pencatatan logs yang tidak dapat disangkal. | Implementasikan integrasi pustaka `Audit_lib` untuk merekam data aktivitas login dan pembaruan database. |
| **I** (Information Disclosure) | Kebocoran data pribadi NIK dan Alamat lengkap pengguna yang tersimpan secara plaintext di database `users`. | Terapkan enkripsi *at-rest* **AES-256-GCM** untuk NIK dan Alamat, dikombinasikan dengan *Deterministic Hashing* untuk deteksi keunikan akun. |
| **D** (Denial of Service) | Banwith jebol dan performa server lambat akibat request download gambar perumahan SiKumbang yang berulang-ulang ke server Tapera. | Mekanisme cache proxy lokal (`buka_foto`) menggunakan hash MD5 yang menyimpan gambar di server lokal (`assets/cache_foto/`) dan memberi header cache 30 hari. |
| **E** (Elevation of Privilege / IDOR) | Pengguna biasa memanipulasi parameter ID pada request POST `Auth/update` untuk memodifikasi profil akun milik pengguna lain. | Hilangkan data `id` dari input POST form. Ambil data ID langsung dari session aman backend (`$this->session->userdata('user_id')`). |

---

## 3. ARSITEKTUR PERTAHANAN MULTI-LAPIS (Defense in Depth)

```
┌────────────────────────────────────────────────────────┐
│ LAPIS 5: LAPISAN DATA & PRIVASI                        │
│  • Enkripsi kolom NIK & Alamat (AES-256-GCM)           │
│  • Deterministic Hashing untuk pencarian unik           │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ LAPIS 4: LAPISAN OTENTIKASI & SESI                     │
│  • Token state acak kriptografis pada Google OAuth     │
│  • Verifikasi kepemilikan sesi (Anti-IDOR)             │
│  • Session timeout ketat 30 menit                      │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ LAPIS 3: LAPISAN APLIKASI (CONTROLLER & HOOKS)         │
│  • Base controllers (Public vs Auth Controller)        │
│  • Proteksi CSRF global aktif di config                │
│  • XSS filtering di input POST & output escaping       │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ LAPIS 2: LAPISAN BROWSER & CLIENT                      │
│  • Cookie sessions HTTPOnly & SameSite=Lax             │
│  • Security headers (X-Frame, X-Content, X-XSS)        │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ LAPIS 1: LAPISAN INFRASTRUKTUR & SERVER                │
│  • Direktori upload diletakkan di luar webroot         │
│  • Blokir akses langsung ke folder system & config     │
└────────────────────────────────────────────────────────┘
```

---

## 4. KEAMANAN OAUTH 2.0 (Google Authentication Hardening)

Untuk mengatasi celah keamanan pada Google Login popup, dilakukan desain ulang alur autentikasi:

1.  **Anti-CSRF State Token:**
    Sebelum me-redirect pengguna ke Google Sign-In, backend memanggil `random_bytes(16)` untuk menghasilkan token kriptografis acak dinamis. Token disimpan di session dan dikirim sebagai parameter `state` ke Google.
2.  **Verifikasi State:**
    Saat Google mengembalikan pengguna ke callback `Auth/google_callback`, backend akan membandingkan parameter `state` dari input GET dengan `state` yang disimpan di session. Jika tidak cocok, proses masuk langsung dibatalkan (mencegah pembajakan alur otorisasi).
3.  **Anti-Open Redirect:**
    Path halaman asal (`from`) disimpan di dalam session `oauth_redirect` secara aman di internal server, bukan dilempar di URL state yang dapat dimanipulasi peretas.

---

## 5. SPESIFIKASI KRIPTOGRAFI PII (UU PDP)

Keamanan data pribadi kependudukan (L4 - Restricted Data) diatur secara ketat menggunakan enkripsi dua arah berbasis library openssl:

*   **Algoritma:** AES-256-GCM (Galois/Counter Mode).
*   **Kunci Enkripsi (`KPKP_DATA_KEY`):** 32-byte kunci acak yang disimpan di file `.env` (tidak masuk git).
*   **Pengaman Salt/Pepper (`KPKP_DATA_PEPPER`):** String acak unik untuk melindungi hashing.
*   **Alur Pengamanan Data:**
    ```php
    // application/libraries/Encryption_lib.php (Arsitektur Enkripsi)
    class Encryption_lib {
        private $key;
        private $pepper;

        public function __construct() {
            $this->key = getenv('KPKP_DATA_KEY');
            $this->pepper = getenv('KPKP_DATA_PEPPER');
        }

        public function encrypt($plaintext) {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt(
                $plaintext, 'aes-256-gcm', $this->key,
                OPENSSL_RAW_DATA, $iv, $tag, 'kpkp:v1'
            );
            // Format penyimpanan: VERSION(2) | IV(12) | TAG(16) | CIPHERTEXT
            return base64_encode('v1' . $iv . $tag . $cipher);
        }
    }
    ```

---

## 6. KEAMANAN AUTENTIKASI HIBRIDA

Sejak Juni 2026, sistem mendukung **dua jalur autentikasi**:

### 6.1 Registrasi Tradisional (Email/Password)
*   Password di-hash menggunakan `password_hash()` (bcrypt, cost default 10+).
*   Validasi kekuatan password dilakukan di sisi klien (JS) dan server.
*   Login mendukung input **email atau username**.

### 6.2 Google OAuth SSO
*   User Google yang baru pertama kali masuk tidak memiliki password.
*   Flag `needs_password` pada onboarding memaksa mereka set password sebelum akses penuh.
*   Setelah onboarding, user Google memiliki password dan bisa login tradisional juga.

### 6.3 Penghapusan Akun
*   Konfirmasi 2-langkah: user wajib mengetik nama akun secara manual.
*   Data forum dianonimkan (bukan dihapus) agar konteks diskusi tetap utuh.
*   Session di-destroy setelah penghapusan.

---
*Dokumen ini diperbarui otomatis oleh Antigravity AI Coding Assistant — 9 Juni 2026.*
