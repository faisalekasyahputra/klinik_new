<?php
/**
 * Katalog Program.
 *
 * Kolom "Nama di hasil diagnosa" bukan hiasan - ia dari
 * `Smart_filter::master_programs()`, sumber yang BERBEDA dari tabel ini, dan
 * selisihnya berarti warga melihat dua nama untuk satu program.
 *
 * Dua sumber: tabel `sf_program` menentukan status aktif dan nama di antrean
 * admin serta /akun warga; judul di kartu hasil diagnosa dan seluruh aturan
 * kelayakan ada di kode (application/libraries/Smart_filter.php). Selisih nama
 * bisa dibereskan dari layar ini, aturan kelayakan tidak (itu perubahan kode).
 *
 * Kolom `batas_penghasilan_maks` ada di tabel dan berisi nilai, tetapi tidak
 * dibaca kode mana pun (kelayakan dihitung dari desil, bukan penghasilan).
 * Karena itu ia tidak ditampilkan maupun bisa diubah di sini.
 */
?>
<div>
    <?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Program bantuan perumahan yang bisa diajukan warga. Yang bisa diubah dari sini:
            <b>nama</b>, <b>deskripsi</b>, <b>status aktif</b>, serta <b>tampilannya di beranda</b> -
            label, syarat utama, foto, dan urutan. Warna kartu tidak diatur di sini: paletnya
            disetel sekali supaya kontras teksnya terjaga.']); ?>

    <?php if ($jml_selisih > 0 || $tanpa_baris || $jml_tanpa_aturan > 0): ?>
    <div class="mb-5 p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-300 text-sm flex items-start gap-3">
        <i class="ph ph-warning text-lg mt-0.5"></i>
        <div class="space-y-1">
            <strong>Nama program diambil dari dua sumber yang belum sama.</strong>
            <p class="text-xs leading-relaxed">
                Nama di layar ini tampil di <b>antrean admin dan akun warga</b>. Judul di
                <b>hasil diagnosa</b> dan aturan kelayakan diatur oleh pengembang.
            </p>
            <ul class="text-xs space-y-0.5 pt-1">
                <?php if ($jml_selisih > 0): ?>
                <li>• <b><?= (int) $jml_selisih ?> program</b> memakai nama berbeda di dua tempat - warga melihat satu nama saat memilih, nama lain di pengajuannya.</li>
                <?php endif; ?>
                <?php if ($jml_tanpa_aturan > 0): ?>
                <li>• <b><?= (int) $jml_tanpa_aturan ?> program</b> ada di tabel tapi tidak punya aturan kelayakan - tidak akan pernah muncul untuk warga mana pun.</li>
                <?php endif; ?>
                <?php if ($tanpa_baris): ?>
                <li>• <b><?= count($tanpa_baris) ?> program</b> punya aturan tapi <b>tidak punya baris tabel</b> (<?= html_escape(implode(', ', $tanpa_baris)) ?>) - kartunya muncul, pengajuannya gagal.</li>
                <?php endif; ?>
            </ul>
            <p class="text-xs pt-1">Selisih nama bisa dibereskan lewat tombol Ubah. Aturan kelayakan hanya bisa diubah pengembang.</p>
        </div>
    </div>
    <?php endif; ?>

    <div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
        <div class="overflow-x-auto aksi-tetap">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4">Nama di antrean &amp; akun warga</th>
                        <th class="px-4 py-4">Nama di hasil diagnosa</th>
                        <th class="px-4 py-4">Kode</th>
                        <th class="px-4 py-4">Kategori</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Dipakai</th>
                        <th class="px-4 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada program.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <?php // max-w diukur, bukan ditebak: 240px menghasilkan 6px kelebihan di 1440px (§17 poin 6). ?>
                        <td class="px-4 py-3 max-w-[280px]">
                            <div class="flex items-center gap-3">
                                <?php // Thumbnail = foto yang tampil di korsel beranda (Program_model::gambar_tampil), 4 Okt 2026. ?>
                                <img src="<?= base_url($r->gambar_tampil) ?>" alt="" class="h-10 w-14 shrink-0 rounded-lg border border-gray-200 object-cover dark:border-white/10">
                                <div class="min-w-0">
                                    <div class="whitespace-normal font-bold leading-tight text-gray-900 dark:text-white"><?= html_escape($r->nama_program) ?></div>
                                    <div class="text-xs text-gray-500 dark:text-brand-muted truncate"><?= html_escape($r->deskripsi_singkat) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 max-w-[220px] text-xs">
                            <?php if ($r->tanpa_aturan): ?>
                            <span class="italic text-amber-600 dark:text-amber-400">tidak punya aturan kelayakan</span>
                            <?php else: ?>
                                <?php foreach ($r->judul_diagnosa as $j): ?>
                                <div class="truncate <?= in_array($j, $r->judul_beda, TRUE) ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-brand-muted' ?>"><?= html_escape($j) ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-black/30"><?= html_escape($r->kode_program) ?></code></td>
                        <td class="px-4 py-3 text-xs max-w-[14rem] whitespace-normal"><?= html_escape($r->nama_kategori ?: '-') ?></td>
                        <td class="px-4 py-3">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => (int) $r->aktif === 1 ? 'Aktif' : 'Nonaktif',
                                'kelas' => (int) $r->aktif === 1 ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $r->dipakai ?> pengajuan</td>
                        <td class="px-4 py-3">
                            <a href="<?= base_url('Admin_Katalog_Program/edit/' . (int) $r->id) ?>" title="Ubah program" class="tombol-aksi"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
