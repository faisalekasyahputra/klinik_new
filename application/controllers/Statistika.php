<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statistika extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
        $this->load->library('ternak_api');
	}

	public function index()
	{
		$data['title'] = 'Statistika Bank Data Perumahan';
        
        $kabupaten = $this->input->get('kabupaten');
        $multiplier = 1.0;
        
        if (!empty($kabupaten) && $kabupaten !== 'all') { // angka simulasi saja; angka nyata difilter sungguhan di bawah
            $hash = crc32($kabupaten);
            $multiplier = (($hash % 80) + 20) / 1000; // Multiplier between 0.02 and 0.1 (2% to 10% of province data)
        }
        
        // Dummy data for statistics grouped by domain
        $data['stats'] = [
            'perumahan' => [
                'tloo' => ['value' => round(1520 * $multiplier), 'sumber' => 'Simperum'],
                'rtlh_apbd' => ['value' => round(12500 * $multiplier), 'sumber' => 'Simperum'],
                'bsps' => ['value' => round(18000 * $multiplier), 'sumber' => 'Simperum'],
                'omah_lestari' => ['value' => round(2500 * $multiplier), 'sumber' => 'Simperum'],
                'unit_subsidi' => ['value' => round(45000 * $multiplier), 'sumber' => 'Sikumbang'],
                'unit_komersil' => ['value' => round(12000 * $multiplier), 'sumber' => 'Sikumbang'],
            ],
            'kawasan' => [
                'luas_kumuh' => ['value' => round(1205.5 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'tertangani' => ['value' => round(850.2 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'sisa_kumuh' => ['value' => round(355.3 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'persentase' => ['value' => round((850.2 / 1205.5) * 100, 1), 'sumber' => 'Sikunang'], // Persentase tetap sama
            ],
            'pertanahan' => [
                'aset_lahan' => ['value' => round(150.5 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
                'lahan_siap_bangun' => ['value' => round(85.2 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
                'lahan_termanfaatkan' => ['value' => round(45.0 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
            ],
            'pengembang' => [
                'total_terdaftar' => ['value' => round(450 * $multiplier), 'sumber' => 'Sikaper'],
                'aktif' => ['value' => round(310 * $multiplier), 'sumber' => 'Sikaper'],
                'proyek_berjalan' => ['value' => round(125 * $multiplier), 'sumber' => 'Sikaper'],
                'asosiasi' => ['value' => round(8 * $multiplier), 'sumber' => 'Sikaper'],
            ],
            'penerima_manfaat' => [
                'bantuan_rtlh' => ['value' => round(15000 * $multiplier), 'sumber' => 'Simperum'],
                'pembeli_subsidi' => ['value' => round(38000 * $multiplier), 'sumber' => 'Sikumbang'],
                'pembeli_komersil' => ['value' => round(8500 * $multiplier), 'sumber' => 'Sikumbang'],
                'total_penerima' => ['value' => round(61500 * $multiplier), 'sumber' => 'Kompilasi'],
            ]
        ];
        
        // ---- Angka NYATA (4 Okt 2026). Sisanya tetap simulasi dan berlabel begitu di view.
        // Daftar wilayah dari tabel `kabupaten`: id-nya kode BPS (3301 dst.), sama dengan
        // kodeWilayah SIKUMBANG, jadi filter kabupaten untuk angka nyata ikut sungguhan.
        $wilayah = array_column($this->db->select('id, nama')->order_by('nama')->get('kabupaten')->result_array(), 'id', 'nama');
        $kab_id  = ($kabupaten && isset($wilayah[$kabupaten])) ? (int) $wilayah[$kabupaten] : NULL;

        // Unit rumah: ringkasan resmi SIKUMBANG (`count` di balasan pencarian; limit=1 cukup).
        $unit = $this->sikumbang_ringkasan($kab_id ? (string) $kab_id : '33');
        $data['stats']['perumahan']['unit_subsidi']  = ['value' => $unit['countUnitSubsidi'] ?? NULL, 'sumber' => 'SiKumbang', 'nyata' => TRUE];
        $data['stats']['perumahan']['unit_komersil'] = ['value' => $unit['countUnitKomersil'] ?? NULL, 'sumber' => 'SiKumbang', 'nyata' => TRUE];
        $data['sikumbang_tersedia'] = $unit !== NULL;

        // Pengembang: Direktori SRP2 kita sendiri (yang tayang di direktori publik). "Bersertifikat"
        // dihitung dari status, BUKAN tanggal berakhir: 67 entri historis tanpa tanggal berakhir
        // (4 Okt 2026), jadi hitungan per tanggal selalu 0 dan menyesatkan.
        $q = $this->db->from('srp2_direktori_pengembang')->where('status_aktif', 1);
        if ($kab_id) { $q->where('kabupaten_id', $kab_id); }
        $dir = $q->select("COUNT(*) total, SUM(status_sertifikasi IN ('bersertifikat','Diterima')) berlaku,"
            . " COUNT(DISTINCT NULLIF(TRIM(asosiasi), '')) asosiasi", FALSE)->get()->row_array();
        $data['stats']['pengembang']['total_terdaftar'] = ['value' => (int) $dir['total'], 'sumber' => 'Direktori SRP2', 'nyata' => TRUE];
        $data['stats']['pengembang']['aktif']           = ['value' => (int) $dir['berlaku'], 'sumber' => 'Direktori SRP2', 'nyata' => TRUE];
        $data['stats']['pengembang']['asosiasi']        = ['value' => (int) $dir['asosiasi'], 'sumber' => 'Direktori SRP2', 'nyata' => TRUE];

        $data['kabupaten_terpilih'] = $kab_id ? $kabupaten : 'all';
        $data['daftar_kabupaten'] = array_keys($wilayah);
        
        // Fetch Real Data from KRSjawa 3 API for Publikasi
        $data['publikasi'] = [
            'artikel' => count($this->ternak_api->get_public_articles() ?: []),
            'video' => count($this->ternak_api->get_public_videos() ?: []),
            'regulasi' => count($this->ternak_api->get_public_regulations() ?: []),
            'desain_rumah' => count($this->ternak_api->get_public_house_designs() ?: [])
        ];

		$this->render('pages/data_spasial/statistika', $data);
	}

    /**
     * Ringkasan unit SIKUMBANG untuk satu wilayah (33 = Jawa Tengah, atau kode kabupaten BPS).
     * Lewat sikumbang_ambil(): cache 6 jam, bendera gagal, dan cadangan basi bila hulu mati.
     * @return array|NULL isi `count` (countUnitSubsidi, countUnitKomersil, totalLokasi, ...)
     */
    private function sikumbang_ringkasan($kode)
    {
        $kode = preg_match('/^33(\d{2})?$/', $kode) ? $kode : '33';
        $this->load->helper('sikumbang');
        $body = sikumbang_ambil('https://sikumbang.tapera.go.id/ajax/lokasi/search?selectedSearch=wilayah&skalaPerumahan=semua&kodeWilayah='
            . $kode . '&sort=terbaru&searchBy=nama-perumahan&page=1&limit=1', APPPATH . 'cache/sikumbang_statistik_' . $kode . '.json', 21600);
        $json = $body !== NULL ? json_decode($body, TRUE) : NULL;
        return is_array($json['count'] ?? NULL) ? $json['count'] : NULL;
    }
}
