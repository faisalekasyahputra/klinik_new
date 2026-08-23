<!-- Tab Content: Bank Data -->
<?php
/**
 * Dulu dua kartu ("Statistik & Grafik", "Data Lainnya") yang mengarah
 * ke halaman lain. Permintaan user 23 Agt 2026: ganti jadi viewer PDF
 * flipbook LANGSUNG di tab ini, di bawah judul "Bank Data" - kedua
 * kartu itu ditimpa sepenuhnya, bukan ditambah.
 *
 * Akses ke Statistika sekarang HANYA lewat base_url('Statistika')
 * langsung (controllernya tidak dihapus, cuma tidak ditautkan dari
 * sini lagi) - kalau nanti ternyata masih dibutuhkan sebagai tautan
 * dari tab ini juga, itu permintaan terpisah, jangan diasumsikan.
 *
 * $pdf_url/$contoh dikirim dari Index::tab_bankdata() - lihat catatan
 * lengkap soal dokumen CONTOH di situ dan di Dokumen::index()
 * (partial viewer yang sama dipakai kedua tempat).
 */
?>
<div class="py-4 sm:py-6 px-1 sm:px-2 font-outfit">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-chart-pie  text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Bank Data</h2>
    </div>

    <?php $this->load->view('pages/data_spasial/_dokumen_viewer', ['pdf_url' => $pdf_url, 'contoh' => $contoh]); ?>
</div>
