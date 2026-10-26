<?php
/* Aturan rekomendasi: ringkasan per program (kartu), tiap kartu membuka halaman aturan program itu
   (admin/aturan/program.php). Draf, uji coba ringkas, dan Terbitkan ada di halaman ini. */
$ada_draf = ! empty($draf);
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$judul = 'text-sm font-black text-gray-900 dark:text-white';
$petunjuk = 'text-xs text-gray-500 dark:text-brand-muted';
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_deskripsi' => 'Program yang ditawarkan ke warga dan matriks rekomendasi awal. Ubah di <b>draf</b>, periksa uji coba, lalu <b>terbitkan</b>. '
        . 'Pengajuan yang sudah masuk antrean tidak berubah.',
    'jh_aksi' => '<a href="' . base_url('Admin_Katalog_Program') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Katalog program</span></a>',
]); ?>
<?php $this->load->view('admin/aturan/_gaya'); ?>

<div class="tumpuk-bagian">
    <section class="kartu-admin isi-kartu flex flex-wrap items-center justify-between gap-3" data-status-aturan>
        <div>
            <p class="<?= $judul ?>">Versi terbit: <code><?= html_escape($aktif['kode'] ?? '-') ?></code></p>
            <p class="<?= $petunjuk ?> mt-1">Dipakai sejak <?= ! empty($aktif['diterbitkan_pada']) ? html_escape(date('d-m-Y H:i', strtotime($aktif['diterbitkan_pada']))) : '-' ?>.
                <?= $ada_draf ? 'Draf <code>' . html_escape($draf['kode']) . '</code> sedang disunting, belum berlaku.' : 'Buat draf untuk mengubah aturan.' ?></p>
        </div>
        <?php if ($ada_draf): ?>
        <span class="lencana-draf">Draf belum terbit</span>
        <?php else: ?>
        <form method="post" action="<?= base_url('Admin_Aturan/buat_draf') ?>"><?= $csrf ?>
            <button class="tombol-utama" data-buat-draf><i class="ph ph-pencil-simple"></i><span>Buat draf</span></button>
        </form>
        <?php endif; ?>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-daftar-program>
        <?php foreach ($katalog as $p): $a = $aturan[$p['id']] ?? NULL; $desil = $a ? array_map('intval', explode(',', $a['desil_sasaran'])) : []; ?>
        <a href="<?= base_url('Admin_Aturan/program/' . (int) $p['id']) ?>" class="kartu-admin isi-kartu kartu-program block" data-program="<?= html_escape($p['kode_program']) ?>">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="<?= $judul ?>"><?= html_escape($p['nama_program']) ?></p>
                    <p class="<?= $petunjuk ?>"><code><?= html_escape($p['kode_program']) ?></code><?= (int) $p['aktif'] === 1 ? '' : ' · nonaktif di katalog' ?></p>
                </div>
                <span class="<?= $a ? 'lencana-ya' : 'lencana-tidak' ?>"><?= $a ? 'Ditawarkan' : 'Belum ditawarkan' ?></span>
            </div>
            <div class="mt-3 flex flex-wrap gap-1" aria-label="Desil sasaran">
                <?php for ($d = 1; $d <= 10; $d++): ?><span class="desil-mini<?= in_array($d, $desil, TRUE) ? ' nyala' : '' ?>"><?= $d ?></span><?php endfor; ?>
            </div>
            <p class="<?= $petunjuk ?> mt-3"><?= $a ? html_escape(Warga_ruleset::JENIS_CEK[$a['jenis_cek']]['label'] ?? $a['jenis_cek']) : 'Belum ada aturan' ?>
                · <?= (int) ($jml_baris[$p['id']] ?? 0) ?> baris matriks</p>
            <p class="mt-3 text-xs font-bold text-brand-primary">Atur aturan <i class="ph ph-arrow-right"></i></p>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($ada_draf && $uji): $berubah = array_values(array_filter($uji['contoh'], fn($c) => $c['berubah'])); ?>
    <section class="kartu-admin isi-kartu" data-uji-coba>
        <h2 class="<?= $judul ?>">Uji coba sebelum terbit</h2>
        <p class="<?= $petunjuk ?> mt-1"><?= count($uji['contoh']) ?> contoh warga sintetis dibandingkan dengan versi terbit:
            <b data-jumlah-berubah class="<?= $berubah ? 'teks-ubah' : '' ?>"><?= count($berubah) ?> contoh berubah hasil</b>.</p>
        <?php if ($berubah): ?>
        <ul class="mt-3 divide-y divide-gray-200 text-xs dark:divide-white/5">
            <?php foreach ($berubah as $c): ?>
            <li class="flex flex-wrap justify-between gap-2 py-2" data-berubah>
                <span class="text-gray-800 dark:text-gray-200"><?= html_escape($c['label']) ?></span>
                <span><span class="text-gray-400"><?= html_escape(implode(', ', $c['aktif'][0]) ?: 'tidak ada') ?></span>
                    <i class="ph ph-arrow-right mx-1"></i><b class="teks-ubah"><?= html_escape(implode(', ', $c['draf'][0]) ?: 'tidak ada') ?></b></span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section class="kartu-admin isi-kartu flex flex-wrap items-center justify-between gap-3" data-aksi-draf>
        <form method="post" action="<?= base_url('Admin_Aturan/terbitkan') ?>" class="flex flex-wrap items-center gap-3"><?= $csrf ?>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="konfirmasi" value="1" required>
                Saya sudah memeriksa uji coba; terbitkan untuk penilaian warga berikutnya.</label>
            <button class="tombol-utama" data-terbitkan><i class="ph ph-rocket-launch"></i><span>Terbitkan draf</span></button>
        </form>
        <form method="post" action="<?= base_url('Admin_Aturan/buang_draf') ?>" data-konfirmasi="Semua perubahan di draf dibuang. Aturan terbit tidak berubah." data-konfirmasi-judul="Buang draf?" data-konfirmasi-label="Buang draf" data-konfirmasi-bahaya><?= $csrf ?>
            <button class="tombol-kedua"><i class="ph ph-trash"></i><span>Buang draf</span></button>
        </form>
    </section>
    <?php endif; ?>

    <section class="kartu-admin isi-kartu">
        <h2 class="<?= $judul ?>">Riwayat versi</h2>
        <ul class="mt-2 text-xs text-gray-600 dark:text-gray-300">
            <?php foreach ($riwayat as $v): ?>
            <li><code><?= html_escape($v['kode']) ?></code> · <?= $v['status'] === 'terbit' ? '<b>terbit</b>' : 'arsip' ?>
                · <?= ! empty($v['diterbitkan_pada']) ? html_escape(date('d-m-Y H:i', strtotime($v['diterbitkan_pada']))) : '-' ?>
                <?= $v['catatan'] ? '· ' . html_escape($v['catatan']) : '' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
