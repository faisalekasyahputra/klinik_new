# Design System — Klinik PKP

**Status palet per 18 Juli 2026; kontrak notifikasi ditambahkan 28 Juli 2026.** Diaudit langsung dari kode (grep menyeluruh ke `application/views/` dan `assets/css/`, 90 file, ribuan kemunculan warna dihitung) — bukan dari asumsi atau dokumen desain lama. Tujuan dokumen ini: jadi satu tempat yang bisa dipercaya soal warna dan umpan balik antarmuka yang **sebenarnya** dipakai di aplikasi, mana yang konsisten, dan mana yang belum.

---

## 1. Kondisi saat ini: 3 sumber warna yang tidak sinkron

| Sumber | Dipakai di mana | Status |
|---|---|---|
| [`docs/design/tokens.css`](tokens.css) + [`design-tokens.json`](design-tokens.json) | Tidak ada — **tidak di-`<link>` di manapun** | Dokumentasi murni, terputus dari aplikasi |
| `assets/css/design-system.css` | Situs publik (`application/views/layouts/head.php`) | Sumber yang beneran aktif, tapi punya `:root` sendiri dengan penamaan variable beda dari `tokens.css`, dan menyimpan "legacy aliases" (`--gold`, `--cyan`) dari rename lama yang belum dibereskan |
| Inline `tailwind.config` di `application/views/admin/layouts/head.php` | Panel admin | Definisi ketiga, sebagian nilainya sudah melenceng dari dua sumber di atas |

Selain itu, mayoritas warna di view (**82% dari 85 file**) tidak lewat variable/token sama sekali — ditulis langsung sebagai hex literal (kemungkinan besar lewat Tailwind arbitrary value, `bg-[#d6fb00]`), karena situs publik tidak punya `tailwind.config` yang mendefinisikan warna brand sebagai utility class.

**Dampak:** kalau nanti mau ganti satu warna brand, harus diubah manual di berapa ratus tempat, dan nggak ada jaminan semuanya konsisten (sudah kejadian — lihat §3).

---

## 2. Palet kanonis (diverifikasi dari pemakaian nyata)

Nilai di `design-tokens.json`/`tokens.css` di bawah ini **sudah dicek satu-satu** terhadap kode — kolom "Pemakaian" adalah jumlah kemunculan hex itu apa adanya di `application/views/` + `assets/css/`. Kecuali `semantic.success` (lihat catatan ⚠️ di tabel dan §3), semua nilai berikut adalah nilai yang **paling banyak dipakai** untuk peran warna itu, jadi ini realistis dijadikan acuan, bukan aspirasi.

| Token | Hex | Peran | Pemakaian |
|---|---|---|---|
| `brand.300` | `#d6fb00` | Brand primary (Electric Lime) | 1.423× / 55 file |
| `bg.body` | `#0a1a1f` | Background dasar (dark mode) | 237× / 48 file |
| `text.secondary` | `#8aacb0` | Teks sekunder | 183× / 26 file |
| `teal.500` | `#00a3b5` | Teal terang (aksen) | 127× / 21 file |
| `bg.card` | `#0f2a30` | Background kartu/panel | 110× / 26 file |
| `brand.100` / `text.primary` | `#ecffb6` | Lime cream (teks utama di dark bg) | 81× / 20 file |
| `semantic.success` | `#10b981` ⚠️ *(diputuskan 18 Jul — lihat §3, bukan nilai mayoritas)* | Hijau sukses | 9× / 4 file saat ini, akan jadi mayoritas setelah cleanup |
| `teal.700` | `#00545f` | Teal gelap (anchor sekunder) | 53× / 6 file |
| `semantic.warning` | `#ffd93d` | Kuning peringatan | 41× / 6 file |
| `semantic.error` | `#ff6b6b` | Merah error | 35× / 7 file |
| `text.muted` | `#5a7a80` | Teks redup | 32× / 13 file |
| `semantic.accent` | `#c084fc` | Ungu aksen | 26× / 4 file |
| `brand.400` | `#c2e600` | Brand hover/variant terang | 18× / 11 file |
| `bg.elevated` | `#1a3d45` | Background terangkat (modal, dropdown) | 13× / 4 file |
| `bg.primary` | `#0d2228` | Background sekunder | 6× / 5 file |
| `brand.500` | `#a8c700` | Brand variant gelap | 5× / 3 file |
| `brand.hover` *(baru ditambahkan)* | `#b5d400` | Brand hover — **cuma dipakai admin panel**, bukan bagian skala 50-900 | 4× / 4 file |

---

## 3. Drift — keputusan (diputuskan 18 Juli 2026)

4 pasang drift utama sudah diputuskan langsung dengan user. Kode belum diubah (di luar scope perbaikan dokumentasi ini) — ini catatan keputusan supaya siapa pun yang nanti bersihkan 1.300+ hex literal tahu arah yang benar, tanpa perlu nebak atau nanya ulang.

| Token kanonis | Varian nyasar | Pemakaian varian | Keputusan | Lokasi utama varian |
|---|---|---|---|---|
| `teal.700` = `#00545f` | `#005e6a` | 52× / 4 file (hampir seimbang, 53 vs 52) | ✅ **`#00545f` menang** — `#005e6a` di kode adalah yang salah, perlu diganti | `pengembang/archive/v_sertifikasi.php`, `perumahan/housing_carrier.php`, `umum/form_aduan.php` |
| `bg.card` = `#0f2a30` | `#0f2933` | 21× / 5 file | ✅ **`#0f2a30` menang**, satukan admin panel ke warna ini | `admin/dashboard.php`, `admin/layouts/head.php` |
| `semantic.warning` = `#ffd93d` | `#ffc107` (Bootstrap amber) | 38× / 4 file | ✅ **`#ffd93d` menang** | `data_spasial/sikumbang_view.php`, `pengembang/archive/syarat.php`, dll |
| `semantic.success` = `#10b981` ⚠️ | `#6bcb77` (token lama) | **59× / 8 file — ini yang MAYORITAS di kode saat ini** | ✅ **`#10b981` menang** — kebalikan dari mayoritas pemakaian. `design-tokens.json`/`tokens.css` sudah diupdate ke nilai baru ini. Yang perlu diganti nanti justru `#6bcb77` (59 kemunculan), bukan `#10b981` (9 kemunculan) | `#6bcb77` tersebar di 8 file; `#10b981` sudah ada di `auth/onboarding.php`, `auth/verify_pending.php` |

**Masih belum diputuskan** (varian sekunder, belum ditanyakan eksplisit):
- `semantic.warning` vs `#f59e0b` (Tailwind amber-500, 13× / 4 file) — asumsikan sementara ikut aturan warning di atas (`#ffd93d` menang) sampai ada keputusan terpisah
- `semantic.success` vs `#34d399` (Tailwind emerald-400, 9× / 3 file) — belum jelas apakah ini dianggap "dekat cukup" dengan `#10b981` yang baru menang, atau tetap drift terpisah
- `brand.hover` (`#b5d400`) vs `#b5d500` (2× / 2 file, kemungkinan salah ketik 1 digit) — dampaknya kecil, belum prioritas

---

## 4. Bukan bagian dari design system — jangan disentuh saat cleanup

`#003399`, `#002166`, `#e13300`, `#4f5155`, `#d0d0d0`, `#f9f9f9`, `#212529` — ini warna bawaan **default error page CodeIgniter 3** (`application/views/errors/html/error_*.php`), bukan hasil desain aplikasi. Kalau nanti ada yang audit ulang warna dan lihat warna-warna ini "tidak ada di token", itu memang benar — dan memang tidak perlu ditambahkan, karena bukan bagian dari brand Klinik PKP.

---

## 5. Yang TIDAK dikerjakan di update ini (rekomendasi lanjutan)

Update ini scope-nya dokumentasi: memperbaiki `tokens.css`/`design-tokens.json` supaya isinya jujur mencerminkan kode, dan mendata drift-nya. Yang belum dikerjakan (butuh keputusan/effort terpisah, jangan dianggap "sudah beres"):

1. **Satukan 3 sumber jadi 1.** Idealnya `assets/css/design-system.css` dan inline config admin dihapus, diganti load `tokens.css` langsung (atau sebaliknya) — supaya cuma ada 1 sumber kebenaran.
2. **Bikin `tailwind.config.js` yang benar** dengan `theme.extend.colors` dari token ini, supaya view bisa pakai `bg-brand-300` alih-alih `bg-[#d6fb00]` literal. Situs publik saat ini bahkan tidak punya config Tailwind sama sekali — itu sebabnya 1.423 kemunculan hex brand tertulis manual.
3. **Ganti 1.300+ hex literal di 70 file view** dengan token/class — ini kerjaan besar, sebaiknya dipecah per modul, bukan sekali jalan.
4. ~~Putuskan drift di §3~~ — **selesai 18 Jul 2026**, lihat tabel keputusan di §3. Termasuk perhatikan `semantic.success` yang butuh diganti ke arah yang tidak intuitif (ganti 59 kemunculan `#6bcb77`, bukan 9 kemunculan `#10b981`).
5. Situs publik saat ini load **Tailwind CDN JIT (`cdn.tailwindcss.com`) *dan* file `tailwind.min.css` hasil build** secara bersamaan — tidak disarankan Tailwind sendiri untuk production, di luar scope warna tapi terkait erat kalau nanti bikin `tailwind.config.js` beneran.

---

## 6. Kontrak notifikasi non-modal

Portal, halaman autentikasi, dan dashboard memakai satu pusat notifikasi:

- renderer bersama: `application/views/components/notification_center.php`;
- API browser: `KPKP.notify.success|error|warning|info(message, options)`;
- transport setelah redirect: flashdata CodeIgniter bernama persis `success`, `error`, `warning`, atau `info`.

Notifikasi bersifat **toast non-modal**: tidak memakai backdrop, tidak mengambil
fokus, dan tidak menghalangi pengguna melanjutkan pekerjaan. Modal/`confirm`
hanya dipakai ketika pengguna memang harus mengambil keputusan, terutama untuk
tindakan destruktif.

| Jenis | Warna | Perilaku awal | Aksesibilitas |
|---|---|---|---|
| `success` | `#10b981` | hilang setelah 5 detik | `role="status"` |
| `info` | `#00a3b5` | hilang setelah 5 detik | `role="status"` |
| `warning` | `#ffd93d` | tetap sampai ditutup | `role="alert"` |
| `error` | `#ff6b6b` | tetap sampai ditutup | `role="alert"` |

Semua pesan dirender sebagai teks biasa, memiliki tombol tutup, berhenti
menghitung waktu saat disentuh/fokus, dan menghormati
`prefers-reduced-motion`. Jangan mengiterasi seluruh flashdata: payload seperti
`warga_old_input`, `warga_lookup`, dan `warga_errors` dapat memuat data warga.

Toast hanya untuk umpan balik sementara. Riwayat pekerjaan atau pesan yang
harus dibaca ulang kelak membutuhkan inbox/notifikasi persisten tersendiri;
indikator lonceng tidak boleh mengarang jumlah pesan.
