# Runbook rilis — wizard Rekam Data Perumahan (`20260701000024`)

**Ditulis 30 Juli 2026.** Melanjutkan [`RUNBOOK_RILIS_30JUL2026.md`](RUNBOOK_RILIS_30JUL2026.md);
pola gerbangnya sama, isinya tidak. Baca §"Yang berbeda" dulu — rilis ini punya
satu sifat yang rilis sebelumnya tidak punya.

Ganti `<SITUS>` dengan `floralwhite-lion-710022`.

---

## Yang berbeda dari rilis 30 Jul

**`20260701000024` TIDAK BISA DITURUNKAN.** `down()`-nya menolak begitu
`rd_perumahan_baris` berisi, dan itu disengaja: migrasi ini mengubah `bulan`
(kumulatif 1–12) jadi `triwulan` (1–4, capaian triwulan itu saja). Tidak ada
pemetaan jujur dari "TW III" kembali ke satu bulan tertentu, jadi `down()`
memilih menolak daripada mengarang. Production **punya data** di tabel itu.

Konsekuensinya satu kalimat: **rollback rilis ini = restore backup.** Kalau
backup belum ada dan terverifikasi, migrasi tidak boleh dijalankan.

**Urutannya terpaksa push-dulu.** Berkas `…024` sampai ke server hanya lewat
deploy. Jadi tidak ada opsi migrate-dulu, dan ada jendela di mana kode baru
bertemu skema lama. Jendela itu hanya melumpuhkan Rekam Data Perumahan (peran
admin kab/kota); portal publik, antrean, SRP2, aduan tidak menyentuh tabel ini.
Persempit dengan menjalankan Fase 3 segera setelah Fase 2 mendarat.

---

## Fase 0 — keadaan awal (baca-saja)

```
ssh hostinger
cd ~/domains/<SITUS>/public_html && git log -1 --oneline && git status --porcelain && php index.php migrate status
```

**GERBANG.** Harus terbaca commit `8da6c4b` dan versi skema `20260701000023`.

- Versi bukan `…023` → **BERHENTI.** Peta lingkungan sudah bergeser dari yang
  diasumsikan runbook ini; jangan lanjut sampai selisihnya dimengerti.
- `git status --porcelain` **tidak kosong** → **BERHENTI dan bersihkan dulu.**
  Ini persis yang menggantung deploy 30 Jul: satu berkas migrasi tertinggal
  untracked di server, `git pull` menolak menimpanya, dan deploy diam-diam
  berhenti. Jangan pernah menyalin berkas migrasi ke server dengan tangan.

## Fase 1 — backup, dan buktikan backup-nya utuh

```
cd ~ && mysqldump -u u504551489_klinikstg -p u504551489_klinikstg | gzip > ~/backup_klinik_pre_wizard_024.sql.gz && ls -lh ~/backup_klinik_pre_wizard_024.sql.gz && zcat ~/backup_klinik_pre_wizard_024.sql.gz | grep -c "^CREATE TABLE"
```

**GERBANG.** Berkasnya harus tidak-nol DAN jumlah `CREATE TABLE` harus **36**
(jumlah tabel production sejak rilis 30 Jul). Kalau `zcat` gagal atau angkanya
lebih kecil, backup-nya terpotong — **BERHENTI**, ulangi. Backup yang tidak
pernah dibuka bukan backup.

## Fase 2 — push (dari lokal)

```bash
git push origin feature/homepage-portal-v2
```

Cabang ini auto-deploy. Setelah push, tunggu deploy lalu:

```
cd ~/domains/<SITUS>/public_html && git log -1 --oneline && git status --porcelain
```

**GERBANG.** Commit di server harus sama dengan HEAD lokal. Kalau masih
`8da6c4b`, deploy belum mendarat — jangan migrasi. Kalau `git status` tidak
kosong, deploy tersendat; bereskan dulu.

## Fase 3 — migrasi

```
cd ~/domains/<SITUS>/public_html && php index.php migrate && php index.php migrate status
```

**GERBANG.** Harus terbaca `Migrasi sukses, versi skema sekarang:
20260701000024`. Kalau gagal di tengah — **BERHENTI, jangan ulangi migrate.**
Langsung ke §Rollback: `…024` mengubah bentuk dalam beberapa ALTER berurutan,
dan menjalankannya lagi di atas keadaan setengah-jadi memperburuk, bukan
memperbaiki.

## Fase 4 — bukti dari luar

Buka sebagai admin kab/kota, lewat klik, bukan curl:

1. `/Rekam_Perumahan` — Capaian tampil, dua tabel (Rencana & Realisasi).
2. `/Rekam_Perumahan/riwayat` — daftar periode, tombol **Lihat capaian** tiap
   baris mendarat di triwulan yang benar.
3. `/Rekam_Perumahan/input` — wizard jalan sampai langkah terakhir.
4. `/Admin_Kabkota` — ganti tab status: **hanya kartu tabel** yang bertukar,
   judul dan posisi gulir tetap.

**GERBANG.** Ada satu saja yang 500 → §Rollback.

---

## Rollback

`migrate down` **bukan** opsi (lihat §Yang berbeda). Satu-satunya jalan:

```
cd ~ && zcat backup_klinik_pre_wizard_024.sql.gz | mysql -u u504551489_klinikstg -p u504551489_klinikstg && cd ~/domains/<SITUS>/public_html && php index.php migrate status
```

Lalu kembalikan kode ke `8da6c4b` supaya kode dan skema kembali sepasang.
Data yang masuk **setelah** backup diambil akan hilang — itu harga yang
dibayar, dan alasan Fase 1 tidak boleh dilewati.

---

## Utang yang sengaja dibawa, jangan dilaporkan sebagai bug baru

**Enam harness pra-wizard akan MERAH setelah migrasi ini** — `uji_rekam_data_d2`
sampai `d6` dan `Migrate::uji_rekam_data_d1()` masih menulis ke
`rd_perumahan_bagian`/`bulan`, tabel dan kolom yang tidak ada lagi. Mereka
diganti [`uji_wizard_rekam_perumahan.php`](uji_wizard_rekam_perumahan.php)
(38 pemeriksaan) dan `Migrate::uji_wizard_w2()` (31). Semuanya alat diagnostik
lokal, tidak ada di jalur pengguna. Belum dirapikan karena merapikannya berarti
memutuskan mana yang benar-benar tergantikan dan mana yang menguji hal lain —
keputusan tersendiri, bukan tempelan rilis.
