<?php
/**
 * Papan cakupan Rekam Data - 35 kabupaten × 2 domain, satu triwulan.
 *
 * Barisnya SELALU 35, ada laporannya atau tidak. Layar rekam data lain
 * menampilkan daftar laporan; yang ini menampilkan daftar KABUPATEN, karena
 * yang ditanyakan dinas adalah siapa yang BELUM - dan yang belum tidak punya
 * baris laporan untuk ditampilkan.
 */
$this->load->helper('admin_table');
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];

// Warna ditulis UTUH per keadaan, bukan dirakit dari potongan nama kelas -
// Tailwind di proyek ini sebagian datang dari CSS statis hasil panen, dan kelas
// yang baru lahir saat render tidak pernah ikut terpanen.
$gaya = [
    'belum'     => 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-brand-muted',
    'draft'     => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'menunggu'  => 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-300',
    'perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
    'diterima'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
];
// Urutan rincian ringkasan: yang sudah masuk dulu, yang belum di akhir. Label sama dengan sel tabel.
$keadaan_label = ['diterima' => 'diterima', 'menunggu' => 'menunggu ditinjau', 'perbaikan' => 'perlu perbaikan',
                  'draft' => 'draft, belum dikirim', 'belum' => 'belum ada laporan'];
$tautan = static function ($t, $tw) {
    return base_url('Admin_Rekam_Data?tahun=' . (int) $t . '&triwulan=' . (int) $tw);
};

// Butir cetak (17 Agt 2026) - lihat penjelasan lengkap di partial-nya.
$this->load->view('admin/layouts/cetak_rekap');
?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Cakupan pelaporan seluruh kabupaten/kota untuk satu triwulan. Super admin dapat meninjau laporan
        (terima atau minta perbaikan) dan mengisi laporan atas nama kabupaten mana pun.']); ?>

<?php // Ringkasan didahulukan: "berapa yang belum" adalah pertanyaannya, bukan detail per baris. ?>
<div class="tumpuk-bagian">
<div class="grid-kartu grid sm:grid-cols-2">
    <?php foreach ($domain as $kode => $nama):
        $r = $ringkas[$kode] ?? [];
    ?>
    <div class="kartu-admin isi-kartu">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="text-sm font-black text-gray-900 dark:text-white"><?= html_escape($nama) ?></span>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-brand-muted">kab/kota sudah mengirim laporan <?= html_escape($nama_tw[$triwulan] ?? '') ?> <?= (int) $tahun ?></p>
            </div>
            <span class="text-2xl font-black text-gray-900 dark:text-white"><?= (int) ($r['masuk'] ?? 0) ?><span class="text-sm font-bold text-gray-400 dark:text-brand-muted">/<?= (int) ($r['total'] ?? 0) ?></span></span>
        </div>
        <?php // Rincian berwarna sama dengan label di tabel, sekaligus jadi keterangan warnanya; jumlahnya selalu = total. ?>
        <ul class="mt-3 flex flex-wrap gap-1.5" data-rincian-pantau>
            <?php foreach ($keadaan_label as $k => $label): ?>
            <li class="rounded-full px-2.5 py-1 text-[11px] font-bold <?= $gaya[$k] ?>"><?= (int) ($r[$k] ?? 0) ?> <?= html_escape($label) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</div>

<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/5">
        <span class="text-xs font-bold text-gray-500 dark:text-brand-muted mr-1">Tahun:</span>
        <?php
        // Tahun berjalan selalu ikut, walau belum ada satu pun laporannya -
        // kalau tidak, tahun baru tidak punya jalan masuk sampai ada yang melapor.
        $tahun_pilihan = array_values(array_unique(array_merge([date('Y')], $tahun_ada, [$tahun])));
        rsort($tahun_pilihan);
        foreach ($tahun_pilihan as $t): ?>
        <a href="<?= $tautan($t, $triwulan) ?>" class="chip-filter"<?= (int) $t === (int) $tahun ? ' aria-current="true"' : '' ?>><?= (int) $t ?></a>
        <?php endforeach; ?>

        <span class="ml-3 text-xs font-bold text-gray-500 dark:text-brand-muted mr-1">Triwulan:</span>
        <?php foreach ($nama_tw as $n => $label): ?>
        <a href="<?= $tautan($tahun, $n) ?>" class="chip-filter"<?= (int) $n === (int) $triwulan ? ' aria-current="true"' : '' ?>><?= html_escape($label) ?></a>
        <?php endforeach; ?>

        <?php /* Cari kabupaten/kota: disaring di peramban karena barisnya selalu 35 (tidak berhalaman),
                 jadi tidak perlu bolak-balik ke server. Nomor baris ikut menyesuaikan karena counter CSS
                 tidak menghitung baris yang disembunyikan. */ ?>
        <label class="relative ml-3">
            <span class="sr-only">Cari kabupaten/kota</span>
            <i class="ph ph-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input type="search" placeholder="Cari kabupaten/kota" data-cari-pantau autocomplete="off"
                   class="h-8 w-56 rounded-lg border border-gray-300 bg-white pl-8 pr-2 text-sm text-gray-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-white/25 dark:bg-black/40 dark:text-white dark:focus:border-brand-primary dark:focus:ring-brand-primary/30"
                   oninput="var q=this.value.trim().toLowerCase(),n=0;this.closest('[data-tabel-admin]').querySelectorAll('tbody tr[data-nama]').forEach(function(r){var ok=r.dataset.nama.indexOf(q)!==-1;r.hidden=!ok;if(ok)n++;});var k=this.closest('[data-tabel-admin]').querySelector('[data-cari-kosong]');if(k)k.hidden=n>0;">
        </label>

        <?php /* Tombol unduh HANYA muncul kalau memang ada baris kabupaten -
                 pola yang sama dengan Rekam_Perumahan/Rekam_Kawasan. Permintaan
                 user 17 Agt 2026: "bisa diexport ke excel atau pdf di
                 breakdown ke per tw dan per tahun". Screenshot ini punya
                 dua sasaran: layar per-kabupaten (Excel sudah ada sejak
                 butir 23 putaran 2, lihat perumahan_capaian.php) dan layar
                 lintas-kabupaten INI, yang sebelumnya tidak punya unduhan
                 sama sekali. */ ?>
        <?php if ( ! empty($baris)): ?>
        <a href="<?= base_url('Admin_Rekam_Data/export?tahun=' . (int) $tahun . '&triwulan=' . (int) $triwulan) ?>" download
           class="tombol-kedua ml-auto">
            <i class="ph ph-download-simple" aria-hidden="true"></i><span>Unduh Excel</span>
        </a>
        <a href="<?= base_url('Admin_Rekam_Data/export?periode=tahun&tahun=' . (int) $tahun) ?>" download
           class="tombol-kedua">
            <i class="ph ph-calendar-blank" aria-hidden="true"></i><span>Unduh setahun</span>
        </a>
        <button type="button" onclick="window.print()"
           class="tombol-kedua">
            <i class="ph ph-printer" aria-hidden="true"></i><span>Cetak</span>
        </button>
        <?php endif; ?>
    </div>

    <?php /* 35 baris tetap: tabel digulir DI DALAM kartu, bukan seluruh halaman. Tinggi = layar dikurangi
             topbar, toolbar kartu, dan footer, supaya toolbar tetap terlihat, judul kolom menempel, dan
             baris terakhir tidak pernah tertutup footer. thead berlatar padat (bukan transparan) agar
             baris yang lewat di bawahnya tidak tembus pandang. */ ?>
    <div class="max-h-[calc(100vh-15rem)] overflow-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="sticky top-0 z-10 bg-gray-100 text-gray-600 shadow-[0_1px_0_rgba(0,0,0,.08)] dark:bg-[#0b222b] dark:text-brand-muted dark:shadow-[0_1px_0_rgba(255,255,255,.08)] text-xs font-bold">
                <tr>
                    <th class="px-3 py-2">Kabupaten / Kota</th>
                    <?php foreach ($domain as $nama): ?>
                    <th class="px-3 py-2"><?= html_escape($nama) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($baris)): ?>
                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Tabel kabupaten kosong - seed wilayah belum jalan.</td></tr>
                <?php else: foreach ($baris as $kab_id => $b): ?>
                <tr data-nama="<?= html_escape(strtolower($b['kabupaten'])) ?>">
                    <td class="px-3 py-1.5 font-bold text-gray-900 dark:text-white"><?= html_escape($b['kabupaten']) ?></td>
                    <?php foreach (array_keys($domain) as $kode):
                        $sel = $b[$kode] ?? NULL;
                        $keadaan = $sel['keadaan'] ?? 'belum';
                    ?>
                    <td class="px-3 py-1.5">
                      <div class="flex items-center gap-1.5">
                        <span class="inline-block min-w-[8.5rem] rounded-md px-2 py-0.5 text-center text-[11px] font-bold <?= $gaya[$keadaan] ?? $gaya['belum'] ?>">
                            <?= html_escape($sel['keadaan_label'] ?? 'Belum ada laporan') ?>
                        </span>
                        <?php // Draft belum dikirim = isian setengah jadi milik kab/kota; superadmin melihat yang sudah dikirim saja. ?>
                        <?php if ( ! empty($sel['laporan_id']) && $keadaan !== 'draft'): ?>
                        <a href="<?= base_url('Admin_Rekam_Data/detail/' . (int) $sel['laporan_id']) ?>" class="tombol-aksi"><i class="ph ph-eye" aria-hidden="true"></i><span>Lihat</span></a>
                        <?php endif; ?>
                        <?php // Mengisi atas nama kabupaten: POST (mengubah sesi), kabupaten divalidasi di server. ?>
                        <form method="post" action="<?= base_url('Admin_Rekam_Data/bertindak') ?>" class="inline">
                            <input type="hidden" name="<?= html_escape($csrf_name) ?>" value="<?= html_escape($csrf_hash) ?>">
                            <input type="hidden" name="kabupaten_id" value="<?= (int) $kab_id ?>">
                            <input type="hidden" name="domain" value="<?= html_escape($kode) ?>">
                            <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
                            <input type="hidden" name="triwulan" value="<?= (int) $triwulan ?>">
                            <button class="tombol-aksi"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Isi</span></button>
                        </form>
                      </div>
                        <?php if ($keadaan === 'perbaikan' && ! empty($sel['catatan_admin'])): ?>
                        <div class="mt-0.5 max-w-[220px] truncate text-[10px] text-gray-500 dark:text-brand-muted" title="<?= html_escape($sel['catatan_admin']) ?>">
                            <?= html_escape($sel['catatan_admin']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php // Di luar <table>: tabel ini dijaga tepat 35 baris kabupaten. ?>
        <p data-cari-kosong hidden class="px-3 py-8 text-center text-sm text-gray-500 dark:text-brand-muted">Tidak ada kabupaten/kota yang cocok dengan pencarian.</p>
    </div>
</div>
</div>
