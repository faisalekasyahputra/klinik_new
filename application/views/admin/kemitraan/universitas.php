<?php
$this->load->helper('admin_table');
/* Dipakai bersama superadmin (Admin_Kemitraan::universitas) dan admin bidang
   (Kemitraan_Bidang::universitas, ditandai $aksi_buat). Tab KKN & Magang hanya untuk
   superadmin: bagi admin bidang ketiganya menolak akses (temuan UAT universitas U1). */
if (empty($aksi_buat)) {
    $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'universitas']);
} else { ?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Buatkan akun untuk universitas mitra KKN, lalu serahkan email dan sandinya kepada universitas tersebut.']); ?>
<?php } ?>
<?php /* TANPA `z-10` di pembungkus - alasan sama dengan admin/users/index.php:
         pembungkus ini memuat modal "Tambah Universitas". */ ?>
<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-5" x-data="{ createOpen: false }">
    <div>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            Akun (role Universitas) yang bisa mengajukan KKN lewat dashboardnya sendiri.
<?php if (empty($aksi_buat)): ?>
            Sunting, nonaktifkan, atau reset sandi lewat <a href="<?= base_url('Admin_Users') ?>" class="font-bold text-blue-600 dark:text-brand-primary hover:underline">Manajemen Pengguna</a> -
            satu tempat untuk seluruh akun apa pun rolenya, tab ini tidak menyalinnya.
        <?php else: ?>
            Sunting data, reset sandi, atau nonaktifkan akun lewat tombol Kelola di tiap baris. Sandi dari admin wajib diganti universitas saat masuk.
<?php endif; ?>
        </p>
    </div>
    <button @click="createOpen = true" class="tombol-utama shrink-0">
        <i class="ph ph-bank"></i><span>Tambah universitas</span>
    </button>

    <!-- Modal: buat akun universitas. POST ke Admin_Users/create_staff yang
         SAMA dipakai Manajemen Pengguna - role dikirim TERSEMBUNYI sebagai
         'universitas' (bukan dipilih manual) supaya formulir ini tidak perlu
         menanyakan sesuatu yang jawabannya sudah pasti. Role ini scope-less
         (tidak butuh kabupaten_id/bidang_kode) sama seperti 'mahasiswa',
         jadi create_staff() memprosesnya tanpa cabang tambahan - lihat
         config/roles.php. Nomor HP OPSIONAL di sini tapi diisi kalau memang
         diketahui - KemitraanPortal::kkn_tambah() mewajibkannya sebelum
         akun bisa mengajukan KKN pertamanya (lihat komentar di
         Admin_Users::create_staff()), jadi mengisinya di sini berarti akun
         langsung siap pakai. -->
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="createOpen = false">
        <div @click.outside="createOpen = false" class="w-full max-w-md rounded-3xl bg-white dark:bg-brand-card p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Tambah Universitas</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-brand-muted">Akun ini bisa langsung masuk dan mengajukan KKN lewat dashboardnya, sesudah mengganti sandi awal dari admin.</p>
            <form method="POST" action="<?= base_url($aksi_buat ?? 'Admin_Users/create_staff') ?>" class="space-y-3">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="role" value="universitas">
<?php if (empty($aksi_buat)): ?>
                <input type="hidden" name="kembali" value="Admin_Kemitraan/universitas">
<?php endif; ?>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Nama Universitas</label>
                    <input type="text" name="name" required maxlength="150" placeholder="Contoh: Universitas Diponegoro"
                           class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Email</label>
                    <input type="email" name="email" required maxlength="100" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Nomor HP/WhatsApp <span class="font-normal normal-case text-gray-400">(opsional, bisa dilengkapi nanti)</span></label>
                    <input type="tel" name="phone" maxlength="20" pattern="\+?[0-9][0-9 \-]{6,19}" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Sandi</label>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-brand-muted">Minimal 8 karakter, ada huruf besar, angka, dan simbol.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createOpen = false" class="tombol-kedua"><span>Batal</span></button>
                    <button type="submit" class="tombol-utama"><i class="ph ph-user-plus"></i><span>Buat akun</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Akun Universitas', 'kt_jumlah' => (int) $table['total_rows'], 'kt_keterangan' => $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama, email, atau username...', 'tb_sebaris' => TRUE], TRUE)]); ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-3"><?= admin_sort_header('Nama Universitas', 'nama', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-3">No. HP</th>
                    <th scope="col" class="px-4 py-3 text-center">KKN Diajukan</th>
                    <th scope="col" class="px-4 py-3">Status</th>
                    <th scope="col" class="px-4 py-3"><?= admin_sort_header('Terdaftar', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-bank"></i>
                            </div>
                            <p>Belum ada akun universitas terdaftar.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($rows as $u):
                    // Aturan status SAMA dengan admin/users/index.php - lihat
                    // komentar lengkap di sana. Disalin, bukan dipanggil dari
                    // controller: view ini tidak boleh menarik model baru.
                    $nonaktif = strtolower(trim((string) ($u->status ?? ''))) === 'nonaktif';
                    $terkunci = ! empty($u->terkunci_sampai) && strtotime($u->terkunci_sampai) > time();
                ?>
                <tr>
                    <td class="px-4 py-3 max-w-[14rem] whitespace-normal">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($u->nama) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted break-words"><?= html_escape($u->email) ?></div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        <?= $u->no_hp ? html_escape($u->no_hp) : '<span class="text-red-500">belum diisi</span>' ?>
                    </td>
                    <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white"><?= (int) $u->jumlah_kkn ?></td>
                    <td class="px-4 py-3">
                        <?php if ($nonaktif): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Nonaktif', 'kelas' => 'reject'], TRUE) ?>
                        <?php elseif ($terkunci): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Terkunci', 'kelas' => 'pending'], TRUE) ?>
                        <?php else: ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Aktif', 'kelas' => 'ok'], TRUE) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs"><?= html_escape(tgl_id($u->created_at ?? '', TRUE)) ?></td>
                    <td class="px-4 py-3 text-right">
                        <?php /* Superadmin: tautan ke baris yang SAMA di Manajemen Pengguna.
                                 Admin bidang: kelola langsung di sini (keputusan pemilik produk
                                 29 Sep 2026), ke Kemitraan_Bidang yang hanya menerima role universitas. */ ?>
<?php if (empty($aksi_buat)): ?>
                        <a href="<?= base_url('Admin_Users?q=' . urlencode($u->email)) ?>" class="tombol-aksi">
                            <i class="ph ph-gear"></i><span>Kelola akun</span>
                        </a>
<?php else:
    $csrf_isian = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '"><input type="hidden" name="id" value="' . (int) $u->id . '">';
    $isian_kls = 'w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200';
    $label_kls = 'mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted';
?>
                        <span x-data="{ kelola: false }">
                        <button type="button" @click="kelola = true" class="tombol-aksi">
                            <i class="ph ph-gear"></i><span>Kelola</span>
                        </button>
                        <?php /* Teleport: sel Aksi sticky membuat stacking context, modal di dalamnya terkubur di bawah topbar (.aksi-tetap di layouts/head.php). */ ?>
                        <template x-teleport="body">
                        <div x-show="kelola" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 text-left whitespace-normal" @keydown.escape.window="kelola = false">
                            <div @click.outside="kelola = false" class="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-brand-card p-6 shadow-xl space-y-5">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Kelola <?= html_escape($u->nama) ?></h3>
                                <form method="POST" action="<?= base_url('Kemitraan_Bidang/ubah_universitas') ?>" class="space-y-3">
                                    <?= $csrf_isian ?>
                                    <div><label class="<?= $label_kls ?>">Nama Universitas</label>
                                        <input type="text" name="name" required maxlength="150" value="<?= html_escape($u->nama) ?>" class="<?= $isian_kls ?>"></div>
                                    <div><label class="<?= $label_kls ?>">Email</label>
                                        <input type="email" name="email" required maxlength="100" value="<?= html_escape($u->email) ?>" class="<?= $isian_kls ?>"></div>
                                    <div><label class="<?= $label_kls ?>">Nomor HP/WhatsApp</label>
                                        <input type="tel" name="phone" maxlength="20" pattern="\+?[0-9][0-9 \-]{6,19}" value="<?= html_escape($u->no_hp ?? '') ?>" class="<?= $isian_kls ?>"></div>
                                    <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan data</span></button>
                                </form>
                                <form method="POST" action="<?= base_url('Kemitraan_Bidang/sandi_universitas') ?>" class="space-y-3 border-t border-gray-100 dark:border-white/5 pt-4">
                                    <?= $csrf_isian ?>
                                    <div><label class="<?= $label_kls ?>">Sandi Baru</label>
                                        <input type="password" name="password" required minlength="8" autocomplete="new-password" class="<?= $isian_kls ?>">
                                        <p class="mt-1 text-[11px] text-gray-500 dark:text-brand-muted">Minimal 8 karakter, ada huruf besar, angka, dan simbol. Universitas wajib menggantinya saat masuk.</p></div>
                                    <button type="submit" class="tombol-utama"><i class="ph ph-key"></i><span>Reset sandi</span></button>
                                </form>
                                <form method="POST" action="<?= base_url('Kemitraan_Bidang/status_universitas') ?>" class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-white/5 pt-4"
                                      <?= $nonaktif ? 'data-konfirmasi="Akun universitas ini bisa masuk kembali." data-konfirmasi-judul="Aktifkan akun?" data-konfirmasi-label="Aktifkan"' : 'data-konfirmasi="Sesinya langsung berakhir dan akun ini tidak bisa masuk." data-konfirmasi-judul="Nonaktifkan akun?" data-konfirmasi-label="Nonaktifkan" data-konfirmasi-bahaya' ?>>
                                    <?= $csrf_isian ?>
                                    <input type="hidden" name="status" value="<?= $nonaktif ? 'active' : 'nonaktif' ?>">
                                    <button type="submit" class="tombol-kedua<?= $nonaktif ? '' : ' tombol-aksi-bahaya' ?>"><i class="ph <?= $nonaktif ? 'ph-check-circle' : 'ph-prohibit' ?>"></i><span><?= $nonaktif ? 'Aktifkan kembali' : 'Nonaktifkan akun' ?></span></button>
                                    <button type="button" @click="kelola = false" class="tombol-kedua"><span>Tutup</span></button>
                                </form>
                            </div>
                        </div>
                        </template>
                        </span>
<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
