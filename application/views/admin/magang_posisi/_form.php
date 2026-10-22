<?php
/**
 * Modal tambah/ubah posisi magang. Dipakai layar Posisi Magang dan detail slot bidang
 * (Admin_Kemitraan::slot_bidang), di dalam elemen ber-x-data dari _alpine.php.
 *
 * @var object[] $bidang       pilihan bidang untuk select
 * @var string   $isian        kelas isian
 * @var callable $tanpa_awalan pembuang awalan "Bidang "
 * @var array    $asal         opsional ['kode' => ..., 'tahun' => ...]: bidang dikunci dan simpan kembali ke detail slot
 */
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$asal      = $asal ?? NULL;
?>
    <?php /* Modal tambah/ubah; hanya terbuka lewat tombol (bukan pengingat otomatis). */ ?>
    <?php /* margin:auto (bukan items-center) agar di layar pendek modal bisa digulir tanpa judul terpotong. */ ?>
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex overflow-y-auto bg-black/50 p-4" @keydown.escape.window="buka = false" data-modal-posisi>
        <div @click.outside="buka = false" class="kartu-admin w-full max-w-md shadow-2xl" style="margin: auto">
            <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post" class="isi-kartu space-y-4">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                <input type="hidden" name="id" :value="id || 0">
                <h2 class="text-base font-black text-gray-900 dark:text-white" x-text="id ? 'Ubah posisi magang' : 'Tambah posisi magang'">Tambah posisi magang</h2>
                <?php if ($asal): ?>
                    <?php // Dari detail slot: bidangnya sudah pasti, dan simpan kembali ke halaman bidang itu. ?>
                    <input type="hidden" name="bidang_kode" value="<?= html_escape($asal['kode']) ?>">
                    <input type="hidden" name="asal" value="slot">
                    <input type="hidden" name="tahun" value="<?= (int) $asal['tahun'] ?>">
                <?php else: ?>
                <label class="block text-xs text-gray-500 dark:text-brand-muted">Bidang
                    <select name="bidang_kode" x-model="bidang" required class="<?= $isian ?>">
                        <?php foreach ($bidang as $b): ?>
                            <option value="<?= html_escape($b->kode) ?>"><?= html_escape($tanpa_awalan($b->nama)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php endif; ?>
                <label class="block text-xs text-gray-500 dark:text-brand-muted">Jurusan, bidang studi, atau keahlian
                    <input type="text" name="nama_posisi" x-ref="posisi" x-model="posisi" maxlength="100" required placeholder="mis. Drafter" class="<?= $isian ?>">
                </label>
                <?php // Daftar kualifikasi: satu kotak per butir, bisa ditambah dan dihapus (permintaan user 22 Okt 2026). ?>
                <fieldset class="block text-xs text-gray-500 dark:text-brand-muted">
                    <legend>Kualifikasi (opsional)</legend>
                    <div class="mt-1 space-y-2" x-ref="daftarKual">
                        <template x-for="(butir, i) in kual" :key="i">
                            <div class="flex items-center gap-2">
                                <span class="w-5 shrink-0 text-right font-bold" x-text="(i + 1) + '.'"></span>
                                <input type="text" name="kualifikasi[]" x-model="kual[i]" maxlength="<?= (int) KUALIFIKASI_MAKS_KARAKTER ?>"
                                       :placeholder="i === 0 ? 'mis. Menguasai AutoCAD' : 'Kualifikasi berikutnya'" :aria-label="'Kualifikasi ' + (i + 1)" class="<?= $isian ?>"
                                       @keydown.enter.prevent="tambahButir()">
                                <button type="button" class="tombol-aksi shrink-0" @click="hapusButir(i)" :aria-label="'Hapus kualifikasi ' + (i + 1)"><i class="ph ph-x" aria-hidden="true"></i></button>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="tombol-aksi mt-2" @click="tambahButir()" :disabled="kual.length >= maksButir"><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah kualifikasi</span></button>
                    <span class="mt-1 block text-[11px]">Maksimal <?= (int) KUALIFIKASI_MAKS_BUTIR ?> butir, masing-masing <?= (int) KUALIFIKASI_MAKS_KARAKTER ?> karakter. Tampil sebagai daftar di papan slot dan formulir daftar magang; lebih dari 3 butir dibuka lewat Selengkapnya.</span>
                </fieldset>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-xs text-gray-500 dark:text-brand-muted">Jumlah dibutuhkan
                        <input type="number" name="kuota" x-model="kuota" min="0" max="99" class="<?= $isian ?>">
                    </label>
                    <div x-show="id"><label class="block text-xs text-gray-500 dark:text-brand-muted">Urutan dalam bidang
                        <input type="number" name="urutan" x-model="urutan" min="0" max="999" class="<?= $isian ?>">
                    </label></div>
                </div>
                <p class="text-[11px] text-gray-500 dark:text-brand-muted">Jumlah ini hanya keterangan untuk mahasiswa; batas pendaftar tetap mengikuti kuota tiap bidang.</p>
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="aktif" value="1" x-model="aktif" class="mt-0.5">
                    <span>Tampilkan di papan magang<span class="block text-[11px] text-gray-500 dark:text-brand-muted">Jika tidak dicentang, posisi disembunyikan dari mahasiswa tetapi tetap tersimpan.</span></span>
                </label>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="tombol-kedua" @click="buka = false">Batal</button>
                    <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
