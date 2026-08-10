<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pengembang extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'download']);
        $this->load->model('Buka_peta');
        $this->load->model('auth_model');
        $this->load->library('session');
        date_default_timezone_set('Asia/Jakarta');
    }

    public function sertifikasi() {
        $data['judul'] = '';
        $table = $this->db->table_exists('srp2_certified_developers') ? 'srp2_certified_developers' : 'srp2_registrations';
        $status = $table === 'srp2_certified_developers' ? ['status_aktif' => 1] : ['status_verifikasi' => 'Diterima'];
        $data['daftar_pengembang'] = $this->db->select('id, nama_perusahaan')->where($status)->order_by('nama_perusahaan', 'ASC')->get($table)->result();
        $this->render('pages/pengembang/sertifikasi', $data);
    }

    /**
     * Wizard SRP2 satu halaman: syarat -> masuk/daftar -> unggah dokumen -> kirim.
     * Semua langkah tetap di URL ini (state di Alpine), tidak pindah halaman.
     */
    public function syarat() {
        $is_logged = $this->is_logged_in();
        $role = $this->session->userdata('role');
        $is_pengembang = $is_logged && $role === 'pengembang';

        $registration_id = null;
        $uploaded_keys = [];
        $status_verifikasi = null;
        $catatan_admin = null;
        if ($is_pengembang) {
            // Keadaan pengajuan lewat satu pintu di Auth_model - sumber yang SAMA
            // dipakai cabang AJAX Auth::do_login(), supaya wizard yang dibuka
            // langsung dan wizard yang baru saja login menampilkan hal identik.
            // Draft ikut dibuat di sini kalau belum ada, termasuk untuk akun yang
            // rolenya di-set manual admin.
            $srp2 = $this->auth_model->srp2_state($this->get_user_id());
            if ($srp2) {
                $registration_id   = $srp2['registration_id'];
                $status_verifikasi = $srp2['status'];
                $catatan_admin     = $srp2['catatan_admin'];
                $uploaded_keys     = $srp2['uploaded_keys'];
            }
        }

        $this->render('pages/pengembang/syarat', [
            'judul'            => '',
            'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
            'is_logged'        => $is_logged,
            'is_pengembang'    => $is_pengembang,
            'wrong_role'       => $is_logged && !$is_pengembang,
            // Tujuan tombol "Ke Dashboard Saya" diturunkan dari registry per role,
            // bukan hardcode ke `akun` - lihat MY_Controller::dashboard_home().
            'dashboard_url'    => $is_logged ? $this->dashboard_home() : 'akun',
            // Role pengelola tidak perlu disuruh "daftar akun pengembang":
            // mereka justru sisi yang memverifikasi SRP2.
            'is_pengelola'     => $is_logged && in_array($role, ['admin', 'admin_kabkota', 'admin_bidang'], TRUE),
            'nama_user'        => $this->session->userdata('name') ?: $this->session->userdata('email'),
            'registration_id'  => $registration_id,
            'dokumen'          => $this->dokumen_persyaratan(),
            // Keterangan per formulir (revisi dinas 3 Agt 2026) - helper terpisah
            // supaya bentuk `dokumen` tidak berubah bagi empat pemakainya.
            'keterangan'       => $this->keterangan_persyaratan(),
            'uploaded_keys'    => $uploaded_keys,
            'status_verifikasi' => $status_verifikasi,
            'catatan_admin'    => $catatan_admin,
        ]);
    }

    /**
     * Diarsipkan - perannya sekarang diambil alih wizard di Pengembang/syarat
     * (satu halaman: syarat -> masuk/daftar -> unggah dokumen -> kirim).
     * Redirect dipertahankan supaya bookmark/tautan lama tidak jadi dead-end.
     */
    public function daftar() { redirect('Pengembang/syarat'); }

    /**
     * Diarsipkan - form manual 12 field digantikan wizard daftar cepat di
     * Pengembang/syarat. Redirect dipertahankan supaya bookmark/tautan lama
     * tidak jadi dead-end. Lihat archive/formulir_sertifikasi_12field.php.
     */
    public function formulir() { redirect('Pengembang/syarat'); }

    public function simpan() { redirect('Pengembang/syarat'); }

    public function profil($id = NULL) {
        if (!is_numeric($id)) show_404();
        if ($this->db->table_exists('srp2_certified_developers')) {
            /* KOLOM DISEBUT SATU PER SATU, dan itu bukan gaya penulisan.
               Sebelum migrasi 040 baris ini `SELECT *`, dan begitu NPWP
               ditambahkan ke tabel - terenkripsi sekalipun - ia IKUT TERKIRIM ke
               view publik tanpa ada yang mengubah baris ini. Butir 7 justru
               meminta NPWP hanya terlihat admin.

               Sidik pencarian (`npwp_lookup_hash`) sama berbahayanya: ia
               deterministik, jadi siapa pun yang bisa membacanya dapat menguji
               tebakan NPWP tanpa perlu memecahkan enkripsinya.

               Menambah kolom baru ke tabel ini WAJIB disertai keputusan sadar
               apakah ia masuk daftar di bawah. `SELECT *` mengambil keputusan
               itu diam-diam, dan selalu ke arah yang salah. */
            $data['pengembang'] = $this->db
                ->select('id, nama_perusahaan, alamat_kantor, website, instagram, sosmed_lainnya,'
                    . ' status_aktif, status_sertifikasi, kabupaten_id, asosiasi,'
                    . ' sertifikat_terbit, sertifikat_berakhir, created_at, updated_at')
                ->get_where('srp2_certified_developers', ['id' => (int) $id, 'status_aktif' => 1])->row();

            // Field yang kosong dilengkapi dari baris pengajuan lewat RELASI ID
            // (certified_developer_id), bukan pencocokan nama string.
            //
            // Pencocokan nama itu rapuh dua arah: putus begitu nama diedit di
            // salah satu sisi, dan bisa MENARIK data perusahaan lain yang
            // kebetulan bernama sama - nama tidak unique di srp2_registrations.
            // Migrasi 20260701000014 dibuat justru untuk menggantikannya;
            // menambah kolom tanpa mencabut jalur lama meninggalkan dua definisi
            // "perusahaan yang sama". Kedua tabel juga beda collation
            // (general_ci vs unicode_ci), jadi join namanya bahkan tidak sah.
            if ($data['pengembang'] && $this->db->table_exists('srp2_registrations')) {
                $registration = $this->db->get_where('srp2_registrations', [
                    'certified_developer_id' => (int) $id,
                    'status_verifikasi'      => 'Diterima',
                ])->row();
                if ($registration) foreach (['asosiasi', 'no_keanggotaan', 'alamat_kantor', 'instagram', 'website', 'sosmed_lainnya'] as $field) if (empty($data['pengembang']->$field)) $data['pengembang']->$field = $registration->$field ?? NULL;
            }
        } else $data['pengembang'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'status_verifikasi' => 'Diterima'])->row();
        if (!$data['pengembang']) show_404();
        $this->render('pages/pengembang/profil', $data);
    }

    /**
     * Diarsipkan 27 Jul 2026 - unggah dokumen SEPENUHNYA di wizard
     * (Pengembang/syarat), dari langkah 1 sampai kirim, tanpa pindah halaman.
     *
     * `mulai_unggah()` dan `dokumen()` adalah sisa era sebelum wizard: permukaan
     * unggah KEDUA untuk pekerjaan yang sama, dan itulah akar tiga temuan
     * sekaligus - penulisan DB setelah seluruh loop (berkas yatim), view yang
     * tidak sadar status, dan daftar yang selalu tampil kosong. Menghapus satu
     * jalur lebih sedikit kode daripada memperbaiki tiga hal di jalur yang
     * memang tidak dipakai.
     *
     * Redirect dipertahankan supaya bookmark/tautan lama tidak jadi dead-end.
     */
    public function mulai_unggah($document_key = NULL) { redirect('Pengembang/syarat'); }
    public function dokumen($id = NULL) { redirect('Pengembang/syarat'); }

    /**
     * Sajikan satu dokumen SRP2 ke PEMILIKNYA. Selama ini serve_private_file()
     * hanya dipakai sisi pengelola (Admin_Srp2::lihat_dokumen), sehingga pemohon
     * tidak punya cara memeriksa apa yang dulu dia kirim - dan itu membuat
     * "Minta Perbaikan" terasa buntu: admin menulis "Form 4 salah", pemohon
     * cuma melihat badge "Tersimpan" tanpa bisa membuka berkasnya.
     *
     * Anti-IDOR memakai pola yang sama dengan simpan_dokumen(): WHERE registrasi
     * DAN user_id dari sesi, sehingga pengembang lain tidak bisa membuka berkas
     * yang bukan miliknya.
     */
    public function lihat_dokumen_saya($id = NULL, $document_key = NULL) {
        if (!is_numeric($id) || empty($document_key) || !$this->akses_pengembang('Pengembang/syarat')) { show_404(); return; }
        $id = (int) $id;

        $milik = $this->db->get_where('srp2_registrations', ['id' => $id, 'user_id' => $this->get_user_id()])->row();
        if (!$milik) { show_404(); return; }

        $doc = $this->db->where(['registration_id' => $id, 'document_key' => $document_key])
            ->get('srp2_documents')->row();
        if (!$doc) { show_404(); return; }

        // Pemilik sah + ledger ada tapi FISIKNYA hilang → jangan 404 bisu.
        // Kasus nyata 29 Jul 2026: berkas reg 7 tersapu pembersih harness
        // ber-DB-sementara yang berbagi folder private_uploads (tabrakan id).
        // 404 tetap untuk bukan-pemilik/key salah (anti-IDOR), tapi pemilik
        // berhak tahu bedanya "tidak pernah ada" dan "tercatat namun hilang".
        $path = $this->private_upload_dir('srp2', $id) . basename((string) $doc->stored_name);
        if (!is_file($path)) {
            log_message('error', sprintf(
                'Berkas SRP2 tercatat di ledger tapi hilang dari disk: reg=%d key=%s stored=%s user=%d',
                $id, $document_key, $doc->stored_name, $this->get_user_id()
            ));
            // Bahasa manusia, bukan bahasa sistem: label dokumen yang dikenal
            // pemohon (bukan kunci mentah form_1), tanpa "tercatat"/"fisik"/
            // "penyimpanan". Detail teknisnya sudah aman di log ERROR di atas.
            $labels = $this->dokumen_persyaratan();
            $this->session->set_flashdata('error',
                'Maaf, dokumen "' . ($labels[$document_key] ?? $document_key) . '" sudah tidak tersedia dan tidak bisa dibuka. '
                . 'Silakan hubungi admin agar pengajuan Anda dibuka kembali, lalu unggah ulang dokumen tersebut.');
            redirect('Pengembang/syarat');
            return;
        }

        $this->serve_private_file('srp2', $id, $doc->stored_name, $doc->mime_type);
    }

    public function simpan_dokumen($id = NULL) {
        $is_ajax = $this->input->is_ajax_request();
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/syarat') || $this->input->method(TRUE) !== 'POST') { if (!$is_ajax) show_404(); return; }

        // NORMALISASI SEKALI di pintu masuk, sebelum $id dipakai untuk APA PUN.
        // Dulu guard memakai is_numeric(), query memakai (int), tapi path berkas
        // memakai $id MENTAH - dan private_upload_dir() membuang karakter non
        // alfanumerik, jadi "7.0" menjadi direktori "70". Hasilnya: baris DB
        // benar (registration_id=7) tapi berkasnya mendarat di srp2/70/, admin
        // melihat 404 untuk SETIAP dokumen, sementara hitungan 14/14 lolos.
        $id = (int) $id;

        // Anti-IDOR: WHERE user_id selalu dari sesi - pengembang lain tidak bisa menulis ke registrasi ini.
        $registration = $this->db->get_where('srp2_registrations', ['id' => $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration || !$this->db->table_exists('srp2_documents')) {
            if ($is_ajax) { $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan.'])); return; }
            show_404();
        }
        // Dokumen dikunci setelah dikirim ke admin - mencegah file diganti diam-diam
        // saat sedang/sudah ditinjau (Diterima pun tidak boleh diubah lagi).
        if (in_array($registration->status_verifikasi, ['Pending', 'Diterima'], TRUE)) {
            $message = 'Dokumen tidak bisa diubah saat status ' . $registration->status_verifikasi . '.';
            if ($is_ajax) { $this->output->set_status_header(409)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message])); return; }
            $this->session->set_flashdata('error', $message); redirect('Pengembang/syarat'); return;
        }
        // Pastikan akar private_uploads/ tertutup dari akses HTTP langsung.
        // "Di luar webroot" ternyata tidak selalu benar - tergantung posisi
        // aplikasi terhadap DocumentRoot; lihat catatan di method itu.
        $this->ensure_private_uploads_protected();
        // Lokasi akar dari helper private_upload (bisa diatur PRIVATE_UPLOADS_PATH
        // di .env) - jangan susun path sendiri di sini.
        $path = $this->private_upload_dir('srp2', $id);
        if (!is_dir($path)) mkdir($path, 0700, TRUE);
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

        $gagal = function ($message, $key = NULL) use ($is_ajax) {
            if ($is_ajax) {
                $this->output->set_content_type('application/json')->set_output(json_encode(
                    array_filter(['status' => 'error', 'document_key' => $key, 'message' => $message])));
                return;
            }
            $this->session->set_flashdata('error', $message); redirect('Pengembang/syarat');
        };

        // DUA TAHAP: validasi SEMUA dulu, baru pindahkan. Dulu validasi dan
        // move_uploaded_file() berada di loop yang sama, sehingga satu berkas
        // gagal validasi membuat berkas-berkas SEBELUMNYA sudah mendarat di disk
        // tanpa baris DB - yatim permanen yang tidak terjangkau pembersihan
        // apa pun. Dengan dua tahap, kegagalan validasi menyisakan NOL berkas.
        $siap = [];
        foreach ($this->dokumen_persyaratan() as $key => $label) {
            if (empty($_FILES[$key]['name'])) continue;
            $file = $_FILES[$key];
            // name/tmp_name berbentuk array = input dikirim sebagai multi-file.
            // Dulu ini menembus ke pathinfo() dan memicu TypeError 500 SEBELUM
            // pemeriksaan error mana pun sempat jalan.
            if (is_array($file['name']) || is_array($file['tmp_name'])) { return $gagal('Berkas ' . $label . ' tidak valid: kirim satu berkas per dokumen.', $key); }
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $mime = is_uploaded_file($file['tmp_name']) ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2097152 || !isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
                return $gagal('Berkas ' . $label . ' tidak valid. Gunakan PDF/JPG/PNG maksimal 2 MB.', $key);
            }
            $siap[$key] = ['file' => $file, 'ext' => $ext, 'mime' => $mime];
        }

        if (empty($siap)) { return $gagal('Tidak ada berkas yang dipilih.'); }

        // Nama berkas LAMA diambil sebelum ditimpa. db->replace() menghapus baris
        // lama beserta nama berkasnya, jadi kalau tidak dicatat dulu, berkas
        // fisiknya jadi mustahil ditemukan - dan _cleanup_owned_files() yang
        // menyapu berdasarkan nama di DB tidak akan pernah menemukannya lagi.
        // Konsekuensi UU PDP: akta/NPWP/laporan keuangan selamat dari hapus akun.
        $lama = [];
        foreach ($this->db->where('registration_id', $id)->where_in('document_key', array_keys($siap))
                     ->get('srp2_documents')->result() as $d) { $lama[$d->document_key] = $d->stored_name; }

        $stored = []; $baru_di_disk = [];
        foreach ($siap as $key => $s) {
            $name = bin2hex(random_bytes(16)) . '.' . $s['ext'];
            if (!move_uploaded_file($s['file']['tmp_name'], $path . $name)) {
                // Bersihkan yang sudah sempat mendarat di request INI, supaya
                // kegagalan di tengah tidak meninggalkan jejak.
                foreach ($baru_di_disk as $f) { @unlink($path . $f); }
                return $gagal('Berkas gagal disimpan.', $key);
            }
            $baru_di_disk[] = $name;
            $row = ['registration_id' => $id, 'document_key' => $key, 'original_name' => substr(basename($s['file']['name']), 0, 255), 'stored_name' => $name, 'mime_type' => $s['mime'], 'file_size' => (int) $s['file']['size']];
            // Baris DB ditulis DI DALAM loop, bukan sesudahnya - berkas yang
            // sudah pindah selalu punya barisnya.
            $this->db->replace('srp2_documents', $row);
            // Berkas lama dibuang SETELAH penggantinya tercatat, bukan sebelum.
            if (!empty($lama[$key]) && $lama[$key] !== $name) { @unlink($path . basename($lama[$key])); }
            $stored[] = $row;
        }

        if ($is_ajax) {
            $uploaded_count = $this->db->where('registration_id', $id)->count_all_results('srp2_documents');
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'success', 'document_key' => $stored[0]['document_key'], 'uploaded_count' => $uploaded_count,
            ]));
            return;
        }
        $this->session->set_flashdata('success', 'Dokumen berhasil diunggah.'); redirect('Pengembang/syarat');
    }

    public function kirim_pengajuan($id = NULL) {
        $is_ajax = $this->input->is_ajax_request();
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/syarat') || $this->input->method(TRUE) !== 'POST') { if (!$is_ajax) show_404(); return; }
        $registration = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration) {
            if ($is_ajax) { $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan.'])); return; }
            show_404();
        }
        if (in_array($registration->status_verifikasi, ['Pending', 'Diterima'], TRUE)) {
            $message = 'Pengajuan sudah dikirim dan berstatus ' . $registration->status_verifikasi . '.';
            if ($is_ajax) { $this->output->set_status_header(409)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message])); return; }
            $this->session->set_flashdata('error', $message); redirect('akun'); return;
        }
        $required = count($this->dokumen_persyaratan()); $uploaded = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
        if ($uploaded < $required) {
            $message = 'Lengkapi seluruh ' . $required . ' dokumen sebelum mengirim pengajuan.';
            if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message, 'uploaded_count' => $uploaded, 'required' => $required])); return; }
            $this->session->set_flashdata('error', $message); redirect('Pengembang/syarat'); return;
        }
        // GERBANG DI HULU. Dulu 14 dokumen adalah SATU-SATUNYA syarat kirim,
        // sehingga baris tanpa nama perusahaan - atau bernama sama persis dengan
        // pengembang yang sudah tersertifikasi - tetap lahir dan mendarat di meja
        // admin. Di sana ia mustahil disetujui: kolom nama di direktori publik
        // NOT NULL dan UNIQUE, jadi approve-nya gagal. Menambal sisi admin saja
        // hanya mengubah kegagalan senyap jadi kegagalan berisik; barisnya harus
        // tidak pernah lahir. Roadmap T1a butir 2.
        $nama = trim((string) $registration->nama_perusahaan);
        if ($nama === '') {
            $message = 'Nama perusahaan belum diisi. Lengkapi dulu di halaman Profil sebelum mengirim pengajuan.';
            if ($is_ajax) { $this->output->set_status_header(422)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'code' => 'nama_perusahaan_kosong', 'message' => $message, 'redirect' => base_url('akun/profil')])); return; }
            // Diarahkan ke tempat memperbaikinya, bukan ditolak buntu.
            $this->session->set_flashdata('error', $message); redirect('akun/profil'); return;
        }

        // Bentrok dengan direktori publik milik ORANG LAIN. Baris direktori milik
        // pemohon ini sendiri (kirim ulang setelah diperbaiki) sengaja dikecualikan.
        $this->db->where('nama_perusahaan', $nama);
        if ($registration->certified_developer_id) { $this->db->where('id !=', $registration->certified_developer_id); }
        if ($this->db->count_all_results('srp2_certified_developers') > 0) {
            $message = 'Nama perusahaan "' . $nama . '" sudah terdaftar di direktori pengembang bersertifikat. Periksa kembali penulisannya di halaman Profil, atau hubungi admin bila ini memang perusahaan Anda.';
            if ($is_ajax) { $this->output->set_status_header(409)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'code' => 'nama_perusahaan_bentrok', 'message' => $message, 'redirect' => base_url('akun/profil')])); return; }
            $this->session->set_flashdata('error', $message); redirect('akun/profil'); return;
        }

        // Anti-IDOR: WHERE user_id selalu dari sesi.
        //
        // Jejak keputusan LAMA ikut dibersihkan saat kirim ulang. Dulu update ini
        // hanya menulis status_verifikasi, sehingga catatan penolakan lama tetap
        // menempel: /akun menampilkan badge "Dalam Peninjauan" DITAMBAH kotak
        // catatan penolakan, tepat sesudah pemohon selesai memperbaiki. Dua
        // permukaan yang sama-sama dia lihat menceritakan hal berbeda.
        $this->db->where('id', (int) $id)->where('user_id', $this->get_user_id())
            ->update('srp2_registrations', [
                'status_verifikasi' => 'Pending',
                'catatan_admin'     => NULL,
                'reviewed_by'       => NULL,
                'reviewed_at'       => NULL,
            ]);

        if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'success', 'message' => 'Pengajuan SRP2 berhasil dikirim dan sedang menunggu verifikasi.'])); return; }
        $this->session->set_flashdata('success', 'Pengajuan SRP2 berhasil dikirim dan sedang menunggu verifikasi.'); redirect('akun');
    }

    private function akses_pengembang($target) {
        // Request AJAX (dipanggil wizard SRP2 lewat fetch) TIDAK BOLEH di-redirect() -
        // fetch akan diam-diam mengikuti redirect dan menerima HTML halaman lain sebagai
        // "berhasil". Balas status HTTP + JSON supaya wizard tahu harus tampilkan apa.
        if ($this->input->is_ajax_request()) {
            if (!$this->is_logged_in()) {
                $this->output->set_status_header(401)->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'code' => 'not_logged_in', 'message' => 'Silakan masuk atau daftar terlebih dahulu.']));
                return FALSE;
            }
            if ($this->session->userdata('role') !== 'pengembang') {
                $this->output->set_status_header(403)->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'code' => 'wrong_role', 'message' => 'Layanan SRP2 hanya tersedia untuk akun dengan peran pengembang.']));
                return FALSE;
            }
            return TRUE;
        }

        if (!$this->is_logged_in()) { $this->session->set_userdata('intended_url', $target); redirect('Pengembang/masuk'); return FALSE; }
        if ($this->session->userdata('role') !== 'pengembang') { $this->session->set_flashdata('error', 'Layanan SRP2 hanya tersedia untuk akun dengan peran pengembang.'); redirect('akun'); return FALSE; }
        return TRUE;
    }

    /**
     * Gerbang masuk/daftar khusus alur SRP2 - supaya user pengembang tetap di dalam
     * alur ini (bukan dilempar ke Auth/login umum) saat belum login. Auth/login dan
     * Auth/register utama TIDAK diubah, tetap berfungsi seperti biasa untuk role lain.
     */
    public function masuk() {
        if ($this->is_logged_in()) {
            $intended = $this->session->userdata('intended_url') ?: 'Pengembang/syarat';
            $this->session->unset_userdata('intended_url');
            redirect($intended);
            return;
        }
        $this->render('pages/pengembang/masuk', ['judul' => '', 'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']);
    }

    private function keterangan_persyaratan() {
        $this->load->helper('srp2');
        return srp2_keterangan_persyaratan();
    }

    private function dokumen_persyaratan() {
        // Daftar sesungguhnya ada di application/helpers/srp2_helper.php - satu
        // sumber kebenaran, juga dipakai Admin_Srp2::detail() untuk verifikasi.
        $this->load->helper('srp2');
        return srp2_dokumen_persyaratan();
    }
}
