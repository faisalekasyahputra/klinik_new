<?php
/**
 * Detail pendaftaran milik mahasiswa sendiri.
 *
 * Sebelum ada halaman ini, yang bisa dilihat pendaftar cuma satu baris ringkasan
 * di /akun: jenis, instansi, divisi, status. NIM, semester, periode, dan apakah
 * berkasnya benar-benar terunggah - tidak ada satu pun yang bisa ia periksa,
 * padahal itulah yang menentukan diterima atau tidaknya.
 *
 * Anti-IDOR ditegakkan di controller (pendaftaran_milik(), user_id dari sesi),
 * bukan di sini. View tidak pernah jadi tempat memutuskan siapa boleh melihat apa.
 */
$kartu = 'rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 shadow-sm';
$label = 'text-xs font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)]';
$nilai = 'mt-1 text-sm font-semibold text-[color:var(--portal-text)]';

$warna_status = [
    'Diajukan'        => 'bg-amber-500/10 text-amber-600',
    'Ditinjau Bidang' => 'bg-cyan-500/10 text-cyan-600',
    'Diterima'   => 'bg-emerald-500/10 text-emerald-600',
    'Ditolak'    => 'bg-rose-500/10 text-rose-600',
    'Dibatalkan' => 'bg-gray-500/10 text-gray-500',
][$row->status] ?? 'bg-amber-500/10 text-amber-600';

$baris = [
    'NIM'                  => $row->nim,
    'Jurusan'              => $row->jurusan,
    'Semester'             => $row->semester ? (int) $row->semester : NULL,
    'Tempat, Tanggal Lahir' => $row->tempat_lahir && $row->tanggal_lahir
        ? $row->tempat_lahir . ', ' . tgl_id($row->tanggal_lahir) : NULL,
    'Instansi Asal'        => $row->instansi_asal,
    'Nomor HP'             => $row->no_hp,
];

/**
 * Kolom `divisi_atau_tema` memuat DUA hal berbeda tergantung jenisnya.
 *
 * Untuk KKN ia tema kegiatan sungguhan - teks bebas yang ditulis pendaftar.
 * Untuk magang, `periksa_slot()` menimpanya dengan nama kanonik bidang dari
 * tabel, jadi menampilkannya sebagai "Tema Kegiatan" DI SAMPING "Bidang Tujuan"
 * mencetak nilai yang sama dua kali dengan dua label berbeda - persis yang
 * terlihat pada pendaftaran nyata pertama, 2 Agt 2026:
 *
 *     BIDANG TUJUAN   Bidang Perumahan
 *     TEMA KEGIATAN   Bidang Perumahan
 *
 * "Divisi" sendiri sudah dihapus dinas (konfirmasi 1 Agt 2026); nama kolomnya
 * dibiarkan karena itu urusan skema, bukan urusan yang dibaca mahasiswa.
 */
if ($row->jenis === 'magang') {
    // Nama bidang datang dari controller - view ini tidak menyentuh DB.
    // Sebelumnya bidang tujuan TIDAK PERNAH disebut ke pendaftar sama sekali:
    // garis waktunya bilang "Bidang penanggung jawab memutuskan" tanpa memberi
    // tahu yang mana, padahal itu yang menentukan ke siapa ia bertanya kalau
    // lama tak ada kabar.
    $baris['Bidang Tujuan'] = $nama_bidang ?: $row->divisi_atau_tema;
} else {
    $baris['Tema Kegiatan'] = $row->divisi_atau_tema;
}

$baris['Periode'] = $row->periode_mulai && $row->periode_selesai
    ? tgl_id($row->periode_mulai, TRUE) . ' - ' . tgl_id($row->periode_selesai, TRUE) : NULL;
?>
<div class="mx-auto max-w-3xl p-2 sm:p-6">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
            <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-3xl">
                Pendaftaran <?= strtoupper(html_escape($row->jenis)) ?>
            </h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-bold <?= $warna_status ?>"><?= html_escape($row->status) ?></span>
                <span class="text-xs text-[color:var(--portal-text-muted)]">Dikirim <?= tgl_id($row->created_at) ?></span>
            </div>
        </div>
        <a href="<?= base_url('akun') ?>" data-tab-link
           class="rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]">Kembali ke Akun</a>
    </div>

    <?php
    /**
     * Garis waktu empat tahap - bentuk yang diminta user 1 Agt 2026.
     *
     * Statusnya satu kolom, tapi yang dilihat mahasiswa harus berupa PERJALANAN:
     * suratnya sekarang ada di meja siapa. "Diajukan" tidak memberi tahu itu.
     * 'Ditolak' menghentikan garis di tahap tempat ia ditolak - catatan_bidang
     * yang terisi berarti penolakannya datang dari meja kedua.
     */
    // Ketiga kolom bidang ditulis sekaligus oleh Kemitraan_Bidang::proses(),
    // jadi memeriksa salah satu saja sebenarnya cukup - sampai ada yang menambal
    // baris lewat SQL manual dan hanya mengisi sebagian. Waktu itu terjadi di
    // data uji 2 Agt 2026, halaman ini MENYANGKAL DIRINYA SENDIRI: garis
    // waktunya bilang "berhenti di tinjauan sekretariat" sementara tepat di
    // bawahnya terpampang "Catatan Bidang". Memeriksa ketiganya menghapus
    // kemungkinan itu dengan biaya nol.
    $ditolak_di_bidang = $row->status === 'Ditolak' && (
        ! empty($row->reviewed_at_bidang) || ! empty($row->reviewed_by_bidang) || ! empty($row->catatan_bidang)
    );
    $urut  = ['Diajukan' => 1, 'Ditinjau Bidang' => 2, 'Diterima' => 3];
    $capai = $urut[$row->status] ?? ($ditolak_di_bidang ? 2 : 1);

    /**
     * Keterangan tahap 1 mengikuti kenyataan, bukan janji.
     *
     * Surat pengantar OPSIONAL di formulir pendaftaran (tidak ada aturan wajib
     * di `$config['kemitraan_pendaftaran']`), sementara keterangan tahap ini
     * dipatok "Surat pengantar diterima sistem". Akibatnya halaman menyangkal
     * dirinya sendiri di layar yang sama: tahap 1 hijau dengan klaim itu, dan
     * beberapa sentimeter di bawahnya tertulis "Surat pengantar belum ada".
     * Ditemukan 2 Agt 2026 pada satu-satunya pendaftaran yang ada di DB dev.
     *
     * Yang dikoreksi cuma keterangannya, BUKAN status hijaunya: pendaftarannya
     * memang benar-benar masuk dan sudah dilewatkan kedua meja. Memutus rantai
     * di tahap 1 justru akan menampilkan urutan ✗✓✓ yang lebih membingungkan
     * daripada masalah yang diperbaikinya.
     */
    $ada_surat = ! empty($row->file_surat_pengantar);

    $tahap = [
        ['judul' => 'Surat Masuk',
         'ket'   => $ada_surat ? 'Surat pengantar diterima sistem'
                               : 'Pendaftaran diterima sistem - tanpa surat pengantar'],
        ['judul' => 'Ditinjau Admin Disperakim', 'ket' => 'Sekretariat memeriksa dan meneruskan'],
        ['judul' => 'Ditinjau Admin Bidang',     'ket' => 'Bidang penanggung jawab memutuskan'],
        ['judul' => 'Surat Balasan',             'ket' => 'Surat resmi siap diunduh'],
    ];
    $berhenti = in_array($row->status, ['Ditolak', 'Dibatalkan'], TRUE);
    ?>
    <div class="<?= $kartu ?> mb-4">
        <div class="<?= $label ?> mb-4">Perjalanan Berkas</div>
        <ol class="space-y-0">
            <?php foreach ($tahap as $i => $t): ?>
                <?php
                // Tahap 4 hanya hijau kalau suratnya BENAR-BENAR ada. Menandainya
                // selesai karena statusnya 'Diterima' akan berbohong selama surat
                // balasannya belum diunggah.
                $nomor  = $i + 1;
                $selesai = $nomor === 4 ? ! empty($row->file_surat_balasan) : $nomor <= $capai;
                $sedang  = ! $berhenti && ! $selesai && $nomor === $capai + 1;
                $mati    = $berhenti && ! $selesai;
                ?>
                <li class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-black
                            <?= $selesai ? 'bg-emerald-500 text-white' : ($sedang ? 'bg-amber-500 text-white' : 'bg-[color:var(--portal-btn-bg)] text-[color:var(--portal-text-muted)]') ?>">
                            <?= $selesai ? '✓' : $nomor ?>
                        </span>
                        <?php if ($nomor < 4): ?>
                            <span class="my-1 w-px flex-1 <?= $selesai ? 'bg-emerald-500' : 'bg-[color:var(--portal-border)]' ?>"></span>
                        <?php endif; ?>
                    </div>
                    <div class="pb-5">
                        <div class="text-sm font-bold <?= $mati ? 'text-[color:var(--portal-text-muted)]' : 'text-[color:var(--portal-text)]' ?>"><?= html_escape($t['judul']) ?></div>
                        <div class="text-xs text-[color:var(--portal-text-muted)]"><?= html_escape($t['ket']) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <?php if ($berhenti): ?>
            <p class="mt-1 rounded-xl bg-rose-500/10 px-4 py-3 text-sm font-semibold text-rose-600">
                Berkas berhenti di tahap <?= $ditolak_di_bidang ? 'tinjauan bidang' : ($row->status === 'Dibatalkan' ? 'ini - Anda membatalkannya sendiri' : 'tinjauan sekretariat') ?>.
            </p>
        <?php elseif ( ! empty($row->file_surat_balasan)): ?>
            <a href="<?= base_url('KemitraanPortal/unduh_balasan/' . (int) $row->id) ?>" target="_blank" rel="noopener"
               class="mt-1 inline-flex items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-4 py-2.5 text-xs font-bold text-[#0a1a1f]">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Unduh Surat Balasan
            </a>
        <?php elseif ($row->status === 'Diterima'): ?>
            <p class="mt-1 text-xs text-[color:var(--portal-text-muted)]">
                Pendaftaran Anda diterima. Surat balasan resmi sedang disiapkan sekretariat - halaman ini akan menampilkan tombol unduh begitu suratnya terbit.
            </p>
        <?php endif; ?>
    </div>

    <?php if ( ! empty($row->catatan_bidang)): ?>
        <div class="<?= $kartu ?> mb-4 border-l-4 border-l-amber-500">
            <div class="<?= $label ?>">Catatan Bidang</div>
            <p class="mt-2 text-sm leading-relaxed text-[color:var(--portal-text)]"><?= nl2br(html_escape($row->catatan_bidang)) ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($row->catatan_admin)): ?>
        <!-- Catatan peninjau ditampilkan lebih dulu: kalau ditolak, inilah satu-
             satunya hal yang benar-benar perlu dibaca. -->
        <div class="<?= $kartu ?> mb-4 border-l-4 border-l-amber-500">
            <div class="<?= $label ?>">Catatan Admin</div>
            <p class="mt-2 text-sm leading-relaxed text-[color:var(--portal-text)]"><?= nl2br(html_escape($row->catatan_admin)) ?></p>
        </div>
    <?php endif; ?>

    <div class="<?= $kartu ?>">
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <?php foreach ($baris as $judul => $isi): ?>
                <div>
                    <dt class="<?= $label ?>"><?= html_escape($judul) ?></dt>
                    <dd class="<?= $nilai ?>"><?= $isi ? html_escape($isi) : '<span class="font-normal text-[color:var(--portal-text-muted)]">Belum diisi</span>' ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <!-- Berkas: yang penting bukan tautannya (mahasiswa tidak diberi akses
             baca ke private_uploads), tapi KEPASTIAN bahwa unggahannya mendarat.
             Sebelumnya tidak ada cara mengetahuinya selain bertanya ke admin. -->
        <div class="mt-6 border-t border-[color:var(--portal-border)] pt-5">
            <div class="<?= $label ?>">Berkas</div>
            <ul class="mt-2 space-y-1.5">
                <?php
                $berkas = ['Surat pengantar' => $row->file_surat_pengantar];
                if ($row->jenis === 'magang') { $berkas['Proposal magang'] = $row->file_proposal; }
                ?>
                <?php foreach ($berkas as $nama_berkas => $tersimpan): ?>
                    <li class="flex items-center gap-2 text-sm">
                        <?php if ( ! empty($tersimpan)): ?>
                            <i class="fa-solid fa-circle-check text-emerald-500" aria-hidden="true"></i>
                            <span class="text-[color:var(--portal-text)]"><?= html_escape($nama_berkas) ?> sudah terunggah</span>
                        <?php else: ?>
                            <i class="fa-regular fa-circle text-[color:var(--portal-text-muted)]" aria-hidden="true"></i>
                            <span class="text-[color:var(--portal-text-muted)]"><?= html_escape($nama_berkas) ?> belum ada</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-2 text-[11px] text-[color:var(--portal-text-muted)]">
                Berkas hanya bisa disusulkan lewat admin. Hubungi pengelola bila ada yang keliru.
            </p>
        </div>
    </div>

    <?php if ($bisa_ubah): ?>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <a href="<?= base_url('KemitraanPortal/ubah/' . (int) $row->id) ?>"
               class="rounded-xl bg-[color:var(--portal-brand)] px-4 py-2.5 text-xs font-bold text-[#0a1a1f]">Ubah Data</a>
            <form method="POST" action="<?= base_url('KemitraanPortal/batal/' . (int) $row->id) ?>"
                  onsubmit="return confirm('Batalkan pendaftaran ini? Slotnya akan dilepas untuk mahasiswa lain, dan Anda perlu mendaftar ulang bila berubah pikiran.')">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Batalkan pendaftaran</button>
            </form>
        </div>
    <?php else: ?>
        <p class="mt-4 text-xs text-[color:var(--portal-text-muted)]">
            Pendaftaran yang sudah <?= strtolower(html_escape($row->status)) ?> tidak bisa diubah lagi.
            Hubungi pengelola bila ada data yang keliru.
        </p>
    <?php endif; ?>
</div>
