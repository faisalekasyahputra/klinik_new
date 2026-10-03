<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Simperum_gateway {

    private $CI;
    private $mode;

    /** Butir 5: benar HANYA selama satu pemanggilan lookup() yang memintanya. */
    private $lewati_tgl_lahir = FALSE;
    private $fixture_path;
    private $base_url;
    private $public_key;
    private $private_key;
    private $connect_timeout;
    private $timeout;
    private $internal_profile = [];

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('simperum', TRUE);
        $this->CI->load->model('Housing_assessment_model');
        $this->mode = $this->CI->config->item('simperum_mode', 'simperum');
        $this->fixture_path = $this->CI->config->item('simperum_fixture_path', 'simperum');
        $this->base_url = $this->CI->config->item('simperum_base_url', 'simperum');
        $this->public_key = $this->CI->config->item('simperum_public_key', 'simperum');
        $this->private_key = $this->CI->config->item('simperum_private_key', 'simperum');
        $this->connect_timeout = (int) $this->CI->config->item('simperum_connect_timeout', 'simperum');
        $this->timeout = (int) $this->CI->config->item('simperum_timeout', 'simperum');
    }

    /**
     * @param int|null $requested_by Akun yang ingin MENGIKAT NIK ini dan melihat datanya.
     * @param bool $tanpa_tgl_lahir Lewati pengaman tanggal lahir; hanya berlaku TANPA $requested_by.
     *
     * Dua bentuk pemakaian, dan batasnya ditegakkan di sini, bukan di pemanggil:
     *
     * - Dengan $requested_by (Warga::lookup): BUKTI KEPEMILIKAN wajib (keputusan pemilik produk
     *   3 Okt 2026). Nama lengkap akun dan tanggal lahir dicocokkan dengan data sumber sebelum
     *   profil diikat atau data apa pun dikembalikan; percobaan gagal dibatasi per akun dan per
     *   NIK (`verifikasi_nik`); NIK milik akun lain ditolak sebelum pencocokan. $tanpa_tgl_lahir
     *   diabaikan.
     * - Tanpa $requested_by (Cek_Rtlh, cek anonim, alat CLI): tidak mengikat apa pun dan hanya
     *   mengembalikan status pencarian serta status intervensi. Bendera tanggal lahir di sini
     *   sisa butir 5 putaran 2 (dinas mencabut tanggal lahir dari layar Cek Data Rumah).
     */
    public function lookup($nik, $birth_date, $requested_by = NULL, $tanpa_tgl_lahir = FALSE)
    {
        $this->internal_profile = [];
        $nik = preg_replace('/\D+/', '', (string) $nik);
        $birth_date = trim((string) $birth_date);
        if ((int) $requested_by > 0) {
            $tanpa_tgl_lahir = FALSE;
        }
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            return $this->response('invalid', 'NIK tidak valid.');
        }
        if ( ! $tanpa_tgl_lahir && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
            return $this->response('invalid', 'NIK atau tanggal lahir tidak valid.');
        }
        $this->lewati_tgl_lahir = (bool) $tanpa_tgl_lahir;
        if ($this->mode === 'api' && ! $this->api_configured()) {
            return $this->response('error', 'Koneksi SIMPERUM belum dikonfigurasi.', [], 'api_not_configured');
        }

        $cached = $this->CI->Housing_assessment_model->get_active_source_snapshot($nik, $this->mode);
        if ($cached) {
            return $this->from_snapshot($cached, $birth_date, TRUE, $requested_by);
        }

        $lock_name = 'simperum:' . hash('sha256', $nik);
        $locked = (int) $this->CI->db
            ->query('SELECT GET_LOCK(?, 3) AS acquired', [$lock_name])
            ->row()->acquired === 1;
        if ( ! $locked) {
            return $this->response('error', 'Sumber data sedang diproses. Silakan coba lagi.', [], 'lookup_busy');
        }

        try {
            $cached = $this->CI->Housing_assessment_model->get_active_source_snapshot($nik, $this->mode);
            if ($cached) {
                return $this->from_snapshot($cached, $birth_date, TRUE, $requested_by);
            }

            $payload = $this->mode === 'api'
                ? $this->load_api($nik)
                : $this->load_fixture($nik);
            $status = $payload['status_respons'] ?? 'error';
            $stored = $this->CI->Housing_assessment_model->store_source_snapshot(
                $nik,
                $this->mode,
                $payload['kunci_rekaman_sumber'] ?? $payload['fixture_id'] ?? NULL,
                $status,
                $payload,
                [
                    'versi_api' => $payload['versi_api'] ?? ($this->mode === 'api' ? 'simperum-rtlh-v1' : 'simulation-v1'),
                    'http_status' => $payload['http_status'] ?? ($status === 'error' ? 503 : 200),
                    'error_code' => $payload['error_code'] ?? NULL,
                    'requested_by' => $requested_by,
                ]
            );
            if (empty($stored['success'])) {
                return $this->response('error', 'Data belum dapat disimpan dengan aman.', [], $stored['code'] ?? 'write_failed');
            }

            $payload['id'] = (int) $stored['rekaman_id'];
            $snapshot = [
                'id' => (int) $stored['rekaman_id'],
                'status_respons' => $status,
                'kunci_rekaman_sumber' => $payload['kunci_rekaman_sumber'] ?? $payload['fixture_id'] ?? NULL,
                'payload' => $payload,
            ];
            return $this->from_snapshot($snapshot, $birth_date, FALSE, $requested_by);
        } finally {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
        }
    }

    /**
     * GET SEGAR untuk penyegaran mingguan (Simperum_segarkan, CLI). Melewati cache snapshot,
     * menyimpan snapshot baru, lalu memperbarui cermin sf_data_simperum. SENGAJA tidak memanggil
     * from_snapshot(): sf_profil_warga dan draft warga (termasuk koreksinya) tidak disentuh.
     * Hanya GET; hanya NIK yang terikat ke akun warga $user_id.
     *
     * @return string found | not_found | error | unbound | busy | invalid | api_not_configured
     */
    public function segarkan($nik, $user_id)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            return 'invalid';
        }
        if ($this->mode === 'api' && ! $this->api_configured()) {
            return 'api_not_configured';
        }
        $model = $this->CI->Housing_assessment_model;
        if ( ! $model->nik_terikat_akun($user_id, $nik)) {
            // Baris akun yang tidak lagi memegang NIK ini ikut dilepas di sini.
            $model->cermin_data_simperum($user_id, $nik, NULL, 'not_found', [], $this->mode);
            return 'unbound';
        }

        $lock_name = 'simperum:' . hash('sha256', $nik);
        if ((int) $this->CI->db->query('SELECT GET_LOCK(?, 3) AS acquired', [$lock_name])->row()->acquired !== 1) {
            return 'busy';
        }
        try {
            $payload = $this->mode === 'api' ? $this->load_api($nik) : $this->load_fixture($nik);
            $status = $payload['status_respons'] ?? 'error';
            if ( ! in_array($status, ['found', 'not_found'], TRUE)) {
                /* Galat TIDAK disimpan sebagai snapshot: snapshot aktif terbaru menang di
                   get_active_source_snapshot(), jadi galat 15 menit akan menutupi hasil found yang
                   masih berlaku. Cermin hanya mencatat galat bila belum ada baris (dicoba lagi). */
                $model->cermin_data_simperum($user_id, $nik, NULL, 'error', [], $this->mode);
                return 'error';
            }
            $stored = $model->store_source_snapshot(
                $nik,
                $this->mode,
                $payload['kunci_rekaman_sumber'] ?? $payload['fixture_id'] ?? NULL,
                $status,
                $payload,
                [
                    'versi_api' => $payload['versi_api'] ?? ($this->mode === 'api' ? 'simperum-rtlh-v1' : 'simulation-v1'),
                    'http_status' => $payload['http_status'] ?? ($status === 'error' ? 503 : 200),
                    'error_code' => $payload['error_code'] ?? NULL,
                ]
            );
            if (empty($stored['success'])) {
                return 'error';
            }
            $model->cermin_data_simperum($user_id, $nik, $stored['rekaman_id'], $status, $payload, $this->mode);
            return $status;
        } finally {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
        }
    }

    /**
     * Cermin sf_data_simperum, dipanggil from_snapshot() HANYA sesudah kepemilikan NIK terverifikasi.
     * Model juga menolak NIK yang belum terverifikasi untuk akun itu (nik_terikat_akun).
     * Kegagalan cermin tidak boleh menggagalkan pencarian warga.
     */
    private function cermin($nik, $requested_by, array $snapshot)
    {
        if ((int) $requested_by < 1) {
            return;
        }
        try {
            $this->CI->Housing_assessment_model->cermin_data_simperum(
                (int) $requested_by,
                $nik,
                $snapshot['id'] ?? NULL,
                $snapshot['status_respons'] ?? 'error',
                (array) ($snapshot['payload'] ?? []),
                $this->mode,
                $snapshot['fetched_at'] ?? NULL
            );
        } catch (\Throwable $e) {
            log_message('error', 'Simperum_gateway: cermin data gagal: ' . $e->getMessage());
        }
    }

    private function load_fixture($nik)
    {
        $index = [
            '0000000000000001' => 'SIM-01',
            '0000000000000002' => 'SIM-02',
            '0000000000000003' => 'SIM-03',
            '0000000000000004' => 'SIM-04',
            '0000000000000005' => 'SIM-05',
            '0000000000000098' => 'SIM-98',
            '0000000000000099' => 'SIM-99',
        ];
        if (isset($index[$nik])) {
            return $this->load_fixture_file($index[$nik]);
        }

        /* Fixture berbentuk respons GetDataRTLH MENTAH (26 Sep 2026), dilewatkan ke
           map_api_response() yang sama dengan production supaya uji lokal merasakan
           prefill asli: tanpa tanggal lahir (dicocokkan ke digit NIK), banyak baris
           per NIK, kode tak dikenal. NIK berawalan 3399 (kabupaten yang tidak ada),
           jadi dijamin bukan NIK warga. Jangan pernah pakai NIK asli di sini. */
        $api = [
            '3399991508850001' => 'API-01', // lengkap, milik sendiri, belum diintervensi, air tidak layak
            '3399995506900002' => 'API-02', // tiga baris: pilih 2023 BSPS, buang baris NIK terpotong
            '3399990101700003' => 'API-03', // kode 6 dinas + disposisi, tahun lahir kosong, Cilacap
        ];
        if (isset($api[$nik])) {
            $body = @file_get_contents($this->fixture_path . DIRECTORY_SEPARATOR . $api[$nik] . '.json');
            $payload = $this->map_api_response($nik, ['http_status' => 200, 'body' => $body, 'curl_errno' => 0]);
            $payload['versi_api'] = 'simulation-api-v1';
            return $payload;
        }

        /* Permintaan user 23 Agt 2026: sambungkan pencarian NIK di mode
           simulasi ke tabel dummy_simperum_rtlh (dummy_simperum.sql di
           root proyek) - SIMPERUM sungguhan sedang tidak bisa diakses,
           jadi tabel ini berperan sebagai pengganti data sumber untuk tes
           lokal, memakai NIK APAPUN (bukan cuma 7 NIK tetap 0000..0001..99
           di atas). Dicoba SETELAH index tetap di atas (supaya 7 NIK
           skenario khusus itu - termasuk SIM-98/99 yang sengaja mensimulasikan
           not_found/error - tetap berperilaku PERSIS seperti sebelumnya,
           tidak bisa ketiban baris dummy_simperum_rtlh manapun), dan
           SEBELUM jatuh ke SIM-98 (not_found) sebagai keadaan akhir. */
        $dummy = $this->load_dummy_table_record($nik);
        if ($dummy !== NULL) {
            return $dummy;
        }

        return $this->load_fixture_file('SIM-98');
    }

    private function load_fixture_file($id)
    {
        $json = file_get_contents($this->fixture_path . DIRECTORY_SEPARATOR . $id . '.json');
        $fixture = json_decode($json, TRUE);
        return is_array($fixture) ? $fixture : [
            'fixture_id' => $id,
            'synthetic' => TRUE,
            'status_respons' => 'error',
            'error_code' => 'fixture_invalid',
        ];
    }

    /**
     * Cari NIK di dummy_simperum_rtlh (tabel dummy LOKAL, bukan bagian
     * migrasi resmi - lihat dummy_simperum.sql) lalu petakan lewat
     * normalize_api_record() yang SAMA dipakai jalur API sungguhan, supaya
     * logika pemetaan kode (AtapID/Pekerjaan/dst -> *_code) tidak
     * diduplikasi di dua tempat yang bisa saling menyimpang.
     *
     * Tabel ini TIDAK WAJIB ada - kalau environment ini belum pernah
     * menjalankan dummy_simperum.sql (mis. clone baru), query akan
     * gagal dan method ini dengan tenang mengembalikan NULL, jatuh ke
     * perilaku lama (SIM-98/not_found), bukan error 500.
     *
     * @return array|null null kalau tabel tidak ada ATAU NIK tidak ketemu
     *                     di dalamnya - kedua kasus itu sengaja diperlakukan
     *                     SAMA oleh pemanggil (lanjut ke SIM-98).
     */
    private function load_dummy_table_record($nik)
    {
        /* Dicek dulu: dengan db_debug aktif (non-production) query ke tabel yang tidak ada
           menghentikan permintaan lewat halaman galat, bukan melempar pengecualian. */
        if ( ! $this->CI->db->table_exists('dummy_simperum_rtlh')) {
            return NULL;
        }
        try {
            $row = $this->CI->db->get_where('dummy_simperum_rtlh', ['nik' => $nik])->row_array();
        } catch (\Throwable $e) {
            return NULL;
        }
        if ( ! $row) {
            return NULL;
        }

        // snake_case (nama kolom tabel) -> PascalCase (nama field API asli
        // di SIMPERUM API.pdf) - normalize_api_record() dibangun untuk
        // konsumsi bentuk PascalCase itu (dipetakan langsung dari body
        // JSON respons API sungguhan), jadi baris tabel diterjemahkan balik
        // ke bentuk itu di sini, bukan menulis pemetaan kode kedua.
        $record = [
            'IDBDT' => $row['idbdt'], 'TahunIntervensi' => $row['tahun_intervensi'],
            'SumberDanaID' => $row['sumber_dana_id'], 'NIK' => $row['nik'], 'Nama' => $row['nama'],
            'Alamat' => $row['alamat'], 'KodeDagri' => $row['kode_dagri'], 'AtapID' => $row['atap_id'],
            'LantaiID' => $row['lantai_id'], 'DindingID' => $row['dinding_id'], 'GeoLat' => $row['geo_lat'],
            'GeoLng' => $row['geo_lng'], 'JenisKelamin' => $row['jenis_kelamin'], 'TahunLahir' => $row['tahun_lahir'],
            'Pendidikan' => $row['pendidikan'], 'Pekerjaan' => $row['pekerjaan'], 'Penghasilan' => $row['penghasilan'],
            'MampuSwadaya' => $row['mampu_swadaya'], 'KepemilikanRumah' => $row['kepemilikan_rumah'],
            'KepemilikanLahan' => $row['kepemilikan_lahan'], 'TanahLain' => $row['tanah_lain'],
            'RumahLain' => $row['rumah_lain'], 'LuasRumah' => $row['luas_rumah'], 'JmlPenghuni' => $row['jml_penghuni'],
            'JmlKK' => $row['jml_kk'], 'KawasanPerumahan' => $row['kawasan_perumahan'],
            'AdaPondasi' => $row['ada_pondasi'], 'KondisiKolom' => $row['kondisi_kolom'],
            'KondisiBalok' => $row['kondisi_balok'], 'KondisiRangka' => $row['kondisi_rangka'],
            'KondisiLantai' => $row['kondisi_lantai'], 'KondisiDinding' => $row['kondisi_dinding'],
            'KondisiAtap' => $row['kondisi_atap'], 'AdaJendela' => $row['ada_jendela'],
            'AdaVentilasi' => $row['ada_ventilasi'], 'SumberAir' => $row['sumber_air'],
            'JarakSepticTank' => $row['jarak_septic_tank'], 'Penerangan' => $row['penerangan'],
        ];

        $payload = $this->normalize_api_record($nik, $record, ['Message' => 'Data dummy lokal', 'Type' => 'array'], 200);
        if (is_array($payload)) {
            $payload['versi_api'] = 'dummy-table-v1';
        }
        return $payload;
    }

    private function api_configured()
    {
        $parts = parse_url((string) $this->base_url);
        return ($parts['scheme'] ?? '') === 'https'
            && ! empty($parts['host'])
            && trim((string) $this->public_key) !== ''
            && trim((string) $this->private_key) !== '';
    }

    private function load_api($nik)
    {
        $command = 'GetDataRTLH?NIK=' . rawurlencode($nik);
        $result = $this->request_api($command, $this->authorization($command));
        return $this->map_api_response($nik, $result);
    }

    private function authorization($command)
    {
        return md5($command . $this->private_key) . '.' . $this->public_key;
    }

    private function request_api($command, $authorization)
    {
        if ( ! function_exists('curl_init')) {
            return ['http_status' => 0, 'body' => NULL, 'curl_errno' => -1];
        }

        $result = ['http_status' => 0, 'body' => NULL, 'curl_errno' => 0];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $curl = curl_init($this->base_url . $command);
            $options = [
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Authorization: ' . $authorization,
                ],
                CURLOPT_CONNECTTIMEOUT => $this->connect_timeout,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_USERAGENT => 'Klinik-PKP/1.0 SIMPERUM-Gateway',
            ];
            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
            }
            curl_setopt_array($curl, $options);
            curl_setopt_array($curl, transport_curl_options()); // + TLS 1.2 ke atas (poin 8.2)
            $body = curl_exec($curl);
            $result = [
                'http_status' => (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
                'body' => is_string($body) ? $body : NULL,
                'curl_errno' => curl_errno($curl),
            ];
            curl_close($curl);

            $retryable = $result['curl_errno'] !== 0 || $result['http_status'] >= 500;
            if ( ! $retryable || $attempt === 1) {
                break;
            }
            usleep(200000);
        }
        return $result;
    }

    private function map_api_response($nik, array $result)
    {
        $http_status = (int) ($result['http_status'] ?? 0);
        $curl_errno = (int) ($result['curl_errno'] ?? 0);
        if ($curl_errno !== 0 || ! is_string($result['body'] ?? NULL)) {
            return $this->api_error('api_transport_error', $http_status);
        }
        if (in_array($http_status, [401, 403], TRUE)) {
            return $this->api_error('api_auth_failed', $http_status);
        }
        if ($http_status === 429) {
            return $this->api_error('api_rate_limited', $http_status);
        }
        if ($http_status < 200 || $http_status >= 300) {
            return $this->api_error('api_http_error', $http_status);
        }

        $response = json_decode($result['body'], TRUE);
        if ( ! is_array($response)) {
            return $this->api_error('api_invalid_json', $http_status);
        }
        $success = filter_var(
            $response['Success'] ?? FALSE,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) === TRUE;
        $records = is_array($response['Data'] ?? NULL) ? $response['Data'] : [];
        if ( ! $success) {
            return $this->api_error('api_rejected', $http_status);
        }
        if (empty($records)) {
            return [
                'status_respons' => 'not_found',
                'versi_api' => 'simperum-rtlh-v1',
                'http_status' => $http_status,
                'source' => ['message' => $response['Message'] ?? NULL],
            ];
        }

        $matches = array_values(array_filter($records, static function ($record) use ($nik) {
            return is_array($record)
                && preg_replace('/\D+/', '', (string) ($record['NIK'] ?? '')) === $nik;
        }));
        if (empty($matches)) {
            return $this->api_error('api_nik_mismatch', $http_status);
        }

        $selected = $matches[0];
        foreach ($matches as $record) {
            if ((int) ($record['TahunIntervensi'] ?? 0) > (int) ($selected['TahunIntervensi'] ?? 0)) {
                $selected = $record;
            }
        }
        return $this->normalize_api_record($nik, $selected, $response, $http_status);
    }

    private function normalize_api_record($nik, array $record, array $response, $http_status)
    {
        $unmapped = [];
        $code = function ($field, array $map) use ($record, &$unmapped) {
            $raw = trim((string) ($record[$field] ?? ''));
            if ($raw === '') {
                return NULL;
            }
            if (array_key_exists($raw, $map)) {
                return $map[$raw];
            }
            $unmapped[$field] = $raw;
            return NULL;
        };
        $text = static function ($value) {
            $value = trim((string) $value);
            return $value === '' ? NULL : $value;
        };
        $number = static function ($value) {
            return is_numeric($value) ? (float) $value : NULL;
        };
        $integer = static function ($value) {
            return filter_var($value, FILTER_VALIDATE_INT) !== FALSE ? (int) $value : NULL;
        };

        $kode_dagri = preg_replace('/\D+/', '', (string) ($record['KodeDagri'] ?? ''));
        $kabupaten_id = strlen($kode_dagri) >= 4 ? (int) substr($kode_dagri, 0, 4) : NULL;
        $birth_year = $integer($record['TahunLahir'] ?? NULL);
        $latitude = $number($record['GeoLat'] ?? NULL);
        $longitude = $number($record['GeoLng'] ?? NULL);
        if ($latitude !== NULL && ($latitude < -90 || $latitude > 90)) {
            $unmapped['GeoLat'] = (string) $record['GeoLat'];
            $latitude = NULL;
        }
        if ($longitude !== NULL && ($longitude < -180 || $longitude > 180)) {
            $unmapped['GeoLng'] = (string) $record['GeoLng'];
            $longitude = NULL;
        }
        // 0,0 adalah titik di Samudra Atlantik: SIMPERUM mengirimnya untuk rumah yang belum dipetakan.
        if ($latitude === 0.0 && $longitude === 0.0) {
            $latitude = $longitude = NULL;
        }
        $foundation_presence = $code('AdaPondasi', ['0' => 'absent', '1' => 'present']);
        $condition = [
            '1' => 'good',
            '2' => 'minor_damage',
            '3' => 'moderate_damage',
            '4' => 'severe_damage_or_absent',
        ];

        $tahun_intervensi = $integer($record['TahunIntervensi'] ?? NULL);
        $bantuan_perumahan = $code('SumberDanaID', [
            '1' => 'apbn_bsps', '2' => 'apbd_prov', '3' => 'apbd_kab',
            '4' => 'csr', '5' => 'other', '7' => 'village_fund',
            '9' => 'bsps_kl', '12' => 'bankab', '13' => 'baznas',
        ]);
        $source_labels = [
            'apbn_bsps' => 'APBN/BSPS', 'apbd_prov' => 'APBD Provinsi',
            'apbd_kab' => 'APBD Kabupaten/Kota', 'csr' => 'CSR',
            'other' => 'Sumber lainnya', 'village_fund' => 'Dana Desa',
            'bsps_kl' => 'BSPS-KL', 'bankab' => 'BANKAB', 'baznas' => 'BAZNAS',
        ];
        $disposition_labels = [
            '6' => 'Sudah Layak Huni', '8' => 'Di luar prioritas',
            '10' => 'Meninggal', '11' => 'Salah/duplikasi data', '15' => 'Pindah',
        ];
        $source_raw = trim((string) ($record['SumberDanaID'] ?? ''));
        if ($bantuan_perumahan !== NULL) {
            $intervention_status = 'Sudah diintervensi — ' . ($source_labels[$bantuan_perumahan] ?? 'Sumber tercatat');
            if ($tahun_intervensi !== NULL) $intervention_status .= ' (' . $tahun_intervensi . ')';
        } elseif (isset($disposition_labels[$source_raw])) {
            $intervention_status = $disposition_labels[$source_raw];
        } elseif ($tahun_intervensi !== NULL) {
            $intervention_status = 'Sudah diintervensi (' . $tahun_intervensi . ')';
        } else {
            $intervention_status = 'Belum diintervensi';
        }
        $payload = [
            'status_respons' => 'found',
            'versi_api' => 'simperum-rtlh-v1',
            'http_status' => (int) $http_status,
            'kunci_rekaman_sumber' => $text($record['IDBDT'] ?? NULL),
            'identity' => [
                'nik' => $nik,
                'full_name' => $text($record['Nama'] ?? NULL),
                'address' => $text($record['Alamat'] ?? NULL),
                'birth_year' => $birth_year,
                'jenis_kelamin' => $code('JenisKelamin', ['L' => 'male', 'P' => 'female']),
                'pendidikan' => $code('Pendidikan', [
                    '0' => 'no_certificate', '1' => 'elementary', '2' => 'junior_high',
                    '3' => 'senior_high', '4' => 'diploma_1_3', '5' => 'bachelor',
                    '6' => 'postgraduate',
                ]),
            ],
            'socioeconomic' => [
                'pekerjaan' => $code('Pekerjaan', [
                    '1' => 'farmer', '2' => 'horticulture', '3' => 'plantation',
                    '4' => 'capture_fisher', '5' => 'aquaculture_fisher', '6' => 'breeder',
                    '7' => 'forestry_agriculture_other', '8' => 'mining',
                    '9' => 'daily_laborer', '10' => 'electricity_gas',
                    '11' => 'construction_worker', '12' => 'trader',
                    '13' => 'hotel_restaurant', '14' => 'driver',
                    '15' => 'information_communication', '16' => 'finance_insurance',
                    '17' => 'educator', '18' => 'health_worker',
                    '19' => 'civil_servant', '20' => 'scavenger', '21' => 'other',
                    '22' => 'military_police', '98' => 'retired', '99' => 'unemployed',
                ]),
                'kelompok_penghasilan' => $code('Penghasilan', [
                    '1' => 'lt_1_8', '2' => '1_9_2_1', '3' => '2_2_2_6',
                    '4' => '2_7_3_1', '5' => '3_2_3_6', '6' => '3_7_4_2',
                    '7' => 'gt_4_2',
                ]),
                'mampu_swadaya' => $code('MampuSwadaya', [
                    '0' => 'not_capable', '1' => 'capable',
                ]),
                'desil_kesejahteraan' => NULL,
            ],
            'housing' => [
                'kepemilikan_rumah' => $code('KepemilikanRumah', [
                    '1' => 'owned', '2' => 'rent', '3' => 'rent_free',
                    '4' => 'official', '5' => 'other',
                ]),
                'kepemilikan_lahan' => $code('KepemilikanLahan', [
                    '1' => 'certificate_unspecified', '2' => 'letter_c',
                    '3' => 'letter_d', '4' => 'village_letter',
                ]),
                'tanah_lain' => $code('TanahLain', ['0' => 0, '1' => 1]),
                'rumah_lain' => $code('RumahLain', ['0' => 0, '1' => 1]),
                'luas_rumah' => $number($record['LuasRumah'] ?? NULL),
                'jml_penghuni' => $integer($record['JmlPenghuni'] ?? NULL),
                'jml_kk' => $integer($record['JmlKK'] ?? NULL),
                /* DAFTAR RESMI DARI DINAS, 31 Agt 2026. Yang ditambahkan di
                   sini HANYA kode yang benar-benar sumber dana: 12 BANKAB dan
                   13 BAZNAS.

                   Kode 0, 6, 8, 10, 11, dan 15 SENGAJA TIDAK DIPETAKAN, dan itu
                   bukan kelalaian: labelnya "-", "Sudah Layak Huni", "Diluar
                   Prioritas", "Meninggal", "Salah/Double Data", dan "Pindah".
                   Itu keterangan DISPOSISI, bukan sumber dana. Memetakannya ke
                   sini membuat layar menyebut "Meninggal" sebagai sumber
                   pembiayaan rumah. Keenamnya jatuh ke `unmapped_codes` apa
                   adanya, dan itu memang perlakuan yang benar sampai ada tempat
                   yang jujur untuk menampungnya. */
                'bantuan_perumahan' => $bantuan_perumahan,
                'tahun_intervensi' => $tahun_intervensi,
                'intervention_status' => $intervention_status,
                'kawasan_perumahan' => $code('KawasanPerumahan', [
                    '1' => 'drought', '6' => 'slum', '10' => 'disaster_prone',
                    '11' => 'riverbank', '12' => 'railway', '98' => 'poor_other',
                    '99' => 'good',
                ]),
            ],
            'structure' => [
                'kondisi_pondasi' => $foundation_presence === 'absent'
                    ? 'severe_damage_or_absent' : NULL,
                'kondisi_kolom' => $code('KondisiKolom', $condition),
                'kondisi_balok' => $code('KondisiBalok', $condition),
                'kondisi_rangka' => $code('KondisiRangka', $condition),
                'bahan_lantai' => $code('LantaiID', [
                    '1' => 'marble_granite', '2' => 'ceramic',
                    '3' => 'parquet_vinyl_carpet', '4' => 'tile_terrazzo',
                    '5' => 'high_quality_wood', '6' => 'cement_plaster',
                    '7' => 'bamboo', '8' => 'low_quality_wood',
                    '9' => 'soil', '10' => 'other',
                ]),
                'kondisi_lantai' => $code('KondisiLantai', $condition),
                'bahan_dinding' => $code('DindingID', [
                    '1' => 'wall', '2' => 'plaster_grc', '3' => 'wood',
                    '4' => 'woven_bamboo', '5' => 'log', '6' => 'bamboo',
                    '7' => 'other',
                ]),
                'kondisi_dinding' => $code('KondisiDinding', $condition),
                'bahan_atap' => $code('AtapID', [
                    '1' => 'concrete', '2' => 'ceramic', '3' => 'metal',
                    '4' => 'clay_tile', '5' => 'asbestos', '6' => 'zinc',
                    '7' => 'shingle', '8' => 'bamboo', '9' => 'thatch',
                    '10' => 'other',
                ]),
                'kondisi_atap' => $code('KondisiAtap', $condition),
            ],
            'sanitation' => [
                'ada_jendela' => $code('AdaJendela', ['0' => 0, '1' => 1]),
                'ada_ventilasi' => $code('AdaVentilasi', ['0' => 0, '1' => 1]),
                /* DAFTAR RESMI DARI DINAS, 31 Agt 2026 (WhatsApp, menjawab
                   permintaan kode kami). Peta sebelumnya BUKAN cuma kurang,
                   melainkan SALAH pada tiga kode: 4 dibaca `well` padahal
                   Leding eceran, 5 dibaca `spring` padahal Sumur, dan 6 dibaca
                   `rain` padahal Sumur terlindung. Kode 12 ("Lainnya / Tidak
                   Layak") tidak dipetakan sama sekali, sehingga pemicu
                   `critical_sanitation` di Warga_ruleset.php:59 tidak pernah
                   menyala untuk rumah bersumber air tidak layak menurut
                   SIMPERUM. Itu bukan tampilan, itu kelayakan.

                   Kode 6, 7, 9, dan 10 mendapat kode kanonik SENDIRI, tidak
                   dilebur ke `well`/`spring`, supaya keterangan terlindung atau
                   tidak tidak hilang. Apakah sumur/mata air tak terlindung dan
                   air permukaan ikut dihitung "tidak layak" adalah keputusan
                   KEBIJAKAN, bukan pemetaan - hanya kode 12 yang labelnya
                   sendiri menyebut Tidak Layak, jadi hanya itu yang menjadi
                   `other_unfit`. */
                'sumber_air' => $code('SumberAir', [
                    '1' => 'bottled', '2' => 'refill', '3' => 'pdam',
                    '4' => 'retail_piped', '5' => 'well', '6' => 'well_protected',
                    '7' => 'well_unprotected', '8' => 'spring',
                    '9' => 'spring_unprotected', '10' => 'surface_water',
                    '11' => 'rain', '12' => 'other_unfit',
                ]),
                'jarak_septic_tank' => $code('JarakSepticTank', [
                    '0' => 'lt_10', '1' => 'gte_10',
                ]),
                'penerangan' => $code('Penerangan', [
                    '1' => 'pln', '2' => 'pln_unmetered',
                    '3' => 'non_pln', '4' => 'none',
                ]),
            ],
            'location' => [
                'kabupaten_id' => $kabupaten_id,
                'location_lat' => $latitude,
                'location_lng' => $longitude,
            ],
            'source' => [
                'message' => $response['Message'] ?? NULL,
                'type' => $response['Type'] ?? NULL,
                'unmapped_codes' => $unmapped,
                'raw_record' => $record,
            ],
        ];

        if (empty($payload['identity']['full_name'])) {
            return $this->api_error('api_identity_incomplete', $http_status);
        }
        if (empty($payload['location']['kabupaten_id'])) {
            return $this->api_error('api_region_missing', $http_status);
        }
        $payload['missing_fields'] = [];
        foreach (['identity', 'socioeconomic', 'housing', 'structure', 'sanitation', 'location'] as $group) {
            foreach ($payload[$group] as $field => $value) {
                if ($value === NULL || $value === '') {
                    $payload['missing_fields'][] = $field;
                }
            }
        }
        return $payload;
    }

    private function api_error($code, $http_status)
    {
        return [
            'status_respons' => 'error',
            'versi_api' => 'simperum-rtlh-v1',
            'http_status' => (int) $http_status,
            'error_code' => $code,
        ];
    }

    private function from_snapshot(array $snapshot, $birth_date, $cache_hit, $requested_by)
    {
        $payload = $snapshot['payload'] ?? [];
        $status = $snapshot['status_respons'] ?? 'error';
        if ($status === 'not_found') {
            return $this->response('not_found', 'Data tidak ditemukan. Silakan isi data secara manual.', [
                'rekaman_id' => (int) $snapshot['id'],
                'cache_hit' => $cache_hit,
            ]);
        }
        if ($status === 'error') {
            $message = $this->mode === 'api'
                ? 'Data SIMPERUM belum dapat diambil. Silakan coba lagi.'
                : 'SIMPERUM simulasi sedang tidak tersedia. Silakan isi manual.';
            return $this->response('error', $message, [
                'rekaman_id' => (int) $snapshot['id'],
                'cache_hit' => $cache_hit,
            ], $payload['error_code'] ?? 'source_error');
        }

        $canonical = $this->normalize($payload);
        $klaim = FALSE;
        if ($requested_by) {
            $ditolak = $this->verifikasi_pemilik((int) $requested_by, (string) ($canonical['nik'] ?? ''), $birth_date, $payload, $klaim);
            if ($ditolak !== NULL) {
                return $ditolak;
            }
        } elseif ( ! $this->lewati_tgl_lahir && ! $this->birth_date_matches($canonical['nik'] ?? '', $birth_date, $payload)) {
            return $this->response('not_found', 'NIK dan tanggal lahir tidak cocok.');
        }
        // Sumber tanpa tanggal lahir (API, dan fixture berbentuk API di mode simulasi):
        // tanggal yang sudah lolos pencocokan digit NIK dipakai sebagai isian warga.
        $tanpa_tgl_sumber = empty($payload['identity']['birth_date']);
        if ($tanpa_tgl_sumber && empty($canonical['birth_date'])) {
            $canonical['birth_date'] = $birth_date;
        }
        $this->internal_profile = $canonical;
        if ($requested_by) {
            $canonical['mode_sumber'] = $this->mode;
            $provenance = array_fill_keys(array_keys($canonical), ['source' => $this->mode]);
            if ($tanpa_tgl_sumber) {
                $provenance['birth_date'] = ['source' => 'citizen'];
            }
            $existing = $this->CI->Housing_assessment_model->get_owned_profile($requested_by);
            $existing_provenance = kunci_tersimpan_ke_baru(json_decode($existing['asal_isian_json'] ?? '{}', TRUE) ?: []);
            foreach ($existing_provenance as $field => $meta) {
                $source = is_array($meta) ? ($meta['source'] ?? '') : $meta;
                if (in_array($source, ['citizen', 'citizen_correction'], TRUE)
                    && array_key_exists($field, (array) $existing)) {
                    $canonical[$field] = $existing[$field];
                    $provenance[$field] = is_array($meta) ? $meta : ['source' => $source];
                }
            }
            /* NIK yang terikat ke akun lain TANPA verifikasi berpindah ke akun yang baru lolos
               verifikasi (keputusan pemilik produk, 3 Okt 2026): pelepasan, profil baru, tanda
               terverifikasi, dan jejak audit dalam SATU transaksi. */
            $db = $this->CI->db;
            if ($klaim) {
                $db->trans_begin();
                $pindah = $this->CI->Housing_assessment_model->pindahkan_ikatan_nik($requested_by, (string) ($canonical['nik'] ?? ''));
                if (empty($pindah['success'])) {
                    $db->trans_rollback();
                    return $this->response('error', $pindah['message'], [], $pindah['code']);
                }
            }
            $saved = $this->CI->Housing_assessment_model->save_profile(
                $requested_by,
                $canonical,
                $provenance
            );
            if (empty($saved['success'])) {
                if ($klaim) { $db->trans_rollback(); }
                // Teruskan pesan spesifik model ("Akun Anda sudah terhubung dengan
                // NIK lain...", dst) - pesan generik terbukti membuat pengguna
                // buntu total: penyebabnya hanya bisa ditelusuri lewat query DB.
                return $this->response('error', $saved['message'] ?? 'Profil belum dapat disimpan dengan aman.', [], $saved['code'] ?? 'profile_failed');
            }
            $this->CI->Housing_assessment_model->tandai_nik_terverifikasi($requested_by);
            if ($klaim) {
                foreach ($pindah['dari'] as $akun_lama) {
                    $this->catat_nik_dipindahkan((int) $requested_by, (int) $akun_lama, $pindah);
                }
                if ( ! $db->trans_status()) {
                    $db->trans_rollback();
                    return $this->response('error', 'Profil belum dapat disimpan dengan aman.', [], 'profile_failed');
                }
                $db->trans_commit();
                try {
                    $this->CI->load->library('Data_erasure');
                    $this->CI->data_erasure->sapu_berkas_draf($pindah['draft_ids']);
                } catch (\Throwable $e) {
                    log_message('error', 'Simperum_gateway: berkas draft akun lama gagal disapu: ' . $e->getMessage());
                }
                $this->kabari_akun_lama($pindah['dari']);
            }
            $this->cermin($canonical['nik'] ?? '', $requested_by, $snapshot);
        }

        return $this->response('found', $this->mode === 'api'
            ? 'Data SIMPERUM ditemukan.'
            : 'Data simulasi ditemukan.', [
            'rekaman_id' => (int) $snapshot['id'],
            'fixture_id' => $snapshot['kunci_rekaman_sumber'] ?? NULL,
            'kunci_rekaman_sumber' => $snapshot['kunci_rekaman_sumber'] ?? NULL,
            'cache_hit' => $cache_hit,
            'missing_fields' => array_values($payload['missing_fields'] ?? []),
            // Tanpa akun terverifikasi hanya status intervensi yang keluar (Cek RTLH, cek anonim).
            'profile' => $requested_by
                ? $this->mask_profile($canonical)
                : ['status_intervensi' => $this->mask_profile($canonical)['status_intervensi']],
        ]);
    }

    /**
     * Bukti kepemilikan NIK sebelum diikat ke $user_id. NULL bila lolos, selain itu respons
     * penolakan yang tidak memuat data sumber apa pun dan tidak menyebut nilai yang diharapkan.
     *
     * Urutan: profil akun sendiri yang terikat NIK lain atau NIK akun yang berbeda ditolak lebih
     * dulu (bukan percobaan gagal), lalu batas percobaan gagal per akun dan per NIK, baru pencocokan
     * nama + tanggal lahir. NIK yang terikat ke akun LAIN baru dijawab sesudah pencocokan lolos:
     * sebelum itu jawabannya sama persis dengan NIK yang tidak terikat siapa pun, jadi layar ini
     * tidak bisa dipakai menebak apakah sebuah NIK sudah terikat atau ikatannya terverifikasi.
     * $klaim TRUE: lolos, dan ikatan di akun lain harus dilepas dulu (from_snapshot), yang
     * menolak dengan PESAN_NIK_TERIKAT bila ikatan itu ternyata terverifikasi.
     */
    private function verifikasi_pemilik($user_id, $nik, $birth_date, array $payload, &$klaim)
    {
        $klaim = FALSE;
        $model = $this->CI->Housing_assessment_model;
        $nik_hash = $this->CI->encryption_lib->deterministic_hash($nik);
        $ikatan = $model->cek_ikatan_nik($user_id, $nik_hash);
        if ($ikatan !== NULL && $ikatan['code'] !== 'nik_already_bound') {
            return $this->response('error', $ikatan['message'], [], $ikatan['code']);
        }
        $akun = $this->CI->db->select('nama, nik_lookup_hash')->get_where('usr_akun', ['id' => $user_id])->row_array();
        if ( ! $akun) {
            return $this->response('error', 'Akun tidak ditemukan.', [], 'akun_tidak_ada');
        }
        if ( ! empty($akun['nik_lookup_hash']) && ! hash_equals((string) $akun['nik_lookup_hash'], (string) $nik_hash)) {
            return $this->response('error', 'NIK ini berbeda dengan NIK yang terdaftar di akun Anda. Gunakan NIK akun Anda sendiri.', [], 'nik_bukan_milik_akun');
        }

        $this->CI->load->library('Rate_limiter');
        $konteks = ['account_id' => $user_id, 'nik' => $nik];
        $laju = $this->CI->rate_limiter->inspect('verifikasi_nik', $konteks);
        if (empty($laju['success']) || empty($laju['allowed'])) {
            return $this->response('error', 'Terlalu banyak percobaan verifikasi NIK yang tidak cocok. Silakan coba lagi besok, atau sampaikan melalui menu Aduan bila data Anda memang benar.', [
                'retry_after' => (int) ($laju['retry_after'] ?? 0),
            ], 'verifikasi_terkunci');
        }

        // Keduanya selalu dihitung (tanpa hubung-singkat), supaya waktu respons tidak membedakannya.
        $nama_cocok = self::nama_sama((string) ($akun['nama'] ?? ''), (string) ($payload['identity']['full_name'] ?? ''));
        $tanggal_cocok = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $birth_date) === 1
            && $this->birth_date_matches($nik, (string) $birth_date, $payload);
        if ($nama_cocok && $tanggal_cocok) {
            $klaim = $ikatan !== NULL;
            return NULL;
        }
        $this->CI->rate_limiter->hit('verifikasi_nik', $konteks);
        return $this->response('mismatch', 'Nama lengkap di akun Anda atau tanggal lahir tidak cocok dengan data NIK ini. Periksa tanggal lahir dan pastikan nama di Profil Saya sama dengan nama di KTP.', [], 'verifikasi_tidak_cocok');
    }

    /** Jejak audit klaim NIK: pelaku = akun yang lolos verifikasi, objek = akun yang melepas. Tanpa NIK. */
    private function catat_nik_dipindahkan($penerima, $akun_lama, array $pindah)
    {
        $this->CI->db->insert('sys_jejak_audit', [
            'pelaku_id' => $penerima,
            'pelaku_email' => $this->CI->session->userdata('email') ?: NULL,
            'pelaku_peran' => $this->CI->session->userdata('role') ?: NULL,
            'aksi' => 'nik_dipindahkan',
            'objek_tipe' => 'usr_akun',
            'objek_id' => (string) $akun_lama,
            'ringkasan' => 'NIK yang belum terverifikasi dilepas dari akun ini karena akun lain lolos verifikasi pemilik (nama dan tanggal lahir)',
            'detail_json' => json_encode(['akun_penerima' => $penerima, 'draft_dihapus' => count($pindah['draft_ids']),
                'pengajuan_berjalan' => $pindah['pengajuan_berjalan']]),
            'ip' => $this->CI->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Web Push ke akun yang melepas NIK (kanal notifikasi per akun yang ada); gagal kirim tidak membatalkan apa pun. */
    private function kabari_akun_lama(array $akun)
    {
        try {
            $this->CI->load->library('Web_push_service');
            $this->CI->web_push_service->notify(array_map(fn($id) => ['user_id' => (int) $id], $akun),
                'Perubahan data akun Klinik PKP',
                'NIK di akun Anda dilepas karena pemiliknya sudah membuktikan kepemilikan. Bila menurut Anda ini keliru, hubungi Dinas Perakim melalui menu Aduan.',
                'Umum/aduan', 'nik-dilepas');
        } catch (\Throwable $e) {
            log_message('error', 'Simperum_gateway: notifikasi akun lama gagal: ' . $e->getMessage());
        }
    }

    /**
     * Nama sama setelah dinormalkan: huruf besar, entitas HTML dibuka (nama akun disimpan lewat
     * html_escape), gelar sesudah koma dibuang, tanda baca jadi spasi (apostrof dihapus),
     * spasi dirapatkan, dan sapaan/gelar umum di depan dibuang. Tidak ada pencocokan longgar:
     * hasilnya harus sama persis. Dibandingkan lewat sidik supaya waktu tidak bergantung isi.
     */
    public static function nama_sama($a, $b)
    {
        $normal = static function ($nama) {
            $nama = mb_strtoupper(html_entity_decode((string) $nama, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');
            $nama = explode(',', $nama)[0];
            $nama = preg_replace(["/['`\x{2019}]/u", '/[^\p{L}\p{N}]+/u'], ['', ' '], $nama);
            $kata = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
            $sapaan = ['H', 'HJ', 'HAJI', 'HAJAH', 'KH', 'DR', 'DRS', 'DRA', 'IR', 'PROF', 'BPK', 'BAPAK', 'IBU', 'SDR', 'SDRI', 'NY', 'TN', 'ALM', 'ALMH'];
            while (count($kata) > 1 && in_array($kata[0], $sapaan, TRUE)) {
                array_shift($kata);
            }
            return implode(' ', $kata);
        };
        $a = $normal($a);
        $b = $normal($b);
        return $a !== '' && $b !== '' && hash_equals(hash('sha256', $a), hash('sha256', $b));
    }

    private function birth_date_matches($nik, $birth_date, array $payload)
    {
        $source_date = trim((string) ($payload['identity']['birth_date'] ?? ''));
        if ($source_date !== '') {
            return hash_equals($source_date, $birth_date);
        }

        $date = DateTime::createFromFormat('!Y-m-d', $birth_date);
        if ( ! $date || $date->format('Y-m-d') !== $birth_date) {
            return FALSE;
        }
        $today = new DateTime('today');
        $oldest = (clone $today)->modify('-120 years');
        if ($date > $today || $date < $oldest) {
            return FALSE;
        }
        $source_year = (int) ($payload['identity']['birth_year'] ?? 0);
        if ($source_year > 0 && (int) $date->format('Y') !== $source_year) {
            return FALSE;
        }

        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            return FALSE;
        }
        $day = (int) substr($nik, 6, 2);
        if ($day > 40) {
            $day -= 40;
        }
        return sprintf('%02d%s', $day, substr($nik, 8, 4)) === $date->format('dmy');
    }

    public function internal_profile()
    {
        return $this->internal_profile;
    }

    private function normalize(array $payload)
    {
        $identity = $payload['identity'] ?? [];
        $socioeconomic = $payload['socioeconomic'] ?? [];
        $housing = $payload['housing'] ?? [];
        $structure = $payload['structure'] ?? [];
        $sanitation = $payload['sanitation'] ?? [];
        $location = $payload['location'] ?? [];
        // Snapshot lama belum memiliki intervention_status. Turunkan dari
        // sumber dana/tahun/disposisi yang sudah tersimpan agar hasil cache
        // langsung konsisten tanpa meminta ulang data API.
        if (empty($housing['intervention_status'])) {
            $source_labels = [
                'apbn_bsps'=>'APBN/BSPS','apbd_prov'=>'APBD Provinsi',
                'apbd_kab'=>'APBD Kabupaten/Kota','csr'=>'CSR','other'=>'Sumber lainnya',
                'village_fund'=>'Dana Desa','bsps_kl'=>'BSPS-KL','bankab'=>'BANKAB','baznas'=>'BAZNAS',
            ];
            $source = $housing['bantuan_perumahan'] ?? NULL;
            $year = $housing['tahun_intervensi'] ?? NULL;
            $raw = (string) ($payload['unmapped_codes']['SumberDanaID'] ?? '');
            $dispositions = ['6'=>'Sudah Layak Huni','8'=>'Di luar prioritas','10'=>'Meninggal','11'=>'Salah/duplikasi data','15'=>'Pindah'];
            if ($source !== NULL && isset($source_labels[$source])) {
                $housing['intervention_status'] = 'Sudah diintervensi — ' . $source_labels[$source] . ($year ? ' (' . $year . ')' : '');
            } elseif (isset($dispositions[$raw])) {
                $housing['intervention_status'] = $dispositions[$raw];
            } elseif ($year) {
                $housing['intervention_status'] = 'Sudah diintervensi (' . $year . ')';
            } else {
                $housing['intervention_status'] = 'Belum diintervensi';
            }
        }
        return array_intersect_key(
            $identity + $socioeconomic + $housing + $structure + $sanitation + $location,
            array_flip([
                'nik', 'family_card_number', 'full_name', 'address', 'phone',
                'birth_date', 'jenis_kelamin', 'status_perkawinan', 'pendidikan',
                'pekerjaan', 'tax_number', 'kelompok_penghasilan', 'desil_kesejahteraan',
                'punya_tabungan', 'mampu_swadaya', 'nilai_swadaya',
                'kepemilikan_rumah', 'kepemilikan_lahan', 'tanah_lain',
                'rumah_lain', 'luas_rumah', 'jml_penghuni', 'jml_kk',
                'bantuan_perumahan', 'tahun_intervensi', 'intervention_status', 'kawasan_perumahan',
                'punya_lahan_calon', 'candidate_land_address',
                'status_lahan_calon', 'asal_lahan_calon',
                'hubungan_pemilik_lahan', 'panjang_lahan_m', 'lebar_lahan_m',
                'luas_lahan_m2', 'kondisi_pondasi', 'kondisi_kolom',
                'kondisi_balok', 'kondisi_sloof',
                'kondisi_plafon', 'kondisi_rangka',
                'bahan_lantai', 'kondisi_lantai', 'bahan_dinding',
                'kondisi_dinding', 'bahan_atap', 'kondisi_atap',
                'ada_jendela', 'ada_ventilasi', 'sumber_air',
                'kamar_mandi', 'jenis_kloset', 'pembuangan_tinja',
                'jarak_septic_tank', 'penerangan', 'bahan_bakar_masak',
                'location_lat', 'location_lng', 'akurasi_lokasi_m', 'kabupaten_id',
            ])
        );
    }

    private function mask_profile(array $profile)
    {
        /* DUA KOSAKATA KODE HIDUP BERDAMPINGAN DI SINI, DAN ITU DISENGAJA.
           Fixture simulasi memakai kode wizard lama (`private_employee`,
           `owned_habitable`), sedangkan normalize_api_record() menghasilkan kode
           turunan katalog SIMPERUM (`daily_laborer`, `owned`). Sebelum 25 Agt 2026
           peta di bawah HANYA memuat kosakata simulasi, jadi begitu mode `api`
           dinyalakan seluruh label pekerjaan dan kepemilikan keluar KOSONG
           walaupun kodenya benar. Terbukti pada NIK nyata: kode pekerjaan
           `daily_laborer` terpetakan, label `pekerjaan` tetap ''. Jangan menghapus
           salah satu kosakata; keduanya dipakai mode yang berbeda. */
        $occupations = [
            // kosakata simulasi
            'private_employee' => 'Karyawan Swasta',
            'informal_worker' => 'Pekerja Informal',
            'self_employed' => 'Wiraswasta',
            // kosakata SIMPERUM (Pekerjaan 1-22, 98, 99)
            'farmer' => 'Petani',
            'horticulture' => 'Petani Hortikultura',
            'plantation' => 'Pekebun',
            'capture_fisher' => 'Nelayan Tangkap',
            'aquaculture_fisher' => 'Nelayan Budidaya',
            'breeder' => 'Peternak',
            'forestry_agriculture_other' => 'Kehutanan/Pertanian Lainnya',
            'mining' => 'Pertambangan',
            'daily_laborer' => 'Buruh Harian Lepas',
            'electricity_gas' => 'Listrik dan Gas',
            'construction_worker' => 'Buruh Bangunan',
            'trader' => 'Pedagang',
            'hotel_restaurant' => 'Hotel dan Rumah Makan',
            'driver' => 'Sopir/Transportasi',
            'information_communication' => 'Informasi dan Komunikasi',
            'finance_insurance' => 'Keuangan dan Asuransi',
            'educator' => 'Tenaga Pendidik',
            'health_worker' => 'Tenaga Kesehatan',
            'civil_servant' => 'Pegawai Negeri',
            'scavenger' => 'Pemulung',
            'military_police' => 'TNI/Polri',
            'retired' => 'Pensiunan',
            'unemployed' => 'Tidak Bekerja',
            'other' => 'Lainnya',
        ];
        $housing = [
            // kosakata simulasi
            'family' => 'Numpang/Keluarga',
            'candidate_land' => 'Punya Lahan Belum Bangun',
            'owned_uninhabitable' => 'Punya Rumah Tidak Layak',
            'owned_habitable' => 'Punya Rumah Layak',
            // kosakata SIMPERUM (KepemilikanRumah 1-5)
            'owned' => 'Milik Sendiri',
            'rent' => 'Sewa/Kontrak',
            'rent_free' => 'Bebas Sewa',
            'official' => 'Rumah Dinas',
            'other' => 'Lainnya',
        ];
        /* Label rentang diturunkan dari nama kodenya sendiri (juta rupiah),
           bukan ditebak: `lt_1_8` = di bawah 1,8 juta, `gt_4_2` = di atas 4,2 juta. */
        $income = [
            'lt_1_8' => 'Kurang dari Rp1,8 juta',
            '1_9_2_1' => 'Rp1,9 juta sampai Rp2,1 juta',
            '2_2_2_6' => 'Rp2,2 juta sampai Rp2,6 juta',
            '2_7_3_1' => 'Rp2,7 juta sampai Rp3,1 juta',
            '3_2_3_6' => 'Rp3,2 juta sampai Rp3,6 juta',
            '3_7_4_2' => 'Rp3,7 juta sampai Rp4,2 juta',
            'gt_4_2' => 'Lebih dari Rp4,2 juta',
        ];

        return [
            'nik' => isset($profile['nik']) ? str_repeat('*', 12) . substr($profile['nik'], -4) : NULL,
            'nama_lengkap' => $this->mask_words($profile['full_name'] ?? ''),
            'alamat' => $this->mask_words($profile['address'] ?? ''),
            'desil' => $profile['desil_kesejahteraan'] ?? NULL,
            'pekerjaan' => $occupations[$profile['pekerjaan'] ?? ''] ?? '',
            'penghasilan' => $income[$profile['kelompok_penghasilan'] ?? ''] ?? '',
            'status_kepemilikan' => $housing[$profile['kepemilikan_rumah'] ?? ''] ?? '',
            'status_intervensi' => $profile['intervention_status'] ?? 'Belum tersedia',
            'kelompok_penghasilan' => $profile['kelompok_penghasilan'] ?? NULL,
        ];
    }

    private function mask_words($value)
    {
        return preg_replace_callback('/\S+/u', static function ($match) {
            $word = $match[0];
            $length = mb_strlen($word);
            return $length < 3
                ? $word
                : mb_substr($word, 0, 1) . str_repeat('*', $length - 2) . mb_substr($word, -1);
        }, (string) $value);
    }

    private function response($status, $message, array $data = [], $code = NULL)
    {
        return [
            'status' => $status,
            'message' => $message,
            'mode_sumber' => $this->mode,
            'simulation' => $this->mode === 'simulation',
            'code' => $code,
            'data' => $data,
        ];
    }
}
