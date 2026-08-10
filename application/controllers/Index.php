<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Index extends MY_Controller {

	/* Batas KERAS dari SIKUMBANG, diukur bukan ditebak: `limit=500` tetap
	   dibalas 100 baris. Wonogiri (47 lokasi) dibalas 47, jadi ini plafon,
	   bukan jumlah tetap. */
	const SIK_BONGKAH = 100;

	/* Plafon bongkahan per permintaan. Wilayah dengan lokasi TERBANYAK di
	   Jateng adalah 3324 (Kendal) dengan 320 lokasi = 4 bongkahan; 36 wilayah
	   diperiksa satu per satu 10 Agt 2026. Lima memberi kelonggaran satu
	   bongkahan tanpa membiarkan satu permintaan menyapu API tanpa batas.
	   ponytail: kalau kelak ada wilayah > 500 lokasi, naikkan angka ini -
	   gejalanya "Semua Data Telah Dimuat" muncul terlalu cepat di wilayah itu. */
	const SIK_MAKS_BONGKAH = 5;

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->load->library('ternak_api');
        $this->load->helper(['ternak', 'url', 'form','download']);
	}
	public function golek_omah()
	{
		$datacontent['judul']='';
		$this->render('pages/golek_omah/index', $datacontent);
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

		$this->render('pages/home/awal', $datacontent);
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
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
            redirect('cari_rumah');
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
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
	/**
	 * Saring hasil Sikumbang menurut status rumah.
	 *
	 * TIGA nilai, bukan dua - dan itulah perbaikannya. Sampai 3 Agt 2026 kode ini
	 * cuma punya cabang `semua` dan "selain itu", dan cabang "selain itu" HANYA
	 * menyimpan baris yang PUNYA tipe subsidi. Halaman cari rumah mengirim
	 * `komersil` saat toggle dimatikan, jatuh ke cabang itu, dan menerima daftar
	 * yang persis sama dengan saat toggle menyala.
	 *
	 * Jadi tombol "Hanya Rumah Subsidi" tidak pernah bisa dimatikan - mematikannya
	 * tidak mengubah apa pun, dan tidak ada satu pun pesan yang memberi tahu.
	 * Ketahuan saat revisi dinas meminta toggle itu diganti dua tombol.
	 *
	 * `komersil` = tidak ada satu pun tipe rumah berstatus subsidi di lokasi itu.
	 * Bukan "ada tipe komersil" - satu lokasi bisa memuat keduanya, dan lokasi
	 * campuran memang layak muncul di daftar subsidi.
	 */
	private function saring_status_rumah($raw_list, $status_rumah) {
		if ( ! is_array($raw_list)) { return []; }
		if ($status_rumah === 'semua') { return $raw_list; }

		$hasil = [];
		foreach ($raw_list as $row) {
			$punya_subsidi = FALSE;
			if ( ! empty($row['tipeRumah']) && is_array($row['tipeRumah'])) {
				foreach ($row['tipeRumah'] as $tipe) {
					if (isset($tipe['status']) && strtolower($tipe['status']) === 'subsidi') {
						$punya_subsidi = TRUE;
						break;
					}
				}
			}
			$cocok = $status_rumah === 'komersil' ? ! $punya_subsidi : $punya_subsidi;
			if ($cocok) { $hasil[] = $row; }
		}
		return $hasil;
	}

	/**
	 * Parameter pencarian, dibaca sekali dari query string.
	 *
	 * `limit` di sini adalah UKURAN HALAMAN yang tampil, bukan lagi limit yang
	 * dikirim ke API. Dijepit 1..50 supaya satu permintaan tidak bisa memaksa
	 * server memindai seluruh wilayah berkali-kali.
	 */
	private function parameter_cari() {
		$limit = (int) ($this->input->get('limit') ?: 9);
		$page  = (int) ($this->input->get('page') ?: 1);

		return [
			'kodeWilayah'  => $this->input->get('kodeWilayah') ?: '3374',
			'keyword'      => $this->input->get('keyword') ?: '',
			'searchBy'     => $this->input->get('searchBy') ?: 'nama-perumahan',
			'sort'         => $this->input->get('sort') ?: 'terbaru',
			'status_rumah' => $this->input->get('status_rumah') ?: 'subsidi',
			'page'         => max(1, $page),
			'limit'        => min(50, max(1, $limit)),
		];
	}

	/**
	 * Satu bongkahan mentah dari SIKUMBANG. NULL = gagal jaringan (beda dari
	 * array kosong, yang berarti sumbernya memang habis - dan bedanya penting:
	 * yang satu tidak boleh dibaca sebagai "semua data telah dimuat").
	 */
	private function bongkah_sikumbang(array $p, $halaman_api) {
		$full_url = 'https://sikumbang.tapera.go.id/ajax/lokasi/search?' . http_build_query([
			'kodeWilayah' => $p['kodeWilayah'],
			'keyword'     => $p['keyword'],
			'searchBy'    => $p['searchBy'],
			'sort'        => $p['sort'],
			'limit'       => self::SIK_BONGKAH,
			'page'        => $halaman_api,
		]);

		$cache_file = APPPATH . 'cache/ajax_perumahan_' . md5($full_url) . '.json';
		$response   = NULL;

		if (file_exists($cache_file) && (time() - filemtime($cache_file) < 3600)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $full_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
			$response = curl_exec($ch);
			$err      = curl_error($ch);
			curl_close($ch);

			if ($err || ! $response) { return NULL; }
			@file_put_contents($cache_file, $response);
		}

		$decoded = json_decode($response, TRUE);
		return isset($decoded['data']) && is_array($decoded['data']) ? $decoded['data'] : [];
	}

	/**
	 * Daftar lokasi yang SUDAH tersaring, dipotong sesuai halaman yang diminta.
	 *
	 * BUTIR A3 REVISI DINAS - "kayaknya belum ke-load semua, muncul cuma 3 atau
	 * 6 gambar saja." Keluhan itu BENAR, dan jawaban kami yang pertama keliru.
	 *
	 * Penyebabnya: penyaring subsidi/non-subsidi berjalan DI SINI, sementara
	 * dulu API diminta `limit=9` lalu sembilan baris itulah yang disaring. Yang
	 * tersisa 1-8 buah dan berubah-ubah tiap halaman. Terukur 10 Agt 2026 di
	 * Kota Semarang (wilayah bawaan halaman ini): API mengirim 9, yang lolos
	 * saringan subsidi hanya SATU. Angka "3 atau 6" itu bukan angka misterius,
	 * ia sisa saringan.
	 *
	 * Akibat keduanya lebih jahat: halaman 3 Semarang kebetulan NOL yang cocok,
	 * dan `load_more()` membaca balasan kosong sebagai "Semua Data Telah
	 * Dimuat". Daftarnya mati di situ padahal halaman 4 dan seterusnya masih
	 * berisi. Data hilang tanpa satu pun tanda.
	 *
	 * Sekarang bongkahannya 100 - bukan 9 - dikumpulkan sampai cukup untuk
	 * halaman yang diminta, BARU dipotong. Halaman penuh 9 selama datanya
	 * masih ada, dan "habis" hanya diucapkan kalau sumbernya memang habis.
	 */
	private function lokasi_tersaring(array $p) {
		$butuh = $p['page'] * $p['limit'];
		$cocok = [];
		$gagal = FALSE;

		for ($i = 1; $i <= self::SIK_MAKS_BONGKAH; $i++) {
			$baris = $this->bongkah_sikumbang($p, $i);

			if ($baris === NULL) { $gagal = TRUE; break; }   // jaringan, bukan kehabisan
			if ( ! $baris)       { break; }                  // sumber habis

			$cocok = array_merge($cocok, $this->saring_status_rumah($baris, $p['status_rumah']));

			if (count($baris) < self::SIK_BONGKAH) { break; } // bongkahan terakhir
			if (count($cocok) >= $butuh)           { break; } // sudah cukup untuk halaman ini
		}

		$potong = array_slice($cocok, ($p['page'] - 1) * $p['limit'], $p['limit']);
		return [$potong, $gagal];
	}

	public function cari_wil() {
		$p = $this->parameter_cari();
		list($list_final, $gagal) = $this->lokasi_tersaring($p);

		if ($gagal && ! $list_final) {
			echo '<p class="col-span-full py-10 text-center text-sm text-[color:var(--portal-text-muted)]">'
			   . 'Data rumah gagal diambil dari SIKUMBANG. Silakan coba lagi sebentar lagi.</p>';
			return;
		}

		$datacontent['results'] = $list_final;
		$this->load->view('components/cards/rumah', $datacontent);
	}

	public function load_more() {
		$p = $this->parameter_cari();
		if ($p['page'] < 2) { $p['page'] = 2; }

		list($list_final, $gagal) = $this->lokasi_tersaring($p);

		/* Balasan kosong dibaca JS sebagai "Semua Data Telah Dimuat", jadi ia
		   hanya boleh keluar saat datanya BENAR-BENAR habis. Waktu jaringan
		   gagal, kirim penanda supaya tombolnya tetap hidup dan bisa dicoba
		   lagi - bukan dimatikan seolah data sudah tandas. */
		if ($gagal && ! $list_final) {
			echo '<!-- gagal-jaringan -->';
			return;
		}

		if ( ! $list_final) { echo ''; return; }

		$datacontent['results'] = $list_final;
		$this->load->view('components/cards/rumah', $datacontent);
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
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
		$this->render('pages/pengembang/pengembang', $datacontent);
	}
	public function kemitraan()
	{

		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/kemitraan/kemitraan', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function panduan_desain()
	{

		$datacontent['judul']='';
		$datacontent['is_ajax_load'] = $this->input->is_ajax_request();

		/* Butir 4 putaran 2: desain PROTOTIPE beserta RAB-nya. Sumbernya API
		   KRS Jawa 3 yang sama dengan Bank Desain, dan sudah terpasang sejak
		   lama - hanya belum pernah ditampilkan. Diambil server-side, bukan
		   AJAX seperti katalog di bawahnya: jumlahnya sembilan, jadi menunda
		   pemuatannya cuma menambah satu perjalanan tanpa manfaat. */
		$datacontent['prototipe'] = $this->ternak_api->get_public_prototype_designs();

		$this->render('pages/bank_desain/panduan_desain', $datacontent);
	}
	public function detail_desain($id = NULL)
	{
		if ($id === NULL || !ctype_digit((string) $id)) {
			show_404();
		}

		$house_designs = $this->ternak_api->get_public_house_designs();
		$design = NULL;
		foreach ($house_designs as $item) {
			if (isset($item['id']) && (string) $item['id'] === (string) $id) {
				$design = $item;
				break;
			}
		}

		if ($design === NULL) {
			show_404();
		}

		$this->render('pages/bank_desain/detail_desain', [
			'judul' => $design['title'],
			'design' => $design,
		]);
	}
	public function cari_rumah()
	{
		$datacontent['judul']='';
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
		$this->render('pages/perumahan/cari_rumah', $datacontent);
	}
	public function listkabupaten()
	{
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/data_spasial/listkabupaten', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function simulasi_kpr() {
		$datacontent['judul'] = 'Simulasi KPR';
		$this->render('pages/kpr/simulasi', $datacontent);
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

	// A4 - struktur() DICABUT 29 Jul 2026 atas keputusan user, bersama
	// view-nya. Halaman itu memajang NAMA INDIVIDU NYATA sebagai Kepala Dinas
	// tanpa satu pun sumber data, ditambah empat placeholder "Nama Pejabat"
	// yang tampil ke publik. Menyebut nama pejabat tanpa sumber lebih berisiko
	// daripada tidak punya halamannya; mengisinya butuh dokumen resmi dinas
	// beserta kesepakatan siapa yang memperbaruinya saat ada mutasi.

	public function materia() {
		$datacontent['judul']='';
		$data['content'] = $this->load->view('pages/bank_desain/materia', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	// A1 - sebaran_rusun(), profil_kumuh(), dan sebaran_sdgs() DICABUT
	// 29 Jul 2026 atas keputusan user, bersama ketiga view-nya, route-nya, dan
	// kartu penautnya di beranda. Ketiganya menyajikan angka literal tanpa
	// sumber, dan sebaran_rusun menyebut rusunawa BERNAMA NYATA berikut
	// koordinat aslinya dengan okupansi karangan serta tuduhan kerusakan aset.
	// Mengganti angkanya butuh seseorang yang tahu angka benarnya - itu dinas,
	// bukan agent; mencabut adalah satu-satunya tindakan yang boleh diambil
	// tanpa mengarang pengganti (§17).

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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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

		$this->render('pages/data_spasial/sebaran', $datacontent);
	}

	// --- PERTANAHAN (DUMMY) ---
	public function info_tanah() {
		$datacontent['title'] = 'Informasi Status Tanah';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk fitur pengecekan kesesuaian tata ruang dan status tanah kawasan permukiman.';
		$this->render('pages/pertanahan/dummy_page', $datacontent);
	}

	public function sertifikasi() {
		$datacontent['title'] = 'Sertifikasi Lahan Perumahan';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk fasilitasi sertifikasi tanah / konsolidasi lahan untuk perumahan masyarakat.';
		$this->render('pages/pertanahan/dummy_page', $datacontent);
	}

	public function sengketa() {
		$datacontent['title'] = 'Penyelesaian Sengketa Tanah';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk layanan penyelesaian sengketa lahan atau fasilitasi serah terima PSU perumahan.';
		$this->render('pages/pertanahan/dummy_page', $datacontent);
	}

	public function bank_tanah() {
		$datacontent['title'] = 'Bank Tanah (Land Bank)';
		$datacontent['desc'] = 'Halaman ini merupakan pratinjau (dummy) untuk sistem informasi ketersediaan lahan pemerintah (Bank Tanah) untuk pembangunan hunian.';
		$this->render('pages/pertanahan/dummy_page', $datacontent);
	}

	// ============================================================
	// PORTAL TAB CONTENT
	// Menu cards untuk setiap tab di panel navigasi homepage
	// ============================================================

	/**
	 * Tab content: Perumahan - menu cards untuk submenu perumahan
	 */
	public function tab_perumahan() {
		$this->render('pages/home/tab_perumahan');
	}

	/**
	 * Tab content: Kawasan - menu cards untuk submenu kawasan
	 */
	public function tab_kawasan() {
		$this->render('pages/home/tab_kawasan');
	}

	/**
	 * Tab content: Pertanahan - menu cards untuk submenu pertanahan
	 */
	public function tab_pertanahan() {
		$this->render('pages/home/tab_pertanahan');
	}

	/**
	 * Tab content: Pengembang - menu cards untuk submenu pengembang
	 */
	public function tab_pengembang() {
		$this->render('pages/home/tab_pengembang');
	}

	/**
	 * Tab content: Bank Data - menu cards untuk submenu bank data
	 */
	public function tab_bankdata() {
		$this->render('pages/home/tab_bankdata');
	}
}
