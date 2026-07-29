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

    /**
     * Check Rekam Data D1 — model, siklus status, scope wilayah.
     *
     *   php index.php migrate uji_rekam_data_d1
     *
     * Memakai tahun sentinel 2999 supaya tidak pernah bersinggungan dengan data
     * pelaporan sungguhan, dan menghapus seluruh jejaknya di `finally`.
     */
    public function uji_rekam_data_d1()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->model('Rekam_data_model', 'rd');

        $total  = 0;
        $failed = 0;
        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        // Tahun sentinel harus tetap di dalam rentang yang model anggap sah
        // (2020-2100), jadi 2099 — bukan 2999.
        $TAHUN = 2099;
        $kabs = $this->db->select('id')->order_by('id', 'ASC')->limit(2)
            ->get('kabupaten')->result_array();
        $aktor = $this->db->select('id')->order_by('id', 'ASC')->limit(1)
            ->get('usr_users')->row_array();
        if (count($kabs) < 2 || ! $aktor) {
            fwrite(STDERR, "Prasyarat gagal: butuh >=2 kabupaten dan >=1 pengguna.\n");
            exit(1);
        }
        $KAB   = (int) $kabs[0]['id'];
        $LAIN  = (int) $kabs[1]['id'];
        $AKTOR = (int) $aktor['id'];

        try {
            foreach (['rd_laporan', 'rd_perumahan_bagian', 'rd_perumahan_baris',
                'rd_perumahan_bnba', 'rd_kawasan_ringkasan', 'rd_kawasan_intervensi'] as $t) {
                $check($this->db->table_exists($t), "Tabel {$t} tersedia");
            }

            // --- periode: idempoten + validasi -----------------------------
            $a = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 6);
            $check( ! empty($a['success']) && ! empty($a['baru']), 'Draft perumahan Juni dibuat');
            $lap6 = (int) $a['laporan']['id'];

            $b = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 6);
            $check( ! empty($b['success']) && empty($b['baru'])
                && (int) $b['laporan']['id'] === $lap6, 'Periode sama tidak membuat laporan kedua');

            $check(empty($this->rd->ambil_atau_buat_draft('rusun', $KAB, $TAHUN, 6)['success']),
                'Domain tak dikenal ditolak');
            $check(empty($this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 13)['success']),
                'Bulan 13 ditolak');

            // --- gerbang + angka -------------------------------------------
            $check(empty($this->rd->simpan_baris($lap6, 'apbd_kabkota',
                ['pk_rtlh' => ['unit' => 1, 'anggaran' => 1]], $KAB)['success']),
                'Angka tanpa gerbang "Ada" ditolak');

            $check( ! empty($this->rd->simpan_bagian($lap6, 'apbd_kabkota', 1, $KAB)['success']),
                'Sumber dana dinyatakan Ada');
            $isi = [];
            foreach (['pk_rtlh', 'pb_rtlh', 'pb_backlog', 'pk_bencana', 'pb_bencana', 'pb_relokasi'] as $p) {
                $isi[$p] = ['unit' => 10, 'anggaran' => 250000000, 'keterangan' => 'diabaikan'];
            }
            $check( ! empty($this->rd->simpan_baris($lap6, 'apbd_kabkota', $isi, $KAB)['success']),
                'Enam program tersimpan');
            $this->rd->simpan_baris($lap6, 'apbd_kabkota', $isi, $KAB);
            $check($this->db->where('laporan_id', $lap6)->count_all_results('rd_perumahan_baris') === 6,
                'Simpan dua kali tidak menggandakan baris');
            $ket = $this->db->select('keterangan')->get_where('rd_perumahan_baris',
                ['laporan_id' => $lap6, 'sumber_dana' => 'apbd_kabkota', 'program' => 'pk_rtlh'])->row_array();
            $check($ket['keterangan'] === '', 'Keterangan dikosongkan pada sumber yang tidak berketerangan');

            $check(empty($this->rd->simpan_baris($lap6, 'apbd_kabkota',
                ['pk_rtlh' => ['unit' => -1, 'anggaran' => 0]], $KAB)['success']),
                'Unit negatif ditolak server');
            $check(empty($this->rd->simpan_baris($lap6, 'apbd_kabkota',
                ['pk_rtlh' => ['unit' => 'dua', 'anggaran' => 0]], $KAB)['success']),
                'Unit bukan angka ditolak server');

            $check(count($this->rd->sumber_belum_dijawab($lap6)) === 9,
                'Sembilan sumber dana masih belum dijawab');

            // --- scope wilayah ---------------------------------------------
            $check($this->rd->laporan($lap6, $LAIN) === NULL, 'Laporan kabupaten lain tidak terbaca');
            $check($this->rd->isi_laporan($lap6, $LAIN) === NULL, 'Isi laporan kabupaten lain tidak terbaca');
            $luar = $this->rd->simpan_bagian($lap6, 'csr', 1, $LAIN);
            $check(empty($luar['success']) && $luar['error'] === 'luar_scope',
                'Tulis dari kabupaten lain ditolak');

            // --- batal centang menyapu angka -------------------------------
            $this->rd->simpan_bagian($lap6, 'apbd_kabkota', 0, $KAB);
            $check($this->db->where('laporan_id', $lap6)->count_all_results('rd_perumahan_baris') === 0,
                'Batal centang sumber dana menyapu angkanya');
            $this->rd->simpan_bagian($lap6, 'apbd_kabkota', 1, $KAB);
            $this->rd->simpan_baris($lap6, 'apbd_kabkota', $isi, $KAB);

            // --- siklus status ---------------------------------------------
            $check(empty($this->rd->transisi($lap6, 'draft', 'perlu_perbaikan', $AKTOR, NULL, 'x')['success']),
                'Transisi draft -> perlu_perbaikan ditolak');
            $check(empty($this->rd->transisi($lap6, 'terkirim', 'perlu_perbaikan', $AKTOR, NULL, 'x')['success']),
                'Transisi dari status asal yang salah ditolak');
            $check( ! empty($this->rd->transisi($lap6, 'draft', 'terkirim', $AKTOR, $KAB)['success']),
                'Kirim laporan berhasil');

            $kunci = $this->rd->simpan_bagian($lap6, 'csr', 1, $KAB);
            $check(empty($kunci['success']) && $kunci['error'] === 'terkunci',
                'Laporan terkirim tidak bisa ditulis kabupaten');

            $check(empty($this->rd->transisi($lap6, 'terkirim', 'perlu_perbaikan', $AKTOR, NULL, '')['success']),
                'Minta perbaikan tanpa catatan ditolak');
            $check( ! empty($this->rd->transisi($lap6, 'terkirim', 'perlu_perbaikan', $AKTOR, NULL,
                'Angka BSPS belum diisi')['success']), 'Minta perbaikan dengan catatan berhasil');
            $lap = $this->rd->laporan($lap6);
            $check($lap['catatan_admin'] === 'Angka BSPS belum diisi' && $lap['reviewed_by'] !== NULL,
                'Catatan dan peninjau tercatat');
            $check( ! empty($this->rd->simpan_bagian($lap6, 'csr', 1, $KAB)['success']),
                'Status perlu_perbaikan bisa ditulis lagi');

            $this->rd->transisi($lap6, 'perlu_perbaikan', 'terkirim', $AKTOR, $KAB);
            $lap = $this->rd->laporan($lap6);
            $check($lap['catatan_admin'] === NULL && $lap['reviewed_at'] === NULL,
                'Kirim ulang membersihkan catatan dan jejak peninjauan');

            $check( ! empty($this->rd->terima($lap6, $AKTOR)['success']), 'Terima laporan berhasil');
            $check(empty($this->rd->terima($lap6, $AKTOR)['success']), 'Terima dua kali ditolak');

            // --- pewarisan --------------------------------------------------
            // Ekspektasi diikat ke ISI SUMBERNYA, bukan ke angka literal. Kalau
            // sebuah guard dilepas untuk uji balik dan tulisan lintas wilayah
            // jadi berhasil, jumlahnya bergeser — dengan angka literal satu
            // mutasi memerahkan beberapa uji sekaligus dan titik sebenarnya
            // jadi kabur (pelajaran D2, AGENTS.md §0e).
            $baris_sumber  = $this->db->where('laporan_id', $lap6)->count_all_results('rd_perumahan_baris');
            $bagian_sumber = $this->db->where('laporan_id', $lap6)->count_all_results('rd_perumahan_bagian');

            $c = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 7);
            $lap7 = (int) $c['laporan']['id'];
            $check((int) $c['diwarisi'] === $baris_sumber,
                "Draft Juli mewarisi {$baris_sumber} baris dari Juni");
            $check($this->db->where('laporan_id', $lap7)->count_all_results('rd_perumahan_bagian') === $bagian_sumber,
                "Jawaban gerbang ikut diwarisi ({$bagian_sumber})");
            $warisan = $this->db->select('unit')->get_where('rd_perumahan_baris',
                ['laporan_id' => $lap7, 'sumber_dana' => 'apbd_kabkota', 'program' => 'pk_rtlh'])->row_array();
            $check((int) $warisan['unit'] === 10, 'Angka warisan sama persis, tidak dinolkan');

            // --- kawasan ----------------------------------------------------
            $k = $this->rd->ambil_atau_buat_draft('kawasan', $KAB, $TAHUN, 6);
            $lapk = (int) $k['laporan']['id'];
            $check(empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 0], $KAB)['success']),
                'Tidak ada progres tanpa catatan ditolak');
            $check( ! empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 1, 'total_luas_ha' => 12.75], $KAB)['success']),
                'Ringkasan kawasan tersimpan');
            $check(empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 1, 'total_luas_ha' => -1], $KAB)['success']),
                'Luas negatif ditolak');

            $iv = [];
            foreach ([['drainase', 480000000, 60000000], ['air_minum', 212500000, 0],
                ['jalan_lingkungan', 675000000, 90000000]] as $n => $row) {
                $r = $this->rd->simpan_intervensi($lapk, [
                    'indikator' => $row[0], 'nama_kegiatan' => 'Kegiatan ' . ($n + 1),
                    'lokasi_teks' => 'RT 1 RW 1, Desa Uji, Kec. Uji',
                    'sumber_anggaran' => 'apbd_kabkota', 'volume' => 100.5,
                    'nilai_anggaran' => $row[1], 'nilai_padat_karya' => $row[2],
                ], NULL, $KAB);
                $iv[] = (int) ($r['intervensi_id'] ?? 0);
            }
            $check(count(array_filter($iv)) === 3, 'Tiga intervensi tersimpan');
            $check(empty($this->rd->simpan_intervensi($lapk, [
                'indikator' => 'tidak_ada', 'nama_kegiatan' => 'x', 'lokasi_teks' => 'x',
                'sumber_anggaran' => 'apbd_kabkota'], NULL, $KAB)['success']),
                'Indikator tak dikenal ditolak');

            $total_k = $this->rd->total_kawasan($lapk);
            $check($total_k['total_anggaran'] === 1367500000 && $total_k['total_padat_karya'] === 150000000,
                'Total anggaran & padat karya dihitung, bukan disimpan');

            $this->rd->hapus_intervensi($lapk, $iv[1], $KAB);
            $urutan = array_column($this->db->select('urutan')->order_by('urutan', 'ASC')
                ->get_where('rd_kawasan_intervensi', ['laporan_id' => $lapk])->result_array(), 'urutan');
            $check($urutan === ['1', '2'] || $urutan === [1, 2], 'Urutan dirapatkan setelah hapus');

            // --- rekap ------------------------------------------------------
            $this->rd->transisi($lapk, 'draft', 'terkirim', $AKTOR, $KAB);
            $rk = $this->rd->rekap('kawasan', $TAHUN, 6, $KAB);
            $check(count($rk) === 1 && (int) $rk[0]['jumlah_intervensi'] === 2,
                'Rekap kawasan satu periode');
            $check(count($this->rd->rekap('perumahan', $TAHUN, 6, $KAB)) === 6,
                'Rekap perumahan hanya periode yang diminta');
            $check($this->rd->rekap('perumahan', $TAHUN, 7, $KAB) === [],
                'Draft belum terkirim tidak masuk rekap');
            $check($this->rd->rekap('perumahan', $TAHUN, 6, $LAIN) === [],
                'Rekap ter-scope kabupaten');
        } finally {
            foreach ($this->db->select('id')->get_where('rd_laporan', ['tahun' => $TAHUN])->result_array() as $row) {
                $this->db->delete('rd_laporan', ['id' => (int) $row['id']]);
            }
        }

        $check($this->db->where('tahun', $TAHUN)->count_all_results('rd_laporan') === 0,
            'Data uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }
}
