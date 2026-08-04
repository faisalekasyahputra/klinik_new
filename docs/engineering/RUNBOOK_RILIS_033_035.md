# Runbook rilis — jejak audit, triase aduan, janji temu (`033` → `035`)

**Ditulis 4 Agustus 2026.** Melanjutkan pola
[`RUNBOOK_RILIS_WIZARD_024.md`](RUNBOOK_RILIS_WIZARD_024.md), tapi **urutannya
kebalikannya** — baca §"Yang berbeda" sebelum menyentuh apa pun.

**Direktori production di server:**

```
~/domains/floralwhite-lion-710022.hostingersite.com/public_html
```

Perhatikan `.hostingersite.com`-nya. `floralwhite-lion-710022` adalah nama situs
di panel Hostinger, **bukan** nama direktori. Ada empat instalasi Klinik PKP di
akun `u504551489` (AGENTS.md §0a); yang benar adalah yang `git log`-nya
menunjukkan commit rilis.

---

## Keadaan awal yang sudah diukur (4 Agt 2026)

Dibaca dari server, bukan dari niat rilis:

| | Nilai |
|---|---|
| Skema production | **`20260701000032`** |
| Total tabel | **38** |
| Kode ter-deploy | `c348e45` |
| Belum di-push | 5 commit (`c1ab15a` … `7308810`) |

## 🔴 Yang sedang rusak sekarang, dan Fase 3 memperbaikinya tanpa push

Commit `34c1506` (3 Agt) **sudah ter-deploy**: `catat_audit()`, layar **Jejak
Audit**, dan **Akses Staf** lengkap dengan nonaktifkan-akun, reset-sandi, dan
buka-kunci. Migrasi `033` tidak pernah ikut jalan, jadi `sys_jejak_audit` **tidak
ada di sana**.

`catat_audit()` sengaja diam kalau tabelnya tidak ada:

```php
if ( ! $this->db->table_exists('sys_jejak_audit')) { return; }
```

Akibatnya di production hari ini: tombolnya bekerja, akun benar-benar
dinonaktifkan, sandi benar-benar direset — dan **nol baris jejak**. Layar Jejak
Audit membuka dan tampil kosong, yang terbaca sebagai "belum ada aktivitas",
bukan "tabelnya tidak ada". Itu persis kegagalan senyap yang tabel itu dibuat
untuk mencegah.

**Jejak yang hilang tidak bisa diisi ulang.** Begitu `033` mendarat, perekaman
hidup seketika — kodenya sudah menunggunya, tanpa push apa pun.

---

## Yang berbeda dari rilis 24

**Rilis 24 TERPAKSA push-dulu.** Rilis ini **migrasi-dulu, dan itu wajib.**
Ketiga migrasi aman terhadap kode yang sedang berjalan di server; kebalikannya
tidak.

| Migrasi | Terhadap kode yang ter-deploy sekarang | Kalau push duluan |
|---|---|---|
| `033` `sys_jejak_audit` | Tabel baru. Kode sudah menunggunya. | — |
| `034` `aduan.bidang` NULL-able | Kode lama selalu mengirim kode bidang sah; kolom NULL-able tetap menerimanya. | `simpan_aduan()` menyisipkan `NULL` ke kolom `NOT NULL`. `db_debug` MATI → insert gagal **senyap**, **setiap** pengiriman aduan gagal. |
| `035` `forum_janji_temu` | Tabel baru, nol kode lama menyentuhnya. | `Umum::detail()` memanggil `hidup_untuk_topik()` → `get()` mengembalikan `FALSE` → `->row()` pada `bool` = **fatal 500** di halaman topik forum, untuk pemilik topik. |

AGENTS.md §17 sempat menuliskan urutan terbalik ini dan sudah dibetulkan
(`341d8d7`); baris aslinya bahkan mengeja alasannya sendiri di kalimat yang sama.

**Rollback tersedia, tapi tidak untuk semuanya.** `down()` ketiganya menolak
kalau datanya sudah terisi (`033` kalau ada jejak, `034` kalau ada aduan belum
ditriase, `035` kalau ada agenda berjalan). Itu disengaja. Selama Fase 3 baru
selesai dan belum ada yang memakai, ketiganya masih bisa turun.

---

## Fase 0 — keadaan awal (baca-saja)

```
ssh hostinger
```

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && git log -1 --oneline && git status --porcelain && php index.php migrate status
```

**GERBANG:**

- Baris pertama harus `DB: 31.97.208.59 / u504551489_klinikstg`. Kalau
  `127.0.0.1 / klinikpkp`, itu mesin lokal — **BERHENTI**.
- Versi skema harus **`20260701000032`**. Bukan itu → **BERHENTI**, peta
  lingkungan sudah bergeser dari yang diasumsikan runbook ini.
- `git status --porcelain` **wajib kosong.** Ini yang menggantung deploy 30 Jul:
  satu berkas migrasi tertinggal untracked, `git pull` menolak menimpanya, dan
  deploy berhenti diam-diam.

> ℹ️ `Migrate status` di server adalah **versi lama** — keluarannya berhenti di
> `kkn_magang_divisi` dan tidak menyebut `sys_jejak_audit` sama sekali. Itu bukan
> bukti tabelnya tidak ada; enumeratornya memang belum tahu harus memeriksanya
> (diperbaiki di `6283acb`, belum ter-deploy). Karena itu verifikasi Fase 4
> membaca `information_schema` langsung, bukan layar ini.

## Fase 1 — backup, dan buktikan backup-nya utuh

> **DB production TIDAK di localhost.** `DB_HOST=31.97.208.59` — baca dari `.env`
> server, jangan hafalkan dari sini. Tanpa `-h`, `mysqldump` diam-diam mencoba
> `localhost` dan gagal dengan `Access denied ...@'localhost'` — pesan yang
> sangat mudah dibaca sebagai "password salah".

```
cd ~ && set -o pipefail && mysqldump -h 31.97.208.59 -u u504551489_klinikstg -p u504551489_klinikstg | gzip > ~/backup_klinik_pre_035.sql.gz && echo "--- DUMP OK ---" && ls -lh ~/backup_klinik_pre_035.sql.gz && echo "CREATE TABLE: $(zcat ~/backup_klinik_pre_035.sql.gz | grep -c '^CREATE TABLE')"
```

**`set -o pipefail` bukan hiasan.** Status pipa diambil dari perintah TERAKHIR,
dan `gzip` selalu sukses — juga saat ia cuma memampatkan galat kosong. Tanpa
pipefail, `mysqldump` gagal pun rantai `&&` jalan terus dan menghasilkan berkas
20 byte bernama meyakinkan. Itu terjadi sungguhan 30 Jul.

**GERBANG.** Harus muncul `DUMP OK`, ukuran wajar, dan **`CREATE TABLE: 38`**.
Lebih kecil → dump terpotong, **BERHENTI dan ulangi**. Berkas bernama `backup_*`
yang isinya nol lebih berbahaya daripada tidak ada backup, karena ia menenangkan.

## Fase 2 — salin TIGA berkas migrasi (dari lokal)

```bash
scp application/migrations/20260701000033_jejak_audit_pusat.php application/migrations/20260701000034_aduan_bidang_triase.php application/migrations/20260701000035_forum_janji_temu.php hostinger:~/domains/floralwhite-lion-710022.hostingersite.com/public_html/application/migrations/
```

Kalau `scp` menolak portnya, tambahkan `-P 65002` (nilai yang dipakai runbook
30 Jul).

**HANYA tiga berkas itu.** Jangan menyalin `Migrate.php`, `migration.php`, atau
berkas lain — semuanya TERLACAK git, dan menyuntingnya di server membuat
`git status` kotor sehingga deploy Fase 6 tertolak.

**GERBANG.** Di server:

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && git status --porcelain
```

Harus persis tiga baris `?? application/migrations/2026070100003{3,4,5}_*.php`.
Ada yang lain → **BERHENTI**.

## Fase 3 — migrasi

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && php index.php migrate
```

`Migrate::index()` memakai `latest()`, yang menargetkan **berkas tertinggi yang
ada di direktori** dan mengabaikan `migration_version` di config (yang di server
masih `…032`). Jadi ketiganya jalan berurutan.

**GERBANG.** Harus terbaca `Migrasi sukses, versi skema sekarang:
20260701000035`.

Gagal di tengah → **BERHENTI, jangan ulangi `migrate`.** Baca dulu Fase 4 untuk
tahu sejauh mana ia sampai, lalu §Rollback.

## Fase 4 — verifikasi dari `information_schema`, bukan dari "Migrasi sukses"

CI menandai migrasi berhasil **tanpa memeriksa nilai balik query-nya**. Dengan
`db_debug` mati di production, `CREATE TABLE` yang gagal tetap tercatat sukses.
Nomor versi bukan bukti; bentuk tabelnya bukti.

```
mysql -h 31.97.208.59 -u u504551489_klinikstg -p u504551489_klinikstg -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('sys_jejak_audit','forum_janji_temu'); SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aduan' AND COLUMN_NAME='bidang'; SELECT COUNT(*) fk_janji_temu FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='forum_janji_temu' AND CONSTRAINT_TYPE='FOREIGN KEY';"
```

**GERBANG — keempatnya, bukan salah satu:**

| Yang dicari | Harus |
|---|---|
| `TABLE_NAME` | **dua baris**: `sys_jejak_audit` dan `forum_janji_temu` |
| `aduan.bidang` `IS_NULLABLE` | `YES` |
| `aduan.bidang` `COLUMN_DEFAULT` | `NULL` (MariaDB mencetak string `NULL`) — yang penting **bukan** `umum` |
| `fk_janji_temu` | **3** |

`fk_janji_temu` kurang dari 3 berarti tabelnya lahir tanpa pengunci relasinya —
terlihat benar dari layar mana pun, dan baru ketahuan saat ada yang menghapus
akun atau topik. **BERHENTI** kalau bukan 3.

### Bonus yang sudah bisa dicek sekarang, sebelum push

Jejak audit sudah hidup begitu Fase 3 selesai. Buka **Akses Staf** di production,
lakukan satu tindakan kecil yang aman (mis. buka-kunci akun yang memang tidak
terkunci), lalu:

```
mysql -h 31.97.208.59 -u u504551489_klinikstg -p u504551489_klinikstg -e "SELECT aksi, ringkasan, created_at FROM sys_jejak_audit ORDER BY id DESC LIMIT 5;"
```

Ada barisnya → lubang senyap yang dijelaskan di atas sudah tertutup.

## Fase 5 — singkirkan berkas salinan, LALU push

Tiga berkas tadi masih untracked. Kalau dibiarkan, `git pull` deploy akan menolak
menimpanya dan deploy berhenti diam-diam — persis 30 Jul.

Dipindah, bukan dihapus, supaya masih ada kalau Fase 6 bermasalah:

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && mkdir -p ~/migrasi_terpakai && mv application/migrations/20260701000033_*.php application/migrations/20260701000034_*.php application/migrations/20260701000035_*.php ~/migrasi_terpakai/ && git status --porcelain && echo "--- WORKING TREE BERSIH DI ATAS INI ---"
```

**GERBANG.** `git status --porcelain` wajib **kosong**.

> 🔴 **JANGAN jalankan `php index.php migrate` lagi sampai deploy Fase 6
> mendarat.** Di jendela ini berkas tertinggi di direktori adalah `…032`,
> sementara DB sudah `…035` — dan `latest()` akan menargetkan `032`, artinya
> menjalankan `down()` untuk `035`, `034`, `033`. `sys_jejak_audit` yang baru
> saja dibuat dan masih kosong akan **lolos penjaganya dan ikut terhapus**.
> Jendela ini hanya selama deploy; jangan diisi perintah lain.

## Fase 6 — push (dari lokal)

```bash
git push origin feature/homepage-portal-v2
```

Cabang ini auto-deploy. Tunggu deploy, lalu di server:

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && git log -1 --oneline && git status --porcelain && php index.php migrate status
```

**GERBANG:**

- Commit di server = HEAD lokal (`7308810` atau lebih baru). Masih `c348e45` →
  deploy belum mendarat, **jangan lanjut**.
- `git status --porcelain` kosong.
- `Migrate status` sekarang versi baru, dan **harus** mencetak tiga baris yang
  sebelumnya tidak ada:

```
sys_jejak_audit: ADA
forum_janji_temu: ADA
aduan.bidang NULL-able (migrasi 034): YA
aduan.bidang DEFAULT 'umum' dicabut: YA
```

Tiga berkas di `~/migrasi_terpakai/` sekarang sudah kembali lewat git; boleh
dihapus setelah Fase 7 hijau.

## Fase 7 — bukti dari luar, lewat klik bukan curl

1. **`/umum/aduan`** — tidak ada lagi dropdown "Bidang Tujuan". Kirim satu aduan
   uji; harus berhasil, dan pesannya tidak menyebut nama bidang.
2. **`/Admin_Aduan`** (superadmin) — aduan tadi muncul, callout "menunggu
   diteruskan" tampil, kolom Bidang berisi `— pilih bidang —`. Teruskan ke satu
   bidang; baris jejaknya muncul di **Jejak Audit**.
3. **`/Admin_Bidang`** (admin bidang tujuan) — aduan tadi baru muncul di sini
   SETELAH ditriase, tidak sebelumnya.
4. **`/umum/papan_aduan`** — wajib login. Buka **sumber halamannya** dan cari
   isi aduan uji tadi: **tidak boleh ada**. Judul dan jawaban dinas saja.
5. **`/Umum/forum`** → buka satu topik milik akun sendiri yang sudah ada
   tanggapannya → panel **Janji Temu Konsultasi** muncul, ajukan.
6. **`/Admin_Konsultasi`** — pengajuan tadi muncul, tawarkan jadwal, lalu setujui
   dari sisi warga.
7. **`/golek_omah`** — kartu keempat sekarang **Cek Status RTLH**, bukan duplikat
   `warga/pendataan`. Buka **tanpa login**: harus mendarat di layar login.
8. **`/Cek_Rtlh`** setelah login — periksa satu NIK. Lihat §Catatan SIMPERUM di
   bawah sebelum menyimpulkan hasilnya salah.

**GERBANG.** Ada satu saja yang 500 atau tidak sesuai → §Rollback.

Hapus aduan uji dan topik uji setelah selesai.

### Catatan SIMPERUM — baca sebelum menguji Cek RTLH

`SIMPERUM_MODE` di `.env` server menentukan dari mana datanya:

| Nilai | Yang terjadi |
|---|---|
| `simulation` | Fixture sintetis. Layarnya memasang spanduk **MODE SIMULASI**; hanya NIK `...0001`–`...0005` yang "terdaftar". Berguna untuk memastikan layarnya hidup, **bukan** untuk menjawab pertanyaan warga. |
| `api` | Data RTLH sungguhan. Butuh `SIMPERUM_BASE_URL` **https** + kunci publik/privat terisi; kalau belum, `lookup()` memulangkan `api_not_configured` dan layarnya berkata "Koneksi SIMPERUM belum dikonfigurasi". |

Baca nilainya dari server, jangan diasumsikan:

```
cd ~/domains/floralwhite-lion-710022.hostingersite.com/public_html && grep -E '^SIMPERUM_(MODE|BASE_URL)=' .env
```

> ⚠️ **Kalau mode-nya diubah ke `api`, satu perilaku lain ikut berubah:**
> `Program::api_cek_simperum()` — endpoint publik lama di alur diagnosa — mulai
> menolak dengan **409** dan mengarahkan ke Wizard Warga. Itu memang disengaja
> (data RTLH nyata tidak lewat endpoint publik), tapi jangan kaget kalau alur
> diagnosa lama tiba-tiba berbeda; ia bukan rusak.

---

## Rollback

> 🔴 **TIDAK ADA perintah turun.** `Migrate` hanya punya `index()` (yang memanggil
> `latest()`) dan `status()` — **nol endpoint yang menerima versi tujuan.**
> `php index.php migrate 20260701000032` bukan rollback, ia memanggil method
> bernama `20260701000032` yang tidak ada. Versi pertama runbook ini menuliskan
> perintah itu sebagai jalur rollback utama; salah, dan diperbaiki sebelum
> dipakai. `down()` ketiga migrasi memang ditulis dengan hati-hati, tapi tidak
> ada pintu untuk memanggilnya dari aplikasi.

**Satu-satunya rollback adalah restore backup:**

```
cd ~ && zcat backup_klinik_pre_035.sql.gz | mysql -h 31.97.208.59 -u u504551489_klinikstg -p u504551489_klinikstg
```

Lalu kembalikan kode ke `c348e45` supaya kode dan skema kembali sepasang. Data
yang masuk **setelah** backup diambil akan hilang — itu harga yang dibayar, dan
alasan Fase 1 tidak boleh dilewati.

Konsekuensi praktisnya, dan ini yang membuat Fase 1 bukan formalitas: **kalau
Fase 3 gagal di tengah, tidak ada jalan mundur bertahap.** Skema separuh jadi
hanya bisa dibereskan dengan restore penuh. Jangan mulai Fase 3 sebelum Fase 1
lulus gerbangnya.

> Kalau kelak butuh rollback bertahap, tambahkan satu method
> `Migrate::turun($versi)` yang memvalidasi argumennya ke daftar berkas migrasi
> lalu memanggil `$this->migration->version()`. Jangan tambahkan sekarang, di
> tengah rilis — endpoint tulis baru yang belum pernah diuji bukan alat rollback,
> ia risiko kedua.

---

## Yang dibawa rilis ini

| Commit | Isi |
|---|---|
| `c1ab15a` | SRP2: syarat & formulir wajib login, keterangan per formulir |
| `7d5c6ad` | Aduan: pelapor tidak memilih bidang, superadmin men-triase, papan aduan |
| `341d8d7` | AGENTS.md: urutan rilis ber-migrasi dibalik ke yang benar |
| `6283acb` | `Migrate::status()` buta terhadap `033`/`034` — diperbaiki |
| `7308810` | Janji temu konsultasi (`035`), `Admin_Konsultasi`, panel warga |

Suite lokal saat runbook ini ditulis: **33 suite, 1049 pemeriksaan, 0 merah, 0
bisu, 2 dilewati**. Dua suite baru (`uji_aduan_triase` 59, `uji_janji_temu` 55)
dua-duanya sudah dibuktikan bisa MERAH lewat mutasi.
