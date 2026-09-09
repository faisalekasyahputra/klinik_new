<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Sertifikat KKN</title>
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <!--
        Halaman POLOS (tanpa navbar/sidebar portal) - permintaan user 22 Agt
        2026: tombol Cetak membuka TAB BARU yang isinya cuma sertifikat ini,
        bukan seluruh shell portal. CSS di bawah SENGAJA hanya yang dipakai
        sertifikatnya sendiri (design-system.css untuk --portal-*, tailwind
        untuk kelas util, FontAwesome untuk ikon) - tidak memuat Alpine/jQuery/
        AOS/Swiper/notifications.js seperti layouts/head.php karena halaman
        ini tidak punya satu pun elemen yang membutuhkannya.
    -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/design-system.css?v=' . filemtime('assets/css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind-generated.css?v=' . filemtime('assets/css/tailwind-generated.css')) ?>">
    <script defer src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">

    <style>
        html, body { background: #f0f6f7; }
        .theme-light {
            --portal-bg: #f0f6f7;
            --portal-bg-card: #ffffff;
            --portal-border: rgba(0, 80, 95, 0.08);
            --portal-brand: #00838f;
            --portal-text: #0a1a1f;
            --portal-text-muted: #5b7a80;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="theme-light min-h-screen py-6 px-2 sm:py-10">
    <div class="mx-auto max-w-2xl no-print">
        <p class="text-center text-xs font-black uppercase tracking-[0.2em] text-[color:var(--portal-text-muted)]">Cetak Sertifikat</p>
    </div>

    <?php
    /* Kartu 90% lebar + PDF sungguhan di dalamnya - permintaan user 22 Agt
       2026 ("perlebar 90% cardnya dan isi dengan embed pdf"). PDF-nya
       dibangun FPDF di KemitraanPortal::sertifikat_kkn_pdf() dari SESI yang
       sama (satu sumber data, bukan dua salinan konten sertifikat yang bisa
       menyimpang - dulu markup di sini adalah satu-satunya salinan;
       sekarang PDF itulah salinan tunggalnya, halaman ini cuma
       membingkainya). <embed>, bukan <iframe> - PDF ditampilkan langsung
       oleh viewer bawaan peramban (Chrome/Firefox/Edge), lengkap dengan
       kontrol cetak/unduh miliknya sendiri, jadi tombol Cetak kustom yang
       lama (mencetak HALAMAN HTML ini, bukan PDF-nya) dilepas - akan
       mencetak bingkai kosong ini, bukan sertifikatnya. */
    ?>
    <div class="mx-auto mt-6 rounded-3xl border-2 border-[color:var(--portal-brand)] bg-[color:var(--portal-bg-card)] p-3 shadow-sm print:mt-0 print:rounded-none print:border-4 print:shadow-none" style="width: 90%;">
        <embed src="<?= base_url('KemitraanPortal/sertifikat_kkn_pdf') ?>" type="application/pdf"
               style="width: 100%; height: 80vh; border-radius: 1rem;">
    </div>

    <div class="mx-auto mt-6 flex flex-wrap items-center justify-center gap-3 no-print" style="width: 90%;">
        <?php
        /* Tombol Cetak LANGSUNG ke sertifikat_kkn_pdf() (bukan tab baru lagi)
           - permintaan user 22 Agt 2026. Tab ini sudah tab tersendiri untuk
           cetak; membuka tab KETIGA dari sini cuma menambah lapisan. Tanpa
           target="_blank": klik menavigasi tab yang sama ke PDF-nya, dan
           viewer PDF bawaan peramban yang mengambil alih dari situ, lengkap
           dengan kontrol cetak/unduh miliknya sendiri. */
        ?>
        <a href="<?= base_url('KemitraanPortal/sertifikat_kkn_pdf') ?>"
           class="inline-flex items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-white transition hover:opacity-90">
            <i class="fa-solid fa-print" aria-hidden="true"></i> Cetak
        </a>
        <button type="button" onclick="window.close()"
                class="rounded-xl border border-[color:var(--portal-border)] px-5 py-3 text-sm font-bold text-[color:var(--portal-text)]">
            Tutup Tab
        </button>
    </div>
</body>
</html>
