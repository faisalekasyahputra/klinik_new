<?php
/**
 * Katalog Program.
 *
 * Kolom "Nama di hasil diagnosa" bukan hiasan — ia dari
 * `Smart_filter::master_programs()`, sumber yang BERBEDA dari tabel ini, dan
 * selisihnya berarti warga melihat dua nama untuk satu program.
 */
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>
<div x-data="{ buka: false, id: 0, nama: '', desk: '', aktif: true, kode: '', dipakai: 0 }">
    <div class="mb-6">
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Katalog Program</h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            Program bantuan perumahan yang bisa diajukan warga. Yang bisa diubah dari sini:
            <b>nama</b>, <b>deskripsi</b>, dan <b>status aktif</b>.
        </p>
    </div>

    <?php if ($jml_selisih > 0 || $tanpa_baris || $jml_tanpa_aturan > 0): ?>
    <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-300 text-sm flex items-start gap-3">
        <i class="ph ph-warning text-lg mt-0.5"></i>
        <div class="space-y-1">
            <strong>Katalog ini punya dua sumber, dan keduanya tidak sepakat.</strong>
            <p class="text-xs leading-relaxed">
                Tabel <code>sf_programs</code> menentukan <b>status aktif</b> dan nama yang tampil di
                <b>antrean admin serta halaman /akun warga</b>. Sementara judul di <b>kartu hasil diagnosa</b>
                dan seluruh aturan kelayakan ada di kode
                (<code>application/libraries/Smart_filter.php</code>).
            </p>
            <ul class="text-xs space-y-0.5 pt-1">
                <?php if ($jml_selisih > 0): ?>
                <li>• <b><?= (int) $jml_selisih ?> program</b> memakai nama berbeda di dua tempat — warga melihat satu nama saat memilih, nama lain di pengajuannya.</li>
                <?php endif; ?>
                <?php if ($jml_tanpa_aturan > 0): ?>
                <li>• <b><?= (int) $jml_tanpa_aturan ?> program</b> ada di tabel tapi tidak punya aturan kelayakan — tidak akan pernah muncul untuk warga mana pun.</li>
                <?php endif; ?>
                <?php if ($tanpa_baris): ?>
                <li>• <b><?= count($tanpa_baris) ?> program</b> punya aturan tapi <b>tidak punya baris tabel</b> (<?= html_escape(implode(', ', $tanpa_baris)) ?>) — kartunya muncul, pengajuannya gagal.</li>
                <?php endif; ?>
            </ul>
            <p class="text-xs pt-1">Selisih nama bisa dibereskan dari layar ini. Aturan kelayakan tidak — itu perubahan kode.</p>
        </div>
    </div>
    <?php endif; ?>

    <div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4">Nama di antrean &amp; /akun</th>
                        <th class="px-4 py-4">Nama di hasil diagnosa</th>
                        <th class="px-4 py-4">Kode <span class="font-normal normal-case">(tidak bisa diubah)</span></th>
                        <th class="px-4 py-4">Kategori</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Dipakai</th>
                        <th class="px-4 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Katalog kosong — seed program belum jalan.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <?php // max-w diukur, bukan ditebak: 240px menghasilkan 6px kelebihan di 1440px (§17 poin 6). ?>
                        <td class="px-4 py-3 max-w-[200px]">
                            <div class="font-bold text-gray-900 dark:text-white truncate"><?= html_escape($r->nama_program) ?></div>
                            <div class="text-xs text-gray-500 dark:text-brand-muted truncate"><?= html_escape($r->deskripsi_singkat) ?></div>
                        </td>
                        <td class="px-4 py-3 max-w-[220px] text-xs">
                            <?php if ($r->tanpa_aturan): ?>
                            <span class="italic text-amber-600 dark:text-amber-400">tidak punya aturan kelayakan</span>
                            <?php else: ?>
                                <?php foreach ($r->judul_diagnosa as $j): ?>
                                <div class="truncate <?= $j !== $r->nama_program ? 'font-bold text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-brand-muted' ?>"><?= html_escape($j) ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-black/30"><?= html_escape($r->kode_program) ?></code></td>
                        <td class="px-4 py-3 text-xs"><?= html_escape($r->nama_kategori ?: '—') ?></td>
                        <td class="px-4 py-3">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => (int) $r->is_active === 1 ? 'Aktif' : 'Nonaktif',
                                'kelas' => (int) $r->is_active === 1 ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $r->dipakai ?> pengajuan</td>
                        <td class="px-4 py-3">
                            <button type="button" title="Ubah program"
                                    @click="id=<?= (int) $r->id ?>; nama=<?= htmlspecialchars(json_encode($r->nama_program), ENT_QUOTES) ?>; desk=<?= htmlspecialchars(json_encode($r->deskripsi_singkat), ENT_QUOTES) ?>; aktif=<?= (int) $r->is_active === 1 ? 'true' : 'false' ?>; kode=<?= htmlspecialchars(json_encode($r->kode_program), ENT_QUOTES) ?>; dipakai=<?= (int) $r->dipakai ?>; buka=true"
                                    class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:border-white/10 dark:text-brand-muted dark:hover:bg-white/5">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i> Ubah
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-3 text-xs text-gray-400 dark:text-brand-muted/70">
        <i class="ph ph-info mr-1" aria-hidden="true"></i>
        Kolom <code>batas_penghasilan_max</code> ada di tabel dan berisi nilai, tetapi
        <b>tidak dibaca kode mana pun</b> — kelayakan dihitung dari desil, bukan penghasilan.
        Karena itu ia tidak ditampilkan maupun bisa diubah di sini.
    </p>

    <?php // Satu modal untuk seluruh tabel — formulir per baris akan mendorongnya melewati wadahnya. ?>
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="buka = false" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-brand-card">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-3 dark:border-white/10">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white">Ubah Program</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-brand-muted"><code x-text="kode"></code></p>
                </div>
                <button type="button" @click="buka = false" aria-label="Tutup" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i class="ph ph-x text-lg" aria-hidden="true"></i></button>
            </div>

            <form method="POST" action="<?= base_url('Admin_Katalog_Program/ubah') ?>" class="mt-4 space-y-3 text-xs">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                <input type="hidden" name="id" :value="id">

                <label class="block">
                    <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300">Nama program</span>
                    <input type="text" name="nama_program" x-model="nama" required maxlength="255"
                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-gray-200">
                    <span class="mt-1 block text-[11px] text-gray-500 dark:text-brand-muted">Tampil di antrean admin dan halaman /akun warga.</span>
                </label>

                <label class="block">
                    <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300">Deskripsi singkat</span>
                    <textarea name="deskripsi_singkat" x-model="desk" rows="2"
                              class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-gray-200"></textarea>
                </label>

                <label class="flex items-start gap-2 rounded-lg bg-gray-50 p-2.5 dark:bg-black/20">
                    <input type="checkbox" name="is_active" value="1" x-model="aktif" class="mt-0.5">
                    <span>
                        <span class="font-bold text-gray-700 dark:text-gray-300">Aktif — bisa diajukan warga</span>
                        <span class="mt-0.5 block text-[11px] leading-relaxed text-gray-500 dark:text-brand-muted">
                            Dimatikan berarti pengajuan BARU ditolak. Pengajuan yang sudah masuk antrean
                            <b>tidak dibatalkan</b> — saat ini <span x-text="dipakai"></span> pengajuan memakai program ini.
                        </span>
                    </span>
                </label>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="buka = false" class="rounded-lg border border-gray-200 px-3 py-2 font-bold text-gray-600 dark:border-white/10 dark:text-brand-muted">Batal</button>
                    <button type="submit" class="rounded-lg border border-brand-primary/50 bg-brand-primary/20 px-4 py-2 font-bold text-brand-primary hover:bg-brand-primary/30">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
