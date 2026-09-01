<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Admin_module_privileges extends CI_Migration {
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS usr_admin_module_privileges (
            user_id INT NOT NULL,
            module_key VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            allowed TINYINT(1) NOT NULL DEFAULT 0,
            updated_by INT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (user_id, module_key),
            KEY idx_admin_privilege_allowed (user_id, allowed),
            CONSTRAINT fk_admin_privilege_user FOREIGN KEY (user_id) REFERENCES usr_users (id) ON DELETE CASCADE,
            CONSTRAINT fk_admin_privilege_updater FOREIGN KEY (updated_by) REFERENCES usr_users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS usr_admin_module_privileges");
    }
}