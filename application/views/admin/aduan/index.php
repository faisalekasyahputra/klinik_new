<?php
// Peta status domain aduan -> kelas komponen bersama.
$badge_kelas = ['Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok'];
// Dimuat DI SINI, bukan di dekat toolbar seperti dulu: callout triase di bawah
// memakai admin_table_url() dan berada di atasnya.
$this->load->helper('admin_table');
$nama_bidang = array_column($daftar_bidang, 'nama', 'kode');
?>
<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Semua Aduan</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Pantauan lintas bidang, dan tempat aduan baru dirutekan ke bidang penanganan. Keputusan status dan jawabannya tetap di tangan admin bidang masing-masing.</p>
</div>

<?php if (!empty($jml_triase)): ?>
<div class="mb-6 p-4 rounded-2xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-800 dark:text-blue-400 text-sm flex items-start gap-3">
    <i class="ph ph-arrows-split text-lg mt-0.5"></i>
    <div>
        <strong><?= (int) $jml_triase ?> aduan menunggu diteruskan.</strong>
        Pelapor tidak lagi memilih bidang sendiri — selama belum diteruskan, aduan ini tidak muncul di dashboard bidang mana pun.
        <a href="<?= admin_table_url($base_url, ['bidang' => 'belum']) ?>" class="underline font-semibold">Tampilkan saja yang belum diteruskan</a>.
    </div>
</div>
<?php endif; ?>

<?php if (!empty($bidang_tanpa_admin)): ?>
<div class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 text-orange-800 dark:text-orange-400 text-sm flex items-start gap-3">
    <i class="ph ph-warning-circle text-lg mt-0.5"></i>
    <div>
        <strong>Bidang tanpa admin ter-assign:</strong> <?= html_escape(implode(', ', $bidang_tanpa_admin)) ?>.
        Aduan yang masuk ke bidang ini tidak akan muncul di dashboard siapa pun — tetapkan admin lewat <a href="<?= base_url('Admin_Users') ?>" class="underline font-semibold">Manajemen Pengguna</a>.
    </div>
</div>
<?php endif; ?>

<?php
// Filter dibangun lewat admin_table_url() supaya pencarian/urutan yang sedang
// aktif tidak hilang saat ganti filter (dan sebaliknya).
ob_start(); ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Status:</span>
<a href="<?= admin_table_url($base_url, ['status' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($status_filter) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<?php foreach ($status_sah as $status): ?>
<a href="<?= admin_table_url($base_url, ['status' => $status]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $status_filter === $status ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= html_escape($status) ?></a>
<?php endforeach; ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Bidang:</span>
<a href="<?= admin_table_url($base_url, ['bidang' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($bidang_filter) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<a href="<?= admin_table_url($base_url, ['bidang' => 'belum']) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $bidang_filter === 'belum' ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Belum diteruskan</a>
<?php foreach ($daftar_bidang as $b): ?>
<a href="<?= admin_table_url($base_url, ['bidang' => $b->kode]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $bidang_filter === $b->kode ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= html_escape($b->nama) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari pelapor, judul, isi...', 'filter_html' => $filter_html], TRUE) ?>

    <?php
    /**
     * px-4, bukan px-6 seperti tabel admin lainnya. Diukur di 1440px (§17 poin
     * 6): kolom Bidang yang kini memuat <select> + tombol menambah 173px, dan
     * tabel ini sudah kelebihan 24px SEBELUM perubahan itu — jadi kolom paling
     * kanan ("Diproses Oleh") sudah terpotong sejak lama tanpa ada yang
     * melihatnya. Enam kolom x 2 sisi x 8px = 96px yang dihemat di sini adalah
     * yang membuat totalnya kembali muat, bersama tanggal berbahasa Indonesia
     * yang kebetulan juga lebih pendek.
     */
    ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4"><?= admin_sort_header('Tanggal', 'aduan.created_at', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Pelapor', 'aduan.nama', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Judul', 'aduan.judul', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Bidang', 'aduan.bidang', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Status', 'aduan.status', $table, $base_url) ?></th>
                    <th class="px-4 py-4">Diproses Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada aduan.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                    <?php // Bulan berbahasa Indonesia — "Aug"/"May" tidak dipakai di layar mana pun lagi. ?>
                    <td class="px-4 py-4 text-xs"><?= html_escape(tgl_id($r->created_at, TRUE) . ' ' . date('H:i', strtotime($r->created_at))) ?></td>
                    <td class="px-4 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email) ?></div>
                    </td>
                    <td class="px-4 py-4 max-w-[280px]">
                        <div class="font-semibold text-gray-900 dark:text-white truncate"><?= html_escape($r->judul) ?></div>
                        <?php if (!empty($r->catatan_admin)): ?>
                        <div class="text-xs text-gray-500 dark:text-brand-muted mt-0.5 truncate">Catatan: <?= html_escape($r->catatan_admin) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r->lampiran)): ?>
                        <a href="<?= base_url('Admin_Aduan/lihat_lampiran/' . $r->id) ?>" target="_blank" rel="noopener" class="inline-block mt-0.5 text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> Lampiran</a>
                        <?php endif; ?>
                    </td>
                    <?php
                    /**
                     * Kontrol rute muncul selama status masih 'Baru' — bukan hanya
                     * saat bidangnya NULL. Salah rute paling sering ketahuan
                     * SETELAH diteruskan, dan gerbang servernya (Admin_Aduan::triase)
                     * memang mengizinkan perbaikan selama belum ada yang memproses.
                     * Kalau di sini hanya baris NULL yang diberi kontrol, salah rute
                     * cuma bisa diperbaiki lewat DB.
                     */
                    ?>
                    <td class="px-4 py-4 text-xs">
                        <?php if ($r->status === 'Baru'): ?>
                        <form method="POST" action="<?= base_url('Admin_Aduan/triase/' . $r->id) ?>" class="flex items-center gap-1.5">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <?php // max-w: nama bidang panjang; daftar lengkapnya tetap utuh saat dropdown dibuka. ?>
                            <select name="bidang" required class="max-w-[110px] rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-2 py-1 text-xs text-gray-700 dark:text-gray-200">
                                <option value=""><?= $r->bidang ? '— ubah —' : '— pilih bidang —' ?></option>
                                <?php foreach ($daftar_bidang as $b): ?>
                                <option value="<?= html_escape($b->kode) ?>" <?= $r->bidang === $b->kode ? 'selected' : '' ?>><?= html_escape($b->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" title="Teruskan ke bidang" class="rounded-lg bg-brand-primary/20 border border-brand-primary/50 text-brand-primary px-2 py-1 font-bold hover:bg-brand-primary/30 transition-colors"><i class="ph ph-paper-plane-right"></i></button>
                        </form>
                        <?php elseif ($r->bidang): ?>
                        <span class="font-bold"><?= html_escape($nama_bidang[$r->bidang] ?? $r->bidang) ?></span>
                        <?php else: ?>
                        <span class="text-gray-400 dark:text-brand-muted/60 italic">Tidak dirutekan</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4"><?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?></td>
                    <td class="px-4 py-4 text-xs">
                        <?php if (!empty($r->nama_petugas)): ?>
                            <span class="text-gray-700 dark:text-gray-300"><?= html_escape($r->nama_petugas) ?></span>
                            <div class="text-[10px] text-gray-400 dark:text-brand-muted/70"><?= $r->reviewed_at ? html_escape(tgl_id($r->reviewed_at, TRUE)) : '' ?></div>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-brand-muted/60">Belum diproses</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
