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
     * @param bool $tanpa_tgl_lahir Lewati pengaman tanggal lahir.
     *
     * BUTIR 5 PUTARAN 2, dan bendera ini sengaja dibuat SEMPIT. Dinas meminta
     * tanggal lahir dihilangkan dari layar Cek Data Rumah; keputusan itu
     * dikonfirmasi user 11 Agt 2026 ("pakai yang terbaru, manut dinas"),
     * membalik keputusan 5 Agt yang mempertahankannya.
     *
     * Yang TIDAK dilakukan: melonggarkan gateway untuk semua pemanggil.
     * `Warga::pendataan()` memakai `lookup()` yang sama, dan di sana tanggal
     * lahir bukan formalitas melainkan pengaman anti-penelusuran. Melepasnya
     * di satu layar adalah keputusan dinas; melepasnya di seluruh sistem
     * adalah kelalaian kami. Karena itu bendera ini harus DIMINTA secara
     * eksplisit, dan hanya `Cek_Rtlh` yang memintanya.
     */
    public function lookup($nik, $birth_date, $requested_by = NULL, $tanpa_tgl_lahir = FALSE)
    {
        $this->internal_profile = [];
        $nik = preg_replace('/\D+/', '', (string) $nik);
        $birth_date = trim((string) $birth_date);
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
            $status = $payload['response_status'] ?? 'error';
            $stored = $this->CI->Housing_assessment_model->store_source_snapshot(
                $nik,
                $this->mode,
                $payload['source_record_key'] ?? $payload['fixture_id'] ?? NULL,
                $status,
                $payload,
                [
                    'api_version' => $payload['api_version'] ?? ($this->mode === 'api' ? 'simperum-rtlh-v1' : 'simulation-v1'),
                    'http_status' => $payload['http_status'] ?? ($status === 'error' ? 503 : 200),
                    'error_code' => $payload['error_code'] ?? NULL,
                    'requested_by' => $requested_by,
                ]
            );
            if (empty($stored['success'])) {
                return $this->response('error', 'Data belum dapat disimpan dengan aman.', [], $stored['code'] ?? 'write_failed');
            }

            $payload['id'] = (int) $stored['snapshot_id'];
            return $this->from_snapshot([
                'id' => (int) $stored['snapshot_id'],
                'response_status' => $status,
                'source_record_key' => $payload['source_record_key'] ?? $payload['fixture_id'] ?? NULL,
                'payload' => $payload,
            ], $birth_date, FALSE, $requested_by);
        } finally {
            $this->CI->db->query('SELECT RELEASE_LOCK(?)', [$lock_name]);
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
            'response_status' => 'error',
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
            $payload['api_version'] = 'dummy-table-v1';
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
                'response_status' => 'not_found',
                'api_version' => 'simperum-rtlh-v1',
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
        $foundation_presence = $code('AdaPondasi', ['0' => 'absent', '1' => 'present']);
        $condition = [
            '1' => 'good',
            '2' => 'minor_damage',
            '3' => 'moderate_damage',
            '4' => 'severe_damage_or_absent',
        ];

        $payload = [
            'response_status' => 'found',
            'api_version' => 'simperum-rtlh-v1',
            'http_status' => (int) $http_status,
            'source_record_key' => $text($record['IDBDT'] ?? NULL),
            'identity' => [
                'nik' => $nik,
                'full_name' => $text($record['Nama'] ?? NULL),
                'address' => $text($record['Alamat'] ?? NULL),
                'birth_year' => $birth_year,
                'gender_code' => $code('JenisKelamin', ['L' => 'male', 'P' => 'female']),
                'education_code' => $code('Pendidikan', [
                    '0' => 'no_certificate', '1' => 'elementary', '2' => 'junior_high',
                    '3' => 'senior_high', '4' => 'diploma_1_3', '5' => 'bachelor',
                    '6' => 'postgraduate',
                ]),
            ],
            'socioeconomic' => [
                'occupation_code' => $code('Pekerjaan', [
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
                'income_band_code' => $code('Penghasilan', [
                    '1' => 'lt_1_8', '2' => '1_9_2_1', '3' => '2_2_2_6',
                    '4' => '2_7_3_1', '5' => '3_2_3_6', '6' => '3_7_4_2',
                    '7' => 'gt_4_2',
                ]),
                'self_help_capability_code' => $code('MampuSwadaya', [
                    '0' => 'not_capable', '1' => 'capable',
                ]),
                'welfare_decile' => NULL,
            ],
            'housing' => [
                'housing_status_code' => $code('KepemilikanRumah', [
                    '1' => 'owned', '2' => 'rent', '3' => 'rent_free',
                    '4' => 'official', '5' => 'other',
                ]),
                'land_title_code' => $code('KepemilikanLahan', [
                    '1' => 'certificate_unspecified', '2' => 'letter_c',
                    '3' => 'letter_d', '4' => 'village_letter',
                ]),
                'has_other_land' => $code('TanahLain', ['0' => 0, '1' => 1]),
                'has_other_house' => $code('RumahLain', ['0' => 0, '1' => 1]),
                'house_area_m2' => $number($record['LuasRumah'] ?? NULL),
                'occupant_count' => $integer($record['JmlPenghuni'] ?? NULL),
                'family_count' => $integer($record['JmlKK'] ?? NULL),
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
                'assistance_source_code' => $code('SumberDanaID', [
                    '1' => 'apbn_bsps', '2' => 'apbd_prov', '3' => 'apbd_kab',
                    '4' => 'csr', '5' => 'other', '7' => 'village_fund',
                    '9' => 'bsps_kl', '12' => 'bankab', '13' => 'baznas',
                ]),
                'assistance_year' => $integer($record['TahunIntervensi'] ?? NULL),
                'area_condition_code' => $code('KawasanPerumahan', [
                    '1' => 'drought', '6' => 'slum', '10' => 'disaster_prone',
                    '11' => 'riverbank', '12' => 'railway', '98' => 'poor_other',
                    '99' => 'good',
                ]),
            ],
            'structure' => [
                'foundation_condition_code' => $foundation_presence === 'absent'
                    ? 'severe_damage_or_absent' : NULL,
                'column_condition_code' => $code('KondisiKolom', $condition),
                'beam_condition_code' => $code('KondisiBalok', $condition),
                'roof_frame_condition_code' => $code('KondisiRangka', $condition),
                'floor_material_code' => $code('LantaiID', [
                    '1' => 'marble_granite', '2' => 'ceramic',
                    '3' => 'parquet_vinyl_carpet', '4' => 'tile_terrazzo',
                    '5' => 'high_quality_wood', '6' => 'cement_plaster',
                    '7' => 'bamboo', '8' => 'low_quality_wood',
                    '9' => 'soil', '10' => 'other',
                ]),
                'floor_condition_code' => $code('KondisiLantai', $condition),
                'wall_material_code' => $code('DindingID', [
                    '1' => 'wall', '2' => 'plaster_grc', '3' => 'wood',
                    '4' => 'woven_bamboo', '5' => 'log', '6' => 'bamboo',
                    '7' => 'other',
                ]),
                'wall_condition_code' => $code('KondisiDinding', $condition),
                'roof_material_code' => $code('AtapID', [
                    '1' => 'concrete', '2' => 'ceramic', '3' => 'metal',
                    '4' => 'clay_tile', '5' => 'asbestos', '6' => 'zinc',
                    '7' => 'shingle', '8' => 'bamboo', '9' => 'thatch',
                    '10' => 'other',
                ]),
                'roof_condition_code' => $code('KondisiAtap', $condition),
            ],
            'sanitation' => [
                'has_window' => $code('AdaJendela', ['0' => 0, '1' => 1]),
                'has_ventilation' => $code('AdaVentilasi', ['0' => 0, '1' => 1]),
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
                'water_source_code' => $code('SumberAir', [
                    '1' => 'bottled', '2' => 'refill', '3' => 'pdam',
                    '4' => 'retail_piped', '5' => 'well', '6' => 'well_protected',
                    '7' => 'well_unprotected', '8' => 'spring',
                    '9' => 'spring_unprotected', '10' => 'surface_water',
                    '11' => 'rain', '12' => 'other_unfit',
                ]),
                'septic_distance_code' => $code('JarakSepticTank', [
                    '0' => 'lt_10', '1' => 'gte_10',
                ]),
                'lighting_source_code' => $code('Penerangan', [
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
            'response_status' => 'error',
            'api_version' => 'simperum-rtlh-v1',
            'http_status' => (int) $http_status,
            'error_code' => $code,
        ];
    }

    private function from_snapshot(array $snapshot, $birth_date, $cache_hit, $requested_by)
    {
        $payload = $snapshot['payload'] ?? [];
        $status = $snapshot['response_status'] ?? 'error';
        if ($status === 'not_found') {
            return $this->response('not_found', 'Data tidak ditemukan. Silakan isi data secara manual.', [
                'snapshot_id' => (int) $snapshot['id'],
                'cache_hit' => $cache_hit,
            ]);
        }
        if ($status === 'error') {
            $message = $this->mode === 'api'
                ? 'Data SIMPERUM belum dapat diambil. Silakan coba lagi.'
                : 'SIMPERUM simulasi sedang tidak tersedia. Silakan isi manual.';
            return $this->response('error', $message, [
                'snapshot_id' => (int) $snapshot['id'],
                'cache_hit' => $cache_hit,
            ], $payload['error_code'] ?? 'source_error');
        }

        $canonical = $this->normalize($payload);
        if ( ! $this->lewati_tgl_lahir && ! $this->birth_date_matches($canonical['nik'] ?? '', $birth_date, $payload)) {
            return $this->response('not_found', 'NIK dan tanggal lahir tidak cocok.');
        }
        if ($this->mode === 'api' && empty($canonical['birth_date'])) {
            $canonical['birth_date'] = $birth_date;
        }
        $this->internal_profile = $canonical;
        if ($requested_by) {
            $canonical['source_mode'] = $this->mode;
            $provenance = array_fill_keys(array_keys($canonical), ['source' => $this->mode]);
            if ($this->mode === 'api' && empty($payload['identity']['birth_date'])) {
                $provenance['birth_date'] = ['source' => 'citizen'];
            }
            $existing = $this->CI->Housing_assessment_model->get_owned_profile($requested_by);
            $existing_provenance = json_decode($existing['field_provenance_json'] ?? '{}', TRUE) ?: [];
            foreach ($existing_provenance as $field => $meta) {
                $source = is_array($meta) ? ($meta['source'] ?? '') : $meta;
                if (in_array($source, ['citizen', 'citizen_correction'], TRUE)
                    && array_key_exists($field, (array) $existing)) {
                    $canonical[$field] = $existing[$field];
                    $provenance[$field] = is_array($meta) ? $meta : ['source' => $source];
                }
            }
            $saved = $this->CI->Housing_assessment_model->save_profile(
                $requested_by,
                $canonical,
                $provenance
            );
            if (empty($saved['success'])) {
                // Teruskan pesan spesifik model ("Akun Anda sudah terhubung dengan
                // NIK lain...", dst) - pesan generik terbukti membuat pengguna
                // buntu total: penyebabnya hanya bisa ditelusuri lewat query DB.
                return $this->response('error', $saved['message'] ?? 'Profil belum dapat disimpan dengan aman.', [], $saved['code'] ?? 'profile_failed');
            }
        }

        return $this->response('found', $this->mode === 'api'
            ? 'Data SIMPERUM ditemukan.'
            : 'Data simulasi ditemukan.', [
            'snapshot_id' => (int) $snapshot['id'],
            'fixture_id' => $snapshot['source_record_key'] ?? NULL,
            'source_record_key' => $snapshot['source_record_key'] ?? NULL,
            'cache_hit' => $cache_hit,
            'missing_fields' => array_values($payload['missing_fields'] ?? []),
            'profile' => $this->mask_profile($canonical),
        ]);
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
        return array_intersect_key(
            $identity + $socioeconomic + $housing + $structure + $sanitation + $location,
            array_flip([
                'nik', 'family_card_number', 'full_name', 'address', 'phone',
                'birth_date', 'gender_code', 'marital_status_code', 'education_code',
                'occupation_code', 'tax_number', 'income_band_code', 'welfare_decile',
                'has_savings', 'self_help_capability_code', 'self_help_amount',
                'housing_status_code', 'land_title_code', 'has_other_land',
                'has_other_house', 'house_area_m2', 'occupant_count', 'family_count',
                'assistance_source_code', 'assistance_year', 'area_condition_code',
                'owns_candidate_land', 'candidate_land_address',
                'candidate_land_title_code', 'candidate_land_origin_code',
                'land_owner_relationship_code', 'land_length_m', 'land_width_m',
                'land_area_m2', 'foundation_condition_code', 'column_condition_code',
                'beam_condition_code', 'sloof_condition_code',
                'ceiling_condition_code', 'roof_frame_condition_code',
                'floor_material_code', 'floor_condition_code', 'wall_material_code',
                'wall_condition_code', 'roof_material_code', 'roof_condition_code',
                'has_window', 'has_ventilation', 'water_source_code',
                'has_bathroom_latrine', 'latrine_type_code', 'feces_disposal_code',
                'septic_distance_code', 'lighting_source_code', 'cooking_fuel_code',
                'location_lat', 'location_lng', 'location_accuracy_m', 'kabupaten_id',
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
           walaupun kodenya benar. Terbukti pada NIK nyata: occupation_code
           `daily_laborer` terpetakan, `pekerjaan` tetap ''. Jangan menghapus
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
            'desil' => $profile['welfare_decile'] ?? NULL,
            'pekerjaan' => $occupations[$profile['occupation_code'] ?? ''] ?? '',
            'penghasilan' => $income[$profile['income_band_code'] ?? ''] ?? '',
            'status_kepemilikan' => $housing[$profile['housing_status_code'] ?? ''] ?? '',
            'income_band_code' => $profile['income_band_code'] ?? NULL,
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
            'source_mode' => $this->mode,
            'simulation' => $this->mode === 'simulation',
            'code' => $code,
            'data' => $data,
        ];
    }
}
