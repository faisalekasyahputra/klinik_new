<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Auth_model â€” Handles email/password authentication, registration,
 * profile onboarding, rate limiting, and user document uploads.
 */
class Auth_model extends CI_Model {

    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_MINUTES    = 15;

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
    public function create_user($email, $password_hash) {
        $data = [
            'email'      => $email,
            'password'   => $password_hash,
            'status'     => 'restricted',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert('usr_users', $data);
        return $this->db->insert_id() ?: FALSE;
    }

    /**
     * Find user by email address.
     * Returns user row as object or NULL.
     */
    public function find_by_email($email) {
        return $this->db->get_where('usr_users', ['email' => $email])->row();
    }

    /**
     * Find user by email or username.
     * Returns user row as object or NULL.
     */
    public function find_by_login($login_id) {
        $this->db->group_start();
        $this->db->where('email', $login_id);
        $this->db->or_where('username', $login_id);
        $this->db->group_end();
        return $this->db->get('usr_users')->row();
    }

    /**
     * Find user by ID.
     */
    public function find_by_id($id) {
        return $this->db->get_where('usr_users', ['id' => (int)$id])->row();
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
        $this->db->update('usr_users', [
            'email_token'        => $token,
            'email_token_expiry' => $expiry,
        ]);
        return $token;
    }

    /**
     * Verify an email token. Returns the user object or NULL.
     */
    public function verify_email_token($token) {
        $user = $this->db->get_where('usr_users', [
            'email_token' => $token,
        ])->row();

        if (!$user) return NULL;

        // Check expiry
        if (strtotime($user->email_token_expiry) < time()) {
            return NULL;
        }

        // Mark email as verified
        $this->db->where('id', $user->id);
        $this->db->update('usr_users', [
            'email_verified_at'  => date('Y-m-d H:i:s'),
            'email_token'        => NULL,
            'email_token_expiry' => NULL,
        ]);

        return $user;
    }

    // =========================================================
    // Login Rate Limiting
    // =========================================================

    /**
     * Check if a user account is currently locked.
     */
    public function is_locked($user) {
        if ($user->login_attempts >= self::MAX_LOGIN_ATTEMPTS && $user->locked_until) {
            return strtotime($user->locked_until) > time();
        }
        return FALSE;
    }

    /**
     * Get remaining lockout seconds.
     */
    public function lockout_remaining($user) {
        if ($user->locked_until) {
            $remaining = strtotime($user->locked_until) - time();
            return max(0, $remaining);
        }
        return 0;
    }

    /**
     * Increment failed login attempts. Lock account if threshold reached.
     */
    public function increment_login_attempts($user_id) {
        $this->db->set('login_attempts', 'login_attempts + 1', FALSE);
        $this->db->where('id', $user_id);
        $this->db->update('usr_users');

        // Check if we need to lock
        $user = $this->find_by_id($user_id);
        if ($user && $user->login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $lock_until = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
            $this->db->where('id', $user_id);
            $this->db->update('usr_users', ['locked_until' => $lock_until]);
        }
    }

    /**
     * Reset login attempts on successful login.
     */
    public function reset_login_attempts($user_id) {
        $this->db->where('id', $user_id);
        $this->db->update('usr_users', [
            'login_attempts' => 0,
            'locked_until'   => NULL,
        ]);
    }

    // =========================================================
    // Onboarding / Profile Completion
    // =========================================================

    /**
     * Save onboarding profile data.
     * $data should contain role-specific fields.
     */
    public function save_profile($user_id, $data) {
        $data['profile_completed'] = 1;
        $data['status']            = 'active';
        $data['updated_at']        = date('Y-m-d H:i:s');

        $this->db->where('id', $user_id);
        return $this->db->update('usr_users', $data);
    }

    /**
     * Pastikan akun pengembang punya baris srp2_registrations, buat kalau belum.
     * SATU-SATUNYA tempat draft SRP2 dibuat — sebelumnya logika ini disalin di
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
     * @return int|null ID baris srp2_registrations, NULL kalau user tidak ada
     */
    public function ensure_srp2_draft($user_id, $status_filter = NULL) {
        $user_id = (int) $user_id;
        if ( ! $user_id) { return NULL; }

        $this->db->order_by('id', 'DESC')->where('user_id', $user_id);
        if ($status_filter !== NULL) { $this->db->where('status_verifikasi', $status_filter); }
        $baris = $this->db->get('srp2_registrations')->row();
        if ($baris) { return (int) $baris->id; }

        $user = $this->find_by_id($user_id);
        if ( ! $user) { return NULL; }

        $this->db->insert('srp2_registrations', [
            'user_id'           => $user_id,
            'email'             => $user->email,
            'nama_perusahaan'   => $user->nama_perusahaan,
            'status_verifikasi' => 'Draft',
        ]);
        return (int) $this->db->insert_id();
    }

    /**
     * Keadaan pengajuan SRP2 milik seorang pengembang — SATU sumber untuk
     * semua yang butuh tahu "sudah sampai mana orang ini".
     *
     * Dibuat karena keadaan ini dulu cuma dihitung di Pengembang::syarat(),
     * sementara Auth::do_login() (jalur AJAX wizard) hanya mengembalikan
     * registration_id. Akibatnya pengembang lama yang masuk LEWAT wizard
     * melihat keadaan tamu: 0/14 dokumen, tombol kirim terkunci, dan catatan
     * admin tidak muncul — padahal di server semuanya sudah ada.
     *
     * @param  int $user_id
     * @return array|null  registration_id, status, catatan_admin, uploaded_keys
     */
    public function srp2_state($user_id) {
        $registration_id = $this->ensure_srp2_draft($user_id);
        if ( ! $registration_id) { return NULL; }

        $baris = $this->db->get_where('srp2_registrations', ['id' => $registration_id])->row();
        if ( ! $baris) { return NULL; }

        $keys = $this->db->select('document_key')
            ->where('registration_id', $registration_id)
            ->get('srp2_documents')->result_array();

        return [
            'registration_id' => $registration_id,
            'status'          => $baris->status_verifikasi,
            'catatan_admin'   => $baris->catatan_admin,
            'uploaded_keys'   => array_column($keys, 'document_key'),
        ];
    }

    /**
     * Check if user has completed onboarding.
     */
    public function is_profile_complete($user_id) {
        $user = $this->find_by_id($user_id);
        return $user && $user->profile_completed == 1;
    }

    // =========================================================
    // Document Uploads
    // =========================================================

    /**
     * Save a document record linked to a user.
     */
    public function save_document($user_id, $doc_type, $file_name, $file_path, $file_size) {
        return $this->db->insert('usr_documents', [
            'user_id'     => $user_id,
            'doc_type'    => $doc_type,
            'file_name'   => $file_name,
            'file_path'   => $file_path,
            'file_size'   => $file_size,
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all documents for a user.
     */
    public function get_user_documents($user_id) {
        return $this->db->get_where('usr_documents', ['user_id' => $user_id])->result();
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
        $user = $this->db->get_where('usr_users', ['email_token' => $token])->row();
        if (!$user) return FALSE;

        if (strtotime($user->email_token_expiry) < time()) {
            return FALSE;
        }

        $this->db->where('id', $user->id);
        return $this->db->update('usr_users', [
            'password'           => $new_password_hash,
            'email_token'        => NULL,
            'email_token_expiry' => NULL,
        ]);
    }
}

