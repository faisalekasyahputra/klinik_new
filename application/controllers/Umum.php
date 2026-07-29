<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Umum extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		$this->load->library('form_validation');
		date_default_timezone_set('Asia/Jakarta');
	}

	/** Load forum dependencies (lazy, hanya untuk method forum) */
	private function _load_forum()
	{
		$this->load->helper('forum');
		$this->load->model('Forum_model');
	}

	public function index()
	{
		
	}

	public function housing()
	{
		// Dulu merender mockup housing_carrier1 yang form-nya action="#" —
		// submit-nya tidak ke mana-mana. Wizard pembiayaan yang sungguhan
		// sudah ada di Program::solusi_pembiayaan(). Redirect supaya
		// bookmark/link lama tidak 404, pola yang sama dengan form_aduan().
		redirect('solusi_pembiayaan');
	}

	// S9 — `info_rumah()` DICABUT 29 Jul 2026 bersama view-nya.
	// Halaman itu me-`include "layout/head.php"` yang tidak pernah ada di
	// `views/pages/umum/`, jadi ia membalas 200 sambil memuat "A PHP Error"
	// dan path absolut server — diverifikasi runtime, bukan dibaca dari kode.
	// Nol tautan masuk dari view mana pun maupun dari routes.php: halaman ini
	// yatim. Menutup display_errors hanya menghilangkan bocorannya, tidak
	// membuat halamannya berfungsi; karena itu dicabut, bukan ditambal.
	// Kalau dinas ternyata masih membutuhkannya, bangun ulang lewat layout
	// aktif berikut perjalanan fungsionalnya.

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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
		// Prefill nama/email kalau user sedang login — kosong untuk tamu.
		$datacontent['nama_default']  = $this->session->userdata('name') ?: '';
		$datacontent['email_default'] = $this->session->userdata('email') ?: '';
		$this->render('pages/umum/aduan', $datacontent);
	}

	public function simpan_aduan()
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		$this->load->model('Aduan_model');

		$this->form_validation->set_rules('nama', 'Nama', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]');
		$this->form_validation->set_rules('judul', 'Judul', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('pesan', 'Pesan', 'required|trim|max_length[2000]');
		// Wajib salah satu pilihan yang memang ada di dropdown (bukan nilai bebas).
		$this->form_validation->set_rules('bidang', 'Bidang Tujuan', 'required|in_list[perumahan,kawasan,pertanahan,pengembang,umum]');

		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
			redirect('umum/aduan');
			return;
		}

		$nama  = $this->input->post('nama', TRUE);
		$email = $this->input->post('email', TRUE);
		$judul = $this->input->post('judul', TRUE);
		$pesan = $this->input->post('pesan', TRUE);

		// user_id selalu dari sesi (anti-IDOR), bukan dari input — tamu tetap
		// boleh kirim aduan dengan user_id NULL.
		$user_id = $this->is_logged_in() ? $this->get_user_id() : NULL;

		$bidang = $this->input->post('bidang', TRUE);

		// Baris dibuat DULU supaya lampirannya punya folder pemilik sendiri
		// (private_uploads/aduan/{id}/), pola yang sama dengan dokumen SRP2.
		$id = $this->Aduan_model->create([
			'user_id'  => $user_id,
			'nama'     => $nama,
			'email'    => $email,
			'judul'    => $judul,
			'bidang'   => $bidang,
			'pesan'    => $pesan,
			'lampiran' => NULL,
		]);

		if ( ! $id) {
			$this->session->set_flashdata('error', 'Gagal menyimpan aduan. Silakan coba lagi.');
			redirect('umum/aduan');
			return;
		}

		$pesan_sukses = 'Aduan Anda berhasil dikirim dan diarahkan ke ' . $this->Aduan_model->bidang_label($bidang) . '. Kami akan segera menindaklanjuti.';

		// Lampiran disimpan di luar webroot dan hanya bisa dibuka lewat endpoint
		// ber-guard (Admin_Bidang/Admin_Aduan) — dulu ditaruh di .assets/uploads/
		// yang bisa diakses HTTP langsung. Kalau lampirannya gagal, aduannya
		// TETAP tersimpan; user diberi tahu apa adanya, bukan dibatalkan diam-diam.
		$galat_lampiran = NULL;
		$nama_lampiran = $this->store_private_upload('lampiran', 'aduan', $id, $galat_lampiran);
		if ($nama_lampiran) {
			$this->db->where('id', $id)->update('aduan', ['lampiran' => $nama_lampiran]);
		} elseif ($galat_lampiran) {
			$pesan_sukses .= ' Namun lampiran gagal diunggah (' . $galat_lampiran . ') — silakan kirim susulan bila perlu.';
		}

		$this->session->set_flashdata('success', $pesan_sukses);

		redirect('umum/aduan');
	}

	public function form_aduan()
	{
		// Konsep lama (pilih bidang -> form terpisah) sudah digabung jadi
		// satu form langsung di aduan(). Redirect supaya bookmark/link lama
		// tidak 404.
		redirect('umum/aduan');
	}

	// =========================================================
	// FORUM: List Diskusi (dengan search & filter)
	// =========================================================

	public function forum()
	{
		$this->_load_forum();
		$search   = $this->input->get('q');
		$kategori = $this->input->get('kategori');
		
		$datacontent['judul']          = 'Forum Diskusi';
		$datacontent['diskusi']        = $this->Forum_model->get_all_diskusi($search, $kategori);
		$datacontent['search']         = $search;
		$datacontent['kategori_aktif'] = $kategori;

		$this->render('pages/umum/forum', $datacontent);
	}

	// =========================================================
	// FORUM: Buat Diskusi Baru (LOGIN REQUIRED)
	// =========================================================

	public function tambah_aksi() {
		$this->_load_forum();
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
		// Penanda sukses TIDAK boleh sederajat dengan query tulisnya (§19).
		// Selama db_debug menyala kegagalan tulis masih berisik; setelah U0
		// mematikannya, INSERT yang ditolak akan diam dan layar tetap
		// mengatakan berhasil.
		if ( ! $this->Forum_model->insert_diskusi($data)) {
			$this->session->set_flashdata('error', 'Topik diskusi belum tersimpan. Coba lagi.');
			redirect('Umum/forum');
			return;
		}

		$this->session->set_flashdata('success', 'Topik diskusi berhasil dibuat!');
		redirect('Umum/forum');
	}

	// =========================================================
	// FORUM: Detail Diskusi
	// =========================================================

	public function detail($id) {
		$this->_load_forum();
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
		
		$data['content'] = $this->load->view('pages/umum/forum_detail', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	// =========================================================
	// FORUM: Balas/Komentar (LOGIN REQUIRED)
	// =========================================================

	public function balas_aksi() {
		$this->_load_forum();
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

		if ( ! $this->Forum_model->insert_komentar($data)) {
			$this->session->set_flashdata('error', 'Balasan belum tersimpan. Coba lagi.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		// `auto_hide_reported(5)` DICABUT dari sini 29 Jul 2026 (B3/U2).
		// Dua alasan. Pertama, penempatannya memang ganjil: menyapu auto-hide
		// sebagai efek samping seseorang membalas komentar. Kedua dan yang
		// menentukan: U2 ledger-only — laporan dicatat, visibilitas TIDAK
		// berubah otomatis. Setelah B3 menghitung `report_count` dari pelapor
		// unik, membiarkan panggilan ini justru membuat lima akun bisa
		// menyembunyikan diskusi tanpa antrean moderasi dan tanpa jalan pulang.
		// Auto-hide baru boleh hidup lewat keputusan #10 beserta roadmapnya.

		redirect('Umum/detail/' . $id_diskusi);
	}

	// =========================================================
	// FORUM: Report System (AJAX)
	// =========================================================

	/**
	 * B3 — dulu endpoint ini ANONIM: siapa pun bisa memanggilnya berulang kali
	 * dan menyensor komentar orang lain sendirian. Guard-nya sudah ada beberapa
	 * baris di bawah, di berkas yang sama (`toggle_like()`), tinggal disalin.
	 *
	 * CSRF bukan penutupnya (B12): `csrf_regenerate` FALSE + double-submit
	 * berarti tokennya bisa diambil anonim lewat satu GET. Yang menutup adalah
	 * guard login + rate limit + dedup di ledger.
	 *
	 * `auto_hide_reported()` SENGAJA tidak lagi dipanggil — U2 ledger-only,
	 * visibilitas komentar tidak berubah sampai keputusan #10 beserta roadmap
	 * moderasinya (antrean + restore) tersedia.
	 */
	public function report_komentar() {
		$this->_load_forum();

		if (!$this->is_logged_in()) {
			echo json_encode(['status' => 'error', 'message' => 'Login required']);
			return;
		}

		$id = (int) $this->input->post('id');
		if (empty($id)) {
			echo json_encode(['status' => 'error']);
			return;
		}

		$rate = $this->rate_limit_consume('forum_report', [
			'account_id' => (int) $this->get_user_id(),
			'object_id'  => $id,
		]);
		if (empty($rate['success']) || empty($rate['allowed'])) {
			$this->rate_limit_reject($rate,
				'Terlalu banyak laporan dalam waktu singkat. Coba lagi sebentar.', TRUE);
			return;
		}

		$hasil = $this->Forum_model->report_komentar($id, $this->get_user_id());
		if (empty($hasil['success'])) {
			echo json_encode(['status' => 'error', 'message' => 'Komentar tidak ditemukan.']);
			return;
		}

		// Pesannya membedakan laporan baru dari laporan ulang, supaya pengguna
		// tidak menekan berkali-kali mengira laporannya tidak masuk.
		echo json_encode([
			'status'  => 'ok',
			'baru'    => $hasil['baru'],
			'message' => $hasil['baru']
				? 'Laporan Anda dicatat.'
				: 'Anda sudah pernah melaporkan komentar ini.',
		]);
	}

	// =========================================================
	// FORUM: Like/Unlike (AJAX, LOGIN REQUIRED)
	// =========================================================

	public function toggle_like() {
		$this->_load_forum();
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
		$this->_load_forum();
		if (!$this->_check_admin()) return;
		$id     = $this->input->post('id_diskusi');
		$status = $this->input->post('status');
		$this->Forum_model->update_status($id, $status);
		redirect('Umum/detail/' . $id);
	}

	public function delete_diskusi() {
		$this->_load_forum();
		if (!$this->_check_admin()) return;
		$id = $this->input->post('id_diskusi');
		// `affected_rows()` ikut diperiksa: UPDATE yang sah tetapi tidak cocok
		// baris mana pun (id salah / sudah terhapus) mengembalikan TRUE, dan
		// tanpa ini admin diberi tahu sesuatu terhapus padahal tidak ada.
		if ( ! $this->Forum_model->soft_delete_diskusi($id) || $this->db->affected_rows() !== 1) {
			$this->session->set_flashdata('error', 'Diskusi tidak ditemukan atau sudah terhapus.');
			redirect('Umum/forum');
			return;
		}
		$this->session->set_flashdata('success', 'Diskusi berhasil dihapus.');
		redirect('Umum/forum');
	}

	public function delete_komentar() {
		$this->_load_forum();
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
		$items = $this->_get_tapera_data();

		// Hanya direktori resmi yang boleh jadi dasar "tersertifikasi" — sama
		// dengan Pengembang::sertifikasi(). Dulu di sini SELURUH srp2_registrations
		// dibaca tanpa filter status, lalu kolom `nib` dipakai sebagai penanda:
		// draft yang belum pernah dikirim pun tampil "Terdata" di halaman publik.
		// Roadmap T0 butir 2.
		$nama_tersertifikasi = [];
		if ($this->db->table_exists('srp2_certified_developers')) {
			$rows = $this->db->select('nama_perusahaan')->where('status_aktif', 1)
				->get('srp2_certified_developers')->result_array();
			foreach ($rows as $r) { $nama_tersertifikasi[strtolower($r['nama_perusahaan'])] = TRUE; }
		}

		$developers = array_map(function($item) use ($nama_tersertifikasi) {
			$pengembang_nama = $item['pengembang']['nama'] ?? '-';
			$sp2_tersertifikasi = $pengembang_nama !== '-'
				&& isset($nama_tersertifikasi[strtolower($pengembang_nama)]);

			return [
				'nama_perumahan' => $item['namaPerumahan'] ?? '-',
				'pengembang'     => $pengembang_nama,
				'asosiasi'       => $item['pengembang']['asosiasi'] ?? '-',
				'kabupaten'      => $item['wilayah']['kabupaten'] ?? '-',
				'telepon'        => $item['kantorPemasaran'][0]['noTelp'] ?? '-',
				'email'          => $item['kantorPemasaran'][0]['email'] ?? '-',
				'sp2_tersertifikasi' => $sp2_tersertifikasi,
			];
		}, $items);

		$datacontent['developers'] = $developers;
		$data['content'] = $this->load->view('pages/pengembang/list_pengembang', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	/**
	 * Helper: ambil data dari API Tapera (dengan file cache).
	 * Return array of raw items dari API.
	 */
	private function _get_tapera_data() {
		$apiUrl     = "https://sikumbang.tapera.go.id/ajax/lokasi/search?selectedSearch=wilayah&skalaPerumahan=semua&kodeWilayah=33&sort=terbaru&searchBy=nama-perumahan&page=1&limit=500";
		$cache_file = APPPATH . 'cache/sikumbang_pengembang_full.json';
		$cache_time = 86400; // 24 jam
		$response   = null;

		if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
			$response = file_get_contents($cache_file);
		} else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $apiUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
				'Referer: https://sikumbang.tapera.go.id/'
			]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			$response = curl_exec($ch);
			$err      = curl_error($ch);
			curl_close($ch);

			if (!$err && $response) {
				@file_put_contents($cache_file, $response);
			}
		}

		if (!$response) return [];
		$decoded = json_decode($response, true);
		return $decoded['data'] ?? [];
	}

	public function detail_pengembang($nama = '') {
		// Ambil nama dari query string (?nama=...) agar aman dari disallowed chars
		$nama = $this->input->get('nama') ? urldecode($this->input->get('nama')) : urldecode($nama);
		$nama = trim($nama);

		if (empty($nama)) {
			redirect('Umum/pengembang');
			return;
		}

		// Cari semua perumahan dari cache Tapera milik pengembang ini
		$items       = $this->_get_tapera_data();
		$tapera_data = [];
		foreach ($items as $item) {
			$dev_nama = $item['pengembang']['nama'] ?? '';
			if (strtolower(trim($dev_nama)) === strtolower($nama)) {
				$tapera_data[] = $item;
			}
		}

		// Ambil info pengembang dari item pertama yang cocok
		$info_pengembang = [];
		if (!empty($tapera_data)) {
			$first = $tapera_data[0];
			$info_pengembang = [
				'nama'     => $first['pengembang']['nama']      ?? $nama,
				'asosiasi' => $first['pengembang']['asosiasi']  ?? '-',
				'telepon'  => $first['kantorPemasaran'][0]['noTelp']  ?? '-',
				'email'    => $first['kantorPemasaran'][0]['email']   ?? '-',
				'alamat'   => $first['kantorPemasaran'][0]['alamat']  ?? '-',
				'website'  => $first['pengembang']['website']   ?? '-',
			];
		}

		// Cap "Terverifikasi SRP2" HANYA boleh dari direktori resmi pengembang
		// bersertifikat — sumber yang SAMA dengan Pengembang::sertifikasi().
		//
		// Dulu di sini `get_where('srp2_registrations', ['nama_perusahaan' => $nama])`
		// TANPA filter status: draft yang belum pernah dikirim pun ikut dicap
		// terverifikasi di halaman publik, dan siapa pun bisa mendaftar memakai
		// nama perusahaan orang lain lalu mendapat cap itu. Roadmap T0 butir 2.
		$local_data = [];
		if ($this->db->table_exists('srp2_certified_developers')) {
			$local_data = $this->db->get_where('srp2_certified_developers', [
				'nama_perusahaan' => $nama,
				'status_aktif'    => 1,
			])->row_array();
		}

		$datacontent['nama_pengembang'] = $nama;
		$datacontent['tapera_data']     = $tapera_data;       // semua perumahan dari pengembang ini
		$datacontent['info_pengembang'] = $info_pengembang;   // info kontak pengembang
		$datacontent['local_data']      = $local_data;        // data srp2 jika ada

		$data['content'] = $this->load->view('pages/pengembang/detail_pengembang', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}


}
