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

	/**
	 * SATU sumber "apakah ini admin" untuk seluruh area forum/konsultasi -
	 * dipakai `_check_admin()` (gerbang keras) DAN pengecekan lunak di
	 * forum()/detail()/balas_aksi()/toggle_like()/report_komentar().
	 *
	 * Menggantikan `in_array($role, ['admin','staff','Petugas Disperakim'])`
	 * yang tersebar di berkas ini - tiga peran itu TIDAK SATU PUN terdaftar
	 * sebagai peran resmi di `usr_users.role` selain 'admin' (lihat
	 * peringatan yang sama di Admin_Konsultasi.php, ditulis 14 Agt 2026 saat
	 * meja janji temu dibuat, tapi berkas INI - sumber aslinya - belum ikut
	 * dibetulkan sampai sekarang).
	 */
	private function _peran_admin()
	{
		return $this->session->userdata('is_logged') === TRUE
			&& $this->session->userdata('role') === 'admin';
	}

	/**
	 * TRUE kalau akun yang sedang login boleh mengakses diskusi $id_diskusi -
	 * pemiliknya, atau admin. SATU tempat dipakai `balas_aksi()`,
	 * `toggle_like()`, `report_komentar()` supaya syarat kepemilikannya tidak
	 * bisa diam-diam menyimpang antar endpoint (kekhawatiran yang sama
	 * mendasari komentar `ajukan_janji_temu()` soal tombol vs server: aturan
	 * di satu tempat harus SAMA PERSIS dengan yang ditegakkan di tempat lain).
	 *
	 * FALSE untuk diskusi yang tidak ada/sudah terhapus - dipanggilnya juga
	 * jadi validasi keberadaan sekaligus, bukan cuma kepemilikan.
	 */
	private function _boleh_akses_diskusi($id_diskusi, $user_id)
	{
		if ($this->_peran_admin()) { return TRUE; }
		$topik = $this->Forum_model->get_diskusi_by_id($id_diskusi);
		return $topik && (int) ($topik['user_id'] ?? 0) === (int) $user_id;
	}

	public function index()
	{
		
	}

	public function housing()
	{
		// Dulu merender mockup housing_carrier1 yang form-nya action="#" -
		// submit-nya tidak ke mana-mana. Wizard pembiayaan yang sungguhan
		// sudah ada di Program::solusi_pembiayaan(). Redirect supaya
		// bookmark/link lama tidak 404, pola yang sama dengan form_aduan().
		redirect('solusi_pembiayaan');
	}

	// S9 - `info_rumah()` DICABUT 29 Jul 2026 bersama view-nya.
	// Halaman itu me-`include "layout/head.php"` yang tidak pernah ada di
	// `views/pages/umum/`, jadi ia membalas 200 sambil memuat "A PHP Error"
	// dan path absolut server - diverifikasi runtime, bukan dibaca dari kode.
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

        /* Sama persis dengan Index::sebaran() - lihat komentar di sana untuk
           alasan pencarian diberi berkas cache sendiri. */
        $is_searching = ($keyword != '' || $sort != 'terbaru');
        $cache_file = $is_searching
            ? APPPATH . 'cache/sikumbang_sebaran_' . md5($full_url) . '.json'
            : APPPATH . 'cache/sikumbang_sebaran_jateng.json';

        list($datacontent['results'], ) = sikumbang_data($full_url, $cache_file, 86400);
		$data['content'] = $this->load->view('pages/data_spasial/sebaran', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}

	public function aduan()
	{
		/* Permintaan user 16 Agt 2026: kirim aduan WAJIB login - membalik
		   keputusan lama yang sengaja membiarkan tamu mengirim dengan
		   user_id NULL (lihat komentar yang masih ada di simpan_aduan()
		   sebelum diperbaiki). gerbang_login() otomatis mengingat halaman
		   ini lewat ingat_halaman_asal(), jadi begitu login selesai orang
		   kembali langsung ke sini (bukan ke beranda) - pola yang sama
		   dipakai papan_aduan() di bawah. */
		if ( ! $this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk mengirim aduan.');
			$this->gerbang_login();
			return;
		}

		$datacontent['judul'] ='';
		$datacontent['nama_default']  = $this->session->userdata('name') ?: '';
		$datacontent['email_default'] = $this->session->userdata('email') ?: '';
		$this->render('pages/umum/aduan', $datacontent);
	}

	public function simpan_aduan()
	{
		if ($this->input->method() !== 'post') {
			show_404();
		}

		/* Wajib login - pola sama dengan gerbang di aduan() (halaman
		   formnya). Endpoint POST ini dipertahankan sendiri, bukan
		   diandalkan pada form yang menyembunyikan tombolnya, karena bisa
		   dipanggil langsung tanpa lewat halaman itu sama sekali. */
		if ( ! $this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk mengirim aduan.');
			$this->gerbang_login();
			return;
		}

		$this->load->model('Aduan_model');

		$this->form_validation->set_rules('nama', 'Nama', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]');
		$this->form_validation->set_rules('judul', 'Judul', 'required|trim|max_length[150]');
		$this->form_validation->set_rules('pesan', 'Pesan', 'required|trim|max_length[2000]');
		// TIDAK ADA rule `bidang` - dan itu bukan kelalaian. Pelapor tidak lagi
		// memilih bidang (revisi dinas 3 Agt 2026); nilainya ditetapkan superadmin
		// lewat Admin_Aduan::triase(). Menerima `bidang` dari POST di sini berarti
		// siapa pun bisa merutekan aduannya sendiri ke bidang mana pun dengan satu
		// field tersembunyi - gerbang triase yang dilewati lewat pintu belakang.
		if ($this->form_validation->run() === FALSE) {
			$this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
			redirect('umum/aduan');
			return;
		}

		$nama  = $this->input->post('nama', TRUE);
		$email = $this->input->post('email', TRUE);
		$judul = $this->input->post('judul', TRUE);
		$pesan = $this->input->post('pesan', TRUE);

		// user_id dari sesi (anti-IDOR), bukan dari input. Selalu terisi
		// sejak gerbang login di atas - tamu tidak pernah sampai baris ini.
		$user_id = $this->get_user_id();

		// Baris dibuat DULU supaya lampirannya punya folder pemilik sendiri
		// (private_uploads/aduan/{id}/), pola yang sama dengan dokumen SRP2.
		$id = $this->Aduan_model->create([
			'user_id'  => $user_id,
			'nama'     => $nama,
			'email'    => $email,
			'judul'    => $judul,
			// NULL = masuk antrean triase. Bukan sentinel 'umum' seperti dulu:
			// NULL tidak cocok dengan WHERE bidang mana pun, jadi aduan yang
			// belum dirutekan otomatis tidak nyangkut di meja siapa pun tanpa
			// satu baris kode penjaga. Lihat migrasi 20260701000034.
			'bidang'   => NULL,
			'pesan'    => $pesan,
			'lampiran' => NULL,
		]);

		if ( ! $id) {
			$this->session->set_flashdata('error', 'Gagal menyimpan aduan. Silakan coba lagi.');
			redirect('umum/aduan');
			return;
		}

		// Tidak menjanjikan bidang tujuan - belum ada, dan menyebut satu nama di
		// sini akan jadi janji yang bisa diingkari triase.
		$pesan_sukses = 'Aduan Anda berhasil dikirim. Kami akan memeriksa dan meneruskannya ke bidang yang menangani.';

		// Lampiran disimpan di luar webroot dan hanya bisa dibuka lewat endpoint
		// ber-guard (Admin_Bidang/Admin_Aduan) - dulu ditaruh di .assets/uploads/
		// yang bisa diakses HTTP langsung. Kalau lampirannya gagal, aduannya
		// TETAP tersimpan; user diberi tahu apa adanya, bukan dibatalkan diam-diam.
		$galat_lampiran = NULL;
		$nama_lampiran = $this->store_private_upload('lampiran', 'aduan', $id, $galat_lampiran);
		if ($nama_lampiran) {
			$this->db->where('id', $id)->update('aduan', ['lampiran' => $nama_lampiran]);
		} elseif ($galat_lampiran) {
			$pesan_sukses .= ' Namun lampiran gagal diunggah (' . $galat_lampiran . ') - silakan kirim susulan bila perlu.';
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

	/**
	 * Papan aduan - daftar aduan yang masuk beserta jawabannya, terbuka untuk
	 * SEMUA pengguna yang sudah login (keputusan dinas 3 Agt 2026: aduan
	 * "bergaya forum", supaya orang bisa melihat isunya sudah pernah diangkat
	 * dan sudah dijawab apa).
	 *
	 * WAJIB LOGIN, dan gerbangnya di sini - bukan sekadar tautannya
	 * disembunyikan di halaman aduan. Aduan bukan konten publik.
	 *
	 * YANG TIDAK PERNAH DI-SELECT: `pesan`, `email`, `lampiran`. Bukan
	 * "tidak dirender" - TIDAK DIAMBIL sama sekali, jadi tidak ada di variabel
	 * mana pun yang bisa ikut terbawa oleh view berikutnya, dump debug, atau
	 * satu baris tampilan yang ditambahkan setahun lagi. Isi aduan bisa memuat
	 * alamat rumah, sengketa tanah, dan nama tetangga; yang dibagikan hanya
	 * JUDUL dan JAWABAN DINAS.
	 *
	 * Nama pelapor disamarkan jadi inisial DI CONTROLLER, lalu nama aslinya
	 * dibuang dari baris sebelum sampai ke view - sama alasannya.
	 *
	 * TANPA kotak cari. Pencarian bebas atas daftar yang identitasnya
	 * disamarkan adalah alat pembuka samaran: "nama tetangga saya" dicoba satu
	 * per satu sampai ada yang cocok. Yang tidak bisa dicari tidak bisa
	 * dipancing.
	 */
	public function papan_aduan()
	{
		if ( ! $this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu untuk membuka papan aduan.');
			$this->gerbang_login();
			return;
		}

		$this->load->model('Aduan_model');

		$per_hal = 20;
		$hal = max(1, (int) $this->input->get('hal'));
		$total = (int) $this->db->count_all('aduan');

		$rows = $this->db->select('id, nama, judul, bidang, status, catatan_admin, created_at')
			->order_by('created_at', 'DESC')
			->limit($per_hal, ($hal - 1) * $per_hal)
			->get('aduan')->result();

		foreach ($rows as $r) {
			$r->inisial = $this->inisial_nama($r->nama);
			unset($r->nama);
			// NULL dibaca sebagai "belum ditriase", bukan dikarang jadi nama
			// bidang. Pelapor yang melihat "Bidang Perumahan" untuk aduan yang
			// belum dirutekan akan mengira sudah ada yang memegangnya.
			$r->bidang_label = $r->bidang ? $this->Aduan_model->bidang_label($r->bidang) : NULL;
			unset($r->bidang);
		}

		$datacontent['judul']    = '';
		$datacontent['rows']     = $rows;
		$datacontent['hal']      = $hal;
		$datacontent['hal_akhir'] = max(1, (int) ceil($total / $per_hal));
		$datacontent['total']    = $total;
		$this->render('pages/umum/papan_aduan', $datacontent);
	}

	/** "Siti Nur Aisyah" -> "S. N. A." - cukup untuk membedakan, tidak cukup untuk mengenali. */
	private function inisial_nama($nama)
	{
		$potong = preg_split('/\s+/', trim((string) $nama), -1, PREG_SPLIT_NO_EMPTY);
		if ( ! $potong) { return 'A.'; }
		return implode(' ', array_map(
			static function ($k) { return mb_strtoupper(mb_substr($k, 0, 1)) . '.'; },
			array_slice($potong, 0, 3)
		));
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
		$datacontent['search']         = $search;
		$datacontent['kategori_aktif'] = $kategori;

		/* PRIVASI KONSULTASI - permintaan user 15 Agt 2026: "semua orang yg
		   konsultasi hanya bisa dilihat oleh admin". Sebelum ini SATU daftar
		   yang sama (get_all_diskusi() tanpa batasan) dikirim ke SIAPA PUN
		   yang membuka /Umum/forum - termasuk tamu anonim - jadi konsultasi
		   warga A terbaca warga B begitu saja. Sekarang:
		     - anonim  : tidak melihat satu topik pun (tidak ada "milik siapa"
		                 untuk anonim) - cuma ajakan masuk, yang memang sudah
		                 ada di view ini sejak awal.
		     - warga   : HANYA topiknya sendiri (`$user_id` diisi).
		     - admin   : SEMUA topik (`$user_id` NULL) - inilah "hanya bisa
		                 dilihat admin" yang dimaksud.
		   Aturan yang SAMA PERSIS ditegakkan lagi di detail()/balas_aksi()/
		   toggle_like()/report_komentar() - daftar ini cuma menentukan apa
		   yang TERLIHAT, bukan satu-satunya penjaga; seseorang yang menebak
		   ID topik langsung tetap ditolak di sana. */
		$datacontent['is_logged'] = $this->is_logged_in();
		$datacontent['is_admin']  = $this->_peran_admin();
		if ($datacontent['is_logged']) {
			$user_id_pemilik = $datacontent['is_admin'] ? NULL : (int) $this->get_user_id();
			$datacontent['diskusi'] = $this->Forum_model->get_all_diskusi($search, $kategori, $user_id_pemilik);
		} else {
			$datacontent['diskusi'] = [];
		}

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
			$this->gerbang_login();
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
			$this->session->set_flashdata('error', 'Judul topik harus antara 10-200 karakter.');
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

		// INSERT - nama & email diambil dari session
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

		/* PRIVASI KONSULTASI 15 Agt 2026 - lihat komentar panjang di forum().
		   Anonim -> gerbang_login() (bukan show_404() langsung): dia BOLEH
		   jadi pemiliknya sendiri yang sesinya sudah habis, dan
		   ingat_halaman_asal() otomatis membawanya balik ke sini sesudah
		   masuk. Sudah login TAPI bukan pemilik & bukan admin -> 404, "404
		   bukan 403" - pola sama dengan gerbang janji_temu di bawah, topik
		   orang lain tidak perlu dikonfirmasi keberadaannya. */
		if ( ! $this->is_logged_in()) {
			$this->gerbang_login();
			return;
		}
		$user_id  = (int) $this->get_user_id();
		$is_admin = $this->_peran_admin();
		if ( ! $is_admin && (int) ($datacontent['topik']['user_id'] ?? 0) !== $user_id) {
			show_404();
		}
		$datacontent['is_admin'] = $is_admin;

		// Increment view count
		$this->Forum_model->increment_view($id);
		$datacontent['topik']['view_count'] = ($datacontent['topik']['view_count'] ?? 0) + 1;

		$datacontent['komentar'] = $this->Forum_model->get_komentar_by_diskusi($id);

		// User likes status (jika login)
		$datacontent['user_likes'] = $this->Forum_model->get_user_likes($user_id, $id);

		/**
		 * Panel janji temu HANYA untuk pemilik topik, dan datanya hanya diambil
		 * untuk pemilik. Bukan sekadar tidak dirender: `alasan` dan
		 * `catatan_user` berisi kenapa seseorang merasa perlu bertemu petugas.
		 * Sejak gerbang privasi di atas, admin juga bisa membuka halaman ini
		 * (untuk topik warga lain) TANPA jadi "pemilik" - panel ini memang
		 * harus tetap tersembunyi baginya, dia meninjau lewat Admin_Konsultasi.
		 */
		$datacontent['saya_pemilik'] = FALSE;
		$datacontent['janji']        = NULL;
		$datacontent['boleh_ajukan'] = FALSE;
		if ($this->is_logged_in() && (int) ($datacontent['topik']['user_id'] ?? 0) === (int) $this->get_user_id()) {
			$this->load->model('Janji_temu_model');
			$datacontent['saya_pemilik'] = TRUE;
			$datacontent['janji']        = $this->Janji_temu_model->hidup_untuk_topik($id);
			// Aturan di sini WAJIB sama persis dengan yang ditegakkan server di
			// `ajukan_janji_temu()`. Kalau tombolnya muncul memakai syarat yang
			// lebih longgar, yang menekannya ditolak tanpa tahu sebabnya; kalau
			// lebih ketat, fiturnya tidak pernah terlihat ada.
			//
			// `count($komentar) > 0` DICABUT 5 Agt 2026 bersama pasangannya di
			// server (butir E1). Justru syarat inilah yang membuat dinas membuka
			// menu Konsultasi Terjadwal dan menyimpulkan "belum ada pilihan bikin
			// jadwalnya" - tombolnya memang tidak pernah mereka lihat.
			$datacontent['boleh_ajukan'] = ! $datacontent['janji']
				&& ($datacontent['topik']['status'] ?? 'open') !== 'closed';
		}

		$data['content'] = $this->load->view('pages/umum/forum_detail', $datacontent, true);
		$this->load->view('layouts/main', $data);
	}

	// =========================================================
	// FORUM: Janji temu konsultasi (LOGIN REQUIRED, PEMILIK TOPIK)
	// =========================================================

	/**
	 * Ajukan tatap muka untuk satu topik.
	 *
	 * Halaman `Umum/forum` sudah berjudul "Konsultasi Terjadwal" dan memajang
	 * tiga kartu alur sejak lama - "Admin meninjau", "Agenda ditentukan",
	 * "Waktu konsultasi disampaikan setelah ditinjau" - tanpa satu pun
	 * mekanisme di belakangnya. Ini mekanismenya.
	 *
	 * Empat syarat, masing-masing menutup hal berbeda:
	 *  1. Pemilik topik saja. Diambil dari SESI lalu dicocokkan ke baris, bukan
	 *     dari POST.
	 *  2. Topik harus sudah DITANGGAPI. Terjemahan aturan dinas "ruang
	 *     konsultasi dulu": pertanyaan yang selesai di forum tidak perlu
	 *     menghabiskan slot tatap muka. Ditegakkan di server, bukan hanya
	 *     dengan menyembunyikan tombolnya.
	 *  3. Satu janji hidup per topik. Tanpa ini satu topik bisa melahirkan
	 *     antrean permintaan yang semuanya menunggu petugas yang sama.
	 *  4. Batas laju per HARI (`janji_temu_ajukan`). Yang dibatasi bukan spam,
	 *     melainkan waktu petugas yang nyata.
	 */
	public function ajukan_janji_temu()
	{
		$this->_load_forum();
		if ($this->input->method(TRUE) !== 'POST') { show_404(); }
		if ( ! $this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
			$this->gerbang_login();
			return;
		}

		$this->load->model('Janji_temu_model');
		$id_diskusi = (int) $this->input->post('id_diskusi');
		$user_id    = (int) $this->get_user_id();

		$topik = $this->Forum_model->get_diskusi_by_id($id_diskusi);
		// 404, bukan 403: topik orang lain tidak perlu dikonfirmasi keberadaannya.
		if (empty($topik) || (int) ($topik['user_id'] ?? 0) !== $user_id) { show_404(); }

		$alasan = sanitize_forum_input($this->input->post('alasan'));
		if (mb_strlen((string) $alasan) < 20) {
			$this->session->set_flashdata('error', 'Jelaskan dulu kenapa perlu tatap muka, minimal 20 karakter.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}
		$cek = contains_profanity($alasan);
		if ($cek['found']) {
			$this->session->set_flashdata('error', 'Alasan Anda mengandung kata-kata yang tidak diperbolehkan.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		if (($topik['status'] ?? 'open') === 'closed') {
			$this->session->set_flashdata('error', 'Topik ini sudah ditutup.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		/* SYARAT "topik harus ditanggapi petugas dulu" DICABUT 5 Agt 2026
		   (revisi dinas butir E1). Semula ia menerjemahkan gagasan "berkonsultasi
		   di forum dulu, tatap muka belakangan" - tapi di lapangan justru itu
		   yang membuat dinas melihat menu Konsultasi Terjadwal dan menyimpulkan
		   "belum ada pilihan bikin jadwalnya": warga yang topiknya belum
		   ditanggapi tidak pernah melihat tombolnya sama sekali.

		   Yang TIDAK ikut dilonggarkan, dan itu disengaja: pemilik topik saja
		   (di atas), topik tertutup ditolak (di atas), satu pengajuan hidup per
		   topik (di bawah), dan batas laju `janji_temu_ajukan`. Melepas satu
		   syarat bukan alasan melepas sisanya. */

		if ($this->Janji_temu_model->hidup_untuk_topik($id_diskusi)) {
			$this->session->set_flashdata('error', 'Sudah ada pengajuan janji temu yang berjalan untuk topik ini.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		$rate = $this->rate_limit_consume('janji_temu_ajukan', ['account_id' => $user_id]);
		if (empty($rate['success']) || empty($rate['allowed'])) {
			$this->rate_limit_reject($rate,
				'Terlalu banyak pengajuan janji temu hari ini. Coba lagi besok.');
			return;
		}

		$baru = $this->Janji_temu_model->buat([
			'id_diskusi' => $id_diskusi,
			'user_id'    => $user_id,
			'alasan'     => $alasan,
		]);
		if ( ! $baru) {
			$this->session->set_flashdata('error', 'Pengajuan belum tersimpan. Coba lagi.');
			redirect('Umum/detail/' . $id_diskusi);
			return;
		}

		$this->catat_audit('janji_temu_diajukan',
			'Janji temu #' . $baru . ' diajukan untuk topik #' . $id_diskusi,
			'forum_janji_temu', $baru, ['id_diskusi' => $id_diskusi]);

		$this->session->set_flashdata('success',
			'Pengajuan janji temu terkirim. Petugas akan menawarkan waktu dan tempatnya.');
		redirect('Umum/detail/' . $id_diskusi);
	}

	/**
	 * Jawaban warga atas tawaran petugas: setujui / minta jadwal lain / batalkan.
	 *
	 * Ketiganya lewat SATU endpoint karena ketiganya perpindahan keadaan yang
	 * sama bentuknya, dan aturan sahnya dibaca dari `Janji_temu_model::ALUR` -
	 * bukan ditulis ulang di sini. Endpoint terpisah per aksi berarti tiga
	 * salinan whitelist yang akan berselisih.
	 *
	 * `user_id` ikut di WHERE (anti-IDOR), dan status asalnya juga: menekan
	 * "Setujui" dua kali tidak menghasilkan dua kali apa pun.
	 */
	public function respon_janji_temu($id = NULL)
	{
		// Helper forum dimuat lazy di controller ini, dan `sanitize_forum_input()`
		// dipakai di bawah. Tanpa baris ini method-nya fatal error - bukan
		// menolak, bukan gagal: 500 sebelum satu pun pemeriksaan berjalan.
		$this->_load_forum();
		if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
		if ( ! $this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
			$this->gerbang_login();
			return;
		}

		$this->load->model('Janji_temu_model');
		$id      = (int) $id;
		$user_id = (int) $this->get_user_id();

		$row = $this->Janji_temu_model->ambil($id);
		if ( ! $row || (int) $row->user_id !== $user_id) { show_404(); }

		$aksi = (string) $this->input->post('aksi');
		$peta = ['setujui' => 'disetujui', 'jadwal_lain' => 'diajukan', 'batalkan' => 'dibatalkan'];
		$ke = $peta[$aksi] ?? NULL;

		if ($ke === NULL || ! $this->Janji_temu_model->boleh($row->status, $ke, 'pemilik')) {
			$this->session->set_flashdata('error', 'Tindakan itu tidak berlaku untuk keadaan pengajuan ini.');
			redirect('Umum/detail/' . $row->id_diskusi);
			return;
		}

		$set = ['catatan_user' => sanitize_forum_input($this->input->post('catatan_user')) ?: NULL];
		// Minta jadwal lain = tawarannya gugur. Membiarkan jadwal lama terisi
		// membuat baris itu terbaca seolah masih punya agenda yang disepakati.
		if ($ke === 'diajukan') { $set['jadwal_mulai'] = NULL; $set['lokasi'] = NULL; }

		$ok = $this->Janji_temu_model->transisi($id, $row->status, $ke, $set, ['user_id' => $user_id]);
		if ( ! $ok) {
			$this->session->set_flashdata('error', 'Pengajuan sudah berubah keadaannya. Muat ulang halaman.');
			redirect('Umum/detail/' . $row->id_diskusi);
			return;
		}

		$this->catat_audit('janji_temu_' . $ke,
			'Janji temu #' . $id . ' menjadi ' . $ke . ' oleh pemohon',
			'forum_janji_temu', $id, ['dari' => $row->status, 'ke' => $ke]);

		$pesan = [
			'disetujui'  => 'Jadwal disetujui. Sampai bertemu di lokasi yang disepakati.',
			'diajukan'   => 'Permintaan jadwal lain terkirim ke petugas.',
			'dibatalkan' => 'Pengajuan janji temu dibatalkan.',
		];
		$this->session->set_flashdata('success', $pesan[$ke]);
		redirect('Umum/detail/' . $row->id_diskusi);
	}

	// =========================================================
	// FORUM: Balas/Komentar (LOGIN REQUIRED)
	// =========================================================

	public function balas_aksi() {
		$this->_load_forum();
		// AUTH GATE
		if (!$this->is_logged_in()) {
			$this->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
			$this->gerbang_login();
			return;
		}

		$user_id     = (int) $this->get_user_id();
		$id_diskusi  = (int) $this->input->post('id_diskusi');
		$isi_komentar = sanitize_forum_input($this->input->post('isi_komentar'));

		// VALIDASI
		if (empty($id_diskusi) || empty($isi_komentar)) {
			$this->session->set_flashdata('error', 'Semua field wajib diisi.');
			redirect('Umum/forum');
			return;
		}

		/* PRIVASI KONSULTASI 15 Agt 2026 - gerbang forum()/detail() menahan
		   yang lewat browser, tapi endpoint POST ini sendiri bisa dipanggil
		   langsung dengan id_diskusi berapa pun oleh warga lain yang sudah
		   login. Tanpa penjagaan di sini, "hanya pemilik & admin yang lihat"
		   masih bisa dilewati - orangnya tidak BISA MELIHAT topiknya, tapi
		   tetap BISA MEMBALAS-nya. 404, bukan 403, pola sama dengan detail(). */
		if ( ! $this->_boleh_akses_diskusi($id_diskusi, $user_id)) { show_404(); }

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

		// ROLE: ditentukan di backend, dari _peran_admin() - satu sumber
		// yang sama dipakai gerbang privasi di atas, bukan daftar peran
		// terpisah yang bisa menyimpang darinya.
		$role = $this->_peran_admin() ? 'Petugas Disperakim' : 'Warga';

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
		// menentukan: U2 ledger-only - laporan dicatat, visibilitas TIDAK
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
	 * B3 - dulu endpoint ini ANONIM: siapa pun bisa memanggilnya berulang kali
	 * dan menyensor komentar orang lain sendirian. Guard-nya sudah ada beberapa
	 * baris di bawah, di berkas yang sama (`toggle_like()`), tinggal disalin.
	 *
	 * CSRF bukan penutupnya (B12): `csrf_regenerate` FALSE + double-submit
	 * berarti tokennya bisa diambil anonim lewat satu GET. Yang menutup adalah
	 * guard login + rate limit + dedup di ledger.
	 *
	 * `auto_hide_reported()` SENGAJA tidak lagi dipanggil - U2 ledger-only,
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

		/* PRIVASI KONSULTASI 15 Agt 2026 - lihat komentar panjang di forum().
		   Melaporkan komentar mensyaratkan sudah MELIHATNYA; orang yang bukan
		   pemilik topik & bukan admin tidak berhak atas keduanya sekaligus.
		   404 (via id diskusi yang tidak resolve) diperlakukan sama dengan
		   "bukan pemilik" - respons JSON generik di bawah, tidak membedakan
		   "komentar tidak ada" dari "bukan milik Anda". */
		$user_id    = (int) $this->get_user_id();
		$id_diskusi = $this->Forum_model->get_diskusi_id_dari_komentar($id);
		if ( ! $id_diskusi || ! $this->_boleh_akses_diskusi($id_diskusi, $user_id)) {
			echo json_encode(['status' => 'error', 'message' => 'Komentar tidak ditemukan.']);
			return;
		}

		$rate = $this->rate_limit_consume('forum_report', [
			'account_id' => $user_id,
			'object_id'  => $id,
		]);
		if (empty($rate['success']) || empty($rate['allowed'])) {
			$this->rate_limit_reject($rate,
				'Terlalu banyak laporan dalam waktu singkat. Coba lagi sebentar.', TRUE);
			return;
		}

		$hasil = $this->Forum_model->report_komentar($id, $user_id);
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
		$target_id   = (int) $this->input->post('id');

		if (!in_array($target_type, ['diskusi', 'komentar']) || empty($target_id)) {
			echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
			return;
		}

		/* PRIVASI KONSULTASI 15 Agt 2026 - lihat komentar panjang di forum().
		   $target_type menentukan cara menemukan diskusi induknya: kalau
		   yang disukai topiknya sendiri, id-nya sudah id diskusi; kalau
		   komentar, ditelusuri dulu induknya. */
		$user_id    = (int) $this->get_user_id();
		$id_diskusi = $target_type === 'diskusi'
			? $target_id
			: $this->Forum_model->get_diskusi_id_dari_komentar($target_id);
		if ( ! $id_diskusi || ! $this->_boleh_akses_diskusi($id_diskusi, $user_id)) {
			echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
			return;
		}

		$result = $this->Forum_model->toggle_like($user_id, $target_type, $target_id);
		echo json_encode(['status' => 'ok', 'action' => $result['action'], 'count' => $result['count']]);
	}

	// =========================================================
	// FORUM: Admin Moderasi (LOGIN + ROLE REQUIRED)
	// =========================================================

	private function _check_admin() {
		if ($this->session->userdata('is_logged') !== TRUE) {
			$this->gerbang_login();
			return false;
		}
		if ( ! $this->_peran_admin()) {
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

		// Hanya direktori resmi yang boleh jadi dasar "tersertifikasi" - sama
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

		/* Header `Referer` yang dulu dikirim di sini SENGAJA tidak diteruskan
		   ke helper. Diuji dari server production 26 Agt 2026: dengan dan
		   tanpa Referer, balasannya sama persis - HTTP 200, 211747 byte
		   keduanya. Jadi ia bukan syarat, cuma peninggalan; menambah
		   parameter header ke helper demi satu pemanggil yang ternyata tidak
		   membutuhkannya cuma menambah permukaan tanpa alasan. Kalau kelak
		   SIKUMBANG mulai mensyaratkannya, ukur dulu seperti ini sebelum
		   menambahkannya kembali. */
		list($baris, ) = sikumbang_data($apiUrl, $cache_file, 86400);
		return $baris;
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
		// bersertifikat - sumber yang SAMA dengan Pengembang::sertifikasi().
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
