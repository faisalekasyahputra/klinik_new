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

<div class="tumpuk-bagian" x-data="{ buka: false, id: '', bidang: '<?= html_escape($bidang_awal) ?>', posisi: '', kual: [''], kuota: 1, urutan: '', aktif: true, maksButir: <?= (int) KUALIFIKASI_MAKS_BUTIR ?>,
        tambahButir() { if (this.kual.length < this.maksButir) { this.kual.push(''); this.$nextTick(() => { const k = this.$refs.daftarKual.querySelectorAll('input'); k[k.length - 1].focus(); }); } },
        hapusButir(i) { this.kual.splice(i, 1); if (!this.kual.length) this.kual.push(''); },
        tambah() { this.id = ''; this.posisi = ''; this.kual = ['']; this.kuota = 1; this.urutan = ''; this.aktif = true; this.buka = true; this.$nextTick(() => this.$refs.posisi.focus()); },
        ubah(d) { this.id = d.id; this.bidang = d.bidang; this.posisi = d.posisi; this.kual = JSON.parse(d.kual || '[]'); if (!this.kual.length) this.kual = ['']; this.kuota = d.kuota; this.urutan = d.urutan; this.aktif = d.aktif === '1'; this.buka = true; this.$nextTick(() => this.$refs.posisi.focus()); } }">
    <?php $this->load->view('admin/components/judul_halaman', [
        'jh_deskripsi' => 'Daftar jurusan, bidang studi, atau keahlian yang sedang dibutuhkan tiap bidang. Yang <strong>ditampilkan</strong> muncul di papan
            magang publik.<br><span class="text-xs">Posisi hanya keterangan: mahasiswa mendaftar per bidang, dan batas pendaftar diatur di
            <strong>Atur slot</strong>.</span><br><span class="text-xs">'
            . (int) $jumlah_aktif . ' posisi ditampilkan · '
            . ($terakhir_diubah ? 'terakhir diperbarui ' . html_escape(tgl_id($terakhir_diubah, TRUE)) : 'belum pernah diisi') . '</span>',
        'jh_aksi' => '<a href="' . base_url('KemitraanPortal/magang') . '" target="_blank" rel="noopener" class="tombol-kedua" data-posisi-papan><i class="ph ph-arrow-square-out" aria-hidden="true"></i><span>Lihat papan magang</span></a>'
            . '<a href="' . base_url('Admin_Kemitraan/slot') . '" class="tombol-kedua" data-posisi-slot><i class="ph ph-calendar-check" aria-hidden="true"></i><span>Atur slot</span></a>'
            . '<button type="button" class="tombol-utama" @click="tambah()" data-posisi-tambah><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah posisi</span></button>',
    ]); ?>

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
                                <a href="<?= base_url('Admin_Kemitraan/slot') ?>" class="mt-1 flex items-center gap-1 font-semibold text-amber-700 hover:underline dark:text-amber-300" data-posisi-bidang-tutup>
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

    <?php /* Modal tambah/ubah; hanya terbuka lewat tombol (bukan pengingat otomatis). */ ?>
    <?php /* margin:auto (bukan items-center) agar di layar pendek modal bisa digulir tanpa judul terpotong. */ ?>
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex overflow-y-auto bg-black/50 p-4" @keydown.escape.window="buka = false" data-modal-posisi>
        <div @click.outside="buka = false" class="kartu-admin w-full max-w-md shadow-2xl" style="margin: auto">
            <form action="<?= base_url('Admin_Magang_Posisi/simpan') ?>" method="post" class="isi-kartu space-y-4">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                <input type="hidden" name="id" :value="id || 0">
                <h2 class="text-base font-black text-gray-900 dark:text-white" x-text="id ? 'Ubah posisi magang' : 'Tambah posisi magang'">Tambah posisi magang</h2>
                <label class="block text-xs text-gray-500 dark:text-brand-muted">Bidang
                    <select name="bidang_kode" x-model="bidang" required class="<?= $isian ?>">
                        <?php foreach ($bidang as $b): ?>
                            <option value="<?= html_escape($b->kode) ?>"><?= html_escape($tanpa_awalan($b->nama)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
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
</div>
