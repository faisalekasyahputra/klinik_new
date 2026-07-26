# Desain Normalisasi Skema Data Role — Dua Opsi + Rekomendasi

**Status:** **Opsi A dipilih dan migrasi inti sudah dijalankan** (26 Jul 2026) — lihat "Implementasi" di akhir dokumen untuk detail migrasi, temuan tambahan yang muncul saat eksekusi, dan yang masih tersisa.
**Menindaklanjuti:** [`docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md`](../engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) — khususnya Pola A (upload di direktori publik, 3 lokasi), Pola B (`delete_user_account()` tidak lengkap), dan gap FK yang ditemukan di setiap audit per-role.
**Konteks pertanyaan yang dijawab:** apakah tabel-tabel per-role (`srp2_registrations`, `aduan`, `sf_housing_queue`, `kkn_magang_pendaftaran`) dinormalisasi jadi skema generik terpusat ala SaaS modern, atau tetap terpisah per domain tapi dipaksa ikut kontrak yang sama.

---

## Kondisi Saat Ini (baseline sebelum opsi apa pun)

Empat tabel "pengajuan" berdiri independen, masing-masing lahir di migrasi berbeda, dengan bentuk yang mirip tapi tidak seragam:

| Tabel | Role pemilik | Status enum | Kolom catatan admin | FK `user_id` | Reviewer tercatat? | Dokumen terpisah? |
|---|---|---|---|---|---|---|
| `srp2_registrations` | `pengembang` | `Draft/Pending/Diterima/Ditolak` | `catatan_admin` | Tidak ada FK | Tidak | Ya (`srp2_documents`, tanpa FK) |
| `aduan` | semua role (pelapor bebas) | `Baru/Diproses/Selesai` | `catatan_admin` | Tidak ada FK | Tidak | Tidak (lampiran path langsung di kolom) |
| `sf_housing_queue` | `warga` | `pending/approved/rejected` | `catatan_admin` | FK `ON DELETE SET NULL` | Tidak | Tidak berlaku |
| `kkn_magang_pendaftaran` | `mahasiswa` | `Diajukan/Diterima/Ditolak` | `catatan_admin` | Tidak ada FK | Tidak | Tidak (file path langsung di kolom) |

Pengamatan kunci: **konsepnya sudah konsisten** (semua punya `user_id` + status + `catatan_admin`), tapi **implementasinya menyimpang sedikit-sedikit** di tiap tabel (nama enum beda-beda, sebagian ada FK sebagian tidak, sebagian punya tabel dokumen terpisah sebagian menumpuk path di kolom). Ini pola klasik "organic growth" — tiap fitur dibangun terpisah waktu berbeda, bukan dari satu cetakan.

---

## Opsi A — Konvensi Konsisten, Tabel Domain Tetap Terpisah

### Prinsip

Setiap tabel pengajuan tetap independen (satu tabel = satu domain bisnis), tapi WAJIB ikut lima kontrak berikut:

1. **`user_id` selalu FK sungguhan** ke `usr_users.id`, dengan `ON DELETE` dipilih sadar per domain:
   - `CASCADE` untuk data yang murni milik pribadi dan tidak berguna lagi tanpa pemiliknya (mis. `kkn_magang_pendaftaran`).
   - `SET NULL` untuk data yang masih relevan buat pihak lain meski akun dihapus (mis. `aduan` yang mungkin masih dalam proses ditangani admin_bidang, `sf_housing_queue` — pola ini sudah benar di sini).
2. **Kolom akuntabilitas reviewer seragam** di semua tabel yang punya alur approve/reject: `reviewed_by INT UNSIGNED NULL` (FK `usr_users.id`, `ON DELETE SET NULL`) + `reviewed_at DATETIME NULL`. Diisi otomatis oleh controller admin manapun yang mengubah status.
3. **Dokumen/lampiran selalu tabel terpisah**, bukan kolom path langsung — mengikuti pola `srp2_documents` yang sudah benar (dengan tambahan FK yang saat ini belum ada di sana juga, lihat di bawah). Berlaku untuk `aduan` dan `kkn_magang_pendaftaran` yang saat ini menumpuk path di kolom tabel utama.
4. **Semua tabel dokumen pakai `private_uploads/{domain}/{owner_id}/`** — menutup Pola A di ringkasan audit (3 lokasi upload publik) sekaligus untuk seluruh sistem, bukan cuma yang sudah ditemukan.
5. **Registry role↔admin eksplisit di kode** (bukan tabel DB) — `application/config/role_admin_map.php` berisi peta deklaratif:
   ```php
   $config['role_admin_map'] = [
       'pengembang' =&gt; ['table' =&gt; 'srp2_registrations', 'admin' =&gt; 'Admin_Srp2', 'scope' =&gt; null],
       'warga_antrean' =&gt; ['table' =&gt; 'sf_housing_queue', 'admin' =&gt; 'Admin_Kabkota', 'scope' =&gt; 'kabupaten_id'],
       'aduan' =&gt; ['table' =&gt; 'aduan', 'admin' =&gt; 'Admin_Bidang', 'scope' =&gt; 'bidang'],
       'mahasiswa' =&gt; ['table' =&gt; 'kkn_magang_pendaftaran', 'admin' =&gt; 'Admin_Kemitraan', 'scope' =&gt; null],
   ];
   ```
   Ini bukan mesin generik yang dipakai runtime (menghindari over-engineering ala Opsi B) — cuma dokumentasi hidup yang bisa di-grep, dan tempat alami untuk mendaftarkan role baru + checklist "Prinsip Umum untuk Role Baru" dari `PRD_VERIFIKASI_ADMIN_SRP2.md`.

### Migrasi yang dibutuhkan (perkiraan)

- 1 migrasi: tambah FK + `reviewed_by`/`reviewed_at` ke `srp2_registrations`, `aduan`, `kkn_magang_pendaftaran` (yang belum punya).
- 1 migrasi: tambah FK ke `srp2_documents.registration_id` (`ON DELETE CASCADE`).
- 1-2 migrasi: buat `aduan_documents`/`kkn_magang_documents` kalau lampirannya memang perlu jadi tabel terpisah (opsional — kalau lampiran cuma satu file per pengajuan, kolom `stored_path` + `original_name` langsung di tabel utama sudah cukup normal, tidak harus dipaksa jadi tabel baru; keputusan ini per-domain, bukan aturan mutlak).
- Kode: satu helper upload terpusat (`store_private_upload()`, sudah diusulkan di ringkasan audit) dipakai 3 lokasi yang sudah ada + jadi standar untuk lokasi baru.
- Kode: audit ulang `delete_user_account()` sekali untuk konsisten dengan kebijakan `ON DELETE` yang dipilih tiap tabel di poin 1 (FK `CASCADE`/`SET NULL` menangani baris DB otomatis, tapi file fisik tetap harus di-`unlink()` manual di kode karena FK tidak bisa menghapus file di disk).

### Trade-off

| + | − |
|---|---|
| Blast radius kecil — tidak ada migrasi data lintas tabel, cuma `ALTER TABLE ADD COLUMN`/`ADD FOREIGN KEY` | Kode admin (`Admin_Srp2`, `Admin_Kabkota`, `Admin_Bidang`, `Admin_Kemitraan`) tetap 4 controller terpisah — tidak ada satu dashboard "semua pengajuan" tanpa kerja tambahan |
| Tidak ada downtime/risiko korupsi data produksi | Kalau role ke-6/ke-7 nanti benar-benar butuh pola yang identik, developer harus sadar meniru pola secara manual (dimitigasi oleh checklist + `role_admin_map.php`) |
| Setiap domain bebas berevolusi sendiri (mis. `sf_housing_queue` boleh punya `ticket_code`, `aduan` boleh punya `bidang` — field spesifik domain tidak dipaksa masuk struktur generik) | — |
| Query existing (`Pengaturan::index()`, semua dashboard admin) TIDAK perlu ditulis ulang, cuma ditambah kolom | — |

---

## Opsi B — Skema Generik Terpusat Lintas Role

### Prinsip

Satu tabel `submissions` menampung semua jenis pengajuan lintas role, satu tabel `submission_documents` menampung semua lampiran:

```sql
CREATE TABLE submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(30) NOT NULL,          -- 'srp2' | 'antrean' | 'aduan' | 'kkn_magang'
  user_id INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL,
  catatan_admin TEXT NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  payload JSON NOT NULL,              -- field spesifik domain: nama_perusahaan, bidang, ticket_code, dst
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES usr_users(id) ON DELETE SET NULL,
  FOREIGN KEY (reviewed_by) REFERENCES usr_users(id) ON DELETE SET NULL,
  INDEX idx_type_status (type, status)
);

CREATE TABLE submission_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id INT UNSIGNED NOT NULL,
  document_key VARCHAR(40) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(80) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  UNIQUE KEY uq_submission_document (submission_id, document_key)
);
```

Manfaat yang dijanjikan pola ini (alasan orang memilihnya di sistem SaaS modern): satu dashboard admin generik bisa menampilkan semua jenis pengajuan; satu sistem notifikasi/audit-log bisa dipasang sekali untuk semua `type`; developer baru menambah role tinggal menambah satu nilai `type` baru, bukan tabel baru.

### Kenapa trade-off-nya berat untuk skala proyek ini sekarang

1. **Kolom `payload JSON` mengorbankan yang justru sedang diperbaiki.** Audit ini bermula dari masalah "FK tidak ada, data integrity lemah" — memindahkan field spesifik domain (nama_perusahaan, nik_ktp, bidang, ticket_code, jenis KKN/Magang) ke JSON blob **menghilangkan** validasi tipe/panjang/uniqueness di level DB yang sekarang justru dimiliki sebagian tabel (`UNIQUE KEY uq_nik_ktp`, `uq_nib` di `srp2_registrations`). MySQL JSON bisa divalidasi via `CHECK` constraint tapi itu sendiri kompleksitas tambahan yang tidak dibutuhkan hari ini.
2. **Migrasi data 4 tabel produksi sekaligus** — bukan `ALTER TABLE`, tapi transformasi baris lama ke bentuk baru (mis. `aduan.bidang` yang varchar jadi `payload->>'$.bidang'`), butuh script migrasi data + downtime/maintenance window + rencana rollback yang matang. Risiko error jauh lebih tinggi daripada Opsi A.
3. **Semua kode yang query keempat tabel harus ditulis ulang**, bukan cuma admin: `Pengaturan::index()` (4 query berbeda jadi satu query `WHERE type IN (...)` + `json_extract`), `Admin_Kabkota`/`Admin_Bidang`/`Admin_Kemitraan`/`Admin_Srp2` (kalau dibangun) — controller-controller ini punya business logic yang **genuinely berbeda** per domain (14-dokumen-wajib untuk SRP2, ticket_code generation untuk antrean, bidang-scoping untuk aduan) — pola generik hanya menyederhanakan bagian "baca/tulis status", bukan menghilangkan logic domain itu sendiri. Hasilnya: layer abstraksi baru ditambahkan, tapi jumlah kode spesifik-domain tidak banyak berkurang — ini gejala klasik premature abstraction, bukan penyederhanaan sungguhan.
4. **"Modern" tidak identik dengan "satu tabel besar".** Praktik modern yang lebih relevan di sini justru domain-driven design / bounded context: tiap domain bisnis (sertifikasi, antrean, aduan, kemitraan) punya model data sendiri yang boleh berbeda bentuk, disatukan lewat **kontrak** (interface/konvensi), bukan lewat **tabel bersama**. Itulah persis Opsi A.

### Kapan Opsi B baru masuk akal

Kalau ke depan benar-benar ada kebutuhan produk yang eksplisit: "satu inbox admin yang menyatukan semua jenis pengajuan lintas role dalam satu tampilan/notifikasi real-time", DAN jumlah jenis pengajuan sudah banyak (7-10+) sehingga menduplikasi kolom reviewer/status di setiap migrasi baru terasa berat. Dengan 4 domain saat ini, biaya duplikasi kecil itu masih jauh lebih murah daripada biaya migrasi + JSON.

---

## Rekomendasi

**Opsi A.** Alasan singkat: masalah yang diaudit bukan "struktur tabelnya berbeda-beda" (itu wajar untuk domain bisnis yang memang berbeda), tapi "tiap tabel lupa menerapkan bagian yang seharusnya sama" (FK, reviewer, lokasi file aman). Opsi A memperbaiki tepat itu, dengan risiko dan biaya jauh lebih kecil daripada Opsi B, dan tanpa mengorbankan validasi data yang sudah ada. Opsi B layak dipertimbangkan ulang nanti kalau kebutuhan "satu dashboard admin universal" benar-benar diminta secara eksplisit sebagai fitur produk — bukan diasumsikan sebagai keharusan arsitektur sekarang.

---

## Implementasi (26 Jul 2026)

Tiga migrasi dijalankan untuk kontrak #1-#3 dari Opsi A, diverifikasi hidup di database lokal (`php index.php migrate`, skema akhir versi `20260701000013`):

- [`20260701000011_add_reviewer_accountability.php`](../../application/migrations/20260701000011_add_reviewer_accountability.php) — kolom `reviewed_by`/`reviewed_at` + FK ke `usr_users.id` (`ON DELETE SET NULL`) di keempat tabel pengajuan (`srp2_registrations`, `aduan`, `kkn_magang_pendaftaran`, `sf_housing_queue`).
- [`20260701000012_add_submission_owner_fk.php`](../../application/migrations/20260701000012_add_submission_owner_fk.php) — FK sungguhan pada `user_id` di `aduan` (`SET NULL`), `kkn_magang_pendaftaran` (`CASCADE`), `srp2_registrations` (`CASCADE`), dengan cleanup baris orphan sebelum constraint dipasang.
- [`20260701000013_add_srp2_documents_fk.php`](../../application/migrations/20260701000013_add_srp2_documents_fk.php) — FK `srp2_documents.registration_id` → `srp2_registrations.id` (`CASCADE`), menutup `AUDIT_ROLE_PENGEMBANG.md` Temuan #5.

Pendamping wajib: `User_model::delete_user_account()` diperbarui untuk `unlink()` file fisik (`private_uploads/srp2/{id}/`, `.assets/uploads/{filename}` milik KKN/Magang) **sebelum** baris DB dihapus — begitu `ON DELETE CASCADE` menghapus baris, tidak ada lagi cara menemukan nama file yang harus dibersihkan dari disk.

### Temuan tak terduga saat eksekusi

Migrasi pertama gagal (`errno 150: Foreign key constraint is incorrectly formed`) karena **`usr_users.id` ternyata `int(11)` SIGNED**, peninggalan skema lama — sedangkan hampir semua kolom `*_id` lain di aplikasi ini (termasuk `reviewed_by` yang baru ditulis, dan `user_id` di `aduan`/`kkn_magang_pendaftaran`/`srp2_registrations`) memakai `INT UNSIGNED`. MySQL menolak FK antar kolom dengan signedness berbeda. Satu-satunya kolom yang kebetulan sudah benar adalah `sf_housing_queue.user_id` (juga signed, sudah begitu sejak awal — itu sebabnya FK-nya bisa hidup lebih dulu tanpa masalah). Migrasi 12 di-update untuk `MODIFY COLUMN` ketiga kolom itu ke signed dulu sebelum menambah constraint (nilai data tidak berubah, cuma definisi tipe). **Implikasi untuk desain database ke depan:** kalau menambah kolom baru yang menunjuk ke `usr_users.id`, gunakan `INT` biasa (bukan `UNSIGNED`) — dicatat juga sebagai catatan class di migrasi 11/12 supaya tidak terulang.

### Yang masih tersisa (di luar cakupan 3 migrasi ini)

- **`role_admin_map.php`** (kontrak #5 di Opsi A) — belum dibuat, murni file konfigurasi/dokumentasi, tidak butuh migrasi.
- **Pola A (upload di direktori publik)** dari `AUDIT_SISTEM_ROLE_RINGKASAN.md` — FK dan cleanup file saat hapus akun sudah beres, tapi lokasi penyimpanan filenya (`.assets/uploads/`, `FCPATH.'uploads/documents/'`) **belum dipindah** ke `private_uploads/`. Itu perubahan kode controller (`Auth::_handle_uploads()`, `Umum::simpan_aduan()`, `KemitraanPortal::simpan()`), bukan migrasi skema — tetap terbuka sebagai pekerjaan terpisah.
- **Pengisian `reviewed_by`/`reviewed_at`** — kolomnya sudah ada, tapi controller admin (`Admin_Srp2`, `Admin_Bidang`, `Admin_Kabkota`, `Admin_Kemitraan`) belum diubah untuk menulis ke kolom ini saat approve/reject. Menutup `AUDIT_ROLE_ADMIN_SCOPED.md` Temuan #2 butuh perubahan kode itu, bukan cuma skema.
