<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->load->library('ternak_api');
        $this->load->helper(['ternak', 'url', 'form','download']);
	}
	public function index()
	{
		$datacontent['judul']='';
		
        // Load settings for landing page
        $this->load->model('Setting_model');
        $datacontent['settings'] = $this->Setting_model->get_all();

		// ============================================================
		// PROGRESSIVE LOADING STRATEGY
		// Hanya load data yang TERLIHAT di layar pertama (above the fold)
		// Sisanya di-lazy-load via AJAX saat user scroll ke section tsb
		// ============================================================

		// Above the fold: Hanya kabupaten list (untuk dropdown filter)
		$datacontent['kabupaten_kota_jateng'] = [
			"3301" => "Kabupaten Cilacap",
			"3302" => "Kabupaten Banyumas",
			"3303" => "Kabupaten Purbalingga",
			"3304" => "Kabupaten Banjarnegara",
			"3305" => "Kabupaten Kebumen",
			"3306" => "Kabupaten Purworejo",
			"3307" => "Kabupaten Wonosobo",
			"3308" => "Kabupaten Magelang",
			"3309" => "Kabupaten Boyolali",
			"3310" => "Kabupaten Klaten",
			"3311" => "Kabupaten Sukoharjo",
			"3312" => "Kabupaten Wonogiri",
			"3313" => "Kabupaten Karanganyar",
			"3314" => "Kabupaten Sragen",
			"3315" => "Kabupaten Grobogan",
			"3316" => "Kabupaten Blora",
			"3317" => "Kabupaten Rembang",
			"3318" => "Kabupaten Pati",
			"3319" => "Kabupaten Kudus",
			"3320" => "Kabupaten Jepara",
			"3321" => "Kabupaten Demak",
			"3322" => "Kabupaten Semarang",
			"3323" => "Kabupaten Temanggung",
			"3324" => "Kabupaten Kendal",
			"3325" => "Kabupaten Batang",
			"3326" => "Kabupaten Pekalongan",
			"3327" => "Kabupaten Pemalang",
			"3328" => "Kabupaten Tegal",
			"3329" => "Kabupaten Brebes",
			"3371" => "Kota Magelang",
			"3372" => "Kota Surakarta (Solo)",
			"3373" => "Kota Salatiga",
			"3374" => "Kota Semarang",
			"3375" => "Kota Pekalongan",
			"3376" => "Kota Tegal"
		];

		$data['content'] = $this->load->view('pages/home/awal', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	/**
	 * AJAX: Lazy load Artikel (dipanggil saat section scroll into view)
	 */
	public function ajax_articles()
	{
		$this->load->library('ternak_api');
		$articles = $this->ternak_api->get_public_articles();
		$this->load->view('components/partials/articles_cards', ['articles' => $articles]);
	}

	/**
	 * AJAX: Lazy load Bank Desain (dipanggil saat section scroll into view)
	 */
	public function ajax_house_designs()
	{
		$this->load->library('ternak_api');
		$house_designs = $this->ternak_api->get_public_house_designs();
		$this->load->view('components/partials/design_cards', ['house_designs' => $house_designs]);
	}

	/**
	 * AJAX: Lazy load Perumahan Sikumbang (dipanggil saat section scroll into view)
	 */
	public function ajax_perumahan()
	{
		$keyword = $this->input->get('keyword') ? $this->input->get('keyword') : '';
		$sort    = $this->input->get('sort') ? $this->input->get('sort') : 'terbaru';
		$limit   = $this->input->get('limit') ? $this->input->get('limit') : 9;
		$page    = $this->input->get('page') ? $this->input->get('page') : 1;

		$api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
		$params = [
			'kodeWilayah' => '33',
			'keyword'     => $keyword,
			'searchBy'    => 'nama-perumahan',
			'sort'        => $sort,
			'limit'       => $limit,
			'page'        => $page
		];

		$full_url = $api_url . '?' . http_build_query($params);
		
		$cache_file = APPPATH . 'cache/ajax_perumahan_' . md5($full_url) . '.json';
		$cache_time = 3600; // 1 jam cache
		$response = null;
		
		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
			$response = curl_exec($ch);
			curl_close($ch);
			
			if ($response) {
				@file_put_contents($cache_file, $response);
			}
		}

		$decoded = json_decode($response, true);

		$datacontent['results'] = isset($decoded['data']) ? $decoded['data'] : [];
		$this->load->view('components/cards/rumah', $datacontent);
	}
	public function detail_artikel ($idYangDicari) {
		$dataArray  = $this->ternak_api->get_public_articles();

		$hasilCari = array_filter($dataArray, function($item) use ($idYangDicari) {
			return $item['id'] == $idYangDicari;
		});
		if (!empty($hasilCari)) {
			// Menggunakan array_values agar index-nya kembali dari 0
			$datacontent['item'] = array_values($hasilCari)[0];
			$datacontent['articles'] = $dataArray;
			// Tampilkan data
			$data['content'] = $this->load->view('pages/artikel/detail_artikel', $datacontent, true);

			$this->load->view('layouts/main',$data);
		} else {
			echo "Data tidak ditemukan.";
		}
	}
	public function detail_perum($idLokasi = NULL) {
		if ($idLokasi === NULL) {
            redirect('layouts/main');
        }
		$cache_file = APPPATH . 'cache/sikumbang_detail_' . $idLokasi . '.json';
		$cache_time = 86400; // 24 jam cache
		$response = null;
		$err = false;

		// 1. Cek apakah ada cache yang valid
		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			// 2. Jika tidak ada cache, fetch dari API
			$full_url = "https://sikumbang.tapera.go.id/lokasi-perumahan/" . $idLokasi . "/json";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);

			// 3. Simpan cache jika berhasil
			if (!$err && $response) {
				@file_put_contents($cache_file, $response);
			}
		}

		// 4. Jika error / kosong
		if ($err || empty($response)) {
			show_404();
		}

		$decoded_data = json_decode($response, true);
		if (empty($decoded_data)) {
			show_404();
		}

    // 6. Masukkan hasil decode langsung ke index 'row' dalam $datacontent
   		$datacontent['row'] = $decoded_data['detail'];
		$datacontent['bangunan'] = isset($decoded_data['bangunan']) ? $decoded_data['bangunan'] : [];
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/perumahan/detail_perumahan', $datacontent, true);
		$this->load->view('layouts/main',$data);

	}
	public function cari_wil() {
		$kode_wilayah = $this->input->get('kodeWilayah') ? $this->input->get('kodeWilayah') : '3374';
        $keyword      = $this->input->get('keyword') ? $this->input->get('keyword') : '';
        $sort         = $this->input->get('sort') ? $this->input->get('sort') : 'terbaru';
        $status_rumah = $this->input->get('status_rumah') ? $this->input->get('status_rumah') : 'subsidi';
		$page         = $this->input->get('page') ? $this->input->get('page') : 1;
		$limit		  = $this->input->get('limit') ? $this->input->get('limit') : 9;

        $api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
		$params = [
			'kodeWilayah' =>$kode_wilayah, 
			'keyword'     => $keyword,
			'searchBy'    => 'nama-perumahan',
			'sort'        => $sort,
			'limit'       => $limit,
			'page'        => $page
		];

		$full_url = $api_url . '?' . http_build_query($params);
		
		$cache_file = APPPATH . 'cache/ajax_perumahan_' . md5($full_url) . '.json';
		$cache_time = 3600; // 1 jam cache
		$response = null;

		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
			$response = curl_exec($ch);
			curl_close($ch);

			if ($response) {
				@file_put_contents($cache_file, $response);
			}
		}

		$decoded = json_decode($response, true);
		$raw_list = isset($decoded['data']) ? $decoded['data'] : [];

		$list_final = [];
		if (is_array($raw_list)) {
			foreach ($raw_list as $row) {
				if ($status_rumah === 'semua') {
					$list_final[] = $row;
				} else {
					$punya_subsidi = false;
					if (!empty($row['tipeRumah'])) {
						foreach ($row['tipeRumah'] as $tipe) {
							if (isset($tipe['status']) && strtolower($tipe['status']) === 'subsidi') {
								$punya_subsidi = true;
								break; 
							}
						}
					}
					if ($punya_subsidi) {
						$list_final[] = $row;
					}
				}
			}
		}

		$datacontent['results'] = $list_final;
		$this->load->view('components/cards/rumah', $datacontent);
	}
	public function load_more() {
    // Ambil parameter dari AJAX
		$kode_wilayah = $this->input->get('kodeWilayah') ? $this->input->get('kodeWilayah') : '3374'; // Default: Wonogiri (3312);
        $keyword      = $this->input->get('keyword') ? $this->input->get('keyword') : '';
        $sort         = $this->input->get('sort') ? $this->input->get('sort') : 'terbaru'; // Default: terbaru
        $status_rumah = $this->input->get('status_rumah') ? $this->input->get('status_rumah') : 'subsidi';
		$page         = $this->input->get('page') ? $this->input->get('page') : 2;
		$limit		  = $this->input->get('limit') ? $this->input->get('limit') : 9;
		$api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
		$params = [
			'kodeWilayah' => $kode_wilayah,
			'keyword'     => $keyword,
			'searchBy'    => 'nama-perumahan',
			'sort'        => $sort,
			'limit'       => $limit,
			'page'        => $page
		];

		$full_url = $api_url . '?' . http_build_query($params);

		$cache_file = APPPATH . 'cache/ajax_perumahan_' . md5($full_url) . '.json';
		$cache_time = 3600; // 1 jam cache
		$response = null;
		$err = false;

		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);

			if (!$err && $response) {
				@file_put_contents($cache_file, $response);
			}
		}

		$list_final = [];

		if (!$err) {
			$decoded_data = json_decode($response, true);
			$raw_list = isset($decoded_data['data']) ? $decoded_data['data'] : [];

			if (is_array($raw_list)) {
				foreach ($raw_list as $row) {
					if ($status_rumah === 'semua') {
						$list_final[] = $row;
					} else {
						$punya_subsidi = false;
						if (!empty($row['tipeRumah'])) {
							foreach ($row['tipeRumah'] as $tipe) {
								if (isset($tipe['status']) && strtolower($tipe['status']) === 'subsidi') {
									$punya_subsidi = true;
									break; 
								}
							}
						}
						if ($punya_subsidi) { $list_final[] = $row; }
					}
				}
			}
		}

		// Jika ada data perumahan yang lolos filter
		if (!empty($list_final)) {
			// Kirim data langsung ke sub-view card item saja
			$datacontent['results'] = $list_final;
			$this->load->view('components/cards/rumah', $datacontent);
		} else {
			// Jika kosong, jangan kirim HTML apapun agar trigger 'Semua Data Telah Dimuat' aktif di JS
			echo "";
		}
	}
	public function buka_foto() {
		$path_gambar = $this->input->get('path');
		if (empty($path_gambar)) { show_404(); }

		// 1. Tentukan folder untuk menyimpan cache gambar di XAMPP Anda
		$dir_cache = FCPATH . 'assets/cache_foto/';
		if (!is_dir($dir_cache)) {
			mkdir($dir_cache, 0777, true); // Buat folder otomatis jika belum ada
		}

		// Buat nama file unik berdasarkan path aslinya agar tidak tertukar
		$nama_file_lokal = md5($path_gambar) . '.jpg';
		$path_file_lokal = $dir_cache . $nama_file_lokal;

		// 2. JIKA GAMBAR SUDAH PERNAH DIDOWNLOAD (ADA DI LOKAL), LANGSUNG TAMPILKAN! (INSTAN)
		if (file_exists($path_file_lokal)) {
			$this->output
				->set_header('Cache-Control: public, max-age=2592000') // Cache di browser 30 hari
				->set_content_type('image/jpeg')
				->set_output(file_get_contents($path_file_lokal));
			return;
		}

		// 3. JIKA BELUM ADA, BARU DOWNLOAD VIA CURL (HANYA SEKALI SAJA)
		$url_asli = 'https://sikumbang.tapera.go.id/' . $path_gambar;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_asli);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
		$gambar_mentah = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code == 200 && !empty($gambar_mentah)) {
			// Simpan file mentah ke folder cache lokal untuk pemanggilan berikutnya
			file_put_contents($path_file_lokal, $gambar_mentah);

			// Tampilkan ke browser
			$this->output
				->set_content_type('image/jpeg')
				->set_output($gambar_mentah);
		} else {
			// Jika gambar di server Sikumbang corupt/tidak ada, tampilkan placeholder gambar kosong
			$this->output
				->set_content_type('image/jpeg')
				->set_output(file_get_contents('https://placehold.co/600x400?text=No+Image'));
		}
	}
	public function umum()
	{
		
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/umum/umum', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function pengembang()
	{
		
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/pengembang/pengembang', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function kemitraan()
	{
		
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/kemitraan/kemitraan', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function listkabupaten()
	{
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/data_spasial/listkabupaten', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function simulasi_kpr() {
		$datacontent['judul'] = 'Simulasi KPR';
		$data['content'] = $this->load->view('pages/kpr/simulasi', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function profil() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/profil/sejarah_visi', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function tugas_pokok() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/profil/tugas_pokok', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function struktur() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/profil/struktur_organisasi', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function materia() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/bank_desain/materia', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function sebaran_rusun() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/spasial/sebaran_rusun', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function profil_kumuh() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/spasial/profil_kumuh', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function sebaran_sdgs() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/spasial/sebaran_sdgs', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function sebaran($kodeWilayah = '33') {
		$datacontent['judul']='';
		
		$keyword = $this->input->get('keyword') ? $this->input->get('keyword') : '';
        $sort    = $this->input->get('sort') ? $this->input->get('sort') : 'terbaru';
        $limit   = $this->input->get('limit') ? $this->input->get('limit') :10000;

        $api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
        
        $params = [
            'kodeWilayah' => $kodeWilayah,
            'keyword'     => $keyword,
            'searchBy'    => 'nama-perumahan',
            'sort'        => $sort,
            'limit'       => $limit,
        ];

        $full_url = $api_url . '?' . http_build_query($params);

        $cache_file = APPPATH . 'cache/sikumbang_sebaran_jateng.json';
        $cache_time = 86400; // 24 jam
        $is_searching = ($keyword != '' || $sort != 'terbaru');
        $response = null;
        $err = false;

        if (!$is_searching && file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
            $response = file_get_contents($cache_file);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $full_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && !$is_searching && $response) {
                @file_put_contents($cache_file, $response);
            }
        }

        if (!$err && $response) {
            $decoded_data = json_decode($response, true);
            $datacontent['results'] = isset($decoded_data['data']) ? $decoded_data['data'] : [];
        } else {
			$datacontent['results'] = [];
		}

		$data['content'] = $this->load->view('pages/data_spasial/sebaran', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	// --- PERTANAHAN (DUMMY) ---
	public function info_tanah() {
		$datacontent['title'] = 'Informasi Status Tanah';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk fitur pengecekan kesesuaian tata ruang dan status tanah kawasan permukiman.';
		$data['content'] = $this->load->view('pages/pertanahan/dummy_page', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function sertifikasi() {
		$datacontent['title'] = 'Sertifikasi Lahan Perumahan';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk fasilitasi sertifikasi tanah / konsolidasi lahan untuk perumahan masyarakat.';
		$data['content'] = $this->load->view('pages/pertanahan/dummy_page', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function sengketa() {
		$datacontent['title'] = 'Penyelesaian Sengketa Tanah';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk layanan penyelesaian sengketa lahan atau fasilitasi serah terima PSU perumahan.';
		$data['content'] = $this->load->view('pages/pertanahan/dummy_page', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function bank_tanah() {
		$datacontent['title'] = 'Bank Tanah (Land Bank)';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk sistem informasi ketersediaan lahan pemerintah (Bank Tanah) untuk pembangunan hunian.';
		$data['content'] = $this->load->view('pages/pertanahan/dummy_page', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}
}
