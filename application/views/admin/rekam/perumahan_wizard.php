<?php
/**
 * Rekam Data - wizard Input Capaian Perumahan (W3).
 *
 * Lima langkah dirender server, satu berkas - mengikuti idiom
 * `pages/warga/pendataan.php`. Tidak ada JavaScript yang wajib: form
 * tambah/ubah memakai `<details>`, sehingga layar tetap berfungsi penuh kalau
 * skrip gagal dimuat. Modal di rancangan diterjemahkan menjadi disclosure,
 * bukan ditiru dengan overlay yang butuh JS.
 *
 * Gaya: set bersama di admin/layouts/head.php (.kartu-admin, .tumpuk-bagian,
 * .tombol-utama/.tombol-kedua/.tombol-aksi, .chip-filter). Kelas tombol ditulis
 * langsung di tiap tag supaya pemindai uji_regresi_tampilan.php bisa melihatnya.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rp = static fn($n) => number_format((int) $n, 0, ',', '.');

$laporan_id = (int) ($laporan['id'] ?? 0);
$aktif      = array_search($langkah, $urutan, TRUE);

$kotak  = 'kartu-admin isi-kartu';
// Kolom isian harus beda dari kartu di belakangnya: latar sendiri, garis tepi tegas, cincin fokus.
// Dulu latarnya transparan dengan garis 10% putih, sehingga di tema gelap kolom menyatu dengan kartu.
$isian  = 'mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900'
    . ' focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30'
    . ' dark:border-white/25 dark:bg-black/40 dark:text-white'
    . ' dark:focus:border-brand-primary dark:focus:ring-brand-primary/30';

$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];
$warna_status = [
    'draft'           => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'terkirim'        => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
    'perlu_perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
];
$label_status = ['draft' => 'Draft, belum dikirim', 'terkirim' => 'Terkirim', 'perlu_perbaikan' => 'Perlu Perbaikan'];

/** Form tambah/ubah satu sumber dana. Dipakai untuk baris baru maupun edit. */
$form_sumber = function ($program, $row = NULL) use ($e, $laporan_id, $isian, $sumber_label, $sumber_berketerangan) {
    $terkunci_sumber = $row !== NULL;
    ?>
    <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_sumber') ?>" enctype="multipart/form-data" class="space-y-3">
      <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
      <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
      <input type="hidden" name="program" value="<?= $e($program) ?>">

      <div>
        <label class="text-xs font-bold text-gray-700 dark:text-brand-muted">Sumber Dana</label>
        <?php if ($terkunci_sumber): ?>
          <input type="hidden" name="sumber_dana" value="<?= $e($row['sumber_dana']) ?>">
          <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white"><?= $e($sumber_label[$row['sumber_dana']] ?? $row['sumber_dana']) ?></p>
        <?php else: ?>
          <select name="sumber_dana" required class="<?= $isian ?>">
            <option value="">Pilih sumber dana</option>
            <?php foreach ($sumber_label as $kode => $label): ?>
              <option value="<?= $e($kode) ?>"><?= $e($label) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <?php foreach (['rencana' => 'Rencana', 'realisasi' => 'Realisasi'] as $sisi => $judul): ?>
        <fieldset class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
          <legend class="px-1 text-xs font-bold text-gray-700 dark:text-gray-200"><?= $judul ?></legend>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Unit</label>
              <input type="number" min="0" step="1" name="<?= $sisi ?>_unit"
                     value="<?= (int) ($row[$sisi . '_unit'] ?? 0) ?>" class="<?= $isian ?>">
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Anggaran (Rp)</label>
              <input type="number" min="0" step="1" name="<?= $sisi ?>_anggaran"
                     value="<?= (int) ($row[$sisi . '_anggaran'] ?? 0) ?>" class="<?= $isian ?>">
            </div>
          </div>
        </fieldset>
      <?php endforeach; ?>

      <div>
        <label class="text-xs font-bold text-gray-700 dark:text-brand-muted">
          Nama perusahaan / penyalur
        </label>
        <input type="text" name="keterangan" maxlength="150"
               value="<?= $e($row['keterangan'] ?? '') ?>" class="<?= $isian ?>">
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
          Hanya dipakai untuk <b><?= $e(implode(', ', array_map(fn($k) => $sumber_label[$k] ?? $k, array_keys($sumber_berketerangan)))) ?></b>;
          di sumber lain isian ini diabaikan, bukan ditolak.
        </p>
      </div>

      <?php // BNBA = daftar penerima per sumber dana (Excel). Wajib untuk sumber yang sudah ada realisasinya (gerbang di Rekam_data_model::kirim). ?>
      <div>
        <label class="text-xs font-bold text-gray-700 dark:text-brand-muted">
          BNBA (Excel) <span class="font-normal">- wajib bila ada realisasi</span>
        </label>
        <div class="mt-1"><?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'bnba', 'ib_accept' => '.xlsx,.xls', 'ib_required' => FALSE, 'ib_attr' => '']); ?></div>
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
          Kolom: NIK, NO KK, NAMA, ALAMAT, KABUPATEN, KECAMATAN, DESA/KELURAHAN, KDDESA (terisi otomatis),
          JUMLAH MATERIAL, JUMLAH PADAT KARYA. Kolom NIK dan NO KK diformat <b>Teks</b>. Satu baris salah
          menolak seluruh berkas. Mengunggah lagi menggantikan berkas lama.
          <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/templat_bnba') ?>">Unduh templat BNBA</a>
        </p>
      </div>

      <button class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
    </form>
    <?php
};
?>

<div data-panel-progresif class="tumpuk-bagian">

  <!-- ================= kepala + penunjuk langkah ================= -->
  <section class="<?= $kotak ?>">
    <?php // Satu judul (wilayah + periode) dan SATU lencana status. Dulu empat kotak berbentuk beda
          // berjajar - kotak, kotak, pil, tautan - sehingga tidak jelas mana judul, mana status. ?>
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-bold text-gray-500 dark:text-brand-muted">Kabupaten/Kota</p>
        <h2 class="text-lg font-black text-gray-900 dark:text-white">
          <?= $e($scope_label) ?>
          <?php if ($laporan): ?>
            <span class="font-bold text-gray-500 dark:text-brand-muted">&middot; <?= $e($nama_tw[(int) $triwulan] ?? $triwulan) ?> <?= (int) $tahun ?></span>
          <?php endif; ?>
        </h2>
        <?php if ($laporan): ?>
          <span class="mt-1 inline-block rounded-md px-2 py-0.5 text-xs font-bold <?= $warna_status[$laporan['status']] ?? '' ?>">
            <?= $e($label_status[$laporan['status']] ?? $laporan['status']) ?>
          </span>
        <?php endif; ?>
      </div>
      <a href="<?= base_url('Rekam_Perumahan') ?>" class="tombol-kedua">
        <i class="ph ph-arrow-left" aria-hidden="true"></i><span>Capaian</span>
      </a>
    </div>

    <?php if ($laporan && $terkunci): ?>
      <?php /* Spanduk ini muncul di SETIAP langkah, bukan hanya di layar isian.
                Sebelumnya L1 dan L2 tetap menyajikan formulir yang bisa ditekan
                untuk laporan terkunci, dan tiap kiriman dijawab "Laporan sudah
                terkirim dan terkunci" - menawarkan tindakan yang sudah pasti
                ditolak, lalu menyalahkan penggunanya. */ ?>
      <p class="mt-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
        <b>Periode ini sudah dikirim dan terkunci.</b> Isinya bisa dibaca, tidak bisa diubah.
        Perubahan baru mungkin setelah Admin Bidang mengembalikannya lewat
        <i>Minta Perbaikan</i>. Untuk mengisi periode lain,
        <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/input') ?>">pilih triwulan lain</a>.
      </p>
    <?php endif; ?>

    <?php if ($laporan && $laporan['status'] === 'perlu_perbaikan' && ! empty($laporan['catatan_admin'])): ?>
      <?php /* Catatan peninjau dulu HANYA dirender di L5 (Review & Kirim).
                Alasan orang ini membuka wizard justru karena laporannya
                dikembalikan - dan ia mendarat di langkah yang tersimpan di
                `langkah_sekarang`, yang belum tentu L5. Jadi ia melihat formulir
                terbuka tanpa satu pun keterangan kenapa, dan baru menemukan
                alasannya kalau kebetulan mengklik sampai langkah terakhir.
                Alasan yang sama persis dengan spanduk terkunci di atas: kalau
                sesuatu mengubah apa yang boleh/harus dilakukan di SETIAP
                langkah, ia harus terlihat di setiap langkah. */ ?>
      <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
        <b>Dikembalikan untuk diperbaiki.</b> Catatan peninjau:
        <?= $e($laporan['catatan_admin']) ?>
      </p>
    <?php endif; ?>

    <?php // Penunjuk langkah, BUKAN tombol: langkahnya tidak bisa diklik, jadi tidak boleh berbentuk
          // tombol berlatar warna. Langkah aktif = teks tebal + garis bawah; yang lewat = centang. ?>
    <ol class="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-gray-200 pt-3 text-sm dark:border-white/10" aria-label="Langkah pengisian">
      <?php foreach ($urutan as $i => $kode): ?>
        <?php $lewat = $aktif !== FALSE && $i < $aktif; $kini = $kode === $langkah; ?>
        <li class="flex items-center gap-1.5 border-b-2 pb-1
                   <?= $kini ? 'border-blue-600 font-bold text-gray-900 dark:border-brand-primary dark:text-white'
                             : ($lewat ? 'border-transparent text-gray-600 dark:text-gray-300'
                                       : 'border-transparent text-gray-400 dark:text-brand-muted') ?>"
            <?= $kini ? 'aria-current="step"' : '' ?>>
          <?php if ($lewat): ?>
            <i class="ph ph-check-circle text-emerald-600 dark:text-emerald-400" aria-label="selesai"></i>
          <?php else: ?>
            <span class="text-xs tabular-nums"><?= $i + 1 ?>.</span>
          <?php endif; ?>
          <span><?= $e($label_langkah[$kode]) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <?php // ================= L1 - PERIODE (frame 003) ================= ?>
  <?php if ($langkah === 'periode'): ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Periode Pelaporan</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Pilih tahun dan triwulan yang akan diisi. Draft baru lahir setelah tombol
        Lanjut ditekan - membuka layar ini saja tidak membuat apa pun.
      </p>

      <form method="post" action="<?= base_url('Rekam_Perumahan/mulai') ?>" class="mt-4 space-y-4">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">

        <div class="max-w-xs">
          <label for="tahun" class="text-xs font-bold text-gray-700 dark:text-brand-muted">Tahun</label>
          <select id="tahun" name="tahun" class="<?= $isian ?>">
            <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
              <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <fieldset>
          <legend class="text-xs font-bold text-gray-700 dark:text-brand-muted">Triwulan</legend>
          <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <?php foreach ($nama_tw as $n => $label): ?>
              <?php $st = $status_triwulan[$n] ?? NULL; ?>
              <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-center transition-colors
                            <?= $st === 'terkirim'
                                  ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/5'
                                  : 'border-gray-200 hover:border-blue-300 dark:border-white/10 dark:hover:border-brand-primary/30' ?>">
                <span class="block text-sm font-bold">
                  <input type="radio" name="triwulan" value="<?= $n ?>" class="mr-1.5"
                         <?= $n === (int) $triwulan ? 'checked' : '' ?>>
                  <?= $e($label) ?>
                </span>
                <?php /* Status disebut DI SINI, bukan setelah orang masuk. Periode
                          terkirim tetap boleh dipilih - isinya boleh dibaca - tetapi
                          ia tahu lebih dulu bahwa tidak ada yang bisa diubah. */ ?>
                <span class="mt-0.5 block text-[11px] font-medium
                             <?= $st ? 'text-gray-600 dark:text-brand-muted' : 'text-gray-400 dark:text-brand-muted/60' ?>">
                  <?= $st === 'terkirim' ? 'terkunci - sudah dikirim'
                        : ($st === 'perlu_perbaikan' ? 'perlu perbaikan'
                        : ($st === 'draft' ? 'draft tersimpan' : 'belum diisi')) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <p class="rounded-xl border-l-4 border-blue-500 bg-blue-50 p-3 text-sm text-blue-900 dark:bg-blue-500/10 dark:text-blue-200">
          Isi <b>capaian triwulan ini saja</b>, bukan kumulatif sejak Januari.
          Angka kumulatif dihitung sendiri oleh sistem dan ditampilkan di layar Capaian.
        </p>

        <button class="tombol-utama"><span>Lanjut</span><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span></button>
      </form>
    </section>

  <?php // ================= L2 - PROGRAM (frame 004) ================= ?>
  <?php elseif ($langkah === 'program'): ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Program yang akan dilaporkan</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Centang hanya program yang memang punya capaian pada triwulan ini.
        Program yang tidak dicentang tidak dianggap kekurangan.
      </p>

      <?php if ($terkunci): ?>
        <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">
          <?php foreach ($program_label as $kode => $label): ?>
            <?php $tercentang = in_array($kode, $program_dipilih, TRUE); ?>
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                        <?= $tercentang
                              ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/5'
                              : 'border-gray-200 opacity-50 dark:border-white/10' ?>">
              <i class="ph <?= $tercentang ? 'ph-check-circle text-emerald-600 dark:text-emerald-400' : 'ph-circle text-gray-400' ?>"></i>
              <span class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>
              <?php if ( ! $tercentang): ?>
                <span class="ml-auto text-xs text-gray-500 dark:text-brand-muted">tidak dilaporkan</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="mt-4">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="isian">
          <button class="tombol-utama"><span>Lihat isian</span><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span></button>
        </form>
      <?php else: ?>
      <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_program') ?>" class="mt-4 space-y-4">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <?php foreach ($program_label as $kode => $label): ?>
            <?php $tercentang = in_array($kode, $program_dipilih, TRUE); ?>
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3
                          transition-colors hover:border-blue-300 dark:border-white/10 dark:hover:border-brand-primary/30">
              <input type="checkbox" name="program[]" value="<?= $e($kode) ?>" <?= $tercentang ? 'checked' : '' ?>>
              <span class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <p class="rounded-xl border-l-4 border-amber-500 bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
          Mencabut centang <b>menghapus seluruh angka</b> program itu pada triwulan ini.
        </p>

        <div class="flex flex-wrap gap-2">
          <button class="tombol-utama"><span>Lanjut</span><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span></button>
        </div>
      </form>
      <?php endif; ?>
    </section>

  <?php // ========== L3 - ISIAN PER PROGRAM ("Setelah ada Data") ========== ?>
  <?php elseif ($langkah === 'isian'): ?>
    <?php if ( ! $program_dipilih): ?>
      <section class="kartu-admin isi-kartu text-center">
        <p class="font-bold text-gray-900 dark:text-white">Belum ada program yang dicentang.</p>
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="mt-4">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="program">
          <button class="tombol-utama"><span>Pilih program dulu</span></button>
        </form>
      </section>
    <?php else: ?>
      <section class="<?= $kotak ?>">
        <div class="flex flex-wrap gap-2">
          <?php foreach ($program_dipilih as $kode): ?>
            <?php $kosong = in_array($kode, $program_kosong, TRUE); ?>
            <a href="<?= base_url('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=isian&program=' . rawurlencode($kode)) ?>"
               class="chip-filter"<?= $kode === $program_aktif ? ' aria-current="true"' : '' ?>>
              <?= $e($program_label[$kode] ?? $kode) ?><?= $kosong ? ' •' : '' ?>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-brand-muted">
          Tanda <b>•</b> berarti program itu belum punya satu pun sumber dana. Laporan
          tidak bisa dikirim selama masih ada tanda itu.
        </p>
      </section>

      <?php $daftar = $baris[$program_aktif] ?? []; ?>
      <section class="<?= $kotak ?>">
        <h2 class="text-lg font-black text-gray-900 dark:text-white">
          <?= $e($program_label[$program_aktif] ?? $program_aktif) ?>
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">Rencana dan Realisasi</p>

        <?php if ( ! $daftar): ?>
          <p class="mt-4 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-brand-muted">
            Belum ada data.
          </p>
        <?php else: ?>
          <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($daftar as $kode => $row): ?>
              <li class="py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($sumber_label[$kode] ?? $kode) ?></p>
                    <?php if ($row['keterangan'] !== ''): ?>
                      <p class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($row['keterangan']) ?></p>
                    <?php endif; ?>
                    <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                      <dt class="text-gray-500 dark:text-brand-muted">Rencana</dt>
                      <dt class="text-gray-500 dark:text-brand-muted">Realisasi</dt>
                      <dd class="text-gray-900 dark:text-white"><?= $rp($row['rencana_unit']) ?> unit<br>
                        <span class="text-xs text-gray-500 dark:text-brand-muted">Rp <?= $rp($row['rencana_anggaran']) ?></span></dd>
                      <dd class="text-gray-900 dark:text-white"><?= $rp($row['realisasi_unit']) ?> unit<br>
                        <span class="text-xs text-gray-500 dark:text-brand-muted">Rp <?= $rp($row['realisasi_anggaran']) ?></span></dd>
                    </dl>
                    <?php $bs = $bnba_sumber[$program_aktif . '|' . $kode] ?? NULL; ?>
                    <?php if ($bs): ?>
                      <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                        BNBA: <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/unduh_bnba_sumber/' . (int) $row['id']) ?>"><?= $e($bs['nama_asli']) ?></a>
                        - <?= $rp($bs['jumlah_penerima']) ?> penerima, material Rp <?= $rp($bs['total_material']) ?>,
                        padat karya Rp <?= $rp($bs['total_padat_karya']) ?>
                      </p>
                    <?php elseif ((int) $row['realisasi_unit'] > 0): ?>
                      <p class="mt-2 text-xs font-bold text-red-600 dark:text-red-400">BNBA belum dilampirkan (wajib karena ada realisasi).</p>
                    <?php endif; ?>
                  </div>
                  <?php if ( ! $terkunci): ?>
                    <form method="post" action="<?= base_url('Rekam_Perumahan/hapus_sumber') ?>"
                          data-konfirmasi="<?= $e($sumber_label[$kode] ?? $kode) ?> akan dihapus dari program ini." data-konfirmasi-judul="Hapus sumber data?" data-konfirmasi-label="Hapus" data-konfirmasi-bahaya>
                      <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
                      <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
                      <input type="hidden" name="program" value="<?= $e($program_aktif) ?>">
                      <input type="hidden" name="sumber_dana" value="<?= $e($kode) ?>">
                      <button class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash"></i><span>Hapus</span></button>
                    </form>
                  <?php endif; ?>
                </div>

                <?php if ( ! $terkunci): ?>
                  <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-bold text-blue-600 dark:text-brand-primary">Ubah</summary>
                    <div class="mt-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                      <?php $form_sumber($program_aktif, $row); ?>
                    </div>
                  </details>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ( ! $terkunci): ?>
          <details class="mt-4" <?= $daftar ? '' : 'open' ?>>
            <summary class="cursor-pointer text-sm font-bold text-blue-600 dark:text-brand-primary">
              &plus; Tambah sumber dana
            </summary>
            <div class="mt-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
              <?php $form_sumber($program_aktif); ?>
            </div>
          </details>
        <?php endif; ?>
      </section>

      <section class="<?= $kotak ?>">
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="flex flex-wrap gap-2">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <button name="langkah" value="program" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Program</span></button>
          <button name="langkah" value="bnba" class="tombol-utama"><span>Lanjut</span><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span></button>
        </form>
      </section>
    <?php endif; ?>

  <?php // ================= L4 - BNBA (WAJIB sejak 5 Agt 2026) ================= ?>
  <?php elseif ($langkah === 'bnba'): ?>
    <section class="<?= $kotak ?>">
      <?php /* Kata "wajib" di sini HARUS sama dengan yang ditegakkan
               `Rekam_data_model::kirim()`. Sampai 5 Agt 2026 langkah ini
               berbunyi "opsional - boleh dilewati", dan membiarkannya berbunyi
               begitu sesudah servernya menolak berarti layar menjanjikan
               sesuatu yang lalu ditolak tanpa sebab yang terbaca. */ ?>
      <h2 class="text-lg font-black text-gray-900 dark:text-white">BNBA per sumber dana</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Daftar penerima (Excel) diunggah <b class="text-gray-900 dark:text-white">di form tiap sumber dana</b>
        pada langkah Isian Capaian. Wajib untuk sumber dana yang sudah ada realisasinya.
      </p>

      <?php if ($bnba): ?>
        <p class="mt-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
          Laporan ini memakai berkas BNBA tunggal dari pelaporan sebelumnya:
          <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/unduh_bnba/' . $laporan_id) ?>"><?= $e($bnba['nama_asli']) ?></a>
          <span class="text-xs">(<?= $rp((int) ($bnba['ukuran'] / 1024)) ?> KB)</span>. Ini sudah memenuhi syarat kirim.
        </p>
      <?php endif; ?>

      <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
        <?php $ada_realisasi = FALSE; foreach ($program_dipilih as $kode_p): foreach ($baris[$kode_p] ?? [] as $kode_s => $row_s): ?>
          <?php if ((int) $row_s['realisasi_unit'] < 1) { continue; } $ada_realisasi = TRUE; $bs = $bnba_sumber[$kode_p . '|' . $kode_s] ?? NULL; ?>
          <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
            <span class="font-bold text-gray-900 dark:text-white"><?= $e($sumber_label[$kode_s] ?? $kode_s) ?>
              <span class="font-normal text-gray-500 dark:text-brand-muted">- <?= $e($program_label[$kode_p] ?? $kode_p) ?></span></span>
            <?php if ($bs): ?>
              <span class="text-emerald-700 dark:text-emerald-300"><?= $rp($bs['jumlah_penerima']) ?> penerima</span>
            <?php else: ?>
              <a class="font-bold text-red-600 underline dark:text-red-400" href="<?= base_url('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=isian&program=' . rawurlencode($kode_p)) ?>">Belum dilampirkan</a>
            <?php endif; ?>
          </li>
        <?php endforeach; endforeach; ?>
        <?php if ( ! $ada_realisasi): ?>
          <li class="py-3 text-sm text-gray-500 dark:text-brand-muted">Belum ada sumber dana dengan realisasi, jadi belum ada BNBA yang diwajibkan.</li>
        <?php endif; ?>
      </ul>
    </section>

    <section class="<?= $kotak ?>">
      <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="flex flex-wrap gap-2">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <button name="langkah" value="isian" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Isian</span></button>
        <button name="langkah" value="review" class="tombol-utama"><span>Lanjut</span><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span></button>
      </form>
    </section>

  <?php // ================= L5 - REVIEW & KIRIM ================= ?>
  <?php else: ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Review &amp; Kirim</h2>

      <?php // Catatan peninjau DIPINDAH ke tingkat halaman (spanduk di atas
            // stepper) supaya terbaca di setiap langkah. Sengaja tidak
            // digandakan di sini: dua salinan pesan yang sama pada satu layar
            // membuat pembacanya mengira ada dua catatan berbeda. ?>

      <?php // Ringkasan angka per program (jumlah semua sumber dana) dan status BNBA,
            // supaya yang akan terkunci sesudah Kirim terbaca dulu di satu layar. ?>
      <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
        <?php foreach ($program_dipilih as $kode): ?>
          <?php
          $daftar_prog = $baris[$kode] ?? [];
          $n = count($daftar_prog);
          $jumlah = static fn($kolom) => array_sum(array_column($daftar_prog, $kolom));
          ?>
          <li class="py-3 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <span class="font-bold text-gray-900 dark:text-white"><?= $e($program_label[$kode] ?? $kode) ?></span>
              <span class="<?= $n ? 'text-gray-500 dark:text-brand-muted' : 'font-bold text-red-600 dark:text-red-400' ?>">
                <?= $n ? $n . ' sumber dana' : 'belum ada sumber dana' ?>
              </span>
            </div>
            <?php if ($n): ?>
              <dl class="mt-1 grid grid-cols-2 gap-x-6 text-xs">
                <dt class="text-gray-500 dark:text-brand-muted">Rencana</dt>
                <dt class="text-gray-500 dark:text-brand-muted">Realisasi</dt>
                <dd class="text-gray-900 dark:text-white"><?= $rp($jumlah('rencana_unit')) ?> unit, Rp <?= $rp($jumlah('rencana_anggaran')) ?></dd>
                <dd class="text-gray-900 dark:text-white"><?= $rp($jumlah('realisasi_unit')) ?> unit, Rp <?= $rp($jumlah('realisasi_anggaran')) ?></dd>
              </dl>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
        <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
          <span class="font-bold text-gray-900 dark:text-white">Lampiran BNBA</span>
          <?php if ($bnba): ?>
            <span class="text-emerald-700 dark:text-emerald-300">Terlampir: <?= $e($bnba['nama_asli']) ?></span>
          <?php elseif ( ! $sumber_tanpa_bnba): ?>
            <span class="text-emerald-700 dark:text-emerald-300">Lengkap (<?= count($bnba_sumber) ?> sumber dana)</span>
          <?php else: ?>
            <span class="font-bold text-red-600 dark:text-red-400"><?= count($sumber_tanpa_bnba) ?> sumber dana belum dilampiri</span>
          <?php endif; ?>
        </li>
      </ul>

      <?php if ( ! $bnba && $sumber_tanpa_bnba && ! $terkunci): ?>
        <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
          Laporan tidak bisa dikirim sebelum setiap sumber dana yang ada realisasinya dilampiri BNBA. Buka langkah BNBA untuk melihat yang belum.
        </p>
      <?php endif; ?>

      <?php if ($program_kosong): ?>
        <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
          <?= count($program_kosong) ?> program dicentang tetapi belum punya satu pun sumber dana.
          Lengkapi atau cabut centangnya sebelum mengirim.
        </p>
      <?php elseif ( ! $program_dipilih): ?>
        <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
          Belum ada program yang dipilih untuk dilaporkan.
        </p>
      <?php endif; ?>
    </section>

    <section class="<?= $kotak ?>">
      <div class="flex flex-wrap gap-2">
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="bnba">
          <button class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>BNBA</span></button>
        </form>
        <?php if ( ! $terkunci): ?>
          <form method="post" action="<?= base_url('Rekam_Perumahan/kirim') ?>"
                data-konfirmasi="Setelah terkirim, laporan terkunci sampai peninjau mengembalikannya." data-konfirmasi-judul="Kirim laporan?" data-konfirmasi-label="Kirim laporan">
            <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
            <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
            <button class="tombol-utama"><i class="ph ph-paper-plane-tilt"></i><span>Kirim laporan</span></button>
          </form>
        <?php else: ?>
          <p class="text-sm text-gray-500 dark:text-brand-muted">
            Laporan sudah terkirim dan terkunci. Perubahan hanya bisa dilakukan setelah
            Admin Bidang mengembalikannya untuk diperbaiki.
          </p>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
