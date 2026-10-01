<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Rekaman tindakan yang mengubah akses dan data pengelolaan - siapa melakukannya, kapan, dan
        terhadap apa. Termasuk percobaan yang <span class="font-bold">ditolak</span> sistem. Layar ini
        hanya bisa dibaca; barisnya tidak bisa diubah atau dihapus dari sini.']); ?>

<?php
$this->load->helper('admin_table');
// Filter dibangun lewat admin_table_url() supaya pencarian dan urutan yang
// sedang aktif tidak hilang saat ganti filter, dan sebaliknya.
// Aksi yang berakhiran `_ditolak` adalah percobaan yang DIBLOKIR - dibedakan
// di filter (kelompok sendiri) maupun di baris (warna merah), karena justru
// itu yang orang cari saat membuka layar ini.
$ditolak = fn($a) => str_ends_with((string) $a, '_ditolak');

// Kode aksi (`aduan_ditriase`) dibaca admin dinas, bukan pengembang: audit_label_aksi()
// di ternak_helper (aturan umum plus kamus kecil). Kodenya tetap di atribut title dan tetap bisa dicari.
$label_aksi = fn($a) => audit_label_aksi($a);

// Filter aksi: SATU dropdown, bukan dinding sekitar 60 pil (audit UI 2 Okt 2026).
// Tetap GET `aksi` yang sama, divalidasi controller lewat in_array($aksi_tersedia).
// Parameter lain (q, urutan) dibawa sebagai hidden; `page` tidak, filter baru mulai dari halaman 1.
$aksi_biasa   = array_values(array_filter($aksi_tersedia, fn($a) => ! $ditolak($a)));
$aksi_ditolak = array_values(array_filter($aksi_tersedia, $ditolak));
ob_start(); ?>
<form method="get" action="<?= base_url($base_url) ?>" class="flex items-center gap-2" data-filter-aksi>
    <?php foreach ($_GET as $k => $v) {
        if (in_array($k, ['aksi', 'page'], TRUE) || is_array($v)) { continue; }
        echo '<input type="hidden" name="' . html_escape($k) . '" value="' . html_escape($v) . '">';
    } ?>
    <label for="filter-aksi" class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Aksi</label>
    <select id="filter-aksi" name="aksi" onchange="this.form.submit()"
            class="max-w-[16rem] rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-800 dark:border-white/10 dark:bg-black/30 dark:text-white">
        <option value="">Semua aksi</option>
        <?php foreach ($aksi_biasa as $a): ?>
        <option value="<?= html_escape($a) ?>" <?= $f_aksi === $a ? 'selected' : '' ?>><?= html_escape($label_aksi($a)) ?></option>
        <?php endforeach; ?>
        <?php if ($aksi_ditolak): ?>
        <optgroup label="Percobaan ditolak">
            <?php foreach ($aksi_ditolak as $a): ?>
            <option value="<?= html_escape($a) ?>" <?= $f_aksi === $a ? 'selected' : '' ?>><?= html_escape($label_aksi($a)) ?></option>
            <?php endforeach; ?>
        </optgroup>
        <?php endif; ?>
    </select>
    <noscript><button type="submit" class="tombol-aksi"><i class="ph ph-funnel"></i><span>Terapkan</span></button></noscript>
</form>
<?php
$filter_html = ob_get_clean();
?>
<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden relative z-10">
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Riwayat Tindakan', 'kt_jumlah' => (int) $table['total_rows'], 'kt_keterangan' => '']); ?>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari ringkasan, email pelaku, atau aksi...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-3"><?= admin_sort_header('Waktu', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-3"><?= admin_sort_header('Pelaku', 'actor_email', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-3"><?= admin_sort_header('Aksi', 'aksi', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-3">Ringkasan</th>
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
                            <p class="mt-1 text-xs">Baris akan muncul sendiri begitu ada tindakan pengelolaan - mengubah peran, menonaktifkan akun, mereset sandi.</p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($jejak as $j): ?>
                <tr>
                    <td class="px-4 py-3 text-xs">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape(tgl_id($j->created_at, TRUE)) ?></div>
                        <div class="text-gray-500 dark:text-brand-muted"><?= html_escape($j->created_at ? date('H.i', strtotime($j->created_at)) . ' WIB' : '-') ?></div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        <?php if ( ! empty($j->actor_email)): ?>
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($j->actor_email) ?></div>
                        <div class="text-gray-500 dark:text-brand-muted"><?= html_escape($j->actor_role ?: '-') ?></div>
                        <?php // actor_id kosong sementara emailnya ada = akunnya sudah dihapus
                              // (FK ON DELETE SET NULL) dan salinan email inilah yang menyelamatkan
                              // "siapa"-nya. Ditandai apa adanya, bukan disembunyikan - akun yang
                              // sudah tidak ada justru yang paling sering perlu ditelusuri. ?>
                        <?php if ($j->actor_id === NULL): ?>
                        <div class="mt-1 text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">akun sudah dihapus</div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-gray-400 dark:text-brand-muted/60 italic">(akun terhapus)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold <?= $ditolak($j->aksi)
                            ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                            : 'bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary' ?>" title="<?= html_escape($j->aksi) ?>"><?= html_escape($label_aksi($j->aksi)) ?></span>
                    </td>
                    <?php // Kolom teks terpanjang (ringkasan sampai 255 karakter). Tabel ini
                          // memakai `whitespace-nowrap`; tanpa max-w + whitespace-normal satu
                          // ringkasan panjang cukup untuk mendorong tabel melewati wadahnya. ?>
                    <td class="px-4 py-3 max-w-[24rem] whitespace-normal">
                        <?php // Diterjemahkan saat tampil (audit_ringkasan); baris tersimpan tetap apa adanya. ?>
                        <?= html_escape(audit_ringkasan($j)) ?>
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
                            $j->objek_tipe ? ucfirst(audit_label_objek($j->objek_tipe, $j->objek_id)) : NULL,
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
