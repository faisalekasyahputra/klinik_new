<?php
// Rantai fallback sampai email — username DAN name bisa dua-duanya NULL untuk
// akun daftar cepat lama, dan email selalu ada. Sebelumnya fallback berhenti di
// $user->name, jadi kalau itu juga NULL, JS membandingkan input (selalu string)
// dengan literal null dan tombol Hapus mustahil aktif (roadmap T5 R2-sebagian).
$current_username = htmlspecialchars($user->username ?? $user->name ?? $user->email);

// Kelas dialek admin, diangkat jadi variabel seperti perumahan_wizard.php.
// Sebelumnya string panjang yang sama ditulis ulang 15 kali di berkas ini; satu
// perubahan kerapatan berarti 15 suntingan, dan satu terlewat berarti satu
// kolom yang tingginya beda sendiri.
$kotak  = 'rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card';
$judul  = 'flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white';
$label  = 'mb-1 block text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted';
$isian  = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800'
    . ' transition-colors placeholder-gray-400 focus:border-brand-primary focus:outline-none'
    . ' focus:ring-1 focus:ring-brand-primary dark:border-white/10 dark:bg-black/20 dark:text-white'
    . ' dark:placeholder-brand-muted/60';
$isian_mati = 'w-full cursor-not-allowed rounded-lg border border-gray-200 bg-gray-100 px-3 py-2'
    . ' text-sm text-gray-500 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted';
$tombol = 'inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold'
    . ' text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary'
    . ' dark:text-brand-dark dark:hover:bg-brand-hover';
$petunjuk = 'mt-1 text-xs text-gray-500 dark:text-brand-muted';
?>
<div class="relative z-10 space-y-3">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-lg text-brand-primary">
                <i class="ph ph-user-circle"></i>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-gray-900 dark:text-white md:text-2xl">Profil Saya</h1>
                <p class="text-xs text-gray-500 dark:text-brand-muted">Kelola data pribadi dan preferensi akun Anda.</p>
            </div>
        </div>
        <a href="<?= base_url('Auth/logout') ?>" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-bold text-red-600 transition-colors hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
            <i class="ph ph-sign-out"></i> <span>Keluar</span>
        </a>
    </div>

    <?php
        // Flash success/error sudah dirender shell admin/index.php sebelum $content
        // disuntikkan — blok di sini dulu merender ulang pesan yang sama (bug B5,
        // sama seperti pages/pengaturan/index.php).
    ?>

    <?php
    // Riwayat keluhan "hanya tampak separo" di halaman ini ada DUA, dan yang
    // pertama sempat saya kira sudah menutup yang kedua:
    //
    //   1. Seluruh halaman dikunci `max-w-2xl` — isinya berhenti di tengah
    //      layar. Sudah dicabut.
    //   2. Diganti grid tiga kolom: formulir dua kolom, Zona Berbahaya satu.
    //      Masih terlihat separo, karena kotak merah itu pendek — sisa kolom
    //      kanannya kosong, dan `align-items: stretch` bawaan grid malah
    //      meregangkannya jadi ~240px blok merah melompong.
    //
    // Akarnya bukan tinggi kotak, tapi membagi lebar untuk isi yang tidak
    // sebanding. Sekarang tanpa kolom sama sekali: formulir memakai lebar
    // penuh dengan medannya yang melebar (empat kolom di layar lebar), dan
    // Zona Berbahaya jadi bilah di bawah — tempat yang lebih jujur untuk
    // tindakan yang jarang dipakai dan tidak bisa dibatalkan, alih-alih blok
    // merah sebesar formulir di sudut kanan atas.
    ?>
    <div class="space-y-3">

        <!-- Informasi Profil -->
        <div class="<?= $kotak ?>">
            <h2 class="<?= $judul ?>">
                <i class="ph ph-identification-card text-brand-primary"></i> Informasi Profil
            </h2>

            <form action="<?= base_url('akun/update') ?>" method="POST" class="mt-3 space-y-3">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="<?= $label ?>">Email <span class="font-normal normal-case">(tidak dapat diubah)</span></label>
                        <input type="email" value="<?= htmlspecialchars($user->email) ?>" disabled class="<?= $isian_mati ?>">
                    </div>

                    <div>
                        <label class="<?= $label ?>">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user->username ?? '') ?>" required maxlength="30" pattern="^\S+$"
                               oninput="this.value = this.value.replace(/\s/g, '').toLowerCase()"
                               class="<?= $isian ?>">
                        <p class="<?= $petunjuk ?>">Tampil di forum diskusi. Tanpa spasi, maks. 30 karakter.</p>
                    </div>

                    <div>
                        <label class="<?= $label ?>">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required class="<?= $isian ?>">
                    </div>

                    <div>
                        <label class="<?= $label ?>">No. WhatsApp</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>" class="<?= $isian ?>">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 dark:border-white/5">
                    <p class="text-sm font-bold text-gray-800 dark:text-white">Ganti Password</p>
                    <p class="<?= $petunjuk ?>">Kosongkan kalau tidak ingin mengubah. Minimal 8 karakter, ada huruf besar, angka, dan simbol.</p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <input type="password" name="password" autocomplete="new-password" placeholder="Password baru" class="<?= $isian ?>">
                        <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Ulangi password baru" class="<?= $isian ?>">
                        <input type="password" name="current_password" autocomplete="current-password" placeholder="Password Anda saat ini" class="<?= $isian ?>">
                    </div>
                </div>

                <button type="submit" class="<?= $tombol ?>">
                    <i class="ph ph-floppy-disk"></i> Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Zona Berbahaya -->
        <div class="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 lg:flex-row lg:items-center lg:justify-between dark:border-red-500/20 dark:bg-red-500/5">
            <div class="min-w-0">
            <h2 class="flex items-center gap-2 text-base font-bold text-red-600 dark:text-red-400">
                <i class="ph ph-warning"></i> Zona Berbahaya
            </h2>
            <!--
              S4 — teks ini DILURUSKAN 29 Jul 2026 agar cocok dengan yang
              benar-benar terjadi. Sebelumnya tombolnya berbunyi "Hapus Akun
              Secara Permanen" dan keterangannya hanya menyebut profil serta
              forum, sehingga pengguna wajar menyimpulkan seluruh datanya
              lenyap. Kenyataannya `User_model::delete_user_account()` hanya
              menghapus akun, menganonimkan forum, dan menyapu dokumen SRP2;
              data pendataan Warga (profil, penilaian, snapshot SIMPERUM, dan
              foto bukti) TETAP ADA karena FK pemiliknya `SET NULL`.
              Pembersihannya sendiri menunggu kebijakan retensi (keputusan #9);
              sampai itu turun, yang bisa segera diperbaiki adalah janjinya —
              bukan diam-diam membiarkannya salah.
            -->
            <p class="mt-2 text-xs text-gray-600 dark:text-brand-muted">Menghapus akun akan menghapus <b>akun dan akses masuk Anda</b>, serta dokumen SRP2 yang pernah Anda unggah. Diskusi dan komentar tidak dihapus, melainkan dianonimkan menjadi "Akun Dihapus" agar alur diskusi tidak rusak. Tindakan ini tidak bisa dibatalkan.</p>

            <p class="mt-1 text-xs text-gray-600 dark:text-brand-muted">Data layanan yang pernah Anda kirimkan — data pendataan perumahan, hasil penilaian, dan foto bukti — <b>tidak ikut terhapus saat ini</b> dan mengikuti kebijakan retensi data yang berlaku. Untuk meminta penghapusannya, hubungi admin.</p>

            </div>

            <button type="button" onclick="openDeleteModal()" class="shrink-0 inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-100 px-4 py-2 text-sm font-bold text-red-600 transition-colors hover:bg-red-200 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20">
                <i class="ph ph-trash"></i> Hapus Akun Saya
            </button>
        </div>

        <?php if (isset($pengajuan_sp2)): ?>
        <!-- SP2 Status & Data Pengembang. Selebar penuh: sub-grid isinya sampai
             tiga kolom, dan memaksanya masuk sepertiga lebar membuatnya
             bertumpuk jadi satu kolom sempit. -->
        <div class="<?= $kotak ?>">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="<?= $judul ?>">
                    <i class="ph ph-certificate text-brand-primary"></i> Sertifikasi Pengembang (SRP2)
                </h2>
                <?php
                // Draft + ada catatan = pengajuan DIBUKA KEMBALI admin lewat
                // "Minta Perbaikan", bukan draft yang belum pernah dikirim —
                // pembedaan yang sama persis dengan /akun (Pengaturan::index()).
                // Sebelumnya halaman ini menampilkan "Lengkapi Dokumen" polos
                // untuk keduanya, dan NOL keterangan, padahal flash ke admin
                // berbunyi "Pengembang melihat catatan Anda di dashboardnya".
                $st = $pengajuan_sp2->status_verifikasi;
                $ada_catatan = !empty($pengajuan_sp2->catatan_admin);
                $perlu_perbaikan = ($st == 'Draft' && $ada_catatan);

                // Status tak dikenal ditampilkan APA ADANYA. Cabang else dulu
                // mencapnya "Ditolak" — mencap sesuatu sebagai penolakan yang
                // bukan penolakan itu kebohongan di layar, sekategori dengan
                // pesan sukses karangan (§0d).
                $badge = [
                    'Pending'  => ['Dalam Peninjauan',  'amber', 'ph-clock'],
                    'Draft'    => ['Lengkapi Dokumen',  'cyan',  'ph-pencil-simple'],
                    'Diterima' => ['Diterima',          'green', 'ph-check-circle'],
                    'Ditolak'  => ['Ditolak',           'red',   'ph-x-circle'],
                ][$st] ?? [$st, 'gray', 'ph-question'];
                if ($perlu_perbaikan) { $badge = ['Perlu Diperbaiki', 'amber', 'ph-pencil-simple']; }

                $warna = [
                    'amber' => 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/30 text-amber-700 dark:text-amber-400',
                    'cyan'  => 'bg-cyan-50 dark:bg-cyan-500/10 border-cyan-200 dark:border-cyan-500/20 text-cyan-700 dark:text-cyan-400',
                    'green' => 'bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-400',
                    'red'   => 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400',
                    'gray'  => 'bg-gray-100 dark:bg-white/5 border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted',
                ][$badge[1]];
                ?>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wider <?= $warna ?>">
                    <i class="ph <?= $badge[2] ?> text-xs"></i> <?= htmlspecialchars($badge[0]) ?>
                </span>
            </div>

            <?php // Catatan admin ditampilkan untuk KEDUA keputusan yang mengembalikan
                  // pekerjaan ke pemohon — Ditolak maupun Minta Perbaikan. Dulu hanya
                  // Ditolak, sehingga keputusan ketiga tidak punya keterangan sama sekali. ?>
            <?php if($ada_catatan && in_array($st, ['Ditolak', 'Draft'], TRUE)): ?>
            <?php $ck = $perlu_perbaikan
                ? ['bg-amber-50 dark:bg-amber-500/5 border-amber-200 dark:border-amber-500/20', 'text-amber-700 dark:text-amber-400', 'Perbaikan Diminta Admin']
                : ['bg-red-50 dark:bg-red-500/5 border-red-200 dark:border-red-500/20', 'text-red-600 dark:text-red-400', 'Catatan Koreksi Admin']; ?>
            <div class="<?= $ck[0] ?> mt-4 rounded-xl border p-4 text-xs text-gray-600 dark:text-brand-muted sm:text-sm">
                <strong class="<?= $ck[1] ?> mb-1 block"><i class="ph ph-warning mr-1"></i> <?= $ck[2] ?>:</strong>
                <?= htmlspecialchars($pengajuan_sp2->catatan_admin) ?>
            </div>
            <?php endif; ?>

            <div class="mt-4 flex flex-wrap items-start gap-3 border-b border-gray-100 pb-4 dark:border-white/10">
                <?php
                // Tautan profil publik memakai certified_developer_id (PK direktori),
                // BUKAN id pengajuan. Dulu memakai $pengajuan_sp2->id — dua tabel,
                // dua urutan ID: registrasi id=7 membuka profil perusahaan LAIN,
                // lengkap dengan badge "Bersertifikat".
                //
                // Dan tombolnya TIDAK dirender kalau kolomnya NULL. Dulu dirender
                // untuk semua status Diterima, jadi tidak ada jalan BENAR sama
                // sekali dari dashboard ke profil publik.
                ?>
                <?php if($pengajuan_sp2->status_verifikasi == 'Diterima' && !empty($pengajuan_sp2->certified_developer_id)): ?>
                    <a href="<?= base_url('Pengembang/profil/' . (int) $pengajuan_sp2->certified_developer_id) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-brand-primary/30 bg-brand-primary/10 px-4 py-2 text-sm font-bold text-brand-hover transition-colors hover:bg-brand-primary/20 dark:text-brand-primary">
                        <i class="ph ph-eye"></i> Lihat Profil Publik
                    </a>
                    <div>
                        <button type="button" disabled title="Sertifikat digital belum tersedia untuk diunduh" class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-bold text-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-brand-muted/60">
                            <i class="ph ph-download"></i> Download Sertifikat
                        </button>
                        <p class="<?= $petunjuk ?>">Sertifikat digital belum tersedia untuk diunduh — proses penerbitan sedang disiapkan Admin Disperakim Jateng.</p>
                    </div>
                <?php elseif($pengajuan_sp2->status_verifikasi == 'Diterima'): ?>
                    <?php // Diterima TAPI belum tertaut ke direktori publik. Sesudah
                          // backfill (migrasi 20260701000016) ini seharusnya tidak
                          // terjadi lagi — tapi kalau terjadi, katakan apa adanya,
                          // jangan tampilkan pesan "menunggu Diterima" kepada orang
                          // yang pengajuannya SUDAH diterima. ?>
                    <p class="text-xs text-amber-700 dark:text-amber-400">Pengajuan Anda sudah diterima, tetapi belum terbit di direktori pengembang publik. Silakan hubungi Admin Disperakim Jateng untuk penerbitannya.</p>
                <?php else: ?>
                    <p class="text-xs text-gray-500 dark:text-brand-muted">Download sertifikat akan tersedia setelah pengajuan Anda dinyatakan <strong class="text-green-600 dark:text-green-400">Diterima</strong>.</p>
                <?php endif; ?>
            </div>

            <h3 class="mt-4 text-sm font-bold text-gray-700 dark:text-gray-300">Edit Data Perusahaan</h3>
            <form action="<?= base_url('akun/update_pengembang') ?>" method="POST" class="mt-3 space-y-4">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="<?= $label ?>">Nama Perusahaan</label>
                        <input type="text" name="nama_perusahaan" value="<?= htmlspecialchars($pengajuan_sp2->nama_perusahaan) ?>" required class="<?= $isian ?> uppercase">
                    </div>
                    <div>
                        <label class="<?= $label ?>">Alamat Kantor</label>
                        <textarea name="alamat_kantor" rows="1" required class="<?= $isian ?> resize-none"><?= htmlspecialchars($pengajuan_sp2->alamat_kantor) ?></textarea>
                    </div>
                    <div>
                        <label class="<?= $label ?>">Asosiasi</label>
                        <select name="asosiasi" required class="<?= $isian ?>">
                            <?php $asosiasi_labels = ['rei' => 'REI', 'himperra' => 'HIMPERRA', 'apersi' => 'APERSI', 'pi' => 'PI', 'lainnya' => 'Lainnya']; ?>
                            <?php foreach ($asosiasi_labels as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $pengajuan_sp2->asosiasi === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="<?= $label ?>">No. Keanggotaan</label>
                        <input type="text" name="no_keanggotaan" value="<?= htmlspecialchars($pengajuan_sp2->no_keanggotaan) ?>" required class="<?= $isian ?>">
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 dark:border-white/5">
                    <p class="text-sm font-bold text-gray-800 dark:text-white">Kontak Publik</p>
                    <p class="<?= $petunjuk ?>">Ditampilkan di halaman profil pengembang.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="<?= $label ?>">Instagram</label>
                            <input type="text" name="instagram" value="<?= htmlspecialchars($pengajuan_sp2->instagram ?? '') ?>" placeholder="https://instagram.com/..." class="<?= $isian ?>">
                        </div>
                        <div>
                            <label class="<?= $label ?>">Website</label>
                            <input type="text" name="website" value="<?= htmlspecialchars($pengajuan_sp2->website ?? '') ?>" placeholder="https://..." class="<?= $isian ?>">
                        </div>
                        <div>
                            <label class="<?= $label ?>">Sosmed Lainnya</label>
                            <input type="text" name="sosmed_lainnya" value="<?= htmlspecialchars($pengajuan_sp2->sosmed_lainnya ?? '') ?>" placeholder="Facebook, WhatsApp Business, dst." class="<?= $isian ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="<?= $tombol ?>">
                    <i class="ph ph-floppy-disk"></i> Simpan Data Pengembang
                </button>
            </form>
        </div>
        <?php endif; ?>

        <?php if (($user->role ?? '') === 'pengembang' && !isset($pengajuan_sp2)): ?>
        <div class="<?= $kotak ?>">
            <h2 class="<?= $judul ?>">Lengkapi Pengajuan SRP2</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-brand-muted">Akun pengembang sudah aktif. Lengkapi profil pengajuan sebelum mengunggah dokumen persyaratan.</p>
            <a href="<?= base_url('Pengembang/formulir') ?>" class="<?= $tombol ?> mt-4"><i class="ph ph-arrow-right"></i> Lengkapi Profil SRP2</a>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center px-4">
    <div class="bg-white dark:bg-brand-card rounded-2xl border border-red-200 dark:border-red-500/30 p-6 max-w-md w-full shadow-2xl relative">
        <button type="button" onclick="closeDeleteModal()" class="absolute top-4 right-4 text-gray-400 dark:text-brand-muted hover:text-gray-700 dark:hover:text-white transition-colors">
            <i class="ph ph-x text-xl"></i>
        </button>

        <div class="w-12 h-12 bg-red-100 dark:bg-red-500/10 rounded-full flex items-center justify-center text-red-500 dark:text-red-400 mb-4 mx-auto">
            <i class="ph ph-warning text-2xl"></i>
        </div>

        <h3 class="text-lg font-bold text-center text-gray-900 dark:text-white mb-2">Konfirmasi Hapus Akun</h3>
        <p class="text-sm text-gray-500 dark:text-brand-muted text-center mb-5">
            Tindakan ini sangat berbahaya dan tidak dapat dibatalkan. Ketik <strong class="text-red-600 dark:text-red-400 select-all"><?= $current_username ?></strong> di bawah ini untuk mengonfirmasi.
        </p>

        <form action="<?= base_url('akun/delete') ?>" method="POST" id="deleteForm">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="mb-3">
                <input type="text" id="confirmDeleteInput" autocomplete="off" onkeyup="checkDeleteConfirm()" placeholder="Ketik nama akun Anda"
                       class="<?= $isian ?> text-center focus:border-red-500 focus:ring-red-500">
            </div>

            <!-- Ketik-nama cuma friksi UI (dan bisa ditembus siapa pun yang
                 sekadar memakai sesi ini) — bukti kepemilikan yang sebenarnya
                 adalah password, diverifikasi SERVER di Pengaturan::delete_account()
                 (roadmap T5 S13). -->
            <div class="mb-5">
                <input type="password" name="current_password" autocomplete="current-password" placeholder="Password Anda saat ini" required
                       class="<?= $isian ?> focus:border-red-500 focus:ring-red-500">
            </div>

            <button type="submit" id="btnConfirmDelete" disabled
                    class="w-full bg-gray-100 dark:bg-white/5 text-gray-400 dark:text-brand-muted/60 font-bold py-2.5 rounded-lg cursor-not-allowed transition-colors">
                Hapus Akun Saya
            </button>
        </form>
    </div>
</div>

<script>
const targetUsername = <?= json_encode($user->username ?? $user->name ?? $user->email) ?>;

function openDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('confirmDeleteInput').value = '';
    document.getElementById('confirmDeleteInput').focus();
    checkDeleteConfirm();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function checkDeleteConfirm() {
    const input = document.getElementById('confirmDeleteInput').value;
    const btn = document.getElementById('btnConfirmDelete');

    if (input === targetUsername) {
        btn.disabled = false;
        btn.classList.remove('bg-gray-100', 'dark:bg-white/5', 'text-gray-400', 'dark:text-brand-muted/60', 'cursor-not-allowed');
        btn.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
    } else {
        btn.disabled = true;
        btn.classList.add('bg-gray-100', 'dark:bg-white/5', 'text-gray-400', 'dark:text-brand-muted/60', 'cursor-not-allowed');
        btn.classList.remove('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
    }
}
</script>
