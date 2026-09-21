<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function check_google_user($data) {
        // Cek apakah user dengan email tersebut sudah ada
        $this->db->where('email', $data['email']);
        $query = $this->db->get('usr_users');

        if ($query->num_rows() > 0) {
            // Jika user ada, update google_id dan avatar (jika sebelumnya login manual)
            $this->db->where('email', $data['email']);
            $this->db->update('usr_users', array(
                'google_id' => $data['google_id'],
                'avatar'    => $data['avatar']
            ));
            return [$query->row_array(),'1'];
        } else {
            // Jika user belum terdaftar, buat akun baru otomatis
            $this->db->insert('usr_users', $data);
            $insert_id = $this->db->insert_id();
            
            $this->db->where('id', $insert_id);
            $new_user = $this->db->get('usr_users');
            return [$new_user->row_array(),'0'];
        }
    }

    public function update_user($user_id, $data) {
        $this->db->trans_start();
        
        $this->db->where('id', $user_id);
        $this->db->update('usr_users', $data);

        // Fetch updated user to get the correct display name (username fallback to name)
        $user = $this->db->get_where('usr_users', ['id' => $user_id])->row_array();
        $display_name = !empty($user['username']) ? $user['username'] : $user['name'];

        // Sync with forum tables
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', ['nama_user' => $display_name]);

        $this->db->where('user_id', $user_id);
        $this->db->update('forum_komentar', ['nama_komentator' => $display_name]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Hapus file fisik yang jadi yatim akibat FK CASCADE saat baris DB dihapus
     * di bawah (srp2_registrations->srp2_documents, kkn_magang_pendaftaran).
     * WAJIB dipanggil SEBELUM baris DB dihapus - begitu CASCADE jalan, tidak
     * ada lagi cara menemukan nama file yang harus dihapus dari disk.
     * Lihat application/migrations/20260701000012_add_submission_owner_fk.php.
     */
    private function _cleanup_owned_files($user_id) {
        // Lokasi akar dari helper - ikut PRIVATE_UPLOADS_PATH di .env kalau diisi.
        // Jangan susun path sendiri di sini; pernah terjadi path di sini tertinggal
        // di lokasi publik lama setelah penyimpanan dipindah, sehingga berkasnya
        // tidak pernah benar-benar terhapus.
        $this->load->helper('private_upload');

        // --- Dokumen SRP2 (private_uploads/srp2/{registration_id}/) ---
        $registration_ids = array_column(
            $this->db->select('id')->get_where('srp2_registrations', ['user_id' => $user_id])->result_array(),
            'id'
        );
        // Disapu berdasarkan ISI DISK, bukan hanya nama yang tercatat DB.
        // Menyapu dari DB saja meninggalkan berkas yatim: setiap kali dokumen
        // DIGANTI, baris lamanya hilang beserta nama berkasnya, sehingga berkas
        // fisiknya tidak lagi terjangkau pencarian apa pun. Akibatnya akta,
        // NPWP, dan laporan keuangan bisa selamat dari penghapusan akun -
        // kewajiban retensi UU PDP, bukan sekadar kerapian.
        foreach ($registration_ids as $rid) {
            $dir = private_uploads_dir('srp2', $rid);
            if (!is_dir($dir)) { continue; }
            foreach (scandir($dir) ?: [] as $berkas) {
                if ($berkas === '.' || $berkas === '..') { continue; }
                $this->_unlink_private($dir, $berkas);
            }
            @rmdir($dir);
        }

        // --- Surat pengantar KKN/Magang (private_uploads/kemitraan/{id}/) ---
        $kkn = $this->db->select('id, file_surat_pengantar')
            ->where('user_id', $user_id)->where('file_surat_pengantar IS NOT NULL', NULL, FALSE)
            ->get('kkn_magang_pendaftaran')->result();
        foreach ($kkn as $row) {
            $this->_unlink_private(private_uploads_dir('kemitraan', $row->id), $row->file_surat_pengantar);
        }

        // --- Dokumen onboarding: KTP/SIUP/KTM (private_uploads/onboarding/{user_id}/) ---
        // usr_documents ikut terhapus lewat FK CASCADE, tapi FK tidak bisa
        // menghapus file di disk - jadi harus dibersihkan di sini.
        $onboarding = $this->db->select('file_name')
            ->get_where('usr_documents', ['user_id' => $user_id])->result();
        foreach ($onboarding as $row) {
            $this->_unlink_private(private_uploads_dir('onboarding', $user_id), $row->file_name);
        }

        // --- Buku kuota unggahan pengguna (poin 11.1): hanya penanda kosong, tanpa data pribadi ---
        $this->load->library('Upload_quota');
        $this->upload_quota->forget('u' . (int) $user_id);
    }

    /**
     * Hapus satu berkas di dalam direktori privat. basename() dipakai supaya
     * nilai dari DB yang memuat path tidak bisa menghapus file di luar direktori
     * yang dimaksud.
     */
    private function _unlink_private($dir, $file_name) {
        if ($dir === '' || empty($file_name)) { return; }
        $path = $dir . basename((string) $file_name);
        if (is_file($path)) { unlink($path); }
    }

    /** Download account-owned records without authentication secrets or file paths. */
    public function export_account_data($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1) { throw new InvalidArgumentException('Akun tidak valid.'); }
        $this->load->library('encryption_lib');
        $this->load->library('sensitive_buffer');
        $user = $this->db->get_where('usr_users', ['id' => $user_id])->row_array();
        if (!$user) { throw new RuntimeException('Akun tidak ditemukan.'); }
        $account_fields = ['id', 'email', 'username', 'name', 'phone', 'role',
            'status', 'nik', 'alamat', 'npwp', 'created_at', 'updated_at'];
        $account = array_intersect_key($user, array_flip($account_fields));
        $this->_prepare_export_record($account);
        $result = ['akun' => $account];
        $owned_tables = [
            'sf_profil_warga', 'sf_penilaian_perumahan', 'sf_housing_queue',
            'sf_citizen_profiles', 'sf_housing_assessments',
            'aduan', 'srp2_registrations', 'kkn_magang_pendaftaran',
            'forum_diskusi', 'forum_komentar', 'forum_janji_temu', 'usr_documents',
        ];
        foreach ($owned_tables as $table) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists('user_id', $table)) {
                continue;
            }
            $rows = $this->db->where('user_id', $user_id)->get($table)->result_array();
            foreach ($rows as &$row) { $this->_prepare_export_record($row); }
            unset($row);
            $result[$table] = $rows;
        }
        $this->sensitive_buffer->wipe($user);
        return $result;
    }

    private function _prepare_export_record(array &$record) {
        foreach (array_keys($record) as $field) {
            if ($field === 'nik' && is_string($record[$field]) && $record[$field] !== '') {
                $plain_nik = $this->encryption_lib->decrypt($record[$field]);
                if ($plain_nik === FALSE) { throw new RuntimeException('NIK tidak dapat dibaca untuk ekspor.'); }
                $record[$field] = $plain_nik;
            }
            if (preg_match('/(?:password|token|secret|session|_hash$|_lookup_hash$|private_path|stored_name|file_name|^file_|_file$|lampiran|verified_by|reviewed_by)/i', $field)) {
                unset($record[$field]);
                continue;
            }
            if (substr($field, -11) === '_ciphertext') {
                $name = substr($field, 0, -11);
                $plain = $record[$field] === NULL ? NULL
                    : $this->encryption_lib->decrypt($record[$field]);
                if ($plain === FALSE) {
                    throw new RuntimeException('Data terenkripsi tidak dapat dibaca untuk ekspor.');
                }
                $record[$name] = $plain;
                unset($record[$field]);
            }
        }
    }
    public function delete_user_account($user_id) {
        $user = $this->db->select('email, role')->get_where('usr_users', ['id' => $user_id])->row_array();
        if (!$user || !$this->db->table_exists('sys_jejak_audit')) { return FALSE; }
        // Di luar transaksi DB dengan sengaja - unlink() tidak bisa di-rollback,
        // jadi lebih aman dijalankan sebelum trans_start() daripada di dalamnya.
        $this->_cleanup_owned_files($user_id);

        // Poin 7.3: berkas KKN/Magang yang tertinggal dan DRAF penilaian warga ikut disapu
        // (lihat libraries/Data_erasure.php); sisa identitas di jejak audit disamarkan di bawah.
        $this->load->library('Data_erasure');
        $sapu = $this->data_erasure->sapu_berkas($user_id);

        $this->db->trans_start();
        $pseudonim = Data_erasure::pseudonim_surel($user['email'], (string) getenv('KPKP_DATA_PEPPER'));
        $this->db->insert('sys_jejak_audit', [
            'actor_id' => $user_id,
            'actor_email' => $pseudonim,
            'actor_role' => $user['role'],
            'aksi' => 'akun_dihapus',
            'objek_tipe' => 'usr_users',
            'objek_id' => (string) $user_id,
            'ringkasan' => 'Pemilik akun menghapus akun dan data terkait',
            'detail_json' => json_encode(['berkas_disapu' => $sapu['berkas'], 'draf_dihapus' => $sapu['draf']]),
            'ip' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Anonymize forum comments
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_komentar', [
            'user_id' => NULL,
            'nama_komentator' => 'Akun Dihapus',
            'role' => 'Warga'
        ]);

        // Anonymize forum discussions
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', [
            'user_id' => NULL,
            'nama_user' => 'Akun Dihapus',
            'email_user' => 'akun-dihapus@invalid',   // dulu surel pengirim tetap terbaca sesudah akun dihapus
        ]);

        // Delete user's likes
        $this->db->where('user_id', $user_id);
        $this->db->delete('forum_likes');

        // Samarkan surel akun ini di seluruh jejak audit (baris miliknya dan penyebutannya oleh admin).
        $this->data_erasure->samarkan_audit($user_id, $user['email']);

        // Finally, delete the user account
        $this->db->where('id', $user_id);
        $this->db->delete('usr_users');

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
