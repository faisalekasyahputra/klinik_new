<?php
/* Detail/ubah satu entri Direktori SRP2, juga dipakai "Tambah pengembang" ($row NULL).
   Label medan dari srp2_label_medan(), sama dengan Profil Perusahaan pengembang dan
   Profil Saya, supaya satu medan tidak berganti nama antar layar. */
$this->load->helper('srp2');
$baru  = empty($row);
$r     = $row ?: (object) ['id' => 0, 'nama_perusahaan' => '', 'status_aktif' => 1, 'status_sertifikasi' => 'bersertifikat', 'user_id' => NULL];
$v     = fn($k) => html_escape((string) ($r->$k ?? ''));
$L     = srp2_label_medan();
$csrf  = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$label = 'block text-xs text-gray-500 dark:text-brand-muted';
$isian = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$judul = 'mb-3 text-sm font-black text-gray-900 dark:text-white';
$label_status = ['belum_mendaftar' => 'Belum mendaftar', 'mendaftar' => 'Mendaftar', 'masih_proses' => 'Masih proses', 'bersertifikat' => 'Bersertifikat'];
$sandi_awal = $this->session->flashdata('sandi_awal');
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul'     => $baru ? 'Tambah Pengembang' : $r->nama_perusahaan,
    'jh_deskripsi' => $baru ? 'Entri manual untuk data historis; pengajuan SRP2 yang diterima masuk otomatis.' : 'Ubah profil, kontak, sertifikasi, dan akun pengembang entri direktori ini.',
    'jh_aksi'      => '<a href="' . base_url('Admin_Srp2') . '" class="tombol-kedua"><i class="ph ph-arrow-left"></i><span>Kembali</span></a>',
]); ?>
<div class="tumpuk-bagian">
<?php if ($sandi_awal): ?>
<section class="kartu-admin isi-kartu border border-amber-200 bg-amber-50" data-sandi-awal>
    <h2 class="<?= $judul ?>">Sandi awal (ditampilkan sekali)</h2>
    <p class="text-sm text-gray-600 dark:text-gray-300">Salin dan serahkan lewat jalur pribadi. Sandi ini tidak disimpan dalam bentuk yang bisa dibaca dan tidak akan tampil lagi; pemiliknya wajib menggantinya saat pertama masuk.</p>
    <p class="mt-2 font-mono text-lg font-bold text-gray-900 dark:text-white select-all"><?= html_escape($sandi_awal) ?></p>
</section>
<?php endif; ?>

<div class="grid grid-kartu lg:grid-cols-3">
<form id="form-direktori" action="<?= base_url('Admin_Srp2/save') ?>" method="post" enctype="multipart/form-data" class="tumpuk-bagian lg:col-span-2">
    <?= $csrf ?>
    <input type="hidden" name="id" value="<?= (int) $r->id ?>">

    <section class="kartu-admin isi-kartu" data-kartu="profil">
        <h2 class="<?= $judul ?>">Profil</h2>
        <div class="mb-4 flex flex-wrap items-center gap-4">
            <?= srp2_logo($r, 80) ?>
            <div class="min-w-0">
                <span class="<?= $label ?>"><?= $L['foto_profil'] ?> (JPG, PNG, atau WEBP, maksimal 2 MB)</span>
                <div class="mt-1"><?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'foto_profil', 'ib_accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', 'ib_required' => FALSE, 'ib_attr' => '']); ?></div>
                <p class="mt-1 text-xs text-gray-400 dark:text-brand-muted">Tanpa foto, direktori menampilkan logo inisial.</p>
            </div>
        </div>
        <div class="mb-3 space-y-3">
            <label class="<?= $label ?>"><?= $L['nama_perusahaan'] ?>
                <input name="nama_perusahaan" required maxlength="180" value="<?= $v('nama_perusahaan') ?>" class="<?= $isian ?> uppercase">
            </label>
            <label class="<?= $label ?>"><?= $L['alamat_kantor'] ?>
                <textarea name="alamat_kantor" rows="2" maxlength="500" class="<?= $isian ?>"><?= $v('alamat_kantor') ?></textarea>
            </label>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="<?= $label ?>"><?= $L['kabupaten_id'] ?>
                <select name="kabupaten_id" class="<?= $isian ?>">
                    <option value="0">- belum tercatat -</option>
                    <?php foreach ($kabupaten as $kb): ?>
                    <option value="<?= (int) $kb->id ?>" <?= (int) $kb->id === (int) ($r->kabupaten_id ?? 0) ? 'selected' : '' ?>><?= html_escape($kb->nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="<?= $label ?>"><?= $L['asosiasi'] ?>
                <select name="asosiasi" class="<?= $isian ?>">
                    <option value="">- belum tercatat -</option>
                    <?php foreach (srp2_daftar_asosiasi() as $ka => $va): ?>
                    <option value="<?= html_escape($ka) ?>" <?= $ka === trim((string) ($r->asosiasi ?? '')) ? 'selected' : '' ?>><?= html_escape($va) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="<?= $label ?>"><?= $L['no_keanggotaan'] ?>
                <input name="no_keanggotaan" maxlength="50" value="<?= $v('no_keanggotaan') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['nib'] ?>
                <input name="nib" inputmode="numeric" maxlength="13" placeholder="13 digit" value="<?= $v('nib') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['npwp'] ?> (hanya admin)
                <input name="npwp" inputmode="numeric" maxlength="25" placeholder="15/16 digit" value="<?= html_escape((string) ($r->npwp_plain ?? '')) ?>" class="<?= $isian ?>">
                <?php if ( ! empty($r->npwp_rusak)): ?><span class="mt-1 block text-xs font-bold text-red-500">Tersimpan tapi gagal dibuka - jangan ditimpa sebelum diperiksa</span><?php endif; ?>
            </label>
        </div>
    </section>

    <section class="kartu-admin isi-kartu" data-kartu="kontak">
        <h2 class="<?= $judul ?>">Kontak</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="<?= $label ?>"><?= $L['no_whatsapp'] ?>
                <input name="no_whatsapp" type="tel" maxlength="20" placeholder="08xxxxxxxxxx" value="<?= $v('no_whatsapp') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['email_kontak'] ?>
                <input name="email_kontak" type="email" maxlength="100" value="<?= $v('email_kontak') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['website'] ?>
                <input name="website" type="url" placeholder="https://..." value="<?= $v('website') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['instagram'] ?>
                <input name="instagram" type="url" placeholder="https://instagram.com/..." value="<?= $v('instagram') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>"><?= $L['sosmed_lainnya'] ?>
                <input name="sosmed_lainnya" type="url" placeholder="https://..." value="<?= $v('sosmed_lainnya') ?>" class="<?= $isian ?>">
            </label>
        </div>
    </section>

    <section class="kartu-admin isi-kartu" data-kartu="sertifikasi">
        <h2 class="<?= $judul ?>">Sertifikasi</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="<?= $label ?>">Status sertifikasi
                <select name="status_sertifikasi" class="<?= $isian ?>">
                    <?php foreach ($label_status as $k => $t): ?>
                    <option value="<?= $k ?>" <?= $k === ($r->status_sertifikasi ?? 'bersertifikat') ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="<?= $label ?>">Masa berlaku saat ini
                <span class="mt-2 block"><?php $berlaku = srp2_sertifikat_berlaku($r); $this->load->view('admin/components/status_badge', ['label' => $berlaku ? 'Berlaku' : 'Tidak berlaku', 'kelas' => $berlaku ? 'ok' : 'reject']); ?></span>
            </div>
            <label class="<?= $label ?>">Terbit sertifikat
                <input name="sertifikat_terbit" type="date" value="<?= $v('sertifikat_terbit') ?>" class="<?= $isian ?>">
            </label>
            <label class="<?= $label ?>">Berakhir
                <input name="sertifikat_berakhir" type="date" value="<?= $v('sertifikat_berakhir') ?>" class="<?= $isian ?>">
            </label>
        </div>
        <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-brand-muted">
            <input type="checkbox" name="status_aktif" value="1" <?= ! empty($r->status_aktif) ? 'checked' : '' ?>> Tampilkan di direktori publik
        </label>
    </section>

    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
        <a href="<?= base_url('Admin_Srp2') ?>" class="tombol-kedua"><i class="ph ph-arrow-left"></i><span>Kembali</span></a>
    </div>
</form>

<?php if ( ! $baru): ?>
<div class="tumpuk-bagian">
    <section class="kartu-admin isi-kartu" data-kartu="akun">
        <h2 class="<?= $judul ?>">Akun pengembang</h2>
        <?php if ( ! empty($akun)): ?>
            <p class="text-sm text-gray-600 dark:text-gray-300">Tertaut ke <strong class="text-gray-900 dark:text-white" data-akun-email><?= html_escape($akun->email) ?></strong>. Pemilik akun bisa mengubah foto, alamat, dan kontak lewat Profil Perusahaan.</p>
            <dl class="mt-3 space-y-1 text-xs text-gray-500 dark:text-brand-muted">
                <div><dt class="inline">Masuk terakhir:</dt> <dd class="inline"><?= ! empty($akun->sesi_aktif_at) ? html_escape(tgl_id($akun->sesi_aktif_at)) : 'tidak ada sesi aktif' ?></dd></div>
                <div><dt class="inline">Sandi:</dt> <dd class="inline"><?= ! empty($akun->sandi_kedaluwarsa_at) && strtotime($akun->sandi_kedaluwarsa_at) <= strtotime((string) $akun->sandi_diganti_at) ? 'sandi dari admin, belum diganti pemilik' : 'sudah diatur pemilik' ?></dd></div>
            </dl>
            <form action="<?= base_url('Admin_Srp2/reset_sandi_akun/' . (int) $r->id) ?>" method="post" class="mt-4 space-y-2">
                <?= $csrf ?>
                <label class="<?= $label ?>">Sandi baru (kosongkan untuk dibuatkan otomatis)
                    <input name="password" type="text" autocomplete="off" class="<?= $isian ?>">
                </label>
                <button type="submit" class="tombol-kedua"><i class="ph ph-key"></i><span>Reset sandi</span></button>
            </form>
            <form action="<?= base_url('Admin_Srp2/lepas_akun/' . (int) $r->id) ?>" method="post" class="mt-3" onsubmit="return confirm('Lepas tautan akun ini? Akunnya tidak dihapus, tetapi tidak bisa lagi mengubah entri ini.')">
                <?= $csrf ?>
                <button type="submit" class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-link-break"></i><span>Lepas tautan</span></button>
            </form>
        <?php else: ?>
            <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada akun. Buatkan akun supaya perusahaan bisa memperbarui foto dan kontaknya sendiri.</p>
            <form action="<?= base_url('Admin_Srp2/buat_akun/' . (int) $r->id) ?>" method="post" class="mt-3 space-y-3" data-form-buat-akun>
                <?= $csrf ?>
                <label class="<?= $label ?>">Email akun
                    <input name="email" type="email" required maxlength="100" value="<?= $v('email_kontak') ?>" class="<?= $isian ?>">
                </label>
                <label class="<?= $label ?>">Nama penanggung jawab (opsional)
                    <input name="nama_pj" maxlength="150" class="<?= $isian ?>">
                </label>
                <label class="<?= $label ?>"><?= $L['no_whatsapp'] ?> (opsional)
                    <input name="no_whatsapp" type="tel" maxlength="20" value="<?= $v('no_whatsapp') ?>" class="<?= $isian ?>">
                </label>
                <label class="<?= $label ?>">Sandi awal (kosongkan untuk dibuatkan otomatis)
                    <input name="password" type="text" autocomplete="off" class="<?= $isian ?>">
                </label>
                <button type="submit" class="tombol-utama"><i class="ph ph-user-plus"></i><span>Buatkan akun</span></button>
            </form>
        <?php endif; ?>
    </section>

    <section class="kartu-admin isi-kartu" data-kartu="hapus">
        <h2 class="<?= $judul ?>">Hapus entri</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">Menghapus entri dari direktori publik. Akun yang tertaut tidak ikut terhapus.</p>
        <form action="<?= base_url('Admin_Srp2/delete/' . (int) $r->id) ?>" method="post" class="mt-3" onsubmit="return confirm('Hapus pengembang ini dari direktori?')">
            <?= $csrf ?>
            <button type="submit" class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
        </form>
    </section>
</div>
<?php endif; ?>
</div>
</div>
