<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {

    protected $table = 'sys_settings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all settings as an associative array (key => value)
     */
    public function get_all()
    {
        $query = $this->db->get($this->table);
        $result = $query->result_array();
        
        $settings = [];
        foreach ($result as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }
        return $settings;
    }

    /**
     * Get a specific setting by key
     */
    public function get_by_key($key)
    {
        $this->db->where('key_name', $key);
        $query = $this->db->get($this->table);
        $row = $query->row_array();
        
        if ($row) {
            return $row['key_value'];
        }
        return null;
    }

    /**
     * Update multiple settings from associative array
     */
    public function update_batch_settings($data)
    {
        foreach ($data as $key => $value) {
            $this->db->where('key_name', $key);
            $this->db->update($this->table, ['key_value' => $value]);
            
            // If key doesn't exist, insert it
            if ($this->db->affected_rows() == 0) {
                // Check if it really exists to avoid inserting if update just didn't change the value
                $this->db->where('key_name', $key);
                $query = $this->db->get($this->table);
                if ($query->num_rows() == 0) {
                    $this->db->insert($this->table, [
                        'key_name' => $key,
                        'key_value' => $value,
                        'type' => 'text'
                    ]);
                }
            }
        }
        return true;
    }
}
