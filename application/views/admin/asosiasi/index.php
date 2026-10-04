<?php
/**
 * Master asosiasi pengembang - lihat Admin_Asosiasi.
 *
 * Dirapikan 4 Okt 2026 untuk petugas dinas: dulu tiap baris berisi kotak isian dengan tombol Simpan
 * sendiri (mirip lembar kerja), kolom Kode dan Urutan yang teknis, dan "Hapus" abu-abu tanpa
 * penjelasan. Kini tabel hanya untuk dibaca; tambah dan ubah lewat satu modal Alpine (pola modal admin).
 *
 * Kode adalah nilai yang tersimpan di data pengembang. Ia dibentuk otomatis dari nama saat ditambah
 * dan tidak bisa diubah sesudahnya: kalau diganti, data yang terlanjur memakainya kehilangan
 * rujukannya. Karena itu kode tidak tampil di tabel, hanya sebagai keterangan di jendela Ubah.
 *
 * Kolom "Dipakai" membuat admin tahu SEBELUM menghapus bahwa asosiasi itu masih menempel di data
 * orang. Penolakannya sendiri tetap di server (Admin_Asosiasi::hapus()).
 */
$total_pakai = static function ($kode) use ($pemakaian) {
    return array_sum($pemakaian[$kode] ?? []);
};
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$isian = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
?>
<div class="tumpuk-bagian" x-data="{ buka: false, id: '', nama: '', urutan: '', aktif: true, kode: '',
        tambah() { this.id = ''; this.nama = ''; this.urutan = ''; this.aktif = true; this.kode = ''; this.buka = true; this.$nextTick(() => this.$refs.nama.focus()); },
        ubah(d) { this.id = d.id; this.nama = d.nama; this.urutan = d.urutan; this.aktif = d.aktif === '1'; this.kode = d.kode; this.buka = true; this.$nextTick(() => this.$refs.nama.focus()); } }">
    <?php $this->load->view('admin/components/judul_halaman', [
        'jh_deskripsi' => 'Daftar asosiasi yang bisa dipilih pengembang di profil akunnya dan oleh admin di
            <span class="font-semibold">Direktori SRP2</span>. Nama di sini juga tampil di direktori publik.',
        'jh_aksi' => '<button type="button" class="tombol-utama" data-asosiasi-tambah @click="tambah()"><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah asosiasi</span></button>',
    ]); ?>

    <div class="kartu-admin overflow-hidden">
        <?php if (empty($rows)): ?>
            <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-brand-muted">
                Belum ada asosiasi. Selama daftar ini kosong, pengembang dan admin tidak punya pilihan asosiasi apa pun.
            </p>
        <?php else: ?>
        <div class="overflow-x-auto aksi-tetap">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-black/20">
                    <tr>
                        <th class="px-4 py-3">Nama asosiasi</th>
                        <th class="px-4 py-3">Dipakai</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($rows as $r): $dipakai = $total_pakai($r->kode); ?>
                    <tr>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama) ?></td>
                        <td class="px-4 py-3 text-xs">
                            <?php if ($dipakai > 0): ?>
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Dipakai <?= $dipakai ?> kali</span>
                            <?php else: ?>
                                <span class="text-gray-400 dark:text-brand-muted">Belum dipakai</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => $r->aktif ? 'Ditampilkan' : 'Disembunyikan',
                                'kelas' => $r->aktif ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" class="tombol-aksi" data-asosiasi-ubah @click="ubah($el.dataset)"
                                    data-id="<?= (int) $r->id ?>" data-nama="<?= html_escape($r->nama) ?>" data-kode="<?= html_escape($r->kode) ?>"
                                    data-urutan="<?= (int) $r->urutan ?>" data-aktif="<?= $r->aktif ? '1' : '0' ?>">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span>
                            </button>
                            <?php if ($dipakai === 0): ?>
                            <form class="inline" action="<?= base_url('Admin_Asosiasi/hapus') ?>" method="post"
                                  data-konfirmasi="Asosiasi <?= html_escape($r->nama) ?> akan dihapus dari daftar." data-konfirmasi-judul="Hapus asosiasi?" data-konfirmasi-label="Hapus" data-konfirmasi-bahaya>
                                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                                <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                                <button class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="border-t border-gray-100 px-4 py-2.5 text-xs text-gray-500 dark:border-white/5 dark:text-brand-muted">
            Asosiasi yang sudah dipakai data pengembang tidak bisa dihapus. Sembunyikan saja lewat <strong>Ubah</strong> bila tidak ingin ditawarkan lagi.
        </p>
        <?php endif; ?>
    </div>

    <?php /* Modal tambah/ubah. Saat menambah, kode dan urutan diisi server (dari nama; di akhir daftar). */ ?>
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="buka = false" data-modal-asosiasi>
        <div @click.outside="buka = false" class="kartu-admin w-full max-w-md shadow-2xl">
            <form action="<?= base_url('Admin_Asosiasi/simpan') ?>" method="post" class="isi-kartu space-y-4">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                <input type="hidden" name="id" :value="id">
                <h2 class="text-base font-black text-gray-900 dark:text-white" x-text="id ? 'Ubah asosiasi' : 'Tambah asosiasi'">Tambah asosiasi</h2>
                <label class="block text-xs text-gray-500 dark:text-brand-muted">Nama asosiasi
                    <input type="text" name="nama" x-ref="nama" x-model="nama" maxlength="100" required placeholder="mis. APERSI" class="<?= $isian ?>">
                </label>
                <div x-show="id"><label class="block text-xs text-gray-500 dark:text-brand-muted">Urutan di daftar pilihan
                    <input type="number" name="urutan" x-model="urutan" min="0" max="999" class="<?= $isian ?>">
                    <span class="mt-1 block text-[11px]">Angka lebih kecil tampil lebih dulu.</span>
                </label></div>
                <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <input type="checkbox" name="aktif" value="1" x-model="aktif" class="mt-0.5">
                    <span>Tampilkan sebagai pilihan<span class="block text-[11px] text-gray-500 dark:text-brand-muted">Jika tidak dicentang, asosiasi disembunyikan dari pilihan tetapi data lama tetap utuh.</span></span>
                </label>
                <p x-show="id" class="text-[11px] text-gray-500 dark:text-brand-muted">Kode sistem: <code x-text="kode"></code> (tidak bisa diubah)</p>
                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" class="tombol-kedua" @click="buka = false">Batal</button>
                    <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

