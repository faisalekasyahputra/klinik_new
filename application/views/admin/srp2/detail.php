<?php
// Admin_Srp2::detail() memang sudah memuatnya sebelum merender view ini,
// tapi disebut lagi di sini supaya view tidak diam-diam bergantung pada
// pemanggilnya (load->helper() idempoten, tidak memuat dua kali).
$this->load->helper('srp2');
$status_kelas = ['Draft' => 'process', 'Pending' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject'][$pendaftar->status_verifikasi] ?? 'pending';
// Validasi skema URL sendiri, jangan bergantung pada global_xss_filtering
// (DEPRECATED). Pola sama dengan pages/pengembang/profil.php:2-4. Bukan
// perbaikan bug -- klaim Stored XSS javascript: di sini sudah DIBANTAH lewat
// pengujian (roadmap T6) -- ini pengerasan agar tidak lagi bergantung satu
// setelan global untuk sesuatu yang bisa divalidasi lokal.
$safe_url = function ($url) {
    return ($url && preg_match('#^https?://#i', $url)) ? $url : null;
};
?>
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="<?= base_url('Admin_Srp2/pending') ?>" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-white">← Kembali ke daftar menunggu</a>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mt-1"><?= html_escape($pendaftar->nama_perusahaan ?: '-') ?></h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted"><?= html_escape($pendaftar->email ?: '-') ?></p>
    </div>
    <?= $this->load->view('admin/components/status_badge', ['label' => $pendaftar->status_verifikasi, 'kelas' => $status_kelas], TRUE) ?>
</div>

<?php if (!empty($pendaftar->catatan_admin)): ?>
<div class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-sm">
    <strong>Catatan admin sebelumnya:</strong> <?= html_escape($pendaftar->catatan_admin) ?>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/5 p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Dokumen Persyaratan (<?= count($uploaded) ?>/<?= count($dokumen_list) ?>)</h3>
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($dokumen_list as $key => $label): $doc = $uploaded[$key] ?? NULL; ?>
            <div class="flex items-center justify-between py-3 gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300"><?= html_escape($label) ?></span>
                <?php if ($doc): ?>
                <a href="<?= base_url('Admin_Srp2/lihat_dokumen/' . $pendaftar->id . '/' . $key) ?>" data-file-view data-file-title="<?= html_escape($label) ?>" target="_blank" rel="noopener" class="shrink-0 text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">Lihat berkas</a>
                <?php else: ?>
                <span class="shrink-0 text-xs text-gray-400 dark:text-brand-muted/60">Belum diunggah</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/5 p-6 space-y-4">
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-1">Alamat Kantor</h4>
            <p class="text-sm text-gray-700 dark:text-gray-300"><?= html_escape($pendaftar->alamat_kantor ?: '-') ?></p>
        </div>

        <?php
        // Data pembanding untuk pengambil keputusan. Kolom-kolom ini SUDAH ADA di
        // baris pengajuan tapi tidak pernah dirender, sehingga admin diminta
        // memutuskan sertifikasi hanya berbekal nama perusahaan dan 14 berkas -
        // tanpa satu pun nilai yang bisa dicocokkan dengan isi berkasnya.
        // Roadmap T1b butir 2.
        //
        // Alur pendaftaran saat ini (daftar cepat) memang belum mengisi kolom
        // identitas; yang kosong ditandai apa adanya, bukan disembunyikan -
        // supaya terlihat bahwa datanya memang belum dikumpulkan.
        $pembanding = [
            'NIK Pemohon'    => $pendaftar->nik_ktp ?? '',
            'Nama Pemohon'   => $pendaftar->nama_peserta ?? '',
            'Jabatan'        => $pendaftar->jabatan ?? '',
            'NIB'            => $pendaftar->nib ?? '',
            // Label, bukan kode mentah (`rei` -> `REI`). Fallback SENGAJA string
            // kosong, bukan '-' bawaan helper: $terisi di bawah menghitung nilai
            // yang tidak kosong, dan '-' akan terhitung sebagai "sudah diisi".
            'Asosiasi'       => srp2_label_asosiasi($pendaftar->asosiasi ?? '', ''),
            'No. Keanggotaan'=> $pendaftar->no_keanggotaan ?? '',
            'WhatsApp'       => $pendaftar->no_whatsapp ?? '',
        ];
        $terisi = count(array_filter($pembanding, function ($v) { return trim((string) $v) !== ''; }));
        ?>
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-2">
                Data Pemohon
                <span class="ml-1 font-semibold normal-case tracking-normal text-gray-400 dark:text-brand-muted/60">(<?= $terisi ?>/<?= count($pembanding) ?> terisi)</span>
            </h4>
            <dl class="space-y-1.5">
                <?php foreach ($pembanding as $label => $nilai): $kosong = trim((string) $nilai) === ''; ?>
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="shrink-0 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($label) ?></dt>
                    <dd class="text-right text-sm <?= $kosong ? 'italic text-gray-400 dark:text-brand-muted/50' : 'font-semibold text-gray-800 dark:text-gray-200' ?>"><?= $kosong ? 'belum diisi' : html_escape($nilai) ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
            <?php if ($terisi === 0): ?>
            <p class="mt-2 rounded-lg px-2.5 py-2 text-[11px] leading-relaxed text-amber-700 bg-amber-50 dark:bg-amber-500/10 dark:text-amber-400">
                Tidak ada satu pun data identitas terisi - alur pendaftaran cepat belum mengumpulkannya. Verifikasi sepenuhnya bersandar pada isi 14 berkas.
            </p>
            <?php endif; ?>
        </div>
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-1">Tautan</h4>
            <p class="text-xs text-gray-500 dark:text-brand-muted space-x-2">
                <?php foreach (['website' => 'Website', 'instagram' => 'Instagram', 'sosmed_lainnya' => 'Lainnya'] as $field => $label): $link = $safe_url($pendaftar->$field ?? null); ?>
                    <?php if ($link): ?>
                    <a href="<?= html_escape($link) ?>" target="_blank" rel="noopener" class="text-blue-600 dark:text-brand-primary hover:underline"><?= $label ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </p>
        </div>

        <?php if ($pendaftar->status_verifikasi === 'Pending'): ?>
        <div class="pt-4 border-t border-gray-100 dark:border-white/5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-3">Keputusan</h4>
            <?php // "Minta Perbaikan" (value Draft) membuka kembali pengajuan tanpa
                  // mencap "Ditolak" di riwayat pemohon. Catatan wajib untuk Tolak
                  // maupun Minta Perbaikan - divalidasi di server. ?>
            <?= $this->load->view('admin/components/review_form', [
                'action_url' => 'Admin_Srp2/proses/' . $pendaftar->id,
                'buttons' => [
                    ['value' => 'Diterima', 'label' => 'Terima', 'style' => 'accept'],
                    ['value' => 'Draft', 'label' => 'Minta Perbaikan', 'style' => 'neutral'],
                    ['value' => 'Ditolak', 'label' => 'Tolak', 'style' => 'reject'],
                ],
                'catatan_name' => 'catatan_admin',
                'catatan_placeholder' => 'Catatan (wajib untuk Tolak & Minta Perbaikan)',
            ], TRUE) ?>
        </div>
        <?php elseif ($pendaftar->status_verifikasi === 'Diterima'): ?>
        <div class="pt-4 border-t border-gray-100 dark:border-white/5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-2">Buka Kembali</h4>
            <p class="text-xs text-gray-500 dark:text-brand-muted mb-3">Pengajuan ini sudah disetujui dan dokumennya terkunci. Kalau ada data yang harus diperbaiki (mis. alamat kantor pindah atau pengurus berganti), buka kembali supaya pengembang bisa memperbarui dokumennya lalu mengirim ulang.</p>
            <p class="text-[11px] text-gray-400 dark:text-brand-muted/70 mb-3">Pengembang tetap tercantum di direktori publik selama diperbaiki. Kalau perlu dicabut dari daftar, nonaktifkan lewat halaman <a href="<?= base_url('Admin_Srp2') ?>" class="underline">Direktori SRP2</a>.</p>
            <?= $this->load->view('admin/components/review_form', [
                'action_url' => 'Admin_Srp2/proses/' . $pendaftar->id,
                'buttons' => [
                    ['value' => 'Draft', 'label' => 'Buka untuk Diperbaiki', 'style' => 'neutral'],
                ],
                'catatan_name' => 'catatan_admin',
                'catatan_placeholder' => 'Wajib: jelaskan apa yang harus diperbaiki',
            ], TRUE) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
