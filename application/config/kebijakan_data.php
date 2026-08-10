<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sakelar kebijakan tampilan data pribadi. SATU TEMPAT, sengaja.
 *
 * Berkas ini bukan pengaturan teknis - isinya keputusan yang menunggu manusia.
 * Karena itu ia berdiri sendiri dan tidak dititipkan ke config lain: orang yang
 * kelak memutuskan harus bisa menemukannya tanpa membaca kode.
 */

/**
 * BUTIR B2 REVISI DINAS - identitas warga di layar "Antrean Wilayah Saya".
 *
 * Dinas melaporkan dashboard kab/kota memunculkan data warga. Batas WILAYAH-nya
 * sudah diuji dan mengikat (`uji_antrean_kabkota_scope.php`): admin kabupaten A
 * tidak bisa melihat, membuka, mengunduh, atau mengubah pengajuan kabupaten B.
 *
 * Yang TERSISA bukan kerusakan, melainkan pertanyaan kebijakan: BOLEHKAH admin
 * kab/kota melihat identitas dan keadaan ekonomi warga di wilayahnya sendiri?
 * Layar itu menampilkan nama, NIK empat digit terakhir, pekerjaan, penghasilan,
 * desil, dan status kepemilikan rumah. Semuanya diperlukan untuk menilai
 * pengajuan - dan semuanya data pribadi. Itu bukan keputusan kami.
 *
 * Selama menunggu, nilainya `menunggu_keputusan`: layar tetap hidup dan bisa
 * diuji, tetapi identitas serta keadaan ekonomi diganti DATA CONTOH. Pilihan
 * ini disengaja daripada mematikan layarnya - dinas tetap bisa menilai bentuk
 * dan alurnya, tanpa satu pun data warga sungguhan tersaji sebelum diputuskan.
 *
 * Ubah ke `tampil` begitu dinas memutuskan boleh. Tidak ada tempat lain yang
 * perlu disentuh, dan penjaganya (`uji_antrean_kabkota_scope.php`) memeriksa
 * kedua nilai itu.
 */
$config['identitas_warga_kabkota'] = 'menunggu_keputusan';
