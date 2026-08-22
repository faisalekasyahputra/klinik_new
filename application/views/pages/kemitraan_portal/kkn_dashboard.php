<?php
/**
 * Dashboard KKN universitas - PENGGANTI formulir sekali-daftar lama
 * (KemitraanPortal::daftar('kkn')), permintaan user 21 Agt 2026. Satu akun
 * universitas mengelola BANYAK KKN dari waktu ke waktu di sini, masing-
 * masing dengan roster pesertanya sendiri (lihat kkn_batch.php).
 *
 * SHELL: dashboard terpadu (admin/index, sama dengan Status Pengajuan/
 * Profil Saya) - keputusan user 21 Agt 2026 ("Desainnya samakan dengan yang
 * ini"), BUKAN shell portal publik yang dipakai daftar.php/pendaftaran.php.
 * Ikon karenanya Phosphor (ph-*), BUKAN FontAwesome - shell admin tidak
 * memuat FontAwesome sama sekali.
 *
 * Identitas universitas (nama akun) dan kontaknya (No. HP) SENGAJA tidak
 * diminta ulang di formulir Tambah KKN - keduanya diambil dari akun sendiri
 * (session name + usr_users.phone), makanya tautan "Profil Saya" di sidebar
 * benar-benar dipakai untuk melengkapinya, bukan hiasan.
 */
$label = 'mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted';
$isian = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800'
    . ' transition-colors placeholder-gray-400 focus:border-brand-primary focus:outline-none'
    . ' focus:ring-1 focus:ring-brand-primary dark:border-white/10 dark:bg-black/20 dark:text-white'
    . ' dark:placeholder-brand-muted/60';
$berkas = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600'
    . ' file:mr-3 file:rounded-lg file:border-0 file:bg-brand-primary/10 file:px-3 file:py-1.5'
    . ' file:text-xs file:font-bold file:text-brand-primary dark:border-white/10 dark:bg-black/20 dark:text-brand-muted';
$petunjuk = 'mt-1 text-xs text-gray-500 dark:text-brand-muted';

$badge_kelas = ['Diajukan' => 'pending', 'Ditinjau Bidang' => 'process',
                'Diterima' => 'ok', 'Ditolak' => 'reject', 'Dibatalkan' => 'reject'];
?>
<div class="relative z-10">

    <!-- Header - pola sama dengan pages/pengaturan/index.php -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-brand-primary/10 flex items-center justify-center text-brand-primary text-xl">
                <i class="ph ph-graduation-cap"></i>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Dashboard KKN</h1>
                <p class="text-gray-500 dark:text-brand-muted text-sm"><?= html_escape($nama_akun ?: '-') ?> &middot; <?= html_escape($email_akun ?: '-') ?></p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('kkn-tambah-dialog').showModal()"
                class="inline-flex items-center gap-2 rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-bold text-white transition-opacity hover:opacity-90">
            <i class="ph ph-plus"></i> Tambah KKN
        </button>
    </div>

    <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">
        <?php if (empty($daftar_kkn)): ?>
            <div class="flex flex-col items-center justify-center text-center py-16 px-6">
                <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                    <i class="ph ph-tray"></i>
                </div>
                <p class="text-gray-500 dark:text-brand-muted font-medium">Belum ada KKN yang diajukan.</p>
                <p class="text-xs text-gray-400 dark:text-brand-muted/70 mt-1">Klik "Tambah KKN" untuk mengajukan yang pertama.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-4">Periode</th>
                            <th class="px-4 py-4">Keterangan</th>
                            <th class="px-4 py-4 text-center">Jumlah Peserta</th>
                            <th class="px-4 py-4">Status</th>
                            <th class="px-4 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                        <?php foreach ($daftar_kkn as $k): ?>
                        <tr>
                            <td class="px-4 py-4">
                                <?= $k->periode_mulai && $k->periode_selesai
                                    ? html_escape(tgl_id($k->periode_mulai, TRUE) . ' - ' . tgl_id($k->periode_selesai, TRUE))
                                    : '<span class="text-gray-400 dark:text-brand-muted/60">-</span>' ?>
                            </td>
                            <td class="px-4 py-4 max-w-[16rem] whitespace-normal text-gray-900 dark:text-white font-semibold"><?= html_escape($k->divisi_atau_tema ?: '-') ?></td>
                            <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white"><?= (int) $k->jumlah_peserta ?></td>
                            <td class="px-4 py-4">
                                <?= $this->load->view('admin/components/status_badge', ['label' => $k->status, 'kelas' => $badge_kelas[$k->status] ?? 'pending'], TRUE) ?>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="<?= base_url('KemitraanPortal/pendaftaran/' . (int) $k->id) ?>" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">Detail →</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<dialog id="kkn-tambah-dialog" style="padding:0;border:0;border-radius:1.5rem;width:min(92vw,640px);max-height:90vh;background:transparent">
    <div class="bg-white dark:bg-brand-card rounded-3xl overflow-hidden flex flex-col" style="max-height:90vh">
        <div class="flex items-center justify-between gap-3 px-6 py-5 border-b border-gray-200 dark:border-white/10">
            <h2 class="text-base font-black text-gray-900 dark:text-white">Tambah KKN</h2>
            <button type="button" onclick="document.getElementById('kkn-tambah-dialog').close()" aria-label="Tutup"
                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-white/10 text-gray-500 dark:text-brand-muted">✕</button>
        </div>
        <form method="POST" action="<?= base_url('KemitraanPortal/kkn_tambah') ?>" enctype="multipart/form-data"
              class="px-6 py-5 overflow-y-auto space-y-4">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="kt-mulai" class="<?= $label ?>">Periode Mulai</label>
                    <?php /* type="text" + readonly, BUKAN type="date" lagi - permintaan
                             user 22 Agt 2026 (datepicker, lihat flatpickr di
                             admin/layouts/head.php). readonly tetap menjaga jaminan lama
                             type="date": tidak ada string tanggal rusak yang bisa diketik
                             manual, cuma sekarang lewat flatpickr, bukan validator browser. */ ?>
                    <input id="kt-mulai" name="periode_mulai" type="text" readonly required class="<?= $isian ?>" placeholder="Pilih tanggal">
                </div>
                <div>
                    <label for="kt-selesai" class="<?= $label ?>">Periode Selesai</label>
                    <input id="kt-selesai" name="periode_selesai" type="text" readonly required class="<?= $isian ?>" placeholder="Pilih tanggal">
                </div>
            </div>

            <div>
                <label for="kt-keterangan" class="<?= $label ?>">Keterangan</label>
                <input id="kt-keterangan" name="keterangan" required maxlength="150" placeholder="Contoh: KKN Tematik Desa Sukamaju" class="<?= $isian ?>">
            </div>

            <div>
                <label for="kt-mitra" class="<?= $label ?>">Surat Permohonan Menjadi Mitra <span class="font-normal normal-case text-red-500">(wajib, PDF)</span></label>
                <input id="kt-mitra" name="file_surat_pengantar" type="file" accept=".pdf" required class="<?= $berkas ?>">
                <p class="<?= $petunjuk ?>">Memuat lokasi pelaksanaan, jumlah mahasiswa, dan periode pelaksanaan KKN. Format PDF, maksimal 5 MB.</p>
            </div>

            <div>
                <label for="kt-simperum" class="<?= $label ?>">Surat Permohonan Akun SIMPERUM <span class="font-normal normal-case text-red-500">(wajib, PDF)</span></label>
                <input id="kt-simperum" name="file_surat_simperum" type="file" accept=".pdf" required class="<?= $berkas ?>">
                <p class="<?= $petunjuk ?>">Memuat daftar nama mahasiswa peserta yang telah ditetapkan. Format PDF, maksimal 5 MB.</p>
            </div>

            <p class="<?= $petunjuk ?>">Roster peserta (NIM + Nama) diunggah terpisah lewat halaman Detail setelah KKN ini tersimpan.</p>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-white/10 pt-4">
                <button type="button" onclick="document.getElementById('kkn-tambah-dialog').close()"
                        class="rounded-xl border border-gray-200 dark:border-white/10 px-5 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300">Batal</button>
                <button type="submit" class="rounded-xl bg-brand-primary px-5 py-2.5 text-xs font-bold text-white">Ajukan KKN</button>
            </div>
        </form>
    </div>
</dialog>
<script>
(function () {
    'use strict';
    var dlg = document.getElementById('kkn-tambah-dialog');
    if (!dlg || !dlg.showModal) { return; }
    // Klik backdrop menutup - pola sama dengan modal lain di aplikasi ini
    // (file_viewer_modal.php, login_modal.php).
    dlg.addEventListener('click', function (e) { if (e.target === dlg) { dlg.close(); } });
    <?php if ($this->session->flashdata('kkn_tambah_gagal')): ?>
    // Formulir dibuka otomatis kalau baru saja gagal - lihat penanda
    // kkn_tambah_gagal yang di-set eksplisit oleh KemitraanPortal::kkn_tambah().
    // Tanpa ini pengguna melihat toast galat tanpa tahu formulir mana yang
    // dimaksud (modal sudah tertutup lagi setelah submit).
    dlg.showModal();
    <?php endif; ?>
})();
</script>
<script>
(function () {
    'use strict';
    /* Flatpickr - permintaan user 22 Agt 2026. Ditunggu sampai DOMContentLoaded
       dengan sengaja: skrip ini berjalan SEGERA saat parser sampai di sini
       (bukan deferred), sedangkan flatpickr.min.js di admin/layouts/head.php
       memang deferred (berjalan belakangan, sama seperti Alpine.js) - tanpa
       menunggu, `flatpickr` belum tentu terdefinisi saat baris ini dieksekusi. */
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof flatpickr === 'undefined') { return; }

        /* appendTo WAJIB diarahkan ke dalam <dialog> ini, BUKAN dibiarkan
           bawaan (document.body). <dialog>.showModal() menaruh dialognya di
           "top layer" browser - elemen APA PUN yang ditambahkan ke body DI
           LUAR dialog (termasuk kalender flatpickr, yang bawaannya nempel ke
           body) jadi tidak terlihat/tidak bisa diklik di belakang lapisan
           itu, walau z-index CSS diatur setinggi apa pun. Dibuktikan: tanpa
           appendTo ini, kalender sama sekali tidak muncul saat field diklik
           di dalam modal Tambah KKN. */
        var dlgUntukKalender = document.getElementById('kkn-tambah-dialog');

        var opsi = {
            locale: 'id',
            appendTo: dlgUntukKalender || undefined,
            // dateFormat: nilai SUNGGUHAN yang terkirim ke server (name="periode_mulai"),
            // harus tetap Y-m-d - KemitraanPortal::kkn_tambah() membandingkannya sebagai
            // string ("$selesai < $mulai") dan kolomnya DATE di database.
            dateFormat: 'Y-m-d',
            // altInput+altFormat: yang TERLIHAT pemakai memakai format Indonesia
            // panjang, sama seperti tgl_id() dipakai menampilkan tanggal di tabel
            // KKN pada halaman ini juga (mis. "22 Agustus 2026").
            altInput: true,
            altFormat: 'j F Y',
            allowInput: false,
        };
        var mulai = flatpickr('#kt-mulai', opsi);
        var selesai = flatpickr('#kt-selesai', opsi);

        // altInput membuat elemen TERLIHAT baru tanpa id aslinya - id tetap
        // menempel di input asli yang sekarang type="hidden". Tanpa baris ini,
        // <label for="kt-mulai"> menunjuk ke elemen tersembunyi, dan mengklik
        // labelnya tidak membuka kalender sama sekali.
        [mulai, selesai].forEach(function (fp) {
            if (fp && fp.altInput && fp.input && fp.input.id) {
                fp.altInput.id = fp.input.id;
                fp.input.removeAttribute('id');
            }
        });

        // Periode Selesai tidak boleh mendahului Periode Mulai - penjagaan yang
        // SAMA sudah ada di server (KemitraanPortal::kkn_tambah()), ini cuma
        // memberi tahu lewat kalender SEBELUM submit, bukan gerbang baru.
        if (mulai && selesai) {
            mulai.config.onChange.push(function (dates) {
                if (dates[0]) { selesai.set('minDate', dates[0]); }
            });
        }
    });
})();
</script>
