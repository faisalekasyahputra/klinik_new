# Protokol Agen Per-Peran

Dibaca oleh setiap agen di `.claude/agents/peran-*.md` **sebelum** menyentuh apa
pun. Berisi yang berlaku untuk semua peran; yang khas per peran ada di berkas
agennya masing-masing.

Tujuannya: menjalani situs ini sebagai manusia dengan peran tertentu, lalu
melaporkan **apa yang rusak** — bukan memperbaikinya.

---

## 0. Aturan yang tidak bisa ditawar

**LOKAL SAJA — `http://localhost/klinik_new`.**
Jangan pernah menyentuh `floralwhite-lion-710022.hostingersite.com`. Itu
production yang dipakai dinas; menekan tombol di sana mengubah data sungguhan.
Kalau `UJI_BASE_URL` mengarah ke selain localhost, **berhenti dan lapor**.

**Melapor, bukan memperbaiki.** Agen ini tidak menyunting kode. Menemukan cacat
lalu menambalnya sambil lalu membuat laporannya tidak bisa dipercaya — pembaca
tidak tahu lagi mana yang rusak dan mana yang sudah berubah. Kalau menemukan
sesuatu, catat lokasinya dan lanjutkan.

**Jangan hapus data yang bukan milikmu.** Data demo sengaja dipertahankan
(AGENTS.md §20) — empat akun `@example.test`, sebelas akun demo, dua pendaftaran
kemitraan, 25 slot 2027. Akun agen berakhiran `@agen.test`; **hanya itu** milikmu.

**Jangan mengarang.** Kalau sebuah langkah tidak bisa dijalani (butuh berkas
yang tidak ada, butuh persetujuan peran lain), tulis **"tidak teruji"** beserta
sebabnya. Laporan yang menandai hijau sesuatu yang tidak pernah dicoba lebih
berbahaya daripada laporan pendek.

---

## 1. Akun

Disiapkan `php docs/engineering/seed_agen_peran.php` (menolak jalan kalau DB-nya
bukan localhost). Sandi semua akun: **`AgenUji!2026`**

| Peran | Email | Cakupan |
|---|---|---|
| warga | `agen_warga@agen.test` | — |
| pengembang | `agen_pengembang@agen.test` | — |
| mahasiswa | `agen_mahasiswa@agen.test` | — |
| admin_kabkota | `agen_admin_kabkota@agen.test` | Kab. Cilacap (3301) |
| admin_bidang | `agen_admin_bidang@agen.test` | Bidang Kawasan Permukiman |
| admin | `agen_admin@agen.test` | seluruh sistem |

Kalau login gagal, jalankan ulang seed-nya sekali — akun disegarkan, bukan
diduplikasi. Kalau masih gagal, itu **temuan**, bukan alasan berhenti diam-diam.

---

## 2. Cara menjalani

**Utama: HTTP dengan sesi ber-cookie.** Cepat, dan cukup untuk sebagian besar
hal. Pola login (jalur NON-AJAX — lihat jebakan di §4):

```bash
JAR=$(mktemp)
TOK=$(curl -s -c $JAR -b $JAR http://localhost/klinik_new/Auth/login \
      | grep -o 'name="csrf_kpkp_token" value="[^"]*"' | sed 's/.*value="//;s/"//')
curl -s -c $JAR -b $JAR -L -o /dev/null -w '%{url_effective}\n' \
     -d "csrf_kpkp_token=$TOK&email=agen_warga@agen.test&password=AgenUji!2026" \
     http://localhost/klinik_new/Auth/do_login
```

Sesudah itu pakai `-c $JAR -b $JAR` untuk tiap permintaan berikutnya.

**Peramban dipakai kalau yang diperiksa memang butuh tata letak** — tumpang
tindih, terpotong, guliran mendatar, modal yang tidak muncul. HTTP tidak bisa
melihat itu; markup yang sah tetap bisa tampil rusak.

---

## 3. Yang diperiksa di tiap halaman

1. **Sampai?** Kode HTTP, dan halaman yang benar — bukan login, bukan 404.
2. **Terisi?** Ada isinya, bukan tabel kosong yang mengaku ada data.
3. **Terbaca?** Istilah sesuai bahasa dinas, tanggal berbahasa Indonesia,
   nol teks pengganti/placeholder yang lolos.
4. **Batas peran ditegakkan?** Coba satu URL milik peran LAIN. Harus ditolak.
   Ini pemeriksaan terpenting yang paling sering dilewat.
5. **Tidak meluap.** Di 1440px dan 375px: `document.documentElement.scrollWidth
   - clientWidth` harus 0.

---

## 4. Jebakan yang sudah memakan waktu — jangan diulang

**Login AJAX tidak mengarahkan.** Dengan header `X-Requested-With`,
`Auth/do_login` membalas JSON dan **tidak menyentuh `intended_url`**. Kalau mau
memeriksa ke mana user mendarat, login harus tanpa header itu.

**POST tanpa token CSRF tidak pernah sampai ke controller.** Ia ditolak lapisan
CSRF lebih dulu. Uji yang mem-POST tanpa token akan "lulus" tanpa menyentuh
apa pun. Ambil tokennya dari halaman mana pun yang berformulir.

**`scrollWidth` tidak bisa mengukur elemen `overflow:hidden`.** Nilainya jatuh
sama dengan `clientWidth`, jadi teks terpotong tetap terbaca "muat". Untuk lebar
teks: span melayang (`position:absolute`, tanpa flex/overflow) **sesudah**
`await document.fonts.ready` — kalau fontnya belum termuat, angkanya dari font
pengganti dan salah.

**Cocokkan pada posisi, bukan pada seluruh halaman.** `strpos($html, 'X')`
sudah empat kali memberi hasil palsu di proyek ini — teks yang dicari ternyata
ada di footer, di komentar HTML, atau di menu. Ikat ke elemen yang dimaksud.

**Selalu pasang pembanding.** Kalau mengukur sesuatu, ukur juga hal yang sudah
diketahui berbeda. Angka yang identik untuk dua hal yang jelas tidak sama adalah
tanda alat ukurnya yang salah — bukan temuan.

---

## 5. Bentuk laporan

Bahasa Indonesia. Ringkas. Format tiap temuan:

```
[BERAT|SEDANG|RINGAN] <halaman/URL>
  Diharapkan : ...
  Kenyataan  : ...
  Langkah    : 1) ... 2) ... 3) ...
  Bukti      : kode HTTP / cuplikan teks / angka terukur
```

- **BERAT** — data bocor lintas peran, gerbang jebol, data hilang/rusak, galat fatal.
- **SEDANG** — alur buntu, tombol tidak bekerja, angka salah.
- **RINGAN** — salah kata, tata letak meleset, istilah tidak konsisten.

Tutup dengan tiga baris: **dijalani** (berapa layar), **tidak teruji** (mana &
kenapa), **temuan** (berapa per tingkat). Kalau nol temuan, katakan begitu —
jangan mencari-cari supaya laporannya terlihat berisi.
