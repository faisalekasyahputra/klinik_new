<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar dokumen persyaratan SRP2 - SATU sumber kebenaran dipakai wizard
 * pemohon (Pengembang::dokumen_persyaratan()) dan verifikasi admin
 * (Admin_Srp2::detail()). Key harus persis sama dengan kunci_dokumen yang
 * tersimpan di srp2_dokumen - jangan diubah tanpa migrasi data.
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
 * `srp2_pengajuan.asosiasi` sudah memakai kode ini sejak migrasi
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

/**
 * Sertifikat pengembang di direktori publik BERLAKU? Satu rumus untuk
 * Pengembang/sertifikasi dan Pengembang/profil: sebelum 27 Sep 2026 profil
 * mencetak "Bersertifikat" tetap, sehingga baris yang di direktori "Tidak
 * berlaku" tampil sah di profilnya. Tanpa tanggal akhir = Tidak berlaku
 * (keputusan 23 Sep 2026, UAT #12/#13).
 */
function srp2_sertifikat_berlaku($row) {
    $akhir = trim((string) ($row->sertifikat_berakhir ?? ''));
    return in_array((string) ($row->status_sertifikasi ?? ''), ['Diterima', 'bersertifikat'], TRUE)
        && $akhir !== '' && strtotime($akhir . ' 23:59:59') >= time();
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

/**
 * Label layar untuk status_verifikasi SRP2. Satu sumber untuk daftar
 * Admin_Srp2/pending dan umpan Aktivitas Terkini di dasbor admin, supaya
 * baris yang sama tidak tertulis "Draft" di satu layar dan "Diminta
 * Perbaikan" di layar lain. Nilai yang disimpan tetap kode Inggrisnya.
 */
if ( ! function_exists('srp2_label_status')) {
    function srp2_label_status() {
        return ['Pending' => 'Menunggu', 'Draft' => 'Diminta Perbaikan', 'Diterima' => 'Diterima', 'Ditolak' => 'Ditolak'];
    }
}

/**
 * Label medan profil perusahaan: SATU sumber untuk formulir admin (Direktori SRP2),
 * Profil Perusahaan pengembang, dan formulir data perusahaan di Profil Saya, supaya
 * medan yang sama tidak berganti nama antar layar (permintaan 2 Okt 2026).
 */
function srp2_label_medan() {
    return [
        'foto_profil'     => 'Foto atau logo',
        'nama_perusahaan' => 'Nama perusahaan',
        'alamat_kantor'   => 'Alamat kantor',
        'kabupaten_id'    => 'Kabupaten/Kota',
        'asosiasi'        => 'Asosiasi',
        'no_keanggotaan'  => 'Nomor keanggotaan asosiasi',
        'nib'             => 'NIB',
        'npwp'            => 'NPWP',
        'no_whatsapp'     => 'Nomor WhatsApp',
        'email_kontak'    => 'Email kontak',
        'website'         => 'Website',
        'instagram'       => 'Instagram',
        'sosmed_lainnya'  => 'Media sosial lainnya',
    ];
}

/**
 * Validasi + normalisasi medan profil perusahaan yang DIKIRIM. Satu aturan untuk ketiga
 * formulir di atas. Kunci yang tidak ada di $masukan tidak disentuh (beda "tidak dikirim"
 * dari "dikirim kosong", lihat Admin_Srp2::save); kunci di luar daftar diabaikan.
 *
 * @return array [array $bersih, string|NULL $galat]; isian kosong menjadi NULL.
 */
function srp2_bersihkan_profil(array $masukan) {
    $label = srp2_label_medan();
    $bersih = [];
    foreach ($masukan as $k => $v) {
        if ( ! is_scalar($v) && $v !== NULL) { return [[], ($label[$k] ?? $k) . ' tidak valid.']; }
        $v = trim((string) $v);
        switch ($k) {
            case 'alamat_kantor':
                if (mb_strlen($v) > 500) { return [[], 'Alamat kantor maksimal 500 karakter.']; }
                break;
            case 'website': case 'instagram': case 'sosmed_lainnya':
                if ($v !== '' && (strlen($v) > 255 || ! filter_var($v, FILTER_VALIDATE_URL)
                    || ! in_array(strtolower((string) parse_url($v, PHP_URL_SCHEME)), ['http', 'https'], TRUE))) {
                    return [[], 'Link ' . $label[$k] . ' harus berupa URL http/https yang valid.'];
                }
                break;
            case 'nib':
                $v = preg_replace('/\D+/', '', $v);
                if ($v !== '' && strlen($v) !== 13) { return [[], 'NIB harus 13 digit angka.']; }
                break;
            case 'no_keanggotaan':
                if (mb_strlen($v) > 50) { return [[], 'Nomor keanggotaan asosiasi maksimal 50 karakter.']; }
                break;
            case 'no_whatsapp':
                $v = preg_replace('/[\s\-+().]/', '', $v);
                if ($v !== '' && ! preg_match('/^[0-9]{8,15}$/', $v)) { return [[], 'Nomor WhatsApp harus 8 sampai 15 digit angka.']; }
                break;
            case 'email_kontak':
                if ($v !== '' && (strlen($v) > 100 || ! filter_var($v, FILTER_VALIDATE_EMAIL))) { return [[], 'Email kontak tidak valid.']; }
                break;
            default:
                continue 2;
        }
        $bersih[$k] = $v === '' ? NULL : $v;
    }
    return [$bersih, NULL];
}

/**
 * Inisial logo tiruan: dua huruf dari nama perusahaan tanpa bentuk badan usaha
 * ("PT. YURIS PRATAMA SEJAHTERA" -> "YP"). Satu kata -> dua huruf pertamanya.
 */
function srp2_inisial($nama) {
    $kata = preg_split('/[^\p{L}\p{N}]+/u', mb_strtoupper((string) $nama), -1, PREG_SPLIT_NO_EMPTY);
    $inti = array_values(array_diff($kata, ['PT', 'CV', 'UD', 'TBK', 'PERSERO', 'FIRMA', 'FA']));
    if ( ! $inti) { $inti = $kata; }
    if ( ! $inti) { return '?'; }
    return count($inti) === 1 ? mb_substr($inti[0], 0, 2) : mb_substr($inti[0], 0, 1) . mb_substr($inti[1], 0, 1);
}

/**
 * Logo tiruan untuk perusahaan yang belum punya foto (permintaan pemilik produk 2 Okt 2026).
 * Hanya tampilan: tidak disimpan ke DB maupun berkas, dan hilang sendiri begitu foto asli
 * diunggah. Warnanya dipilih tetap dari nama (palet gelap ber-teks putih, kontras >= 4.5:1
 * di tema terang maupun gelap). Gaya inline supaya sama di shell admin dan portal publik.
 */
function srp2_logo_mock($nama, $ukuran = 40) {
    $palet = ['#0f766e', '#1d4ed8', '#7c3aed', '#b45309', '#be123c', '#15803d', '#0e7490', '#4338ca'];
    $ukuran = (int) $ukuran;
    $ini = srp2_inisial($nama);
    return '<span role="img" aria-label="' . html_escape('Logo ' . $nama) . '" data-logo-mock="' . html_escape($ini) . '"'
        . ' style="display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;width:' . $ukuran . 'px;height:' . $ukuran . 'px;'
        . 'border-radius:' . max(6, (int) round($ukuran / 4)) . 'px;background:' . $palet[crc32((string) $nama) % count($palet)] . ';color:#fff;'
        . 'font-weight:800;font-size:' . max(10, (int) round($ukuran * 0.38)) . 'px;line-height:1;letter-spacing:.02em">' . html_escape($ini) . '</span>';
}

/** Foto/logo perusahaan bila ada, logo tiruan bila belum. $row butuh nama_perusahaan dan foto_profil. */
function srp2_logo($row, $ukuran = 40) {
    $foto = (string) ($row->foto_profil ?? '');
    if ( ! preg_match('#^assets/img/pengembang/unggahan/[a-f0-9]{32}\.(jpg|png|webp)$#', $foto)) {
        return srp2_logo_mock($row->nama_perusahaan ?? '', $ukuran);
    }
    $ukuran = (int) $ukuran;
    return '<img src="' . base_url($foto) . '" alt="' . html_escape('Logo ' . ($row->nama_perusahaan ?? '')) . '" data-logo-foto'
        . ' width="' . $ukuran . '" height="' . $ukuran . '" loading="lazy"'
        . ' style="flex-shrink:0;width:' . $ukuran . 'px;height:' . $ukuran . 'px;border-radius:' . max(6, (int) round($ukuran / 4)) . 'px;object-fit:cover;background:#fff">';
}
