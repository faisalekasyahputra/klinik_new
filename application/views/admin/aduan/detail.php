<?php
/**
 * Detail satu aduan - butir 16 putaran 2. READ-ONLY.
 *
 * Isi aduan ditampilkan UTUH di sini, dan itu justru alasan layar ini ada:
 * di daftar, `pesan` terpotong, sehingga admin menriase dari judul saja.
 *
 * Lampiran TIDAK ditautkan langsung ke berkasnya - tetap lewat
 * `Admin_Aduan/lihat_lampiran` yang sudah ada, karena itu satu-satunya jalur
 * yang memeriksa kewenangan sebelum menyajikan berkas privat.
 */
$e = static fn($v) => html_escape((string) $v);
$kelas_status = ['Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok'];
$this->load->view('admin/components/judul_halaman', [
    'jh_judul' => $aduan->judul,
    'jh_deskripsi' => 'Diterima ' . $e(tgl_id($aduan->created_at, TRUE, TRUE)) . ' '
        . $this->load->view('admin/components/status_badge', ['label' => $aduan->status, 'kelas' => $kelas_status[$aduan->status] ?? 'pending'], TRUE),
    'jh_aksi' => '<a href="' . base_url($back_url) . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali ke daftar aduan</span></a>',
]);
?>
<div class="tumpuk-bagian">
    <dl class="kartu-admin isi-kartu grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs text-gray-500 dark:text-brand-muted">Pengirim</dt>
            <dd class="mt-0.5 font-bold text-gray-900 dark:text-white"><?= $e($aduan->nama ?: 'Tidak dicantumkan') ?></dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-brand-muted">Email</dt>
            <dd class="mt-0.5 font-bold text-gray-900 dark:text-white"><?= $e($aduan->email ?: 'Tidak dicantumkan') ?></dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-brand-muted">Bidang tujuan</dt>
            <dd class="mt-0.5 font-bold text-gray-900 dark:text-white">
                <?= $aduan->bidang_kode === NULL
                    ? '<span class="text-amber-700 dark:text-amber-300">Belum ditriase</span>'
                    : $e($aduan->nama_bidang ?: $aduan->bidang_kode) ?>
            </dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500 dark:text-brand-muted">Ditinjau oleh</dt>
            <dd class="mt-0.5 font-bold text-gray-900 dark:text-white">
                <?= $aduan->reviewed_by
                    ? $e($aduan->nama_peninjau ?: 'Petugas') . ' &middot; ' . $e(tgl_id($aduan->reviewed_at, TRUE))
                    : 'Belum ditinjau' ?>
            </dd>
        </div>
    </dl>

    <section class="kartu-admin isi-kartu">
        <h2 class="text-sm font-black text-gray-900 dark:text-white">Isi aduan</h2>
        <?php // Sengaja `nl2br` atas teks yang SUDAH di-escape: paragraf pengirim
              // tetap terbaca sebagaimana ia menulisnya, tanpa satu pun tag ikut hidup. ?>
        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-200"><?= nl2br($e($aduan->pesan)) ?></p>
    </section>

    <?php if ( ! empty($aduan->lampiran)): ?>
        <section class="kartu-admin isi-kartu">
            <h2 class="text-sm font-black text-gray-900 dark:text-white">Lampiran</h2>
            <a href="<?= base_url('Admin_Aduan/lihat_lampiran/' . (int) $aduan->id) ?>" class="tombol-kedua mt-2">
                <i class="ph ph-paperclip"></i><span>Buka lampiran</span>
            </a>
        </section>
    <?php endif; ?>

    <?php if ( ! empty($aduan->catatan_admin)): ?>
        <section class="kartu-admin isi-kartu border-l-4 border-l-emerald-500">
            <h2 class="text-sm font-black text-emerald-700 dark:text-emerald-300">Jawaban / catatan petugas</h2>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-200"><?= nl2br($e($aduan->catatan_admin)) ?></p>
        </section>
    <?php endif; ?>
</div>
