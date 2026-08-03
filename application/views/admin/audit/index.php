<div class="mb-6 relative z-10">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Jejak Audit</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted max-w-2xl">
        Rekaman tindakan yang mengubah akses dan data pengelolaan — siapa melakukannya, kapan, dan
        terhadap apa. Termasuk percobaan yang <span class="font-bold">ditolak</span> sistem. Layar ini
        hanya bisa dibaca; barisnya tidak bisa diubah atau dihapus dari sini.
    </p>
</div>

<?php
$this->load->helper('admin_table');
// Filter dibangun lewat admin_table_url() supaya pencarian dan urutan yang
// sedang aktif tidak hilang saat ganti filter, dan sebaliknya.
$pil = 'px-3 py-1 rounded-lg text-xs font-bold border transition-colors';
$nyala = 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary';
$padam = 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10';

// Aksi yang berakhiran `_ditolak` adalah percobaan yang DIBLOKIR — dibedakan
// warnanya di pil filter maupun di baris, karena justru itu yang orang cari
// saat membuka layar ini. Dipakai dua kali, jadi didefinisikan sekali.
$ditolak = fn($a) => str_ends_with((string) $a, '_ditolak');

ob_start(); ?>
<span class="mr-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Aksi:</span>
<a href="<?= admin_table_url($base_url, ['aksi' => NULL]) ?>" class="<?= $pil ?> <?= empty($f_aksi) ? $nyala : $padam ?>">Semua</a>
<?php foreach ($aksi_tersedia as $a): ?>
    <a href="<?= admin_table_url($base_url, ['aksi' => $a]) ?>"
       class="<?= $pil ?> <?= $f_aksi === $a ? $nyala : ($ditolak($a) ? 'border-red-200 text-red-600 hover:bg-red-50 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10' : $padam) ?>"><?= html_escape($a) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden relative z-10">
    <div class="p-6 border-b border-gray-200 dark:border-white/5">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-scroll text-brand-primary"></i> Riwayat Tindakan (<?= number_format((int) $table['total_rows']) ?>)
        </h3>
    </div>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari ringkasan, email pelaku, atau aksi...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Waktu', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Pelaku', 'actor_email', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Aksi', 'aksi', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4">Ringkasan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($jejak)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-scroll"></i>
                            </div>
                            <?php // Dua sebab layar kosong, dua kalimat berbeda: tabel yang
                                  // memang masih kosong vs saringan yang tidak menemukan apa
                                  // pun. Satu kalimat untuk keduanya membuat orang mengira
                                  // jejaknya hilang padahal cuma tersaring. ?>
                            <?php if ($table['q'] !== '' || ! empty($f_aksi)): ?>
                            <p>Tidak ada jejak yang cocok dengan pencarian atau filter ini.</p>
                            <a href="<?= base_url($base_url) ?>" class="mt-3 text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">Tampilkan semua jejak</a>
                            <?php else: ?>
                            <p>Belum ada jejak tercatat.</p>
                            <p class="mt-1 text-xs">Baris akan muncul sendiri begitu ada tindakan pengelolaan — mengubah role, menonaktifkan akun, mereset sandi.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($jejak as $j): ?>
                <tr>
                    <td class="px-4 py-4 text-xs">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape(tgl_id($j->created_at, TRUE)) ?></div>
                        <div class="text-gray-500 dark:text-brand-muted"><?= html_escape($j->created_at ? date('H:i', strtotime($j->created_at)) : '-') ?></div>
                    </td>
                    <td class="px-4 py-4 text-xs">
                        <?php if ( ! empty($j->actor_email)): ?>
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($j->actor_email) ?></div>
                        <div class="text-gray-500 dark:text-brand-muted"><?= html_escape($j->actor_role ?: '-') ?></div>
                        <?php // actor_id kosong sementara emailnya ada = akunnya sudah dihapus
                              // (FK ON DELETE SET NULL) dan salinan email inilah yang menyelamatkan
                              // "siapa"-nya. Ditandai apa adanya, bukan disembunyikan — akun yang
                              // sudah tidak ada justru yang paling sering perlu ditelusuri. ?>
                        <?php if ($j->actor_id === NULL): ?>
                        <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">akun sudah dihapus</div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-gray-400 dark:text-brand-muted/60 italic">(akun terhapus)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold <?= $ditolak($j->aksi)
                            ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                            : 'bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary' ?>"><?= html_escape($j->aksi) ?></span>
                    </td>
                    <?php // Kolom teks terpanjang (ringkasan sampai 255 karakter). Tabel ini
                          // memakai `whitespace-nowrap`; tanpa max-w + whitespace-normal satu
                          // ringkasan panjang cukup untuk mendorong tabel melewati wadahnya. ?>
                    <td class="px-4 py-4 max-w-[24rem] whitespace-normal">
                        <?= html_escape($j->ringkasan) ?>
                        <?php
                        // detail_json ditaruh di <details> tertutup, bukan dirender langsung:
                        // isinya blob "dari/ke" yang membanjiri layar dan membuat kolom lain
                        // tidak terbaca, padahal yang dibutuhkan 95% waktu cuma ringkasannya.
                        // json_decode dulu supaya bisa dicetak rapi; JSON yang tidak bisa
                        // dibaca tetap ditampilkan mentah, karena menyembunyikannya berarti
                        // menghapus bukti hanya sebab bentuknya rusak.
                        $detail = $j->detail_json !== NULL && $j->detail_json !== ''
                            ? json_decode($j->detail_json, TRUE) : NULL;
                        $rapi = is_array($detail)
                            ? json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                            : $j->detail_json;
                        $konteks = array_filter([
                            $j->objek_tipe ? $j->objek_tipe . '#' . $j->objek_id : NULL,
                            $j->ip ? 'IP ' . $j->ip : NULL,
                        ]);
                        ?>
                        <?php if ($konteks): ?>
                        <div class="mt-1 text-[10px] text-gray-400 dark:text-brand-muted/60"><?= html_escape(implode(' · ', $konteks)) ?></div>
                        <?php endif; ?>
                        <?php if ( ! empty($rapi)): ?>
                        <details class="mt-2 group">
                            <summary class="cursor-pointer list-none text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">
                                <i class="ph ph-caret-right transition-transform group-open:rotate-90"></i> Detail
                            </summary>
                            <pre class="mt-2 max-h-64 overflow-auto rounded-xl bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 p-3 text-[11px] leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all"><?= html_escape($rapi) ?></pre>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
