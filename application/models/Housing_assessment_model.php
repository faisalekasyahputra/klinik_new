<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Housing_assessment_model extends CI_Model {

    private const SOURCE_MODES = ['simulation', 'api'];
    private const TRACKS = ['undetermined', 'existing_house', 'candidate_land', 'financing'];
    private const SNAPSHOT_STATUSES = ['found', 'not_found', 'error'];
    private const EVIDENCE_KINDS = ['self_photo','house_front_photo','house_side_photo','roof_photo','floor_photo','wall_photo','latrine_photo','land_photo','candidate_land_photo','land_transfer_proof','recipient_photo','id_card_photo','family_card_photo','land_owner_family_card_photo'];

    private const PROFILE_FIELDS = [
        'gender_code', 'marital_status_code', 'education_code',
        'occupation_code', 'income_band_code', 'welfare_decile',
        'has_savings', 'self_help_capability_code', 'self_help_amount',
    ];

    private const DRAFT_FIELDS = [
        'current_step', 'assessment_track', 'housing_status_code', 'land_title_code',
        'has_other_land', 'has_other_house', 'house_area_m2',
        'occupant_count', 'family_count', 'assistance_source_code',
        'assistance_year', 'area_condition_code', 'owns_candidate_land',
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
        'location_accuracy_m',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption_lib');
        $this->load->library('Warga_ruleset');
        $this->load->helper('housing_queue');
    }

    public function save_profile($user_id, array $data, array $provenance = [])
    {
        $user_id = (int) $user_id;
        $nik = preg_replace('/\D+/', '', (string) ($data['nik'] ?? ''));
        $name = trim((string) ($data['full_name'] ?? ''));
        $source_mode = (string) ($data['source_mode'] ?? 'simulation');

        if ($user_id < 1 || ! preg_match('/^\d{16}$/', $nik) || $name === ''
            || ! in_array($source_mode, self::SOURCE_MODES, TRUE)) {
            return $this->fail('invalid_profile', 'Data profil warga tidak valid.');
        }
        if ( ! $this->encryption_ready()) {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $nik_hash = $this->encryption_lib->deterministic_hash($nik);
        if ($nik_hash === '') {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $bound = $this->db->select('id, user_id')
            ->get_where('sf_profil_warga', ['nik_lookup_hash' => $nik_hash])
            ->row_array();
        if ($bound && (int) $bound['user_id'] !== $user_id) {
            return $this->fail('nik_already_bound', 'NIK ini sudah terdaftar pada akun lain. Jika Anda merasa ini keliru, hubungi Dinas Perakim.');
        }

        $existing = $this->db->select('id, nik_lookup_hash')
            ->get_where('sf_profil_warga', ['user_id' => $user_id])
            ->row_array();
        if ($existing && ! hash_equals($existing['nik_lookup_hash'], $nik_hash)) {
            return $this->fail('account_already_bound', 'Akun Anda sudah terhubung dengan NIK lain. Gunakan NIK yang sama dengan pendataan sebelumnya.');
        }

        $row = [
            'user_id' => $user_id,
            'source_mode' => $source_mode,
            'nik_ciphertext' => $this->encrypt_value($nik),
            'nik_lookup_hash' => $nik_hash,
            'family_card_ciphertext' => $this->encrypt_optional($data['family_card_number'] ?? NULL),
            'family_card_lookup_hash' => $this->hash_optional($data['family_card_number'] ?? NULL),
            'full_name_ciphertext' => $this->encrypt_value($name),
            'address_ciphertext' => $this->encrypt_optional($data['address'] ?? NULL),
            'phone_ciphertext' => $this->encrypt_optional($data['phone'] ?? NULL),
            'birth_date_ciphertext' => $this->encrypt_optional($data['birth_date'] ?? NULL),
            'tax_number_ciphertext' => $this->encrypt_optional($data['tax_number'] ?? NULL),
            'field_provenance_json' => $this->encode_json($provenance),
        ];

        foreach (self::PROFILE_FIELDS as $field) {
            $row[$field] = array_key_exists($field, $data) ? $data[$field] : NULL;
        }

        if ($row['field_provenance_json'] === FALSE || $this->contains_unencrypted_sensitive($row)) {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $this->db->trans_start();
        if ($existing) {
            $this->db->where('id', (int) $existing['id'])->update('sf_profil_warga', $row);
            $profile_id = (int) $existing['id'];
        } else {
            $this->db->insert('sf_profil_warga', $row);
            $profile_id = (int) $this->db->insert_id();
        }
        $this->db->trans_complete();

        return $this->db->trans_status()
            ? ['success' => TRUE, 'profile_id' => $profile_id]
            : $this->fail('write_failed', 'Profil warga belum dapat disimpan.');
    }

    public function store_source_snapshot(
        $nik,
        $source_mode,
        $source_record_key,
        $response_status,
        $payload,
        array $meta = []
    ) {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)
            || ! in_array($source_mode, self::SOURCE_MODES, TRUE)
            || ! in_array($response_status, self::SNAPSHOT_STATUSES, TRUE)) {
            return $this->fail('invalid_snapshot', 'Snapshot sumber tidak valid.');
        }
        if ( ! $this->encryption_ready()) {
            return $this->fail('encryption_unavailable', 'Snapshot sumber belum dapat disimpan.');
        }

        $nik_hash = $this->encryption_lib->deterministic_hash($nik);
        $payload_json = $payload === NULL ? NULL : $this->encode_json($payload);
        if ($payload !== NULL && $payload_json === FALSE) {
            return $this->fail('invalid_payload', 'Payload sumber tidak dapat disimpan.');
        }
        $payload_ciphertext = $payload_json === NULL ? NULL : $this->encrypt_value($payload_json);
        if ($nik_hash === '' || ($payload_json !== NULL && ! $this->encryption_lib->is_encrypted($payload_ciphertext))) {
            return $this->fail('encryption_unavailable', 'Snapshot sumber belum dapat disimpan.');
        }

        $fetched_at = $meta['fetched_at'] ?? date('Y-m-d H:i:s');
        $default_expiry = $response_status === 'found'
            ? '+30 days'
            : ($response_status === 'not_found' ? '+1 day' : '+15 minutes');
        $expires_at = $meta['expires_at'] ?? date('Y-m-d H:i:s', strtotime($default_expiry));
        $row = [
            'nik_lookup_hash' => $nik_hash,
            'source_mode' => $source_mode,
            'source_record_key' => $source_record_key ?: NULL,
            'response_status' => $response_status,
            'api_version' => $meta['api_version'] ?? NULL,
            'http_status' => isset($meta['http_status']) ? (int) $meta['http_status'] : NULL,
            'error_code' => $meta['error_code'] ?? NULL,
            'payload_ciphertext' => $payload_ciphertext,
            'payload_sha256' => $payload_json === NULL ? NULL : hash('sha256', $payload_json),
            'fetched_at' => $fetched_at,
            'expires_at' => $expires_at,
            'requested_by' => empty($meta['requested_by']) ? NULL : (int) $meta['requested_by'],
        ];

        return $this->db->insert('sf_rekaman_simperum', $row)
            ? ['success' => TRUE, 'snapshot_id' => (int) $this->db->insert_id()]
            : $this->fail('write_failed', 'Snapshot sumber belum dapat disimpan.');
    }

    public function get_active_source_snapshot($nik, $source_mode)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)
            || ! in_array($source_mode, self::SOURCE_MODES, TRUE)
            || ! $this->encryption_ready()) {
            return NULL;
        }

        $row = $this->db
            ->where('nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
            ->where('source_mode', $source_mode)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('sf_rekaman_simperum')
            ->row_array();
        if ( ! $row) {
            return NULL;
        }

        $payload_json = $row['payload_ciphertext'] === NULL
            ? NULL : $this->encryption_lib->decrypt($row['payload_ciphertext']);
        $payload = $payload_json === NULL ? [] : json_decode($payload_json, TRUE);
        if ( ! is_array($payload)) {
            return NULL;
        }

        $row['payload'] = $payload;
        unset($row['payload_ciphertext']);
        return $row;
    }

    public function create_draft(
        $user_id,
        $profile_id,
        $kabupaten_id,
        $track = 'undetermined',
        $source_mode = 'simulation',
        $snapshot_id = NULL,
        $previous_version_id = NULL
    ) {
        $user_id = (int) $user_id;
        $profile_id = (int) $profile_id;
        $kabupaten_id = (int) $kabupaten_id;

        if ($user_id < 1 || $profile_id < 1 || $kabupaten_id < 1
            || ! in_array($track, self::TRACKS, TRUE)
            || ! in_array($source_mode, self::SOURCE_MODES, TRUE)) {
            return $this->fail('invalid_draft', 'Data draft tidak valid.');
        }

        $profile = $this->db->select('nik_lookup_hash, source_mode')
            ->get_where('sf_profil_warga', ['id' => $profile_id, 'user_id' => $user_id])
            ->row_array();
        $scope_exists = $this->db->where('id', $kabupaten_id)
            ->count_all_results('kabupaten') === 1;
        if ( ! $profile || ! $scope_exists || $profile['source_mode'] !== $source_mode) {
            return $this->fail('ownership_or_scope_invalid', 'Profil atau wilayah tidak valid.');
        }
        if ($snapshot_id) {
            $snapshot = $this->db->select('nik_lookup_hash, source_mode')
                ->get_where('sf_rekaman_simperum', ['id' => (int) $snapshot_id])
                ->row_array();
            if ( ! $snapshot
                || $snapshot['source_mode'] !== $source_mode
                || ! hash_equals($profile['nik_lookup_hash'], $snapshot['nik_lookup_hash'])) {
                return $this->fail('snapshot_invalid', 'Snapshot sumber tidak valid.');
            }
        }

        $version_no = 1;
        if ($previous_version_id) {
            $previous = $this->db->select('id, user_id, version_no')
                ->get_where('sf_penilaian_perumahan', ['id' => (int) $previous_version_id])
                ->row_array();
            if ( ! $previous || (int) $previous['user_id'] !== $user_id) {
                return $this->fail('previous_version_invalid', 'Versi sebelumnya tidak valid.');
            }
            $version_no = (int) $previous['version_no'] + 1;
        }

        $row = [
            'user_id' => $user_id,
            'citizen_profile_id' => $profile_id,
            'previous_version_id' => $previous_version_id ? (int) $previous_version_id : NULL,
            'kabupaten_id' => $kabupaten_id,
            'assessment_track' => $track,
            'status' => 'draft',
            'current_step' => 'find_data',
            'version_no' => $version_no,
            'lock_version' => 0,
            'simperum_snapshot_id' => $snapshot_id ? (int) $snapshot_id : NULL,
            'source_mode' => $source_mode,
        ];

        return $this->db->insert('sf_penilaian_perumahan', $row)
            ? [
                'success' => TRUE,
                'assessment_id' => (int) $this->db->insert_id(),
                'lock_version' => 0,
            ]
            : $this->fail('write_failed', 'Draft belum dapat dibuat.');
    }

    public function update_owned_draft($assessment_id, $user_id, $expected_lock_version, array $data)
    {
        $assessment_id = (int) $assessment_id;
        $user_id = (int) $user_id;
        $expected_lock_version = (int) $expected_lock_version;

        $safe = [];
        foreach (self::DRAFT_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $safe[$field] = $data[$field];
            }
        }
        foreach (['candidate_land_address', 'location_lat', 'location_lng'] as $field) {
            if (array_key_exists($field, $data)) {
                if ( ! $this->encryption_ready()) {
                    return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
                }
                $column = $field === 'candidate_land_address'
                    ? 'candidate_land_address_ciphertext'
                    : $field . '_ciphertext';
                $safe[$column] = $this->encrypt_optional($data[$field]);
                if ($safe[$column] !== NULL && ! $this->encryption_lib->is_encrypted($safe[$column])) {
                    return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
                }
            }
        }
        if (isset($safe['assessment_track']) && ! in_array($safe['assessment_track'], self::TRACKS, TRUE)) {
            return $this->fail('invalid_track', 'Cabang assessment tidak valid.');
        }
        if ($assessment_id < 1 || $user_id < 1 || empty($safe)) {
            return $this->fail('invalid_update', 'Perubahan draft tidak valid.');
        }

        $safe['lock_version'] = $expected_lock_version + 1;
        $updated = $this->db
            ->where('id', $assessment_id)
            ->where('user_id', $user_id)
            ->where('status', 'draft')
            ->where('lock_version', $expected_lock_version)
            ->update('sf_penilaian_perumahan', $safe);

        if ( ! $updated || $this->db->affected_rows() !== 1) {
            return $this->fail(
                'stale_or_not_owned',
                'Draft sudah berubah atau tidak dapat diakses. Muat ulang data terbaru.'
            );
        }

        return ['success' => TRUE, 'lock_version' => $expected_lock_version + 1];
    }

    public function get_owned_assessment($assessment_id, $user_id)
    {
        $row = $this->db
            ->where('id', (int) $assessment_id)
            ->where('user_id', (int) $user_id)
            ->get('sf_penilaian_perumahan')
            ->row_array();
        return $this->decrypt_assessment($row);
    }

    public function get_latest_owned_draft($user_id)
    {
        $row = $this->db
            ->where('user_id', (int) $user_id)
            ->where('status', 'draft')
            ->order_by('updated_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('sf_penilaian_perumahan')
            ->row_array();
        return $this->decrypt_assessment($row);
    }

    public function get_owned_profile($user_id)
    {
        $row = $this->db->get_where('sf_profil_warga', ['user_id' => (int) $user_id])->row_array();
        if ( ! $row || ! $this->encryption_ready()) {
            return NULL;
        }

        foreach ([
            'nik' => 'nik_ciphertext', 'family_card_number' => 'family_card_ciphertext',
            'full_name' => 'full_name_ciphertext', 'address' => 'address_ciphertext',
            'phone' => 'phone_ciphertext', 'birth_date' => 'birth_date_ciphertext',
            'tax_number' => 'tax_number_ciphertext',
        ] as $name => $column) {
            $row[$name] = $row[$column] === NULL ? NULL : $this->encryption_lib->decrypt($row[$column]);
            unset($row[$column]);
        }
        return $row;
    }

    public function source_snapshot_kabupaten_id($snapshot_id)
    {
        if ( ! $this->encryption_ready()) {
            return NULL;
        }
        $row = $this->db->select('payload_ciphertext')
            ->get_where('sf_rekaman_simperum', ['id' => (int) $snapshot_id])
            ->row_array();
        $payload = $row ? json_decode($this->encryption_lib->decrypt($row['payload_ciphertext']), TRUE) : NULL;
        $kabupaten_id = is_array($payload) ? (int) ($payload['location']['kabupaten_id'] ?? 0) : 0;
        return $kabupaten_id > 0 ? $kabupaten_id : NULL;
    }

    public function source_snapshot_prefill($snapshot_id)
    {
        if ( ! $this->encryption_ready()) { return []; }
        $row = $this->db->select('payload_ciphertext')->get_where('sf_rekaman_simperum', ['id' => (int) $snapshot_id])->row_array();
        $payload = $row ? json_decode($this->encryption_lib->decrypt($row['payload_ciphertext']), TRUE) : [];
        $prefill = [];
        foreach (['housing', 'structure', 'sanitation', 'location'] as $group) {
            foreach ((array) ($payload[$group] ?? []) as $field => $value) {
                if (in_array($field, self::DRAFT_FIELDS, TRUE)
                    || in_array($field, ['location_lat', 'location_lng'], TRUE)) {
                    $prefill[$field] = $value;
                }
            }
        }
        return $prefill;
    }

    public function save_owned_step(
        $assessment_id,
        $user_id,
        $expected_lock_version,
        array $draft_data,
        ?array $profile_data = NULL,
        array $provenance = [],
        ?array $recommendations = NULL,
        $recommendation_hash = NULL
    )
    {
        $this->db->trans_begin();
        $updated = $this->update_owned_draft($assessment_id, $user_id, $expected_lock_version, $draft_data);
        if (empty($updated['success'])) {
            $this->db->trans_rollback();
            return $updated;
        }
        if ($profile_data !== NULL) {
            $profile = $this->save_profile($user_id, $profile_data, $provenance);
            if (empty($profile['success'])) {
                $this->db->trans_rollback();
                return $profile;
            }
        }
        if ($recommendations !== NULL) {
            $saved = $this->write_recommendations(
                $assessment_id,
                $recommendations,
                (string) $recommendation_hash
            );
            if (empty($saved['success'])) {
                $this->db->trans_rollback();
                return $saved;
            }
        }
        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->fail('write_failed', 'Draft belum dapat disimpan.');
        }
        $this->db->trans_commit();
        return $updated;
    }

    public function replace_recommendations($assessment_id, $user_id, array $items, $hash)
    {
        $assessment = $this->get_owned_assessment($assessment_id, $user_id);
        if ( ! $assessment || $assessment['status'] !== 'draft') {
            return $this->fail('not_owned', 'Draft tidak dapat diakses.');
        }
        $this->db->trans_begin();
        $saved = $this->write_recommendations($assessment_id, $items, (string) $hash);
        if (empty($saved['success']) || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return empty($saved['success'])
                ? $saved : $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
        }
        $this->db->trans_commit();
        return $saved;
    }

    public function get_owned_recommendations($assessment_id, $user_id, $ruleset_version = NULL)
    {
        $query = $this->db
            ->select('r.id recommendation_id,p.kode_program program_code,p.nama_program program_name,r.eligibility_status,r.reason_codes_json,r.ruleset_version')
            ->from('sf_rekomendasi_penilaian r')
            ->join('sf_programs p', 'p.id=r.program_id')
            ->join('sf_penilaian_perumahan a', 'a.id=r.assessment_id')
            ->where('a.id', (int) $assessment_id)
            ->where('a.user_id', (int) $user_id);
        if ($ruleset_version !== NULL) {
            $query->where('r.ruleset_version', (string) $ruleset_version);
        }
        $rows = $query
            ->order_by('r.id', 'ASC')
            ->get()
            ->result_array();
        foreach ($rows as &$row) {
            $row['reason_codes'] = json_decode($row['reason_codes_json'] ?? '[]', TRUE) ?: [];
            unset($row['reason_codes_json']);
        }
        unset($row);
        return $rows;
    }

    public function submit_owned_assessment($assessment_id, $user_id, $recommendation_id, $ruleset_version)
    {
        $assessment_id = (int) $assessment_id;
        $user_id = (int) $user_id;
        $recommendation_id = (int) $recommendation_id;
        $submission_key = hash('sha256', implode(':', ['warga', $user_id, $assessment_id, $recommendation_id, $ruleset_version]));
        $existing = $this->db->get_where('sf_housing_queue', ['submission_key' => $submission_key])->row_array();
        if ($existing) { return $this->queue_result($existing); }

        $assessment = $this->db->get_where('sf_penilaian_perumahan', [
            'id' => $assessment_id, 'user_id' => $user_id, 'status' => 'draft',
        ])->row_array();
        $recommendation = $this->db
            ->select('r.id, r.program_id, r.eligibility_status, p.is_active')
            ->from('sf_rekomendasi_penilaian r')
            ->join('sf_programs p', 'p.id=r.program_id')
            ->where(['r.id' => $recommendation_id, 'r.assessment_id' => $assessment_id, 'r.ruleset_version' => $ruleset_version])
            ->get()->row_array();
        if ( ! $assessment || ! $recommendation
            || ! in_array($recommendation['eligibility_status'], ['eligible', 'potential'], TRUE)
            || (int) $recommendation['is_active'] !== 1 || empty($assessment['kabupaten_id'])
            || $ruleset_version !== Warga_ruleset::VERSION
            || Warga_ruleset::STATUS !== 'active'
            || strtotime(Warga_ruleset::EFFECTIVE_FROM) > time()
            || $assessment['current_step'] !== 'review') {
            return $this->fail('submission_invalid', 'Draft atau rekomendasi tidak dapat diajukan.');
        }
        $profile = $this->get_owned_profile($user_id);
        if ( ! $profile) { return $this->fail('profile_missing', 'Profil warga tidak tersedia.'); }
        $profile_snapshot = $this->encrypt_value($this->encode_json($profile));
        if ( ! $this->encryption_lib->is_encrypted($profile_snapshot)) {
            return $this->fail('encryption_unavailable', 'Snapshot profil belum dapat disimpan.');
        }

        $this->db->trans_begin();
        $updated = $this->db->where([
            'id' => $assessment_id, 'user_id' => $user_id, 'status' => 'draft',
        ])->update('sf_penilaian_perumahan', [
            'status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s'),
            'profile_snapshot_ciphertext' => $profile_snapshot,
        ]);
        if ( ! $updated || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            $existing = $this->db->get_where('sf_housing_queue', ['submission_key' => $submission_key])->row_array();
            return $existing ? $this->queue_result($existing) : $this->fail('stale_draft', 'Draft sudah berubah.');
        }

        if ( ! empty($assessment['previous_version_id'])) {
            $queue = $this->db->get_where('sf_housing_queue', [
                'user_id' => $user_id, 'assessment_id' => (int) $assessment['previous_version_id'],
                'status_antrean' => 'needs_revision',
            ])->row_array();
            if ( ! $queue || ! $this->db->where(['id' => $queue['id'], 'status_antrean' => 'needs_revision'])
                ->update('sf_housing_queue', [
                    'assessment_id' => $assessment_id, 'recommendation_id' => $recommendation_id,
                    'program_id' => $recommendation['program_id'], 'submission_key' => $submission_key,
                    'status_antrean' => 'pending', 'catatan_admin' => NULL,
                    'reviewed_by' => NULL, 'reviewed_at' => NULL, 'updated_at' => date('Y-m-d H:i:s'),
                ]) || $this->db->affected_rows() !== 1) {
                $this->db->trans_rollback();
                return $this->fail('revision_stale', 'Revisi tidak dapat dikirim ulang.');
            }
            $history_inserted = $this->db->insert('sf_riwayat_keputusan_antrean', [
                'queue_id' => $queue['id'], 'from_status' => 'needs_revision',
                'to_status' => 'pending', 'note' => NULL, 'actor_id' => $user_id,
            ]);
            if ( ! $history_inserted) {
                $this->db->trans_rollback();
                return $this->fail('write_failed', 'Riwayat pengajuan belum dapat disimpan.');
            }
            $superseded = $this->db->where([
                'id' => (int) $assessment['previous_version_id'], 'status' => 'submitted',
            ])->update('sf_penilaian_perumahan', ['status' => 'superseded']);
            if ( ! $superseded || $this->db->affected_rows() !== 1) {
                $this->db->trans_rollback();
                return $this->fail('revision_stale', 'Versi sebelumnya sudah berubah.');
            }
            $queue_id = (int) $queue['id']; $ticket = $queue['ticket_code'];
        } else {
            $ticket = $this->generate_ticket_code();
            $inserted = $this->db->insert('sf_housing_queue', [
                'ticket_code' => $ticket, 'user_id' => $user_id, 'kabupaten_id' => $assessment['kabupaten_id'],
                'assessment_id' => $assessment_id, 'recommendation_id' => $recommendation_id,
                'submission_key' => $submission_key, 'program_id' => $recommendation['program_id'],
                'nik_pengaju' => NULL, 'nama_lengkap' => NULL,
                'status_antrean' => 'pending', 'source_mode' => $assessment['source_mode'],
            ]);
            if ( ! $inserted) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Pengajuan belum dapat disimpan.'); }
            $queue_id = (int) $this->db->insert_id();
            if ( ! $this->db->insert('sf_riwayat_keputusan_antrean', [
                'queue_id' => $queue_id, 'from_status' => NULL,
                'to_status' => 'pending', 'note' => NULL, 'actor_id' => $user_id,
            ])) {
                $this->db->trans_rollback();
                return $this->fail('write_failed', 'Riwayat pengajuan belum dapat disimpan.');
            }
        }
        if ( ! $this->db->trans_status()) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Pengajuan belum dapat disimpan.'); }
        $this->db->trans_commit();
        return ['success' => TRUE, 'queue_id' => $queue_id, 'ticket_code' => $ticket, 'assessment_id' => $assessment_id];
    }

    public function resubmit_revision($assessment_id, $user_id, $recommendation_id, $ruleset_version)
    {
        return $this->submit_owned_assessment($assessment_id, $user_id, $recommendation_id, $ruleset_version);
    }

    public function start_revision($queue_id, $user_id)
    {
        $this->db->trans_begin();
        $queue = $this->db->query("SELECT * FROM sf_housing_queue
            WHERE id=? AND user_id=? AND status_antrean='needs_revision' FOR UPDATE",
            [(int) $queue_id, (int) $user_id])->row_array();
        if ( ! $queue || empty($queue['assessment_id'])) {
            $this->db->trans_rollback();
            return $this->fail('revision_unavailable', 'Revisi tidak tersedia.');
        }
        $existing = $this->db->get_where('sf_penilaian_perumahan', [
            'user_id' => (int) $user_id, 'previous_version_id' => (int) $queue['assessment_id'], 'status' => 'draft',
        ])->row_array();
        if ($existing) {
            $this->db->trans_commit();
            return ['success' => TRUE, 'queue_id' => (int) $queue_id, 'ticket_code' => $queue['ticket_code'], 'assessment_id' => (int) $existing['id']];
        }

        $source = $this->db->get_where('sf_penilaian_perumahan', [
            'id' => (int) $queue['assessment_id'], 'user_id' => (int) $user_id, 'status' => 'submitted',
        ])->row_array();
        if ( ! $source) {
            $this->db->trans_rollback();
            return $this->fail('revision_source_missing', 'Versi pengajuan tidak tersedia.');
        }
        foreach (['id', 'created_at', 'updated_at', 'submitted_at'] as $field) unset($source[$field]);
        $source['previous_version_id'] = (int) $queue['assessment_id'];
        $source['version_no'] = (int) $source['version_no'] + 1;
        $source['status'] = 'draft'; $source['current_step'] = 'citizen_data';
        $source['lock_version'] = 0; $source['profile_snapshot_ciphertext'] = NULL;

        if ( ! $this->db->insert('sf_penilaian_perumahan', $source)) {
            $this->db->trans_rollback(); return $this->fail('write_failed', 'Draft revisi belum dapat dibuat.');
        }
        $new_id = (int) $this->db->insert_id();
        $this->db->query("INSERT INTO sf_berkas_penilaian
            (assessment_id,file_kind,private_path,original_name_ciphertext,mime_type,size_bytes,sha256,uploaded_by,created_at,verified_by,verified_at)
            SELECT ?,file_kind,private_path,original_name_ciphertext,mime_type,size_bytes,sha256,uploaded_by,NOW(),NULL,NULL
            FROM sf_berkas_penilaian WHERE assessment_id=?", [$new_id, (int) $queue['assessment_id']]);
        if ( ! $this->db->trans_status()) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Draft revisi belum dapat dibuat.'); }
        $this->db->trans_commit();
        return ['success' => TRUE, 'queue_id' => (int) $queue_id, 'ticket_code' => $queue['ticket_code'], 'assessment_id' => $new_id];
    }

    public function transition_queue($queue_id, $from, $to, $reviewer_id, $kabupaten_id = NULL, $catatan = '')
    {
        $catatan = trim((string) $catatan);
        $this->db->select('id,assessment_id,status_antrean')->where('id', (int) $queue_id);
        if ($kabupaten_id !== NULL) $this->db->where('kabupaten_id', (int) $kabupaten_id);
        $queue = $this->db->get('sf_housing_queue')->row_array();
        if ($queue && empty($from) && empty($queue['assessment_id'])) {
            $from = $queue['status_antrean'];
        }
        if ( ! $queue || $queue['status_antrean'] !== $from) {
            return $this->fail('stale_or_out_of_scope', 'Pengajuan sudah berubah atau di luar wilayah.');
        }

        $assessment_flow = ! empty($queue['assessment_id']);
        $valid = $assessment_flow
            ? $from === 'pending' && in_array($to, ['needs_revision', 'approved', 'rejected'], TRUE)
            : $to !== 'needs_revision' && housing_queue_can_transition($from, $to);
        if ( ! $valid || (in_array($to, ['needs_revision', 'rejected'], TRUE) && $catatan === '')) {
            return $this->fail('invalid_transition', 'Perubahan status tidak valid.');
        }
        $this->db->trans_begin();
        $this->db->where(['id' => (int) $queue_id, 'status_antrean' => $from]);
        if ($kabupaten_id !== NULL) $this->db->where('kabupaten_id', (int) $kabupaten_id);
        $ok = $this->db
            ->update('sf_housing_queue', [
                'status_antrean' => $to, 'catatan_admin' => $catatan ?: NULL,
                'reviewed_by' => (int) $reviewer_id, 'reviewed_at' => date('Y-m-d H:i:s'),
            ]);
        if ( ! $ok || $this->db->affected_rows() !== 1
            || ! $this->db->insert('sf_riwayat_keputusan_antrean', [
                'queue_id' => (int) $queue_id, 'from_status' => $from, 'to_status' => $to,
                'note' => $catatan ?: NULL, 'actor_id' => (int) $reviewer_id,
            ]) || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->fail('stale_or_out_of_scope', 'Pengajuan sudah berubah atau di luar wilayah.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'queue_id' => (int) $queue_id];
    }

    public function get_latest_owned_flow($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)->order_by('updated_at', 'DESC')
            ->limit(1)->get('sf_housing_queue')->row_array();
    }

    public function get_scoped_queue_detail($queue_id, $kabupaten_id = NULL)
    {
        $this->db->select('q.*,p.kode_program,p.nama_program,r.eligibility_status,r.ruleset_version,r.reason_codes_json')
            ->from('sf_housing_queue q')
            ->join('sf_rekomendasi_penilaian r', 'r.id=q.recommendation_id', 'left')
            ->join('sf_programs p', 'p.id=q.program_id', 'left')
            ->where('q.id', (int) $queue_id);
        if ($kabupaten_id !== NULL) $this->db->where('q.kabupaten_id', (int) $kabupaten_id);
        $queue = $this->db->get()->row_array();
        if ( ! $queue) return NULL;
        $assessment = $this->decrypt_assessment($this->db->get_where('sf_penilaian_perumahan', ['id' => $queue['assessment_id']])->row_array());
        $profile = [];
        if (is_array($assessment) && ! empty($assessment['profile_snapshot_ciphertext'])) {
            $profile = json_decode($this->encryption_lib->decrypt($assessment['profile_snapshot_ciphertext']), TRUE) ?: [];
            unset($assessment['profile_snapshot_ciphertext']);
        }
        $recommendation = [
            'recommendation_id' => $queue['recommendation_id'],
            'eligibility_status' => $queue['eligibility_status'],
            'ruleset_version' => $queue['ruleset_version'],
            'reason_codes' => json_decode($queue['reason_codes_json'] ?? '[]', TRUE) ?: [],
        ];
        unset($queue['eligibility_status'], $queue['ruleset_version'], $queue['reason_codes_json']);
        return ['queue' => $queue, 'assessment' => $assessment,
            'profile_snapshot' => $profile, 'recommendation' => $recommendation];
    }

    public function get_scoped_queue_files($queue_id, $kabupaten_id = NULL)
    {
        $queue = $this->get_scoped_queue_detail($queue_id, $kabupaten_id);
        if ( ! $queue || empty($queue['queue']['assessment_id'])) return [];
        return $this->files_with_storage_owner((int) $queue['queue']['assessment_id']);
    }

    private function write_recommendations($assessment_id, array $items, $hash)
    {
        if ( ! preg_match('/^[a-f0-9]{64}$/', $hash)) {
            return $this->fail('invalid_recommendation_hash', 'Input evaluasi rekomendasi tidak valid.');
        }
        if (empty($items)) {
            return ['success' => TRUE];
        }

        $versions = [];
        $programs = [];
        foreach ($items as $item) {
            $code = (string) ($item['program_code'] ?? '');
            $version = (string) ($item['ruleset_version'] ?? '');
            $status = (string) ($item['eligibility_status'] ?? '');
            if ($code === '' || $version === ''
                || ! in_array($status, ['eligible', 'potential', 'not_eligible', 'needs_data'], TRUE)) {
                return $this->fail('invalid_recommendation', 'Hasil evaluasi rekomendasi tidak valid.');
            }
            $program = $this->db->select('id')->get_where('sf_programs', ['kode_program' => $code])->row_array();
            if ( ! $program) {
                return $this->fail('program_not_found', 'Program rekomendasi tidak tersedia.');
            }
            $programs[$code] = (int) $program['id'];
            $versions[$version] = TRUE;
        }

        foreach (array_keys($versions) as $version) {
            if ( ! $this->db->where('assessment_id', (int) $assessment_id)
                ->where('ruleset_version', $version)
                ->delete('sf_rekomendasi_penilaian')) {
                return $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
            }
        }
        foreach ($items as $item) {
            if ( ! $this->db->insert('sf_rekomendasi_penilaian', [
                'assessment_id' => (int) $assessment_id,
                'program_id' => $programs[$item['program_code']],
                'ruleset_version' => $item['ruleset_version'],
                'eligibility_status' => $item['eligibility_status'],
                'reason_codes_json' => $this->encode_json($item['reason_codes'] ?? []),
                'input_snapshot_sha256' => $hash,
                'evaluated_at' => date('Y-m-d H:i:s'),
            ])) {
                return $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
            }
        }
        return ['success' => TRUE];
    }

    public function replace_owned_file($assessment_id, $user_id, $file_kind, $private_path, $original_name, $mime, $size, $sha256)
    {
        $owned = $this->get_owned_assessment($assessment_id, $user_id);
        if ( ! $owned || $owned['status'] !== 'draft' || !in_array($file_kind, self::EVIDENCE_KINDS, TRUE) || ! $this->encryption_ready()) {
            return $this->fail('not_owned_or_unavailable', 'Draft tidak dapat diakses.');
        }
        $old = $this->db->get_where('sf_berkas_penilaian', ['assessment_id' => (int) $assessment_id, 'file_kind' => $file_kind])->row_array();
        $row = ['assessment_id' => (int) $assessment_id, 'file_kind' => $file_kind, 'private_path' => $private_path,
            'original_name_ciphertext' => $this->encrypt_value($original_name), 'mime_type' => $mime,
            'size_bytes' => (int) $size, 'sha256' => $sha256, 'uploaded_by' => (int) $user_id];
        $ok = $old ? $this->db->where('id', $old['id'])->update('sf_berkas_penilaian', $row) : $this->db->insert('sf_berkas_penilaian', $row);
        $old_path = $old['private_path'] ?? NULL;
        if ($old_path && $this->db->where('private_path', $old_path)->where('id !=', $old['id'])
            ->count_all_results('sf_berkas_penilaian') > 0) {
            $old_path = NULL;
        }
        return $ok ? ['success' => TRUE, 'old_path' => $old_path] : $this->fail('write_failed', 'Berkas belum dapat disimpan.');
    }

    public function get_owned_files($assessment_id, $user_id)
    {
        if ( ! $this->get_owned_assessment($assessment_id, $user_id)) {
            return [];
        }
        $rows = $this->files_with_storage_owner((int) $assessment_id);
        $files = [];
        foreach ($rows as $row) {
            $files[$row['file_kind']] = $row;
        }
        return $files;
    }

    private function files_with_storage_owner($assessment_id)
    {
        return $this->db->select('f.id,f.file_kind,f.private_path,f.mime_type,f.size_bytes,f.created_at,
                (SELECT MIN(f2.assessment_id) FROM sf_berkas_penilaian f2
                 WHERE f2.private_path=f.private_path AND f2.sha256=f.sha256) storage_assessment_id', FALSE)
            ->from('sf_berkas_penilaian f')->where('f.assessment_id', (int) $assessment_id)
            ->get()->result_array();
    }

    private function encrypt_value($value)
    {
        return $this->encryption_lib->encrypt((string) $value);
    }

    private function decrypt_assessment($row)
    {
        if (!$row || !$this->encryption_ready()) return $row;
        foreach (['candidate_land_address' => 'candidate_land_address_ciphertext', 'location_lat' => 'location_lat_ciphertext', 'location_lng' => 'location_lng_ciphertext'] as $name => $column) {
            $row[$name] = $row[$column] === NULL ? NULL : $this->encryption_lib->decrypt($row[$column]);
            unset($row[$column]);
        }
        return $row;
    }

    private function encrypt_optional($value)
    {
        $value = trim((string) $value);
        return $value === '' ? NULL : $this->encrypt_value($value);
    }

    private function hash_optional($value)
    {
        $value = preg_replace('/\D+/', '', (string) $value);
        return $value === '' ? NULL : $this->encryption_lib->deterministic_hash($value);
    }

    private function contains_unencrypted_sensitive(array $row)
    {
        foreach ([
            'nik_ciphertext', 'family_card_ciphertext', 'full_name_ciphertext',
            'address_ciphertext', 'phone_ciphertext', 'birth_date_ciphertext',
            'tax_number_ciphertext',
        ] as $field) {
            if ($row[$field] !== NULL && ! $this->encryption_lib->is_encrypted($row[$field])) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function encryption_ready()
    {
        $key = getenv('KPKP_DATA_KEY');
        $pepper = getenv('KPKP_DATA_PEPPER');
        return is_string($key)
            && preg_match('/^[a-f0-9]{64}$/i', $key)
            && is_string($pepper)
            && trim($pepper) !== '';
    }

    private function encode_json($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json;
    }

    private function generate_ticket_code()
    {
        do {
            $ticket = 'PKP-';
            for ($i = 0; $i < 6; $i++) {
                $ticket .= 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)];
            }
        } while ($this->db->where('ticket_code', $ticket)->count_all_results('sf_housing_queue'));
        return $ticket;
    }

    private function queue_result(array $queue)
    {
        return ['success' => TRUE, 'queue_id' => (int) $queue['id'],
            'ticket_code' => $queue['ticket_code'], 'assessment_id' => (int) $queue['assessment_id']];
    }

    private function fail($code, $message)
    {
        return ['success' => FALSE, 'code' => $code, 'message' => $message];
    }
}
