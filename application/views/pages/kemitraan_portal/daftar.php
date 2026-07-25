<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-2xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Kemitraan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]"><?= $jenis === 'kkn' ? 'Daftar KKN Tematik' : 'Daftar Magang / Kerja Praktik' ?></h1>
        <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-[color:var(--portal-text-muted)]">Isi formulir di bawah, tim kami akan meninjau dan menghubungi Anda lewat akun terdaftar.</p>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
    <div class="mx-auto mt-6 max-w-2xl rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-500" role="alert">
        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> <?= $this->session->flashdata('error') ?>
    </div>
    <?php endif; ?>

    <form id="kemitraan-daftar-form" class="mx-auto mt-8 max-w-2xl space-y-4" action="<?= base_url('KemitraanPortal/simpan') ?>" method="POST" enctype="multipart/form-data"
          x-data="{
              instansi_asal: '', no_hp: '', divisi_atau_tema: '', periode_mulai: '', periode_selesai: '',
              get isValid() {
                  return this.instansi_asal.trim() && this.no_hp.trim() && this.divisi_atau_tema.trim() && this.periode_mulai && this.periode_selesai;
              }
          }">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">

        <div>
            <label for="kd-instansi" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Instansi Asal (Kampus/Universitas)</label>
            <input id="kd-instansi" name="instansi_asal" x-model="instansi_asal" required maxlength="150" placeholder="Nama kampus/universitas" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
        </div>

        <div>
            <label for="kd-hp" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Nomor HP/WhatsApp</label>
            <input id="kd-hp" name="no_hp" x-model="no_hp" type="tel" required maxlength="15" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
        </div>

        <div>
            <label for="kd-divisi" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]"><?= $jenis === 'kkn' ? 'Tema Kegiatan' : 'Divisi yang Dituju' ?></label>
            <input id="kd-divisi" name="divisi_atau_tema" x-model="divisi_atau_tema" required maxlength="150" placeholder="<?= $jenis === 'kkn' ? 'Contoh: Penataan Kawasan Kumuh' : 'Contoh: Infrastruktur dan Teknologi Digital' ?>" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kd-mulai" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Periode Mulai</label>
                <input id="kd-mulai" name="periode_mulai" x-model="periode_mulai" type="date" required class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
            </div>
            <div>
                <label for="kd-selesai" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Periode Selesai</label>
                <input id="kd-selesai" name="periode_selesai" x-model="periode_selesai" type="date" required class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
            </div>
        </div>

        <div>
            <label for="kd-surat" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Surat Pengantar <span class="font-normal text-[color:var(--portal-text-muted)]">(opsional)</span></label>
            <input id="kd-surat" name="file_surat_pengantar" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-2.5 text-xs text-[color:var(--portal-text-muted)] shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[color:var(--portal-btn-bg)] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[color:var(--portal-icon)]">
            <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]">Format JPG, PNG, atau PDF. Maksimal 5 MB.</p>
        </div>

        <button type="submit" :disabled="!isValid"
                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-black transition"
                :style="isValid
                    ? 'background-color: var(--portal-brand); color: var(--portal-bg); cursor: pointer;'
                    : 'background-color: var(--portal-border); color: var(--portal-text-muted); cursor: not-allowed;'"
                :class="isValid && 'hover:-translate-y-0.5 hover:brightness-95'">Kirim Pendaftaran <i class="fa-solid fa-paper-plane"></i></button>
    </form>
</div>
