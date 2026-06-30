
<?php
class Buka_peta extends CI_Model
{
    public function frd($tablename, $args, $field)
    {
        if ($args != NULL) {
            $this->db->where([$field => $args]);
            $query = $this->db->get($tablename);
        } else {
            $query = $this->db->get($tablename);
        }

        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return NULL;
        }
    }
    public function side($n)
    {

        $nilai = $n;
        $menu = $this->frd('sys_menu', $nilai, 'id', null, null);
        if ($menu[0]->link != '#') {
            $res = array($menu[0]->default => array('class="active"', 'class="current-page"', ''));
        } else {
            $res = array($menu[0]->default => array('', '', 'active'));
        }

        $this->db->where('id !=', $nilai);
        $query = $this->db->get('sys_menu');
        $tidak_ada = $query->result();
        foreach ($tidak_ada as $td) {
            $res1 = array($td->default => array('', '', ''));
            $res = array_merge($res, $res1);
        }
        return $res;
    }
    public function menu($args)
    {
        $this->db->select("menu.*");
        $this->db->from('sys_multi');
        $this->db->join("`user`", "multi.id_user = `user`.id");
        $this->db->join('sys_menu', "sys_multi.id_menu = sys_menu.id");
        $this->db->where("multi.id_user", $args);
        $query = $this->db->get();
        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return NULL;
        }
    }
    public function authenticate_user($Email, $password)
    {
        $this->db->where('email =', $Email);
        $this->db->where('pass =', sha1($password));
        $query = $this->db->get('user');
        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return NULL;
        }
    }
     public function jml($tablename, $args, $field)
    {
        if ($args != NULL) {
            $this->db->where([$field => $args]);
            $query = $this->db->get($tablename);
        } else {
            $query = $this->db->get($tablename);
        }
        return $query->num_rows();
       
    }
    public function frd1($tablename, $args, $field)
    {
        $this->db->where('id >=', '1');
        $this->db->where('id <=', '700');
        $query = $this->db->get($tablename);

        if ($query->num_rows() > 0) {
            return $query->result();
        } else {
            return NULL;
        }
    }
	public function peta3($table, $arg, $field, $tipe)
{
    $hasil = $this->frd($table, $arg, $field);

    $features = [];

    if ($hasil != NULL) {
        foreach ($hasil as $has) {

            $properties = [];
            foreach ($has as $key => $val) {
                if ($key != 'geojson') {
                    $properties[$key] = $val;
                }
            }

            $features[] = [
                "type" => "Feature",
                "properties" => $properties,
                "geometry" => [
                    "type" => $tipe,
                    "coordinates" => json_decode($has->geojson)
                ]
            ];
        }
    }

    return json_encode($features);
}
    public function peta($table, $arg, $field, $tipe)
    {

        $hasil = $this->frd($table, $arg, $field, null, null);
        $utama = "";
      
      
        $mygeo = new stdClass();
        $mygeo->type = $tipe;
        if ($hasil != NULL) {
            foreach ($hasil as $has) {
				 $myObj = new stdClass();
                foreach ($has as $key => $val) {
                    if ($key != 'geojson') {
                        $myObj->$key = $val;
                    }
                }
                $mygeo->coordinates = json_decode($has->geojson, TRUE);
				$myawal = new stdClass();
                $myawal->type = "Feature";
                $myawal->properties = $myObj;
                $myawal->geometry = $mygeo;
                $utama = $utama . json_encode($myawal) . ',';
            }
        } else {
            $utama = NULL;
        }
        return array($utama,$hasil);
    }
    public function peta1($table, $arg, $field, $tipe)
    {

        $hasil = $this->frd1($table, $arg, $field, null, null);
        $utama = "";
        $myObj = new stdClass();
        $myawal = new stdClass();
        $mygeo = new stdClass();
        $mygeo->type = $tipe;
        if ($hasil != NULL) {
            foreach ($hasil as $has) {
                foreach ($has as $key => $val) {
                    if ($key != 'geojson') {
                        $myObj->$key = $val;
                    }
                }
                $mygeo->coordinates = json_decode($has->geojson, TRUE);
                $myawal->type = "Feature";
                $myawal->properties = $myObj;
                $myawal->geometry = $mygeo;
                $utama = $utama . json_encode($myawal) . ',';
            }
        } else {
            $utama = NULL;
        }
        return $utama;
    }
    public function delete_record($tablename, $arg)
    {
        $db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $this->db->where(['id' => $arg]);
        $this->db->delete($tablename);
        if ($this->db->affected_rows() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }

        $this->db->db_debug = $db_debug;
    }

    public function insert_data($tablename, $arg1)
    {
        $this->db->insert($tablename, $arg1);
        if ($this->db->affected_rows() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function edit_record($args, $data)
    {
        extract($args);
        $this->db->where($field, $val);
        $this->db->update($table_name, $data);
        return TRUE;
    }

    
    public function peta_jalan($arg, $field, $table, $tipe)
    {

        $hasil = $this->frd($table, $arg, $field, null, null);
        $utama = "";
        $myObj = new stdClass();
        $myawal = new stdClass();
        $mygeo = new stdClass();
        $mygeo->type = $tipe;
        if ($hasil != NULL) {
            foreach ($hasil as $has) {
                foreach ($has as $key => $val) {
                    if ($key != 'geojson') {
                        $myObj->$key = $val;
                    }
                }
                $mygeo->coordinates = json_decode($has->geojson, TRUE);
                $myawal->type = "Feature";
                $myawal->properties = $myObj;
                $myawal->geometry = $mygeo;
                $utama = $utama . json_encode($myawal) . ',';
            }
        } else {
            $utama = NULL;
        }
        return $utama;
    }

    public function statistik($args, $args1,$kondisi,$table)
    {

        if ($args != null) {
            $this->db->where(['UPTD' => $args]);
        }
        if ($kondisi != null) {
            $this->db->where([$kondisi => $args1]);
        }
      
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            return $query->num_rows();
        } else {
            return 0;
        }
    }
    public function statistik_kecamatan($args, $args1,$kondisi,$table)
    {

        if ($args != null) {
            $this->db->where(['Kecamatan' => $args]);
        }
        if ($kondisi != null) {
            $this->db->where([$kondisi => $args1]);
        }
      
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            return $query->num_rows();
        } else {
            return 0;
        }
    }
     public function statistik_kemantren($args,$table,$jns)
    {

        if ($args != null) {
            $this->db->where([$jns => $args]);
        }
        
      
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            return $query->num_rows();
        } else {
            return 0;
        }
    }
    public function statistik_pan($args, $args1,$kondisi,$table,$panjang)
    {
        $this->db->select_sum($panjang);
        if ($args != null) {
            $this->db->where(['UPTD' => $args]);
        }
        if ($kondisi != null) {
            $this->db->where([$kondisi => $args1]);
        }
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            
            if ($query->result() != '') {
                  return $query->result();
            }else{
                 return 0;
            }
           
        } else {
            return 0;
        }
    }
     public function statistik_pan_kecamatan($args, $args1,$kondisi,$table,$panjang)
    {
        $this->db->select_sum($panjang);
        if ($args != null) {
            $this->db->where(['Kecamatan' => $args]);
        }
        if ($kondisi != null) {
            $this->db->where([$kondisi => $args1]);
        }
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            
            if ($query->result() != '') {
                  return $query->result();
            }else{
                 return 0;
            }
           
        } else {
            return 0;
        }
    }
    public function statistik_pan_kemantren($args,$table,$jns,$panjang)
    {
        $this->db->select_sum($panjang);
        if ($args != null) {
            $this->db->where([$jns => $args]);
        }
       
        $query = $this->db->get($table);

        if ($query->num_rows() > 0) {
            
            if ($query->result() != '') {
                  return $query->result();
            }else{
                 return 0;
            }
           
        } else {
            return 0;
        }
    }

}

