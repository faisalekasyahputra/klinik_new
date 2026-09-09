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

    /** Kolom yang boleh dijadikan kunci urut, dipetakan ke medan respons API.
     *  Whitelist, BUKAN menerima nama medan bebas dari URL: kunci urut ikut
     *  dipakai untuk mengakses array baris, dan nama yang tidak dikenal cuma
     *  menghasilkan urutan acak yang sulit dilacak. `kondisi` diturunkan dari
     *  skor akhir, jadi ia berbagi medan yang sama. */
    const KOLOM_URUT = [
        'kawasan'    => 'nama_kawasan',
        'kabupaten'  => 'nama_kab',
        'skor_awal'  => 'skor_kumuh_awal',
        'skor_akhir' => 'skor_kumuh_akhir',
        'kondisi'    => 'skor_kumuh_akhir',
    ];
    const KOLOM_ANGKA = ['skor_kumuh_awal', 'skor_kumuh_akhir'];
    const PER_HALAMAN = [25, 50, 100];

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

        /* URUT. Bawaan skor akhir menurun: skor kumuh TINGGI = kondisi lebih
           buruk (skala Sikaper), jadi yang paling berat tampil dulu. Kolom
           teks dibandingkan tanpa peduli huruf besar-kecil karena ejaan
           Sikaper campur ("KOTA MAGELANG" vs "Cilacap"); kolom angka
           dibandingkan sebagai angka supaya 9 tidak jatuh sesudah 10.
           Pengikat seri selalu nama kawasan, supaya urutan stabil antar
           permintaan - tanpa itu, dua kawasan berskor sama bisa bertukar
           tempat tiap kali halaman dimuat ulang. */
        $urut = (string) $this->input->get('urut');
        if ( ! isset(self::KOLOM_URUT[$urut])) { $urut = 'skor_akhir'; }
        $arah = $this->input->get('arah') === 'asc' ? 'asc' : 'desc';
        if ($this->input->get('urut') === NULL) { $arah = 'desc'; }
        $medan = self::KOLOM_URUT[$urut];
        $angka = in_array($medan, self::KOLOM_ANGKA, TRUE);

        usort($baris, function ($x, $y) use ($medan, $angka, $arah) {
            $a = $x[$medan] ?? ($angka ? 0 : '');
            $b = $y[$medan] ?? ($angka ? 0 : '');
            $c = $angka ? ((int) $a <=> (int) $b) : strcasecmp((string) $a, (string) $b);
            if ($c === 0) {
                $c = strcasecmp((string) ($x['nama_kawasan'] ?? ''), (string) ($y['nama_kawasan'] ?? ''));
            }
            return $arah === 'asc' ? $c : -$c;
        });

        /* HALAMAN. Dipotong di sini, bukan di API: satu tahun sudah ada di
           memori dari cache, jadi memotongnya gratis, dan URL-nya membawa
           seluruh keadaan (tahun, kabupaten, urut, halaman) sehingga bisa
           dibagikan atau di-bookmark apa adanya. */
        $per = (int) $this->input->get('per');
        if ( ! in_array($per, self::PER_HALAMAN, TRUE)) { $per = self::PER_HALAMAN[0]; }
        $total   = count($baris);
        $jumlah_hal = max(1, (int) ceil($total / $per));
        $hal = (int) $this->input->get('hal');
        if ($hal < 1) { $hal = 1; }
        if ($hal > $jumlah_hal) { $hal = $jumlah_hal; }
        $baris_hal = array_slice($baris, ($hal - 1) * $per, $per);

        $data = [
            'judul'          => 'Data Kawasan Kumuh Jawa Tengah',
            'tahun'          => $tahun,
            'tahun_tersedia' => self::TAHUN_TERSEDIA,
            'kab_terpilih'   => $kab,
            'daftar_kab'     => $daftar_kab,
            'baris'          => $baris_hal,
            'gagal'          => $gagal,
            'urut'           => $urut,
            'arah'           => $arah,
            'kolom_urut'     => array_keys(self::KOLOM_URUT),
            'per'            => $per,
            'per_pilihan'    => self::PER_HALAMAN,
            'hal'            => $hal,
            'jumlah_hal'     => $jumlah_hal,
            'total'          => $total,
            'mulai'          => $total ? ($hal - 1) * $per + 1 : 0,
            'sampai'         => min($total, $hal * $per),
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
