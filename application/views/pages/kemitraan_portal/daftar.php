<?php
// Kelas isian diangkat jadi variabel — string yang sama sebelumnya ditulis
// ulang di setiap medan, dan formulir ini baru saja bertambah lima medan.
$isian = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)]'
    . ' px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors'
    . ' focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15';
$isian_mati = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)]'
    . ' px-4 py-3 text-sm text-[color:var(--portal-text-muted)] shadow-sm cursor-not-allowed';
$label = 'mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]';
$berkas = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)]'
    . ' px-4 py-2.5 text-xs text-[color:var(--portal-text-muted)] shadow-sm file:mr-3 file:rounded-lg'
    . ' file:border-0 file:bg-[color:var(--portal-btn-bg)] file:px-3 file:py-1.5 file:text-xs'
    . ' file:font-bold file:text-[color:var(--portal-icon)]';
$petunjuk = 'mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]';
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-2xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Kemitraan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]"><?= $jenis === 'kkn' ? 'Daftar KKN Tematik' : 'Daftar Magang / Kerja Praktik' ?></h1>
        <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-[color:var(--portal-text-muted)]">Isi formulir di bawah, tim kami akan meninjau dan menghubungi Anda lewat akun terdaftar.</p>
    </div>

    <form id="kemitraan-daftar-form" class="mx-auto mt-8 max-w-2xl space-y-4" action="<?= base_url('KemitraanPortal/simpan') ?>" method="POST" enctype="multipart/form-data"
          x-data="{
              nim: '', tempat_lahir: '', tanggal_lahir: '', semester: '', jurusan: '',
              instansi_asal: '', no_hp: '', divisi_atau_tema: '', periode_mulai: '', periode_selesai: '',
              get isValid() {
                  return this.nim.trim() && this.tempat_lahir.trim() && this.tanggal_lahir
                      && this.semester && this.jurusan.trim()
                      && this.instansi_asal.trim() && this.no_hp.trim()
                      && this.divisi_atau_tema.trim() && this.periode_mulai && this.periode_selesai;
              }
          }">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">

        <?php
        // BACA-SAJA dan tanpa atribut name: nilainya tidak pernah dikirim, dan
        // `simpan()` mengambil user_id dari sesi. Ditampilkan supaya pendaftar
        // tahu akun mana yang menempel pada pendaftaran ini — sebelumnya itu
        // terjadi diam-diam.
        ?>
        <div class="rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)]/50 p-4">
            <p class="text-[11px] font-black uppercase tracking-wider text-[color:var(--portal-text-muted)]">Terkirim atas nama akun</p>
            <p class="mt-1 text-sm font-bold text-[color:var(--portal-text)]"><?= html_escape($nama_akun ?: '—') ?></p>
            <p class="text-xs text-[color:var(--portal-text-muted)]"><?= html_escape($email_akun ?: '—') ?></p>
            <p class="mt-2 text-[11px] text-[color:var(--portal-text-muted)]">Ingin mengubah nama? Perbarui lewat <a href="<?= base_url('akun/profil') ?>" class="font-bold underline">Profil Saya</a>.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kd-nim" class="<?= $label ?>">NIM</label>
                <input id="kd-nim" name="nim" x-model="nim" required maxlength="30" placeholder="Nomor induk mahasiswa" class="<?= $isian ?>">
            </div>
            <div>
                <label for="kd-jurusan" class="<?= $label ?>">Jurusan / Program Studi</label>
                <input id="kd-jurusan" name="jurusan" x-model="jurusan" required maxlength="150" placeholder="Contoh: Teknik Sipil" class="<?= $isian ?>">
            </div>
        </div>

        <?php
        // "TTL" di mockup satu baris, di DB dua kolom (tempat_lahir VARCHAR +
        // tanggal_lahir DATE). Tampilannya tetap satu baris berdampingan;
        // yang dipisah cuma penyimpanannya, supaya tanggalnya bisa diurutkan
        // dan dihitung tanpa membongkar teks bebas nanti.
        ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="kd-tempat" class="<?= $label ?>">Tempat Lahir</label>
                <input id="kd-tempat" name="tempat_lahir" x-model="tempat_lahir" required maxlength="100" placeholder="Kota kelahiran" class="<?= $isian ?>">
            </div>
            <div>
                <label for="kd-tanggal" class="<?= $label ?>">Tanggal Lahir</label>
                <input id="kd-tanggal" name="tanggal_lahir" x-model="tanggal_lahir" type="date" required class="<?= $isian ?>">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="kd-semester" class="<?= $label ?>">Semester</label>
                <input id="kd-semester" name="semester" x-model="semester" type="number" min="1" max="14" required placeholder="6" class="<?= $isian ?>">
            </div>
            <div class="sm:col-span-2">
                <label for="kd-instansi" class="<?= $label ?>">Universitas / Instansi Asal</label>
                <input id="kd-instansi" name="instansi_asal" x-model="instansi_asal" required maxlength="150" placeholder="Nama kampus/universitas" class="<?= $isian ?>">
            </div>
        </div>

        <div>
            <label for="kd-hp" class="<?= $label ?>">Nomor HP/WhatsApp</label>
            <input id="kd-hp" name="no_hp" x-model="no_hp" type="tel" required maxlength="15" placeholder="08xxxxxxxxxx" class="<?= $isian ?>">
        </div>

        <div>
            <label for="kd-divisi" class="<?= $label ?>"><?= $jenis === 'kkn' ? 'Tema Kegiatan' : 'Bidang yang Dituju' ?></label>
            <?php if ($jenis === 'magang'): ?>
                <!-- Divisi dipilih dari daftar, bukan diketik. Sebelumnya ini
                     teks bebas, jadi pendaftar bisa menulis divisi yang di
                     papan slot berwarna merah — dan tidak ada yang menahannya.
                     Penjagaan yang sebenarnya tetap di KemitraanPortal::simpan();
                     select ini hanya supaya orang tidak menebak-nebak. -->
                <select id="kd-divisi" name="divisi_atau_tema" x-model="divisi_atau_tema" required class="<?= $isian ?>">
                    <option value="">— Pilih bidang —</option>
                    <?php foreach ($divisi as $d): ?>
                        <option value="<?= html_escape($d->kode) ?>"><?= html_escape($d->nama) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="<?= $petunjuk ?>">
                    Pastikan seluruh bulan dalam periode Anda terbuka di
                    <a href="<?= base_url('KemitraanPortal/magang') ?>" class="font-bold underline">papan slot</a>.
                    Pendaftaran ditolak kalau ada bulan yang tertutup.
                </p>
            <?php else: ?>
                <input id="kd-divisi" name="divisi_atau_tema" x-model="divisi_atau_tema" required maxlength="150" placeholder="Contoh: Penataan Kawasan Kumuh" class="<?= $isian ?>">
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kd-mulai" class="<?= $label ?>">Periode Mulai</label>
                <input id="kd-mulai" name="periode_mulai" x-model="periode_mulai" type="date" required class="<?= $isian ?>">
            </div>
            <div>
                <label for="kd-selesai" class="<?= $label ?>">Periode Selesai</label>
                <input id="kd-selesai" name="periode_selesai" x-model="periode_selesai" type="date" required class="<?= $isian ?>">
            </div>
        </div>

        <?php
        // WAJIB untuk magang, opsional untuk KKN — keputusan user 2 Agt 2026.
        // `required` di sini cuma kenyamanan; yang menegakkannya
        // KemitraanPortal::simpan(), yang menolak sebelum barisnya lahir.
        $surat_wajib = $jenis === 'magang';
        ?>
        <div>
            <label for="kd-surat" class="<?= $label ?>">Surat Pengantar
                <span class="font-normal <?= $surat_wajib ? 'text-rose-500' : 'text-[color:var(--portal-text-muted)]' ?>"><?= $surat_wajib ? '(wajib)' : '(opsional)' ?></span></label>
            <input id="kd-surat" name="file_surat_pengantar" type="file" accept=".jpg,.jpeg,.png,.pdf"
                   <?= $surat_wajib ? 'required' : '' ?> class="<?= $berkas ?>">
            <p class="<?= $petunjuk ?>">Format JPG, PNG, atau PDF. Maksimal 5 MB.<?= $surat_wajib
                ? ' Tanpa surat pengantar, pendaftaran magang tidak bisa dikirim.' : '' ?></p>
        </div>

        <?php if ($jenis === 'magang'): ?>
        <?php
        // Proposal hanya untuk magang (keputusan user 30 Jul 2026). Tidak
        // dirender untuk KKN, DAN tidak diproses server untuk KKN — lihat
        // KemitraanPortal::simpan(); formulir yang tidak menampilkan sesuatu
        // bukan penjagaan, ia cuma tidak menawarkannya.
        //
        // Ditandai "opsional" mengikuti surat pengantar. Menjadikan dokumen
        // WAJIB adalah keputusan #11 (syarat bukti resmi) yang masih BLOCKED —
        // bukan hal yang boleh diputuskan sambil merapikan formulir.
        ?>
        <div>
            <label for="kd-proposal" class="<?= $label ?>">Proposal Magang <span class="font-normal text-[color:var(--portal-text-muted)]">(opsional)</span></label>
            <input id="kd-proposal" name="file_proposal" type="file" accept=".jpg,.jpeg,.png,.pdf" class="<?= $berkas ?>">
            <p class="<?= $petunjuk ?>">Rencana kegiatan selama magang. Format JPG, PNG, atau PDF. Maksimal 5 MB.</p>
        </div>
        <?php endif; ?>

        <button type="submit" :disabled="!isValid"
                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-black transition"
                :style="isValid
                    ? 'background-color: var(--portal-brand); color: var(--portal-bg); cursor: pointer;'
                    : 'background-color: var(--portal-border); color: var(--portal-text-muted); cursor: not-allowed;'"
                :class="isValid && 'hover:-translate-y-0.5 hover:brightness-95'">Kirim Pendaftaran <i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>
