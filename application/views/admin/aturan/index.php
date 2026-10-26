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
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-brand-muted">Versi terbit</p>
            <p class="mt-0.5 font-mono text-base font-bold text-gray-900 dark:text-white"><?= html_escape($aktif['kode'] ?? '-') ?></p>
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

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-daftar-program>
        <?php foreach ($katalog as $p): $a = $aturan[$p['id']] ?? NULL; $desil = $a ? array_map('intval', explode(',', $a['desil_sasaran'])) : []; ?>
        <article class="kartu-admin kartu-program" data-program="<?= html_escape($p['kode_program']) ?>">
            <header class="kartu-program-kepala">
                <div class="min-w-0">
                    <h2 class="text-[15px] font-bold leading-snug text-gray-900 dark:text-white"><?= html_escape($p['nama_program']) ?></h2>
                    <p class="mt-0.5 font-mono text-[11px] text-gray-400"><?= html_escape($p['kode_program']) ?></p>
                    <?php if ( ! $a): ?><span class="lencana-tidak mt-2 inline-block">Belum ditawarkan</span>
                    <?php elseif ((int) $p['aktif'] !== 1): ?><span class="lencana-tidak mt-2 inline-block">Nonaktif di katalog</span><?php endif; ?>
                </div>
                <img src="<?= base_url($p['gambar_tampil']) ?>" alt="" class="thumb-program" loading="lazy">
            </header>
            <dl class="kartu-program-isi">
                <div>
                    <dt>Desil sasaran</dt>
                    <dd class="flex flex-wrap gap-1"><?php for ($d = 1; $d <= 10; $d++): ?><span class="desil-mini<?= in_array($d, $desil, TRUE) ? ' nyala' : '' ?>"><?= $d ?></span><?php endfor; ?></dd>
                </div>
                <div>
                    <dt>Data wajib</dt>
                    <dd><?= $a ? html_escape(Warga_ruleset::JENIS_CEK[$a['jenis_cek']]['label'] ?? $a['jenis_cek']) : '<span class="text-gray-400">Belum diatur</span>' ?></dd>
                </div>
                <div>
                    <dt>Matriks awal</dt>
                    <dd><?= (int) ($jml_baris[$p['id']] ?? 0) ? (int) $jml_baris[$p['id']] . ' baris' : '<span class="text-gray-400">Tidak ada baris</span>' ?></dd>
                </div>
            </dl>
            <footer class="kartu-program-kaki">
                <span class="text-[11px] text-gray-400"><?= $a ? 'Urutan tampil ' . (int) $a['urutan'] : '' ?></span>
                <a href="<?= base_url('Admin_Aturan/program/' . (int) $p['id']) ?>" class="tombol-aksi"><i class="ph ph-sliders-horizontal" aria-hidden="true"></i><span>Atur</span></a>
            </footer>
        </article>
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
        <table class="mt-3 w-full text-left text-xs">
            <thead class="text-gray-500 dark:text-brand-muted"><tr><th class="py-1.5 pr-4 font-semibold">Versi</th><th class="py-1.5 pr-4 font-semibold">Status</th>
                <th class="py-1.5 pr-4 font-semibold">Diterbitkan</th><th class="py-1.5 font-semibold">Catatan</th></tr></thead>
            <tbody class="divide-y divide-gray-200 text-gray-700 dark:divide-white/5 dark:text-gray-300">
            <?php foreach ($riwayat as $v): ?>
                <tr><td class="py-2 pr-4 font-mono"><?= html_escape($v['kode']) ?></td>
                    <td class="py-2 pr-4"><?= $v['status'] === 'terbit' ? '<span class="lencana-ya">Terbit</span>' : 'Arsip' ?></td>
                    <td class="py-2 pr-4 whitespace-nowrap"><?= ! empty($v['diterbitkan_pada']) ? html_escape(date('d-m-Y H:i', strtotime($v['diterbitkan_pada']))) : '-' ?></td>
                    <td class="py-2"><?= html_escape((string) $v['catatan']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
