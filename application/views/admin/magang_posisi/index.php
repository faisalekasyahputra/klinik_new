<?php
/**
 * Layar posisi/lowongan magang - butir F1.
 *
 * Dirapikan 4 Okt 2026 (pola sama dengan Asosiasi Pengembang): dulu tiap baris berisi kotak isian
 * putih dengan tombol Simpan sendiri (teks hampir tak terbaca di mode gelap), tanpa tombol Hapus
 * walau formulir hapusnya ada, dan pesan sukses/galat tampil dua kali (paragraf sendiri + pusat
 * notifikasi). Kini tabel hanya untuk dibaca; tambah dan ubah lewat satu modal Alpine.
 *
 * PENGINGAT (permintaan user, "agar mereka tidak malas update") dulu berupa modal yang terbuka
 * sendiri tiap halaman dibuka. Kini kotak biasa di atas tabel (audit UI 2 Okt 2026: modal otomatis
 * menghalangi kerja), dan hanya muncul saat daftar sudah lama tidak diperbarui. Modal tambah/ubah di
 * bawah hanya terbuka bila tombolnya diklik.
 *
 * Catatan:
 * - Daftar sengaja tidak diisi lebih dulu. Lima contoh dari rapat (programmer, arsitek, pengelola
 *   data, drafter, content creator) masih contoh dalam kalimat, bukan daftar resmi; tebakan kita akan
 *   terbaca sebagai keputusan dinas dan mahasiswa melamar posisi yang mungkin tidak ada.
 * - Jumlah dibutuhkan di sini keterangan, bukan pengunci. Yang membatasi jumlah pendaftar tetap kuota
 *   per bidang, jadi mengubah daftar ini tidak mengubah pendaftaran yang sedang berjalan.
 */
$hari_basi = 60;
$kosong    = empty($rows);
$umur_hari = $terakhir_diubah ? (int) floor((time() - strtotime($terakhir_diubah)) / 86400) : NULL;
$basi      = ( ! $kosong) && $umur_hari !== NULL && $umur_hari >= $hari_basi;
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$tanpa_awalan = fn($n) => preg_replace('/^Bidang\s+/i', '', (string) $n);
$isian = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$bidang_awal = $bidang ? $bidang[0]->kode : '';
?>

<div class="tumpuk-bagian" x-data="<?= html_escape($this->load->view('admin/magang_posisi/_alpine', ['bidang_awal' => $bidang_awal], TRUE)) ?>">
    <?php // Tab di menu Magang sejak 22 Okt 2026; posisi satu bidang juga bisa dikelola dari detail slot bidang itu. ?>
    <div><?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'posisi']); ?></div>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <p class="max-w-2xl text-sm text-gray-500 dark:text-brand-muted">
            Semua posisi dari semua bidang. Yang <strong>ditampilkan</strong> muncul di papan magang publik.
            Posisi hanya keterangan: batas pendaftar tetap kuota di <strong>Slot &amp; bidang</strong>, tempat posisi tiap bidang juga bisa diatur.
            <span class="block text-xs"><?= (int) $jumlah_aktif ?> posisi ditampilkan · <?= $terakhir_diubah ? 'terakhir diperbarui ' . html_escape(tgl_id($terakhir_diubah, TRUE)) : 'belum pernah diisi' ?></span>
        </p>
        <div class="flex flex-wrap gap-2">
            <a href="<?= base_url('KemitraanPortal/magang') ?>" target="_blank" rel="noopener" class="tombol-kedua" data-posisi-papan><i class="ph ph-arrow-square-out" aria-hidden="true"></i><span>Lihat papan magang</span></a>
            <button type="button" class="tombol-utama" @click="tambah()" data-posisi-tambah><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah posisi</span></button>
        </div>
    </div>

    <?php if ($basi): ?>
    <div data-pengingat-posisi class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-300 text-sm flex items-start gap-3">
        <i class="ph ph-clock-countdown text-lg mt-0.5" aria-hidden="true"></i>
        <p><strong>Daftar ini belum diperbarui <?= (int) $umur_hari ?> hari.</strong>
        Sembunyikan posisi yang sudah tidak dibuka (lewat <strong>Ubah</strong>) agar mahasiswa tidak melamar posisi yang sudah terisi.</p>
    </div>
    <?php endif; ?>

    <?php if ($kosong): ?>
        <p class="kartu-admin isi-kartu text-center text-sm text-gray-500 dark:text-brand-muted">
            Belum ada posisi. Papan magang publik masih menampilkan nama bidang saja.
        </p>
    <?php else: ?>
        <div class="kartu-admin overflow-x-auto aksi-tetap">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-black/20">
                    <tr>
                        <th class="px-4 py-3">Bidang</th>
                        <th class="px-4 py-3">Posisi yang dibutuhkan</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-brand-muted">
                            <?= html_escape($tanpa_awalan($r->nama_bidang ?: $r->bidang_kode)) ?>
                            <?php if (isset($keadaan_bidang[$r->bidang_kode])): ?>
                                <a href="<?= base_url('Admin_Kemitraan/slot_bidang/' . rawurlencode($r->bidang_kode) . '/' . (int) $tahun_papan) ?>" class="mt-1 flex items-center gap-1 font-semibold text-amber-700 hover:underline dark:text-amber-300" data-posisi-bidang-tutup>
                                    <i class="ph ph-warning-circle" aria-hidden="true"></i><?= html_escape($keadaan_bidang[$r->bidang_kode]) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_posisi) ?></div>
                            <?php $kual = daftar_kualifikasi($r->keterangan); if ($kual): ?>
                                <ul class="mt-0.5 list-disc pl-4 text-xs text-gray-500 dark:text-brand-muted"><?php foreach (array_slice($kual, 0, 3) as $k): ?><li><?= html_escape($k) ?></li><?php endforeach; ?></ul>
                                <?php if (count($kual) > 3): ?>
                                    <details class="text-xs text-gray-500 dark:text-brand-muted">
                                        <summary class="cursor-pointer font-semibold text-brand-600 hover:underline dark:text-brand-300">Selengkapnya (<?= count($kual) ?> kualifikasi)</summary>
                                        <ul class="list-disc pl-4"><?php foreach (array_slice($kual, 3) as $k): ?><li><?= html_escape($k) ?></li><?php endforeach; ?></ul>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $r->kuota ?> orang</td>
                        <td class="px-4 py-3">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => $r->aktif ? 'Ditampilkan' : 'Disembunyikan',
                                'kelas' => $r->aktif ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" class="tombol-aksi" @click="ubah($el.dataset)" data-posisi-ubah
                                    data-id="<?= (int) $r->id ?>" data-bidang="<?= html_escape($r->bidang_kode) ?>" data-posisi="<?= html_escape($r->nama_posisi) ?>"
                                    data-kual="<?= html_escape(json_encode(daftar_kualifikasi($r->keterangan))) ?>" data-kuota="<?= (int) $r->kuota ?>" data-urutan="<?= (int) $r->urutan ?>" data-aktif="<?= $r->aktif ? '1' : '0' ?>">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span>
                            </button>
                            <form class="inline" action="<?= base_url('Admin_Magang_Posisi/hapus') ?>" method="post"
                                  data-konfirmasi="Posisi <?= html_escape($r->nama_posisi) ?> akan dihapus dari daftar. Pendaftaran yang sudah masuk tidak berubah." data-konfirmasi-judul="Hapus posisi?" data-konfirmasi-label="Hapus" data-konfirmasi-bahaya>
                                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                                <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                                <button class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php $this->load->view('admin/magang_posisi/_form', ['bidang' => $bidang, 'isian' => $isian, 'tanpa_awalan' => $tanpa_awalan]); ?>
</div>
