<?php
/**
 * Sunting satu pendaftaran KKN/Magang oleh superadmin.
 *
 * Yang TIDAK bisa diubah di sini: pemilik akun (`user_id`), jenis, berkas, dan
 * status. Tiga yang pertama bukan koreksi data melainkan pemindahan identitas —
 * kalau salah orang yang mendaftar, yang benar adalah menolak lalu mendaftar
 * ulang. Status punya jalurnya sendiri di `proses()`, lengkap dengan catatan
 * peninjau dan jejak siapa-kapan.
 */
$isian = 'w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20'
    . ' px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none';
$label = 'mb-1.5 block text-xs font-bold text-gray-900 dark:text-white';
?>
<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'pendaftaran']); ?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-1">Ubah Pendaftaran</h3>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            <?= html_escape($row->nama_mahasiswa ?: '(akun tanpa nama)') ?>
            &middot; <?= html_escape($row->email_mahasiswa ?: '-') ?>
            &middot; <span class="font-bold uppercase"><?= html_escape($row->jenis) ?></span>
            &middot; status <span class="font-bold"><?= html_escape($row->status) ?></span>
        </p>
    </div>
    <a href="<?= base_url('Admin_Kemitraan') ?>"
       class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-xs font-bold text-gray-700 dark:text-gray-300">
        Kembali
    </a>
</div>

<form method="POST" action="<?= base_url('Admin_Kemitraan/simpan_ubah/' . (int) $row->id) ?>"
      class="rounded-3xl border border-gray-200 dark:border-white/5 bg-white dark:bg-brand-card p-6 space-y-5">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="u-nim" class="<?= $label ?>">NIM</label>
            <input id="u-nim" name="nim" value="<?= html_escape($row->nim) ?>" required maxlength="30" class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-jurusan" class="<?= $label ?>">Jurusan / Program Studi</label>
            <input id="u-jurusan" name="jurusan" value="<?= html_escape($row->jurusan) ?>" required maxlength="150" class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-tempat" class="<?= $label ?>">Tempat Lahir</label>
            <input id="u-tempat" name="tempat_lahir" value="<?= html_escape($row->tempat_lahir) ?>" required maxlength="100" class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-tanggal" class="<?= $label ?>">Tanggal Lahir</label>
            <input id="u-tanggal" name="tanggal_lahir" type="date" value="<?= html_escape($row->tanggal_lahir) ?>" required class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-semester" class="<?= $label ?>">Semester</label>
            <input id="u-semester" name="semester" type="number" min="1" max="14" value="<?= (int) $row->semester ?>" required class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-instansi" class="<?= $label ?>">Universitas / Instansi Asal</label>
            <input id="u-instansi" name="instansi_asal" value="<?= html_escape($row->instansi_asal) ?>" required maxlength="150" class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-hp" class="<?= $label ?>">Nomor HP/WhatsApp</label>
            <input id="u-hp" name="no_hp" type="tel" value="<?= html_escape($row->no_hp) ?>" required maxlength="15" class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-divisi" class="<?= $label ?>"><?= $row->jenis === 'kkn' ? 'Tema Kegiatan' : 'Bidang yang Dituju' ?></label>
            <?php if ($row->jenis === 'magang'): ?>
                <!-- Mengirim KODE bidang, bukan namanya: nama bisa berubah, kode
                     adalah kunci yang tersimpan dan dipakai routing tinjauan. -->
                <select id="u-divisi" name="divisi_atau_tema" required class="<?= $isian ?>">
                    <?php foreach ($bidang as $b): ?>
                        <option value="<?= html_escape($b->kode) ?>" <?= $b->kode === $row->bidang_kode ? 'selected' : '' ?>>
                            <?= html_escape($b->nama) ?><?= (int) $b->aktif ? '' : ' (tidak menerima)' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input id="u-divisi" name="divisi_atau_tema" value="<?= html_escape($row->divisi_atau_tema) ?>" required maxlength="150" class="<?= $isian ?>">
            <?php endif; ?>
        </div>
        <div>
            <label for="u-mulai" class="<?= $label ?>">Periode Mulai</label>
            <input id="u-mulai" name="periode_mulai" type="date" value="<?= html_escape($row->periode_mulai) ?>" required class="<?= $isian ?>">
        </div>
        <div>
            <label for="u-selesai" class="<?= $label ?>">Periode Selesai</label>
            <input id="u-selesai" name="periode_selesai" type="date" value="<?= html_escape($row->periode_selesai) ?>" required class="<?= $isian ?>">
        </div>
    </div>

    <?php if ($row->jenis === 'magang'): ?>
        <p class="text-xs text-gray-500 dark:text-brand-muted">
            Kuota TIDAK ditegakkan di layar ini — Anda boleh menempatkan mahasiswa pada bulan yang
            sudah penuh, dan papan slot akan menampilkan kelebihannya apa adanya.
        </p>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 dark:border-white/5 pt-5">
        <!-- Hapus sengaja diletakkan paling kiri dan berwarna netral, bukan
             tombol merah besar di sebelah Simpan: ini satu-satunya aksi di modul
             ini yang tidak bisa dibatalkan. "Ditolak" cukup untuk hampir semua
             kasus dan meninggalkan jejak yang bisa dibaca. -->
        <button type="submit" form="hapus-pendaftaran" class="mr-auto text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
            Hapus pendaftaran ini
        </button>
        <a href="<?= base_url('Admin_Kemitraan') ?>"
           class="rounded-xl border border-gray-200 dark:border-white/10 px-5 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300">Batal</a>
        <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white">Simpan Perubahan</button>
    </div>
</form>

<?php if ($row->status === 'Diterima'): ?>
    <!-- Surat balasan: DIUNGGAH, bukan dibuat sistem. Dokumen resmi yang dikarang
         perangkat lunak — lengkap dengan kop dan tanda tangan yang tidak pernah
         dibubuhkan siapa pun — adalah dokumen palsu, apa pun niatnya. -->
    <form method="POST" action="<?= base_url('Admin_Kemitraan/unggah_balasan/' . (int) $row->id) ?>"
          enctype="multipart/form-data"
          class="mt-6 rounded-3xl border border-gray-200 dark:border-white/5 bg-white dark:bg-brand-card p-6">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <h3 class="mb-1 text-sm font-black text-gray-900 dark:text-white">Surat Balasan</h3>
        <p class="mb-4 text-xs text-gray-500 dark:text-brand-muted">
            Unggah PDF surat balasan yang <strong>sudah ditandatangani</strong>. Mahasiswa mengunduhnya
            dari halaman pendaftarannya. Sistem tidak membuat surat ini.
        </p>

        <?php if ( ! empty($row->file_surat_balasan)): ?>
            <p class="mb-3 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                <i class="ph ph-check-circle"></i>
                Sudah ada —
                <a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . (int) $row->id . '/balasan') ?>" target="_blank" rel="noopener" class="underline">lihat berkasnya</a>.
                Mengunggah lagi akan menggantikannya.
            </p>
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-3">
            <input type="file" name="file_surat_balasan" accept=".pdf" required
                   class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 py-2.5 text-xs text-gray-600 dark:text-brand-muted">
            <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white">
                <?= empty($row->file_surat_balasan) ? 'Unggah' : 'Ganti Surat' ?>
            </button>
        </div>
    </form>
<?php elseif ($row->jenis === 'magang'): ?>
    <p class="mt-6 text-xs text-gray-500 dark:text-brand-muted">
        Surat balasan bisa diunggah setelah pendaftaran ini berstatus <strong>Diterima</strong>.
        Status sekarang: <?= html_escape($row->status) ?>.
    </p>
<?php endif; ?>

<form id="hapus-pendaftaran" method="POST" action="<?= base_url('Admin_Kemitraan/hapus/' . (int) $row->id) ?>" class="hidden"
      onsubmit="return confirm('Hapus pendaftaran ini beserta berkasnya? Tindakan ini tidak bisa dibatalkan.')">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
</form>
