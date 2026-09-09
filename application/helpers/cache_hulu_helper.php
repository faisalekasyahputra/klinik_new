<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cache berkas + cadangan basi + penahan tembakan untuk SEMUA hulu eksternal.
 *
 * KENAPA DIANGKAT JADI SATU FUNGSI. Pola ini lahir di `sikumbang_helper.php`
 * 26 Agt 2026 sesudah menelusuri keluhan "hostinger selalu mati" dan "API
 * Sikumbang tidak load", yang ternyata satu akar: pola cache lama disalin di
 * TUJUH tempat dan tidak satu pun punya cadangan basi. Menyalinnya sekali
 * lagi untuk Sikaper berarti membuat salinan KEDELAPAN dari cacat yang sama.
 *
 * Yang dijaga fungsi ini, dan ketiganya lahir dari kegagalan nyata:
 *
 * 1. CADANGAN BASI. Cache lewat TTL + hulu gagal TIDAK berarti halaman
 *    kosong. Data kemarin jauh lebih berguna daripada layar kosong, dan
 *    berkasnya memang sudah ada di disk.
 * 2. PENAHAN TEMBAKAN. Satu kegagalan menahan percobaan berikutnya selama
 *    jendela pendek, supaya hulu yang sedang bermasalah tidak diserbu ulang
 *    oleh tiap pengunjung sambil menahan worker PHP.
 * 3. KODE HTTP IKUT DIPERIKSA, bukan cuma galat transport - kalau tidak,
 *    badan halaman error 502 tertulis ke cache lalu disajikan sebagai "data"
 *    selama TTL penuh.
 *
 * Cara mengambilnya diserahkan ke pemanggil lewat callback, karena tiap hulu
 * beda: Sikumbang tanpa auth ber-User-Agent peramban, Sikaper pakai Basic
 * Auth. Yang dibagi mekanisme cache-nya, BUKAN cara menembaknya - itu batas
 * yang disengaja.
 */

if ( ! defined('CACHE_HULU_JEDA_GAGAL')) {
    /**
     * ponytail: satu bendera per DIREKTORI cache + prefiks, bukan per URL.
     * Ceilingnya: satu URL yang gagal membungkam percobaan URL lain dari hulu
     * yang sama selama jendela ini. Diterima sadar - kalau host-nya
     * bermasalah ia bermasalah untuk semua URL-nya, dan selama dibungkam kita
     * TETAP menyajikan cadangan basi, bukan kosong. Kalau kelak terbukti ada
     * endpoint yang gagal sendirian sementara yang lain sehat, pecah
     * benderanya per-endpoint, jangan hapus mekanismenya.
     */
    define('CACHE_HULU_JEDA_GAGAL', 60);
}

if ( ! function_exists('cache_hulu_ambil')) {
    /**
     * @param string   $cache_file Path berkas cache.
     * @param int      $ttl        Umur maksimum cache yang dianggap segar.
     * @param callable $ambil      Pengambil data. WAJIB mengembalikan array
     *                             [bool $berhasil, string|NULL $isi]. Sengaja
     *                             tidak menerima "string atau FALSE": string
     *                             kosong dari hulu yang sehat dan kegagalan
     *                             transport itu dua hal berbeda, dan
     *                             menyamakannya persis cara cache teracuni.
     * @param string   $prefiks    Pembeda bendera antar hulu dalam satu folder.
     *
     * @return string|NULL Isi balasan, atau NULL kalau gagal DAN tidak ada
     *                     cadangan apa pun. NULL sengaja dibedakan dari string
     *                     kosong supaya pemanggil bisa memisahkan "gagal" dari
     *                     "sumbernya memang kosong".
     */
    function cache_hulu_ambil($cache_file, $ttl, callable $ambil, $prefiks = 'hulu')
    {
        $ada_cache = is_file($cache_file);

        // 1. Cache segar: pulang tanpa menyentuh jaringan sama sekali.
        if ($ada_cache && (time() - filemtime($cache_file)) < $ttl) {
            return file_get_contents($cache_file);
        }

        $bendera = dirname($cache_file) . '/' . $prefiks . '_gagal.flag';

        // 2. Baru saja gagal: jangan menambah antrean ke hulu yang bermasalah.
        if (is_file($bendera) && (time() - filemtime($bendera)) < CACHE_HULU_JEDA_GAGAL) {
            return $ada_cache ? file_get_contents($cache_file) : NULL;
        }

        list($berhasil, $isi) = $ambil();

        if ( ! $berhasil || ! is_string($isi) || $isi === '') {
            @touch($bendera);
            return $ada_cache ? file_get_contents($cache_file) : NULL;
        }

        @file_put_contents($cache_file, $isi);
        @unlink($bendera);
        return $isi;
    }
}
