<?php
/* Profil Perusahaan pengembang (2 Okt 2026). Menyunting baris Direktori SRP2 milik akun
   ini (Pengaturan::simpan_perusahaan). Label medan dari srp2_label_medan(), sama dengan
   formulir admin dan formulir data perusahaan di Profil Saya. Kelas mengikuti profil.php. */
$kotak  = 'rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card';
$judul  = 'flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white';
$label  = 'mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted';
$isian  = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800'
    . ' transition-colors placeholder-gray-400 focus:border-brand-primary focus:outline-none'
    . ' focus:ring-1 focus:ring-brand-primary dark:border-white/10 dark:bg-black/20 dark:text-white'
    . ' dark:placeholder-brand-muted/60';
$petunjuk = 'mt-1 text-xs text-gray-500 dark:text-brand-muted';
$L = srp2_label_medan();
$v = fn($k) => html_escape((string) ($row->$k ?? ''));
$label_status = ['belum_mendaftar' => 'Belum mendaftar', 'mendaftar' => 'Mendaftar', 'masih_proses' => 'Masih proses', 'bersertifikat' => 'Bersertifikat'];
?>
<div class="relative z-10 space-y-3">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-lg text-brand-primary"><i class="ph ph-buildings"></i></div>
        <div>
            <h1 class="text-xl font-black tracking-tight text-gray-900 dark:text-white md:text-2xl">Profil Perusahaan</h1>
            <p class="text-xs text-gray-500 dark:text-brand-muted">Data perusahaan Anda di direktori pengembang bersertifikat.</p>
        </div>
    </div>

<?php if ( ! $row): ?>
    <div class="<?= $kotak ?>" data-perusahaan-belum-tertaut>
        <h2 class="<?= $judul ?>">Belum tertaut ke direktori</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-brand-muted">Akun Anda belum memegang entri di Direktori SRP2. Entri tertaut otomatis saat pengajuan SRP2 Anda diterima, atau saat Dinas Perakim menautkan akun ini ke entri perusahaan Anda. Sampai saat itu, data perusahaan diisi lewat Profil Saya.</p>
        <a href="<?= base_url('akun/profil') ?>" class="tombol-kedua mt-4"><i class="ph ph-arrow-right"></i><span>Ke Profil Saya</span></a>
    </div>
<?php else: ?>
    <?php $berlaku = srp2_sertifikat_berlaku($row); $akhir = (string) ($row->sertifikat_berakhir ?? ''); ?>
    <div class="<?= $kotak ?>" data-kartu="identitas">
        <div class="flex flex-wrap items-center gap-4">
            <?= srp2_logo($row, 80) ?>
            <div class="min-w-0">
                <h2 class="text-lg font-black text-gray-900 dark:text-white"><?= html_escape($row->nama_perusahaan) ?></h2>
                <p class="<?= $petunjuk ?>">Nama, NPWP, wilayah, status sertifikasi, masa berlaku, dan penayangan dikelola Dinas Perakim. Hubungi dinas bila ada yang keliru.</p>
            </div>
        </div>
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="<?= $label ?>"><?= $L['npwp'] ?></dt><dd class="text-gray-800 dark:text-white"><?= ! empty($row->npwp_lookup_hash) ? 'Tersimpan (hanya dinas)' : 'Belum tercatat' ?></dd></div>
            <div><dt class="<?= $label ?>"><?= $L['kabupaten_id'] ?></dt><dd class="text-gray-800 dark:text-white"><?= html_escape($wilayah ?: 'Belum tercatat') ?></dd></div>
            <div><dt class="<?= $label ?>">Sertifikasi</dt><dd class="text-gray-800 dark:text-white"><?= html_escape($label_status[$row->status_sertifikasi] ?? $row->status_sertifikasi) ?>, <span class="font-bold <?= $berlaku ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= $berlaku ? 'Berlaku' : 'Tidak berlaku' ?></span><?= $akhir !== '' ? ' s.d. ' . html_escape(tgl_id($akhir, TRUE)) : '' ?></dd></div>
            <div><dt class="<?= $label ?>">Direktori publik</dt><dd class="text-gray-800 dark:text-white"><?= $row->status_aktif ? 'Ditayangkan' : 'Tidak ditayangkan' ?></dd></div>
        </dl>
        <?php if ($row->status_aktif): ?>
        <a href="<?= base_url('Pengembang/profil/' . (int) $row->id) ?>" target="_blank" rel="noopener" class="tombol-kedua mt-4"><i class="ph ph-eye"></i><span>Lihat profil publik</span></a>
        <?php endif; ?>
    </div>

    <form action="<?= base_url('akun/perusahaan/simpan') ?>" method="post" enctype="multipart/form-data" class="space-y-3" data-form-perusahaan>
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <div class="<?= $kotak ?>">
            <h2 class="<?= $judul ?>">Foto dan profil</h2>
            <div class="mt-3 space-y-3">
                <div>
                    <span class="<?= $label ?>"><?= $L['foto_profil'] ?></span>
                    <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'foto_profil', 'ib_accept' => '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp', 'ib_required' => FALSE, 'ib_attr' => '']); ?>
                    <p class="<?= $petunjuk ?>">JPG, PNG, atau WEBP, maksimal 2 MB. Tanpa foto, direktori menampilkan logo inisial.</p>
                </div>
                <div>
                    <label class="<?= $label ?>" for="pp-alamat"><?= $L['alamat_kantor'] ?></label>
                    <textarea id="pp-alamat" name="alamat_kantor" rows="2" required maxlength="500" class="<?= $isian ?>"><?= $v('alamat_kantor') ?></textarea>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="<?= $label ?>" for="pp-asosiasi"><?= $L['asosiasi'] ?></label>
                        <select id="pp-asosiasi" name="asosiasi" class="<?= $isian ?>">
                            <option value="">- belum tercatat -</option>
                            <?php foreach (srp2_daftar_asosiasi() as $ka => $va): ?>
                            <option value="<?= html_escape($ka) ?>" <?= $ka === trim((string) ($row->asosiasi ?? '')) ? 'selected' : '' ?>><?= html_escape($va) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label ?>" for="pp-anggota"><?= $L['no_keanggotaan'] ?></label>
                        <input id="pp-anggota" name="no_keanggotaan" maxlength="50" value="<?= $v('no_keanggotaan') ?>" class="<?= $isian ?>">
                    </div>
                    <div>
                        <label class="<?= $label ?>" for="pp-nib"><?= $L['nib'] ?></label>
                        <input id="pp-nib" name="nib" inputmode="numeric" maxlength="13" placeholder="13 digit" value="<?= $v('nib') ?>" class="<?= $isian ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="<?= $kotak ?>">
            <h2 class="<?= $judul ?>">Kontak publik</h2>
            <p class="<?= $petunjuk ?>">Ditampilkan di direktori dan halaman profil publik perusahaan.</p>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="<?= $label ?>" for="pp-wa"><?= $L['no_whatsapp'] ?></label>
                    <input id="pp-wa" name="no_whatsapp" type="tel" maxlength="20" placeholder="08xxxxxxxxxx" value="<?= $v('no_whatsapp') ?>" class="<?= $isian ?>">
                </div>
                <div>
                    <label class="<?= $label ?>" for="pp-email"><?= $L['email_kontak'] ?></label>
                    <input id="pp-email" name="email_kontak" type="email" maxlength="100" value="<?= $v('email_kontak') ?>" class="<?= $isian ?>">
                </div>
                <div>
                    <label class="<?= $label ?>" for="pp-web"><?= $L['website'] ?></label>
                    <input id="pp-web" name="website" type="url" placeholder="https://..." value="<?= $v('website') ?>" class="<?= $isian ?>">
                </div>
                <div>
                    <label class="<?= $label ?>" for="pp-ig"><?= $L['instagram'] ?></label>
                    <input id="pp-ig" name="instagram" type="url" placeholder="https://instagram.com/..." value="<?= $v('instagram') ?>" class="<?= $isian ?>">
                </div>
                <div>
                    <label class="<?= $label ?>" for="pp-sosmed"><?= $L['sosmed_lainnya'] ?></label>
                    <input id="pp-sosmed" name="sosmed_lainnya" type="url" placeholder="https://..." value="<?= $v('sosmed_lainnya') ?>" class="<?= $isian ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
    </form>
<?php endif; ?>
</div>
