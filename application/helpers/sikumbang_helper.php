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
     *
     * @return string|NULL Isi balasan, atau NULL kalau gagal DAN tidak ada
     *                     cadangan apa pun. NULL sengaja dibedakan dari
     *                     string kosong: pemanggil harus bisa memisahkan
     *                     "gagal jaringan" dari "sumbernya memang habis" -
     *                     bedanya menentukan tombol "Muat lagi" mati atau
     *                     hidup (lihat Index::load_more).
     */
    function sikumbang_ambil($url, $cache_file, $ttl, $timeout = SIKUMBANG_TIMEOUT)
    {
        $ada_cache = is_file($cache_file);

        // 1. Cache segar: pulang tanpa menyentuh jaringan sama sekali.
        if ($ada_cache && (time() - filemtime($cache_file)) < $ttl) {
            return file_get_contents($cache_file);
        }

        $bendera = dirname($cache_file) . '/sikumbang_gagal.flag';

        // 2. Baru saja gagal: jangan menambah antrean ke hulu yang sedang
        //    bermasalah. Sajikan cadangan kalau ada.
        if (is_file($bendera) && (time() - filemtime($bendera)) < SIKUMBANG_JEDA_GAGAL) {
            return $ada_cache ? file_get_contents($cache_file) : NULL;
        }

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
        $balasan = curl_exec($ch);
        $galat   = curl_error($ch);
        $kode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        /* Kode HTTP ikut diperiksa, bukan cuma $galat. Balasan 500 atau 502
           dari hulu bukan galat curl - tanpa cek ini, badan halaman error
           akan tertulis ke cache sebagai kalau-kalau itu data yang sah, lalu
           disajikan sebagai "data" selama TTL penuh. */
        if ($galat || $balasan === FALSE || $balasan === '' || $kode < 200 || $kode >= 300) {
            @touch($bendera);
            return $ada_cache ? file_get_contents($cache_file) : NULL;
        }

        @file_put_contents($cache_file, $balasan);
        @unlink($bendera);
        return $balasan;
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
