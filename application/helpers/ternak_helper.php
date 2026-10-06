<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Label jejak audit menerjemahkan objek_tipe lama (sebelum migrasi 072); helper ini juga dimuat tanpa autoload oleh suite.
require_once __DIR__ . '/kunci_tersimpan_helper.php';

/**
 * Menyusun URL absolut untuk aset file atau gambar
 */
if ( ! function_exists('api_image_url')) {
    function api_image_url($path) {
        if (empty($path)) {
            // Bisa return placeholder gambar default jika kosong
            return 'assets/img/default-placeholder.svg';
        }
        
        // Cek jika path sudah berupa URL HTTP utuh
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        $CI =& get_instance();
        $CI->load->config('ternak_api');
        
        // Karena base api url diakhiri dengan /api, kita ambil rootnya
        $api_base = str_replace('/api', '', $CI->config->item('ternak_api_url'));
        
        return rtrim($api_base, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Format tanggal ISO ke format yang bisa dibaca di Indonesia
 */
if ( ! function_exists('format_tanggal_api')) {
    function format_tanggal_api($iso_date_string) {
        // Diteruskan ke tgl_id supaya bulannya Indonesia (Okt, bukan Oct).
        return tgl_id($iso_date_string, TRUE, TRUE);
    }
}

/**
 * Avatar inisial sebagai data URI SVG - tanpa permintaan jaringan.
 *
 * Menggantikan `https://ui-avatars.com/api/?name=...`, yang mengirim NAMA ASLI
 * pengguna ke server pihak ketiga pada SETIAP pemuatan halaman, untuk setiap
 * peran yang login - bersama IP dan referer-nya. Di portal dinas, itu berarti
 * nama warga, pengembang, mahasiswa, dan admin mengalir ke luar terus-menerus
 * untuk sesuatu yang hasilnya hanya dua huruf di atas kotak berwarna.
 *
 * Sekalian menghapus satu titik gagal: kalau ui-avatars tidak bisa dijangkau,
 * avatar di seluruh portal berubah jadi ikon gambar rusak.
 *
 * @param string $nama  Nama yang diambil inisialnya
 * @param string $bg    Warna latar (default kuning-hijau merek)
 * @param string $fg    Warna huruf
 */
if ( ! function_exists('avatar_inisial')) {
    function avatar_inisial($nama, $bg = '#d6fb00', $fg = '#0a1a1f') {
        $nama = trim((string) $nama);
        if ($nama === '') { $nama = 'Pengguna'; }

        // Dua inisial dari dua kata pertama; satu huruf kalau namanya satu kata.
        $kata = preg_split('/\s+/', $nama, -1, PREG_SPLIT_NO_EMPTY);
        $inisial = mb_strtoupper(mb_substr($kata[0], 0, 1));
        if (count($kata) > 1) { $inisial .= mb_strtoupper(mb_substr($kata[count($kata) - 1], 0, 1)); }

        // `text-anchor` + `dominant-baseline` dipakai supaya tidak perlu
        // menghitung posisi teks sendiri untuk setiap panjang inisial.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="80" height="80">'
             . '<rect width="80" height="80" fill="' . $bg . '"/>'
             . '<text x="50%" y="50%" text-anchor="middle" dominant-baseline="central" '
             . 'font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif" '
             . 'font-size="34" font-weight="700" fill="' . $fg . '">'
             . htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

/**
 * Tanggal berbahasa Indonesia.
 *
 * `date('j F Y')` mengeluarkan "2 August 2026" - nama bulan Inggris di tengah
 * layar berbahasa Indonesia. Ketahuan 2 Agt 2026 waktu halaman pendaftaran
 * mahasiswa dibuka di browser; tidak akan pernah tertangkap harness HTTP,
 * karena responsnya 200 dan isinya "benar" bagi mesin.
 *
 * `strftime()` sengaja tidak dipakai: deprecated sejak PHP 8.1 dan bergantung
 * pada locale sistem yang di Windows tidak menyediakan id_ID.
 *
 * Satu helper ini dipakai untuk SEMUA tanggal dan waktu yang tampil di layar,
 * supaya tidak ada lagi campuran "2026-10-02 01:22:00" (ISO mentah), "02 Oct
 * 2026" (bulan Inggris), dan "2 Okt 2026" di aplikasi yang sama. Hanya
 * tampilan: nilai yang disimpan di basis data tetap Y-m-d H:i:s.
 *
 * @param string $tanggal  Apa pun yang dimengerti strtotime()
 * @param bool   $pendek   TRUE untuk "Agt", FALSE untuk "Agustus"
 * @param bool   $jam      TRUE menambahkan jam: "2 Okt 2026, 01.22 WIB"
 *                         (titik sebagai pemisah jam sesuai ejaan Indonesia;
 *                         WIB karena aplikasi berjalan di Asia/Jakarta).
 */
if ( ! function_exists('tgl_id')) {
    function tgl_id($tanggal, $pendek = FALSE, $jam = FALSE) {
        if (empty($tanggal)) { return '-'; }
        $ts = strtotime($tanggal);
        if ($ts === FALSE) { return '-'; }

        $panjang = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $singkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                    'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulan = ($pendek ? $singkat : $panjang)[(int) date('n', $ts) - 1];

        return date('j', $ts) . ' ' . $bulan . ' ' . date('Y', $ts)
            . ($jam ? ', ' . date('H.i', $ts) . ' WIB' : '');
    }
}



/**
 * Angka bulat berpemisah ribuan Indonesia: 4238 -> "4.238".
 *
 * number_format() tanpa argumen memakai koma ("4,238"), yang di layar dinas terbaca
 * sebagai desimal (audit UI 2 Okt 2026: "Riwayat Tindakan (4,238)" di Jejak Audit).
 */
if ( ! function_exists("angka_id")) {
    function angka_id($n) {
        return number_format((float) $n, 0, ",", ".");
    }
}

/**
 * Nomor WhatsApp (628xxx) dari teks telepon bebas, atau '' kalau tidak ada nomor HP.
 *
 * Data hulu (SIKUMBANG, profil pengembang) sering berisi telepon kantor, dua nomor
 * dalam satu kolom ("0271-593507 081393090297"), atau nomor tanpa kode area. Menghapus
 * semua non-digit menggabungkan nomor itu jadi tautan wa.me yang rusak, jadi yang
 * diambil hanya nomor HP pertama (08..., 628..., +62 8...). Telepon rumah/kantor
 * menghasilkan '' supaya tampilan menunjukkan WhatsApp tidak tersedia.
 * Panjang: 08 lalu 7-11 digit (paling panjang 13 digit), nomor HP 13 digit ikut sah.
 */
if ( ! function_exists('nomor_whatsapp')) {
    function nomor_whatsapp($teks) {
        // Pemisah boleh lebih dari satu karakter: data SIKUMBANG memuat "0821 - 3553 - 9740".
        if ( ! preg_match_all('/(?<!\d)(?:\+?62|0)[\s.-]*8(?:[\s.-]*\d){7,11}(?!\d)/', (string) $teks, $m)) {
            return '';
        }
        foreach ($m[0] as $calon) {
            $digit = preg_replace('/\D/', '', $calon);
            $digit = $digit[0] === '0' ? '62' . substr($digit, 1) : $digit;
            if (preg_match('/^628\d{7,11}$/', $digit)) {
                return $digit;
            }
        }
        return '';
    }
}

/*
 * Jejak Audit dibaca staf dinas, bukan pengembang (audit UI 2 Okt 2026). Baris tersimpan
 * memakai kode dan nama tabel ("akses_npwp_srp2", "srp2_pengajuan#daftar"); di sini
 * kode itu diterjemahkan SAAT TAMPIL. Baris di sys_jejak_audit tidak pernah diubah:
 * jejak audit yang ditulis ulang bukan bukti lagi.
 * ponytail: satu kamus kecil; kode yang belum terdaftar tetap tampil dengan garis bawah jadi spasi.
 */
if ( ! function_exists('audit_kamus')) {
    function audit_kamus() {
        return [
            // Label aksi yang tidak cukup dengan aturan umum audit_label_aksi().
            'aksi' => [
                'role_diubah'            => 'Peran diubah',
                'role_diubah_ditolak'    => 'Perubahan peran ditolak',
                'privilege_admin_diubah' => 'Hak modul admin diubah',
                'nik_dipindahkan'        => 'NIK dipindahkan ke pemilik terverifikasi',
                'klaim_nik_diajukan'     => 'Klaim NIK diajukan, menunggu tinjauan',
                'klaim_nik_disetujui'    => 'Klaim NIK disetujui',
                'klaim_nik_ditolak'      => 'Klaim NIK ditolak',
                'reset_nik_diajukan'     => 'Reset NIK diminta warga, menunggu tinjauan',
                'reset_nik_permintaan_disetujui' => 'Permintaan reset NIK disetujui',
                'reset_nik_permintaan_ditolak'   => 'Permintaan reset NIK ditolak',
                'superadmin_dibuat_cli'  => 'Super Admin dibuat lewat CLI',
                'akun_demo_dinonaktifkan' => 'Akun demo dinonaktifkan (migrasi)',
            ],
            // Jenis data pribadi dari MY_Controller::catat_akses_data_pribadi().
            'jenis' => [
                'berkas_privat'    => 'berkas lampiran',
                'identitas_warga'  => 'identitas warga',
                'aduan_warga'      => 'data pelapor aduan',
                'npwp_srp2'        => 'NPWP pengembang',
                'pengajuan_srp2'   => 'data pengajuan SRP2',
                'konsultasi_warga' => 'isi konsultasi warga',
                'penilaian_warga'  => 'hasil penilaian rumah warga',
            ],
            // objek_tipe: nama tabel atau domain.
            'objek' => [
                'aduan'                     => 'aduan',
                'akun_terkunci'             => 'akun terkunci',
                'login_beruntun'            => 'login gagal beruntun',
                'batas_laju'                => 'batas percobaan',
                'berkas_berbahaya'          => 'berkas berbahaya',
                'bidang'                    => 'bidang',
                'bot_form'                  => 'isian formulir otomatis',
                'eskalasi'                  => 'eskalasi',
                'forum_diskusi'             => 'konsultasi',
                'forum_janji_temu'          => 'janji temu',
                'kabupaten'                 => 'kabupaten',
                'kemitraan'                 => 'pengajuan KKN & Magang',
                'kkn_magang_bidang'         => 'magang bidang',
                'kkn_magang_pendaftaran'    => 'pendaftaran magang',
                'kkn_magang_posisi'         => 'posisi magang',
                'psu_serah_terima'          => 'serah terima PSU',
                'rd_laporan'                => 'laporan rekam data',
                'rekam_bnba'                => 'lampiran BNBA',
                'retensi'                   => 'pembersihan data lama',
                'sf_bank_data_dokumen'      => 'dokumen bank data',
                'sf_antrean_pengajuan'      => 'antrean pendataan',
                'sf_program'                => 'program',
                'simperum'                  => 'cek RTLH',
                'skema_tidak_valid'         => 'data tidak sesuai format',
                'srp2'                      => 'pengajuan SRP2',
                'srp2_asosiasi'             => 'asosiasi pengembang',
                'srp2_direktori_pengembang' => 'direktori SRP2',
                'srp2_pengajuan'            => 'SRP2',
                'usr_akun'                  => 'akun',
                'warga_assessment'          => 'penilaian rumah warga',
            ],
        ];
    }
}

if ( ! function_exists('audit_label_aksi')) {
    /** "aduan_ditriase" -> "Aduan ditriase"; singkatan jadi huruf besar. */
    function audit_label_aksi($aksi) {
        $aksi = (string) $aksi;
        $kamus = audit_kamus()['aksi'];
        if (isset($kamus[$aksi])) { return $kamus[$aksi]; }
        if (strncmp($aksi, 'akses_', 6) === 0 && isset(audit_kamus()['jenis'][substr($aksi, 6)])) {
            return 'Membuka ' . audit_kamus()['jenis'][substr($aksi, 6)];
        }
        return ucfirst(preg_replace_callback('/\b(srp2|nik|npwp|psu|rtlh|kkn|sk)\b/',
            fn($m) => strtoupper($m[1]), str_replace('_', ' ', $aksi)));
    }
}

if ( ! function_exists('audit_label_objek')) {
    /** ("srp2_pengajuan", "daftar") -> "daftar SRP2"; ("aduan", "431") -> "aduan nomor 431". */
    function audit_label_objek($tipe, $id) {
        if ((string) $tipe === '') { return ''; }
        $tipe = kunci_tersimpan_objek($tipe); // baris sebelum migrasi 072 menyimpan nama tabel lama
        $label = audit_kamus()['objek'][$tipe] ?? str_replace('_', ' ', (string) $tipe);
        if ((string) $id === 'daftar') { return 'daftar ' . $label; }
        return $label . ((string) $id !== '' ? ' nomor ' . $id : '');
    }
}

if ( ! function_exists('audit_ringkasan')) {
    /** Ringkasan baris jejak dalam bahasa layar. $j: baris sys_jejak_audit (objek). */
    function audit_ringkasan($j) {
        $aksi = (string) $j->aksi;
        if (strncmp($aksi, 'akses_', 6) === 0) {
            $jenis = audit_kamus()['jenis'][substr($aksi, 6)] ?? str_replace('_', ' ', substr($aksi, 6));
            $objek = audit_label_objek($j->objek_tipe, $j->objek_id);
            return 'Staf membuka ' . $jenis . ($objek !== '' ? ' (' . $objek . ')' : '');
        }
        // Baris lama Cek Data Rumah menyimpan kode status mentah (found/not_found/error).
        return str_ireplace(['privilege modul', 'privilege', 'hasil: not_found', 'hasil: found', 'hasil: error'],
            ['hak modul', 'hak modul', 'hasil: tidak terdaftar', 'hasil: terdaftar', 'hasil: gagal diperiksa'], (string) $j->ringkasan);
    }
}

if ( ! function_exists('audit_kode_dari_cari')) {
    /**
     * Kata kunci layar ("NPWP pengembang", "daftar SRP2", "Peran diubah") tidak ada di kolom
     * tersimpan, jadi pencarian juga mencocokkan kode yang label tampilnya memuat kata itu.
     * @return array ['aksi' => [...], 'objek_tipe' => [...]]
     */
    function audit_kode_dari_cari($q, array $aksi_tersedia) {
        $q = mb_strtolower(trim((string) $q));
        $hasil = ['aksi' => [], 'objek_tipe' => []];
        if ($q === '') { return $hasil; }
        foreach ($aksi_tersedia as $a) {
            $teks = audit_label_aksi($a);
            if (strncmp($a, 'akses_', 6) === 0) { $teks .= ' staf membuka ' . (audit_kamus()['jenis'][substr($a, 6)] ?? ''); }
            if (str_contains(mb_strtolower($teks), $q)) { $hasil['aksi'][] = $a; }
        }
        foreach (audit_kamus()['objek'] as $tipe => $label) {
            if (str_contains(mb_strtolower($label), $q)) {
                // Baris lama menyimpan nama tabel sebelum migrasi 072; keduanya dicari.
                $hasil['objek_tipe'] = array_merge($hasil['objek_tipe'], [$tipe], array_keys(kunci_tersimpan_tabel(), $tipe, TRUE));
            }
        }
        return $hasil;
    }
}
