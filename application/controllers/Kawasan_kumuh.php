<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Data Kawasan Kumuh Jawa Tengah, bersumber API Sikaper (Disperakim).
 *
 * PUBLIK, TANPA LOGIN - keputusan user 9 Sep 2026, dan alasannya diperiksa
 * dulu bukan diasumsikan: situs dinas sendiri
 * (sikaper.disperakim.jatengprov.go.id) menyajikan data yang sama tanpa
 * login, jadi menutupnya di sini tidak menambah perlindungan apa pun -
 * cuma menambah gesekan. Yang membuat itu sah: respons `data_kawasan/*`
 * berisi statistik wilayah (nama kawasan, kabupaten, skor kumuh), NOL data
 * pribadi.
 *
 * ⚠️ BATAS ITU MENGIKAT. Endpoint `hari_habitat/detail_peserta` di library
 * yang sama memuat NAMA PIC dan NOMOR HP. Jangan tambahkan endpoint itu ke
 * controller ini hanya karena library-nya sudah ada di tangan - halaman
 * publik yang sama akan berubah jadi daftar kontak orang.
 *
 * 🔻 BUKAN MENGHIDUPKAN LAGI `Sikaper.php` YANG DULU. Controller lama dicabut
 * 29 Jul 2026 (B6, "tutup pintu anonim") karena ia halaman publik yang
 * menumpahkan KELIMA endpoint mentah-mentah dalam satu layar, DAN ia
 * `extends CI_Controller` sehingga jadi satu-satunya controller publik yang
 * lolos security header. Yang ini `extends MY_Controller` (dapat header +
 * konvensi yang sama dengan halaman lain) dan hanya memakai tiga endpoint
 * `data_kawasan/*` yang memang tanpa data pribadi.
 */
class Kawasan_kumuh extends MY_Controller {

    /** Tahun yang BENAR-BENAR berisi, dihitung dari API 10 Sep 2026, bukan
     *  ditebak: 2020=725, 2021=340, 2022=297, 2023=437, 2024=756, 2025=655,
     *  2026=8. 2026 sengaja tetap ditawarkan walau baru 8 - itu tahun berjalan
     *  dan angkanya akan bertambah; menyembunyikannya membuat data terbaru
     *  tidak bisa dilihat sama sekali. */
    const TAHUN_TERSEDIA = [2026, 2025, 2024, 2023, 2022, 2021, 2020];
    const TAHUN_BAWAAN   = 2025;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('sikaper_api');
    }

    public function index()
    {
        $tahun = (int) $this->input->get('tahun');
        if ( ! in_array($tahun, self::TAHUN_TERSEDIA, TRUE)) {
            $tahun = self::TAHUN_BAWAAN;
        }
        /* Kabupaten disaring di SINI, bukan dikirim ke API - endpointnya cuma
           menerima `tahun`, dan satu respons memuat seluruh provinsi. Menyaring
           di sisi kita berarti nol permintaan tambahan per pilihan kabupaten. */
        $kab = preg_replace('/\D+/', '', (string) $this->input->get('kab'));

        $hasil = $this->sikaper_api->get_data_kawasan((string) $tahun);
        $baris = [];
        $gagal = empty($hasil['status']);

        if ( ! $gagal && is_array($hasil['data']['data'] ?? NULL)) {
            $baris = $hasil['data']['data'];
        }

        /* Daftar kabupaten diturunkan DARI DATA, bukan dari tabel `kabupaten`
           lokal. Dua sumber bisa menyimpang (Sikaper memakai kode dan ejaan
           sendiri, mis. "KOTA MAGELANG"), dan menyilangkannya di sini cuma
           menghasilkan baris yang tidak cocok dengan apa pun. */
        $daftar_kab = [];
        foreach ($baris as $b) {
            $kode = (string) ($b['kode_kab'] ?? '');
            if ($kode === '') { continue; }
            if ( ! isset($daftar_kab[$kode])) {
                $daftar_kab[$kode] = ['nama' => (string) ($b['nama_kab'] ?? $kode), 'jumlah' => 0];
            }
            $daftar_kab[$kode]['jumlah']++;
        }
        ksort($daftar_kab);

        if ($kab !== '') {
            $baris = array_values(array_filter($baris, function ($b) use ($kab) {
                return (string) ($b['kode_kab'] ?? '') === $kab;
            }));
        }

        /* Diurutkan supaya yang skornya paling berat tampil dulu. Skor kumuh
           TINGGI = kondisi lebih buruk (skala Sikaper), jadi urut menurun. */
        usort($baris, function ($x, $y) {
            return (int) ($y['skor_kumuh_akhir'] ?? 0) <=> (int) ($x['skor_kumuh_akhir'] ?? 0);
        });

        $data = [
            'judul'          => 'Data Kawasan Kumuh Jawa Tengah',
            'tahun'          => $tahun,
            'tahun_tersedia' => self::TAHUN_TERSEDIA,
            'kab_terpilih'   => $kab,
            'daftar_kab'     => $daftar_kab,
            'baris'          => $baris,
            'gagal'          => $gagal,
        ];

        $this->render('pages/data_spasial/kawasan_kumuh', $data);
    }

    /**
     * Detail satu kawasan: skor + daftar RT/RW.
     *
     * `$id` datang dari respons API (base64 dari angka), bukan dari URL situs
     * publik dinas yang memakai skema hash berbeda - dua skema ID itu TIDAK
     * bisa saling ditukar, lihat SIKAPER_API.md.
     */
    public function detail($id = NULL)
    {
        $id = trim((string) $id);
        /* Batasi bentuknya sebelum diteruskan ke hulu. Bukan paranoia: nilai
           ini masuk ke body permintaan ke server dinas, dan membiarkannya
           bebas berarti halaman kita jadi perantara permintaan sembarang. */
        if ($id === '' || ! preg_match('/^[A-Za-z0-9_-]{4,64}$/', $id)) {
            show_404();
            return;
        }

        $detail = $this->sikaper_api->get_detail_kawasan($id);
        $rtrw   = $this->sikaper_api->get_data_rtrw($id);

        if (empty($detail['status'])) {
            show_404();
            return;
        }

        $this->render('pages/data_spasial/kawasan_kumuh_detail', [
            'judul'   => 'Detail Kawasan Kumuh',
            'kawasan' => $detail['data']['data'] ?? [],
            /* TIGA tingkat, dan tiap tingkat punya alasan berbeda:
               library membungkus balasan di `data`, respons API sendiri
               membungkus isinya di `data`, lalu `data_rtrw` membungkus lagi
               di `rtrw` (bersama `kawasan`) - sementara `detail_kawasan`
               berhenti di tingkat kedua. Sempat saya tulis dua tingkat dan
               halaman menampilkan "0 RT/RW" untuk kawasan yang API-nya jelas
               mengembalikan 2 - salah diam, bukan galat. */
            'rtrw'    => is_array($rtrw['data']['data']['rtrw'] ?? NULL) ? $rtrw['data']['data']['rtrw'] : [],
        ]);
    }
}
