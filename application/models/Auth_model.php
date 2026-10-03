<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Auth_model â€” Handles email/password authentication, registration,
 * profile onboarding, rate limiting, and user document uploads.
 */
class Auth_model extends CI_Model {

    const MAX_LOGIN_ATTEMPTS = 5;
    const PASSWORD_TTL_DAYS  = 90;

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // =========================================================
    // Registration & Lookup
    // =========================================================

    /**
     * Create a new user with email and hashed password.
     * Returns the new user's ID or FALSE on failure.
     */
    public function create_user($email, $password_hash, $email_terverifikasi = FALSE) {
        $now = date('Y-m-d H:i:s');
        $data = [
            'email'      => $email,
            'kata_sandi' => $password_hash,
            'email_verified_at' => $email_terverifikasi ? $now : NULL, // TRUE hanya sesudah kode OTP benar
            'status'     => 'restricted',
            'sandi_diganti_at' => $now,
            'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+' . self::PASSWORD_TTL_DAYS . ' days')),
            'created_at' => $now,
        ];
        $this->db->insert('usr_akun', $data);
        return $this->db->insert_id() ?: FALSE;
    }

    /**
     * Find user by email address.
     * Returns user row as object or NULL.
     */
    public function find_by_email($email) {
        return $this->db->get_where('usr_akun', ['email' => $email])->row();
    }

    /**
     * Username: huruf kecil, angka, titik, garis bawah, tanda hubung; 3-40 karakter (isian formulir
     * maks. 30, username buatan sistem bisa lebih panjang). Tanpa "@", jadi username tidak pernah
     * bisa sama dengan email akun mana pun.
     */
    const POLA_USERNAME = '/^[a-z0-9_.-]{3,40}$/';

    /**
     * Alasan username ditolak untuk akun $user_id, atau NULL bila boleh dipakai. Dipakai onboarding
     * dan Profil Saya.
     */
    public function username_ditolak($username, $user_id) {
        if ( ! preg_match(self::POLA_USERNAME, (string) $username)) {
            return 'Username hanya boleh huruf kecil, angka, titik, garis bawah, atau tanda hubung (3-40 karakter), tanpa @.';
        }
        $dipakai = $this->db->group_start()->where('nama_pengguna', $username)->or_where('email', $username)->group_end()
            ->where('id !=', (int) $user_id)->count_all_results('usr_akun');
        return $dipakai > 0 ? 'Username sudah digunakan, silakan pilih yang lain.' : NULL;
    }

    /**
     * Cari akun dari isian login. Isian ber-"@" hanya dicocokkan ke kolom email, selain itu hanya ke
     * username: satu isian tidak pernah bisa cocok dengan dua akun (dulu `email = X OR username = X`
     * mengambil baris pertama, sehingga username yang sama dengan email akun lain membelokkan login).
     */
    public function find_by_login($login_id) {
        $kolom = strpos((string) $login_id, '@') !== FALSE ? 'email' : 'nama_pengguna';
        return $this->db->where($kolom, $login_id)->get('usr_akun')->row();
    }

    /**
     * Find user by ID.
     */
    public function find_by_id($id) {
        return $this->db->get_where('usr_akun', ['id' => (int)$id])->row();
    }

    // =========================================================
    // Email Verification (placeholder â€” tokens generated but email not sent yet)
    // =========================================================

    /**
     * Generate and store an email verification token.
     * Returns the token string.
     */
    public function generate_email_token($user_id) {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->db->where('id', $user_id);
        $this->db->update('usr_akun', [
            'token_email'        => $token,
            'token_email_kedaluwarsa' => $expiry,
        ]);
        return $token;
    }

    /**
     * Verify an email token. Returns the user object or NULL.
     */
    public function verify_email_token($token) {
        $user = $this->db->get_where('usr_akun', [
            'token_email' => $token,
        ])->row();

        if (!$user) return NULL;

        // Check expiry
        if (strtotime($user->token_email_kedaluwarsa) < time()) {
            return NULL;
        }

        // Mark email as verified
        $this->db->where('id', $user->id);
        $this->db->update('usr_akun', [
            'email_verified_at'  => date('Y-m-d H:i:s'),
            'token_email'        => NULL,
            'token_email_kedaluwarsa' => NULL,
        ]);

        return $user;
    }

    // =========================================================
    // Login Rate Limiting
    // =========================================================

    /**
     * Hitung login gagal beruntun (direset saat login berhasil). Sejak 3 Okt 2026 hanya bahan
     * peringatan admin: akun TIDAK dikunci, karena kunci per akun bisa dipicu siapa saja yang tahu
     * email/username korban. Penebak ditahan per pasangan IP + nama masuk (rate limit login_akun).
     * TRUE tiap kelipatan MAX_LOGIN_ATTEMPTS (pemicu peringatan; duplikat ditekan Security_alert).
     * terkunci_sampai tidak ditulis lagi; kolomnya tetap untuk baris lama dan tombol buka kunci.
     */
    public function increment_login_attempts($user_id) {
        $this->db->set('gagal_masuk', 'LEAST(gagal_masuk + 1, 127)', FALSE);
        $this->db->where('id', $user_id);
        $this->db->update('usr_akun');
        $user = $this->find_by_id($user_id);
        return $user && (int) $user->gagal_masuk % self::MAX_LOGIN_ATTEMPTS === 0;
    }

    /**
     * Reset login attempts on successful login.
     */
    public function reset_login_attempts($user_id) {
        $this->db->where('id', $user_id);
        $this->db->update('usr_akun', [
            'gagal_masuk' => 0,
            'terkunci_sampai'   => NULL,
        ]);
    }

    /** Terbitkan token sesi baru dan ikat ke ID sesi CI yang sedang aktif. */
    public function issue_session_token($user_id, $session_id = NULL) {
        $token = bin2hex(random_bytes(32));
        $session_id = (string) ($session_id ?: $this->session->session_id);
        $this->db->where('id', (int) $user_id)->update('usr_akun', [
            'sesi_aktif_hash' => hash('sha256', $token),
            'sesi_aktif_id_hash' => hash('sha256', $session_id),
            'sesi_aktif_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public function session_token_valid($user_id, $token, $session_id = NULL) {
        $session_id = (string) ($session_id ?: $this->session->session_id);
        if (empty($token) || empty($session_id)) { return FALSE; }
        $row = $this->db->select('sesi_aktif_hash,sesi_aktif_id_hash')
            ->get_where('usr_akun', ['id' => (int) $user_id])->row();
        return $row && ! empty($row->sesi_aktif_hash)
            && ! empty($row->sesi_aktif_id_hash)
            && hash_equals((string) $row->sesi_aktif_hash, hash('sha256', (string) $token))
            && hash_equals((string) $row->sesi_aktif_id_hash, hash('sha256', $session_id));
    }

    public function revoke_session_token($user_id, $token) {
        if ( ! $this->session_token_valid($user_id, $token)) { return; }
        $this->db->where('id', (int) $user_id)->update('usr_akun', [
            'sesi_aktif_hash' => NULL, 'sesi_aktif_id_hash' => NULL, 'sesi_aktif_at' => NULL,
        ]);
    }

    public function password_expired($user) {
        return ! empty($user->sandi_kedaluwarsa_at) && strtotime($user->sandi_kedaluwarsa_at) <= time();
    }

    public function password_lifetime_fields() {
        $now = date('Y-m-d H:i:s');
        return [
            'sandi_diganti_at' => $now,
            'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+' . self::PASSWORD_TTL_DAYS . ' days')),
        ];
    }

    /**
     * Sandi yang ditetapkan admin (akun baru, reset) langsung kedaluwarsa, jadi login pertama
     * diarahkan ke ganti sandi lewat mekanisme 90 hari yang sama. Sandi itu sempat diketahui
     * admin dan dikirim lewat jalur lain (keputusan pemilik produk 29 Sep 2026).
     */
    public function password_awal_fields() {
        $now = date('Y-m-d H:i:s');
        return ['sandi_diganti_at' => $now, 'sandi_kedaluwarsa_at' => $now];
    }

    /** Pesan wajib ganti sandi: sandi awal dari admin dikenali dari kedaluwarsa == saat ditetapkan. */
    public function pesan_ganti_sandi($user) {
        // Kedaluwarsa tanpa sandi_diganti_at hanya ditulis check_google_user() saat mencabut sandi lama.
        if ($user && ! empty($user->sandi_kedaluwarsa_at) && empty($user->sandi_diganti_at)) {
            return 'Kata sandi lama akun ini dihapus karena kepemilikan email dibuktikan lewat Google. Buat kata sandi baru untuk melanjutkan.';
        }
        $dari_admin = ! empty($user->sandi_diganti_at) && ! empty($user->sandi_kedaluwarsa_at)
            && strtotime($user->sandi_kedaluwarsa_at) <= strtotime($user->sandi_diganti_at);
        return $dari_admin
            ? 'Sandi awal dari admin harus diganti sebelum melanjutkan. Buat sandi baru yang hanya Anda ketahui.'
            : 'Kata sandi telah berusia 90 hari. Ganti kata sandi untuk melanjutkan.';
    }
    // =========================================================
    // Onboarding / Profile Completion
    // =========================================================

    /**
     * Turunkan username unik dari local-part email - dipakai HANYA saat
     * daftar cepat SRP2 mengisi profil_lengkap=1 tanpa pernah melalui
     * onboarding, sehingga name/username tidak pernah NULL (roadmap T5
     * S12-a). Bukan pengganti onboarding: user tetap bisa menggantinya
     * lewat /akun/profil kapan saja.
     */
    public function generate_unique_username($seed) {
        $base = strtolower(preg_replace('/[^a-z0-9_]/', '', $seed));
        if ($base === '') { $base = 'pengembang'; }
        $base = substr($base, 0, 40);

        $username = $base;
        $suffix = 1;
        while ($this->db->where('nama_pengguna', $username)->count_all_results('usr_akun') > 0) {
            $username = substr($base, 0, 40) . (++$suffix);
        }
        return $username;
    }

    /**
     * Save onboarding profile data.
     * $data should contain role-specific fields.
     */
    public function save_profile($user_id, $data) {
        $data['profil_lengkap'] = 1;
        $data['status']            = 'active';
        $data['updated_at']        = date('Y-m-d H:i:s');

        $this->db->where('id', $user_id);
        return $this->db->update('usr_akun', $data);
    }

    /**
     * Pastikan akun pengembang punya baris srp2_pengajuan, buat kalau belum.
     * SATU-SATUNYA tempat draft SRP2 dibuat - sebelumnya logika ini disalin di
     * empat tempat (Auth::do_login cabang AJAX, Auth::do_register,
     * Auth::lanjutkan, Pengembang::syarat) dan satu jalur terlewat:
     * Auth::save_onboarding() tidak membuatnya sama sekali, sehingga user yang
     * jadi pengembang lewat onboarding umum tidak melihat item SRP2 apa pun di
     * /akun sampai kebetulan membuka wizard. Lihat PRD_VERIFIKASI_ADMIN_SRP2.md
     * Fase 2 dan AUDIT_ROLE_PENGEMBANG.md temuan #2.
     *
     * Idempotent: aman dipanggil berkali-kali, tidak pernah membuat draft dobel.
     *
     * @param int         $user_id
     * @param string|null $status_filter batasi pencarian ke status tertentu
     *                                   (dipakai Auth::lanjutkan yang memang
     *                                   cuma peduli draft yang belum dikirim)
     * @return int|null ID baris srp2_pengajuan, NULL kalau user tidak ada
     */
    public function ensure_srp2_draft($user_id, $status_filter = NULL) {
        $user_id = (int) $user_id;
        if ( ! $user_id) { return NULL; }

        $this->db->order_by('id', 'DESC')->where('user_id', $user_id);
        if ($status_filter !== NULL) { $this->db->where('status_verifikasi', $status_filter); }
        $baris = $this->db->get('srp2_pengajuan')->row();
        if ($baris) { return (int) $baris->id; }

        // JANGAN pernah membuat baris kedua untuk user yang sudah punya pengajuan.
        // $status_filter menyempitkan PENCARIAN, bukan izin membuat: pemanggil
        // yang mencari khusus 'Draft' (Auth::lanjutkan) dulu jatuh ke INSERT saat
        // pengajuannya sudah Pending - draft kosong baru itu lalu menang di semua
        // ORDER BY id DESC, dan pengajuan yang sudah dikirim lenyap dari pandangan
        // pemohon padahal admin masih melihatnya.
        //
        // Guard diletakkan di sini, bukan di pemanggilnya, supaya kelima jalur
        // yang memakai fungsi ini ikut benar sekaligus.
        if ($status_filter !== NULL) {
            $terakhir = $this->db->order_by('id', 'DESC')->where('user_id', $user_id)
                ->get('srp2_pengajuan')->row();
            if ($terakhir) { return (int) $terakhir->id; }
        }

        $user = $this->find_by_id($user_id);
        if ( ! $user) { return NULL; }

        // Data perusahaan tidak lagi ada di usr_akun (migrasi 070): pemanggil yang punya isian
        // perusahaan menuliskannya lewat isi_pengajuan_kosong() sesudah draft ada.
        $this->db->insert('srp2_pengajuan', [
            'user_id'           => $user_id,
            'email'             => $user->email,
            'status_verifikasi' => 'Draft',
        ]);
        return (int) $this->db->insert_id();
    }

    /**
     * Isi medan perusahaan pengajuan yang MASIH KOSONG; isian pengajuan yang sudah ada tidak
     * ditimpa. Tempat data perusahaan akun pengembang yang belum punya baris direktori
     * (migrasi 070); sesudah tertaut, sumbernya srp2_direktori_pengembang.
     */
    public function isi_pengajuan_kosong($pengajuan_id, array $data) {
        foreach ($data as $kolom => $nilai) {
            if (trim((string) $nilai) === '') { continue; }
            $this->db->where('id', (int) $pengajuan_id)
                ->group_start()->where($kolom . ' IS NULL', NULL, FALSE)->or_where($kolom, '')->group_end()
                ->update('srp2_pengajuan', [$kolom => $nilai]);
        }
    }

    /**
     * Keadaan pengajuan SRP2 milik seorang pengembang - SATU sumber untuk
     * semua yang butuh tahu "sudah sampai mana orang ini".
     *
     * Dibuat karena keadaan ini dulu cuma dihitung di Pengembang::syarat(),
     * sementara Auth::do_login() (jalur AJAX wizard) hanya mengembalikan
     * pengajuan_id. Akibatnya pengembang lama yang masuk LEWAT wizard
     * melihat keadaan tamu: 0/14 dokumen, tombol kirim terkunci, dan catatan
     * admin tidak muncul - padahal di server semuanya sudah ada.
     *
     * @param  int $user_id
     * @return array|null  pengajuan_id, status, catatan_admin, uploaded_keys
     */
    public function srp2_state($user_id) {
        $pengajuan_id = $this->ensure_srp2_draft($user_id);
        if ( ! $pengajuan_id) { return NULL; }

        $baris = $this->db->get_where('srp2_pengajuan', ['id' => $pengajuan_id])->row();
        if ( ! $baris) { return NULL; }

        $keys = $this->db->select('kunci_dokumen')
            ->where('pengajuan_id', $pengajuan_id)
            ->get('srp2_dokumen')->result_array();

        return [
            'pengajuan_id' => $pengajuan_id,
            'status'          => $baris->status_verifikasi,
            'catatan_admin'   => $baris->catatan_admin,
            'uploaded_keys'   => array_column($keys, 'kunci_dokumen'),
        ];
    }

    /**
     * Terbitkan / segarkan baris direktori publik dari sebuah pengajuan.
     * SATU fungsi, dipanggil dari DUA tempat: Admin_Srp2::proses() saat approve,
     * dan Pengaturan::update_pengembang_profile() saat pemohon mengubah datanya.
     *
     * Dibuat karena dulu baris direktori hanya diisi SEKALI saat approve: ganti
     * alamat/website/Instagram sesudah itu tidak pernah sampai ke publik, padahal
     * form-nya berlabel "Kontak publik - ditampilkan di halaman profil
     * pengembang". Label yang menjanjikan sesuatu yang tidak terjadi termasuk
     * kebohongan di layar (§0d).
     *
     * Dipanggil dari dalam transaksi pemanggilnya - sengaja tidak membuka
     * transaksi sendiri supaya tidak bersarang.
     *
     * @param  object $reg baris srp2_pengajuan
     * @return int|null    id baris direktori, NULL kalau tidak bisa diterbitkan
     */
    public function upsert_direktori_publik($reg) {
        $nama = trim((string) ($reg->nama_perusahaan ?? ''));
        // Kolom nama di direktori NOT NULL + UNIQUE - tanpa nama tidak ada yang
        // bisa diterbitkan. Gerbangnya sendiri ada di kirim_pengajuan() (T1a).
        if ($nama === '') { return NULL; }

        $payload = [
            'nama_perusahaan' => $nama,
            'alamat_kantor'   => $reg->alamat_kantor ?? NULL,
            'website'         => $reg->website ?? NULL,
            'instagram'       => $reg->instagram ?? NULL,
            'sosmed_lainnya'  => $reg->sosmed_lainnya ?? NULL,
        ];

        /* Asosiasi ikut menular ke direktori 14 Agt 2026. Sebelumnya TIDAK -
           pengembang memilih asosiasinya di /akun/profil, nilainya tersimpan
           rapi di srp2_pengajuan, dan berhenti di situ: kolom asosiasi di
           direktori publik tidak pernah terisi dari jalur ini (67 dari 67
           baris NULL saat diperiksa).

           Hanya disalin kalau MEMANG TERISI - beda dari field lain di atas.
           Kolom ini juga bisa diisi admin langsung lewat Admin_Srp2 untuk data
           historis yang tidak punya baris registrasi berpasangan; menyalin
           NULL apa adanya akan menghapus isian admin itu tiap kali pemohon
           menyentuh formulir profilnya. Ini persis bug `sosmed_lainnya`
           10 Agt (lihat komentarnya di Admin_Srp2::index()), jangan diulang. */
        $asosiasi = trim((string) ($reg->asosiasi ?? ''));
        if ($asosiasi !== '') { $payload['asosiasi'] = $asosiasi; }

        // NPWP sudah divalidasi dan dienkripsi saat onboarding. Nilai ini
        // diteruskan saat pengajuan diterima tanpa pernah dibuka ke publik.
        if ( ! empty($reg->npwp_lookup_hash) && ! empty($reg->npwp_ciphertext)) {
            $payload['npwp_ciphertext'] = $reg->npwp_ciphertext;
            $payload['npwp_lookup_hash'] = $reg->npwp_lookup_hash;
        }
        // Medan kontak migrasi 066: sama seperti asosiasi, hanya yang TERISI yang menular,
        // supaya isian dinas di baris direktori tidak tersapu NULL dari pengajuan.
        foreach (['nib', 'no_keanggotaan', 'no_whatsapp'] as $k) {
            $v = trim((string) ($reg->$k ?? ''));
            if ($v !== '') { $payload[$k] = $v; }
        }

        if ( ! empty($reg->pengembang_id)) {
            // Sudah terbit: segarkan isinya, JANGAN sentuh status_aktif -
            // pencabutan/pengaktifan adalah keputusan admin yang terpisah.
            $id = (int) $reg->pengembang_id;
            $this->db->where('id', $id)->update('srp2_direktori_pengembang', $payload);
        } else {
            $payload['status_aktif'] = 1;
            $this->db->insert('srp2_direktori_pengembang', $payload);
            $id = (int) $this->db->insert_id();
            if ( ! $id) { return NULL; }
        }

        /* Email pengajuan itu email AKUN, bukan kontak publik pilihan perusahaan: hanya
           mengisi email_kontak yang masih kosong, tidak pernah menimpanya. */
        $email = trim((string) ($reg->email ?? ''));
        if ($email !== '') {
            $this->db->where('id', $id)->group_start()->where('email_kontak IS NULL', NULL, FALSE)->or_where('email_kontak', '')->group_end()
                ->update('srp2_direktori_pengembang', ['email_kontak' => $email]);
        }

        /* Pemilik pengajuan menjadi pemilik baris direktori (Profil Perusahaan), selama baris
           itu belum bertuan dan akunnya belum memegang baris lain (UNIQUE user_id). */
        if ( ! empty($reg->user_id)
            && ! $this->db->where('user_id', (int) $reg->user_id)->where('id !=', $id)->count_all_results('srp2_direktori_pengembang')) {
            $this->db->where('id', $id)->where('user_id IS NULL', NULL, FALSE)
                ->update('srp2_direktori_pengembang', ['user_id' => (int) $reg->user_id]);
        }
        return $id;
    }

    /**
     * Arah sebaliknya dari upsert_direktori_publik(): baris direktori yang tertaut akun
     * (user_id) menyalin isinya ke pengajuan SRP2 akun itu yang menunjuk baris ini (usr_akun
     * tidak lagi menyimpan data perusahaan sejak migrasi 070). Dipanggil sesudah admin atau
     * pengembang mengubah baris direktori, supaya Profil Saya, Status Pengajuan, dan layar pengajuan admin tidak
     * menampilkan data lama. Tanpa transaksi sendiri (dipanggil dari dalam transaksi).
     *
     * NIB dan NPWP UNIQUE di pengajuan: hanya disalin bila tidak dipakai pengajuan lain.
     */
    public function sinkron_pengajuan_dari_direktori($cid) {
        $d = $this->db->get_where('srp2_direktori_pengembang', ['id' => (int) $cid])->row();
        if ( ! $d || ! $d->user_id) { return; }

        $data = [];
        foreach (['alamat_kantor', 'no_keanggotaan', 'no_whatsapp', 'instagram', 'website', 'sosmed_lainnya'] as $k) { $data[$k] = $d->$k; }
        if (strlen((string) $d->nama_perusahaan) <= 150) { $data['nama_perusahaan'] = $d->nama_perusahaan; }
        if (strlen((string) $d->asosiasi) <= 30) { $data['asosiasi'] = $d->asosiasi; }
        $milik = function ($kolom, $nilai) use ($d, $cid) {
            return $nilai === NULL || ! $this->db->where($kolom, $nilai)
                ->group_start()->where('user_id !=', (int) $d->user_id)->or_where('pengembang_id !=', (int) $cid)->or_where('pengembang_id IS NULL', NULL, FALSE)->group_end()
                ->count_all_results('srp2_pengajuan');
        };
        if ($milik('nib', $d->nib)) { $data['nib'] = $d->nib; }
        if ($milik('npwp_lookup_hash', $d->npwp_lookup_hash)) {
            $data['npwp_ciphertext'] = $d->npwp_ciphertext;
            $data['npwp_lookup_hash'] = $d->npwp_lookup_hash;
        }
        $this->db->where('pengembang_id', (int) $cid)->where('user_id', (int) $d->user_id)
            ->update('srp2_pengajuan', $data);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function is_profile_complete($user_id) {
        $user = $this->find_by_id($user_id);
        return $user && $user->profil_lengkap == 1;
    }

    // =========================================================
    // Document Uploads
    // =========================================================

    /**
     * Save a document record linked to a user.
     */
    public function save_document($user_id, $jenis_dokumen, $file_name, $file_path, $file_size) {
        return $this->db->insert('usr_dokumen', [
            'user_id'     => $user_id,
            'jenis_dokumen'    => $jenis_dokumen,
            'nama_berkas' => $file_name,
            'path_berkas' => $file_path,
            'ukuran_berkas' => $file_size,
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all documents for a user.
     */
    public function get_user_documents($user_id) {
        return $this->db->get_where('usr_dokumen', ['user_id' => $user_id])->result();
    }

    // =========================================================
    // Password Reset (placeholder)
    // =========================================================

    /**
     * Generate password reset token (same mechanism as email token).
     */
    public function generate_reset_token($user_id) {
        return $this->generate_email_token($user_id);
    }

    /**
     * Reset password using token.
     */
    public function reset_password($token, $new_password_hash) {
        $user = $this->db->get_where('usr_akun', ['token_email' => $token])->row();
        if (!$user) return FALSE;

        if (strtotime($user->token_email_kedaluwarsa) < time()) {
            return FALSE;
        }

        $this->db->where('id', $user->id);
        return $this->db->update('usr_akun', [
            'kata_sandi'         => $new_password_hash,
            'token_email'        => NULL,
            'token_email_kedaluwarsa' => NULL,
            'sesi_aktif_hash' => NULL,
            'sesi_aktif_id_hash' => NULL,
            'sesi_aktif_at' => NULL,
        ] + $this->password_lifetime_fields());
    }
}
