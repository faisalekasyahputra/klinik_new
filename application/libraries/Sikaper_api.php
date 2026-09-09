<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sikaper_api {

    protected $CI;
    private $base_url;
    private $username;
    private $password;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('sikaper');

        $this->base_url = rtrim($this->CI->config->item('sikaper_api_base_url'), '/') . '/';
        $this->username = $this->CI->config->item('sikaper_api_username');
        $this->password = $this->CI->config->item('sikaper_api_password');
    }

    /**
     * Helper untuk HTTP Request
     */
    /**
     * @param int $ttl Umur cache dalam detik. 0 = jangan di-cache.
     *
     * SEMUA endpoint Sikaper di-cache, dan itu bukan optimasi prematur:
     * `data_kawasan/kawasan` untuk satu tahun saja membalas ~292 KB berisi 756
     * kawasan. Memanggilnya tiap kunjungan halaman berarti mengulang persis
     * kesalahan yang membuat "hostinger selalu mati" - hulu lambat menahan
     * worker PHP, dan di hosting bersama itu menular ke seluruh situs.
     * Datanya sendiri rekap tahunan yang berubah sangat jarang.
     */
    private function _request($endpoint, $method = 'GET', $data = [], $ttl = 21600)
    {
        $url = $this->base_url . ltrim($endpoint, '/');

        if ($ttl > 0 && function_exists('cache_hulu_ambil')) {
            $berkas = APPPATH . 'cache/sikaper_' . md5($url . '|' . http_build_query($data)) . '.json';
            $isi = cache_hulu_ambil($berkas, $ttl, function () use ($url, $method, $data) {
                $mentah = $this->_tembak($url, $method, $data);
                return [$mentah['ok'], $mentah['body']];
            }, 'sikaper');

            if ($isi === NULL) {
                return ['status' => FALSE, 'message' => 'Sikaper tidak dapat dihubungi dan tidak ada cadangan.', 'data' => NULL];
            }
            return ['status' => TRUE, 'http_code' => 200, 'data' => json_decode($isi, TRUE)];
        }

        $mentah = $this->_tembak($url, $method, $data);
        if ( ! $mentah['ok']) {
            return ['status' => FALSE, 'http_code' => $mentah['kode'], 'message' => $mentah['pesan'], 'data' => json_decode((string) $mentah['body'], TRUE)];
        }
        return ['status' => TRUE, 'http_code' => $mentah['kode'], 'data' => json_decode((string) $mentah['body'], TRUE)];
    }

    /** Tembakan mentah tanpa cache. Dipisah supaya cache_hulu_ambil() punya
     *  callback yang bersih dan jalur tanpa-cache tetap memakai kode yang sama. */
    private function _tembak($url, $method, $data)
    {

        $ch = curl_init();

        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        /* B4 LUNAS 9 Sep 2026. Verifikasi TLS dinyalakan penuh, dan itu tidak
           mengorbankan apa pun: sertifikat host API-nya sah (Google Trust
           Services, CN=phicos.co.id) dan diuji 200 dengan verifikasi AKTIF
           dari lokal MAUPUN dari PHP di server production. Utang ini dulu
           dibiarkan karena library-nya yatim; sekarang ia dipakai. JANGAN
           kembalikan ke false "sementara" - kalau kelak ada galat sertifikat,
           betulkan CA bundle-nya, jangan matikan pemeriksaannya. */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if ( ! empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $body  = curl_exec($ch);
        $kode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $galat = curl_error($ch);
        curl_close($ch);

        /* Kode HTTP ikut menentukan, bukan cuma galat transport - kalau tidak,
           badan halaman error 502 akan tertulis ke cache sebagai data sah. */
        $ok = ! $galat && is_string($body) && $body !== '' && $kode >= 200 && $kode < 300;

        return [
            'ok'    => $ok,
            'kode'  => $kode,
            'body'  => is_string($body) ? $body : NULL,
            'pesan' => $galat ? ('cURL Error: ' . $galat) : ('API Error (HTTP ' . $kode . ')'),
        ];
    }

    // ------------------------------------------------------------------------
    // API ENDPOINTS
    // ------------------------------------------------------------------------

    /**
     * Info Hari Habitat (GET api/v2/hari_habitat/info)
     */
    public function get_info_hari_habitat()
    {
        return $this->_request('hari_habitat/info', 'GET');
    }

    /**
     * Jadwal Hari Habitat (POST api/v2/hari_habitat/jadwal)
     * @param string $id_lomba
     */
    public function get_jadwal_hari_habitat($id_lomba)
    {
        return $this->_request('hari_habitat/jadwal', 'POST', ['id_lomba' => $id_lomba]);
    }

    /**
     * Data Kawasan Per Tahun (POST api/v2/data_kawasan/kawasan)
     * @param string $tahun
     */
    public function get_data_kawasan($tahun = '2024')
    {
        return $this->_request('data_kawasan/kawasan', 'POST', ['tahun' => $tahun]);
    }

    /**
     * Detail Per ID Kawasan (POST api/v2/data_kawasan/detail_kawasan)
     * @param string $id_kawasan
     */
    public function get_detail_kawasan($id_kawasan)
    {
        return $this->_request('data_kawasan/detail_kawasan', 'POST', ['id_kawasan' => $id_kawasan]);
    }

    /**
     * Data RTRW per ID Kawasan (POST api/v2/data_kawasan/data_rtrw)
     * @param string $id_kawasan
     */
    public function get_data_rtrw($id_kawasan)
    {
        return $this->_request('data_kawasan/data_rtrw', 'POST', ['id_kawasan' => $id_kawasan]);
    }

    /**
     * Daftar peserta Lomba Hari Habitat (POST hari_habitat/detail_peserta).
     *
     * TIDAK ADA di tabel SIKAPER_API.md maupun di koleksi Postman yang
     * dipetakan 1 Sep - ketahuan dari tangkapan layar dinas 9 Sep 2026.
     * Diverifikasi hidup: 200, ~13 KB, berisi kode_kab, nama_kab,
     * judul_proposal, pic, no_hp per peserta.
     *
     * ⚠️ Responsnya memuat NOMOR HP dan NAMA PIC. Kalau kelak ditampilkan ke
     * halaman publik, saring dulu - itu data kontak orang, bukan statistik.
     */
    public function get_detail_peserta_hari_habitat($id_lomba)
    {
        return $this->_request('hari_habitat/detail_peserta', 'POST', ['id_lomba' => $id_lomba]);
    }
}
