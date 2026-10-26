<?php
/* Aturan satu program (mengikuti rancangan 26 Okt 2026): ditawarkan + desil sasaran, cek data, baris matriks milik
   program ini, uji coba yang menyebut program ini. Tanpa draf semuanya baca-saja. Simpan lewat Admin_Aturan::simpan(). */
$ada_draf = ! empty($draf);
$kunci = $ada_draf ? '' : ' disabled';
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$judul = 'text-sm font-black text-gray-900 dark:text-white';
$petunjuk = 'text-xs text-gray-500 dark:text-brand-muted';
$pilih = 'rounded-md border border-gray-200 bg-transparent px-2 py-1.5 text-xs text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-white';
$desil = $aturan ? array_map('intval', explode(',', $aturan['desil_sasaran'])) : [];
$P = Matriks_program_ruleset::PILIHAN;
$opsi = function ($kolom, $nilai, $bebas = TRUE) use ($P) {
    $h = $bebas ? '<option value="">Bebas</option>' : '';
    foreach ($P[$kolom] as $k => $l) { $h .= '<option value="' . $k . '"' . ($k === $nilai ? ' selected' : '') . '>' . html_escape($l) . '</option>'; }
    return $h;
};
$baris_matriks = function ($i, array $r) use ($opsi, $pilih, $kunci, $p) {
    $h = '<tr data-baris-matriks class="align-top">';
    $h .= '<td class="px-2 py-2"><select name="baris[' . $i . '][rentang_penghasilan]" class="' . $pilih . '"' . $kunci . '>' . $opsi('rentang_penghasilan', $r['rentang_penghasilan'] ?? '', FALSE) . '</select>';
    foreach (array_filter(explode(',', (string) ($r['desil'] ?? ''))) as $d) { $h .= '<input type="hidden" name="baris[' . $i . '][desil][]" value="' . (int) $d . '">'; }
    $h .= '</td>';
    foreach (['rumah', 'lahan', 'lingkungan', 'finansial', 'usia', 'keluarga'] as $k) {
        $h .= '<td class="px-2 py-2"><select name="baris[' . $i . '][' . $k . ']" class="' . $pilih . '"' . $kunci . '>' . $opsi($k, $r[$k] ?? '') . '</select></td>';
    }
    $h .= '<td class="px-2 py-2"><input name="baris[' . $i . '][nama_skema]" maxlength="100" required value="' . html_escape($r['nama_skema'] ?? $p['nama_program']) . '" class="w-48 ' . $pilih . '"' . $kunci . '></td>';
    $h .= '<td class="px-2 py-2 text-center"><input type="checkbox" name="baris[' . $i . '][hapus]" value="1" aria-label="Hapus baris"' . $kunci . '></td></tr>';
    return $h;
};
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul' => $p['nama_program'],
    'jh_deskripsi' => '<a class="underline" href="' . base_url('Admin_Katalog_Program') . '">Katalog program</a> / <a class="underline" href="' . base_url('Admin_Aturan') . '">Aturan rekomendasi</a>'
        . ' · kode <code>' . html_escape($p['kode_program']) . '</code>',
    'jh_aksi' => $ada_draf ? '<span class="lencana-draf">Draf belum terbit</span>' : '',
]); ?>
<?php $this->load->view('admin/aturan/_gaya'); ?>

<?php if ( ! $ada_draf): ?>
<form method="post" action="<?= base_url('Admin_Aturan/buat_draf') ?>" class="kartu-admin isi-kartu mb-4 flex flex-wrap items-center justify-between gap-3"><?= $csrf ?>
    <input type="hidden" name="program_id" value="<?= (int) $p['id'] ?>">
    <p class="<?= $petunjuk ?>">Ini aturan yang sedang berlaku (baca saja). Buat draf untuk mengubahnya; perubahan baru berlaku setelah diterbitkan.</p>
    <button class="tombol-utama" data-buat-draf><i class="ph ph-pencil-simple"></i><span>Buat draf</span></button>
</form>
<?php endif; ?>

<form method="post" action="<?= base_url('Admin_Aturan/simpan/' . (int) $p['id']) ?>" class="tumpuk-bagian" data-form-aturan><?= $csrf ?>
    <section class="kartu-admin isi-kartu" data-kartu-desil>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="<?= $judul ?>">1. Desil sasaran <span class="font-normal <?= $petunjuk ?>">(kelompok kesejahteraan yang ditawari program ini)</span></h2>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="program[ikut]" value="1"<?= $aturan ? ' checked' : '' ?><?= $kunci ?> data-ikut>
                <b>Ditawarkan ke warga</b></label>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <?php for ($d = 1; $d <= 10; $d++): ?>
            <label class="chip-desil"><input type="checkbox" name="program[desil][]" value="<?= $d ?>"<?= in_array($d, $desil, TRUE) ? ' checked' : '' ?><?= $kunci ?> aria-label="Desil <?= $d ?>"><span><?= $d ?></span></label>
            <?php endfor; ?>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-gray-700 dark:text-gray-300">
            <label class="flex items-center gap-2"><input type="checkbox" name="program[tanpa_desil]" value="1"<?= ! empty($aturan['kandidat_tanpa_desil']) ? ' checked' : '' ?><?= $kunci ?>>
                Tetap ditawarkan saat desil warga belum ada</label>
            <label class="flex items-center gap-2">Urutan tampil <input type="number" min="1" max="99" name="program[urutan]" value="<?= (int) ($aturan['urutan'] ?? 99) ?>" class="w-16 <?= $pilih ?>"<?= $kunci ?>></label>
        </div>
    </section>

    <section class="kartu-admin isi-kartu" data-kartu-cek>
        <h2 class="<?= $judul ?>">2. Data wajib sebelum warga bisa memilih program ini</h2>
        <div class="mt-3 grid gap-2 sm:grid-cols-2">
            <?php foreach (Warga_ruleset::JENIS_CEK as $k => $c): ?>
            <label class="pilih-cek text-sm text-gray-800 dark:text-gray-200"><input type="radio" name="program[cek]" value="<?= $k ?>" class="mt-0.5"<?= ($aturan['jenis_cek'] ?? 'pembiayaan') === $k ? ' checked' : '' ?><?= $kunci ?>>
                <span><?= html_escape($c['label']) ?></span></label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="kartu-admin isi-kartu" data-kartu-matriks>
        <h2 class="<?= $judul ?>">3. Baris matriks rekomendasi awal <span class="font-normal <?= $petunjuk ?>">(warga cocok bila memenuhi salah satu baris; tidak wajib)</span></h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-gray-500 dark:text-brand-muted"><tr>
                    <th class="px-2 py-2">Pendapatan</th><th class="px-2 py-2">Rumah</th><th class="px-2 py-2">Lahan</th><th class="px-2 py-2">Lingkungan</th>
                    <th class="px-2 py-2">Kerja</th><th class="px-2 py-2">Usia</th><th class="px-2 py-2">Keluarga</th><th class="px-2 py-2">Nama di matriks</th><th class="px-2 py-2">Hapus</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5" data-isi-matriks>
                    <?php foreach ($baris as $i => $r) { echo $baris_matriks($i, $r); } ?>
                </tbody>
            </table>
            <?php if ( ! $baris): ?><p class="<?= $petunjuk ?> py-2" data-matriks-kosong>Belum ada baris matriks untuk program ini.</p><?php endif; ?>
        </div>
        <?php if ($ada_draf): ?>
        <template data-templat-baris><?= $baris_matriks('__I__', []) ?></template>
        <button type="button" class="tombol-kedua mt-3" data-tambah-baris><i class="ph ph-plus"></i><span>Tambah baris</span></button>
        <?php endif; ?>
    </section>

    <?php if ($uji): ?>
    <section class="kartu-admin isi-kartu" data-uji-coba>
        <h2 class="<?= $judul ?>">4. Uji coba sebelum terbit <span class="font-normal <?= $petunjuk ?>">(contoh warga sintetis yang ditawari program ini)</span></h2>
        <ul class="mt-3 divide-y divide-gray-200 text-sm dark:divide-white/5">
            <?php foreach ($uji['contoh'] as $c): $a = in_array($p['nama_program'], $c['aktif'][0], TRUE); $b = in_array($p['nama_program'], $c['draf'][0], TRUE); ?>
            <li class="flex flex-wrap justify-between gap-2 py-2"<?= $a !== $b ? ' data-berubah' : '' ?>>
                <span class="text-gray-800 dark:text-gray-200"><?= html_escape($c['label']) ?></span>
                <span class="<?= $a !== $b ? 'teks-ubah font-bold' : 'text-emerald-600 dark:text-emerald-400' ?>"><?= $b ? 'Ditawarkan' : 'Tidak ditawarkan' ?><?= $a !== $b ? ($b ? ' (baru)' : ' (dicabut)') : '' ?></span>
            </li>
            <?php endforeach; ?>
            <?php if ( ! $uji['contoh']): ?><li class="py-2 <?= $petunjuk ?>">Belum ada contoh warga yang ditawari program ini. Simpan draf untuk melihat hasilnya.</li><?php endif; ?>
        </ul>
    </section>
    <?php endif; ?>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="<?= base_url('Admin_Aturan') ?>" class="tombol-kedua"><span>Kembali</span></a>
        <?php if ($ada_draf): ?><button class="tombol-utama" data-simpan-draf><i class="ph ph-floppy-disk"></i><span>Simpan draf</span></button><?php endif; ?>
    </div>
</form>
<?php if ($ada_draf): ?>
<script>
// Tambah baris matriks dari templat; indeks unik supaya isian tidak tertimpa.
(function () {
    var isi = document.querySelector('[data-isi-matriks]'), templat = document.querySelector('[data-templat-baris]'), n = 1000;
    document.querySelector('[data-tambah-baris]').addEventListener('click', function () {
        var t = document.createElement('tbody');
        t.innerHTML = templat.innerHTML.replace(/__I__/g, String(n++));
        isi.appendChild(t.firstElementChild);
        var kosong = document.querySelector('[data-matriks-kosong]'); if (kosong) { kosong.remove(); }
        isi.lastElementChild.querySelector('select').focus();
    });
})();
</script>
<?php endif; ?>
