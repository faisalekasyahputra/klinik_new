<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Session_id_validation extends CI_Migration {
    public function up() {
        if ( ! $this->db->field_exists('active_session_id_hash', 'usr_users')) {
            $this->dbforge->add_column('usr_users', [
                'active_session_id_hash' => [
                    'type' => 'CHAR',
                    'constraint' => 64,
                    'null' => TRUE,
                    'after' => 'active_session_hash',
                ],
            ]);
        }
    }

    public function down() {
        if ($this->db->field_exists('active_session_id_hash', 'usr_users')) {
            $this->dbforge->drop_column('usr_users', 'active_session_id_hash');
        }
    }
}
