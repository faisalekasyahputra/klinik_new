<?php /* TANPA `z-10`: pembungkus ini memuat modal "Tambah Pengguna", dan
         `relative z-10` menguncinya di stacking context z-10 — persis alasan yang
         sama dengan `#main-content` di admin/index.php. Dua konteks bersarang,
         satu bug. */ ?>
<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6" x-data="{ createOpen: false }">
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
<?php /* TANPA `z-10`: modal "Reset Sandi" (`fixed inset-0 z-50`) ditulis di dalam
         <td> di kartu ini, jadi stacking context z-10 di sini menguburnya juga.
         `relative` dipertahankan — popover Ubah Role di dalam sel memakainya. */ ?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden relative">
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
                    <?php /* px-4, bukan px-6: kolom bertambah dari 5 ke 6 dan tabel ini
                             whitespace-nowrap — AGENTS.md §17 poin 6. Kolom Aksi yang
                             pertama hilang di balik gulir horizontal, dan itu sudah dua
                             kali terjadi di layar admin lain. */ ?>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Nama Pengguna', 'name', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Peran (Role)', 'role', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4">Scope</th>
                    <?php /* Tanpa admin_sort_header(): `status` tidak ada di whitelist
                             table_state() milik controller, dan controller tidak boleh
                             disentuh di pekerjaan ini. Header urut yang menunjuk kolom
                             di luar whitelist akan diam-diam jatuh ke urutan default —
                             tombol yang berpura-pura mengurutkan lebih buruk daripada
                             tidak ada tombol. */ ?>
                    <th scope="col" class="px-4 py-4">Status</th>
                    <th scope="col" class="px-4 py-4"><?= admin_sort_header('Terdaftar', 'created_at', $table, $base_url) ?></th>
                    <th scope="col" class="px-4 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users"></i>
                            </div>
                            <p>Belum ada pengguna terdaftar.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($users as $u):

                    // ---------------------------------------------------------
                    // TIGA KEADAAN AKSES. Dihitung dengan aturan yang SAMA persis
                    // seperti gerbang login (Auth::login), bukan aturan sendiri —
                    // layar ini satu-satunya tempat superadmin membaca "siapa yang
                    // masih bisa masuk", jadi begitu keduanya beda, layarnya
                    // berbohong.
                    // ---------------------------------------------------------

                    // `=== 'nonaktif'`, DITOLAK sadar: `!== 'active'`. Di DB ini ada
                    // akun berstatus `restricted` yang hari ini masuk normal —
                    // menandainya "Nonaktif" membuat superadmin mengira akses
                    // orang itu sudah dicabut padahal belum sama sekali.
                    // trim+strtolower mengikuti Auth::login baris 125.
                    $nonaktif = strtolower(trim((string) ($u->status ?? ''))) === 'nonaktif';

                    // Cukup dinilai dari locked_until di masa depan. Auth_model::is_locked()
                    // menuntut login_attempts >= 5 juga, tapi locked_until HANYA pernah
                    // ditulis di dalam cabang itu dan selalu di-NULL-kan bersama
                    // penghitungnya — dua syarat itu identik di praktik. Memanggil
                    // is_locked() langsung ditolak: view tidak menarik model, dan
                    // controller memang tidak boleh disentuh di pekerjaan ini.
                    $terkunci = ! empty($u->locked_until) && strtotime($u->locked_until) > time();

                    // Kalau keduanya benar, nonaktif yang ditampilkan. "Terkunci"
                    // menjanjikan pulih sendiri sebentar lagi; janji itu palsu untuk
                    // akun yang juga dinonaktifkan — ia tidak pulih sampai ada manusia
                    // yang mengaktifkannya kembali.

                    // Server sudah menolak tindakan pencabutan akses pada akun sendiri
                    // (Admin_Users::sasaran_sah). UI ikut tahu supaya tombol yang PASTI
                    // ditolak tidak dipajang — tombol begitu cuma memancing diklik lalu
                    // melempar pesan error yang terasa seperti kerusakan.
                    $milik_sendiri = (int) $u->id === (int) $this->session->userdata('user_id');

                    // Kalimat konfirmasi lewat json_encode, bukan html_escape: html_escape
                    // mengubah apostrof jadi &#039; yang didekode browser kembali jadi '
                    // di DALAM string JS dan mematahkan onsubmit. Email dengan apostrof
                    // itu sah menurut RFC. json_encode menghasilkan literal JS yang benar.
                    $konfirmasi_status = $nonaktif
                        ? 'Aktifkan kembali akun ' . $u->email . '? Ia bisa langsung masuk ke sistem lagi.'
                        : 'Nonaktifkan akun ' . $u->email . '? Ia langsung tidak bisa masuk lagi, dan tetap begitu sampai ada yang mengaktifkannya kembali.';
                    $konfirmasi_kunci = 'Buka kunci akun ' . $u->email . '? Ia bisa langsung mencoba masuk lagi tanpa menunggu sisa waktu kunci habis.';
                    $konfirmasi_sandi = 'Ganti password akun ' . $u->email . '? Password lamanya langsung tidak berlaku dan yang bersangkutan tidak bisa masuk sampai Anda memberitahukan password barunya.';
                ?>
                <tr x-data="{ editOpen: false, resetOpen: false, role: '<?= html_escape($u->role ?? '') ?>' }">
                    <?php /* Kolom teks terpanjang dibatasi + boleh membungkus (§17 poin 6):
                             nama dan email panjang di tabel whitespace-nowrap adalah
                             sumber meluber nomor satu. */ ?>
                    <td class="px-4 py-4 max-w-[14rem] whitespace-normal">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($u->name) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted break-words"><?= html_escape($u->email) ?></div>
                        <?php if ($milik_sendiri): ?>
                        <span class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-brand-muted/70">
                            <i class="ph ph-user-circle"></i> akun Anda
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4">
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary">
                            <?= html_escape($available_roles[$u->role] ?? ($u->role ?: '-')) ?>
                        </span>
                    </td>
                    <td class="px-4 py-4 text-xs">
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
                    <?php /* Badge memakai komponen bersama admin/components/status_badge —
                             pasangan dark:-nya sudah diurus di sana. Pemetaan ke kosakata
                             komponen: nonaktif→reject (merah), terkunci→pending (kuning:
                             amber terang / brand-primary #d6fb00 gelap), sisanya→ok (hijau). */ ?>
                    <td class="px-4 py-4">
                        <?php if ($nonaktif): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Nonaktif', 'kelas' => 'reject'], TRUE) ?>
                            <div class="mt-1 text-[11px] text-gray-500 dark:text-brand-muted">diblokir manual</div>
                        <?php elseif ($terkunci): ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Terkunci', 'kelas' => 'pending'], TRUE) ?>
                            <?php /* "Sampai kapan" wajib tampil: itu satu-satunya hal yang
                                     membedakan terkunci (sementara, pulih sendiri) dari
                                     nonaktif (keputusan manusia) di mata pembacanya. */ ?>
                            <div class="mt-1 text-[11px] text-amber-700 dark:text-brand-primary">sampai <?= html_escape(date('d M Y H:i', strtotime($u->locked_until))) ?></div>
                        <?php else: ?>
                            <?= $this->load->view('admin/components/status_badge', ['label' => 'Aktif', 'kelas' => 'ok'], TRUE) ?>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-xs"><?= html_escape(date('d M Y', strtotime($u->created_at ?? 'now'))) ?></td>
                    <td class="px-4 py-4 text-right relative">
                        <?php /* flex-wrap + max-w: tombol menumpuk ke bawah saat sempit,
                                 BUKAN melebarkan tabel. Kolom Aksi adalah yang pertama
                                 hilang di balik gulir horizontal (§17 poin 6). */ ?>
                        <div class="ml-auto flex max-w-[15rem] flex-wrap items-center justify-end gap-1">
                            <button @click="editOpen = !editOpen" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">
                                <i class="ph ph-pencil-simple"></i> Ubah Role
                            </button>

                            <?php if ($terkunci): ?>
                            <?php /* Hanya muncul kalau akun MEMANG sedang terkunci. Tombol yang
                                     selalu terpampang tapi hampir selalu tanpa efek melatih orang
                                     mengabaikan seluruh kolom ini. */ ?>
                            <form method="POST" action="<?= base_url('Admin_Users/buka_kunci') ?>" class="inline"
                                  onsubmit="return confirm(<?= html_escape(json_encode($konfirmasi_kunci)) ?>)">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <input type="hidden" name="id" value="<?= (int) $u->id ?>">
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-amber-700 dark:text-brand-primary hover:bg-amber-50 dark:hover:bg-brand-primary/10">
                                    <i class="ph ph-lock-open"></i> Buka Kunci
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php /* Reset sandi TIDAK disembunyikan untuk akun sendiri:
                                     Admin_Users::reset_sandi() memanggil sasaran_sah(TRUE) —
                                     ini memulihkan akses, bukan mencabutnya. Sama untuk Buka Kunci. */ ?>
                            <button @click="resetOpen = true" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5">
                                <i class="ph ph-key"></i> Reset Sandi
                            </button>

                            <?php if ( ! $milik_sendiri): ?>
                            <form method="POST" action="<?= base_url('Admin_Users/ubah_status') ?>" class="inline"
                                  onsubmit="return confirm(<?= html_escape(json_encode($konfirmasi_status)) ?>)">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <input type="hidden" name="id" value="<?= (int) $u->id ?>">
                                <input type="hidden" name="status" value="<?= $nonaktif ? 'active' : 'nonaktif' ?>">
                                <?php if ($nonaktif): ?>
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-500/10">
                                    <i class="ph ph-check-circle"></i> Aktifkan
                                </button>
                                <?php else: ?>
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                                    <i class="ph ph-prohibit"></i> Nonaktifkan
                                </button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </div>

                        <!-- Modal: reset sandi -->
                        <?php /* `whitespace-normal` — alasan sama dengan panel Ubah Role di
                                 atas. Modal ini `fixed`, tapi ia tetap DITULIS di dalam
                                 <td>, dan `white-space` mewaris lewat pohon DOM, bukan
                                 lewat posisi layar. Dua kolom sandinya `inline-block`. */ ?>
                        <div x-show="resetOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center whitespace-normal bg-black/50 p-4" @keydown.escape.window="resetOpen = false">
                            <div @click.outside="resetOpen = false" class="w-full max-w-md rounded-3xl bg-white dark:bg-brand-card p-6 text-left shadow-xl" x-data="{ sandi: '', ulang: '' }">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reset Password</h3>
                                <p class="mt-1 mb-4 text-xs text-gray-500 dark:text-brand-muted break-words">
                                    Untuk <span class="font-bold text-gray-700 dark:text-gray-300"><?= html_escape($u->email) ?></span>.
                                    Sampaikan password barunya lewat jalur pribadi — sistem tidak mengirimkannya ke siapa pun.
                                </p>
                                <form method="POST" action="<?= base_url('Admin_Users/reset_sandi') ?>" class="space-y-3"
                                      onsubmit="return confirm(<?= html_escape(json_encode($konfirmasi_sandi)) ?>)">
                                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $u->id ?>">
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Password Baru</label>
                                        <?php /* minlength 8 = ambang yang sama dengan reset_sandi() di server.
                                                 Sengaja cuma cermin, bukan pengganti: gerbangnya tetap di server. */ ?>
                                        <input type="password" name="password" x-model="sandi" required minlength="8" autocomplete="new-password"
                                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-bold text-gray-600 dark:text-brand-muted">Ulangi Password Baru</label>
                                        <?php /* Tanpa atribut name: konfirmasi tidak perlu sampai ke server,
                                                 dan sandi yang tidak dikirim tidak bisa bocor lewat log request. */ ?>
                                        <input type="password" x-model="ulang" required minlength="8" autocomplete="new-password"
                                               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-800 dark:text-gray-200">
                                    </div>
                                    <p x-show="ulang !== '' && sandi !== ulang" x-cloak class="text-xs font-bold text-red-600 dark:text-red-400">
                                        Kedua isian belum sama.
                                    </p>
                                    <div class="flex justify-end gap-2 pt-1">
                                        <button type="button" @click="resetOpen = false" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5">Batal</button>
                                        <button type="submit" :disabled="sandi.length < 8 || sandi !== ulang"
                                                class="px-4 py-2 rounded-xl text-sm font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover disabled:opacity-40 disabled:cursor-not-allowed">
                                            Ganti Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php /* `whitespace-normal` WAJIB, bukan kerapian.
                                 Tabel admin memakai `whitespace-nowrap` (§17 poin 6), dan
                                 `white-space` DIWARISI sampai ke sini. Kontrol form di
                                 dalam popover ini `display:inline-block`, jadi tanpa reset
                                 ini mereka tidak pernah turun baris: <select> dan tombol
                                 Simpan berjajar dalam SATU baris selebar 446px di dalam
                                 panel 256px, dan tombolnya terlempar 193px ke luar
                                 pembungkus `overflow-x-auto` — terpotong, tidak bisa
                                 diklik. Terukur di production 4 Agt 2026: tombol di
                                 x=1371 sementara panelnya berakhir di x=1385. */ ?>
                        <div x-show="editOpen" x-cloak @click.outside="editOpen = false" class="absolute right-4 top-full mt-1 z-20 w-64 whitespace-normal rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
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
