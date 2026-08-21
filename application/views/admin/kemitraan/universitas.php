<?php
$this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'universitas']);
$this->load->helper('admin_table');
?>
<?php /* TANPA `z-10` di pembungkus - alasan sama dengan admin/users/index.php:
         pembungkus ini memuat modal "Tambah Universitas". */ ?>
<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6" x-data="{ createOpen: false }">
    <div>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            Akun (role Mahasiswa/Universitas) yang bisa mengajukan KKN lewat dashboardnya sendiri.
            Sunting, nonaktifkan, atau reset sandi lewat <a href="<?= base_url('Admin_Users') ?>" class="font-bold text-blue-600 dark:text-brand-primary hover:underline">Manajemen Pengguna</a> -
            satu tempat untuk seluruh akun apa pun rolenya, tab ini tidak menyalinnya.
        </p>
    </div>
    <button @click="createOpen = true" class="shrink-0 bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark px-5 py-2.5 rounded-xl font-bold flex items-center hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20">
        <i class="ph ph-bank text-lg mr-2"></i> Tambah Universitas
    </button>

    <!-- Modal: buat akun universitas. POST ke Admin_Users/create_staff yang
         SAMA dipakai Manajemen Pengguna - role dikirim TERSEMBUNYI sebagai
         'mahasiswa' (bukan dipilih manual) supaya formulir ini tidak perlu
         menanyakan sesuatu yang jawabannya sudah pasti. Nomor HP OPSIONAL
         di sini tapi diisi kalau memang diketahui - KemitraanPortal::kkn_tambah()
         mewajibkannya sebelum akun bisa mengajukan KKN pertamanya (lihat
         komentar di Admin_Users::create_staff()), jadi mengisinya di sini
         berarti akun langsung siap pakai. -->
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="createOpen = false">
        <div @click.outside="createOpen = false" class="w-full max-w-md rounded-3xl bg-white dark:bg-brand-card p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Tambah Universitas</h3>
            <p class="mb-4 text-xs text-gray-500 dark:text-brand-muted">Akun ini bisa langsung masuk dan mengajukan KKN lewat dashboardnya.</p>
            <form method="POST" action="<?= base_url('Admin_Users/create_staff') ?>" class="space-y-3">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="role" value="mahasiswa">
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
                    <input type="tel" name="phone" maxlength="20" placeholder="08xxxxxxxxxx" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Password</label>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-white/5">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-bank text-brand-primary"></i> Akun Universitas (<?= number_format((int) $table['total_rows']) ?>)
        </h3>
    </div>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama, email, atau username...'], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Nama Universitas', 'name', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4">No. HP</th>
                    <th scope="col" class="px-4 py-4 text-center">KKN Diajukan</th>
                    <th scope="col" class="px-4 py-4">Status</th>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Terdaftar', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4 text-right">Aksi</th>
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
                    $terkunci = ! empty($u->locked_until) && strtotime($u->locked_until) > time();
                ?>
                <tr>
                    <td class="px-4 py-4 max-w-[14rem] whitespace-normal">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($u->name) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted break-words"><?= html_escape($u->email) ?></div>
                    </td>
                    <td class="px-4 py-4 text-xs">
                        <?= $u->phone ? html_escape($u->phone) : '<span class="text-red-500">belum diisi</span>' ?>
                    </td>
                    <td class="px-4 py-4 text-center font-bold text-gray-900 dark:text-white"><?= (int) $u->jumlah_kkn ?></td>
                    <td class="px-4 py-4">
                        <?php if ($nonaktif): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Nonaktif', 'kelas' => 'reject'], TRUE) ?>
                        <?php elseif ($terkunci): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Terkunci', 'kelas' => 'pending'], TRUE) ?>
                        <?php else: ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Aktif', 'kelas' => 'ok'], TRUE) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-xs"><?= html_escape(date('d M Y', strtotime($u->created_at ?? 'now'))) ?></td>
                    <td class="px-4 py-4 text-right">
                        <?php /* Sunting/nonaktifkan/reset sandi TIDAK diduplikasi di sini -
                                 lihat komentar kepala Admin_Kemitraan::universitas(). Tautan
                                 ini membawa admin ke baris yang SAMA di Manajemen Pengguna
                                 lewat pencarian email, bukan cuma ke daftar penuh. */ ?>
                        <a href="<?= base_url('Admin_Users?q=' . urlencode($u->email)) ?>"
                           class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">
                            <i class="ph ph-gear"></i> Kelola Akun
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
