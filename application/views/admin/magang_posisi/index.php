<?php
/**
 * Layar posisi/lowongan magang - butir F1.
 *
 * PENGINGAT (permintaan user, "agar mereka tidak malas update") dulu berupa
 * modal yang terbuka sendiri tiap halaman dibuka. Kini jadi kotak biasa di
 * atas tabel (audit UI 2 Okt 2026: modal otomatis menghalangi kerja), dan hanya
 * muncul saat daftar sudah lama tidak diperbarui. Keadaan kosong cukup jadi
 * baris kosong biasa di bawah formulir.
 *
 * Catatan yang dipindah dari modal:
 * - Daftar sengaja tidak diisi lebih dulu. Lima contoh dari rapat (programmer,
 *   arsitek, pengelola data, drafter, content creator) masih contoh dalam
 *   kalimat, bukan daftar resmi; tebakan kita akan terbaca sebagai keputusan
 *   dinas dan mahasiswa melamar posisi yang mungkin tidak ada.
 * - Kuota di sini keterangan, bukan pengunci. Yang membatasi jumlah pendaftar
 *   tetap kuota per bidang, jadi mengubah daftar ini tidak mengubah
 *   pendaftaran yang sedang berjalan.
 */
$hari_basi = 60;
$kosong    = empty($rows);
$umur_hari = $terakhir_diubah ? (int) floor((time() - strtotime($terakhir_diubah)) / 86400) : NULL;
$basi      = ( ! $kosong) && $umur_hari !== NULL && $umur_hari >= $hari_basi;
?>

<div class="tumpuk-bagian">
    <?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Daftar jurusan, bidang studi, atau keahlian yang sedang dibutuhkan tiap bidang. Yang <strong>aktif</strong> tampil di papan
            magang publik.<br><span class="text-xs">'
            . (int) $jumlah_aktif . ' posisi aktif · '
            . ($terakhir_diubah ? 'terakhir diperbarui ' . html_escape(tgl_id($terakhir_diubah, TRUE)) : 'belum pernah diisi') . '</span>']); ?>

    <?php if ($basi): ?>
    <div data-pengingat-posisi class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-300 text-sm flex items-start gap-3">
        <i class="ph ph-clock-countdown text-lg mt-0.5" aria-hidden="true"></i>
        <p><strong>Daftar ini belum diperbarui <?= (int) $umur_hari ?> hari.</strong>
        Matikan tanda Aktif pada posisi yang sudah tidak dibuka agar mahasiswa tidak melamar posisi yang sudah terisi.</p>
    </div>
    <?php endif; ?>

    <?php foreach (['success' => '#047857', 'error' => '#b91c1c'] as $jenis => $warna): ?>
        <?php if ($this->session->flashdata($jenis)): ?>
            <p class="rounded-xl border p-3 text-sm" style="color:<?= $warna ?>;border-color:currentColor">
                <?= html_escape($this->session->flashdata($jenis)) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>

    <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post"
          class="kartu-admin isi-kartu">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="id" value="0">
        <p class="mb-3 text-sm font-bold">Tambah kebutuhan</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <label class="text-xs">Bidang
                <select name="bidang_kode" required class="mt-1 w-full rounded-lg border p-2 text-sm">
                    <?php foreach ($bidang as $b): ?>
                        <option value="<?= html_escape($b->kode) ?>"><?= html_escape(preg_replace('/^Bidang\s+/i', '', $b->nama)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs">Jurusan/Bidang/Keahlian
                <input type="text" name="nama_posisi" maxlength="100" required
                       placeholder="mis. Drafter" class="mt-1 w-full rounded-lg border p-2 text-sm">
            </label>
            <label class="text-xs sm:col-span-2">Keterangan singkat (opsional)
                <input type="text" name="keterangan" maxlength="255"
                       placeholder="mis. Menguasai AutoCAD" class="mt-1 w-full rounded-lg border p-2 text-sm">
            </label>
            <div class="grid grid-cols-2 gap-2">
                <label class="text-xs">Dibutuhkan
                    <input type="number" name="kuota" min="0" max="99" value="1"
                           class="mt-1 w-full rounded-lg border p-2 text-sm">
                </label>
                <label class="text-xs">Urutan
                    <input type="number" name="urutan" min="0" max="999" value="0"
                           class="mt-1 w-full rounded-lg border p-2 text-sm">
                </label>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="aktif" value="1" checked> Tampilkan di papan magang
            </label>
            <button type="submit" class="tombol-utama"><i class="ph ph-plus"></i><span>Tambah</span></button>
        </div>
    </form>

    <?php if ($kosong): ?>
        <p class="kartu-admin isi-kartu text-center text-sm text-gray-500 dark:text-brand-muted">
            Belum ada posisi. Papan magang publik masih menampilkan nama bidang saja.
        </p>
    <?php else: ?>
        <div class="kartu-admin overflow-x-auto aksi-tetap">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs dark:bg-black/20">
                    <tr>
                        <th class="px-3 py-2">Bidang</th><th class="px-3 py-2">Posisi</th>
                        <th class="px-3 py-2">Keterangan</th><th class="px-3 py-2">Dibutuhkan</th>
                        <th class="px-3 py-2">Urutan</th><th class="px-3 py-2">Aktif</th>
                        <th class="px-3 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post" id="f<?= (int) $r->id ?>">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                        </form>
                        <td class="px-3 py-2">
                            <select name="bidang_kode" form="f<?= (int) $r->id ?>" class="rounded border p-1 text-xs">
                                <?php foreach ($bidang as $b): ?>
                                    <option value="<?= html_escape($b->kode) ?>" <?= $b->kode === $r->bidang_kode ? 'selected' : '' ?>><?= html_escape(preg_replace('/^Bidang\s+/i', '', $b->nama)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-3 py-2"><input type="text" name="nama_posisi" form="f<?= (int) $r->id ?>" maxlength="100"
                            value="<?= html_escape($r->nama_posisi) ?>" class="w-36 rounded border p-1 text-xs"></td>
                        <td class="px-3 py-2"><input type="text" name="keterangan" form="f<?= (int) $r->id ?>" maxlength="255"
                            value="<?= html_escape((string) $r->keterangan) ?>" class="w-48 rounded border p-1 text-xs"></td>
                        <td class="px-3 py-2"><input type="number" name="kuota" form="f<?= (int) $r->id ?>" min="0" max="99"
                            value="<?= (int) $r->kuota ?>" class="w-16 rounded border p-1 text-xs"></td>
                        <td class="px-3 py-2"><input type="number" name="urutan" form="f<?= (int) $r->id ?>" min="0" max="999"
                            value="<?= (int) $r->urutan ?>" class="w-16 rounded border p-1 text-xs"></td>
                        <td class="px-3 py-2"><input type="checkbox" name="aktif" value="1" form="f<?= (int) $r->id ?>" <?= $r->aktif ? 'checked' : '' ?>></td>
                        <td class="whitespace-nowrap px-3 py-2">
                            <button type="submit" form="f<?= (int) $r->id ?>" class="tombol-aksi"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form action="<?= base_url('Admin_Magang_Posisi/hapus') ?>" method="post" class="hidden" id="form-hapus-posisi">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="id" id="hapus-id" value="0">
        </form>
    <?php endif; ?>
</div>
