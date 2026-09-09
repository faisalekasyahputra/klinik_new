<?php
/* Field & markup form ini SENGAJA sama persis dengan form "Tambah
   pengembang" lama di index.php (dipindah, bukan ditulis ulang) - target
   POST-nya (Admin_Srp2/save) juga tidak berubah, save() sudah menerima
   id=0/kosong sebagai INSERT sejak awal. Kalau field di sini berubah,
   cek juga apakah form BARIS di index.php (edit) perlu ikut berubah -
   keduanya independen tapi menulis ke tabel yang sama. */
$label_status = [
    'belum_mendaftar' => 'Belum mendaftar',
    'mendaftar'       => 'Mendaftar',
    'masih_proses'    => 'Masih proses',
    'bersertifikat'   => 'Bersertifikat',
];
// Daftar tertutup yang SAMA dengan formulir pengembang & baris edit di
// index.php - lihat srp2_daftar_asosiasi(). Dulu ketik bebas di sini.
$this->load->helper('srp2');
$daftar_asosiasi = srp2_daftar_asosiasi();
?>
<div class="mb-6">
    <a href="<?= base_url('Admin_Srp2') ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-brand-muted hover:text-gray-700 dark:hover:text-brand-light mb-3">
        <i class="ph ph-arrow-left"></i> Kembali ke Direktori SRP2
    </a>
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Tambah Pengembang</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Tambahkan pengembang bersertifikat secara manual - dipakai untuk data historis, karena pengajuan yang diterima lewat SRP2 masuk otomatis ke direktori.</p>
</div>

<div class="rounded-2xl bg-white dark:bg-brand-card border border-gray-200 dark:border-white/5 p-5 max-w-3xl">
    <form action="<?= base_url('Admin_Srp2/save') ?>" method="post" class="grid md:grid-cols-2 gap-4">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input name="nama_perusahaan" required maxlength="180" placeholder="Nama perusahaan" class="rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        <input name="website" type="url" placeholder="https://website.co.id" class="rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        <input name="instagram" type="url" placeholder="https://instagram.com/akun" class="rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        <input name="sosmed_lainnya" type="url" placeholder="Link media sosial lainnya" class="rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        <label class="text-xs text-gray-500 dark:text-brand-muted">Terbit sertifikat
            <input name="sertifikat_terbit" type="date" class="mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        </label>
        <label class="text-xs text-gray-500 dark:text-brand-muted">Berakhir
            <input name="sertifikat_berakhir" type="date" class="mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        </label>
        <label class="text-xs text-gray-500 dark:text-brand-muted">Status sertifikasi
            <select name="status_sertifikasi" class="mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm">
                <?php foreach ($label_status as $k => $v): ?>
                <option value="<?= $k ?>" <?= $k === 'bersertifikat' ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs text-gray-500 dark:text-brand-muted">Kabupaten/Kota
            <select name="kabupaten_id" class="mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm">
                <option value="0">- belum tercatat -</option>
                <?php foreach ($kabupaten as $kb): ?>
                <option value="<?= (int) $kb->id ?>"><?= htmlspecialchars($kb->nama, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs text-gray-500 dark:text-brand-muted">Asosiasi
            <select name="asosiasi" class="mt-1 w-full rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm">
                <option value="">- belum tercatat -</option>
                <?php foreach ($daftar_asosiasi as $ka => $va): ?>
                <option value="<?= $ka ?>"><?= $va ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input name="npwp" inputmode="numeric" maxlength="25" placeholder="NPWP (15/16 digit, hanya admin)" class="rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm" />
        <textarea name="alamat_kantor" placeholder="Alamat kantor" class="md:col-span-2 rounded-xl border border-gray-200 dark:border-white/10 bg-transparent px-4 py-3 text-sm"></textarea>
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-brand-muted"><input type="checkbox" name="status_aktif" value="1" checked /> Tampilkan di publik</label>
        <button class="md:col-span-2 rounded-xl bg-brand-primary px-4 py-3 text-sm font-bold text-brand-dark">Simpan</button>
    </form>
</div>
