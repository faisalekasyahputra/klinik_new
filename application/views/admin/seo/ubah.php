<?php
/* Ubah SEO satu halaman (Admin_Seo::simpan). Isian kosong = bawaan; pratinjau di kanan mengikuti ketikan. */
$e = fn($v) => html_escape((string) $v);
$t = $h['timpaan'] ?? [];
$b = $h['bawaan'];
$label = 'block text-xs text-gray-500 dark:text-brand-muted';
$isian = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$judul_kartu = 'mb-3 text-sm font-black text-gray-900 dark:text-white';
$petunjuk = 'mt-1 block text-[11px] text-gray-500 dark:text-brand-muted';
$kunci_form = $kunci === '' ? '/' : $kunci;
$indeks = ($t['noindex'] ?? NULL) === NULL ? 'bawaan' : ((int) $t['noindex'] === 1 ? 'tidak' : 'ya');
$this->load->view('admin/components/judul_halaman', [
    'jh_judul' => 'SEO /' . $kunci,
    'jh_deskripsi' => 'Halaman <a class="underline" target="_blank" rel="noopener" href="' . $e(base_url($kunci)) . '">' . $e(base_url($kunci)) . '</a>. Kosongkan isian untuk memakai bawaan.',
    'jh_aksi' => '<a href="' . base_url('Admin_Seo') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali</span></a>',
]);
?>
<style>
    /* Warna meniru Google dan WhatsApp, di luar palet admin; kelas Tailwind-nya tidak ada di tailwind-admin.css. */
    .seo-hitung { float: right; font-variant-numeric: tabular-nums }
    .seo-google { background: #fff; font-family: Arial, Helvetica, sans-serif }
    .seo-google-judul { color: #1a0dab; line-height: 1.3 }
    .seo-wa { background: #e7fbe4 }
    .seo-wa-gambar { aspect-ratio: 1.91 / 1; border-radius: 6px 6px 0 0 }
    .seo-wa-isi { background: #f3f5f6; border-radius: 0 0 6px 6px }
    .dark .seo-google { background: #202124 }
    .dark .seo-google-judul { color: #8ab4f8 }
    .dark .seo-wa { background: #1f3a2c }
    .dark .seo-wa-isi { background: #1b2b31 }
</style>
<div class="grid grid-kartu items-start lg:grid-cols-5" data-seo-ubah>
    <form action="<?= base_url('Admin_Seo/simpan') ?>" method="post" enctype="multipart/form-data" class="tumpuk-bagian lg:col-span-3">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="kunci" value="<?= $e($kunci_form) ?>">
        <section class="kartu-admin isi-kartu space-y-4">
            <h2 class="<?= $judul_kartu ?>">Teks di hasil pencarian</h2>
            <label class="<?= $label ?>">Judul <span class="seo-hitung" data-hitung="judul"></span>
                <input id="seo-judul" name="judul" maxlength="<?= (int) $maks_judul ?>" value="<?= $e($t['judul'] ?? '') ?>" placeholder="<?= $e($b['judul']) ?>" class="<?= $isian ?>">
                <span class="<?= $petunjuk ?>">Maksimal <?= (int) $maks_judul ?> karakter. Nama situs ditambahkan otomatis di belakangnya. Sebut kata yang dicari orang, misalnya "syarat", "cara daftar", atau nama program.</span>
            </label>
            <label class="<?= $label ?>">Deskripsi <span class="seo-hitung" data-hitung="deskripsi"></span>
                <textarea id="seo-deskripsi" name="deskripsi" rows="3" maxlength="<?= (int) $maks_deskripsi ?>" placeholder="<?= $e($b['deskripsi']) ?>" class="<?= $isian ?>"><?= $e($t['deskripsi'] ?? '') ?></textarea>
                <span class="<?= $petunjuk ?>">120 sampai <?= (int) $maks_deskripsi ?> karakter terbaca utuh di Google. Ringkas isi halaman dan ajak bertindak.</span>
            </label>
            <label class="<?= $label ?>">Tampil di mesin pencari
                <select id="seo-indeks" name="indeks" class="<?= $isian ?>">
                    <option value="bawaan"<?= $indeks === 'bawaan' ? ' selected' : '' ?>>Ikut bawaan (<?= $b['noindex'] ? 'disembunyikan' : 'diindeks' ?>)</option>
                    <option value="ya"<?= $indeks === 'ya' ? ' selected' : '' ?>>Diindeks</option>
                    <option value="tidak"<?= $indeks === 'tidak' ? ' selected' : '' ?>>Disembunyikan dari mesin pencari</option>
                </select>
            </label>
        </section>
        <section class="kartu-admin isi-kartu space-y-3">
            <h2 class="<?= $judul_kartu ?>">Gambar pratinjau tautan</h2>
            <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'gambar', 'ib_accept' => 'image/jpeg,image/png', 'ib_required' => FALSE, 'ib_attr' => 'data-seo-gambar-input']); ?>
            <span class="<?= $petunjuk ?>">JPG atau PNG, maksimal 3&nbsp;MB, minimal 600x315. Gambar dipotong otomatis ke 1200x630 (rasio pratinjau WhatsApp dan Facebook) dan data lokasinya dibersihkan.</span>
            <?php if ( ! empty($t['gambar'])): ?>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="hapus_gambar" value="1" data-seo-hapus-gambar> Hapus gambar unggahan, kembali ke gambar bawaan
            </label>
            <?php endif; ?>
        </section>
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
            <a href="<?= base_url('Admin_Seo') ?>" class="tombol-kedua"><span>Batal</span></a>
        </div>
    </form>

    <aside class="tumpuk-bagian lg:col-span-2">
        <section class="kartu-admin isi-kartu">
            <h2 class="<?= $judul_kartu ?>">Pratinjau Google</h2>
            <div class="seo-google rounded-lg p-3">
                <div class="text-xs text-gray-600 dark:text-gray-400"><?= $e(preg_replace('#^https?://#', '', base_url($kunci))) ?></div>
                <div class="seo-google-judul mt-1 text-lg" data-pratinjau="judul"></div>
                <div class="mt-0.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400" data-pratinjau="deskripsi"></div>
            </div>
        </section>
        <section class="kartu-admin isi-kartu">
            <h2 class="<?= $judul_kartu ?>">Pratinjau WhatsApp</h2>
            <div class="seo-wa overflow-hidden rounded-lg p-1">
                <img src="<?= base_url(! empty($t['gambar']) ? $t['gambar'] : $b['gambar']) ?>" alt="" data-pratinjau="gambar" data-bawaan="<?= $e(base_url($b['gambar'])) ?>" class="seo-wa-gambar w-full object-cover">
                <div class="seo-wa-isi px-3 py-2">
                    <div class="text-sm font-bold text-gray-900 dark:text-white" data-pratinjau="judul-og"></div>
                    <div class="text-xs text-gray-600 dark:text-gray-400" data-pratinjau="deskripsi-og"></div>
                </div>
            </div>
        </section>
        <?php if ($t): ?>
        <form action="<?= base_url('Admin_Seo/kembalikan') ?>" method="post" class="kartu-admin isi-kartu">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="kunci" value="<?= $e($kunci_form) ?>">
            <p class="mb-3 text-xs text-gray-500 dark:text-brand-muted">Diubah <?= $e(tgl_id($t['updated_at'], TRUE, TRUE)) ?>. Kembalikan untuk membuang semua isian admin di halaman ini.</p>
            <button type="submit" class="tombol-kedua tombol-aksi-bahaya" data-seo-kembalikan><i class="ph ph-arrow-counter-clockwise"></i><span>Kembalikan ke bawaan</span></button>
        </form>
        <?php endif; ?>
    </aside>
</div>
<script>
// Pratinjau mengikuti ketikan; isian kosong memakai teks bawaan (placeholder), sama seperti di situs.
(function () {
    var situs = <?= json_encode(seo_cfg()['nama_situs'], JSON_HEX_TAG) ?>, beranda = <?= $kunci === '' ? 'true' : 'false' ?>;
    var j = document.getElementById('seo-judul'), d = document.getElementById('seo-deskripsi');
    var gIn = document.querySelector('[data-seo-gambar-input]'), gImg = document.querySelector('[data-pratinjau="gambar"]');
    var hapus = document.querySelector('[data-seo-hapus-gambar]'), asal = gImg.src, url = null;
    function nilai(el) { return el.value.trim() || el.placeholder; }
    function potong(s, n) { return s.length > n ? s.slice(0, n - 1) + '…' : s; }
    function segarkan() {
        var jj = nilai(j), dd = nilai(d);
        document.querySelector('[data-pratinjau="judul"]').textContent = potong(beranda ? situs + ': ' + jj : jj + ' | ' + situs, 62);
        document.querySelector('[data-pratinjau="deskripsi"]').textContent = potong(dd, 160);
        document.querySelector('[data-pratinjau="judul-og"]').textContent = jj;
        document.querySelector('[data-pratinjau="deskripsi-og"]').textContent = potong(dd, 100);
        document.querySelector('[data-hitung="judul"]').textContent = j.value.trim().length + '/' + j.maxLength;
        document.querySelector('[data-hitung="deskripsi"]').textContent = d.value.trim().length + '/' + d.maxLength;
    }
    j.addEventListener('input', segarkan); d.addEventListener('input', segarkan); segarkan();
    if (gIn) gIn.addEventListener('change', function () {
        if (url) { URL.revokeObjectURL(url); url = null; }
        gImg.src = gIn.files[0] ? (url = URL.createObjectURL(gIn.files[0])) : asal;
    });
    if (hapus) hapus.addEventListener('change', function () { gImg.src = hapus.checked ? gImg.dataset.bawaan : asal; });
})();
</script>
