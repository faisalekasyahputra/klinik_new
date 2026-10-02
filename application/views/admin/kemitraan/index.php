<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'pendaftaran']); ?>

<?php
$this->load->helper('admin_table');
// Filter dibangun lewat admin_table_url() supaya pencarian dan urutan yang
// sedang aktif tidak hilang saat ganti filter, dan sebaliknya. Menyusun URL
// sendiri di sini akan membuang salah satunya diam-diam.
$nyala = ' aria-current="true"';
ob_start(); ?>
<span class="mr-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Status:</span>
<a href="<?= admin_table_url($base_url, ['status' => NULL]) ?>" class="chip-filter"<?= empty($f_status) ? $nyala : '' ?>>Semua</a>
<?php foreach ($status_sah as $s): ?>
    <a href="<?= admin_table_url($base_url, ['status' => $s]) ?>" class="chip-filter"<?= $f_status === $s ? $nyala : '' ?>><?= html_escape($s) ?></a>
<?php endforeach; ?>
<span class="ml-3 mr-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Jenis:</span>
<a href="<?= admin_table_url($base_url, ['jenis' => NULL]) ?>" class="chip-filter"<?= empty($f_jenis) ? $nyala : '' ?>>Semua</a>
<?php foreach ($jenis_sah as $j): ?>
    <a href="<?= admin_table_url($base_url, ['jenis' => $j]) ?>" class="chip-filter"<?= $f_jenis === $j ? $nyala : '' ?>><?= $j === 'kkn' ? 'KKN' : html_escape(ucfirst($j)) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>
<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari mahasiswa, instansi, divisi...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3"><?= admin_sort_header('Mahasiswa', 'usr_users.name', $table, $base_url) ?></th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3"><?= admin_sort_header('Instansi Asal', 'kkn_magang_pendaftaran.instansi_asal', $table, $base_url) ?></th>
                    <!-- "Divisi" dihapus dinas (konfirmasi 1 Agt 2026); kolomnya
                         memuat nama BIDANG untuk magang dan tema bebas untuk KKN. -->
                    <th class="px-4 py-3">Bidang/Tema</th>
                    <th class="px-4 py-3 whitespace-normal"><?= admin_sort_header('Tanggal Pengajuan', 'kkn_magang_pendaftaran.created_at', $table, $base_url) ?></th>
                    <th class="px-4 py-3"><?= admin_sort_header('Status', 'kkn_magang_pendaftaran.status', $table, $base_url) ?></th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pendaftaran KKN/Magang.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr x-data="{ procOpen: false }">
                    <td class="px-4 py-3 max-w-[14rem] whitespace-normal break-words">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_mahasiswa ?: '-') ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email_mahasiswa ?: '-') ?></div>
                        <?php
                        // Identitas dari migrasi 20260701000025. Baris LAMA tidak
                        // punya nilainya (kolomnya NULL demi mereka), jadi barisnya
                        // hanya muncul kalau memang terisi - bukan deretan "-"
                        // yang menyaru seperti data.
                        $identitas = array_filter([
                            $r->nim ?? NULL,
                            ($r->jurusan ?? NULL),
                            ($r->semester ?? NULL) ? 'Smt ' . (int) $r->semester : NULL,
                        ]);
                        ?>
                        <?php if ($identitas): ?>
                        <div class="mt-1 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape(implode(' · ', $identitas)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs font-bold"><?= $r->jenis === 'kkn' ? 'KKN' : html_escape(ucfirst($r->jenis)) ?></td>
                    <!-- Dua kolom teks ini boleh membungkus. Dengan
                         `whitespace-nowrap` milik tabel, nama kampus dan nama
                         bidang yang panjang mendorong lebar tabel melewati
                         wadahnya - dan yang pertama hilang di balik gulir
                         horizontal adalah kolom AKSI, satu-satunya tempat admin
                         bisa memutuskan apa pun. Dua kolom ini saja ternyata
                         belum cukup: 1 Okt 2026 masih terukur scrollWidth 1273 vs
                         clientWidth 1118 pada 1440px. Kini kolom Mahasiswa ikut
                         membungkus, judul Tanggal Pengajuan boleh dua baris, dan
                         tombol Aksi boleh bertumpuk; terukur ulang 1118 = 1118. -->
                    <td class="px-4 py-3 max-w-[14rem] whitespace-normal"><?= html_escape($r->instansi_asal) ?></td>
                    <td class="px-4 py-3 max-w-[14rem] whitespace-normal">
                        <?= html_escape($r->divisi_atau_tema ?: '-') ?>
                        <?php
                        // Dokumen didaftar dari satu tempat supaya menambah jenis
                        // berkas berikutnya tidak berarti menyalin blok <a> lagi.
                        // Surat pengantar dan proposal HANYA ADA pada magang
                        // sejak 21 Agt 2026 - KKN tidak lagi memintanya sama
                        // sekali (dulu opsional), jadi "Tanpa surat pengantar"
                        // untuk baris KKN akan salah: bukan berkas yang belum
                        // diunggah, tapi berkas yang memang tidak pernah
                        // diminta. KKN dari dashboard universitas (migrasi 044)
                        // punya DUA surat sendiri, beda nama dan beda makna.
                        $dokumen = [];
                        if ($r->jenis === 'magang') {
                            $dokumen['surat']    = ['Surat pengantar', $r->file_surat_pengantar ?? NULL];
                            $dokumen['proposal'] = ['Proposal', $r->file_proposal ?? NULL];
                        } elseif ($r->jenis === 'kkn') {
                            $dokumen['surat']    = ['Surat permohonan mitra', $r->file_surat_pengantar ?? NULL];
                            $dokumen['simperum'] = ['Surat permohonan SIMPERUM', $r->file_surat_simperum ?? NULL];
                            // Laporan akhir (migrasi 050) dinilai admin sebelum mengisi tanggal
                            // sertifikat; tanpa tautan ini hanya bisa dibuka lewat URL ketikan.
                            $dokumen['laporan']  = ['Laporan akhir', $r->file_laporan_akhir ?? NULL];
                        }
                        ?>
                        <?php /* Dokumen yang belum ada dirangkum satu baris supaya baris KKN tidak menjulang
                                 (cek visual 2 Okt 2026: tiga baris "Tanpa ..." per pendaftaran). */
                        $belum_ada = []; ?>
                        <?php foreach ($dokumen as $kunci => $d): ?>
                            <?php if ( ! empty($d[1])): ?>
                            <div class="mt-1"><a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . $r->id . '/' . $kunci) ?>" target="_blank" rel="noopener" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> <?= html_escape($d[0]) ?></a></div>
                            <?php else: $belum_ada[] = strtolower($d[0]); endif; ?>
                        <?php endforeach; ?>
                        <?php if ($belum_ada): ?>
                            <div class="mt-1 text-xs italic text-gray-400 dark:text-brand-muted">Belum ada: <?= html_escape(implode(', ', $belum_ada)) ?></div>
                        <?php endif; ?>
                        <?php if ($r->jenis === 'kkn' && $r->status === 'Diterima'): ?>
                            <?php /* Form tanggal dilipat: terbuka hanya saat diatur, ringkasannya tetap terlihat. */ ?>
                            <details class="mt-1 text-xs">
                            <summary class="cursor-pointer font-bold <?= empty($r->tanggal_sertifikat) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-brand-muted' ?>">
                                <i class="ph ph-certificate" aria-hidden="true"></i>
                                <?= empty($r->tanggal_sertifikat) ? 'Sertifikat terkunci, atur tanggal' : 'Sertifikat terbit ' . html_escape(tgl_id($r->tanggal_sertifikat, TRUE)) ?>
                            </summary>
                            <form method="POST" action="<?= base_url('Admin_Kemitraan/tanggal_sertifikat/' . (int) $r->id) ?>" class="mt-2 flex flex-wrap items-end gap-1.5">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <label class="text-xs font-bold text-gray-600 dark:text-brand-muted">Tanggal sertifikat
                                    <input type="date" name="tanggal_sertifikat" value="<?= html_escape($r->tanggal_sertifikat ?? '') ?>" class="mt-1 block rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 py-2 text-sm font-normal text-gray-800 dark:text-gray-200">
                                </label>
                                <button type="submit" class="tombol-aksi"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                            </form>
                            </details>
                        <?php endif; ?>
                        <?php if ($r->jenis === 'kkn' && ! empty($r->link_dokumentasi)): ?>
                            <div class="mt-1"><a href="<?= html_escape($r->link_dokumentasi) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-link"></i> Dokumentasi (cloud)</a></div>
                        <?php endif; ?>
                        <?php if ($r->jenis === 'kkn'): ?>
                            <!-- Jumlah peserta - roster diunggah universitas sendiri
                                 lewat dashboardnya (migrasi 044). Dihitung di query
                                 index() ($r->jumlah_peserta), bukan di view. Link ke
                                 daftar sebenarnya (NIM/nama) - permintaan user 22 Agt
                                 2026, sebelumnya cuma angka tanpa cara membaca isinya
                                 selain buka DB langsung. Baca saja - lihat
                                 Admin_Kemitraan::peserta(). -->
                            <div class="mt-1">
                                <a href="<?= base_url('Admin_Kemitraan/peserta/' . $r->id) ?>" class="text-xs font-bold text-gray-500 dark:text-brand-muted hover:text-blue-600 dark:hover:text-brand-primary hover:underline">
                                    <i class="ph ph-users"></i> <?= (int) ($r->jumlah_peserta ?? 0) ?> peserta
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-xs"><?= html_escape(tgl_id($r->created_at, TRUE, TRUE)) ?></td>
                    <td class="px-4 py-3">
                        <?php
                            // Peta status domain KKN/Magang -> kelas komponen bersama.
                            // 'Dibatalkan' datang dari mahasiswa yang menarik
                            // pendaftarannya sendiri - kuotanya sudah lepas, dan
                            // barisnya tinggal riwayat.
                            $badge_kelas = ['Diajukan' => 'pending', 'Ditinjau Bidang' => 'process',
                                                             'Diterima' => 'ok', 'Ditolak' => 'reject', 'Dibatalkan' => 'reject'];
                        ?>
                        <?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-normal">
                        <!-- Tersedia pada status APA PUN: koreksi data paling sering
                             dibutuhkan justru setelah diproses, saat mahasiswa
                             mengabari NIM keliru atau periodenya bergeser. -->
                        <a href="<?= base_url('Admin_Kemitraan/ubah/' . $r->id) ?>" class="tombol-aksi"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span></a>
                        <?php // Dirender untuk status APA PUN, bukan cuma 'Diajukan'. Dulu
                              // keputusan yang sudah terlanjur salah tidak punya jalan
                              // pulang sama sekali - admin harus mengubahnya lewat DB. ?>
                        <?php /* Modal, bukan dropdown. Dropdown absolut dulu bermasalah dua kali:
                                 mendarat di baris berikutnya pada baris tinggi (22 Agt 2026), lalu
                                 terpotong wadah overflow-x-auto (audit UI 2 Okt 2026). Modal tidak
                                 bergantung pada tinggi baris maupun wadah. Teleport ke body karena
                                 sel Aksi sticky (lihat .aksi-tetap di layouts/head.php). */ ?>
                            <button @click="procOpen = true" class="tombol-aksi"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span><?= $r->status === 'Diajukan' ? 'Proses' : 'Ubah keputusan' ?></span></button>
                            <template x-teleport="body">
                            <div x-show="procOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center whitespace-normal bg-black/50 p-4" @keydown.escape.window="procOpen = false">
                            <div @click.outside="procOpen = false" class="w-full max-w-sm rounded-3xl bg-white dark:bg-brand-card p-6 text-left shadow-xl">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= $r->status === 'Diajukan' ? 'Proses Pendaftaran' : 'Ubah Keputusan' ?></h3>
                                <p class="mt-1 mb-4 text-xs text-gray-500 dark:text-brand-muted break-words"><?= html_escape($r->nama_mahasiswa ?: '-') ?> &middot; <?= $r->jenis === 'kkn' ? 'KKN' : html_escape(ucfirst($r->jenis)) ?> &middot; <?= html_escape($r->instansi_asal) ?></p>
                                <?php
                                /* KKN TIDAK melewati meja bidang - Admin_Kemitraan::proses()
                                   sudah menolak status 'Ditinjau Bidang' untuk jenis KKN
                                   ("Pendaftaran KKN tidak melewati tinjauan bidang - putuskan
                                   langsung di sini"), tapi tombolnya dulu tetap dirender di
                                   sini untuk KEDUA jenis - keluhan user 22 Agt 2026 ("teruskan
                                   ke bidang itu bidang mana?") justru muncul dari tombol yang
                                   selalu gagal ini. Ditegakkan di SATU tempat (proses()); di
                                   sini cuma tidak lagi MENAWARKAN tombol yang pasti ditolak. */
                                $tombol_proses = [
                                    ['value' => 'Ditolak', 'label' => 'Tolak', 'style' => 'reject'],
                                    ['value' => 'Diterima', 'label' => 'Terima', 'style' => 'accept'],
                                ];
                                if ($r->jenis === 'magang') {
                                    // Jalur normal tahap satu adalah MENERUSKAN, bukan
                                    // menerima - keputusan menerima ada di meja bidang.
                                    // 'Terima langsung' tetap disediakan untuk divisi yang
                                    // bidangnya belum punya peninjau, dan diletakkan
                                    // terakhir supaya bukan yang paling mudah diklik.
                                    array_unshift($tombol_proses, ['value' => 'Ditinjau Bidang', 'label' => 'Teruskan ke bidang', 'style' => 'accept']);
                                    $tombol_proses[2]['label'] = 'Terima langsung';
                                }
                                ?>
                                <?= $this->load->view('admin/components/review_form', [
                                    'action_url' => 'Admin_Kemitraan/proses/' . $r->id,
                                    'buttons' => $tombol_proses,
                                    'catatan_name' => 'catatan_admin',
                                ], TRUE) ?>
                            </div>
                            </div>
                            </template>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
