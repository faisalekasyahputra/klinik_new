<?php
/* Ubah satu program Katalog (dulu modal di daftar). Simpan lewat Admin_Katalog_Program::ubah(),
   yang memvalidasi, memindai, dan membersihkan metadata foto. Thumbnail memakai
   Program_model::gambar_tampil(), aturan yang sama dengan korsel beranda, jadi yang terlihat
   di sini adalah yang terlihat warga. */
$v     = fn($k) => html_escape((string) ($p[$k] ?? ''));
$label = 'block text-xs text-gray-500 dark:text-brand-muted';
$isian = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$judul = 'mb-3 text-sm font-black text-gray-900 dark:text-white';
$petunjuk = 'mt-1 block text-[11px] text-gray-500 dark:text-brand-muted';
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul'     => $p['nama_program'],
    'jh_deskripsi' => 'Kode <code>' . html_escape($p['kode_program']) . '</code> (tidak bisa diubah)'
        . ($p['nama_kategori'] ? ' · ' . html_escape($p['nama_kategori']) : '') . '.',
    'jh_aksi'      => '<a href="' . base_url('Admin_Katalog_Program') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali</span></a>',
]); ?>
<?php /* `enctype` WAJIB: tanpa itu `$_FILES` kosong dan foto gagal tersimpan diam-diam. */ ?>
<form action="<?= base_url('Admin_Katalog_Program/ubah') ?>" method="post" enctype="multipart/form-data" class="grid grid-kartu items-start lg:grid-cols-3">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

    <div class="tumpuk-bagian lg:col-span-2">
        <section class="kartu-admin isi-kartu space-y-3">
            <h2 class="<?= $judul ?>">Data program</h2>
            <label class="<?= $label ?>">Nama program
                <input name="nama_program" required maxlength="255" value="<?= $v('nama_program') ?>" class="<?= $isian ?>">
                <span class="<?= $petunjuk ?>">Satu nama untuk semua layar: korsel beranda, kartu rekomendasi, akun warga, dan antrean admin.</span>
            </label>
            <label class="<?= $label ?>">Deskripsi singkat
                <textarea name="deskripsi_singkat" rows="3" class="<?= $isian ?>"><?= $v('deskripsi_singkat') ?></textarea>
            </label>
            <label class="flex items-start gap-2 rounded-lg bg-gray-50 p-3 text-sm dark:bg-black/20">
                <input type="checkbox" name="is_active" value="1" class="mt-0.5"<?= (int) $p['aktif'] === 1 ? ' checked' : '' ?>>
                <span>
                    <span class="font-bold text-gray-800 dark:text-white">Aktif, bisa diajukan warga</span>
                    <span class="<?= $petunjuk ?>">Dimatikan berarti pengajuan BARU ditolak. Pengajuan yang sudah masuk antrean <b>tidak dibatalkan</b>; saat ini <?= (int) $p['dipakai'] ?> pengajuan memakai program ini.</span>
                </span>
            </label>
        </section>

        <?php /* Warna kartu sengaja tidak diatur di sini: palet pastelnya disetel dan kontrasnya diukur. */ ?>
        <section class="kartu-admin isi-kartu space-y-3">
            <h2 class="<?= $judul ?>">Tampilan di beranda</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="<?= $label ?>">Label
                    <input name="badge" maxlength="60" placeholder="mis. MBR Fixed Income" value="<?= $v('lencana') ?>" class="<?= $isian ?>">
                </label>
                <label class="<?= $label ?>">Urutan
                    <input name="urutan" type="number" min="1" max="99" value="<?= (int) $p['urutan'] ?>" class="<?= $isian ?>">
                </label>
            </div>
            <label class="<?= $label ?>">Syarat utama
                <textarea name="syarat_utama" rows="2" maxlength="300" placeholder="Satu kalimat syarat yang tampil di slide" class="<?= $isian ?>"><?= $v('syarat_utama') ?></textarea>
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="tampil_korsel" value="1"<?= (int) $p['tampil_korsel'] === 1 ? ' checked' : '' ?>> Tampilkan di korsel beranda
            </label>
        </section>

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
            <a href="<?= base_url('Admin_Katalog_Program') ?>" class="tombol-kedua"><span>Batal</span></a>
        </div>
    </div>

    <section class="kartu-admin isi-kartu" data-kartu="foto">
        <h2 class="<?= $judul ?>">Foto program</h2>
        <img src="<?= base_url($gambar_tampil) ?>" alt="Foto <?= $v('nama_program') ?>" data-foto-program
             class="aspect-video w-full rounded-lg border border-gray-200 object-cover dark:border-white/10">
        <p class="<?= $petunjuk ?>" data-foto-keterangan><?= $gambar_unggah ? 'Foto unggahan admin, tampil di beranda.' : 'Foto bawaan, tampil di beranda sampai diganti.' ?></p>
        <div class="mt-3">
            <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'gambar', 'ib_accept' => 'image/jpeg,image/png', 'ib_required' => FALSE, 'ib_attr' => 'data-foto-input']); ?>
        </div>
        <span class="<?= $petunjuk ?>">JPG atau PNG, maksimal 3&nbsp;MB. Kosongkan bila fotonya tidak diganti. Data lokasi pada foto dibersihkan otomatis.</span>
    </section>
</form>
<script>
// Pratinjau foto baru sebelum disimpan; batal memilih mengembalikan foto yang tampil sekarang.
(function () {
    var input = document.querySelector('[data-foto-input]'), img = document.querySelector('[data-foto-program]'),
        ket = document.querySelector('[data-foto-keterangan]'), asal = img.src, asalKet = ket.textContent, url = null;
    input.addEventListener('change', function () {
        if (url) { URL.revokeObjectURL(url); url = null; }
        var f = input.files[0];
        if (!f) { img.src = asal; ket.textContent = asalKet; return; }
        img.src = url = URL.createObjectURL(f);
        ket.textContent = 'Pratinjau, tersimpan sesudah menekan Simpan.';
    });
})();
</script>
