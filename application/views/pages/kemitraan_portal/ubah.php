<?php
/**
 * Mahasiswa menyunting pendaftarannya sendiri - hanya selama masih Diajukan.
 *
 * Berkas TIDAK ada di sini. Mengganti unggahan berarti menghapus berkas lama
 * dari private_uploads/ sementara admin mungkin sudah membukanya sebagai dasar
 * penilaian, dan itu keputusan tersendiri yang belum pernah diminta. Yang bisa
 * dikoreksi di sini adalah isian teks - salah ketik NIM dan periode yang
 * bergeser, dua hal yang selama ini menumpuk di meja superadmin.
 *
 * Penjagaan slot tetap berlaku, dengan pendaftaran ini sendiri dikecualikan
 * dari hitungan kuota - lihat KemitraanPortal::periksa_slot($abaikan_id).
 */
$isian = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)]'
    . ' px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors'
    . ' focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15';
$isian_mati = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)]'
    . ' px-4 py-3 text-sm text-[color:var(--portal-text-muted)] shadow-sm cursor-not-allowed';
$label = 'mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]';
$petunjuk = 'mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]';
?>
<div class="mx-auto max-w-2xl p-2 sm:p-6">
    <div class="mb-6">
        <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
        <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-3xl">Ubah Pendaftaran <?= strtoupper(html_escape($row->jenis)) ?></h1>
        <p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">
            Perbaiki isian yang keliru selagi pendaftaran masih ditinjau. Berkas hanya bisa diganti lewat admin.
        </p>
    </div>

    <form method="POST" action="<?= base_url('KemitraanPortal/simpan_ubah/' . (int) $row->id) ?>"
          class="space-y-4 rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 shadow-sm">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div>
            <label class="<?= $label ?>">Terkirim atas nama akun</label>
            <input value="<?= html_escape($nama_akun . ' - ' . $email_akun) ?>" class="<?= $isian_mati ?>" disabled>
        </div>

        <?php
        // KKN mendaftarkan kampus, bukan satu mahasiswa - lihat alasan
        // lengkap di daftar.php (permintaan user 21 Agt 2026). Baris yang
        // sudah ada dari SEBELUM perubahan ini bisa saja masih menyimpan
        // NIM/TTL/semester lama (kolomnya tidak dihapus, cuma tidak
        // diminta lagi) - tersembunyi juga untuk baris lama, supaya tidak
        // ada yang menyunting data yang sudah tidak seharusnya diminta.
        $wajib_data_pribadi = $row->jenis === 'magang';

        // Label ganda untuk `jurusan` - lihat komentar lengkap di daftar.php.
        $judul_jurusan  = $row->jenis === 'kkn' ? 'Penanggung Jawab' : 'Jurusan / Program Studi';
        $judul_instansi = $row->jenis === 'kkn' ? 'Nama Universitas' : 'Universitas / Instansi Asal';
        $judul_hp       = $row->jenis === 'kkn' ? 'No HP' : 'Nomor HP/WhatsApp';
        ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <?php if ($wajib_data_pribadi): ?>
            <div>
                <label for="ku-nim" class="<?= $label ?>">NIM</label>
                <input id="ku-nim" name="nim" value="<?= html_escape($row->nim) ?>" required maxlength="30" class="<?= $isian ?>">
            </div>
            <?php endif; ?>
            <div>
                <label for="ku-jurusan" class="<?= $label ?>"><?= html_escape($judul_jurusan) ?></label>
                <input id="ku-jurusan" name="jurusan" value="<?= html_escape($row->jurusan) ?>" required maxlength="150" class="<?= $isian ?>">
            </div>
            <?php if ($wajib_data_pribadi): ?>
            <div>
                <label for="ku-tempat" class="<?= $label ?>">Tempat Lahir</label>
                <input id="ku-tempat" name="tempat_lahir" value="<?= html_escape($row->tempat_lahir) ?>" required maxlength="100" class="<?= $isian ?>">
            </div>
            <div>
                <label for="ku-tanggal" class="<?= $label ?>">Tanggal Lahir</label>
                <input id="ku-tanggal" name="tanggal_lahir" type="date" value="<?= html_escape($row->tanggal_lahir) ?>" required class="<?= $isian ?>">
            </div>
            <div>
                <label for="ku-semester" class="<?= $label ?>">Semester</label>
                <input id="ku-semester" name="semester" type="number" min="1" max="14" value="<?= (int) $row->semester ?>" required class="<?= $isian ?>">
            </div>
            <?php endif; ?>
            <div>
                <label for="ku-instansi" class="<?= $label ?>"><?= html_escape($judul_instansi) ?></label>
                <input id="ku-instansi" name="instansi_asal" value="<?= html_escape($row->instansi_asal) ?>" required maxlength="150" class="<?= $isian ?>">
            </div>
        </div>

        <div>
            <label for="ku-hp" class="<?= $label ?>"><?= html_escape($judul_hp) ?></label>
            <input id="ku-hp" name="no_hp" type="tel" value="<?= html_escape($row->no_hp) ?>" required maxlength="15" class="<?= $isian ?>">
        </div>

        <?php
        // Tema Kegiatan dan Periode DIHILANGKAN untuk KKN - lihat alasan
        // lengkap di daftar.php (permintaan user 21 Agt 2026). Baris KKN
        // lama yang masih menyimpan nilai di kolom-kolom itu juga tidak
        // disunting lewat sini lagi.
        ?>
        <?php if ($row->jenis === 'magang'): ?>
        <div>
            <label for="ku-divisi" class="<?= $label ?>">Bidang yang Dituju</label>
                <select id="ku-divisi" name="divisi_atau_tema" required class="<?= $isian ?>">
                    <?php foreach ($divisi as $d): ?>
                        <option value="<?= html_escape($d->kode) ?>" <?= $d->kode === $row->bidang_kode ? 'selected' : '' ?>><?= html_escape($d->nama) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="<?= $petunjuk ?>">
                    Pastikan seluruh bulan dalam periode Anda terbuka di
                    <a href="<?= base_url('KemitraanPortal/magang') ?>" class="font-bold underline">papan slot</a>.
                </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="ku-mulai" class="<?= $label ?>">Periode Mulai</label>
                <input id="ku-mulai" name="periode_mulai" type="date" value="<?= html_escape($row->periode_mulai) ?>" required class="<?= $isian ?>">
            </div>
            <div>
                <label for="ku-selesai" class="<?= $label ?>">Periode Selesai</label>
                <input id="ku-selesai" name="periode_selesai" type="date" value="<?= html_escape($row->periode_selesai) ?>" required class="<?= $isian ?>">
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-wrap justify-end gap-3 border-t border-[color:var(--portal-border)] pt-5">
            <a href="<?= base_url('KemitraanPortal/pendaftaran/' . (int) $row->id) ?>"
               class="rounded-xl border border-[color:var(--portal-border)] px-5 py-2.5 text-xs font-bold text-[color:var(--portal-text)]">Batal</a>
            <button type="submit" class="rounded-xl bg-[color:var(--portal-brand)] px-5 py-2.5 text-xs font-bold text-[#0a1a1f]">Simpan Perubahan</button>
        </div>
    </form>
</div>
