<?php
/* Syarat dan Ketentuan (/syarat-ketentuan). Aturan yang disebut di sini adalah aturan yang
   memang ditegakkan kode (batas percobaan, ikatan NIK, penyaring kata, gerbang peran), bukan
   janji baru. Kalau perilakunya berubah, halaman ini ikut diubah dan tanggalnya diperbarui. */
$h2 = 'mt-8 text-lg font-black text-[color:var(--portal-text)]';
$p  = 'mt-2 text-sm leading-relaxed text-[color:var(--portal-text-muted)]';
$ul = 'mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-[color:var(--portal-text-muted)]';
$b  = 'text-[color:var(--portal-text)]';
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
<article class="mx-auto max-w-3xl">
    <?php $this->load->view('pages/umum/dokumen_hukum_kepala', [
        'judul_dokumen' => 'Syarat dan Ketentuan',
        'pengantar'     => 'Syarat dan Ketentuan ini mengatur pemakaian Klinik PKP. Dengan mendaftar dan mencentang persetujuan, Anda dianggap telah membaca dan menyetujuinya.',
        'tautan_lain'   => base_url('kebijakan-privasi'),
        'label_lain'    => 'Kebijakan Privasi',
    ]); ?>

    <h2 id="layanan" class="<?= $h2 ?>">1. Tentang layanan</h2>
    <p class="<?= $p ?>">Klinik PKP adalah portal layanan informasi dan konsultasi perumahan serta kawasan permukiman yang dikelola oleh <strong class="<?= $b ?>">Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah</strong> (Disperakim). Cara kami memperlakukan data Anda dijelaskan di <a href="<?= base_url('kebijakan-privasi') ?>" class="font-bold underline" style="color:var(--teal)">Kebijakan Privasi</a>, yang menjadi bagian dari ketentuan ini.</p>

    <h2 id="pengguna" class="<?= $h2 ?>">2. Siapa yang dapat memakai</h2>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">Tanpa akun:</strong> siapa pun dapat membaca informasi publik, mencari rumah, melihat direktori pengembang, memakai Cek Data Rumah, dan memeriksa sertifikat KKN.</li>
        <li><strong class="<?= $b ?>">Akun yang didaftarkan sendiri:</strong> warga, pengembang perumahan, dan mahasiswa.</li>
        <li><strong class="<?= $b ?>">Akun yang dibuat dinas:</strong> universitas, admin kabupaten/kota, admin bidang, dan administrator. Akun ini tidak dapat didaftarkan sendiri.</li>
    </ul>

    <h2 id="akun" class="<?= $h2 ?>">3. Aturan akun</h2>
    <ul class="<?= $ul ?>">
        <li>Satu orang satu akun. Satu NIK hanya dapat terikat ke satu akun.</li>
        <li>Data yang Anda isi harus benar dan milik Anda sendiri. NIK yang dipakai harus NIK Anda.</li>
        <li>Akun dibuat setelah kode verifikasi yang dikirim ke surel Anda dimasukkan dengan benar.</li>
        <li>Sebelum data SIMPERUM untuk sebuah NIK ditampilkan dan diikat ke akun warga, nama lengkap di akun dan tanggal lahir Anda dicocokkan dengan data NIK tersebut. Percobaan yang tidak cocok dibatasi 5 kali sehari.</li>
        <li>Kata sandi minimal 8 karakter dan memuat huruf besar, angka, dan simbol. Jaga kerahasiaannya; tindakan yang dilakukan dengan akun Anda dianggap tindakan Anda.</li>
        <li>Percobaan masuk yang gagal berulang kali akan dihentikan sementara.</li>
    </ul>

    <h2 id="larangan" class="<?= $h2 ?>">4. Yang tidak boleh dilakukan</h2>
    <ul class="<?= $ul ?>">
        <li>Memakai NIK, identitas, atau dokumen milik orang lain, atau mengisi data palsu.</li>
        <li>Mencoba membuka data pengguna lain atau bagian yang bukan hak akses Anda.</li>
        <li>Mengirim permintaan otomatis secara berlebihan, mengganggu, atau mencoba merusak sistem.</li>
        <li>Mengunggah berkas yang tidak berkaitan dengan layanan atau berkas berbahaya.</li>
    </ul>
    <p class="<?= $p ?>">Pelanggaran dapat berakibat pengajuan ditolak dan akun dinonaktifkan oleh administrator.</p>

    <h2 id="aduan-konsultasi" class="<?= $h2 ?>">5. Aduan dan konsultasi</h2>
    <ul class="<?= $ul ?>">
        <li>Gunakan bahasa yang sopan. Topik konsultasi yang memuat kata tidak pantas ditolak otomatis.</li>
        <li>Konsultasi Anda hanya dapat dibaca oleh Anda dan administrator. Komentar yang tidak pantas dapat dilaporkan, dan administrator dapat menutup atau menghapus topik dan komentar.</li>
        <li>Judul aduan dan jawaban dinas tampil di Papan Aduan bagi pengguna lain yang sudah masuk, dengan nama pelapor disingkat menjadi inisial. Jangan menulis data pribadi di judul aduan.</li>
        <li>Pengiriman dibatasi: paling banyak 5 aduan per jam dan 5 topik konsultasi per jam untuk setiap akun.</li>
        <li>Aduan dibaca lebih dulu oleh administrator, lalu diteruskan ke bidang yang menangani.</li>
    </ul>

    <h2 id="rekomendasi" class="<?= $h2 ?>">6. Rekomendasi program bukan keputusan</h2>
    <p class="<?= $p ?>">Rekomendasi program bantuan perumahan dihitung otomatis dari data yang Anda isi dan data SIMPERUM. Rekomendasi bukan jaminan bantuan. <strong class="<?= $b ?>">Keputusan tetap ada di dinas</strong> setelah pengajuan ditinjau petugas, dan statusnya dapat Anda pantau di menu Status Pengajuan pada dashboard.</p>

    <h2 id="pengembang" class="<?= $h2 ?>">7. Pengembang dan direktori SRP2</h2>
    <ul class="<?= $ul ?>">
        <li>Pengajuan Sertifikasi Pengembang (SRP2) ditinjau dinas berdasarkan dokumen yang Anda unggah; dinas dapat meminta perbaikan atau menolaknya.</li>
        <li>Data perusahaan pengembang yang tersertifikasi ditampilkan di direktori publik, termasuk kontak kantor, situs web, media sosial, dan masa berlaku sertifikat. Direktori dikelola oleh dinas.</li>
        <li>Pengembang wajib menjaga data perusahaannya tetap benar melalui menu Profil Perusahaan.</li>
    </ul>

    <h2 id="kemitraan" class="<?= $h2 ?>">8. KKN dan magang</h2>
    <ul class="<?= $ul ?>">
        <li>Kuota dan jadwal KKN serta magang ditentukan dinas. Pendaftaran hanya dapat diubah selama masih berstatus diajukan.</li>
        <li>Universitas bertanggung jawab atas kebenaran daftar peserta KKN yang diunggahnya.</li>
        <li>Sertifikat KKN yang sudah terbit dapat diperiksa siapa pun dengan NIM.</li>
    </ul>

    <h2 id="informasi" class="<?= $h2 ?>">9. Informasi dari pihak lain</h2>
    <p class="<?= $p ?>">Sebagian informasi berasal dari sistem lain, misalnya SIMPERUM Disperakim dan data perumahan SIKUMBANG Tapera. Ketersediaan dan kebenarannya bergantung pada sistem sumber; bila informasi tentang Anda keliru, sampaikan melalui menu Aduan.</p>

    <h2 id="perubahan" class="<?= $h2 ?>">10. Perubahan layanan dan ketentuan</h2>
    <p class="<?= $p ?>">Dinas dapat mengubah, menambah, atau menghentikan fitur, serta memperbarui ketentuan ini. Tanggal di bagian atas halaman menunjukkan pembaruan terakhir. Dengan tetap memakai layanan setelah pembaruan, Anda dianggap menyetujui ketentuan yang baru.</p>

    <h2 id="hukum" class="<?= $h2 ?>">11. Hukum yang berlaku</h2>
    <p class="<?= $p ?>">Ketentuan ini tunduk pada hukum Republik Indonesia, termasuk Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi.</p>

    <h2 id="kontak" class="<?= $h2 ?>">12. Hubungi kami</h2>
    <p class="<?= $p ?>">Pertanyaan tentang ketentuan ini dapat disampaikan melalui menu <a href="<?= base_url('umum/aduan') ?>" class="font-bold underline" style="color:var(--teal)">Aduan</a> (perlu masuk akun).</p>
    <?php if ( ! empty($kontak)): ?>
    <ul class="<?= $ul ?>">
        <?php foreach ($kontak as $label => $nilai): ?><li><?= html_escape($label) ?>: <?= html_escape($nilai) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
</article>
</div>
