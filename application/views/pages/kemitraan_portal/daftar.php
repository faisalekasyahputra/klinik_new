<?php
// Kelas isian diangkat jadi variabel - string yang sama sebelumnya ditulis
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
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]"><?= $jenis === 'kkn' ? 'Daftar KKN Kemitraan' : 'Daftar Magang / Kerja Praktik' ?></h1>
        <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-[color:var(--portal-text-muted)]">Isi formulir di bawah, tim kami akan meninjau dan menghubungi Anda lewat akun terdaftar.</p>
    </div>

    <?php
    // KKN mendaftarkan KAMPUS, bukan satu mahasiswa (permintaan user 21 Agt
    // 2026: "untuk kkn yang bisa mendaftar adalah universitas") - NIM,
    // tempat/tanggal lahir, dan semester adalah data pribadi SATU
    // mahasiswa, jadi tidak lagi ditanyakan untuk jenis ini. Magang tetap
    // per-mahasiswa seperti semula, keempatnya tetap wajib di sana.
    $wajib_data_pribadi = $jenis === 'magang';

    // Kolom `jurusan` juga bermakna GANDA seperti `divisi_atau_tema` (lihat
    // komentarnya di pendaftaran.php) - untuk magang ia program studi
    // mahasiswa, untuk KKN ia nama penanggung jawab kegiatan dari pihak
    // kampus (permintaan user 21 Agt 2026: "Ganti dengan Nama Nama
    // Universitas, Penanggung Jawab, No HP"). Nama kolom DB dibiarkan
    // `jurusan` - itu urusan skema, bukan urusan yang dibaca mahasiswa.
    $judul_jurusan       = $jenis === 'kkn' ? 'Penanggung Jawab' : 'Jurusan / Program Studi';
    $placeholder_jurusan = $jenis === 'kkn' ? 'Nama penanggung jawab kegiatan' : 'Contoh: Teknik Sipil';
    $judul_instansi      = $jenis === 'kkn' ? 'Nama Universitas' : 'Universitas / Instansi Asal';
    $judul_hp            = $jenis === 'kkn' ? 'No HP' : 'Nomor HP/WhatsApp';
    ?>
    <form id="kemitraan-daftar-form" class="mx-auto mt-8 max-w-2xl space-y-4" action="<?= base_url('KemitraanPortal/simpan') ?>" method="POST" enctype="multipart/form-data"
          x-data="{
              ...<?= html_escape(json_encode($isian_awal)) ?>,
              divisi_atau_tema: '', periode_mulai: '', periode_selesai: '',
              langkah: <?= (int) ($jenis === 'magang' ? 1 : 2) ?>,
              slot: <?= html_escape(json_encode(array_map(function ($k) { return ['nama' => $k['bidang'], 'bulan' => $k['bulan'], 'rentang' => $k['rentang']]; }, $ketersediaan))) ?>,
              tahunPapan: <?= (int) $tahun_papan ?>,
              hariIni: '<?= date('Y-m-d') ?>',
              namaBulan: <?= html_escape(json_encode(array_values(Kemitraan_slot_model::nama_bulan()))) ?>,
              tgl(t, m, h) { return t + '-' + String(m).padStart(2, '0') + '-' + String(h).padStart(2, '0'); },
              akhirBulan(t, m) { return this.tgl(t, m, new Date(t, m, 0).getDate()); },
              besok(s) { var d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() + 1); return this.tgl(d.getFullYear(), d.getMonth() + 1, d.getDate()); },
              /* Pilih bidang = tanggal terisi dari slotnya (permintaan dinas 21 Okt 2026):
                 slot pertama yang masih punya kursi dan belum lewat, mulai paling cepat
                 hari ini, disambung selama slot bulan berikutnya bersambung harinya.
                 Mahasiswa tetap boleh mengubah tanggalnya. */
              isiTanggal(kode) {
                  var s = this.slot[kode] || {}, r = s.rentang || {}, b = s.bulan || {};
                  for (var m = 1; m <= 12; m++) {
                      if (!r[m] || !(b[m] > 0) || r[m][1] < this.hariIni) continue;
                      var mulai = r[m][0] > this.hariIni ? r[m][0] : this.hariIni, selesai = r[m][1];
                      for (var k = m + 1; k <= 12 && r[k] && b[k] > 0 && r[k][0] === this.besok(selesai); k++) { selesai = r[k][1]; }
                      this.periode_mulai = mulai; this.periode_selesai = selesai;
                      return;
                  }
                  this.periode_mulai = ''; this.periode_selesai = '';
              },
              /* Sama dengan aturan server (Kemitraan_slot_model::bulan_terhalang): tiap hari
                 periode harus di dalam rentang slot bulannya dan bulan itu masih ada kursi.
                 Hanya tahun papan; tahun lain diputuskan server. */
              get pesanSlot() {
                  if (!this.divisi_atau_tema) return 'Pilih bidang yang dituju.';
                  var s = this.slot[this.divisi_atau_tema] || {}, r = s.rentang || {}, b = s.bulan || {};
                  if (!Object.keys(r).length) return 'Bidang ini belum membuka slot tahun ' + this.tahunPapan + '. Pilih bidang lain.';
                  if (!this.periode_mulai || !this.periode_selesai) return 'Isi tanggal mulai dan selesai.';
                  if (this.periode_selesai < this.periode_mulai) return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                  var t = +this.periode_mulai.slice(0, 4), m = +this.periode_mulai.slice(5, 7);
                  var tAkhir = +this.periode_selesai.slice(0, 4), mAkhir = +this.periode_selesai.slice(5, 7);
                  for (; t < tAkhir || (t === tAkhir && m <= mAkhir); m === 12 ? (m = 1, t++) : m++) {
                      if (t !== this.tahunPapan) continue;
                      var nama = 'Bulan ' + this.namaBulan[m - 1] + ' ' + t;
                      if (!r[m]) return nama + ' belum dibuka untuk bidang ini.';
                      if (!(b[m] > 0)) return nama + ' sudah penuh untuk bidang ini.';
                      var awal = this.periode_mulai > this.tgl(t, m, 1) ? this.periode_mulai : this.tgl(t, m, 1);
                      var akhir = this.periode_selesai < this.akhirBulan(t, m) ? this.periode_selesai : this.akhirBulan(t, m);
                      if (awal < r[m][0] || akhir > r[m][1]) {
                          return nama + ' hanya dibuka tanggal ' + (+r[m][0].slice(8)) + ' sampai ' + (+r[m][1].slice(8)) + '.';
                      }
                  }
                  return '';
              },
              get isValid() {
                  return <?= $wajib_data_pribadi ? "this.nim.trim() && this.tempat_lahir.trim() && this.tanggal_lahir && this.semester &&" : '' ?>
                      this.jurusan.trim()
                      && this.instansi_asal.trim() && this.no_hp.trim()
                      <?= $jenis === 'magang' ? '&& this.divisi_atau_tema.trim() && this.periode_mulai && this.periode_selesai' : '' ?>;
              }
          }">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="jenis" value="<?= html_escape($jenis) ?>">

        <?php if ($jenis === 'magang'): ?>
        <?php
        // DUA LANGKAH (permintaan dinas 21 Okt 2026): mahasiswa memilih slot -
        // bidang dan tanggal - lebih dulu, baru mengisi data diri dan berkas.
        // Langkah 1 memeriksa tiap bulan periode terhadap slot bidang (tahun
        // papan saja) supaya pendaftar tahu sebelum mengetik apa pun. Itu
        // kenyamanan; penolakan sebenarnya tetap periksa_slot() di simpan().
        // Keduanya satu <form> (x-show, bukan x-if) supaya semua isian ikut
        // terkirim.
        ?>
        <ol class="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider">
            <li class="flex items-center gap-2" :class="langkah === 1 ? 'text-[color:var(--portal-brand)]' : 'text-[color:var(--portal-text-muted)]'">
                <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-current">1</span> Pilih slot
            </li>
            <li class="h-px flex-1 bg-[color:var(--portal-border)]" aria-hidden="true"></li>
            <li class="flex items-center gap-2" :class="langkah === 2 ? 'text-[color:var(--portal-brand)]' : 'text-[color:var(--portal-text-muted)]'">
                <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-current">2</span> Data diri &amp; berkas
            </li>
        </ol>

        <div x-show="langkah === 1" class="space-y-4">
        <?php
        // Tema Kegiatan dan Periode DIHILANGKAN untuk KKN (bukan cuma
        // dikosongkan) - permintaan user 21 Agt 2026. KKN sekarang
        // mendaftarkan kampus lewat surat pengantar; tema dan rentang
        // tanggal kegiatan bukan lagi sesuatu yang diminta lewat formulir
        // ini. Magang tetap membutuhkan keduanya - itulah yang mengunci
        // slot bulanan lewat periksa_slot().
        ?>
        <?php
        // Bidang dipilih lewat KARTU, bukan dropdown: tiap kartu membawa
        // keadaan, sisa kursi, dan strip 12 bulan, jadi pendaftar tidak perlu
        // membuka papan slot di halaman lain (keluhan dinas 21 Okt 2026).
        // Istilah keadaan sama dengan papan slot.
        //
        // Kartu yang terpenuhi/belum dibuka TIDAK dimatikan, hanya diredupkan:
        // keadaan dihitung untuk tahun papan saja, sedangkan periode pendaftar
        // bisa jatuh di tahun lain. Penjagaan sebenarnya tetap di
        // KemitraanPortal::simpan() lewat periksa_slot().
        $gaya_keadaan = [
            'menerima'  => ['bg-emerald-500/10 text-emerald-700', 'Masih menerima'],
            'terpenuhi' => ['bg-amber-500/10 text-amber-700',     'Sudah terpenuhi'],
            'tutup'     => ['bg-rose-500/10 text-rose-700',       'Belum dibuka'],
        ];
        $nama_bulan = Kemitraan_slot_model::nama_bulan();
        ?>
        <fieldset>
            <legend class="<?= $label ?>">Bidang yang Dituju</legend>
            <p class="mb-3 text-[11px] text-[color:var(--portal-text-muted)]">
                Slot tahun <?= (int) $tahun_papan ?>. Kotak bulan:
                <span class="inline-block h-2 w-2 rounded-sm bg-emerald-500 align-middle"></span> ada kursi,
                <span class="inline-block h-2 w-2 rounded-sm bg-amber-400 align-middle"></span> penuh,
                <span class="inline-block h-2 w-2 rounded-sm bg-[color:var(--portal-border)] align-middle"></span> tidak dibuka.
            </p>

            <div class="grid grid-cols-1 items-start gap-2 sm:grid-cols-2">
                <?php foreach ($ketersediaan as $kode => $k):
                    $nilai = html_escape(json_encode((string) $kode));
                    $gaya  = $gaya_keadaan[$k['keadaan']];
                ?>
                <label class="flex cursor-pointer flex-col gap-2 rounded-xl border bg-[color:var(--portal-bg-card)] p-3 shadow-sm transition
                              hover:shadow-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[color:var(--portal-brand)]/40
                              <?= $k['keadaan'] === 'menerima' ? '' : 'opacity-75' ?>"
                       :class="divisi_atau_tema === <?= $nilai ?>
                           ? 'border-[color:var(--portal-brand)] ring-2 ring-[color:var(--portal-brand)]/20 opacity-100'
                           : 'border-[color:var(--portal-border)]'">
                    <input type="radio" name="divisi_atau_tema" value="<?= html_escape($kode) ?>" x-model="divisi_atau_tema" @change="isiTanggal($event.target.value)" required class="sr-only">

                    <div class="flex items-center gap-2">
                        <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border text-[8px] transition"
                              :class="divisi_atau_tema === <?= $nilai ?>
                                  ? 'border-[color:var(--portal-brand)] bg-[color:var(--portal-brand)] text-[color:var(--portal-bg)]'
                                  : 'border-[color:var(--portal-border)] text-transparent'">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                        </span>
                        <h3 class="min-w-0 flex-1 truncate text-xs font-black text-[color:var(--portal-text)]" title="<?= html_escape($k['bidang']) ?>"><?= html_escape($k['bidang']) ?></h3>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-black uppercase <?= $gaya[0] ?>"><?= $gaya[1] ?></span>
                    </div>

                    <p class="text-[11px] text-[color:var(--portal-text-muted)]">
                        <?php if ($k['keadaan'] === 'tutup'): ?>
                            Kuota <?= (int) $k['kuota'] ?> mahasiswa
                        <?php else: ?>
                            Sisa <strong class="text-[color:var(--portal-text)]"><?= (int) $k['sisa'] ?></strong> dari <?= (int) $k['kuota'] ?> kursi
                        <?php endif; ?>
                    </p>
                    <?php // Posisi dan kualifikasinya (dinas 22 Okt 2026), sama dengan papan slot. ?>
                    <?php $this->load->view('pages/kemitraan_portal/_posisi', ['posisi' => $k['posisi']]); ?>

                    <div class="grid grid-cols-12 gap-0.5" aria-label="Slot per bulan">
                        <?php foreach ($k['bulan'] as $nomor => $sisa):
                            $warna = $sisa === NULL ? 'bg-[color:var(--portal-btn-bg)] text-[color:var(--portal-text-muted)]'
                                   : ($sisa > 0 ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-amber-950');
                            $judul = $nama_bulan[$nomor] . ': ' . ($sisa === NULL ? 'tidak dibuka' : ($sisa > 0 ? 'sisa ' . $sisa . ' kursi' : 'penuh'));
                        ?>
                            <span class="flex h-4 items-center justify-center rounded-sm text-[8px] font-bold <?= $warna ?>" title="<?= html_escape($judul) ?>">
                                <?= (int) $nomor ?><span class="sr-only"> <?= html_escape($judul) ?></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <?php if (empty($ketersediaan)): ?>
                <p class="rounded-xl border border-[color:var(--portal-border)] p-4 text-center text-xs text-[color:var(--portal-text-muted)]">Belum ada bidang yang membuka magang.</p>
            <?php endif; ?>

        </fieldset>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="kd-mulai" class="<?= $label ?>">Periode Mulai</label>
                <input id="kd-mulai" name="periode_mulai" x-model="periode_mulai" type="date" required min="<?= date('Y-m-d') ?>" class="<?= $isian ?>">
            </div>
            <div>
                <label for="kd-selesai" class="<?= $label ?>">Periode Selesai</label>
                <input id="kd-selesai" name="periode_selesai" x-model="periode_selesai" type="date" required :min="periode_mulai || '<?= date('Y-m-d') ?>'" class="<?= $isian ?>">
            </div>
            <p class="<?= $petunjuk ?> sm:col-span-2 !mt-0">Terisi otomatis dari slot bidang yang dipilih. Boleh diubah selama masih di dalam slot.</p>
        </div>

            <p class="rounded-xl px-4 py-3 text-xs font-bold"
               :class="pesanSlot ? 'bg-[color:var(--portal-btn-bg)] text-[color:var(--portal-text-muted)]' : 'bg-emerald-500/10 text-emerald-700'"
               x-text="pesanSlot || 'Slot tersedia untuk periode ini. Lanjutkan ke data diri.'" aria-live="polite"></p>

            <button type="button" :disabled="!!pesanSlot" @click="langkah = 2; $nextTick(() => $el.closest('form').scrollIntoView({ behavior: 'smooth' }))"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-black transition"
                    :style="!pesanSlot
                        ? 'background-color: var(--portal-brand); color: var(--portal-bg); cursor: pointer;'
                        : 'background-color: var(--portal-border); color: var(--portal-text-muted); cursor: not-allowed;'">Lanjut <i class="fa-solid fa-arrow-right"></i></button>
            <p class="text-center text-[11px] text-[color:var(--portal-text-muted)]">
                Rincian semua bidang ada di <a href="<?= base_url('KemitraanPortal/magang') ?>" class="font-bold underline">papan slot</a>.
            </p>
        </div>
        <?php endif; ?>

        <div x-show="langkah === 2" <?= $jenis === 'magang' ? 'x-cloak' : '' ?> class="space-y-4">
        <?php if ($jenis === 'magang'): ?>
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[color:var(--portal-brand)]/40 bg-[color:var(--portal-brand)]/5 p-4">
            <div class="min-w-0">
                <p class="text-[11px] font-black uppercase tracking-wider text-[color:var(--portal-text-muted)]">Slot dipilih</p>
                <p class="mt-1 text-sm font-bold text-[color:var(--portal-text)]" x-text="(slot[divisi_atau_tema] || {}).nama || '-'"></p>
                <p class="text-xs text-[color:var(--portal-text-muted)]" x-text="periode_mulai.split('-').reverse().join('/') + ' sampai ' + periode_selesai.split('-').reverse().join('/')"></p>
            </div>
            <button type="button" @click="langkah = 1" class="rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]">Ubah slot</button>
        </div>
        <?php endif; ?>

        <?php
        // BACA-SAJA dan tanpa atribut name: nilainya tidak pernah dikirim, dan
        // `simpan()` mengambil user_id dari sesi. Ditampilkan supaya pendaftar
        // tahu akun mana yang menempel pada pendaftaran ini - sebelumnya itu
        // terjadi diam-diam.
        ?>
        <div class="rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)]/50 p-4">
            <p class="text-[11px] font-black uppercase tracking-wider text-[color:var(--portal-text-muted)]">Terkirim atas nama akun</p>
            <p class="mt-1 text-sm font-bold text-[color:var(--portal-text)]"><?= html_escape($nama_akun ?: '-') ?></p>
            <p class="text-xs text-[color:var(--portal-text-muted)]"><?= html_escape($email_akun ?: '-') ?></p>
            <p class="mt-2 text-[11px] text-[color:var(--portal-text-muted)]">Data diri di bawah terisi dari <a href="<?= base_url('akun/profil') ?>" class="font-bold underline">Profil Saya</a>. Lengkapi di sana supaya tidak perlu mengetik ulang.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 <?= $wajib_data_pribadi ? 'sm:grid-cols-2' : '' ?>">
            <?php if ($wajib_data_pribadi): ?>
            <div>
                <label for="kd-nim" class="<?= $label ?>">NIM</label>
                <input id="kd-nim" name="nim" x-model="nim" required maxlength="30" placeholder="Nomor induk mahasiswa" class="<?= $isian ?>">
            </div>
            <?php endif; ?>
            <div>
                <label for="kd-jurusan" class="<?= $label ?>"><?= html_escape($judul_jurusan) ?></label>
                <input id="kd-jurusan" name="jurusan" x-model="jurusan" required maxlength="150" placeholder="<?= html_escape($placeholder_jurusan) ?>" class="<?= $isian ?>">
            </div>
        </div>

        <?php
        // "TTL" di mockup satu baris, di DB dua kolom (tempat_lahir VARCHAR +
        // tanggal_lahir DATE). Tampilannya tetap satu baris berdampingan;
        // yang dipisah cuma penyimpanannya, supaya tanggalnya bisa diurutkan
        // dan dihitung tanpa membongkar teks bebas nanti.
        //
        // Seluruh blok TTL + Semester DIHILANGKAN untuk KKN (bukan cuma
        // dikosongkan) - "tempat/tanggal lahir siapa" tidak lagi punya
        // jawaban begitu yang mendaftar adalah kampus, bukan satu orang.
        ?>
        <?php if ($wajib_data_pribadi): ?>
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
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-4 <?= $wajib_data_pribadi ? 'sm:grid-cols-3' : '' ?>">
            <?php if ($wajib_data_pribadi): ?>
            <div>
                <label for="kd-semester" class="<?= $label ?>">Semester</label>
                <input id="kd-semester" name="semester" x-model="semester" type="number" min="1" max="14" required placeholder="6" class="<?= $isian ?>">
            </div>
            <?php endif; ?>
            <div class="<?= $wajib_data_pribadi ? 'sm:col-span-2' : '' ?>">
                <label for="kd-instansi" class="<?= $label ?>"><?= html_escape($judul_instansi) ?></label>
                <input id="kd-instansi" name="instansi_asal" x-model="instansi_asal" required maxlength="150" placeholder="Nama kampus/universitas" class="<?= $isian ?>">
            </div>
        </div>

        <div>
            <label for="kd-hp" class="<?= $label ?>"><?= html_escape($judul_hp) ?></label>
            <input id="kd-hp" name="no_hp" x-model="no_hp" type="tel" required maxlength="15" placeholder="08xxxxxxxxxx" class="<?= $isian ?>">
        </div>

        <?php
        // Surat Pengantar DIHILANGKAN untuk KKN (bukan lagi opsional -
        // permintaan user 21 Agt 2026: "tidak perlu dikasih surat
        // pengantar"). WAJIB untuk magang seperti sebelumnya (keputusan
        // user 2 Agt 2026). `required` di sini cuma kenyamanan; yang
        // menegakkannya KemitraanPortal::simpan(), yang menolak sebelum
        // barisnya lahir.
        ?>
        <?php if ($jenis === 'magang'): ?>
        <div>
            <label for="kd-surat" class="<?= $label ?>">Surat Pengantar
                <span class="font-normal text-rose-500">(wajib)</span></label>
            <input id="kd-surat" name="file_surat_pengantar" type="file" accept=".jpg,.jpeg,.png,.pdf"
                   required class="<?= $berkas ?>">
            <p class="<?= $petunjuk ?>">Format JPG, PNG, atau PDF. Maksimal 5 MB. Tanpa surat pengantar, pendaftaran magang tidak bisa dikirim.</p>
        </div>

        <?php
        // Proposal hanya untuk magang (keputusan user 30 Jul 2026). Tidak
        // dirender untuk KKN, DAN tidak diproses server untuk KKN - lihat
        // KemitraanPortal::simpan(); formulir yang tidak menampilkan sesuatu
        // bukan penjagaan, ia cuma tidak menawarkannya.
        //
        // Ditandai "opsional" mengikuti surat pengantar. Menjadikan dokumen
        // WAJIB adalah keputusan #11 (syarat bukti resmi) yang masih BLOCKED -
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
        </div>
    </form>
</div>
