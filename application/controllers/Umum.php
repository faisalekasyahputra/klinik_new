<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Umum extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		$this->load->model('Forum_model');
		date_default_timezone_set('Asia/Jakarta');
	}
	public function index()
	{
		
	}

	public function housing()
	{
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('housing_carrier1', $datacontent, true);
		$this->load->view('index',$data);
	}
	public function info_rumah()
	{
		
		$this->load->view('info_rumah');
	}
	public function sebaran($kodeWilayah = '33')
	{
		
		$keyword = $this->input->get('keyword') ? $this->input->get('keyword') : '';
        $sort    = $this->input->get('sort') ? $this->input->get('sort') : 'terbaru';
        $limit   = $this->input->get('limit') ? $this->input->get('limit') :10000;
     

        // 2. Tentukan Endpoint sesuai dokumen OpenAPI
        $api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
        
        // 3. Susun Parameter (Kunci utama: 'kodeWilayah' => '33' untuk Jawa Tengah)
        $params = [
            'kodeWilayah' => $kodeWilayah, // MENGUNCI WILAYAH JAWA TENGAH
            'keyword'     => $keyword,
            'searchBy'    => 'nama-perumahan',
            'sort'        => $sort,
            'limit'       => $limit,
         
        ];

        $full_url = $api_url . '?' . http_build_query($params);

        // 4. Inisialisasi cURL untuk mengambil data JSON
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Lewati verifikasi SSL di localhost/Laragon
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); 

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err) {
            $decoded_data = json_decode($response, true);
            // Sesuaikan indeks data berdasarkan response asli API Sikumbang
            $datacontent['results'] = isset($decoded_data['data']) ? $decoded_data['data'] : [];
			
		  
        }
		$data['content'] = $this->load->view('sebaran', $datacontent, true);
		$this->load->view('index',$data);
	}
	public function aduan()
	{
		
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('aduan', $datacontent, true);
		$this->load->view('index',$data);
	}
    public function form_aduan()
	{
		
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('form_aduan', $datacontent, true);
		$this->load->view('index',$data);
	}
	public function forum()
	{
		
		$datacontent['judul']='Forum Diskusi';
		$datacontent['diskusi'] = $this->Forum_model->get_all_diskusi();
		$data['content'] = $this->load->view('forum', $datacontent, true);
		$this->load->view('index',$data);
	}
	public function tambah_aksi() {
        $data = [
            'nama_user'   => $this->input->post('nama_user', true),
            'email_user'  => $this->input->post('email_user', true),
            'judul_topik' => $this->input->post('judul_topik', true),
            'kategori'    => $this->input->post('kategori', true),
            'isi_diskusi' => $this->input->post('isi_diskusi', true)
        ];
        $this->Forum_model->insert_diskusi($data);
        redirect('Umum/forum');
    }
	public function detail($id) {
        $datacontent['topik'] = $this->Forum_model->get_diskusi_by_id($id);
        if(empty($datacontent['topik'])) { show_404(); }
        
        $datacontent['komentar'] = $this->Forum_model->get_komentar_by_diskusi($id);
        $data['content'] = $this->load->view('detail', $datacontent, true);
		$this->load->view('index',$data);
    }
	public function pengembang() {
    // URL yang Anda temukan
    $apiUrl = "https://sikumbang.tapera.go.id/ajax/lokasi/search?selectedSearch=wilayah&skalaPerumahan=semua&kodeWilayah=33&sort=terbaru&searchBy=nama-perumahan&page=1&limit=18";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Header penting untuk menghindari blokir dari server
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer: https://sikumbang.tapera.go.id/' // Seringkali API membutuhkan Referer yang valid
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        die("Error cURL: " . $error);
    }

    $decoded = json_decode($response, true);
    
    // Perhatikan struktur: di SiKumbang, data biasanya berada di dalam key 'data'
    // Coba tampilkan data mentah jika masih error (hapus comment baris di bawah)
    // echo "<pre>"; print_r($decoded); die();

    $items = $decoded['data'] ?? []; // Sesuaikan berdasarkan struktur JSON hasil print_r

    $developers = array_map(function($item) {
        return [
            'nama_perumahan' => $item['namaPerumahan'] ?? '-',
            'pengembang'     => $item['pengembang']['nama'] ?? '-',
            'asosiasi'       => $item['pengembang']['asosiasi'] ?? '-',
            'kabupaten'      => $item['wilayah']['kabupaten'] ?? '-',
            'telepon'        => $item['kantorPemasaran'][0]['noTelp'] ?? '-',
            'email'          => $item['kantorPemasaran'][0]['email'] ?? '-'
        ];
    }, $items);
		$datacontent['developers'] = $developers;
    	$data['content'] = $this->load->view('list_pengembang', $datacontent, true);
		$this->load->view('index',$data);
}

    /**
     * Endpoint: base_url('Umum/balas_aksi') — POST
     * 
     * SECURITY FIX (Fase 4): Method yang sebelumnya tidak ada (404 error).
     * Role ditentukan di BACKEND, BUKAN dari input user.
     * Default role = 'Warga'. Hanya jika user login dengan session admin/staff,
     * role akan di-set ke 'Petugas Disperakim'.
     */
    public function balas_aksi() {
        $id_diskusi      = $this->input->post('id_diskusi', true);
        $nama_komentator = $this->input->post('nama_komentator', true);
        $isi_komentar    = $this->input->post('isi_komentar', true);

        // Validasi input wajib
        if (empty($id_diskusi) || empty($nama_komentator) || empty($isi_komentar)) {
            $this->session->set_flashdata('error', 'Semua field wajib diisi.');
            redirect('Umum/forum');
            return;
        }

        // PROTEKSI PERAN: Tentukan role secara otomatis di backend
        // Default: Warga. Hanya user dengan session role admin/staff yang mendapat badge Petugas.
        $role = 'Warga';
        $session_role = $this->session->userdata('role');
        if ($this->session->userdata('is_logged') === TRUE && 
            in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
            $role = 'Petugas Disperakim';
        }

        $data = [
            'id_diskusi'      => $id_diskusi,
            'nama_komentator' => $nama_komentator,
            'isi_komentar'    => $isi_komentar,
            'role'            => $role,
            'created_at'      => date('Y-m-d H:i:s')
        ];

        $this->Forum_model->insert_komentar($data);
        redirect('Umum/detail/' . $id_diskusi);
    }
}
