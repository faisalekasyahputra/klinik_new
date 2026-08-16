<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar dokumen persyaratan SRP2 - SATU sumber kebenaran dipakai wizard
 * pemohon (Pengembang::dokumen_persyaratan()) dan verifikasi admin
 * (Admin_Srp2::detail()). Key harus persis sama dengan document_key yang
 * tersimpan di srp2_documents - jangan diubah tanpa migrasi data.
 */
/**
 * Keterangan tambahan per formulir - apa yang harus DILAMPIRKAN atau dari mana
 * datanya didapat. Revisi dinas 3 Agt 2026.
 *
 * HELPER TERPISAH, bukan mengubah nilai `srp2_dokumen_persyaratan()` menjadi
 * array. Empat pemakai daftar itu (Pengembang.php:166, :227-237, :295, dan
 * Admin_Srp2.php:103 -> admin/srp2/detail.php) semuanya memperlakukan nilainya
 * sebagai string dan menyambungnya ke pesan. Mengubah bentuknya berarti
 * menyentuh empat tempat demi satu tampilan - dan salah satunya pesan validasi
 * yang akan berubah jadi "Array" tanpa satu galat pun.
 *
 * ⚠️ BELUM LENGKAP, DAN ITU DISENGAJA. Dinas baru memberi CONTOH: "missal form
 * 4, lampirkan ktp, melampirkan SPT pph dst" plus form 10 & 11. Kata "missal"
 * dan "dst" menandakan daftarnya belum utuh. Sebelas formulir sisanya
 * DIBIARKAN KOSONG - mengarang syarat dokumen resmi jauh lebih berbahaya
 * daripada membiarkannya kosong, karena pemohon menyiapkan berkas berdasarkan
 * apa yang tertulis di sini. Tambahkan hanya setelah daftar resminya diterima.
 */
function srp2_keterangan_persyaratan() {
    return [
        'form_4'  => 'Lampirkan KTP dan SPT PPh.',
        'form_10' => 'Data bisa didapatkan dari asosiasi.',
        'form_11' => 'Data bisa didapatkan dari asosiasi.',
    ];
}

/**
 * Daftar asosiasi pengembang - SATU sumber kebenaran, dipakai formulir
 * pengembang (pages/pengaturan/profil.php), formulir admin (admin/srp2/
 * index.php + tambah.php), validasi keduanya (Pengaturan::update_pengembang_profile()
 * dan Admin_Srp2::save()), serta semua tempat yang MENAMPILKANNYA (direktori
 * publik, profil publik, detail admin).
 *
 * Kunci = yang tersimpan di DB (huruf kecil, jangan diubah tanpa migrasi data -
 * `srp2_registrations.asosiasi` sudah memakai kode ini sejak migrasi
 * 20260701000001). Nilai = yang dibaca orang.
 *
 * SUMBERNYA TABEL `srp2_asosiasi` sejak 14 Agt 2026 (migrasi 042), bukan lagi
 * daftar mati di sini - dinas mengelolanya sendiri lewat Admin_Asosiasi. Yang
 * masih tertulis di bawah cuma CADANGAN kalau tabelnya belum ada.
 *
 * Hasilnya di-cache per-request (`static`): srp2_label_asosiasi() dipanggil
 * SEKALI PER BARIS di direktori publik, dan tanpa cache itu 67 query untuk
 * satu halaman.
 *
 * $termasuk_nonaktif: FALSE (bawaan) untuk MENAWARKAN pilihan di formulir -
 * yang sudah dinonaktifkan tidak boleh dipilih lagi. TRUE untuk MEMBACA nilai
 * yang terlanjur tersimpan, supaya baris lama tidak mendadak menampilkan kode
 * mentah begitu asosiasinya dinonaktifkan.
 *
 * ⚠️ MENGGANTI KEPUTUSAN LAMA, SENGAJA. Admin_Srp2::save() dulu menerima
 * KETIK BEBAS dengan alasan "sampai dinas mengirim daftar resminya, mengarang
 * daftar sendiri berarti memaksa pengembang memilih asosiasi yang mungkin
 * bukan miliknya". Diubah 14 Agt 2026 atas permintaan eksplisit user (setelah
 * ditunjukkan konsekuensinya): dua sisi yang sama menyimpan bentuk berbeda
 * ("REI" vs "rei") membuat kolom asosiasi di direktori publik tidak mungkin
 * seragam. Kekhawatiran lama tetap dijawab oleh `lainnya` - itu jalan keluar
 * untuk asosiasi di luar empat yang tercatat, bukan pemaksaan.
 */
function srp2_daftar_asosiasi($termasuk_nonaktif = FALSE) {
    static $cache = [];

    $kunci = $termasuk_nonaktif ? 'semua' : 'aktif';
    if (isset($cache[$kunci])) { return $cache[$kunci]; }

    $CI =& get_instance();

    /* Cadangan kalau tabelnya belum ada - mis. lingkungan yang migrasinya
       belum dijalankan. Mengembalikan array kosong akan membuat SELURUH
       formulir asosiasi kehilangan pilihannya tanpa satu pun galat, dan
       validasi menolak semua isian yang sah. Nilainya sama persis dengan
       seed migrasi 042. */
    if ( ! $CI->db->table_exists('srp2_asosiasi')) {
        return $cache[$kunci] = [
            'rei' => 'REI', 'himperra' => 'HIMPERRA', 'apersi' => 'APERSI',
            'pi' => 'PI', 'lainnya' => 'Lainnya',
        ];
    }

    $CI->db->select('kode, nama')->from('srp2_asosiasi');
    if ( ! $termasuk_nonaktif) { $CI->db->where('aktif', 1); }
    $baris = $CI->db->order_by('urutan', 'ASC')->order_by('nama', 'ASC')->get()->result();

    $daftar = [];
    foreach ($baris as $b) { $daftar[$b->kode] = $b->nama; }
    return $cache[$kunci] = $daftar;
}

/**
 * Kode asosiasi -> label yang dibaca orang. Kode yang TIDAK dikenal
 * dikembalikan apa adanya, bukan dijadikan $kosong: 67 baris direktori
 * sekarang NULL, tapi kalau kelak ada data lama bertuliskan bebas (mis.
 * "REI Jateng") menyembunyikannya justru membuat admin mengira kolomnya
 * belum diisi lalu menimpanya.
 */
function srp2_label_asosiasi($kode, $kosong = '-') {
    $kode = trim((string) $kode);
    if ($kode === '') { return $kosong; }
    // TRUE: ini MEMBACA nilai tersimpan, bukan menawarkan pilihan. Asosiasi
    // yang dinonaktifkan admin tetap harus tampil sebagai namanya di baris
    // yang terlanjur memakainya - bukan berubah jadi "rei" mentah.
    $daftar = srp2_daftar_asosiasi(TRUE);
    return $daftar[$kode] ?? $kode;
}

function srp2_dokumen_persyaratan() {
    return [
        'form_1'  => 'Form 1 - Surat Permohonan SRP2',
        'form_2a' => 'Form 2.A - Data Administrasi dan Identitas Pengembang',
        'form_2b' => 'Form 2.B - Data Administrasi dan Data Pengurus',
        'form_3'  => 'Form 3 - Pernyataan Bukan ASN',
        'form_4'  => 'Form 4 - Laporan Keuangan dan Data Kepemilikan',
        'form_5'  => 'Form 5 - Ketersediaan SDM Penanggung Jawab Teknis',
        'form_6'  => 'Form 6 - Pengalaman Pekerjaan',
        'form_6b' => 'Form 6B - Rekomendasi Perusahaan Baru',
        'form_7'  => 'Form 7 - Kesanggupan Penyampaian Laporan',
        'form_8'  => 'Form 8 - Kebenaran Data',
        'form_9'  => 'Form 9 - Pakta Integritas',
        'form_10' => 'Form 10 - BA Verifikasi dan Validasi',
        'form_11' => 'Form 11 - BA Klasifikasi dan Kualifikasi',
        'form_13' => 'Form 13 - Laporan Pembangunan Perumahan',
    ];
}
