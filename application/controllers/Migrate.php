<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Runner migrasi skema DB. Hanya bisa diakses dari CLI atau localhost —
 * dipakai untuk menyamakan skema lokal & staging lewat application/migrations/,
 * menggantikan kebiasaan lama jalankan file .sql di docs/engineering/ manual satu-satu.
 */
class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        if ( ! $this->input->is_cli_request() && ! in_array($this->input->ip_address(), array('127.0.0.1', '::1')))
        {
            show_404();
        }

        $this->load->library('migration');
    }

    public function index()
    {
        $result = $this->migration->latest();

        if ($result === FALSE)
        {
            echo 'Migrasi gagal: '.$this->migration->error_string()."\n";
            return;
        }

        echo "Migrasi sukses, versi skema sekarang: {$result}\n";
    }

    /**
     * Diagnostik BACA SAJA — jalankan SEBELUM index() di lingkungan mana pun
     * yang keadaan migrasinya belum pasti (production khususnya). CI
     * migration->version() menandai migrasi sebagai berhasil TANPA memeriksa
     * nilai balik query di dalamnya (lihat system/libraries/Migration.php
     * baris ~302-309), jadi kalau tabel migrations ternyata tidak ada sama
     * sekali, latest() akan mencoba ulang migrasi 1..N dari nol dan bisa
     * menandai sukses walau CREATE TABLE-nya gagal senyap karena db_debug
     * mati di production. Method ini tidak mengubah apa pun — dipertahankan
     * sebagai alat baku, bukan sekali pakai, karena T6 dan role berikutnya
     * akan menghadapi masalah yang sama.
     */
    public function status()
    {
        $tables = $this->db->list_tables();
        echo 'Total tabel: '.count($tables)."\n";
        echo 'migrations: '.(in_array('migrations', $tables) ? 'ADA' : 'TIDAK ADA')."\n";

        if (in_array('migrations', $tables)) {
            $row = $this->db->order_by('version', 'DESC')->limit(1)->get('migrations')->row();
            echo 'Versi migrasi tercatat: '.($row->version ?? 'NONE')."\n";
        }

        foreach ([
            'sys_rate_limits',
            'srp2_registrations',
            'srp2_certified_developers',
            'sf_profil_warga',
            'sf_rekaman_simperum',
            'sf_penilaian_perumahan',
            'sf_berkas_penilaian',
            'sf_rekomendasi_penilaian',
        ] as $t) {
            echo $t.': '.(in_array($t, $tables) ? 'ADA' : 'TIDAK ADA')."\n";
        }

        if (in_array('srp2_registrations', $tables)) {
            echo 'srp2_registrations.certified_developer_id: '.
                ($this->db->field_exists('certified_developer_id', 'srp2_registrations') ? 'ADA' : 'TIDAK ADA')."\n";
        }
    }

    /**
     * Check mutasi R1 khusus lokal/DB uji. Membuat data sintetis lalu selalu
     * membersihkannya; sengaja bukan endpoint aplikasi warga.
     */
    public function uji_warga_r1()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->model('Housing_assessment_model');
        $this->load->library('encryption_lib');

        $total = 0;
        $failed = 0;
        $user_id = NULL;
        $snapshot_id = NULL;
        $other_snapshot_id = NULL;
        $assessment_id = NULL;
        $stamp = time();

        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        try {
            foreach ([
                'sf_profil_warga',
                'sf_rekaman_simperum',
                'sf_penilaian_perumahan',
                'sf_berkas_penilaian',
                'sf_rekomendasi_penilaian',
            ] as $table) {
                $check($this->db->table_exists($table), "Tabel {$table} tersedia");
            }

            $this->db->insert('usr_users', [
                'email' => "uji_warga_r1_{$stamp}@example.test",
                'password' => password_hash('UjiWargaR1!', PASSWORD_BCRYPT),
                'name' => 'Warga Simulasi R1',
                'username' => "uji_warga_r1_{$stamp}",
                'role' => 'warga',
                'status' => 'active',
                'profile_completed' => 1,
                'kabupaten_id' => 3374,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $user_id = (int) $this->db->insert_id();
            $check($user_id > 0, 'Akun sintetis dibuat');

            $profile = $this->Housing_assessment_model->save_profile($user_id, [
                'source_mode' => 'simulation',
                'nik' => '0000000000000001',
                'family_card_number' => '0000000000001001',
                'full_name' => 'Warga Simulasi R1',
                'address' => 'Alamat Sintetis R1',
                'birth_date' => '1980-01-01',
                'gender_code' => 'male',
                'welfare_decile' => 2,
            ], ['full_name' => ['source' => 'simulation']]);
            $check(! empty($profile['success']), 'Model menyimpan profil');

            $profile_row = empty($profile['profile_id']) ? NULL : $this->db
                ->get_where('sf_profil_warga', ['id' => (int) $profile['profile_id']])
                ->row_array();
            $check(
                $profile_row
                && $profile_row['nik_ciphertext'] !== '0000000000000001'
                && $this->encryption_lib->decrypt($profile_row['nik_ciphertext']) === '0000000000000001',
                'NIK tersimpan terenkripsi dan dapat didekripsi'
            );
            $check(
                $profile_row
                && strpos((string) $profile_row['full_name_ciphertext'], 'Warga Simulasi') === FALSE,
                'Nama tidak tersimpan plaintext'
            );

            $snapshot = $this->Housing_assessment_model->store_source_snapshot(
                '0000000000000001',
                'simulation',
                'SIM-01',
                'found',
                ['fixture_id' => 'SIM-01', 'synthetic' => TRUE],
                ['api_version' => 'simulation-v1', 'requested_by' => $user_id]
            );
            $snapshot_id = empty($snapshot['snapshot_id']) ? NULL : (int) $snapshot['snapshot_id'];
            $check(! empty($snapshot['success']), 'Snapshot simulasi tersimpan');

            $snapshot_row = $snapshot_id ? $this->db
                ->get_where('sf_rekaman_simperum', ['id' => $snapshot_id])
                ->row_array() : NULL;
            $check(
                $snapshot_row
                && strpos((string) $snapshot_row['payload_ciphertext'], 'SIM-01') === FALSE
                && $this->encryption_lib->is_encrypted($snapshot_row['payload_ciphertext']),
                'Payload snapshot terenkripsi'
            );

            $original_key = getenv('KPKP_DATA_KEY');
            putenv('KPKP_DATA_KEY=');
            try {
                $no_key = $this->Housing_assessment_model->store_source_snapshot(
                    '0000000000000097',
                    'simulation',
                    'SIM-97',
                    'not_found',
                    NULL
                );
            } finally {
                putenv('KPKP_DATA_KEY=' . $original_key);
            }
            $check(
                empty($no_key['success']) && ($no_key['code'] ?? '') === 'encryption_unavailable',
                'Penulisan ditolak saat kunci enkripsi tidak tersedia'
            );

            $other_snapshot = $this->Housing_assessment_model->store_source_snapshot(
                '0000000000000098',
                'simulation',
                'SIM-98',
                'not_found',
                NULL
            );
            $other_snapshot_id = empty($other_snapshot['snapshot_id'])
                ? NULL : (int) $other_snapshot['snapshot_id'];
            $mismatched_draft = $this->Housing_assessment_model->create_draft(
                $user_id,
                (int) ($profile['profile_id'] ?? 0),
                3374,
                'existing_house',
                'simulation',
                $other_snapshot_id
            );
            $check(
                empty($mismatched_draft['success'])
                && ($mismatched_draft['code'] ?? '') === 'snapshot_invalid',
                'Snapshot dengan NIK berbeda ditolak'
            );

            $draft = $this->Housing_assessment_model->create_draft(
                $user_id,
                (int) ($profile['profile_id'] ?? 0),
                3374,
                'existing_house',
                'simulation',
                $snapshot_id
            );
            $assessment_id = empty($draft['assessment_id']) ? NULL : (int) $draft['assessment_id'];
            $check(! empty($draft['success']), 'Draft assessment dibuat');

            $first_update = $this->Housing_assessment_model->update_owned_draft(
                $assessment_id,
                $user_id,
                0,
                ['current_step' => 'housing', 'housing_status_code' => 'owned']
            );
            $check(! empty($first_update['success']) && (int) $first_update['lock_version'] === 1,
                'Update pertama dengan lock_version 0 berhasil');

            $stale_update = $this->Housing_assessment_model->update_owned_draft(
                $assessment_id,
                $user_id,
                0,
                ['current_step' => 'structure']
            );
            $check(
                empty($stale_update['success']) && ($stale_update['code'] ?? '') === 'stale_or_not_owned',
                'Update kedua dengan lock lama ditolak'
            );

            $wrong_owner = $this->Housing_assessment_model->get_owned_assessment(
                $assessment_id,
                $user_id + 999999
            );
            $check($wrong_owner === NULL, 'Assessment tidak terbaca sebagai user lain');
        } finally {
            if ($assessment_id) {
                $this->db->delete('sf_penilaian_perumahan', ['id' => $assessment_id]);
            }
            if ($snapshot_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $snapshot_id]);
            }
            if ($other_snapshot_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $other_snapshot_id]);
            }
            if ($user_id) {
                $this->db->delete('usr_users', ['id' => $user_id]);
            }
        }

        $leftovers = $this->db->like('email', 'uji_warga_r1_', 'after')
            ->count_all_results('usr_users');
        $check($leftovers === 0, 'Data uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    /**
     * Check gateway/cache R2 tanpa HTTP dan tanpa data permanen.
     */
    public function uji_warga_r2()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->library('simperum_gateway');
        $this->load->library('encryption_lib');

        $cases = [
            ['0000000000000001', '1980-01-01', 'found'],
            ['0000000000000098', '2000-01-01', 'not_found'],
            ['0000000000000099', '2000-01-01', 'error'],
        ];
        $hashes = array_map(function ($case) {
            return $this->encryption_lib->deterministic_hash($case[0]);
        }, $cases);
        $this->db->where_in('nik_lookup_hash', $hashes)->delete('sf_rekaman_simperum');

        $total = 0;
        $failed = 0;
        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        try {
            foreach ($cases as [$nik, $birth_date, $expected]) {
                $first = $this->simperum_gateway->lookup($nik, $birth_date);
                $second = $this->simperum_gateway->lookup($nik, $birth_date);
                $count = $this->db
                    ->where('nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
                    ->count_all_results('sf_rekaman_simperum');

                $check($first['status'] === $expected, "{$expected}: respons pertama benar");
                $check(empty($first['data']['cache_hit']), "{$expected}: respons pertama bukan cache");
                $check(! empty($second['data']['cache_hit']), "{$expected}: respons kedua dari cache");
                $check($count === 1, "{$expected}: hanya satu snapshot dibuat");
            }

            $public_json = json_encode($this->simperum_gateway->lookup(
                '0000000000000001',
                '1980-01-01'
            ));
            $check(
                strpos($public_json, 'Warga Simulasi RTLH') === FALSE
                && strpos($public_json, 'Alamat Sintetis Kota Semarang') === FALSE
                && strpos($public_json, '0000000000000001') === FALSE,
                'Respons gateway tidak memuat raw PII'
            );
        } finally {
            $this->db->where_in('nik_lookup_hash', $hashes)->delete('sf_rekaman_simperum');
        }

        $leftovers = $this->db->where_in('nik_lookup_hash', $hashes)
            ->count_all_results('sf_rekaman_simperum');
        $check($leftovers === 0, 'Snapshot uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    public function simperum_probe($action = 'lookup')
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $nik = '0000000000000001';
        $this->load->library('encryption_lib');
        $hash = $this->encryption_lib->deterministic_hash($nik);
        if ($action === 'reset') {
            $this->db->delete('sf_rekaman_simperum', ['nik_lookup_hash' => $hash]);
            echo "reset\n";
            return;
        }
        if ($action === 'count') {
            echo $this->db->where('nik_lookup_hash', $hash)
                ->count_all_results('sf_rekaman_simperum') . "\n";
            return;
        }

        $this->load->library('simperum_gateway');
        echo json_encode($this->simperum_gateway->lookup($nik, '1980-01-01')) . "\n";
    }
}
