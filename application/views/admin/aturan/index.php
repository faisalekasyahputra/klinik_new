<?php
/* Aturan rekomendasi (Admin_Aturan). Tanpa draf: aturan terbit tampil baca-saja dengan tombol Buat draf.
   Dengan draf: dua formulir (program yang ditawarkan, baris matriks), uji coba, lalu Terbitkan. Nilai pilihan
   diambil dari daftar tetap mesin, jadi yang tampil di sini persis yang dikenal mesin. */
$ada_draf = ! empty($draf);
$kunci = $ada_draf ? '' : ' disabled';
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$judul = 'mb-1 text-sm font-black text-gray-900 dark:text-white';
$petunjuk = 'text-xs text-gray-500 dark:text-brand-muted';
$pilih = 'rounded-md border border-gray-200 bg-transparent px-1.5 py-1 text-xs text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-white';
$P = Matriks_program_ruleset::PILIHAN;
$opsi = function ($kolom, $nilai, $bebas = TRUE) use ($P) {
    $h = $bebas ? '<option value="">Bebas</option>' : '';
    foreach ($P[$kolom] as $k => $l) { $h .= '<option value="' . $k . '"' . ($k === $nilai ? ' selected' : '') . '>' . html_escape($l) . '</option>'; }
    return $h;
};
$baris_matriks = function ($i, array $r) use ($opsi, $pilih, $kunci) {
    $h = '<tr data-baris-matriks class="align-top">';
    $h .= '<td class="px-2 py-2 text-xs text-gray-400" data-no>' . ($i === '__I__' ? '' : $i + 1) . '</td>';
    $h .= '<td class="px-2 py-2"><select name="baris[' . $i . '][rentang_penghasilan]" class="' . $pilih . '"' . $kunci . '>' . $opsi('rentang_penghasilan', $r['rentang_penghasilan'] ?? '', FALSE) . '</select>';
    foreach (array_filter(explode(',', (string) ($r['desil'] ?? ''))) as $d) { $h .= '<input type="hidden" name="baris[' . $i . '][desil][]" value="' . (int) $d . '">'; }
    $h .= '</td>';
    foreach (['rumah', 'lahan', 'lingkungan', 'finansial', 'usia', 'keluarga'] as $k) {
        $h .= '<td class="px-2 py-2"><select name="baris[' . $i . '][' . $k . ']" class="' . $pilih . '"' . $kunci . '>' . $opsi($k, $r[$k] ?? '') . '</select></td>';
    }
    $h .= '<td class="px-2 py-2"><input name="baris[' . $i . '][nama_skema]" maxlength="100" required value="' . html_escape($r['nama_skema'] ?? '') . '" class="w-56 ' . $pilih . '"' . $kunci . '></td>';
    $h .= '<td class="px-2 py-2 text-center"><input type="checkbox" name="baris[' . $i . '][hapus]" value="1" aria-label="Hapus baris"' . $kunci . '></td></tr>';
    return $h;
};
$daftar = fn(array $x) => $x ? html_escape(implode(', ', $x)) : '<span class="italic text-gray-400">tidak ada</span>';
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_deskripsi' => 'Program yang ditawarkan ke warga dan matriks rekomendasi awal. Perubahan disunting di <b>draf</b>, diuji coba, lalu <b>diterbitkan</b>; '
        . 'pengajuan yang sudah masuk antrean tidak berubah.',
    'jh_aksi' => '<a href="' . base_url('Admin_Katalog_Program') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Katalog program</span></a>',
]); ?>

<div class="tumpuk-bagian">
    <section class="kartu-admin isi-kartu flex flex-wrap items-center justify-between gap-3" data-status-aturan>
        <div>
            <p class="<?= $judul ?>">Versi terbit: <code><?= html_escape($aktif['kode'] ?? '-') ?></code></p>
            <p class="<?= $petunjuk ?>">Dipakai mesin sejak <?= ! empty($aktif['diterbitkan_pada']) ? html_escape(date('d-m-Y H:i', strtotime($aktif['diterbitkan_pada']))) : '-' ?>.
                <?= $ada_draf ? 'Draf <code>' . html_escape($draf['kode']) . '</code> sedang disunting, belum berlaku.' : 'Tidak ada draf. Buat draf untuk mengubah aturan.' ?></p>
        </div>
        <?php if ( ! $ada_draf): ?>
        <form method="post" action="<?= base_url('Admin_Aturan/buat_draf') ?>"><?= $csrf ?>
            <button class="tombol-utama" data-buat-draf><i class="ph ph-pencil-simple"></i><span>Buat draf</span></button>
        </form>
        <?php else: ?>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">Draf belum terbit</span>
        <?php endif; ?>
    </section>

    <form method="post" action="<?= base_url('Admin_Aturan/simpan_program') ?>" class="kartu-admin isi-kartu" data-form-program><?= $csrf ?>
        <h2 class="<?= $judul ?>">1. Program yang ditawarkan</h2>
        <p class="<?= $petunjuk ?> mb-3">Program dicentang "Ditawarkan" muncul di hasil rekomendasi warga bila desilnya sesuai dan cek-nya terpenuhi.
            "Desil kosong" = tetap ditawarkan saat desil warga belum ada.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-gray-500 dark:text-brand-muted"><tr>
                    <th class="px-2 py-2">Ditawarkan</th><th class="px-2 py-2">Program</th><th class="px-2 py-2">Desil sasaran</th>
                    <th class="px-2 py-2">Desil kosong</th><th class="px-2 py-2">Cek data</th><th class="px-2 py-2">Urutan</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                <?php foreach ($katalog as $p): $a = $aturan[$p['id']] ?? NULL; $desil = $a ? array_map('intval', explode(',', $a['desil_sasaran'])) : []; $n = 'program[' . (int) $p['id'] . ']'; ?>
                    <tr data-program="<?= html_escape($p['kode_program']) ?>">
                        <td class="px-2 py-2"><input type="checkbox" name="<?= $n ?>[ikut]" value="1"<?= $a ? ' checked' : '' ?><?= $kunci ?>></td>
                        <td class="px-2 py-2"><b class="text-gray-900 dark:text-white"><?= html_escape($p['nama_program']) ?></b>
                            <div class="text-gray-400"><code><?= html_escape($p['kode_program']) ?></code><?= (int) $p['aktif'] === 1 ? '' : ' · nonaktif di katalog' ?></div></td>
                        <td class="px-2 py-2 whitespace-nowrap"><?php for ($d = 1; $d <= 10; $d++): ?>
                            <label class="mr-1 inline-flex flex-col items-center"><span class="text-[10px] text-gray-400"><?= $d ?></span>
                            <input type="checkbox" name="<?= $n ?>[desil][]" value="<?= $d ?>"<?= in_array($d, $desil, TRUE) ? ' checked' : '' ?><?= $kunci ?>></label>
                        <?php endfor; ?></td>
                        <td class="px-2 py-2 text-center"><input type="checkbox" name="<?= $n ?>[tanpa_desil]" value="1"<?= ! empty($a['kandidat_tanpa_desil']) ? ' checked' : '' ?><?= $kunci ?>></td>
                        <td class="px-2 py-2"><select name="<?= $n ?>[cek]" class="<?= $pilih ?>"<?= $kunci ?>>
                            <?php foreach (Warga_ruleset::JENIS_CEK as $k => $c): ?>
                            <option value="<?= $k ?>"<?= ($a['jenis_cek'] ?? 'pembiayaan') === $k ? ' selected' : '' ?>><?= html_escape($c['label']) ?></option>
                            <?php endforeach; ?></select></td>
                        <td class="px-2 py-2"><input type="number" min="1" max="99" name="<?= $n ?>[urutan]" value="<?= (int) ($a['urutan'] ?? 99) ?>" class="w-16 <?= $pilih ?>"<?= $kunci ?>></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($ada_draf): ?><button class="tombol-utama mt-3" data-simpan-program><i class="ph ph-floppy-disk"></i><span>Simpan program di draf</span></button><?php endif; ?>
    </form>

    <form method="post" action="<?= base_url('Admin_Aturan/simpan_matriks') ?>" class="kartu-admin isi-kartu" data-form-matriks><?= $csrf ?>
        <h2 class="<?= $judul ?>">2. Matriks rekomendasi awal</h2>
        <p class="<?= $petunjuk ?> mb-3">Satu baris = satu kombinasi kriteria. Warga cocok dengan baris bila semua kolom yang bukan "Bebas" terpenuhi;
            urutan baris = prioritas. Nama program di matriks tampil di "Rekomendasi awal".</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-gray-500 dark:text-brand-muted"><tr>
                    <th class="px-2 py-2">No</th><th class="px-2 py-2">Penghasilan</th><th class="px-2 py-2">Rumah</th><th class="px-2 py-2">Lahan</th>
                    <th class="px-2 py-2">Lingkungan</th><th class="px-2 py-2">Kerja</th><th class="px-2 py-2">Usia</th><th class="px-2 py-2">Keluarga</th>
                    <th class="px-2 py-2">Nama program di matriks</th><th class="px-2 py-2">Hapus</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5" data-isi-matriks>
                <?php foreach ($matriks as $i => $r) { echo $baris_matriks($i, $r); } ?>
                </tbody>
            </table>
        </div>
        <?php if ($ada_draf): ?>
        <template data-templat-baris><?= $baris_matriks('__I__', []) ?></template>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" class="tombol-kedua" data-tambah-baris><i class="ph ph-plus"></i><span>Tambah baris</span></button>
            <button class="tombol-utama" data-simpan-matriks><i class="ph ph-floppy-disk"></i><span>Simpan matriks di draf</span></button>
        </div>
        <?php endif; ?>
    </form>

    <?php if ($ada_draf && $uji): ?>
    <section class="kartu-admin isi-kartu" data-uji-coba>
        <h2 class="<?= $judul ?>">3. Uji coba: draf dibanding versi terbit</h2>
        <p class="<?= $petunjuk ?> mb-3"><?= count($uji['contoh']) ?> contoh warga sintetis. <b data-jumlah-berubah><?= (int) $uji['berubah'] ?> contoh berubah hasil</b>
            (program yang ditawarkan atau rekomendasi awal).</p>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-gray-500 dark:text-brand-muted"><tr><th class="px-2 py-2">Contoh warga</th><th class="px-2 py-2">Ditawarkan (terbit)</th>
                    <th class="px-2 py-2">Ditawarkan (draf)</th><th class="px-2 py-2">Rekomendasi awal (draf)</th></tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                <?php foreach ($uji['contoh'] as $c): ?>
                    <tr class="<?= $c['berubah'] ? 'bg-amber-50 dark:bg-amber-500/10' : '' ?>"<?= $c['berubah'] ? ' data-berubah' : '' ?>>
                        <td class="px-2 py-2"><?= html_escape($c['label']) ?><?= $c['berubah'] ? ' <b class="text-amber-700 dark:text-amber-300">berubah</b>' : '' ?></td>
                        <td class="px-2 py-2"><?= $daftar($c['aktif'][0]) ?></td>
                        <td class="px-2 py-2"><?= $daftar($c['draf'][0]) ?></td>
                        <td class="px-2 py-2"><?= $daftar($c['draf'][1]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="kartu-admin isi-kartu flex flex-wrap items-center justify-between gap-3" data-aksi-draf>
        <form method="post" action="<?= base_url('Admin_Aturan/terbitkan') ?>" class="flex flex-wrap items-center gap-3"><?= $csrf ?>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="konfirmasi" value="1" required>
                Saya sudah memeriksa uji coba; terbitkan untuk semua penilaian warga berikutnya.</label>
            <button class="tombol-utama" data-terbitkan><i class="ph ph-rocket-launch"></i><span>Terbitkan draf</span></button>
        </form>
        <form method="post" action="<?= base_url('Admin_Aturan/buang_draf') ?>" data-konfirmasi="Semua perubahan di draf dibuang. Aturan terbit tidak berubah." data-konfirmasi-judul="Buang draf?" data-konfirmasi-label="Buang draf" data-konfirmasi-bahaya><?= $csrf ?>
            <button class="tombol-kedua"><i class="ph ph-trash"></i><span>Buang draf</span></button>
        </form>
    </section>
    <?php endif; ?>

    <section class="kartu-admin isi-kartu">
        <h2 class="<?= $judul ?>">Riwayat versi</h2>
        <ul class="text-xs text-gray-600 dark:text-gray-300">
            <?php foreach ($riwayat as $v): ?>
            <li><code><?= html_escape($v['kode']) ?></code> · <?= $v['status'] === 'terbit' ? '<b>terbit</b>' : 'arsip' ?>
                · <?= ! empty($v['diterbitkan_pada']) ? html_escape(date('d-m-Y H:i', strtotime($v['diterbitkan_pada']))) : '-' ?>
                <?= $v['catatan'] ? '· ' . html_escape($v['catatan']) : '' ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<?php if ($ada_draf): ?>
<script>
// Tambah baris matriks dari templat; nomor indeks unik supaya isian tidak tertimpa.
(function () {
    var isi = document.querySelector('[data-isi-matriks]'), templat = document.querySelector('[data-templat-baris]'), n = isi.children.length + 1000;
    document.querySelector('[data-tambah-baris]').addEventListener('click', function () {
        var t = document.createElement('tbody');
        t.innerHTML = templat.innerHTML.replace(/__I__/g, String(n++));
        var baris = t.firstElementChild;
        isi.appendChild(baris);
        baris.querySelector('[data-no]').textContent = isi.children.length;
        baris.querySelector('select').focus();
    });
})();
</script>
<?php endif; ?>
