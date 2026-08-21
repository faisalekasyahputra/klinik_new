<?php
/**
 * Detail satu KKN dari dashboard universitas (permintaan user 21 Agt 2026)
 * - template TERPISAH dari pendaftaran.php lama karena bentuk datanya
 * beda: dua surat (bukan satu), roster peserta (tidak ada di model lama),
 * dan tanpa data pribadi mahasiswa sama sekali (NIM/Jurusan/Semester/TTL
 * semuanya NULL untuk baris dari dashboard ini).
 *
 * SHELL: dashboard terpadu (admin/index), sama dengan kkn_dashboard.php -
 * dijangkau dari sana, jadi harus terlihat sebagai bagian dari layar yang
 * sama (keputusan user 21 Agt 2026). Ikon Phosphor (ph-*), bukan FontAwesome.
 *
 * Anti-IDOR ditegakkan di controller (pendaftaran_milik(), user_id dari
 * sesi) - view tidak pernah jadi tempat memutuskan siapa boleh melihat apa.
 */
$kotak = 'rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card';
$label = 'text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted';
$nilai = 'mt-1 text-sm font-semibold text-gray-900 dark:text-white';
$isian = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600'
    . ' file:mr-3 file:rounded-lg file:border-0 file:bg-brand-primary/10 file:px-3 file:py-1.5'
    . ' file:text-xs file:font-bold file:text-brand-primary dark:border-white/10 dark:bg-black/20 dark:text-brand-muted';
$petunjuk = 'mt-1 text-xs text-gray-500 dark:text-brand-muted';

$badge_kelas = ['Diajukan' => 'pending', 'Ditinjau Bidang' => 'process',
                'Diterima' => 'ok', 'Ditolak' => 'reject', 'Dibatalkan' => 'reject'];

$baris = [
    'Nama Universitas' => $row->instansi_asal,
    'Keterangan'        => $row->divisi_atau_tema,
    'Periode'           => $row->periode_mulai && $row->periode_selesai
        ? tgl_id($row->periode_mulai, TRUE) . ' - ' . tgl_id($row->periode_selesai, TRUE) : NULL,
    'Nomor HP'          => $row->no_hp,
];

$ditolak_di_bidang = $row->status === 'Ditolak' && (
    ! empty($row->reviewed_at_bidang) || ! empty($row->reviewed_by_bidang) || ! empty($row->catatan_bidang)
);
$urut  = ['Diajukan' => 1, 'Ditinjau Bidang' => 2, 'Diterima' => 3];
$capai = $urut[$row->status] ?? ($ditolak_di_bidang ? 2 : 1);
$tahap = [
    ['judul' => 'Berkas Masuk', 'ket' => 'Kedua surat diterima sistem'],
    ['judul' => 'Ditinjau Admin Disperakim', 'ket' => 'Sekretariat memeriksa dan meneruskan'],
    ['judul' => 'Ditinjau Admin Bidang',     'ket' => 'Bidang penanggung jawab memutuskan'],
    ['judul' => 'Surat Balasan',             'ket' => 'Surat resmi siap diunduh'],
];
$berhenti = in_array($row->status, ['Ditolak', 'Dibatalkan'], TRUE);
?>
<div class="relative z-10 max-w-3xl">

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted">Kemitraan</span>
            <h1 class="mt-1 text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Detail KKN</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <?= $this->load->view('admin/components/status_badge', ['label' => $row->status, 'kelas' => $badge_kelas[$row->status] ?? 'pending'], TRUE) ?>
                <span class="text-xs text-gray-500 dark:text-brand-muted">Diajukan <?= html_escape(tgl_id($row->created_at)) ?></span>
            </div>
        </div>
        <a href="<?= base_url('KemitraanPortal/kkn_dashboard') ?>"
           class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-xs font-bold text-gray-700 dark:text-gray-300">Kembali ke Dashboard</a>
    </div>

    <div class="<?= $kotak ?> mb-4">
        <div class="<?= $label ?> mb-4">Perjalanan Berkas</div>
        <ol class="space-y-0">
            <?php foreach ($tahap as $i => $t): ?>
                <?php
                $nomor  = $i + 1;
                $selesai = $nomor === 4 ? ! empty($row->file_surat_balasan) : $nomor <= $capai;
                $sedang  = ! $berhenti && ! $selesai && $nomor === $capai + 1;
                $mati    = $berhenti && ! $selesai;
                ?>
                <li class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black
                            <?= $selesai ? 'bg-green-500 text-white' : ($sedang ? 'bg-brand-primary text-white' : 'bg-gray-100 dark:bg-white/5 text-gray-400 dark:text-brand-muted') ?>">
                            <?= $selesai ? '✓' : $nomor ?>
                        </span>
                        <?php if ($nomor < 4): ?>
                            <span class="my-1 w-px flex-1 <?= $selesai ? 'bg-green-500' : 'bg-gray-200 dark:bg-white/10' ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="pb-5">
                        <div class="text-sm font-bold <?= $mati ? 'text-gray-400 dark:text-brand-muted/60' : 'text-gray-900 dark:text-white' ?>"><?= html_escape($t['judul']) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($t['ket']) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <?php if ($berhenti): ?>
            <p class="mt-1 rounded-xl bg-red-50 dark:bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-600 dark:text-red-400">
                Berkas berhenti di tahap <?= $ditolak_di_bidang ? 'tinjauan bidang' : ($row->status === 'Dibatalkan' ? 'ini - Anda membatalkannya sendiri' : 'tinjauan sekretariat') ?>.
            </p>
        <?php elseif ( ! empty($row->file_surat_balasan)): ?>
            <a href="<?= base_url('KemitraanPortal/unduh_balasan/' . (int) $row->id) ?>" target="_blank" rel="noopener"
               class="mt-1 inline-flex items-center gap-2 rounded-xl bg-brand-primary px-4 py-2.5 text-xs font-bold text-white">
                <i class="ph ph-file-arrow-down" aria-hidden="true"></i> Unduh Surat Balasan
            </a>
        <?php elseif ($row->status === 'Diterima'): ?>
            <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
                KKN ini diterima. Surat balasan resmi sedang disiapkan sekretariat - halaman ini akan menampilkan tombol unduh begitu suratnya terbit.
            </p>
        <?php endif; ?>
    </div>

    <?php if ( ! empty($row->catatan_bidang)): ?>
        <div class="<?= $kotak ?> mb-4 border-l-4 border-l-amber-500">
            <div class="<?= $label ?>">Catatan Bidang</div>
            <p class="mt-2 text-sm leading-relaxed text-gray-900 dark:text-white"><?= nl2br(html_escape($row->catatan_bidang)) ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($row->catatan_admin)): ?>
        <div class="<?= $kotak ?> mb-4 border-l-4 border-l-amber-500">
            <div class="<?= $label ?>">Catatan Admin</div>
            <p class="mt-2 text-sm leading-relaxed text-gray-900 dark:text-white"><?= nl2br(html_escape($row->catatan_admin)) ?></p>
        </div>
    <?php endif; ?>

    <div class="<?= $kotak ?> mb-4">
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?php foreach ($baris as $judul => $isi): ?>
                <div>
                    <dt class="<?= $label ?>"><?= html_escape($judul) ?></dt>
                    <dd class="<?= $nilai ?>"><?= $isi ? html_escape($isi) : '<span class="font-normal text-gray-400 dark:text-brand-muted/60">Belum diisi</span>' ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="mt-6 border-t border-gray-200 dark:border-white/10 pt-5">
            <div class="<?= $label ?>">Berkas</div>
            <ul class="mt-2 space-y-1.5">
                <?php
                $berkas_status = [
                    'Surat permohonan menjadi mitra' => $row->file_surat_pengantar,
                    'Surat permohonan akun SIMPERUM' => $row->file_surat_simperum,
                ];
                ?>
                <?php foreach ($berkas_status as $nama_berkas => $tersimpan): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <?php if ( ! empty($tersimpan)): ?>
                            <i class="ph ph-check-circle text-green-500" aria-hidden="true"></i>
                            <span class="text-gray-900 dark:text-white"><?= html_escape($nama_berkas) ?> sudah terunggah</span>
                        <?php else: ?>
                            <i class="ph ph-circle text-gray-400 dark:text-brand-muted/60" aria-hidden="true"></i>
                            <span class="text-gray-500 dark:text-brand-muted"><?= html_escape($nama_berkas) ?> belum ada</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-2 text-[11px] text-gray-500 dark:text-brand-muted">
                Berkas hanya bisa disusulkan lewat admin. Hubungi pengelola bila ada yang keliru.
            </p>
        </div>
    </div>

    <!-- Roster peserta - permintaan user 21 Agt 2026. Diisi dari unggahan
         Excel, bukan diketik satu-satu (lihat KemitraanPortal::kkn_upload_peserta()
         dan Kkn_peserta_import). Mengunggah ulang MENGGANTI seluruh roster,
         bukan menambah. -->
    <div class="<?= $kotak ?> mb-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="<?= $label ?>">Roster Peserta</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted"><?= count($peserta) ?> peserta tercatat.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('KemitraanPortal/kkn_upload_peserta/' . (int) $row->id) ?>"
              enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <div class="flex-1 min-w-[220px]">
                <label for="kb-peserta" class="<?= $label ?>">Unggah Daftar Peserta (XLS/XLSX)</label>
                <input id="kb-peserta" name="file_peserta" type="file" accept=".xls,.xlsx" required class="<?= $isian ?>">
                <p class="<?= $petunjuk ?>">Baris pertama berisi judul kolom "NIM" dan "Nama". Mengunggah ulang MENGGANTI seluruh roster yang tersimpan. Maksimal 5 MB.</p>
            </div>
            <button type="submit" class="rounded-xl bg-brand-primary px-5 py-2.5 text-xs font-bold text-white shrink-0">
                <i class="ph ph-upload-simple"></i> Unggah
            </button>
        </form>

        <?php if ($peserta): ?>
        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full min-w-[360px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/20 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">
                        <th class="px-4 py-2.5">NIM</th>
                        <th class="px-4 py-2.5">Nama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    <?php foreach ($peserta as $p): ?>
                        <tr>
                            <td class="px-4 py-2.5 text-gray-900 dark:text-white"><?= html_escape($p->nim) ?></td>
                            <td class="px-4 py-2.5 text-gray-900 dark:text-white"><?= html_escape($p->nama) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($bisa_batal): ?>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <form method="POST" action="<?= base_url('KemitraanPortal/batal/' . (int) $row->id) ?>"
                  onsubmit="return confirm('Batalkan KKN ini? Anda perlu mengajukan ulang bila berubah pikiran.')">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <button type="submit" class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline">Batalkan KKN ini</button>
            </form>
        </div>
    <?php else: ?>
        <p class="mt-4 text-xs text-gray-500 dark:text-brand-muted">
            KKN yang sudah <?= strtolower(html_escape($row->status)) ?> tidak bisa dibatalkan lagi.
            Hubungi pengelola bila ada data yang keliru.
        </p>
    <?php endif; ?>
</div>
