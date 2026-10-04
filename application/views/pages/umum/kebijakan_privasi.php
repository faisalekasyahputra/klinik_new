<?php
/* Kebijakan Privasi (/kebijakan-privasi). Setiap klaim di sini ditulis dari kode, bukan
   dikarang: retensi dari config/data_lifecycle.php, pihak luar dari register `pertukaran`
   di berkas yang sama, penyamaran kab/kota dari config/kebijakan_data.php. Kalau perilaku
   aplikasi berubah, halaman ini ikut diubah dan tanggalnya diperbarui. */
$h2 = 'mt-8 text-lg font-black text-[color:var(--portal-text)]';
$p  = 'mt-2 text-sm leading-relaxed text-[color:var(--portal-text-muted)]';
$ul = 'mt-2 list-disc space-y-1 pl-5 text-sm leading-relaxed text-[color:var(--portal-text-muted)]';
$b  = 'text-[color:var(--portal-text)]';
$lp = $this->config->item('data_lifecycle');
if ( ! is_array($lp)) { $this->config->load('data_lifecycle', FALSE, TRUE); $lp = $this->config->item('data_lifecycle'); }
$r  = $lp['retensi'] ?? [];
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
<article class="mx-auto max-w-3xl">
    <?php $this->load->view('pages/umum/dokumen_hukum_kepala', [
        'judul_dokumen' => 'Kebijakan Privasi',
        'pengantar'     => 'Halaman ini menjelaskan data apa yang dikumpulkan Klinik PKP, untuk apa, siapa yang dapat melihatnya, berapa lama disimpan, dan apa hak Anda atas data tersebut.',
        'tautan_lain'   => base_url('syarat-ketentuan'),
        'label_lain'    => 'Syarat dan Ketentuan',
    ]); ?>

    <h2 id="pengelola" class="<?= $h2 ?>">1. Siapa pengelola data Anda</h2>
    <p class="<?= $p ?>">Klinik PKP (Klinik Perumahan dan Kawasan Permukiman) dikelola oleh <strong class="<?= $b ?>">Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah</strong> (Disperakim). Disperakim adalah pengendali data pribadi yang Anda serahkan melalui aplikasi ini.</p>

    <h2 id="data-dikumpulkan" class="<?= $h2 ?>">2. Data yang kami kumpulkan</h2>
    <p class="<?= $p ?>">Yang kami simpan bergantung pada layanan yang Anda pakai.</p>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">Semua akun:</strong> alamat surel, nama lengkap, nama pengguna, kata sandi (disimpan sebagai sandi teracak, tidak dapat dibaca), nomor HP, alamat domisili, peran akun, dan foto profil bila Anda masuk dengan Google.</li>
        <li><strong class="<?= $b ?>">Warga:</strong> NIK, nomor KK, tanggal lahir, jenis kelamin, status perkawinan, pendidikan, pekerjaan, penghasilan bulanan, kepemilikan tabungan, dan kemampuan swadaya; data rumah dan lahan (status kepemilikan, luas, jumlah penghuni dan KK, kondisi bangunan, air bersih, sanitasi, penerangan, bahan bakar memasak, riwayat bantuan); foto bukti kondisi rumah; serta titik lokasi rumah bila Anda mengizinkan peramban membagikannya.</li>
        <li><strong class="<?= $b ?>">Pengembang:</strong> data penanggung jawab (nama, NIK KTP, jabatan, nomor WhatsApp, surel), data perusahaan (nama, NIB, NPWP, asosiasi, nomor keanggotaan, alamat kantor, situs web dan media sosial), serta dokumen persyaratan Sertifikasi Pengembang (SRP2) seperti KTP, KTA, NIB, dan surat tugas.</li>
        <li><strong class="<?= $b ?>">Mahasiswa dan universitas:</strong> NIM, tempat dan tanggal lahir, semester, jurusan, instansi asal, nomor HP, periode dan tema atau bidang KKN/magang, berkas surat pengantar, proposal, laporan akhir, surat balasan, tautan dokumentasi, serta daftar peserta KKN (nama dan NIM) yang diunggah universitas.</li>
        <li><strong class="<?= $b ?>">Aduan dan konsultasi:</strong> nama, surel, judul, isi, dan lampiran aduan; topik dan komentar konsultasi beserta alamat IP pengirimnya; permintaan janji temu (alasan dan jadwal).</li>
        <li><strong class="<?= $b ?>">Data teknis:</strong> alamat IP dan waktu pada jejak audit, penghitung pembatas percobaan, log aplikasi (terenkripsi), dan data langganan notifikasi peramban (alamat layanan notifikasi dan jenis peramban).</li>
    </ul>

    <h2 id="sumber" class="<?= $h2 ?>">3. Dari mana data itu berasal</h2>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">Isian Anda sendiri</strong> saat mendaftar, melengkapi profil, mengisi pendataan, atau mengirim aduan.</li>
        <li><strong class="<?= $b ?>">SIMPERUM Disperakim</strong> (data rumah tidak layak huni). Data SIMPERUM untuk NIK Anda baru ditampilkan dan diikat ke akun setelah nama lengkap di akun dan tanggal lahir Anda cocok dengan data NIK tersebut. Pada Cek Data Rumah tanpa masuk, NIK yang Anda ketik dikirim ke SIMPERUM dan yang ditampilkan hanya status pencarian dan status bantuan; hasilnya tidak diikat ke akun mana pun.</li>
        <li><strong class="<?= $b ?>">Google</strong>, bila Anda memilih masuk dengan Google: nama, surel, dan foto profil akun Google Anda.</li>
        <li><strong class="<?= $b ?>">Petugas dinas</strong>, berupa catatan peninjauan dan keputusan atas pengajuan Anda, serta <strong class="<?= $b ?>">universitas</strong>, berupa daftar peserta KKN.</li>
    </ul>

    <h2 id="tujuan" class="<?= $h2 ?>">4. Untuk apa data dipakai</h2>
    <ul class="<?= $ul ?>">
        <li>Membuat dan mengamankan akun Anda, termasuk mengirim kode verifikasi ke surel saat mendaftar.</li>
        <li>Pendataan rumah dan pemberian rekomendasi program bantuan perumahan yang sesuai dengan keadaan Anda.</li>
        <li>Memproses pengajuan Anda di antrean dinas sampai ada keputusan, dan menampilkan statusnya kepada Anda.</li>
        <li>Sertifikasi pengembang (SRP2) dan penayangan direktori pengembang tersertifikasi.</li>
        <li>Kemitraan KKN dan magang, termasuk surat balasan dan sertifikat KKN.</li>
        <li>Menindaklanjuti aduan dan konsultasi, serta mengirim notifikasi bila Anda mengizinkannya.</li>
        <li>Menyusun rekap layanan untuk pemantauan dan perencanaan dinas.</li>
        <li>Menjaga keamanan: mencatat akses petugas ke data pribadi, membatasi percobaan berulang, dan menyelidiki penyalahgunaan.</li>
    </ul>
    <p class="<?= $p ?>">Saat mendaftar Anda menyatakan setuju dengan Syarat dan Ketentuan serta Kebijakan Privasi ini; persetujuan itu dicatat (akun dan waktunya) di jejak audit.</p>

    <h2 id="siapa-melihat" class="<?= $h2 ?>">5. Siapa yang dapat melihat data Anda</h2>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">Anda sendiri</strong>, melalui akun Anda.</li>
        <li><strong class="<?= $b ?>">Administrator dinas</strong> (super admin): data layanan yang diperlukan untuk meninjau dan memutuskan. Akses petugas ke berkas pribadi, profil warga, dan NPWP dicatat di jejak audit.</li>
        <li><strong class="<?= $b ?>">Admin kabupaten/kota:</strong> hanya pengajuan dari wilayahnya sendiri. Selama dinas belum memutuskan apakah admin kabupaten/kota boleh melihat identitas warga, nama, NIK, dan keadaan ekonomi warga di layar mereka diganti data contoh.</li>
        <li><strong class="<?= $b ?>">Admin bidang:</strong> hanya aduan yang diteruskan ke bidangnya dan pendaftaran KKN/magang di bidangnya.</li>
        <li><strong class="<?= $b ?>">Pengguna lain:</strong> konsultasi Anda hanya terlihat oleh Anda dan administrator. Papan Aduan menampilkan judul aduan, inisial nama pelapor, dan jawaban dinas kepada pengguna yang sudah masuk; isi, surel, dan lampiran aduan tidak ditampilkan. Data perusahaan pengembang tersertifikasi tampil di direktori publik. Sertifikat KKN yang sudah terbit dapat dicek siapa pun dengan NIM (menampilkan nama, NIM, universitas, tema, dan periode).</li>
    </ul>
    <p class="<?= $p ?>">Kami tidak menjual data pribadi Anda. Pihak luar yang menerima sebagian data agar layanan dapat berjalan:</p>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">SIMPERUM Disperakim</strong>: NIK untuk pencarian data rumah, melalui koneksi terenkripsi (HTTPS).</li>
        <li><strong class="<?= $b ?>">Google</strong>: proses masuk dengan Google; dan pemeriksaan anti-robot (reCAPTCHA) bila fitur itu diaktifkan, yang menerima token pemeriksaan dan alamat IP Anda.</li>
        <li><strong class="<?= $b ?>">Penyedia layanan surel</strong>: alamat surel Anda dan kode verifikasi saat mendaftar.</li>
        <li><strong class="<?= $b ?>">Layanan notifikasi peramban</strong> (milik pembuat peramban Anda): isi notifikasi umum tanpa data pribadi, hanya bila Anda mengaktifkan notifikasi.</li>
        <li><strong class="<?= $b ?>">Penyedia server (hosting)</strong> tempat aplikasi dan basis data berjalan.</li>
        <li><strong class="<?= $b ?>">Penyedia pustaka tampilan dan peta</strong>: peramban Anda memuat sebagian berkas tampilan dan gambar peta dari server mereka, sehingga mereka dapat melihat alamat IP dan jenis peramban Anda.</li>
    </ul>

    <h2 id="keamanan" class="<?= $h2 ?>">6. Cara kami menjaga data</h2>
    <ul class="<?= $ul ?>">
        <li>Seluruh lalu lintas memakai HTTPS (TLS 1.2 ke atas).</li>
        <li>NIK, nomor KK, dan NPWP; nama, alamat, nomor HP, dan tanggal lahir di profil warga; titik lokasi rumah; serta salinan data SIMPERUM disimpan terenkripsi AES-256-GCM. Pencarian NIK memakai sidik digital berkunci (hash) sehingga NIK tidak perlu dibuka untuk dicari.</li>
        <li>Kata sandi disimpan dengan bcrypt dan tidak dapat dibaca siapa pun, termasuk petugas.</li>
        <li>Berkas unggahan disimpan di luar folder publik dan hanya dapat dibuka lewat pintu yang memeriksa hak akses.</li>
        <li>Akses dibatasi menurut peran, wilayah, dan bidang; percobaan berulang (masuk, verifikasi NIK, ekspor data) dibatasi; tindakan penting dan akses petugas ke data pribadi dicatat di jejak audit.</li>
    </ul>
    <p class="<?= $p ?>">Tidak semua data dienkripsi. Isi aduan dan konsultasi, misalnya, disimpan biasa dengan pembatasan akses. Hindari menulis data pribadi yang tidak perlu di dalamnya.</p>

    <h2 id="retensi" class="<?= $h2 ?>">7. Berapa lama data disimpan</h2>
    <p class="<?= $p ?>">Penghapusan otomatis di bawah dijalankan sekali sehari.</p>
    <ul class="<?= $ul ?>">
        <li>Data akun dan profil: selama akun Anda ada.</li>
        <li>Salinan hasil pencarian SIMPERUM: berlaku 30 hari bila data ditemukan dan 1 hari bila tidak, lalu dihapus <?= (int) ($r['snapshot_simperum_lewat_hari'] ?? 7) ?> hari setelah kedaluwarsa, kecuali yang menjadi bukti asal sebuah pengajuan yang sudah dikirim.</li>
        <li>Draf pendataan yang dilepas karena NIK dipindahkan ke pemilik yang terverifikasi: <?= (int) ($r['draf_nik_dipindah_hari'] ?? 30) ?> hari.</li>
        <li>Kode verifikasi surel berlaku 10 menit; token surel yang kedaluwarsa dihapus <?= (int) ($r['token_surel_lewat_hari'] ?? 1) ?> hari kemudian.</li>
        <li>Penghitung pembatas percobaan: <?= (int) ($r['rate_limit_hari'] ?? 2) ?> hari.</li>
        <li>Langganan notifikasi yang sudah dimatikan: <?= (int) ($r['langganan_push_nonaktif_hari'] ?? 90) ?> hari.</li>
        <li>Log aplikasi: <?= (int) ($r['log_aplikasi_hari'] ?? 180) ?> hari.</li>
        <li>Jejak audit: <?= (int) round(($r['jejak_audit_hari'] ?? 1825) / 365) ?> tahun.</li>
        <li>Sesi masuk berakhir setelah sekitar 2 jam tanpa aktivitas.</li>
    </ul>
    <p class="<?= $p ?>">Pengajuan yang sudah Anda kirim beserta keputusan dinas, aduan, laporan rekam data, dan entri direktori pengembang adalah <strong class="<?= $b ?>">arsip layanan dinas</strong>. Arsip ini tetap disimpan setelah akun dihapus. Tautannya ke akun dilepas, tetapi isinya (misalnya nama dan surel pada aduan) tetap ada sampai ditinjau lewat permintaan penghapusan data layanan (lihat bagian 8).</p>

    <h2 id="hak" class="<?= $h2 ?>">8. Hak Anda</h2>
    <p class="<?= $p ?>">Sesuai Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi, Anda dapat:</p>
    <ul class="<?= $ul ?>">
        <li><strong class="<?= $b ?>">Melihat dan mengunduh data Anda.</strong> Buka Profil Saya, bagian Unduh Data Saya. Anda akan mendapat berkas JSON berisi data akun dan catatan layanan yang terkait dengan akun (berkas unggahan tidak termasuk). Kata sandi diperlukan.</li>
        <li><strong class="<?= $b ?>">Memperbaiki data.</strong> Ubah data di Profil Saya. Surel tidak dapat diubah, dan NIK terkunci setelah tersimpan; bila keliru, sampaikan lewat menu Aduan agar petugas membetulkannya.</li>
        <li><strong class="<?= $b ?>">Menghapus akun.</strong> Di Profil Saya pilih Hapus Akun Saya (kata sandi diperlukan). Ini menghapus akun, profil warga, draf pendataan beserta fotonya, data SIMPERUM yang tersimpan untuk akun Anda, pengajuan SRP2 dan berkasnya, pendaftaran KKN/magang dan berkasnya, dokumen akun, janji temu, dan langganan notifikasi. Topik dan komentar konsultasi tidak dihapus tetapi dianonimkan, dan surel Anda di jejak audit disamarkan.</li>
        <li><strong class="<?= $b ?>">Meminta penghapusan arsip layanan.</strong> Arsip layanan (lihat bagian 7) tidak ikut terhapus bersama akun. Gunakan tombol Ajukan Penghapusan Data Layanan di Profil Saya; petugas akan meninjau mana yang dapat dihapus dan mana yang wajib diarsipkan.</li>
        <li><strong class="<?= $b ?>">Menarik persetujuan.</strong> Anda dapat berhenti memakai layanan dan menghapus akun kapan saja, serta mematikan notifikasi peramban.</li>
        <li><strong class="<?= $b ?>">Mengajukan keberatan atau pertanyaan</strong> tentang pemrosesan data Anda melalui menu Aduan.</li>
    </ul>

    <h2 id="cookie" class="<?= $h2 ?>">9. Cookie</h2>
    <p class="<?= $p ?>">Klinik PKP hanya memasang dua cookie yang diperlukan agar aplikasi berfungsi: cookie sesi (menjaga Anda tetap masuk) dan cookie perlindungan formulir (CSRF). Kami tidak memakai cookie iklan atau alat analitik pelacak. Bila pemeriksaan reCAPTCHA Google aktif di halaman masuk atau daftar, Google dapat memasang cookie miliknya sendiri. Dashboard menyimpan pilihan tampilan terang atau gelap di penyimpanan peramban Anda.</p>

    <h2 id="perubahan" class="<?= $h2 ?>">10. Perubahan kebijakan</h2>
    <p class="<?= $p ?>">Kebijakan ini dapat diperbarui bila cara kerja aplikasi berubah. Tanggal di bagian atas halaman menunjukkan pembaruan terakhir.</p>

    <h2 id="kontak" class="<?= $h2 ?>">11. Hubungi kami</h2>
    <p class="<?= $p ?>">Pertanyaan, permintaan, atau keberatan tentang data pribadi dapat disampaikan melalui menu <a href="<?= base_url('umum/aduan') ?>" class="font-bold underline" style="color:var(--teal)">Aduan</a> (perlu masuk akun).</p>
    <?php if ( ! empty($kontak)): ?>
    <ul class="<?= $ul ?>">
        <?php foreach ($kontak as $label => $nilai): ?><li><?= html_escape($label) ?>: <?= html_escape($nilai) ?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
</article>
</div>
