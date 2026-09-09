<?php
/**
 * Cetak halaman rekap - dipakai bareng oleh Pantau Rekam Data (superadmin),
 * Rekap Perumahan, dan Rekap Kawasan (admin kab/kota).
 *
 * Satu partial, bukan disalin tiga kali - kalau struktur cangkang admin
 * (admin/index.php) berubah, cukup satu tempat yang perlu ikut diperbarui.
 *
 * Alasannya SAMA PERSIS dengan blok cetak di pages/program/hasil_diagnosa.php
 * (yang menutup butir A10c untuk portal publik): cangkang admin memasang
 * `overflow:hidden` + tinggi tetap berlapis (.admin-shell, pembungkus
 * konten, #main-content) supaya sidebar dan topbar tetap diam saat halaman
 * digulir. Tanpa dibuka paksa di sini, Ctrl+P cuma mencetak satu layar penuh
 * lalu memotong sisanya - halaman "berhasil" dicetak padahal isinya hilang.
 *
 * Permintaan user 17 Agt 2026: "Rekap rekam data bisa diexport ke excel
 * atau pdf". PDF di sini BUKAN berkas PDF sungguhan (tidak ada pustaka PDF
 * di proyek ini) - tombol "Cetak" memanggil `window.print()`, dan warga/
 * admin memakai "Simpan sebagai PDF" bawaan peramban. Pola yang sama
 * dengan tombol "Cetak Hasil" di hasil_diagnosa.php, supaya tidak ada dua
 * cara berbeda untuk hal yang sama di aplikasi ini.
 */
?>
<style>
@media print {
    html, body,
    .admin-shell, .admin-shell > div, #main-content, #main-content > div {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        position: static !important;
    }
    body { background: #fff !important; }

    /* Perabot layar yang tidak berarti di kertas. */
    .admin-sidebar, .admin-topbar, .admin-shell > footer,
    .admin-sidebar-backdrop, [data-notification-center], [data-file-viewer-modal],
    button, .no-print { display: none !important; }

    table { page-break-inside: auto; }
    tr, td, th { page-break-inside: avoid; }

    /* Latar berwarna dibuang: printer kantor umumnya hitam-putih, dan warna
       pastel di atas kertas putih berubah jadi abu yang menurunkan kontras. */
    * { background-image: none !important; box-shadow: none !important; color: #000 !important; }
}
</style>
