<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Cocokkan login Google ke akun. Kembalian [baris, '1' akun lama | '0' akun baru], atau NULL
     * bila ditolak.
     *
     * - Ditolak bila Google tidak menyatakan email itu terverifikasi.
     * - Dicocokkan lewat google_id lebih dulu, baru email. Email yang sudah tertaut ke akun
     *   Google LAIN tidak ditautkan ulang.
     * - Akun berkata sandi yang belum pernah tertaut Google: email pendaftarannya tidak pernah
     *   dibuktikan (verifikasi email di pendaftaran hanya simulasi), jadi sandinya bisa milik
     *   orang lain. Google membuktikan pemilik email, maka saat penautan pertama sandi lama
     *   DIHAPUS, sesi aktif dicabut, dan pemilik wajib membuat sandi baru (onboarding bila
     *   profil belum lengkap, selain itu gerbang ganti sandi di Profil Saya).
     */
    public function check_google_user($data, $email_terverifikasi = FALSE) {
        if ($email_terverifikasi !== TRUE || empty($data['google_id']) || empty($data['email'])) {
            return NULL;
        }
        $sekarang = date('Y-m-d H:i:s');

        $user = $this->db->get_where('usr_akun', ['google_id' => $data['google_id']])->row_array();
        if ( ! $user) {
            $user = $this->db->get_where('usr_akun', ['email' => $data['email']])->row_array();
            if ($user && ! empty($user['google_id'])) {
                return NULL;
            }
        }

        if ( ! $user) {
            $this->db->insert('usr_akun', $data + ['email_verified_at' => $sekarang]);
            $new_user = $this->db->get_where('usr_akun', ['id' => $this->db->insert_id()]);
            return [$new_user->row_array(), '0'];
        }

        $ubah = [
            'google_id' => $data['google_id'],
            'foto_profil' => $data['foto_profil'],
            'email_verified_at' => $sekarang,
        ];
        if (empty($user['google_id']) && ! empty($user['kata_sandi'])) {
            $ubah += [
                'kata_sandi' => NULL,
                'sesi_aktif_hash' => NULL, 'sesi_aktif_id_hash' => NULL, 'sesi_aktif_at' => NULL,
                // Kedaluwarsa sekarang: gerbang sandi MY_Controller memaksa membuat sandi baru.
                'sandi_diganti_at' => NULL, 'sandi_kedaluwarsa_at' => $sekarang,
            ];
        }
        $this->db->where('id', (int) $user['id'])->update('usr_akun', $ubah);
        return [array_merge($user, $ubah), '1'];
    }

    public function update_user($user_id, $data) {
        $this->db->trans_start();
        
        $this->db->where('id', $user_id);
        $this->db->update('usr_akun', $data);

        // Fetch updated user to get the correct display name (username fallback to name)
        $user = $this->db->get_where('usr_akun', ['id' => $user_id])->row_array();
        $display_name = !empty($user['nama_pengguna']) ? $user['nama_pengguna'] : $user['nama'];

        // Sync with forum tables
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', ['nama_pengguna' => $display_name]);

        $this->db->where('user_id', $user_id);
        $this->db->update('forum_komentar', ['nama_komentator' => $display_name]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Hapus file fisik yang jadi yatim akibat FK CASCADE saat baris DB dihapus
     * di bawah (srp2_pengajuan->srp2_dokumen, kkn_magang_pendaftaran).
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

        // --- Dokumen SRP2 (private_uploads/srp2/{pengajuan_id}/) ---
        $registration_ids = array_column(
            $this->db->select('id')->get_where('srp2_pengajuan', ['user_id' => $user_id])->result_array(),
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
        // usr_dokumen ikut terhapus lewat FK CASCADE, tapi FK tidak bisa
        // menghapus file di disk - jadi harus dibersihkan di sini.
        $onboarding = $this->db->select('nama_berkas')
            ->get_where('usr_dokumen', ['user_id' => $user_id])->result();
        foreach ($onboarding as $row) {
            $this->_unlink_private(private_uploads_dir('onboarding', $user_id), $row->nama_berkas);
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
        $user = $this->db->get_where('usr_akun', ['id' => $user_id])->row_array();
        if (!$user) { throw new RuntimeException('Akun tidak ditemukan.'); }
        $account_fields = ['id', 'email', 'nama_pengguna', 'nama', 'no_hp', 'peran',
            'status', 'nik', 'alamat', 'npwp', 'created_at', 'updated_at'];
        $account = array_intersect_key($user, array_flip($account_fields));
        $this->_prepare_export_record($account);
        $result = ['akun' => $account];
        $owned_tables = [
            'sf_profil_warga', 'sf_penilaian_perumahan', 'sf_antrean_pengajuan',
            'sf_citizen_profiles', 'sf_housing_assessments',
            'aduan', 'srp2_pengajuan', 'kkn_magang_pendaftaran',
            'forum_diskusi', 'forum_komentar', 'forum_janji_temu', 'usr_dokumen',
            'sf_data_simperum',
        ];
        foreach ($owned_tables as $table) {
            if (!$this->db->table_exists($table) || !$this->db->field_exists('user_id', $table)) {
                continue;
            }
            $this->db->where('user_id', $user_id);
            if ($table === 'sf_penilaian_perumahan') {
                // Draf yang dilepas saat NIK dipindahkan bukan lagi milik akun ini (isinya identitas pemilik NIK).
                $this->db->where("NOT (status = 'superseded' AND submitted_at IS NULL)", NULL, FALSE);
            }
            $rows = $this->db->get($table)->result_array();
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
            if (preg_match('/(?:password|kata_sandi|token|secret|session|sesi_|_hash$|_lookup_hash$|path_privat|path_berkas|nama_simpan|nama_berkas|file_name|^file_|_file$|lampiran|verified_by|reviewed_by)/i', $field)) {
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
        $user = $this->db->select('email, peran')->get_where('usr_akun', ['id' => $user_id])->row_array();
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
            'pelaku_id' => $user_id,
            'pelaku_email' => $pseudonim,
            'pelaku_peran' => $user['peran'],
            'aksi' => 'akun_dihapus',
            'objek_tipe' => 'usr_akun',
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
            'peran' => 'Warga'
        ]);

        // Anonymize forum discussions
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', [
            'user_id' => NULL,
            'nama_pengguna' => 'Akun Dihapus',
            'email_pengguna' => 'akun-dihapus@invalid',   // dulu surel pengirim tetap terbaca sesudah akun dihapus
        ]);

        // Delete user's likes
        $this->db->where('user_id', $user_id);
        $this->db->delete('forum_suka');

        // Samarkan surel akun ini di seluruh jejak audit (baris miliknya dan penyebutannya oleh admin).
        $this->data_erasure->samarkan_audit($user_id, $user['email']);

        // Finally, delete the user account
        $this->db->where('id', $user_id);
        $this->db->delete('usr_akun');

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
