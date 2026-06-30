<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Umum extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download', 'forum'));
		$this->load->model('Forum_model');
		date_default_timezone_set('Asia/Jakarta');
	}

	public function index()
	{
		
	}

	public function housing()
	{
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/perumahan/housing_carrier1', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function info_rumah()
	{
		$this->load->view('pages/umum/info_rumah');
	}

	public function sebaran($kodeWilayah = '33')
	{
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
        $cache_time = 86400;
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
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'); 

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
        }
		$data['content'] = $this->load->view('pages/data_spasial/sebaran', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function aduan()
	{
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/umum/aduan', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

    public function form_aduan()
	{
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/umum/form_aduan', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	// =========================================================
	// FORUM: List Diskusi (dengan search & filter)
	// =========================================================

	public function forum()
	{
		$search   = $this->input->get('q');
		$kategori = $this->input->get('kategori');
		
		$datacontent['judul']          = 'Forum Diskusi';
		$datacontent['diskusi']        = $this->Forum_model->get_all_diskusi($search, $kategori);
		$datacontent['search']         = $search;
		$datacontent['kategori_aktif'] = $kategori;
		
		$data['content'] = $this->load->view('pages/umum/forum', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	// =========================================================
	// FORUM: Buat Diskusi Baru (LOGIN REQUIRED)
	// =========================================================

	public function tambah_aksi() {
		// AUTH GATE: Wajib login
		if (!$this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk membuat diskusi.');
			redirect('Auth/login');
			return;
		}

		$user_id = $this->get_user_id();

		// RATE LIMIT: Maks 5 topik per jam per user
		if (!check_forum_rate_limit('diskusi', $user_id, 5)) {
			$this->session->set_flashdata('error', 'Anda terlalu sering membuat topik. Silakan coba lagi nanti.');
			redirect('Umum/forum');
			return;
		}

		// INPUT
		$judul = sanitize_forum_input($this->input->post('judul_topik'));
		$kat   = $this->input->post('kategori');
		$isi   = sanitize_forum_input($this->input->post('isi_diskusi'));

		// VALIDASI WAJIB
		if (empty($judul) || empty($isi)) {
			$this->session->set_flashdata('error', 'Judul dan deskripsi wajib diisi.');
			redirect('Umum/forum');
			return;
		}

		// VALIDASI PANJANG
		if (mb_strlen($judul) < 10 || mb_strlen($judul) > 200) {
			$this->session->set_flashdata('error', 'Judul topik harus antara 10–200 karakter.');
			redirect('Umum/forum');
			return;
		}
		if (mb_strlen($isi) < 20) {
			$this->session->set_flashdata('error', 'Deskripsi masalah minimal 20 karakter.');
			redirect('Umum/forum');
			return;
		}

		// VALIDASI KATEGORI (whitelist)
		$valid_kategori = ['RTLH', 'Prasarana Umum', 'Sengketa Lahan', 'Rumah Subsidi', 'Lainnya'];
		if (!in_array($kat, $valid_kategori)) {
			$this->session->set_flashdata('error', 'Kategori tidak valid.');
			redirect('Umum/forum');
			return;
		}

		// PROFANITY CHECK
		$check_judul = contains_profanity($judul);
		$check_isi   = contains_profanity($isi);
		if ($check_judul['found'] || $check_isi['found']) {
			$this->session->set_flashdata('error', 'Postingan Anda mengandung kata-kata yang tidak diperbolehkan. Harap gunakan bahasa yang sopan.');
			redirect('Umum/forum');
			return;
		}

		// INSERT — nama & email diambil dari session
		$data = [
			'nama_user'   => $this->session->userdata('username') ?: ($this->session->userdata('name') ?: 'Pengguna'),
			'email_user'  => $this->session->userdata('email') ?: '',
			'judul_topik' => $judul,
			'kategori'    => $kat,
			'isi_diskusi' => $isi,
			'user_id'     => $user_id,
			'ip_address'  => $this->input->ip_address(),
			'status'      => 'open',
			'created_at'  => date('Y-m-d H:i:s')
		];
		$this->Forum_model->insert_diskusi($data);

		$this->session->set_flashdata('success', 'Topik diskusi berhasil dibuat!');
		redirect('Umum/forum');
	}

	// =========================================================
	// FORUM: Detail Diskusi
	// =========================================================

	public function detail($id) {
		$datacontent['topik'] = $this->Forum_model->get_diskusi_by_id($id);
		if (empty($datacontent['topik'])) { show_404(); }
		
		// Increment view count
		$this->Forum_model->increment_view($id);
		$datacontent['topik']['view_count'] = ($datacontent['topik']['view_count'] ?? 0) + 1;
		
		$datacontent['komentar'] = $this->Forum_model->get_komentar_by_diskusi($id);
		
		// User likes status (jika login)
		$datacontent['user_likes'] = [];
		if ($this->is_logged_in()) {
			$datacontent['user_likes'] = $this->Forum_model->get_user_likes($this->get_user_id(), $id);
		}
		
		$data['content'] = $this->load->view('pages/perumahan/detail', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	// =========================================================
	// FORUM: Balas/Komentar (LOGIN REQUIRED)
	// =========================================================

	public function balas_aksi() {
		// AUTH GATE
		if (!$this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
			redirect('Auth/login');
			return;
		}

		$user_id     = $this->get_user_id();
		$id_diskusi  = $this->input->post('id_diskusi');
		$isi_komentar = sanitize_forum_input($this->input->post('isi_komentar'));

		// VALIDASI
		if (empty($id_diskusi) || empty($isi_komentar)) {
			$this->session->set_flashdata('error', 'Semua field wajib diisi.');
			redirect('Umum/forum');
			return;
		}

		if (mb_strlen($isi_komentar) < 5) {
			$this->session->set_flashdata('error', 'Tanggapan minimal 5 karakter.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		// RATE LIMIT: Maks 10 komentar per jam per user
		if (!check_forum_rate_limit('komentar', $user_id, 10)) {
			$this->session->set_flashdata('error', 'Anda terlalu sering membalas. Silakan coba lagi nanti.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		// PROFANITY CHECK
		$check = contains_profanity($isi_komentar);
		if ($check['found']) {
			$this->session->set_flashdata('error', 'Komentar Anda mengandung kata-kata yang tidak diperbolehkan.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		// ROLE: ditentukan di backend
		$role = 'Warga';
		$session_role = $this->session->userdata('role');
		if (in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
			$role = 'Petugas Disperakim';
		}

		$data = [
			'id_diskusi'      => $id_diskusi,
			'reply_to'        => $this->input->post('reply_to') ?: NULL,
			'nama_komentator' => $this->session->userdata('username') ?: ($this->session->userdata('name') ?: 'Pengguna'),
			'isi_komentar'    => $isi_komentar,
			'role'            => $role,
			'user_id'         => $user_id,
			'ip_address'      => $this->input->ip_address(),
			'created_at'      => date('Y-m-d H:i:s')
		];

		$this->Forum_model->insert_komentar($data);
		$this->Forum_model->auto_hide_reported(5);

		redirect('Umum/detail/' . $id_diskusi);
	}

	// =========================================================
	// FORUM: Report System (AJAX)
	// =========================================================

	public function report_komentar() {
		$id = $this->input->post('id');
		if (empty($id)) {
			echo json_encode(['status' => 'error']);
			return;
		}
		$this->Forum_model->report_komentar($id);
		$this->Forum_model->auto_hide_reported(5);
		echo json_encode(['status' => 'ok']);
	}

	// =========================================================
	// FORUM: Like/Unlike (AJAX, LOGIN REQUIRED)
	// =========================================================

	public function toggle_like() {
		if (!$this->is_logged_in()) {
			echo json_encode(['status' => 'error', 'message' => 'Login required']);
			return;
		}
		
		$target_type = $this->input->post('type'); // 'diskusi' atau 'komentar'
		$target_id   = $this->input->post('id');
		
		if (!in_array($target_type, ['diskusi', 'komentar']) || empty($target_id)) {
			echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
			return;
		}
		
		$result = $this->Forum_model->toggle_like($this->get_user_id(), $target_type, $target_id);
		echo json_encode(['status' => 'ok', 'action' => $result['action'], 'count' => $result['count']]);
	}

	// =========================================================
	// FORUM: Admin Moderasi (LOGIN + ROLE REQUIRED)
	// =========================================================

	private function _check_admin() {
		if ($this->session->userdata('is_logged') !== TRUE) {
			redirect('Auth/login');
			return false;
		}
		$session_role = $this->session->userdata('role');
		if (!in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
			show_error('Anda tidak memiliki izin.', 403);
			return false;
		}
		return true;
	}

	public function update_status_diskusi() {
		if (!$this->_check_admin()) return;
		$id     = $this->input->post('id_diskusi');
		$status = $this->input->post('status');
		$this->Forum_model->update_status($id, $status);
		redirect('Umum/detail/' . $id);
	}

	public function delete_diskusi() {
		if (!$this->_check_admin()) return;
		$id = $this->input->post('id_diskusi');
		$this->Forum_model->soft_delete_diskusi($id);
		$this->session->set_flashdata('success', 'Diskusi berhasil dihapus.');
		redirect('Umum/forum');
	}

	public function delete_komentar() {
		if (!$this->_check_admin()) return;
		$id_komentar = $this->input->post('id_komentar');
		$id_diskusi  = $this->input->post('id_diskusi');
		$this->Forum_model->soft_delete_komentar($id_komentar);
		redirect('Umum/detail/' . $id_diskusi);
	}

	// =========================================================
	// PENGEMBANG
	// =========================================================

	public function pengembang() {
		$apiUrl = "https://sikumbang.tapera.go.id/ajax/lokasi/search?selectedSearch=wilayah&skalaPerumahan=semua&kodeWilayah=33&sort=terbaru&searchBy=nama-perumahan&page=1&limit=18";

		$cache_file = APPPATH . 'cache/sikumbang_pengembang.json';
		$cache_time = 86400;
		$response = null;
		$error = false;

		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $apiUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Referer: https://sikumbang.tapera.go.id/'
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			
			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);
			
			if (!$error && $response) {
				@file_put_contents($cache_file, $response);
			}
		}

		if ($response === false) {
			$this->session->set_flashdata('error', 'Gagal terhubung ke API Tapera: ' . $error);
			$items = [];
		} else {
			$decoded = json_decode($response, true);
			$items = $decoded['data'] ?? [];
		}

		$local_sp2 = [];
		if ($this->db->table_exists('srp2_registrations')) {
			$local_sp2 = $this->db->get('srp2_registrations')->result_array();
		}

		$developers = array_map(function($item) use ($local_sp2) {
			$pengembang_nama = $item['pengembang']['nama'] ?? '-';
			$sp2_status = "Belum Terdata";
			
			if ($pengembang_nama !== '-') {
				foreach ($local_sp2 as $local) {
					if (strtolower($pengembang_nama) === strtolower($local['nama_perusahaan'])) {
						$sp2_status = $local['nib'];
						break;
					}
				}
			}

			return [
				'nama_perumahan' => $item['namaPerumahan'] ?? '-',
				'pengembang'     => $pengembang_nama,
				'asosiasi'       => $item['pengembang']['asosiasi'] ?? '-',
				'kabupaten'      => $item['wilayah']['kabupaten'] ?? '-',
				'telepon'        => $item['kantorPemasaran'][0]['noTelp'] ?? '-',
				'email'          => $item['kantorPemasaran'][0]['email'] ?? '-',
				'sp2_status'     => $sp2_status
			];
		}, $items);

		$datacontent['developers'] = $developers;
		$data['content'] = $this->load->view('pages/pengembang/list_pengembang', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function detail_pengembang($nama = '') {
		$nama = urldecode($nama);
		$local_data = [];
		if ($this->db->table_exists('srp2_registrations')) {
			$local_data = $this->db->get_where('srp2_registrations', ['nama_perusahaan' => $nama])->row_array();
		}
		$datacontent['nama_pengembang'] = $nama;
		$datacontent['local_data'] = $local_data;
		
		$data['content'] = $this->load->view('pages/pengembang/detail_pengembang', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	public function download_sertifikat($nama = '') {
		if (!$this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk mengunduh sertifikat.');
			redirect('Auth/login');
			return;
		}

		$this->session->set_flashdata('success', 'Sertifikat berhasil diunduh. (Simulasi)');
		redirect('Umum/detail_pengembang/' . $nama);
	}
}
