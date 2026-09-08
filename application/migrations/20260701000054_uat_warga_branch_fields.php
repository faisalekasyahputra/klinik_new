<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Uat_warga_branch_fields extends CI_Migration {
    public function up()
    {
        if ( ! $this->db->field_exists('employment_stability_code', 'sf_profil_warga')) {
            $this->dbforge->add_column('sf_profil_warga', [
                'employment_stability_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE, 'after' => 'occupation_code'],
            ]);
        }
        if ( ! $this->db->field_exists('monthly_income', 'sf_profil_warga')) {
            $this->dbforge->add_column('sf_profil_warga', [
                'monthly_income' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => TRUE, 'after' => 'employment_stability_code'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('monthly_income', 'sf_profil_warga')) $this->dbforge->drop_column('sf_profil_warga', 'monthly_income');
        if ($this->db->field_exists('employment_stability_code', 'sf_profil_warga')) $this->dbforge->drop_column('sf_profil_warga', 'employment_stability_code');
    }
}