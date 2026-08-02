<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6 relative z-10" x-data="{ createOpen: false }">
    <div>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Manajemen Pengguna</h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola akun, peran, dan akses pengguna dalam sistem.</p>
    </div>
    <button @click="createOpen = true" class="bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark px-5 py-2.5 rounded-xl font-bold flex items-center hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20">
        <i class="ph ph-user-plus text-lg mr-2"></i> Tambah Pengguna Baru
    </button>

    <!-- Modal: buat akun staff -->
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="createOpen = false">
        <div @click.outside="createOpen = false" class="w-full max-w-md rounded-3xl bg-white dark:bg-brand-card p-6 shadow-xl" x-data="{ role: '' }">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Buat Akun Staff</h3>
            <form method="POST" action="<?= base_url('Admin_Users/create_staff') ?>" class="space-y-3">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Nama</label>
                    <input type="text" name="name" required maxlength="150" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Email</label>
                    <input type="email" name="email" required maxlength="100" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Role</label>
                    <select name="role" x-model="role" required class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                        <option value="">Pilih role</option>
                        <?php foreach ($available_roles as $role_key => $role_label): ?>
                        <option value="<?= html_escape($role_key) ?>"><?= html_escape($role_label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div x-show="role === 'admin_kabkota'" x-cloak>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Kabupaten/Kota</label>
                    <select name="kabupaten_id" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                        <option value="">Pilih kabupaten/kota</option>
                        <?php foreach ($kabupaten_list as $k): ?>
                        <option value="<?= $k->id ?>"><?= html_escape($k->nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div x-show="role === 'admin_bidang'" x-cloak>
                    <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Bidang</label>
                    <select name="bidang_kode" class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                        <option value="">Pilih bidang</option>
                        <?php foreach ($bidang_list as $b): ?>
                        <option value="<?= html_escape($b->kode) ?>"><?= html_escape($b->nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="createOpen = false" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->load->helper('admin_table'); ?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden relative z-10">
    <div class="p-6 border-b border-gray-200 dark:border-white/5">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-users-three text-brand-primary"></i> Daftar Pengguna (<?= number_format((int) $table['total_rows']) ?>)
        </h3>
    </div>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama, email, atau username...'], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-4"><?= admin_sort_header('Nama Pengguna', 'name', $table, $base_url) ?></th>
                    <th scope="col" class="px-6 py-4"><?= admin_sort_header('Peran (Role)', 'role', $table, $base_url) ?></th>
                    <th scope="col" class="px-6 py-4">Scope</th>
                    <th scope="col" class="px-6 py-4"><?= admin_sort_header('Terdaftar', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users"></i>
                            </div>
                            <p>Belum ada pengguna terdaftar.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($users as $u): ?>
                <tr x-data="{ editOpen: false, role: '<?= html_escape($u->role ?? '') ?>' }">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($u->name) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($u->email) ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary">
                            <?= html_escape($available_roles[$u->role] ?? ($u->role ?: '-')) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        <?php if ($u->role === 'admin_kabkota'): ?>
                            <?php $kab = current(array_filter($kabupaten_list, fn($k) => $k->id == $u->kabupaten_id)) ?: null; ?>
                            <?= $kab ? html_escape($kab->nama) : '<span class="text-red-500">belum diset</span>' ?>
                        <?php elseif ($u->role === 'admin_bidang'): ?>
                            <?php $bid = current(array_filter($bidang_list, fn($b) => $b->kode === $u->bidang_kode)) ?: null; ?>
                            <?= $bid ? html_escape($bid->nama) : '<span class="text-red-500">belum diset</span>' ?>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-brand-muted/60">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-xs"><?= html_escape(date('d M Y', strtotime($u->created_at ?? 'now'))) ?></td>
                    <td class="px-6 py-4 text-right relative">
                        <button @click="editOpen = !editOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">
                            <i class="ph ph-pencil-simple"></i> Ubah Role
                        </button>
                        <div x-show="editOpen" x-cloak @click.outside="editOpen = false" class="absolute right-6 top-full mt-1 z-20 w-64 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                            <form method="POST" action="<?= base_url('Admin_Users/update_role') ?>" class="space-y-2">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <input type="hidden" name="id" value="<?= $u->id ?>">
                                <select name="role" x-model="role" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                                    <?php foreach ($available_roles as $role_key => $role_label): ?>
                                    <option value="<?= html_escape($role_key) ?>"><?= html_escape($role_label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div x-show="role === 'admin_kabkota'" x-cloak>
                                    <select name="kabupaten_id" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                                        <option value="">Pilih kabupaten/kota</option>
                                        <?php foreach ($kabupaten_list as $k): ?>
                                        <option value="<?= $k->id ?>" <?= (int)$u->kabupaten_id === (int)$k->id ? 'selected' : '' ?>><?= html_escape($k->nama) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div x-show="role === 'admin_bidang'" x-cloak>
                                    <select name="bidang_kode" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                                        <option value="">Pilih bidang</option>
                                        <?php foreach ($bidang_list as $b): ?>
                                        <option value="<?= html_escape($b->kode) ?>" <?= $u->bidang_kode === $b->kode ? 'selected' : '' ?>><?= html_escape($b->nama) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="w-full mt-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover">Simpan</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
