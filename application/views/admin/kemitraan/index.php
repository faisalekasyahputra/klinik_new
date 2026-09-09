<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'pendaftaran']); ?>

<?php
$this->load->helper('admin_table');
// Filter dibangun lewat admin_table_url() supaya pencarian dan urutan yang
// sedang aktif tidak hilang saat ganti filter, dan sebaliknya. Menyusun URL
// sendiri di sini akan membuang salah satunya diam-diam.
$pil = 'px-3 py-1 rounded-lg text-xs font-bold border transition-colors';
$nyala = 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary';
$padam = 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10';
ob_start(); ?>
<span class="mr-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Status:</span>
<a href="<?= admin_table_url($base_url, ['status' => NULL]) ?>" class="<?= $pil ?> <?= empty($f_status) ? $nyala : $padam ?>">Semua</a>
<?php foreach ($status_sah as $s): ?>
    <a href="<?= admin_table_url($base_url, ['status' => $s]) ?>" class="<?= $pil ?> <?= $f_status === $s ? $nyala : $padam ?>"><?= html_escape($s) ?></a>
<?php endforeach; ?>
<span class="ml-3 mr-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Jenis:</span>
<a href="<?= admin_table_url($base_url, ['jenis' => NULL]) ?>" class="<?= $pil ?> <?= empty($f_jenis) ? $nyala : $padam ?>">Semua</a>
<?php foreach ($jenis_sah as $j): ?>
    <a href="<?= admin_table_url($base_url, ['jenis' => $j]) ?>" class="<?= $pil ?> <?= $f_jenis === $j ? $nyala : $padam ?>"><?= strtoupper($j) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari mahasiswa, instansi, divisi...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4"><?= admin_sort_header('Mahasiswa', 'usr_users.name', $table, $base_url) ?></th>
                    <th class="px-4 py-4">Jenis</th>
                    <th class="px-4 py-4"><?= admin_sort_header('Instansi Asal', 'kkn_magang_pendaftaran.instansi_asal', $table, $base_url) ?></th>
                    <!-- "Divisi" dihapus dinas (konfirmasi 1 Agt 2026); kolomnya
                         memuat nama BIDANG untuk magang dan tema bebas untuk KKN. -->
                    <th class="px-4 py-4">Bidang/Tema</th>
                    <th class="px-4 py-4"><?= admin_sort_header('Tanggal Pengajuan', 'kkn_magang_pendaftaran.created_at', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Status', 'kkn_magang_pendaftaran.status', $table, $base_url) ?></th>
                    <th class="px-4 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pendaftaran KKN/Magang.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr x-data="{ procOpen: false }">
                    <td class="px-4 py-4">
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
                    <td class="px-4 py-4 uppercase text-xs font-bold"><?= html_escape($r->jenis) ?></td>
                    <!-- Dua kolom teks ini boleh membungkus. Dengan
                         `whitespace-nowrap` milik tabel, nama kampus dan nama
                         bidang yang panjang mendorong lebar tabel melewati
                         wadahnya - dan yang pertama hilang di balik gulir
                         horizontal adalah kolom AKSI, satu-satunya tempat admin
                         bisa memutuskan apa pun. Terukur 120px terpotong pada
                         viewport 1440px, 3 Agt 2026. -->
                    <td class="px-4 py-4 max-w-[14rem] whitespace-normal"><?= html_escape($r->instansi_asal) ?></td>
                    <td class="px-4 py-4 max-w-[14rem] whitespace-normal">
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
                        }
                        ?>
                        <?php foreach ($dokumen as $kunci => $d): ?>
                            <?php if ( ! empty($d[1])): ?>
                            <div class="mt-1"><a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . $r->id . '/' . $kunci) ?>" target="_blank" rel="noopener" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> <?= html_escape($d[0]) ?></a></div>
                            <?php else: ?>
                            <div class="mt-1 text-[10px] text-gray-400 dark:text-brand-muted/60">Tanpa <?= html_escape(strtolower($d[0])) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($r->jenis === 'kkn'): ?>
                            <!-- Jumlah peserta - roster diunggah universitas sendiri
                                 lewat dashboardnya (migrasi 044). Dihitung di query
                                 index() ($r->jumlah_peserta), bukan di view. Link ke
                                 daftar sebenarnya (NIM/nama) - permintaan user 22 Agt
                                 2026, sebelumnya cuma angka tanpa cara membaca isinya
                                 selain buka DB langsung. Baca saja - lihat
                                 Admin_Kemitraan::peserta(). -->
                            <div class="mt-1">
                                <a href="<?= base_url('Admin_Kemitraan/peserta/' . $r->id) ?>" class="text-[10px] font-bold text-gray-500 dark:text-brand-muted hover:text-blue-600 dark:hover:text-brand-primary hover:underline">
                                    <i class="ph ph-users"></i> <?= (int) ($r->jumlah_peserta ?? 0) ?> peserta
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-4 text-xs"><?= html_escape(date('d M Y, H:i', strtotime($r->created_at))) ?></td>
                    <td class="px-4 py-4">
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
                    <td class="px-4 py-4 text-right">
                        <!-- Tersedia pada status APA PUN: koreksi data paling sering
                             dibutuhkan justru setelah diproses, saat mahasiswa
                             mengabari NIM keliru atau periodenya bergeser. -->
                        <a href="<?= base_url('Admin_Kemitraan/ubah/' . $r->id) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">Ubah</a>
                        <?php // Dirender untuk status APA PUN, bukan cuma 'Diajukan'. Dulu
                              // keputusan yang sudah terlanjur salah tidak punya jalan
                              // pulang sama sekali - admin harus mengubahnya lewat DB. ?>
                        <?php
                        /* Dropdown DIBUNGKUS wrapper SENDIRI ("relative inline-block"),
                           BUKAN mengandalkan "relative" di <td> - keluhan user 22 Agt
                           2026 ("baris ketiga diklik, muncul di baris keempat").

                           Sebabnya: baris tabel ini TINGGINYA TIDAK SERAGAM (kolom
                           Bidang/Tema bisa memuat dua tautan surat + jumlah peserta,
                           kadang tiga baris teks, kadang satu). Sel <td> yang jadi
                           acuan "relative" DIREGANGKAN mengikuti tinggi baris oleh
                           tata letak tabel - jadi "top-full" (100%) terhitung dari
                           tinggi SEL YANG SUDAH DIREGANGKAN itu, bukan dari tinggi
                           tombolnya sendiri. Pada baris yang tinggi, itu mendorong
                           dropdown turun sejauh tinggi baris - tepat mendarat di
                           baris berikutnya, persis yang dilaporkan. Wrapper baru ini
                           setinggi tombolnya saja (inline-block, bukan sel penuh),
                           jadi "top-full" selalu 100% dari TOMBOL, berapa pun
                           tingginya baris. */
                        ?>
                        <span class="relative inline-block">
                            <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10"><?= $r->status === 'Diajukan' ? 'Proses' : 'Ubah Keputusan' ?></button>
                            <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-0 top-full mt-1 z-20 w-72 whitespace-normal rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
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
                                    array_unshift($tombol_proses, ['value' => 'Ditinjau Bidang', 'label' => 'Teruskan ke Bidang', 'style' => 'accept']);
                                    $tombol_proses[2]['label'] = 'Terima Langsung';
                                }
                                ?>
                                <?= $this->load->view('admin/components/review_form', [
                                    'action_url' => 'Admin_Kemitraan/proses/' . $r->id,
                                    'buttons' => $tombol_proses,
                                    'catatan_name' => 'catatan_admin',
                                ], TRUE) ?>
                            </div>
                        </span>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
