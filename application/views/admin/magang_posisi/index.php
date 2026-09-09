<?php
/**
 * Layar posisi/lowongan magang - butir F1.
 *
 * MODAL PENGINGAT muncul tiap halaman dibuka, dan isinya berubah menurut
 * keadaan: kosong sama sekali, atau sudah lama tidak disentuh. Itu permintaan
 * user - "agar mereka tidak malas update". Pengingat yang berbunyi sama dalam
 * keadaan apa pun cepat jadi hiasan yang diklik tanpa dibaca.
 */
$hari_basi = 60;
$kosong    = empty($rows);
$umur_hari = $terakhir_diubah ? (int) floor((time() - strtotime($terakhir_diubah)) / 86400) : NULL;
$basi      = ( ! $kosong) && $umur_hari !== NULL && $umur_hari >= $hari_basi;
?>

<?php if ($kosong || $basi): ?>
<dialog id="modal-posisi-magang" class="rounded-2xl border p-0"
        style="max-width:34rem;width:calc(100% - 2rem);background:var(--portal-bg-card,#fff);border-color:var(--portal-border,#e5e7eb);color:var(--portal-text,#111827)"
        aria-labelledby="modal-posisi-judul">
    <div class="p-5 sm:p-6">
        <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:#b45309">Pengingat</p>

        <?php if ($kosong): ?>
            <h2 id="modal-posisi-judul" class="mt-1 text-lg font-black sm:text-xl">Belum ada satu pun posisi magang</h2>
            <p class="mt-3 text-sm leading-relaxed" style="color:var(--portal-text-muted,#6b7280)">
                Selama daftar ini kosong, papan magang publik hanya menampilkan <strong>nama bidang</strong> -
                dan itulah yang dikeluhkan dinas: mahasiswa tidak tahu sebenarnya dibutuhkan keahlian apa.
            </p>
            <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
                 style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
                <p><strong>Kami sengaja tidak mengisinya lebih dulu.</strong> Lima contoh yang disebut di rapat
                (programmer, arsitek, pengelola data, drafter, content creator) masih berupa contoh dalam
                kalimat, bukan daftar resmi. Kalau kami tuliskan sendiri, tebakan kami akan terbaca sebagai
                keputusan dinas dan mahasiswa melamar posisi yang mungkin tidak ada.</p>
            </div>
        <?php else: ?>
            <h2 id="modal-posisi-judul" class="mt-1 text-lg font-black sm:text-xl">Daftar posisi belum disentuh <?= (int) $umur_hari ?> hari</h2>
            <p class="mt-3 text-sm leading-relaxed" style="color:var(--portal-text-muted,#6b7280)">
                Mahasiswa melamar berdasarkan daftar ini. Posisi yang sudah terisi tetapi masih tercantum
                membuat mereka mendaftar untuk sesuatu yang tidak ada lagi - dan penolakannya baru datang
                jauh belakangan.
            </p>
            <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
                 style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
                <p><strong>Cukup satu hal:</strong> matikan tanda &ldquo;Aktif&rdquo; pada posisi yang sudah
                tidak dibuka. Tidak perlu dihapus - mematikannya menyembunyikannya dari papan publik
                sambil menyimpan catatannya untuk periode berikutnya.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4 rounded-xl border p-3 text-xs leading-relaxed"
             style="background:rgba(14,165,233,.09);border-color:rgba(14,165,233,.3);color:#075985">
            <p><strong>Kuota di sini keterangan, bukan pengunci.</strong> Yang membatasi jumlah pendaftar
            tetap kuota per bidang. Jadi mengubah daftar ini aman - tidak ada pendaftaran berjalan
            yang ikut berubah.</p>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button" id="modal-posisi-tutup" class="rounded-xl px-4 py-2 text-sm font-bold"
                    style="background:var(--portal-brand,#0e6b7a);color:#fff">Mengerti, saya perbarui</button>
        </div>
    </div>
</dialog>
<script>
(function () {
    var d = document.getElementById('modal-posisi-magang');
    if (!d || typeof d.showModal !== 'function') { return; }
    var buka = function () { if (!d.open) { d.showModal(); } };
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', buka); } else { buka(); }
    document.getElementById('modal-posisi-tutup').addEventListener('click', function () { d.close(); });
})();
</script>
<?php endif; ?>

<div class="space-y-5">
    <header>
        <h1 class="text-xl font-black">Jurusan/Bidang/Keahlian Magang</h1>
        <p class="mt-1 text-sm" style="color:var(--portal-text-muted,#6b7280)">
            Daftar jurusan, bidang studi, atau keahlian yang sedang dibutuhkan tiap bidang. Yang <strong>aktif</strong> tampil di papan
            magang publik; kuota di sini keterangan, bukan pengunci pendaftaran.
        </p>
        <p class="mt-1 text-xs" style="color:var(--portal-text-muted,#6b7280)">
            <?= (int) $jumlah_aktif ?> posisi aktif ·
            <?= $terakhir_diubah ? 'terakhir diperbarui ' . html_escape(date('d M Y', strtotime($terakhir_diubah))) : 'belum pernah diisi' ?>
        </p>
    </header>

    <?php foreach (['success' => '#047857', 'error' => '#b91c1c'] as $jenis => $warna): ?>
        <?php if ($this->session->flashdata($jenis)): ?>
            <p class="rounded-xl border p-3 text-sm" style="color:<?= $warna ?>;border-color:currentColor">
                <?= html_escape($this->session->flashdata($jenis)) ?></p>
        <?php endif; ?>
    <?php endforeach; ?>

    <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post"
          class="rounded-2xl border p-4" style="border-color:var(--portal-border,#e5e7eb)">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <input type="hidden" name="id" value="0">
        <p class="mb-3 text-sm font-bold">Tambah kebutuhan</p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <label class="text-xs">Bidang
                <select name="bidang_kode" required class="mt-1 w-full rounded-lg border p-2 text-sm">
                    <?php foreach ($bidang as $b): ?>
                        <option value="<?= html_escape($b->kode) ?>"><?= html_escape($b->nama) ?></option>
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
            <button type="submit" class="rounded-xl px-4 py-2 text-sm font-bold"
                    style="background:var(--portal-brand,#0e6b7a);color:#fff">Tambah</button>
        </div>
    </form>

    <?php if ($kosong): ?>
        <p class="rounded-2xl border p-6 text-center text-sm" style="border-color:var(--portal-border,#e5e7eb);color:var(--portal-text-muted,#6b7280)">
            Belum ada posisi. Papan magang publik masih menampilkan nama bidang saja.
        </p>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--portal-border,#e5e7eb)">
            <table class="w-full text-sm">
                <thead class="text-left text-xs" style="background:rgba(0,0,0,.03)">
                    <tr>
                        <th class="px-3 py-2">Bidang</th><th class="px-3 py-2">Posisi</th>
                        <th class="px-3 py-2">Keterangan</th><th class="px-3 py-2">Dibutuhkan</th>
                        <th class="px-3 py-2">Urutan</th><th class="px-3 py-2">Aktif</th>
                        <th class="px-3 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr class="border-t" style="border-color:var(--portal-border,#e5e7eb)">
                        <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post" id="f<?= (int) $r->id ?>">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                        </form>
                        <td class="px-3 py-2">
                            <select name="bidang_kode" form="f<?= (int) $r->id ?>" class="rounded border p-1 text-xs">
                                <?php foreach ($bidang as $b): ?>
                                    <option value="<?= html_escape($b->kode) ?>" <?= $b->kode === $r->bidang_kode ? 'selected' : '' ?>><?= html_escape($b->nama) ?></option>
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
                            <button type="submit" form="f<?= (int) $r->id ?>" class="rounded px-2 py-1 text-xs font-bold"
                                    style="background:var(--portal-brand,#0e6b7a);color:#fff">Simpan</button>
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
