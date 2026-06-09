<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Auth_model — Handles email/password authentication, registration,
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
        $this->db->insert('users', $data);
        return $this->db->insert_id() ?: FALSE;
    }

    /**
     * Find user by email address.
     * Returns user row as object or NULL.
     */
    public function find_by_email($email) {
        return $this->db->get_where('users', ['email' => $email])->row();
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
        return $this->db->get('users')->row();
    }

    /**
     * Find user by ID.
     */
    public function find_by_id($id) {
        return $this->db->get_where('users', ['id' => (int)$id])->row();
    }

    // =========================================================
    // Email Verification (placeholder — tokens generated but email not sent yet)
    // =========================================================

    /**
     * Generate and store an email verification token.
     * Returns the token string.
     */
    public function generate_email_token($user_id) {
        $token  = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->db->where('id', $user_id);
        $this->db->update('users', [
            'email_token'        => $token,
            'email_token_expiry' => $expiry,
        ]);
        return $token;
    }

    /**
     * Verify an email token. Returns the user object or NULL.
     */
    public function verify_email_token($token) {
        $user = $this->db->get_where('users', [
            'email_token' => $token,
        ])->row();

        if (!$user) return NULL;

        // Check expiry
        if (strtotime($user->email_token_expiry) < time()) {
            return NULL;
        }

        // Mark email as verified
        $this->db->where('id', $user->id);
        $this->db->update('users', [
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
        $this->db->update('users');

        // Check if we need to lock
        $user = $this->find_by_id($user_id);
        if ($user && $user->login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $lock_until = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
            $this->db->where('id', $user_id);
            $this->db->update('users', ['locked_until' => $lock_until]);
        }
    }

    /**
     * Reset login attempts on successful login.
     */
    public function reset_login_attempts($user_id) {
        $this->db->where('id', $user_id);
        $this->db->update('users', [
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
        return $this->db->update('users', $data);
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
        return $this->db->insert('user_documents', [
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
        return $this->db->get_where('user_documents', ['user_id' => $user_id])->result();
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
        $user = $this->db->get_where('users', ['email_token' => $token])->row();
        if (!$user) return FALSE;

        if (strtotime($user->email_token_expiry) < time()) {
            return FALSE;
        }

        $this->db->where('id', $user->id);
        return $this->db->update('users', [
            'password'           => $new_password_hash,
            'email_token'        => NULL,
            'email_token_expiry' => NULL,
        ]);
    }
}
