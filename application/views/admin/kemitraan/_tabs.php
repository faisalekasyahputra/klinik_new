<?php
/**
 * Kepala halaman Magang dan KKN: judul + tab.
 *
 * Dulu domain ini terpecah jadi dua entri sidebar ("KKN/Magang" dan "Slot Magang"), lalu
 * disatukan jadi satu menu "KKN & Magang" berisi lima tab. Sejak 22 Okt 2026 (permintaan user,
 * "agar tidak bingung dengan KKN") dipisah lagi menurut ALURNYA, bukan menurut layarnya:
 * magang adalah mahasiswa perorangan dengan slot dan posisi per bidang, KKN lewat akun
 * universitas dan berujung sertifikat. Tiap menu punya tiga tab.
 *
 * Sub-halaman (detail bidang, sunting pendaftaran) memakai kepala yang sama dengan tab
 * induknya menyala, supaya admin tidak pernah kehilangan tempatnya.
 *
 * @param string $tab_aktif 'pendaftaran_magang' | 'slot' | 'posisi'          (menu Magang)
 *                          'pendaftaran_kkn' | 'sertifikat' | 'universitas'  (menu KKN)
 *                          'semua' (daftar gabungan lama, Admin_Kemitraan tanpa jenis)
 */
$menu = [
    'magang' => [
        'judul'     => 'Magang',
        'deskripsi' => 'Mahasiswa perorangan: slot per bidang, posisi yang dicari, dan pendaftarannya.',
        'aksi'      => '',
        'tab'       => [
            'pendaftaran_magang' => ['label' => 'Pendaftaran magang', 'url' => 'Admin_Kemitraan/magang', 'ikon' => 'ph-student'],
            'slot'               => ['label' => 'Slot magang', 'url' => 'Admin_Kemitraan/slot', 'ikon' => 'ph-calendar-check'],
            // Posisi satu bidang juga dikelola langsung di detail slot bidang itu.
            'posisi'             => ['label' => 'Posisi magang', 'url' => 'Admin_Magang_Posisi', 'ikon' => 'ph-briefcase'],
        ],
    ],
    'kkn' => [
        'judul'     => 'KKN',
        'deskripsi' => 'Diajukan akun universitas, berujung sertifikat peserta.',
        'aksi'      => '<a href="' . base_url('Admin_Kemitraan/catat') . '" class="tombol-utama" data-catat-kkn-tombol><i class="ph ph-plus"></i><span>Catat KKN</span></a>',
        'tab'       => [
            'pendaftaran_kkn' => ['label' => 'Pendaftaran KKN', 'url' => 'Admin_Kemitraan/kkn', 'ikon' => 'ph-student'],
            // Kendali sertifikat KKN di tangan dinas (7 Okt 2026): permintaan mahasiswa, catat KKN, tanggal terbit.
            'sertifikat'      => ['label' => 'Sertifikat KKN', 'url' => 'Admin_Kemitraan/sertifikat', 'ikon' => 'ph-seal-check'],
            // Akun (bukan pengajuan), permintaan user 22 Agt 2026. Sunting/nonaktifkan/reset sandi
            // tetap di Admin_Users; tab ini menautkan ke sana per akun. Lihat Admin_Kemitraan::universitas().
            'universitas'     => ['label' => 'Akun universitas', 'url' => 'Admin_Kemitraan/universitas', 'ikon' => 'ph-bank'],
        ],
    ],
];
$aktif = isset($tab_aktif) ? $tab_aktif : 'pendaftaran_magang';
$m = NULL;
foreach ($menu as $isi) { if (isset($isi['tab'][$aktif])) { $m = $isi; } }
?>
<?php if ($m === NULL): ?>
    <?php // Daftar gabungan lama: tanpa tab, hanya jalan ke dua menu. ?>
    <?php $this->load->view('admin/components/judul_halaman', ['jh_judul' => 'Kemitraan', 'jh_deskripsi' => 'Semua pendaftaran magang dan KKN. Kelola lewat menu Magang atau KKN.',
        'jh_aksi' => '<a href="' . base_url('Admin_Kemitraan/magang') . '" class="tombol-kedua"><i class="ph ph-student"></i><span>Menu Magang</span></a>'
            . '<a href="' . base_url('Admin_Kemitraan/kkn') . '" class="tombol-kedua"><i class="ph ph-graduation-cap"></i><span>Menu KKN</span></a>']); ?>
<?php else: ?>
    <?php // Judul = label sidebar menunya; tab yang menyala menunjukkan bagiannya. ?>
    <?php $this->load->view('admin/components/judul_halaman', ['jh_judul' => $m['judul'], 'jh_deskripsi' => $m['deskripsi'], 'jh_aksi' => $m['aksi']]); ?>

    <div class="mb-5 flex flex-wrap gap-1 border-b border-gray-200 dark:border-white/5">
        <?php foreach ($m['tab'] as $kunci => $t): ?>
            <a href="<?= base_url($t['url']) ?>" class="tombol-tab"<?= $kunci === $aktif ? ' aria-current="page"' : '' ?>><i class="ph <?= $t['ikon'] ?>"></i><span><?= html_escape($t['label']) ?></span></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
