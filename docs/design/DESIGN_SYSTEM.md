# Design System — Klinik PKP

**Status per 18 Juli 2026.** Diaudit langsung dari kode (grep menyeluruh ke `application/views/` dan `assets/css/`, 90 file, ribuan kemunculan warna dihitung) — bukan dari asumsi atau dokumen desain lama. Tujuan dokumen ini: jadi satu tempat yang bisa dipercaya soal warna apa yang **sebenarnya** dipakai di aplikasi, mana yang konsisten, dan mana yang belum.

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

Nilai di `design-tokens.json`/`tokens.css` di bawah ini **sudah dicek satu-satu** terhadap kode — kolom "Pemakaian" adalah jumlah kemunculan hex itu apa adanya di `application/views/` + `assets/css/`. Semua nilai berikut adalah nilai yang **paling banyak dipakai** untuk peran warna itu, jadi ini realistis dijadikan acuan, bukan aspirasi.

| Token | Hex | Peran | Pemakaian |
|---|---|---|---|
| `brand.300` | `#d6fb00` | Brand primary (Electric Lime) | 1.423× / 55 file |
| `bg.body` | `#0a1a1f` | Background dasar (dark mode) | 237× / 48 file |
| `text.secondary` | `#8aacb0` | Teks sekunder | 183× / 26 file |
| `teal.500` | `#00a3b5` | Teal terang (aksen) | 127× / 21 file |
| `bg.card` | `#0f2a30` | Background kartu/panel | 110× / 26 file |
| `brand.100` / `text.primary` | `#ecffb6` | Lime cream (teks utama di dark bg) | 81× / 20 file |
| `semantic.success` | `#6bcb77` | Hijau sukses | 59× / 8 file |
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

## 3. Drift — warna kembar yang butuh keputusan (bukan saya putuskan sepihak)

Ini warna-warna yang **jelas dimaksudkan** sebagai warna yang sama dengan salah satu token di atas, tapi nilai hex-nya beda tipis. Saya tidak mengganti kode viewnya (di luar scope perbaikan dokumentasi ini) — ini daftar yang perlu keputusan desain sebelum ada yang bersihkan:

| Token kanonis | Varian nyasar | Pemakaian varian | Lokasi utama |
|---|---|---|---|
| `bg.card` (`#0f2a30`) | `#0f2933` | 21× / 5 file | `admin/dashboard.php`, `admin/layouts/head.php` — jelas berasal dari `tailwind.config` inline admin yang beda sumber |
| `teal.700` (`#00545f`) | `#005e6a` | **52× / 4 file — hampir seimbang, bukan sekadar typo minoritas** | `pengembang/archive/v_sertifikasi.php`, `perumahan/housing_carrier.php`, `umum/form_aduan.php` |
| `semantic.warning` (`#ffd93d`) | `#ffc107` (Bootstrap amber) | 38× / 4 file | `data_spasial/sikumbang_view.php`, `pengembang/archive/syarat.php`, dll |
| `semantic.warning` (`#ffd93d`) | `#f59e0b` (Tailwind amber-500) | 13× / 4 file | `auth/onboarding.php`, `data_spasial/statistika.php` |
| `semantic.success` (`#6bcb77`) | `#10b981` (Tailwind emerald-500) | 9× / 4 file | `auth/onboarding.php`, `auth/verify_pending.php` |
| `semantic.success` (`#6bcb77`) | `#34d399` (Tailwind emerald-400) | 9× / 3 file | `data_spasial/statistika.php`, `home/awal.php` |
| `brand.hover` (`#b5d400`) | `#b5d500` (kemungkinan salah ketik 1 digit) | 2× / 2 file | — |

**Yang paling mendesak diputuskan:** `teal.700` vs `#005e6a` — pemakaiannya nyaris 50/50 (53 vs 52), jadi bukan kasus "yang satu jelas benar, yang satu typo minoritas". Perlu ditentukan warna mana yang jadi standar resmi.

---

## 4. Bukan bagian dari design system — jangan disentuh saat cleanup

`#003399`, `#002166`, `#e13300`, `#4f5155`, `#d0d0d0`, `#f9f9f9`, `#212529` — ini warna bawaan **default error page CodeIgniter 3** (`application/views/errors/html/error_*.php`), bukan hasil desain aplikasi. Kalau nanti ada yang audit ulang warna dan lihat warna-warna ini "tidak ada di token", itu memang benar — dan memang tidak perlu ditambahkan, karena bukan bagian dari brand Klinik PKP.

---

## 5. Yang TIDAK dikerjakan di update ini (rekomendasi lanjutan)

Update ini scope-nya dokumentasi: memperbaiki `tokens.css`/`design-tokens.json` supaya isinya jujur mencerminkan kode, dan mendata drift-nya. Yang belum dikerjakan (butuh keputusan/effort terpisah, jangan dianggap "sudah beres"):

1. **Satukan 3 sumber jadi 1.** Idealnya `assets/css/design-system.css` dan inline config admin dihapus, diganti load `tokens.css` langsung (atau sebaliknya) — supaya cuma ada 1 sumber kebenaran.
2. **Bikin `tailwind.config.js` yang benar** dengan `theme.extend.colors` dari token ini, supaya view bisa pakai `bg-brand-300` alih-alih `bg-[#d6fb00]` literal. Situs publik saat ini bahkan tidak punya config Tailwind sama sekali — itu sebabnya 1.423 kemunculan hex brand tertulis manual.
3. **Ganti 1.300+ hex literal di 70 file view** dengan token/class — ini kerjaan besar, sebaiknya dipecah per modul, bukan sekali jalan.
4. **Putuskan drift di §3** dulu sebelum langkah 3, supaya nggak salah pilih nilai kanonis.
5. Situs publik saat ini load **Tailwind CDN JIT (`cdn.tailwindcss.com`) *dan* file `tailwind.min.css` hasil build** secara bersamaan — tidak disarankan Tailwind sendiri untuk production, di luar scope warna tapi terkait erat kalau nanti bikin `tailwind.config.js` beneran.
