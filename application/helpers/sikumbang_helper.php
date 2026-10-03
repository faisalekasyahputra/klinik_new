<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Satu pintu untuk semua pengambilan data SIKUMBANG (tapera.go.id).
 *
 * LAHIR DARI SATU PENYELIDIKAN, 26 Agt 2026. Dinas melaporkan dua keluhan
 * yang kelihatannya terpisah: "hostinger selalu mati" dan "API Sikumbang
 * tidak bisa load". Keduanya berakar di sini, dan BUKAN di SIKUMBANG -
 * hulunya diukur sehat hari itu, 1,48 dtk dari lokal dan 1,75 dtk dari
 * server production, termasuk lewat jalur PHP curl yang persis sama.
 *
 * Pola lama disalin di TUJUH tempat (Sikumbang::index, Index::ajax_perumahan,
 * Index::bongkah_sikumbang, Index::detail_perum, Index::sebaran,
 * Umum::sebaran, Umum::ajax_perumahan):
 *
 *     if (cache masih segar) pakai cache;
 *     else { curl; if (berhasil) tulis cache; }
 *     if ($response) tampilkan; else KOSONG;
 *
 * DUA CACAT DI POLA ITU:
 *
 * 1. Tidak ada cadangan basi. Cache lewat TTL + curl gagal = berkas cache
 *    yang masih bagus di disk DIABAIKAN, halaman render kosong. Data
 *    kemarin jauh lebih berguna daripada halaman kosong, dan berkasnya ada
 *    di sana, cuma tidak dibaca.
 *
 * 2. Tidak ada catatan kegagalan. Setiap permintaan berikutnya mengulang
 *    tembakan dan membayar timeout penuh lagi. Terukur: satu permintaan
 *    /index/load_more dengan cache dingin butuh 19,9 detik, karena
 *    lokasi_tersaring() menembak sampai LIMA bongkahan berurutan yang
 *    masing-masing bertimeout 60 detik. Kasus terburuk 300 detik dalam satu
 *    permintaan HTTP, dan selama itu satu worker PHP terkunci. Satu tampilan
 *    /cari_rumah memegang 11 worker sekaligus (1 load_more + 10 foto). Di
 *    hosting bersama yang jatah entry process-nya kecil dan dipakai bareng
 *    10+ domain lain di akun yang sama, dua pengunjung sudah cukup membuat
 *    semuanya antre. Itulah "situs mati" yang dilaporkan.
 *
 * DAN SATU KOREKSI ARAH. Komentar di kode mencatat timeout pernah dinaikkan
 * 15 ke 45 (proxy foto) dan 30 ke 60 (bongkah) pada 20 Agt 2026, keduanya
 * untuk menambal "gambar/kartu tidak muncul". Kedua kenaikan itu mengobati
 * gejala dari cacat nomor 1: timeout dipanjangkan KARENA tidak ada cadangan,
 * jadi gagal berarti kosong. Akibatnya worker ditahan 2 sampai 4 kali lebih
 * lama, yang memperparah cacat nomor 2. Urutan yang benar kebalikannya:
 * beri cadangan dulu, baru timeout boleh pendek. Itu yang dilakukan berkas
 * ini, dan itu sebabnya SIKUMBANG_TIMEOUT di bawah jauh lebih kecil dari 60.
 */

/* Mekanisme cache-nya dipakai bersama Sikaper - lihat cache_hulu_helper.php.
   Di-`require` bersyarat, bukan diandalkan pada autoload, supaya helper ini
   tetap bisa dipakai dari skrip CLI di luar CodeIgniter (harness di
   docs/engineering/ memang begitu cara memanggilnya). */
if ( ! function_exists('cache_hulu_ambil')) {
    require_once __DIR__ . '/cache_hulu_helper.php';
}

/** Batas satu permintaan ke SIKUMBANG. Lihat catatan koreksi arah di atas. */
define('SIKUMBANG_TIMEOUT', 12);

/** Batas fase sambung saja. Tanpa ini, sambungan yang menggantung memakan
 *  seluruh jatah timeout dan tidak menyisakan waktu untuk membaca balasan. */
define('SIKUMBANG_CONNECT_TIMEOUT', 5);

/**
 * Berapa lama tembakan baru ditahan sesudah satu kegagalan.
 *
 * ponytail: satu bendera untuk SELURUH host, bukan per-URL. Ceilingnya:
 * satu URL yang gagal membungkam percobaan URL lain selama jendela ini.
 * Diterima sadar - kalau host-nya bermasalah ia bermasalah untuk semua URL,
 * dan selama dibungkam kita TETAP menyajikan cadangan basi, bukan kosong.
 * Kalau kelak terbukti ada endpoint yang gagal sendirian sementara yang lain
 * sehat, pecah benderanya per-host-path, jangan hapus mekanismenya.
 */
define('SIKUMBANG_JEDA_GAGAL', 60);

if ( ! function_exists('sikumbang_ambil')) {
    /**
     * Ambil satu URL SIKUMBANG lewat cache berkas, dengan cadangan basi.
     *
     * @param string $url        URL penuh yang diminta.
     * @param string $cache_file Path berkas cache. SENGAJA parameter, bukan
     *                           diturunkan dari md5($url) di dalam sini:
     *                           tujuh pemanggil sudah memakai nama berkas
     *                           yang berbeda-beda, dan menyeragamkannya
     *                           berarti membuang 80 berkas cache yang sudah
     *                           hangat di production tanpa alasan.
     * @param int    $ttl        Umur maksimum cache yang dianggap segar.
     * @param int    $timeout    Batas waktu curl.
     * @param string $bendera    Nama bendera penahan tembakan. Bawaan satu untuk
     *                           seluruh host; detail per lokasi memakai benderanya
     *                           sendiri (lihat Index::detail_perum).
     *
     * @return string|NULL Isi balasan, atau NULL kalau gagal DAN tidak ada
     *                     cadangan apa pun. NULL sengaja dibedakan dari
     *                     string kosong: pemanggil harus bisa memisahkan
     *                     "gagal jaringan" dari "sumbernya memang habis" -
     *                     bedanya menentukan tombol "Muat lagi" mati atau
     *                     hidup (lihat Index::load_more).
     */
    function sikumbang_ambil($url, $cache_file, $ttl, $timeout = SIKUMBANG_TIMEOUT, $bendera = 'sikumbang')
    {
        /* Mekanisme cache-nya DIPINDAH ke cache_hulu_ambil() 10 Sep 2026 dan
           dipakai bersama Sikaper. Yang tinggal di sini cuma cara MENEMBAK-nya,
           karena tiap hulu beda: Sikumbang tanpa auth ber-User-Agent peramban,
           Sikaper pakai Basic Auth. Perilakunya tidak berubah sedikit pun -
           nama berkas benderanya tetap `sikumbang_gagal.flag`, dan
           uji_sikumbang_cadangan.php (11 pemeriksaan) yang membuktikannya. */
        return cache_hulu_ambil($cache_file, $ttl, function () use ($url, $timeout) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CONNECTTIMEOUT => SIKUMBANG_CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ]);
            curl_setopt_array($ch, transport_curl_options()); // TLS 1.2+, HTTPS saja (poin 8.2)
            $balasan = curl_exec($ch);
            $galat   = curl_error($ch);
            $kode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            /* Kode HTTP ikut diperiksa, bukan cuma $galat. Balasan 500 atau
               502 dari hulu bukan galat curl - tanpa cek ini, badan halaman
               error tertulis ke cache sebagai kalau-kalau itu data sah. */
            $ok = ! $galat && is_string($balasan) && $balasan !== '' && $kode >= 200 && $kode < 300;
            /* SIKUMBANG juga membungkus galatnya dengan HTTP 200:
               {"error":true,"code":"ERR_UNEXPECTED",...} (UAT warga#2.0,
               26 Sep 2026). Tanpa cek ini amplop galat tertulis ke cache
               24 jam dan menimpa cache bagus, sehingga cadangan basi tidak
               pernah terpakai. */
            if ($ok) {
                $urai = json_decode($balasan, TRUE);
                $ok = ! (is_array($urai) && ! empty($urai['error']));
                // NIK pembeli per unit (nikPemilik/nikBooking di blok bangunan) tidak dipakai aplikasi:
                // dibuang sebelum menyentuh cache.
                if ($ok && is_array($urai)) {
                    [$urai, $n] = sikumbang_buang_nik($urai);
                    if ($n > 0) { $balasan = json_encode($urai, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
                }
            }
            return [$ok, is_string($balasan) ? $balasan : NULL];
        }, $bendera);
    }
}

if ( ! function_exists('sikumbang_buang_nik')) {
    /**
     * Buang setiap kunci yang diawali "nik" (huruf besar/kecil bebas) di seluruh kedalaman.
     * Juga dipakai Penyapu_retensi untuk membersihkan cache yang ditulis sebelum aturan ini.
     * @return array [data, jumlah kunci yang dibuang]
     */
    function sikumbang_buang_nik(array $data)
    {
        $n = 0;
        foreach ($data as $k => $v) {
            if (is_string($k) && stripos($k, 'nik') === 0) { unset($data[$k]); $n++; continue; }
            if (is_array($v)) { [$data[$k], $m] = sikumbang_buang_nik($v); $n += $m; }
        }
        return [$data, $n];
    }
}

if ( ! function_exists('sikumbang_data')) {
    /**
     * Pembungkus untuk bentuk balasan yang paling sering dipakai:
     * `{"data": [...]}`.
     *
     * @return array [baris, gagal]. `gagal` TRUE hanya kalau tidak ada data
     *               sama sekali yang bisa disajikan - baris dari cadangan
     *               basi TIDAK dihitung gagal, karena bagi pengguna halaman
     *               itu berhasil terisi.
     */
    function sikumbang_data($url, $cache_file, $ttl, $timeout = SIKUMBANG_TIMEOUT)
    {
        $balasan = sikumbang_ambil($url, $cache_file, $ttl, $timeout);
        if ($balasan === NULL) { return [[], TRUE]; }

        $urai = json_decode($balasan, TRUE);
        return [isset($urai['data']) && is_array($urai['data']) ? $urai['data'] : [], FALSE];
    }
}

/** Batas ukuran satu foto dari SIKUMBANG. Foto terbesar yang pernah tercatat di production 3,5 MB. */
define('SIKUMBANG_FOTO_MAKS_BYTE', 6 * 1024 * 1024);

if ( ! function_exists('sikumbang_foto_path')) {
    /**
     * Path foto SIKUMBANG yang boleh diambil proxy Index::buka_foto, atau NULL.
     *
     * Hanya bentuk path foto yang memang ada di data SIKUMBANG (dicek terhadap seluruh cache lokal
     * 3 Okt 2026: 59.815 path, semuanya cocok): diawali `public/`, segmen huruf/angka/garis bawah/
     * tanda hubung, berakhiran ekstensi gambar. Tanpa `?`, `#`, `%`, `..`, `//`, jadi satu foto
     * hanya punya SATU bentuk path dan satu berkas cache; varian tak terbatas tidak bisa dibuat.
     */
    function sikumbang_foto_path($path)
    {
        $path = (string) $path;
        return strlen($path) <= 200
            && preg_match('#^public(?:/[A-Za-z0-9][A-Za-z0-9_-]*){1,8}\.(?:jpe?g|png|webp|gif)$#i', $path)
            ? $path : NULL;
    }
}

if ( ! function_exists('sikumbang_ambil_foto')) {
    /**
     * Unduh satu foto dari SIKUMBANG. Mengembalikan byte gambar, atau NULL bila gagal, bukan gambar
     * (Content-Type dan isi diperiksa), atau lebih besar dari SIKUMBANG_FOTO_MAKS_BYTE (unduhan
     * diputus begitu melewati batas, tidak ditampung dulu).
     */
    function sikumbang_ambil_foto($path)
    {
        if (sikumbang_foto_path($path) === NULL) { return NULL; }
        $ch = curl_init('https://sikumbang.tapera.go.id/' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER   => TRUE,
            CURLOPT_SSL_VERIFYPEER   => TRUE,
            CURLOPT_SSL_VERIFYHOST   => 2,
            CURLOPT_CONNECTTIMEOUT   => SIKUMBANG_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT          => SIKUMBANG_TIMEOUT,
            CURLOPT_USERAGENT        => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_MAXFILESIZE      => SIKUMBANG_FOTO_MAKS_BYTE,
            CURLOPT_NOPROGRESS       => FALSE,
            // Memutus balasan tanpa Content-Length yang terus mengalir melewati batas.
            CURLOPT_XFERINFOFUNCTION => function ($ch, $total, $terunduh) { return $terunduh > SIKUMBANG_FOTO_MAKS_BYTE ? 1 : 0; },
        ]);
        curl_setopt_array($ch, transport_curl_options()); // TLS 1.2+, HTTPS saja (poin 8.2)
        $isi  = curl_exec($ch);
        $kode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $tipe = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
        curl_close($ch);
        if ($kode !== 200 || ! is_string($isi) || $isi === '' || strlen($isi) > SIKUMBANG_FOTO_MAKS_BYTE
            || strpos($tipe, 'image/') !== 0) {
            return NULL;
        }
        $info = @getimagesizefromstring($isi);
        return $info && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], TRUE) ? $isi : NULL;
    }
}

if ( ! function_exists('sikumbang_param')) {
    /**
     * Normalkan satu parameter pencarian SIKUMBANG dari GET sebelum masuk URL hulu, yang md5-nya
     * menjadi nama berkas cache. Nilai di luar daftar izin jatuh ke $bawaan, jadi nilai sembarang
     * tidak melahirkan berkas cache dan tembakan hulu baru. Daftar izin = pilihan di formulir
     * cari_rumah/sikumbang (dan saring_status_rumah). Kata kunci memang teks bebas: dirapikan dan
     * dipotong 60 karakter; jumlahnya ditahan kelas laju 'cari' dan penyapu cache.
     */
    function sikumbang_param($nama, $nilai, $bawaan = NULL)
    {
        $nilai = is_scalar($nilai) ? trim((string) $nilai) : '';
        if ($nama === 'keyword') { return mb_substr((string) preg_replace('/\s+/u', ' ', $nilai), 0, 60); }
        if ($nama === 'kodeWilayah') { return preg_match('/^\d{2}(\d{2})?$/', $nilai) ? $nilai : $bawaan; } // kode provinsi atau kab/kota
        $izin = [
            'sort'         => ['terbaru', 'subsidi-termurah', 'subsidi-tertinggi'],
            'searchBy'     => ['nama-perumahan', 'nama-pengembang', 'asosiasi'],
            'status_rumah' => ['subsidi', 'komersil', 'semua'],
        ];
        return in_array($nilai, $izin[$nama] ?? [], TRUE) ? $nilai : $bawaan;
    }
}
