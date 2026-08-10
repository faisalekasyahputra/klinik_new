# Roadmap Implementasi Form Warga - Simulasi SIMPERUM

**Versi:** 1.0  
**Tanggal:** 27 Juli 2026  
**Status:** Aktif - jalur simulation-first disetujui  
**PRD:** [`PRD_FORM_WARGA_SIMPERUM.md`](./PRD_FORM_WARGA_SIMPERUM.md)  
**Skema:** [`SKEMA_DATA_FORM_WARGA_SIMPERUM.md`](../architecture/SKEMA_DATA_FORM_WARGA_SIMPERUM.md)

Tujuan roadmap ini adalah memungkinkan agent lain melanjutkan pekerjaan tanpa
mengandalkan ingatan percakapan. Status, bukti uji, keputusan, dan langkah
berikutnya wajib diperbarui di file ini pada akhir setiap fase.

---

## 0. Keputusan Inti

Sistem dibangun lengkap sekarang. Karena API SIMPERUM belum diberikan:

- sumber eksternal sementara berupa data sintetis;
- data sintetis masuk melalui gateway yang sama seperti API kelak;
- seluruh cache, normalisasi, enkripsi, draft, wizard, scoring, antrean, admin,
  dan audit tetap memakai jalur production-grade;
- UI dan database selalu menandai `source_mode = simulation`;
- ruleset demo selalu berawalan `SIM-` dan bukan keputusan resmi Dinas;
- saat API tersedia, yang diganti/ditambah hanya driver gateway dan mapping.

**Dilarang:** menaruh `if ($nik === ...)` baru di controller, mengisi session
langsung dari fixture, atau menampilkan klaim “terhubung ke SIMPERUM”.

## 1. Baseline Terverifikasi

| Item | Keadaan 28 Jul 2026 |
|---|---|
| Branch lokal | `feature/homepage-portal-v2` |
| Skema lokal | `20260701000020` |
| Production aktif | Branch yang sama; push langsung merilis |
| Form saat ini | Wizard komprehensif sampai submit, revisi, dan keputusan admin |
| Antrean/admin | Alur assessment baru R6 hijau 58/58; alur lama tetap 19/19 |
| PRD dan kamus | Selesai; 5 gambar utama + 45 detail dianalisis |
| Implementasi roadmap ini | R0-R7 selesai lokal; R8 menunggu izin rilis |

Jangan push branch tanpa perintah eksplisit user. Jangan menjalankan migrasi
production. Mulai dari `git status` dan pertahankan seluruh perubahan lokal
yang sudah ada.

## 2. Tracker Fase

Hanya satu fase boleh berstatus **IN PROGRESS**.

| Fase | Hasil | Status | Bukti terakhir |
|---|---|---|---|
| R0 | PRD, skema, simulation-first, roadmap | **COMPLETED** | Audit dokumen 27 Jul 2026 |
| R1 | Skema DB dan model assessment | **COMPLETED** | Migrasi 18 + check 18/18 hijau |
| R2 | Gateway simulasi, fixture, cache local-first | **COMPLETED** | Cache 14/14 + concurrency 20→1 hijau |
| R3 | Wizard identitas + rumah/lahan + save/resume | **COMPLETED** | Check HTTP 16/16; lanjut R4 untuk modul cabang |
| R4 | Struktur, sanitasi, lokasi, bukti privat | **COMPLETED** | Check HTTP 44/44 + browser peta/foto |
| R5 | Ruleset simulasi dan rekomendasi beralasan | **COMPLETED** | Check HTTP/DB 59/59 |
| R6 | Submit, antrean, admin, minta perbaikan | **COMPLETED** | Check HTTP/DB 58/58 + browser warga/admin |
| R7 | E2E, browser, keamanan, paket presentasi | **COMPLETED** | DB fresh 224/224 + browser desktop/mobile |
| R8 | Rilis production | BLOCKED - izin user | - |
| R9 | Driver API SIMPERUM nyata | **IN PROGRESS** - adapter offline selesai, aktivasi diblokir | Contract 27/27 + fresh 224/224 |

## 3. Kontrak Mode

Gunakan mode eksplisit:

| Mode | Perilaku |
|---|---|
| `simulation` | Gateway membaca fixture sintetis, tetap menulis snapshot/cache DB |
| `api` | Kelak memanggil API nyata; gagal konfigurasi tidak boleh fallback diam-diam |

Konfigurasi target saat implementasi: `SIMPERUM_MODE=simulation`. Mode harus
dibaca server-side. Production presentasi boleh memakai simulation hanya jika
label simulasi terlihat di seluruh permukaan relevan.

Setiap snapshot, assessment, rekomendasi, dan antrean baru menyimpan sumber
atau dapat menelusurinya:

- `source_mode`: `simulation` atau `api`;
- `source_record_key`: ID fixture/hash request, tanpa PII plaintext;
- `ruleset_version`: contoh `SIM-2026-01`;
- `simperum_snapshot_id`.

## 4. Dataset Sintetis

Semua identitas harus jelas fiktif. Jangan memakai NIK, KK, nama, alamat,
KTP, atau foto orang nyata. NIK berikut hanya valid di mode simulation dan
gateway API kelak wajib menolaknya.

| ID | NIK dummy | Skenario | Kondisi awal | Hasil demo |
|---|---|---|---|---|
| SIM-01 | `0000000000000001` | RTLH lengkap | Profil, rumah eksisting, kerusakan, sanitasi terisi | Rekomendasi RTLH |
| SIM-02 | `0000000000000002` | Data parsial | Identitas ada; rumah/struktur/sanitasi kosong | Warga melengkapi, lalu RTLH |
| SIM-03 | `0000000000000003` | Backlog/calon lahan | Kontrak, punya calon lahan dan legalitas | Rekomendasi PB/Omah Sekeng simulasi |
| SIM-04 | `0000000000000004` | Pembiayaan | Pekerjaan tetap, penghasilan band, belum punya rumah layak | Rekomendasi FLPP/Oemah Lestari simulasi |
| SIM-05 | `0000000000000005` | Koreksi data | Alamat/status rumah sumber sudah usang | Tampilkan provenance dan override warga |
| SIM-98 | `0000000000000098` | Tidak ditemukan | Gateway mengembalikan `not_found` | Input manual + negative cache |
| SIM-99 | `0000000000000099` | Gangguan sumber | Gateway mengembalikan timeout/5xx sintetis | Manual fallback + backoff |

Tanggal lahir demo menjadi faktor pendamping dan ditulis di fixture. Gunakan
nama seperti `Warga Simulasi RTLH`, bukan nama manusia umum.

### 4.1 Isi Fixture

Lokasi target: `application/fixtures/simperum/`. Satu JSON per skenario agar
diff terbaca. Setiap fixture mempunyai:

```json
{
  "fixture_id": "SIM-01",
  "synthetic": true,
  "response_status": "found",
  "api_version": "simulation-v1",
  "identity": {},
  "socioeconomic": {},
  "housing": {},
  "structure": {},
  "sanitation": {},
  "location": {},
  "missing_fields": []
}
```

SIM-01, SIM-03, dan SIM-04 harus mengisi semua field yang relevan bagi
cabangnya. SIM-02 sengaja hanya mengisi identitas/sosial-ekonomi. SIM-05
mempunyai nilai sumber yang aman untuk dikoreksi dan dibuktikan tidak tertimpa.

### 4.2 Evidence Dummy

- Gunakan gambar placeholder buatan/ikon rumah bertuliskan **SIMULASI**.
- KTP/KK berupa kartu placeholder, tanpa format identitas orang nyata.
- PDF verval berisi watermark **DOKUMEN SIMULASI**.
- Semua file tetap melewati MIME check, ukuran, private storage, ownership,
  replacement cleanup, dan endpoint download berizin.

## 5. Ruleset Simulasi

Versi awal: `SIM-2026-01`. Ini hanya untuk membuktikan mekanisme.

| Program | Aturan demo minimum | Reason code contoh |
|---|---|---|
| RTLH | Desil 1-3; ada rumah tidak layak; minimal satu komponen rusak sedang/berat atau sanitasi kritis | `SIM_RTLH_DAMAGE`, `SIM_RTLH_SANITATION` |
| PB | Desil 1-4; belum punya rumah layak dan calon lahan/legalitas memenuhi data minimum | `SIM_PB_LAND_READY` |
| Omah Sekeng | Desil = 4; mampu swadaya; kebutuhan rumah/perbaikan terkonfirmasi | `SIM_OMAH_DESIL4_SELF_HELP` |
| FLPP | Desil 5-8; belum punya rumah layak; band penghasilan dan data pembiayaan memenuhi | `SIM_FLPP_INCOME` |
| Oemah Lestari | Desil 5-8 jalur subsidi atau 9-10 jalur non-subsidi; band penghasilan sesuai | `SIM_OEMAH_INCOME` |
| Rumah Apung | Lintas desil berdasarkan kawasan pesisir/rob; tetap `needs_data` sampai definisi resmi ada | `needs_data` |

Jika input penentu kosong, hasilnya `needs_data`; jangan mengasumsikan nilai.
Halaman rekomendasi memuat banner bahwa hasil memakai ruleset simulasi.

## 6. Matriks Field/Bukti Simulasi

| Cabang | Field wajib | Bukti wajib demo |
|---|---|---|
| Rumah eksisting | Identitas, sosial-ekonomi, rumah/keluarga, seluruh kondisi utama, air/jamban/penerangan, lokasi | Foto diri placeholder, depan, samping, atap, lantai, dinding, jamban |
| Calon lahan/backlog | Identitas, sosial-ekonomi, status rumah, kepemilikan/legalitas/asal/ukuran tanah, lokasi | Calon lahan, penerima, KTP dummy, KK dummy; KK pemilik + pindah tangan jika bukan milik sendiri |
| Pembiayaan | Identitas, sosial-ekonomi, status rumah, pekerjaan, penghasilan, keluarga, lokasi kabupaten | KTP/KK dummy cukup untuk simulasi; tanpa foto struktur |

`Berkas Verval` diunggah admin dan tidak menghalangi submit warga pada mode demo.

## 7. R1 - Skema DB dan Model Assessment

**Tujuan:** persistence yang benar sebelum UI.

### Pekerjaan

1. Verifikasi nomor migrasi terbaru; baseline saat dokumen ditulis adalah 17.
2. Tambah tabel konseptual dari dokumen skema:
   - `sf_profil_warga`;
   - `sf_rekaman_simperum`;
   - `sf_penilaian_perumahan`;
   - `sf_berkas_penilaian`;
   - `sf_rekomendasi_penilaian`.
3. Tambah `assessment_id` dan sumber simulasi ke `sf_housing_queue` tanpa
   merusak baris lama.
4. Tambah status `needs_revision` hanya bersama writer/reader/transisi pada fase R6;
   jangan membuat kolom/status tanpa pemakai.
5. Buat satu model domain, target
   `application/models/Housing_assessment_model.php`.
6. PII write fail-closed jika key/pepper invalid.
7. Simpan `lock_version` untuk mencegah overwrite dua tab.

### Check runnable

- Import baseline ke DB uji fresh.
- Jalankan migrasi sampai latest.
- Buat/read/update draft dengan data dummy terenkripsi.
- Buktikan DB tidak berisi NIK/nama/alamat plaintext.
- Buktikan dua update memakai `lock_version` sama: hanya satu berhasil.

### Selesai jika

DB fresh hijau, migrasi rollback policy tertulis, dan tidak ada controller/UI
baru yang menulis langsung ke tabel domain.

## 8. R2 - Gateway Simulasi dan Cache Local-First

**Tujuan:** semua data dummy berperilaku seperti sumber eksternal.

### Pekerjaan

1. Tambah config mode dan `Simperum_gateway`.
2. Gateway membaca fixture hanya di `simulation`.
3. Normalizer mengeluarkan allowlist canonical dari dokumen skema.
4. Lookup selalu:
   `profile/cache → lock per NIK hash → gateway → snapshot → normalized profile`.
5. Simpan raw fixture sebagai snapshot terenkripsi untuk menyamai alur API.
6. Terapkan found cache, negative cache, error backoff, dan request coalescing.
7. Respons UI hanya memuat data normalized/masked.
8. Tambah label mode pada payload internal dan view.

### Check runnable

Buat `docs/engineering/uji_simperum_gateway.php`:

- SIM-01 ditemukan dan snapshot tersimpan;
- lookup ulang membuat nol gateway fetch tambahan;
- 20 lookup bersamaan menghasilkan satu fetch;
- SIM-98 negative cache;
- SIM-99 backoff;
- raw payload tidak muncul di response browser/log.

### Selesai jika

Mengganti isi fixture mengubah prefill tanpa menyentuh controller/form, dan
semua lookup berikutnya berasal dari cache sampai kebijakan refresh mengizinkan.

## 9. R3 - Wizard Dasar dan Save/Resume

**Tujuan:** mempertahankan hanya input NIK + tanggal lahir dari diagnosa lama,
lalu mengganti seluruh langkah sesudahnya dengan wizard baru.

### Pekerjaan

1. Route kanonik: `/warga/pendataan`.
2. Controller warga baru hanya mengorkestrasi; validasi/persistence di model.
3. Langkah:
   - Temukan Data;
   - Data Warga;
   - Rumah dan Keluarga;
   - Kondisi Bangunan **atau** Calon Lahan;
   - Sanitasi;
   - Lokasi dan Bukti;
   - Review dan Rekomendasi.
4. Gunakan server-rendered stepper + POST/Redirect/Get. JavaScript hanya untuk
   peningkatan UX, bukan syarat data tersimpan.
5. `Simpan dan lanjut` menulis draft tiap langkah.
6. Back/reload/login ulang memulihkan draft.
7. Badge per field: SIMPERUM, Diisi Warga, Koreksi Warga.
8. Jangan memanggil gateway saat Next/Back/reload.
9. Error per field dan ringkasan fokus keyboard.
10. Jangan merender atau memanggil survei/kalkulasi lama setelah lookup;
    rekomendasi program hanya dibuat oleh ruleset setelah wizard lengkap.
11. Wizard tidak diduplikasi per program. Desil menghasilkan kandidat internal,
    lalu field yang tampil adalah gabungan data inti dan modul cabang faktual.

### Check runnable

- SIM-01 prefill lengkap.
- SIM-02 menunjukkan field kosong, warga melengkapi, logout/login, nilai tetap.
- SIM-05 menyimpan override tanpa mengubah snapshot.
- Request palsu mengubah `user_id`/draft ID ditolak.
- Dua tab memicu konflik yang jujur, bukan silent overwrite.

### Selesai jika

Skenario R3 dapat menempuh langkah 0-2, pulih setelah login ulang, dan masuk ke
modul cabang yang benar tanpa kehilangan data. Mencapai halaman review baru
menjadi acceptance lintas-fase setelah field cabang R4 tersedia; menjadikannya
syarat R3 akan membuat R3 bergantung pada fase berikutnya.

## 10. R4 - Struktur, Sanitasi, Lokasi, Evidence

**Tujuan:** seluruh field dari 50 gambar hidup sesuai cabang.

### Pekerjaan

1. Implementasikan katalog option code tunggal.
2. Terapkan dependensi field dari dokumen skema.
3. Cabang Backlog melewati struktur/sanitasi yang tidak relevan.
4. Cabang RTLH mewajibkan data kondisi utama.
5. Simpan lokasi presisi terenkripsi dan `kabupaten_id` sebagai scope.
6. Upload satu per satu ke private storage.
7. Strip EXIF, cek MIME/ukuran, nama acak, ownership, dan cleanup file lama.
8. Gunakan evidence placeholder sintetis untuk presentasi.

### Check runnable

- Seluruh 56 label penting dalam audit field mempunyai writer/reader atau
  tercatat sebagai field nonaktif.
- Mengubah cabang tidak membuat nilai tersembunyi ikut validasi/scoring.
- File langsung melalui URL publik gagal.
- Pemilik dapat melihat file sendiri; warga lain/admin wilayah lain tidak.
- Ganti file menghapus file lama setelah file baru tersimpan.

## 11. R5 - Ruleset dan Rekomendasi Simulasi

**Tujuan:** rekomendasi lengkap dan dapat dijelaskan.

### Pekerjaan

1. Implementasikan ruleset eksplisit `SIM-2026-01`; tidak perlu rule-builder.
2. Nilai setiap program menjadi eligible/potential/not_eligible/needs_data.
3. Simpan reason codes dan input snapshot hash.
4. UI menjelaskan data penentu dan label simulasi.
5. Program yang dipilih harus berasal dari rekomendasi server aktif.
6. Rumah Apung tetap `needs_data` sampai definisi resmi ada.
7. Gunakan desil sumber sebagai routing utama; dilarang menghitung desil baru
   dari input penghasilan warga.
8. Pisahkan `route_candidates(desil)` dari
   `evaluate_candidate(program, assessment)`, tetapi pertahankan keduanya dalam
   satu ruleset eksplisit tanpa rule-builder generik.
9. Ruleset memiliki versi, status, dan masa berlaku. Evaluasi lama tetap
   menunjuk versi yang digunakan saat itu.
10. Tahap simulasi memakai konfigurasi server berversi; editor aturan berbasis
    admin/DB baru dibuat bila Dinas memang perlu memperbarui tanpa deployment.

### Check runnable

- SIM-01 → RTLH.
- SIM-02 sebelum lengkap → needs_data; sesudah lengkap → RTLH.
- SIM-03 → PB/Omah Sekeng sesuai data.
- SIM-04 → FLPP/Oemah Lestari.
- Balik satu kondisi ruleset dengan sengaja: skrip menjadi merah pada alasan
  yang diprediksi, lalu hijau setelah dikembalikan.
- POST `program_id` manipulatif ditolak.

## 12. R6 - Submit, Antrean, Admin, Perbaikan

**Tujuan:** perjalanan warga sampai keputusan admin lengkap.

### Pekerjaan

1. Submit membuat snapshot assessment immutable.
2. Idempotency key mencegah antrean ganda.
3. Tautkan queue ke assessment dan rekomendasi terpilih.
4. Tambah `needs_revision` dengan transisi server-side:
   `pending → needs_revision → pending → approved/rejected`.
5. Catatan wajib untuk revisi/penolakan.
6. Admin melihat nilai sumber vs koreksi, evidence, ruleset, reason codes,
   dan label simulasi.
7. Scope kabupaten tetap dari sesi.
8. Perbaikan warga membuat versi assessment baru; versi submitted lama tidak berubah.
9. `/akun` menampilkan draft, revisi, queue, status, dan catatan.

### Check runnable

- SIM-01 submit → admin Semarang → needs_revision → warga revisi → resubmit → approve.
- Admin wilayah lain gagal baca dan gagal menulis.
- Submit dua kali membuat satu queue.
- Update bersamaan memakai status asal; hanya satu keputusan berhasil.
- Nilai raw snapshot tetap sama setelah revisi.

## 13. R7 - Pembuktian dan Paket Presentasi

**Tujuan:** bukan sekadar demo yang terlihat bagus; alur terbukti.

### Pekerjaan

1. Buat satu skrip E2E CLI + curl untuk seluruh journey.
2. Jalankan pada DB uji fresh, bukan hanya DB dev lama.
3. Uji browser desktop dan mobile.
4. Uji mode simulation pada seluruh banner/label.
5. Uji cache count, anti-IDOR, CSRF, PII, private files, dan failure path.
6. Uji registry rate limit bersama: IP, akun, hash NIK, `429`,
   `Retry-After`, reset jendela, serta isolasi scope antarfitur.
7. Siapkan urutan presentasi:
   - data lengkap;
   - data parsial dilengkapi warga;
   - cabang calon lahan;
   - rekomendasi beralasan;
   - admin meminta perbaikan dan menerima.
8. Bersihkan akun/queue/file uji setelah skrip.

### Definisi selesai

- Seluruh acceptance criteria PRD hijau.
- Skrip sengaja dibuat merah dengan membalik satu guard penting, lalu hijau
  setelah dipulihkan dan `git diff` membuktikan tidak ada perubahan tersisa.
- Tidak ada error console baru selain warning eksternal yang sudah dikenal.
- Tidak ada klaim UI bahwa API SIMPERUM nyata sudah terhubung.

## 14. R8 - Rilis

R8 hanya boleh dimulai atas perintah eksplisit user.

Urutan:

1. pastikan worktree dan commit scope jelas;
2. uji lokal + DB fresh;
3. karena push branch aktif langsung deploy production, minta izin;
4. push kode;
5. tunggu deploy;
6. verifikasi health route;
7. baru migrasi production dengan izin eksplisit;
8. verifikasi ulang dan siapkan rollback.

Jangan mengikuti dokumen lama yang menyebut staging aman; situs aktif sekarang
production.

## 15. R9 - Integrasi API Nyata

Status 28 Juli 2026:

1. kontrak PDF 9 halaman sudah dibaca dan dipetakan;
2. driver GET `GetDataRTLH?NIK` selesai di `Simperum_gateway`;
3. signature MD5, TLS wajib, timeout, satu retry, error mapping, cache, snapshot
   terenkripsi, dan normalisasi canonical selesai;
4. contract fixture tersanitasi **27/27** dan runner fresh simulation
   **224/224**;
5. secret lokal berada di `.env` yang diabaikan Git;
6. endpoint legacy tidak diizinkan memakai API nyata;
7. `SaveDataRTLH` sengaja tidak diimplementasikan.

Kontrak dan mapping lengkap:
[`KONTRAK_INTEGRASI_SIMPERUM_API.md`](../architecture/KONTRAK_INTEGRASI_SIMPERUM_API.md).

R9 belum boleh dinyatakan selesai atau mengubah `SIMPERUM_MODE=api` karena:

1. kontrak tidak memuat desil, padahal ruleset memakai desil sebagai routing
   utama;
2. API hanya menerima NIK dan hanya mengembalikan tahun lahir opsional;
3. quota, bentuk error nyata, dan status keaktifan dokumentasi 2020 belum
   dikonfirmasi;
4. smoke test live belum mendapat NIK uji resmi/persetujuan untuk mengirim NIK;
5. UAT mode API belum dilakukan.

Wizard, assessment, scoring, queue, dan admin tidak ditulis ulang pada fase ini.

## 16. Protokol Handoff Antar-Agent

Agent yang mulai/resume pekerjaan wajib:

1. baca `AGENTS.md` §0 sampai selesai;
2. baca PRD, skema, dan roadmap ini;
3. jalankan `git status --short`; jangan menimpa perubahan yang bukan miliknya;
4. cek tracker §2 dan ambil fase pertama `NOT STARTED` setelah fase terakhir hijau;
5. ubah hanya fase itu menjadi `IN PROGRESS`;
6. sebelum mengedit, telusuri seluruh caller untuk writer/model yang disentuh;
7. implementasikan satu fase sampai runnable check hijau;
8. perbarui tracker, Catatan Handoff (§17), dan `AGENTS.md` §0;
9. tulis apa yang belum diuji secara eksplisit;
10. jangan push/migrasi production tanpa izin user.

Jika token hampir habis, berhenti pada batas aman dan isi Catatan Handoff
sebelum respons akhir. Jangan meninggalkan status `COMPLETED` bila check belum hijau.

## 17. Catatan Handoff

Tambahkan entri terbaru di paling atas menggunakan template:

```md
### YYYY-MM-DD HH:mm - Agent/Fase
- Status fase: IN PROGRESS / COMPLETED / BLOCKED
- Commit/branch: ...
- Migrasi lokal: ...
- Yang selesai: ...
- File utama: ...
- Uji dijalankan: `perintah` → hasil
- Data uji dibersihkan: ya/tidak + detail
- Yang belum selesai: ...
- Langkah pertama agent berikutnya: ...
- Larangan/peringatan khusus: ...
```

### 2026-07-28 - R3 Dimulai / Penamaan Tabel Indonesia

- Status fase: **IN PROGRESS**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: `20260701000019`; production tetap `20260701000016`
- Yang selesai: lima tabel baru diganti ke nama Bahasa Indonesia melalui
  migrasi 19; seluruh model, diagnostik, PRD skema, dan tracker diselaraskan
- File utama: migrasi 19, `Housing_assessment_model.php`, `Migrate.php`,
  `SKEMA_DATA_FORM_WARGA_SIMPERUM.md`
- Uji dijalankan: migrasi 18→19 di DB uji dan lokal; R1 **18/18** serta R2
  **5/5** hijau setelah rename
- Data uji dibersihkan: ya
- Yang belum selesai: UI wizard, save/resume, validasi langkah, dan check R3
- Langkah pertama agent berikutnya: buat route/controller/view
  `/warga/pendataan` menggunakan tabel Indonesia dan model yang sudah ada
- Larangan/peringatan khusus: `usr_users`, `sf_programs`, `sf_housing_queue`
  adalah tabel legacy production dan sengaja tidak diganti

### 2026-07-28 - R3 Wizard Dasar Langkah 0-2

- Status fase: **IN PROGRESS**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tetap `20260701000019`; production tetap `20260701000016`
- Yang selesai: route `/warga/pendataan`, guard role warga, langkah Temukan
  Data → Data Warga → Rumah & Keluarga, POST/Redirect/Get, prefill snapshot
  tanpa lookup ulang, koreksi profil, ownership, optimistic lock, navigasi
  kembali, serta routing dasar `existing_house`/`candidate_land`/`financing`
- File utama: `Warga.php`, `Housing_assessment_model.php`,
  `pages/warga/pendataan.php`, dan
  `docs/engineering/AUDIT_AKURASI_FORM_WARGA_R3.md`
- Uji dijalankan: lint empat file hijau; R1 **18/18**; R2 **5/5**; HTTP lokal
  login warga → SIM-05 lookup/prefill → simpan langkah 1 → simpan langkah 2
  → cabang calon lahan → kembali ke langkah 2, seluruh respons **200**
- Data uji dibersihkan: tidak; draft SIM-05 sengaja tertinggal pada akun demo
  `warga@example.com` sebagai data simulasi lokal
- Yang belum selesai: check R3 permanen untuk SIM-02 logout/login, pembuktian
  override tidak mengubah snapshot melalui HTTP, forged draft via HTTP, dan
  konflik dua tab; langkah 3-6 masih placeholder dan masuk R4/R5
- Langkah pertama agent berikutnya: buat satu check HTTP R3 yang membersihkan
  data sintetisnya sendiri, lalu tutup acceptance R3 sebelum mulai R4
- Larangan/peringatan khusus: opsi yang belum terkonfirmasi wajib mengikuti
  audit akurasi; jangan menebak isi dropdown dari gambar yang tidak terbaca

### 2026-07-28 - R3 Selesai

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tetap `20260701000019`; production tetap `20260701000016`
- Yang selesai: seluruh pekerjaan R3 langkah 0-2; katalog pekerjaan yang
  terbaca lengkap; error mempertahankan input; Back melewati validasi native;
  badge membedakan SIMPERUM/diisi warga/koreksi warga; lookup ulang menjaga
  override warga; BOM dua model lama dibersihkan agar JSON login valid
- File utama: file R3 pada entri sebelumnya ditambah
  `docs/engineering/uji_pendataan_warga_r3.php`
- Uji dijalankan: R3 **16/16**, R1 **18/18**, R2 **5/5**, perjalanan Warga lama
  **19/19**, seluruh lint PHP terkait hijau
- Data uji dibersihkan: ya; check menghapus akun/profil/draft sintetis sendiri
  dan mempertahankan snapshot cache yang dipakai bersama
- Yang belum selesai: R4 struktur, sanitasi, lokasi, peta, foto, dan dokumen;
  langkah 3-6 masih placeholder
- Langkah pertama agent berikutnya: mulai R4 dari katalog yang sudah
  terkonfirmasi di `AUDIT_AKURASI_FORM_WARGA_R3.md`
- Larangan/peringatan khusus: jangan menambah opsi yang ditandai OPEN dan jangan
  menaruh koordinat, foto, atau dokumen di webroot/log

### 2026-07-28 - R4 Selesai

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tidak ada migrasi baru; skema 19 sudah memuat kolom terenkripsi
  dan tabel `sf_berkas_penilaian`
- Yang selesai: cabang kondisi bangunan, calon lahan, sanitasi, pembiayaan,
  lokasi terenkripsi, peta Leaflet, foto per jenis bukti, status unggahan,
  private storage, penghapusan metadata PNG/JPEG, dan replace-cleanup
- File utama: `Warga.php`, `Housing_assessment_model.php`,
  `pages/warga/pendataan.php`, `uji_pendataan_warga_r4.php`
- Uji dijalankan: R4 HTTP **44/44**; R3 **16/16**; R1 **18/18**; R2 **5/5**;
  perjalanan Warga lama **19/19**; browser nyata menampilkan cabang calon
  lahan, enam input foto, dan peta interaktif
- Data uji dibersihkan: ya; akun/profil/draft/ledger/file fisik R4 dibersihkan
  oleh shutdown cleanup, snapshot cache bersama tidak dihapus
- Yang belum selesai: R5 ruleset dan rekomendasi; `Kamar Mandi/Jamban` tetap
  nonaktif karena opsi sumber belum terbaca lengkap, Berkas Verval tetap milik
  admin dan bukan syarat warga
- Langkah pertama agent berikutnya: implementasikan ruleset eksplisit
  `SIM-2026-01` dan review/rekomendasi R5 tanpa rule-builder generik
- Larangan/peringatan khusus: jangan menjadikan field nonaktif ikut scoring;
  berkas warga tetap privat dan tidak boleh disajikan lewat URL langsung

### 2026-07-28 - R5 Selesai

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tidak ada migrasi baru; tetap `20260701000019`
- Yang selesai: ruleset eksplisit aktif `SIM-2026-01`, routing kandidat dari
  desil sumber, evaluasi berstatus dan reason code per program, hash input
  efektif tanpa PII, penyimpanan rekomendasi atomik bersama langkah wizard,
  histori ruleset lama tetap utuh, reader dibatasi ownership, serta halaman
  review server-driven dengan label simulasi
- Batas fakta: Omah Sekeng tetap `needs_data` sampai kebutuhan rumah/perbaikan
  punya field terkonfirmasi; Rumah Apung tetap `needs_data` sampai data
  pesisir/rob tersedia; FLPP/Oemah Lestari baru `potential`, bukan persetujuan
- File utama: `Warga_ruleset.php`, `Warga.php`,
  `Housing_assessment_model.php`, `pages/warga/pendataan.php`, dan
  `docs/engineering/uji_pendataan_warga_r5.php`
- Uji dijalankan: R5 **59/59**; R4 **44/44**; R3 **16/16**; gateway R2
  **5/5**; perjalanan Warga lama **19/19**; seluruh lint PHP terkait hijau;
  browser nyata menampilkan PB `eligible`, Omah Sekeng `needs_data`, dan Rumah
  Apung `needs_data` beserta alasan manusia
- Data uji dibersihkan: ya; check R5 membersihkan akun, assessment,
  rekomendasi, dan data sintetis miliknya
- Yang belum selesai: R6 submit assessment immutable, pilihan rekomendasi
  aktif, antrean baru, layar admin, dan alur minta perbaikan
- Langkah pertama agent berikutnya: mulai R6 dari snapshot immutable dan satu
  transaksi submit idempoten; jangan menyambungkan tombol review langsung ke
  antrean legacy
- Larangan/peringatan khusus: jangan mengubah `potential` menjadi klaim layak,
  jangan menghitung desil dari penghasilan, dan naikkan versi ruleset saat
  syarat bisnis berubah

### 2026-07-28 - R6 Selesai

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: `20260701000020`; production tetap `20260701000016`
- Yang selesai: submit assessment immutable dan idempoten, pilihan hanya dari
  rekomendasi aktif `eligible`/`potential`, antrean bertaut rekomendasi tanpa
  PII plaintext baru, riwayat keputusan, scope admin kab/kota, detail sumber
  vs koreksi, bukti privat, `needs_revision`, versi perbaikan baru, resubmit
  ke tiket yang sama, serta status/catatan/tindakan perbaikan di `/akun`
- File utama: migrasi 20, `Housing_assessment_model.php`, `Warga.php`,
  `Admin.php`, `Admin_Kabkota.php`, dua view antrean admin,
  `pages/warga/pendataan.php`, `pages/pengaturan/index.php`, dan
  `docs/engineering/uji_pendataan_warga_r6.php`
- Uji dijalankan: R6 **58/58**; R5 **59/59**; R4 **44/44**; R3 **16/16**;
  gateway **5/5** termasuk 20 lookup serentak menjadi satu snapshot; perjalanan
  Warga lama **19/19**; lint PHP dan `git diff --check` hijau
- Browser nyata: warga memilih Stimulan Pembangunan Baru, submit memperoleh
  tiket dan melihat status menunggu; Admin Kab/Kota Semarang menerima tiket,
  membuka detail, dan melihat tiga keputusan termasuk minta perbaikan
- Data uji dibersihkan: check R6 membersihkan data sintetisnya sendiri; satu
  tiket demo `PKP-BVNPNE` sengaja tersimpan lokal sebagai bahan presentasi
- Yang belum selesai: R7 pembuktian pada DB fresh, browser mobile, security
  matrix/rate limit lintas fitur, dan paket presentasi
- Langkah pertama agent berikutnya: mulai R7; jangan mengubah kontrak transaksi
  R6 atau menghubungkan API nyata sebelum kredensial/kontrak SIMPERUM tersedia
- Larangan/peringatan khusus: jangan push branch atau migrasi production tanpa
  izin user; production aktif masih skema 16

### 2026-07-28 - R9 Adapter API Offline

- Status fase: **IN PROGRESS**; aktivasi masih diblokir
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Mode aktif: `simulation`; key nyata hanya di `.env` lokal yang diabaikan Git
- Yang selesai: kontrak PDF diverifikasi visual, driver GET, token MD5,
  TLS/timeout/retry, error mapping, canonical mapping, raw snapshot terenkripsi,
  pemetaan opsi form yang kini resmi, guard endpoint legacy, dan perpindahan
  draft saat source mode atau snapshot sumber berubah
- Bukti runnable: contract API offline **27/27**, gateway simulation **5/5**,
  runner DB fresh R1-R7 + legacy **224/224**
- Yang belum selesai: smoke test live, konfirmasi quota/error, UAT mode API,
  dan endpoint/field desil
- Langkah pertama agent berikutnya: minta NIK uji resmi atau persetujuan
  eksplisit pemilik NIK untuk smoke test; jangan memakai NIK contoh PDF tanpa
  izin dan jangan mengaktifkan mode API sebelum sumber desil jelas
- Larangan/peringatan khusus: jangan menulis key ke Git/dokumen/log, jangan
  implementasikan `SaveDataRTLH`, jangan push/migrasi production

### 2026-07-28 - UX Review dan Jalur Pengajuan

- Status fase: **COMPLETED** lokal; tidak ada perubahan aturan kelayakan
- Yang selesai: review menampilkan panel tindakan untuk tiga keadaan. Hasil
  `eligible/potential` dapat dipilih lalu diajukan; `needs_data` membedakan data
  yang bisa diperiksa dari syarat yang belum tersedia di formulir; hasil tanpa
  kandidat mengarahkan ke pemeriksaan data atau layanan lain
- Aksesibilitas: pilihan program menjadi radio group ber-`fieldset`/`legend`,
  status tidak hanya mengandalkan warna, dan CTA terhubung ke teks konsekuensi
- Bukti runnable: R5 **59/59**, R6 **58/58**, lint PHP, dan browser nyata
  desktop + mobile 390×844
- Data uji: satu status rekomendasi demo diubah sementara untuk visual check,
  dipulihkan tepat satu baris; radio diuji tanpa menekan submit sehingga tidak
  ada tiket baru

### 2026-07-28 - Koreksi UX Unggah Kosong

- Status: **COMPLETED** lokal; tanpa perubahan skema atau aturan kelayakan
- Akar masalah: guard unggah menyamakan berkas kosong dengan assessment/jenis
  bukti ilegal sehingga aksi normal pengguna dibalas 404
- Perbaikan: assessment atau jenis bukti ilegal tetap 404; berkas kosong
  kembali ke langkah Lokasi & Bukti dengan toast error non-modal
- Bukti: lint PHP, check R4 **44/44**, dan klik browser nyata; unggah kosong
  tidak membuat ledger/file, PNG valid tetap tersimpan, dan IDOR tetap ditolak
- Rilis: belum di-commit atau di-push

### 2026-07-28 - Pusat Notifikasi Global

- Status: **COMPLETED** lokal; tanpa perubahan skema atau aturan kelayakan
- Portal, halaman autentikasi, dan dashboard memakai satu renderer flash serta
  API `KPKP.notify`
- Tipe yang diizinkan hanya `success`, `error`, `warning`, dan `info`; payload
  Warga seperti old input/lookup/errors tidak pernah dibaca secara massal
- Toast non-modal menggantikan `alert()` informasi dan toast lokal SRP2;
  modal/konfirmasi tetap dipakai untuk tindakan yang membutuhkan keputusan
- Unggah bukti kosong kini menjadi bukti alur server → redirect → toast global
- Bukti: check kontrak **10/10**, R4 **44/44**, flash login via HTTP, serta
  browser nyata pada Warga dan dashboard admin
- Rilis: belum di-commit atau di-push

### 2026-07-28 - R7 Selesai

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tetap `20260701000020`; production tetap `20260701000016`
- Yang selesai: runner DB fresh baseline→20, registry rate limit bersama,
  dimensi IP/akun/HMAC-NIK/objek, `429` + `Retry-After`, CSRF tanpa side effect,
  cleanup state limiter identik, audit security, browser desktop/mobile,
  perbaikan shell admin dan kartu `/akun` responsif, label simulasi persisten,
  serta paket presentasi berurutan
- Bukti runnable: fresh **224/224**, keamanan **5/5**, R6 **58/58**, dan
  `sys_rate_limits` sebelum/sesudah check identik
- Mutation proof: scope baca admin kab/kota dilepas sementara dan R6 merah
  tepat pada akses admin wilayah lain; guard dipulihkan lalu R6 **58/58**
- Browser nyata: wizard mobile, `/akun` mobile+desktop, detail admin
  mobile+desktop, dan label simulasi lulus visual; bug sidebar flex/cascade dan
  kartu mobile yang ditemukan browser diperbaiki. Console tidak memuat error
  aplikasi baru; hanya warning Tailwind CDN serta error reCAPTCHA tanpa sitekey
  yang merupakan keputusan sadar/konfigurasi lokal lama
- Titik masuk warga: tiga kartu lama di `/golek_omah` tetap dipertahankan dan
  satu kartu berbadge **Wizard Baru** menuju `/warga/pendataan` ditambahkan.
  Halaman wizard menampilkan carousel program sebelum tahapan pendataan; tombol
  carousel menggulir ke form warga, bukan kembali ke diagnosa lama
- Dokumen bukti: `UJI_FRESH_WARGA_R7.md`,
  `AUDIT_KEAMANAN_WARGA_R7.md`, `BUKTI_BROWSER_WARGA_R7.md`, dan
  `PAKET_PRESENTASI_WARGA_R7.md`
- Data uji dibersihkan: ya; DB sementara 0, `.env` kembali byte asli, akun
  sintetis 0, file/queue test dibersihkan, state rate limit dipulihkan
- Yang belum selesai: R8 rilis membutuhkan izin eksplisit user; R9 menunggu
  kontrak/kredensial API SIMPERUM
- Langkah pertama agent berikutnya: jangan push branch atau migrasi production;
  tunggu keputusan user apakah beralih ke domain fitur lain atau menyiapkan R8
- Larangan/peringatan khusus: production aktif auto-deploy dari branch ini dan
  masih skema 16; push tanpa migrasi 17-20 akan merusak fitur warga

### 2026-07-27 23:42 - R2 Gateway Simulasi

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: tetap `20260701000018`; production tetap `20260701000016`
- Yang selesai: config mode, tujuh fixture sintetis, normalizer allowlist,
  cache found/not-found/error, MySQL advisory lock, snapshot terenkripsi,
  respons termasking, label simulasi, dan penggantian mock controller
- File utama: `Simperum_gateway.php`, `application/fixtures/simperum/*.json`,
  `simperum.php`, `Housing_assessment_model.php`, `uji_simperum_gateway.php`
- Uji dijalankan: `php docs/engineering/uji_simperum_gateway.php` pada DB fresh
  → **5/5 hijau**, termasuk 20 proses serentak menghasilkan satu snapshot;
  `php docs/engineering/uji_perjalanan_warga.php` → **19/19 hijau**
- Data uji dibersihkan: ya; seluruh snapshot probe dan baris perjalanan dihapus
- Yang belum selesai: R3-R9; log tidak memuat raw payload berdasarkan inspeksi
  jalur kode, belum memakai observability eksternal
- Langkah pertama agent berikutnya: mulai R3 route `/warga/pendataan`, guard role
  warga, wizard bertahap, dan save/resume memakai model R1
- Larangan/peringatan khusus: API mode sengaja fail-closed `api_not_configured`;
  jangan menambahkan HTTP palsu atau mengirim canonical profile mentah ke browser

### 2026-07-27 23:37 - R1 Fondasi Assessment

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: `20260701000018`; production tetap `20260701000016`
- Yang selesai: lima tabel domain, relasi antrean legacy, model profil/snapshot/draft,
  enkripsi PII fail-closed, ownership, kecocokan NIK snapshot, dan optimistic lock
- File utama: migrasi 18, `Housing_assessment_model.php`, `Migrate::uji_warga_r1()`
- Uji dijalankan: DB fresh baseline→18; `php index.php migrate uji_warga_r1`
  pada `klinikpkp_uji_warga_r1_20260727` → **18/18 hijau**
- Data uji dibersihkan: ya; akun, assessment, dan dua snapshot sintetis terhapus
- Yang belum selesai: R2-R9; rollback migrasi 18 tersedia tetapi belum dijalankan
  karena rollback akan menghapus tabel baru
- Langkah pertama agent berikutnya: lanjutkan R2 melalui satu gateway; hapus mock
  bercabang dari `Program::api_cek_simperum()` setelah endpoint memakai gateway
- Larangan/peringatan khusus: jangan push branch atau migrasi production; jangan
  mengembalikan raw fixture/PII ke browser atau log

### 2026-07-27 - R0 Dokumentasi

- Status fase: **COMPLETED**
- Commit/branch: belum dibuat; `feature/homepage-portal-v2`
- Migrasi lokal: `20260701000017`
- Yang selesai: analisis 50 gambar, PRD, skema, keputusan simulation-first,
  dataset/ruleset demo, roadmap dan protokol handoff
- File utama: tiga dokumen yang tertaut di header
- Uji dijalankan: audit 56 label, hitung sumber 5+45, validasi link, pemeriksaan
  PII contoh → hijau
- Data uji dibersihkan: tidak ada data baru dibuat
- Yang belum selesai: seluruh R1-R9
- Langkah pertama agent berikutnya: mulai R1 dengan membaca skema aktual dan
  menentukan migrasi setelah versi 17
- Larangan: jangan push branch; jangan migrasi production; jangan hardcode
  fixture di controller
