<?php
/**
 * Pusat Pemberitahuan: baris yang membentuk angka merah di sidebar, per modul (Pemberitahuan::index).
 *
 * Jumlah tiap bagian = pending_modul_baris()['total'], definisi yang sama dengan badge sidebar.
 * Yang ditampilkan per baris HANYA penanda non-pribadi dari 'tindakan' registry (kode tiket atau
 * nomor), tanggal masuk, dan umurnya. Nama, NIK, kontak, dan isi aduan tetap di halaman modulnya,
 * yang punya guard, scope, penyamaran B2, dan pencatatan akses data pribadinya sendiri.
 */
$this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Pekerjaan yang membentuk angka merah di menu samping, per modul. Angka setiap bagian sama dengan angka di menu; identitas pemohon hanya dibuka di halaman modulnya.']);

$label_keterangan = ['bidang_kode' => 'Bidang', 'jenis' => 'Jenis'];
$isi_keterangan = static function ($kolom, $nilai) {
    if ($kolom === 'bidang_kode') { return $nilai === NULL || $nilai === '' ? 'Belum diteruskan' : ucwords(str_replace('_', ' ', $nilai)); }
    if ($kolom === 'jenis') { return $nilai === 'kkn' ? 'KKN' : ucfirst((string) $nilai); }
    return (string) $nilai;
};
$hari_ini = strtotime(date('Y-m-d'));
?>

<?php if (empty($modul_pemberitahuan)): ?>
<div class="kartu-admin isi-kartu text-center" data-kosong-pemberitahuan>
    <i class="ph ph-check-circle text-3xl text-green-600" aria-hidden="true"></i>
    <p class="mt-2 font-bold text-gray-900 dark:text-white">Tidak ada yang menunggu tindakan.</p>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Semua antrean di modul Anda sudah diproses. Halaman ini terisi lagi begitu ada pengajuan baru.</p>
</div>
<?php else: ?>

<nav class="mb-5 flex flex-wrap items-center gap-2" aria-label="Lompat ke modul">
    <?php foreach ($modul_pemberitahuan as $mp): ?>
    <a href="#modul-<?= html_escape($mp['key']) ?>" class="chip-filter" data-no-page-transition><?= html_escape($mp['modul']['label']) ?> (<?= angka_id($mp['total']) ?>)</a>
    <?php endforeach; ?>
</nav>

<?php foreach ($modul_pemberitahuan as $mp):
    $key = $mp['key'];
    $m = $mp['modul'];
    $t = $m['tindakan'] ?? [];
    $semua = base_url($m['overview_url'] ?? $m['url']);
    $kolom_ket = $t['keterangan'] ?? NULL;
    $kolom_penanda = $t['penanda'] ?? NULL;
?>
<section id="modul-<?= html_escape($key) ?>" data-modul-pemberitahuan="<?= html_escape($key) ?>" data-jumlah="<?= (int) $mp['total'] ?>" class="kartu-admin overflow-hidden mb-5">
    <?php $this->load->view('admin/components/kepala_tabel', [
        'kt_judul' => $m['label'], 'kt_jumlah' => $mp['total'],
        'kt_keterangan' => '<a href="' . html_escape($semua) . '" class="tombol-kedua" data-tautan-modul><i class="ph ph-arrow-square-out" aria-hidden="true"></i><span>Buka modul</span></a>',
    ]); ?>
    <?php if ( ! empty($t['cara'])): ?>
    <p class="px-4 pt-3 text-sm text-gray-600 dark:text-brand-muted"><b class="text-gray-900 dark:text-white">Cara menyelesaikan:</b> <?= html_escape($t['cara']) ?></p>
    <?php endif; ?>
    <div class="overflow-x-auto aksi-tetap mt-3">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold">
                <tr>
                    <th class="px-4 py-3">Penanda</th>
                    <?php if ($kolom_ket): ?><th class="px-4 py-3"><?= html_escape($label_keterangan[$kolom_ket] ?? 'Keterangan') ?></th><?php endif; ?>
                    <th class="px-4 py-3">Masuk</th>
                    <th class="px-4 py-3">Menunggu</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-600 dark:text-brand-muted">
                <?php foreach ($mp['baris'] as $b):
                    $id = (int) $b['id'];
                    $penanda = $kolom_penanda && ($b[$kolom_penanda] ?? '') !== '' ? $b[$kolom_penanda] : '#' . $id;
                    $hari = (int) floor(($hari_ini - strtotime(date('Y-m-d', strtotime($b['created_at'])))) / 86400);
                    $tujuan = ! empty($t['detail']) ? base_url($t['detail'] . $id) : $semua;
                ?>
                <tr>
                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($penanda) ?></td>
                    <?php if ($kolom_ket): ?><td class="px-4 py-3"><?= html_escape($isi_keterangan($kolom_ket, $b[$kolom_ket] ?? NULL)) ?></td><?php endif; ?>
                    <td class="px-4 py-3 text-xs"><?= html_escape(tgl_id($b['created_at'], TRUE, TRUE)) ?></td>
                    <td class="px-4 py-3 text-xs<?= $hari > 7 ? ' font-bold text-amber-700' : '' ?>"><?= $hari <= 0 ? 'Masuk hari ini' : 'Menunggu ' . $hari . ' hari' ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?= html_escape($tujuan) ?>" data-baris-pemberitahuan="<?= html_escape($key) ?>:<?= $id ?>" class="tombol-aksi"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-right"></i></span><span>Tindak lanjuti</span></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($mp['total'] > count($mp['baris'])): ?>
    <p class="border-t border-gray-200 dark:border-white/5 px-4 py-3 text-sm text-gray-600 dark:text-brand-muted">
        Menampilkan <?= angka_id(count($mp['baris'])) ?> terbaru dari <?= angka_id($mp['total']) ?>.
        <a href="<?= html_escape($semua) ?>" data-tautan-modul class="font-bold text-blue-600 dark:text-brand-primary underline">Lihat semua di modul</a>
    </p>
    <?php endif; ?>
</section>
<?php endforeach; ?>
<?php endif; ?>
