<?php
$this->load->helper('admin_table');
// Keadaan domain -> kelas komponen bersama. 'dibatalkan' sengaja sekelas
// dengan 'ditolak': dua-duanya berarti pertemuannya tidak jadi, dan yang
// membedakan (siapa yang membatalkan) sudah terbaca dari labelnya.
$badge_kelas = [
    'diajukan' => 'pending', 'ditawarkan' => 'process',
    'disetujui' => 'ok', 'selesai' => 'ok',
    'ditolak' => 'reject', 'dibatalkan' => 'reject',
];
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>
<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Janji Temu Konsultasi</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Permintaan tatap muka dari topik Konsultasi Terjadwal. Warga hanya bisa mengajukan setelah topiknya ditanggapi di forum.</p>
</div>

<?php if (!empty($jml_baru)): ?>
<div class="mb-6 p-4 rounded-2xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-800 dark:text-blue-400 text-sm flex items-start gap-3">
    <i class="ph ph-calendar-plus text-lg mt-0.5"></i>
    <div>
        <strong><?= (int) $jml_baru ?> pengajuan menunggu ditawarkan jadwal.</strong>
        Selama belum ditawarkan, warga melihat "Menunggu ditinjau petugas" tanpa perkiraan waktu.
        <a href="<?= admin_table_url($base_url, ['status' => 'diajukan']) ?>" class="underline font-semibold">Tampilkan yang menunggu</a>.
    </div>
</div>
<?php endif; ?>

<?php
ob_start(); ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Status:</span>
<a href="<?= admin_table_url($base_url, ['status' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($status_filter) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<?php foreach ($status_sah as $s): ?>
<a href="<?= admin_table_url($base_url, ['status' => $s]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $status_filter === $s ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= html_escape(ucfirst($s)) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>

<?php
/**
 * Satu modal untuk SELURUH tabel, bukan satu per baris. Formulir "tawarkan"
 * memuat tanggal + lokasi + catatan; menaruhnya di dalam sel akan mendorong
 * tabel jauh melewati wadahnya, dan yang pertama hilang di balik gulir selalu
 * kolom Aksi (AGENTS.md §17 poin 6). Selnya cuma memuat tombol kecil.
 */
?>
<div data-tabel-admin x-data="{ buka: false, id: null, judul: '', mode: 'tawarkan' }"
     class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari topik, pemohon, alasan...', 'filter_html' => $filter_html], TRUE) ?>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4"><?= admin_sort_header('Diajukan', 'jt.created_at', $table, $base_url) ?></th>
                    <th class="px-4 py-4">Pemohon</th>
                    <th class="px-4 py-4"><?= admin_sort_header('Topik', 'd.judul_topik', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Status', 'jt.status', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Jadwal', 'jt.jadwal_mulai', $table, $base_url) ?></th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pengajuan janji temu.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="px-4 py-4 text-xs"><?= html_escape(tgl_id($r->created_at, TRUE)) ?></td>
                    <td class="px-4 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_pemohon ?: '-') ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email_pemohon) ?></div>
                    </td>
                    <td class="px-4 py-4 max-w-[260px]">
                        <a href="<?= base_url('Umum/detail/' . $r->id_diskusi) ?>" target="_blank" rel="noopener" class="font-semibold text-gray-900 dark:text-white hover:underline block truncate"><?= html_escape($r->judul_topik ?: '(topik terhapus)') ?></a>
                        <div class="text-xs text-gray-500 dark:text-brand-muted truncate"><?= html_escape($r->alasan) ?></div>
                    </td>
                    <td class="px-4 py-4"><?= $this->load->view('admin/components/status_badge', ['label' => ucfirst($r->status), 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?></td>
                    <td class="px-4 py-4 text-xs">
                        <?php if ($r->jadwal_mulai): ?>
                            <div class="font-bold"><?= html_escape(tgl_id($r->jadwal_mulai, TRUE)) ?> <?= html_escape(date('H:i', strtotime($r->jadwal_mulai))) ?></div>
                            <div class="text-[10px] text-gray-400 dark:text-brand-muted/70 truncate max-w-[140px]"><?= html_escape($r->lokasi) ?></div>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-brand-muted/60">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-xs">
                        <?php
                        // Tombol dibangun dari ALUR model. Tombol yang ada di layar
                        // tapi ditolak server adalah cara tercepat membuat petugas
                        // mengira sistemnya rusak.
                        $bisa_tawar   = $this->Janji_temu_model->boleh($r->status, 'ditawarkan', 'admin');
                        $bisa_tolak   = $this->Janji_temu_model->boleh($r->status, 'ditolak', 'admin');
                        $bisa_selesai = $this->Janji_temu_model->boleh($r->status, 'selesai', 'admin');
                        $judul_js = htmlspecialchars(json_encode($r->judul_topik ?: '(topik terhapus)'), ENT_QUOTES);
                        ?>
                        <div class="flex items-center gap-1.5">
                            <?php if ($bisa_tawar): ?>
                            <button type="button" @click="id = <?= (int) $r->id ?>; judul = <?= $judul_js ?>; mode = 'tawarkan'; buka = true"
                                    class="rounded-lg bg-brand-primary/20 border border-brand-primary/50 text-brand-primary px-2 py-1 font-bold hover:bg-brand-primary/30 transition-colors">
                                <i class="ph ph-calendar-plus"></i> Tawarkan
                            </button>
                            <?php endif; ?>
                            <?php if ($bisa_tolak): ?>
                            <?php // Tombol ikon-saja: butuh nama yang terbaca pembaca layar, bukan cuma glyph. ?>
                            <button type="button" title="Tolak pengajuan" aria-label="Tolak pengajuan"
                                    @click="id = <?= (int) $r->id ?>; judul = <?= $judul_js ?>; mode = 'tolak'; buka = true"
                                    class="rounded-lg bg-red-500/10 border border-red-500/20 text-red-500 dark:text-red-400 px-2 py-1 font-bold hover:bg-red-500/20 transition-colors">
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($bisa_selesai): ?>
                            <form method="POST" action="<?= base_url('Admin_Konsultasi/selesai/' . $r->id) ?>" class="inline">
                                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                                <button type="submit" class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 px-2 py-1 font-bold hover:bg-emerald-500/20 transition-colors">
                                    <i class="ph ph-check"></i> Selesai
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ( ! $bisa_tawar && ! $bisa_tolak && ! $bisa_selesai): ?>
                            <span class="text-gray-400 dark:text-brand-muted/60">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>

    <!-- Modal tunggal: tawarkan / tolak -->
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="buka = false" class="w-full max-w-md rounded-2xl bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 dark:border-white/10 pb-3">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white" x-text="mode === 'tawarkan' ? 'Tawarkan Jadwal' : 'Tolak Pengajuan'"></h3>
                    <p class="text-xs text-gray-500 dark:text-brand-muted mt-0.5" x-text="judul"></p>
                </div>
                <button type="button" @click="buka = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="ph ph-x text-lg"></i></button>
            </div>

            <form method="POST" :action="'<?= base_url('Admin_Konsultasi/') ?>' + mode + '/' + id" class="mt-4 space-y-3 text-xs">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">

                <template x-if="mode === 'tawarkan'">
                    <div class="space-y-3">
                        <?php
                        /**
                         * `datetime-local` bawaan browser, bukan pustaka pemilih
                         * tanggal. Ia sudah menangani zona waktu lokal, keyboard,
                         * dan pembaca layar; server tetap memeriksa ulang bahwa
                         * waktunya di masa depan, karena atribut `min` di sini
                         * cuma petunjuk dan bisa dilepas siapa saja.
                         */
                        ?>
                        <label class="block">
                            <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300">Waktu konsultasi</span>
                            <input type="datetime-local" name="jadwal_mulai" required min="<?= date('Y-m-d\TH:i') ?>"
                                   class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 py-2 text-gray-800 dark:text-gray-200">
                        </label>
                        <label class="block">
                            <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300">Lokasi</span>
                            <input type="text" name="lokasi" required maxlength="160" placeholder="Contoh: Ruang Rapat Disperakim Lt. 2"
                                   class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 py-2 text-gray-800 dark:text-gray-200">
                        </label>
                    </div>
                </template>

                <label class="block">
                    <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300"
                          x-text="mode === 'tolak' ? 'Alasan penolakan (wajib)' : 'Catatan untuk pemohon (opsional)'"></span>
                    <textarea name="catatan_admin" rows="3" :required="mode === 'tolak'"
                              class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 py-2 text-gray-800 dark:text-gray-200"></textarea>
                </label>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="buka = false" class="rounded-lg border border-gray-200 dark:border-white/10 px-3 py-2 font-bold text-gray-600 dark:text-brand-muted">Batal</button>
                    <button type="submit" class="rounded-lg bg-brand-primary/20 border border-brand-primary/50 text-brand-primary px-4 py-2 font-bold hover:bg-brand-primary/30"
                            x-text="mode === 'tawarkan' ? 'Kirim Tawaran' : 'Tolak'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
