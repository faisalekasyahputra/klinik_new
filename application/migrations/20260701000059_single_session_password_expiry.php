<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Single_session_password_expiry extends CI_Migration {
    public function up() {
        $fields = $this->db->list_fields('usr_users');
        $add = [];
        if ( ! in_array('active_session_hash', $fields, TRUE)) {
            $add['active_session_hash'] = ['type' => 'CHAR', 'constraint' => 64, 'null' => TRUE];
        }
        if ( ! in_array('active_session_at', $fields, TRUE)) {
            $add['active_session_at'] = ['type' => 'DATETIME', 'null' => TRUE];
        }
        if ( ! in_array('password_changed_at', $fields, TRUE)) {
            $add['password_changed_at'] = ['type' => 'DATETIME', 'null' => TRUE];
        }
        if ( ! in_array('password_expires_at', $fields, TRUE)) {
            $add['password_expires_at'] = ['type' => 'DATETIME', 'null' => TRUE];
        }
        if ($add) { $this->dbforge->add_column('usr_users', $add); }

        // Akun lama memperoleh masa transisi penuh 90 hari sejak migrasi.
        $now = date('Y-m-d H:i:s');
        $expiry = date('Y-m-d H:i:s', strtotime('+90 days'));
        $this->db->where('password IS NOT NULL', NULL, FALSE)
            ->where('password_changed_at IS NULL', NULL, FALSE)
            ->update('usr_users', ['password_changed_at' => $now, 'password_expires_at' => $expiry]);
    }

    public function down() {
        foreach (['active_session_hash', 'active_session_at', 'password_changed_at', 'password_expires_at'] as $field) {
            if ($this->db->field_exists($field, 'usr_users')) {
                $this->dbforge->drop_column('usr_users', $field);
            }
        }
    }
}
