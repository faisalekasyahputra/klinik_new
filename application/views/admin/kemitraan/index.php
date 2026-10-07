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
                    <th class="px-4 py-3"><?= admin_sort_header('Mahasiswa', 'usr_akun.nama', $table, $base_url) ?></th>
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
                <tr x-data="{ procOpen: false }" class="align-top">
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
                    <td class="px-4 py-3 min-w-[13rem] max-w-[14rem] whitespace-normal">
                        <?php /* Dirapikan 7 Okt 2026: baris KKN dulu menjulang sebelas baris (satu baris per berkas,
                                 alasan susulan penuh). Kini empat lapis: judul, label, berkas sebagai chip, lalu
                                 peserta dan sertifikat dalam satu baris. */ ?>
                        <div class="font-semibold text-gray-900 dark:text-white"><?= html_escape($r->divisi_atau_tema ?: '-') ?></div>
                        <?php if ( ! empty($r->dicatat_oleh) || ! empty($r->alasan_susulan)): ?>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <?php if ( ! empty($r->dicatat_oleh)): ?><span class="inline-flex rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-bold uppercase text-gray-600 dark:bg-white/10 dark:text-gray-300" data-kkn-dicatat-admin>Dicatat admin</span><?php endif; ?>
                            <?php if ( ! empty($r->alasan_susulan)): ?><span data-kkn-susulan class="inline-flex rounded-md bg-amber-50 px-1.5 py-0.5 text-[11px] font-bold uppercase text-amber-800 dark:bg-amber-500/10 dark:text-amber-300" title="Periode sudah lewat saat diajukan (input susulan)">Susulan</span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ( ! empty($r->alasan_susulan)): ?>
                        <p class="mt-1 line-clamp-2 text-[11px] text-amber-700 dark:text-amber-300" title="<?= html_escape($r->alasan_susulan) ?>">Alasan susulan: <?= html_escape($r->alasan_susulan) ?></p>
                        <?php endif; ?>
                        <?php
                        // Dokumen didaftar dari satu tempat: [label chip, nama lengkap, berkas]. Surat pengantar
                        // dan proposal HANYA ADA pada magang sejak 21 Agt 2026; KKN dari dashboard universitas
                        // (migrasi 044) punya DUA surat sendiri, plus laporan akhir (migrasi 050) yang dinilai
                        // admin sebelum mengisi tanggal sertifikat.
                        $dokumen = [];
                        if ($r->jenis === 'magang') {
                            $dokumen['surat']    = ['Surat pengantar', 'Surat pengantar', $r->file_surat_pengantar ?? NULL];
                            $dokumen['proposal'] = ['Proposal', 'Proposal', $r->file_proposal ?? NULL];
                        } elseif ($r->jenis === 'kkn') {
                            $dokumen['surat']    = ['Surat mitra', 'Surat permohonan mitra', $r->file_surat_pengantar ?? NULL];
                            $dokumen['simperum'] = ['Surat SIMPERUM', 'Surat permohonan SIMPERUM', $r->file_surat_simperum ?? NULL];
                            $dokumen['laporan']  = ['Laporan akhir', 'Laporan akhir', $r->file_laporan_akhir ?? NULL];
                        }
                        $chip = 'inline-flex items-center gap-1 whitespace-nowrap rounded-md border border-gray-200 px-1.5 py-0.5 text-[11px] font-bold text-blue-600 hover:border-blue-300 dark:border-white/10 dark:text-brand-primary dark:hover:bg-white/5';
                        $ada = array_filter($dokumen, fn($d) => ! empty($d[2]));
                        $belum_ada = array_map(fn($d) => strtolower($d[1]), array_diff_key($dokumen, $ada));
                        ?>
                        <?php if ($ada || ($r->jenis === 'kkn' && ! empty($r->link_dokumentasi))): ?>
                        <div class="mt-2 flex flex-wrap gap-1" data-berkas-kemitraan>
                            <?php foreach ($ada as $kunci => $d): ?>
                                <a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . $r->id . '/' . $kunci) ?>" data-file-view data-file-title="<?= html_escape($d[1]) ?>" title="<?= html_escape($d[1]) ?>" target="_blank" rel="noopener" class="<?= $chip ?>"><i class="ph ph-paperclip" aria-hidden="true"></i><?= html_escape($d[0]) ?></a>
                            <?php endforeach; ?>
                            <?php if ($r->jenis === 'kkn' && ! empty($r->link_dokumentasi)): ?>
                                <a href="<?= html_escape($r->link_dokumentasi) ?>" target="_blank" rel="noopener noreferrer" title="Dokumentasi (cloud)" class="<?= $chip ?>"><i class="ph ph-link" aria-hidden="true"></i>Dokumentasi</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($belum_ada): ?>
                        <div class="mt-1 text-[11px] italic text-gray-400 dark:text-brand-muted">Belum ada: <?= html_escape(implode(', ', $belum_ada)) ?></div>
                        <?php endif; ?>
                        <?php if ($r->jenis === 'kkn'): ?>
                        <div class="mt-2 flex flex-wrap items-start gap-x-3 gap-y-1 text-xs">
                            <a href="<?= base_url('Admin_Kemitraan/peserta/' . $r->id) ?>" class="font-bold text-gray-500 dark:text-brand-muted hover:text-blue-600 dark:hover:text-brand-primary hover:underline"><i class="ph ph-users" aria-hidden="true"></i> <?= (int) ($r->jumlah_peserta ?? 0) ?> peserta</a>
                            <?php if ($r->status === 'Diterima'): ?>
                            <?php /* Form tanggal dilipat: terbuka hanya saat diatur, ringkasannya tetap terlihat. */ ?>
                            <details>
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
                        <?php /* Tombol ditumpuk sama lebar dan berjarak (cek visual 7 Okt 2026: dulu terlipat
                                 berlainan lebar dan saling menempel). */ ?>
                        <div class="inline-grid min-w-[9rem] gap-1.5">
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
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
