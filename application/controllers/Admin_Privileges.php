<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Privileges extends Admin_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->config('roles');
        $this->load->config('dashboard_modules');
    }

    private function target($id) {
        $user = $this->db->get_where('usr_akun', ['id' => (int) $id])->row();
        return $user && in_array($user->peran, ['admin_kabkota','admin_bidang'], TRUE) ? $user : NULL;
    }

    private function modules($user) {
        $result = [];
        foreach (($this->config->item('dashboard_modules') ?: []) as $key => $module) {
            if ($key === 'profil' || (isset($module['enabled']) && $module['enabled'] === FALSE)) { continue; }
            if (empty($module['roles']) || ! in_array($user->peran, $module['roles'], TRUE)) { continue; }
            if ( ! empty($module['scope'])) {
                $value = $user->{$module['scope']} ?? NULL;
                if (empty($value) || (!empty($module['scope_values']) && !in_array($value, $module['scope_values'], TRUE))) { continue; }
            }
            $result[$key] = $module;
        }
        return $result;
    }

    public function index($id = 0) {
        $user = $this->target($id);
        if (!$user) { $this->session->set_flashdata('error','Hak modul hanya untuk Admin Kabupaten/Kota atau Admin Bidang.'); redirect('Admin_Users'); return; }
        $modules = $this->modules($user); $groups = [];
        foreach ($modules as $key => $module) { $groups[$module['group'] ?? 'Lainnya'][$key] = $module; }
        $rows = $this->db->get_where('usr_hak_modul_admin',['user_id'=>(int)$user->id])->result();
        $selected = [];
        if ($rows) { foreach ($rows as $row) { $selected[$row->kunci_modul] = (bool)$row->diizinkan; } }
        else { foreach ($modules as $key => $_) { $selected[$key] = TRUE; } }
        $scope = $user->peran === 'admin_kabkota'
            ? $this->db->get_where('kabupaten',['id'=>$user->kabupaten_id])->row()
            : $this->db->get_where('bidang',['kode'=>$user->bidang_kode])->row();
        $roles = $this->config->item('available_roles') ?: [];
        $this->render_admin('admin/users/privileges', [
            'title'=>'Hak Modul','user'=>$user,'role_label'=>$roles[$user->peran] ?? $user->peran,
            'scope_label'=>$scope->nama ?? '-','module_groups'=>$groups,'selected'=>$selected
        ]);
    }

    public function save() {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $user = $this->target($this->input->post('id'));
        if (!$user) { $this->session->set_flashdata('error','Akun admin tidak valid.'); redirect('Admin_Users'); return; }
        $modules = $this->modules($user);
        $posted = $this->input->post('modules');
        $posted = is_array($posted) ? array_intersect(array_keys($modules),$posted) : [];
        $this->db->trans_start();
        $this->db->where('user_id',(int)$user->id)->delete('usr_hak_modul_admin');
        foreach ($modules as $key => $_) {
            $this->db->insert('usr_hak_modul_admin',[
                'user_id'=>(int)$user->id,'kunci_modul'=>$key,'diizinkan'=>in_array($key,$posted,TRUE)?1:0,
                'updated_by'=>(int)$this->get_user_id(),'updated_at'=>date('Y-m-d H:i:s')
            ]);
        }
        $this->db->trans_complete();
        if (!$this->db->trans_status()) { $this->session->set_flashdata('error','Hak modul gagal disimpan.'); }
        else {
            $this->catat_audit('privilege_admin_diubah','Mengatur privilege modul '.$user->email,'usr_akun',(string)$user->id,['modules'=>array_values($posted)]);
            $this->session->set_flashdata('success','Hak modul admin berhasil disimpan.');
        }
        redirect('Admin_Privileges/index/'.(int)$user->id);
    }
}