<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warga extends MY_Controller {

    private const STEPS = ['find_data', 'citizen_data', 'housing_family', 'building_condition', 'candidate_land', 'sanitation', 'location_evidence', 'review'];
    private const STEP_LABELS = ['Temukan Data', 'Data Warga', 'Rumah & Keluarga', 'Kondisi Bangunan/Calon Lahan', 'Sanitasi & Utilitas', 'Lokasi & Bukti', 'Review & Rekomendasi'];

    public function __construct()
    {
        parent::__construct();
        if ( ! $this->is_logged_in() || ! $this->has_role('warga')) {
            $this->session->set_flashdata('error', 'Akses pendataan hanya untuk akun warga.');
            redirect('login');
        }
        $this->load->model('Housing_assessment_model');
        $this->load->library('Simperum_gateway');
        $this->load->library('Warga_ruleset');
    }

    public function pendataan()
    {
        if ($this->input->method() === 'post') {
            $this->handle_post();
            return;
        }

        $user_id = (int) $this->get_user_id();
        $assessment = $this->Housing_assessment_model->get_latest_owned_draft($user_id);
        $profile = $this->Housing_assessment_model->get_owned_profile($user_id);
        $provenance = json_decode($profile['field_provenance_json'] ?? '{}', TRUE) ?: [];
        foreach ($provenance as $field => $meta) {
            $provenance[$field] = is_array($meta) ? ($meta['source'] ?? 'citizen') : $meta;
        }
        if ($assessment && ! empty($assessment['simperum_snapshot_id'])) {
            foreach ($this->Housing_assessment_model->source_snapshot_prefill($assessment['simperum_snapshot_id']) as $field => $source_value) {
                if (array_key_exists($field, $assessment) && $assessment[$field] !== NULL) {
                    $provenance[$field] = (string) $assessment[$field] === (string) $source_value
                        ? $assessment['source_mode'] : 'citizen_correction';
                }
            }
        }
        $old_input = $this->session->flashdata('warga_old_input') ?: [];
        $this->render('pages/warga/pendataan', [
            'title' => 'Pendataan Warga',
            'assessment' => $assessment,
            'profile' => $profile,
            'values' => array_merge($assessment ?: [], $profile ?: [], $old_input),
            'lookup' => $this->session->flashdata('warga_lookup'),
            'errors' => $this->session->flashdata('warga_errors') ?: [],
            'step' => $assessment['current_step'] ?? 'find_data',
            'steps' => self::STEP_LABELS,
            'field_provenance' => $provenance,
            'evidence_files' => $assessment
                ? $this->Housing_assessment_model->get_owned_files($assessment['id'], $user_id) : [],
            'action_url' => site_url('warga/pendataan'),
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
            'recommendations' => $assessment
                ? $this->Housing_assessment_model->get_owned_recommendations(
                    $assessment['id'],
                    $user_id,
                    Warga_ruleset::VERSION
                ) : [],
            'review_summary' => ['welfare_decile'=>$profile['welfare_decile']??NULL,'assessment_track'=>$assessment['assessment_track']??NULL,'source_mode'=>$assessment['source_mode']??NULL],
        ]);
    }

    /**
     * Pemohon membuka kembali bukti yang sudah dia unggah.
     *
     * Tanpa ini warga hanya melihat badge "Sudah tersimpan" tanpa cara
     * memastikan yang terunggah memang berkas yang benar — dan saat petugas
     * meminta perbaikan, dia tidak punya rujukan apa pun. Pola yang sama
     * sudah terbukti di Pengembang::lihat_dokumen_saya() (T3).
     *
     * Kepemilikan disaring get_owned_files() (WHERE user_id di model), jadi
     * assessment milik orang lain mengembalikan daftar kosong -> 404.
     */
    public function lihat_bukti($assessment_id = NULL, $file_kind = NULL)
    {
        if ( ! is_numeric($assessment_id) || empty($file_kind)) { show_404(); return; }
        $files = $this->Housing_assessment_model->get_owned_files(
            (int) $assessment_id, (int) $this->get_user_id()
        );
        $file = $files[$file_kind] ?? NULL;
        if ( ! $file) { show_404(); return; }

        $this->serve_private_file(
            'warga_assessment', $file['storage_assessment_id'], $file['private_path'], $file['mime_type']
        );
    }

    private function handle_post()
    {
        $action = (string) $this->input->post('action', TRUE);
        if ($action === '') {
            $action = $this->input->post('step', TRUE) === 'find_data' ? 'lookup' : 'save';
        }
        if ($action === 'lookup') {
            $this->lookup();
            return;
        }
        if ($action === 'save') {
            $this->save();
            return;
        }
        if ($action === 'upload') {
            $this->upload();
            return;
        }
        if ($action === 'submit') {
            $this->submit();
            return;
        }
        if ($action === 'start_revision') {
            $this->start_revision();
            return;
        }
        show_404();
    }

    private function lookup()
    {
        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $birth_date = trim((string) $this->input->post('birth_date', TRUE));
        $rate = $this->rate_limit_consume('warga_lookup', [
            'account_id' => (int) $this->get_user_id(),
            'nik' => $nik,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan pencarian data. Silakan coba lagi sebentar.',
                $this->input->is_ajax_request()
            );
            return;
        }
        if ( ! preg_match('/^\d{16}$/', $nik) || ! $this->valid_date($birth_date)) {
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik, 'birth_date' => $birth_date]);
            $this->flash_errors(['nik' => 'NIK harus 16 digit.', 'birth_date' => 'Tanggal lahir tidak valid.']);
            redirect('warga/pendataan');
            return;
        }

        $result = $this->simperum_gateway->lookup($nik, $birth_date, (int) $this->get_user_id());
        $this->session->set_flashdata('warga_lookup', $result);
        if (($result['status'] ?? '') !== 'found') {
            $this->session->set_flashdata('error', $result['message'] ?? 'Data belum dapat ditemukan.');
            redirect('warga/pendataan');
            return;
        }

        $user_id = (int) $this->get_user_id();
        $profile = $this->Housing_assessment_model->get_owned_profile($user_id);
        $draft = $this->Housing_assessment_model->get_latest_owned_draft($user_id);
        $previous_draft = NULL;
        $source_mode = (string) ($result['source_mode'] ?? 'simulation');
        $snapshot_id = (int) ($result['data']['snapshot_id'] ?? 0);
        if ($draft && (
            ($draft['source_mode'] ?? '') !== $source_mode
            || (int) ($draft['simperum_snapshot_id'] ?? 0) !== $snapshot_id
        )) {
            $previous_draft = $draft;
            $draft = NULL;
        }
        if ( ! $draft && $profile) {
            $kabupaten_id = $this->Housing_assessment_model->source_snapshot_kabupaten_id($snapshot_id);
            $created = $kabupaten_id
                ? $this->Housing_assessment_model->create_draft(
                    $user_id,
                    $profile['id'],
                    $kabupaten_id,
                    'undetermined',
                    $source_mode,
                    $snapshot_id,
                    $previous_draft['id'] ?? NULL
                )
                : ['success' => FALSE, 'message' => 'Wilayah sumber belum dapat dipakai untuk membuat draft.'];
            if (empty($created['success'])) {
                $this->session->set_flashdata('error', $created['message']);
                redirect('warga/pendataan');
                return;
            }
            $draft = $this->Housing_assessment_model->get_owned_assessment($created['assessment_id'], $user_id);
        }
        if ($draft && $draft['current_step'] === 'find_data') {
            $started = $this->Housing_assessment_model->update_owned_draft(
                $draft['id'], $user_id, $draft['lock_version'],
                ['current_step' => 'citizen_data'] + $this->Housing_assessment_model->source_snapshot_prefill($snapshot_id)
            );
            if (empty($started['success'])) {
                $this->session->set_flashdata('error', $started['message']);
                redirect('warga/pendataan');
                return;
            }
        }
        $this->session->set_flashdata('success', 'Data awal tersimpan. Silakan periksa dan lengkapi pendataan.');
        redirect('warga/pendataan');
    }

    private function save()
    {
        $user_id = (int) $this->get_user_id();
        $assessment_id = (int) $this->input->post('assessment_id', TRUE);
        $lock_version = filter_var($this->input->post('lock_version', TRUE), FILTER_VALIDATE_INT);
        $draft = $this->Housing_assessment_model->get_owned_assessment($assessment_id, $user_id);
        if ( ! $draft || $lock_version === FALSE || (int) $draft['lock_version'] !== $lock_version) {
            $this->session->set_flashdata('error', 'Draft sudah berubah atau tidak dapat diakses. Muat ulang data terbaru.');
            redirect('warga/pendataan');
            return;
        }

        $step = (string) $this->input->post('step', TRUE);
        if ( ! in_array($step, self::STEPS, TRUE) || $step !== $draft['current_step']) {
            show_404();
            return;
        }
        if (($step === 'building_condition' || $step === 'sanitation') && $draft['assessment_track'] !== 'existing_house') { show_404(); return; }
        if ($step === 'candidate_land' && $draft['assessment_track'] !== 'candidate_land') { show_404(); return; }
        $direction = $this->input->post('direction', TRUE) === 'back' ? 'back' : 'next';
        $errors = $direction === 'next' ? $this->step_errors($step) : [];
        if ($errors) {
            $old_input = $this->input->post(NULL, TRUE);
            unset($old_input['action'], $old_input['direction'], $old_input['assessment_id'], $old_input['lock_version']);
            $this->session->set_flashdata('warga_old_input', $old_input);
            $this->flash_errors($errors);
            redirect('warga/pendataan');
            return;
        }
        $data = $direction === 'back' ? [] : $this->draft_data();
        if ($direction === 'next' && $step === 'housing_family') {
            $data['assessment_track'] = $this->assessment_track(
                (string) $this->input->post('housing_status_code', TRUE),
                (string) $this->input->post('owns_candidate_land', TRUE)
            );
        }
        if ($direction === 'next' && $step === 'candidate_land') {
            $data['land_area_m2'] = round((float) $data['land_length_m'] * (float) $data['land_width_m'], 2);
        }
        $data['current_step'] = $this->adjacent_step($step, $direction, $data['assessment_track'] ?? $draft['assessment_track']);
        $profile_change = $direction === 'next' && $step === 'citizen_data'
            ? $this->profile_corrections($user_id) : NULL;
        if ($direction === 'next' && $step === 'citizen_data' && $profile_change === NULL) {
            $this->session->set_flashdata('error', 'Profil warga tidak ditemukan.');
            redirect('warga/pendataan');
            return;
        }
        $recommendations = NULL;
        $recommendation_hash = NULL;
        if ($direction === 'next' && $step === 'location_evidence') {
            $profile = $this->Housing_assessment_model->get_owned_profile($user_id) ?: [];
            $effective = array_merge($draft, $data);
            $recommendations = [];
            foreach ($this->warga_ruleset->route_candidates($profile['welfare_decile'] ?? NULL) as $code) {
                $recommendations[] = $this->warga_ruleset->evaluate($code, $effective, $profile) + [
                    'program_code' => $code,
                    'ruleset_version' => Warga_ruleset::VERSION,
                ];
            }
            $recommendation_hash = $this->recommendation_input_hash($effective, $profile);
        }
        $updated = $this->Housing_assessment_model->save_owned_step(
            $assessment_id, $user_id, $lock_version, $data,
            $profile_change['data'] ?? NULL, $profile_change['provenance'] ?? [],
            $recommendations, $recommendation_hash
        );
        if (empty($updated['success'])) {
            $this->session->set_flashdata('error', $updated['message']);
            redirect('warga/pendataan');
            return;
        }

        $this->session->set_flashdata('success', 'Draft tersimpan.');
        redirect('warga/pendataan');
    }

    private function upload()
    {
        $user_id = (int) $this->get_user_id();
        $assessment_id = (int) $this->input->post('assessment_id', TRUE);
        $draft = $this->Housing_assessment_model->get_owned_assessment($assessment_id, $user_id);
        $kind = (string) $this->input->post('file_kind', TRUE);
        $allowed = $this->evidence_kinds($draft['assessment_track'] ?? '');
        if ( ! $draft || ! in_array($kind, $allowed, TRUE)) {
            show_404(); return;
        }
        if (empty($_FILES[$kind]['tmp_name'])) {
            $this->session->set_flashdata('error', 'Pilih berkas JPG/PNG terlebih dahulu.');
            redirect('warga/pendataan'); return;
        }
        $file = $_FILES[$kind];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ( ! in_array($mime, ['image/jpeg', 'image/png'], TRUE)
            || ! $this->strip_image_metadata($file['tmp_name'], $mime)) {
            $this->session->set_flashdata('error', 'Bukti harus berupa JPG/PNG yang valid.');
            redirect('warga/pendataan'); return;
        }
        $sha256 = hash_file('sha256', $file['tmp_name']);
        $file['size'] = filesize($file['tmp_name']);
        $error = NULL;
        $stored = $this->store_private_upload($kind, 'warga_assessment', $assessment_id, $error);
        if ($stored === FALSE) {
            $this->session->set_flashdata('error', $error ?: 'Berkas belum dapat diunggah.');
            redirect('warga/pendataan'); return;
        }
        $saved = $this->Housing_assessment_model->replace_owned_file(
            $assessment_id, $user_id, $kind, $stored, $file['name'], $mime, $file['size'], $sha256
        );
        $dir = $this->private_upload_dir('warga_assessment', $assessment_id);
        if (empty($saved['success'])) {
            @unlink($dir . $stored);
            $this->session->set_flashdata('error', $saved['message']);
        } else {
            if (!empty($saved['old_path']) && $saved['old_path'] !== $stored) { @unlink($dir . basename($saved['old_path'])); }
            $this->session->set_flashdata('success', 'Berkas tersimpan.');
        }
        redirect('warga/pendataan');
    }

    private function submit()
    {
        $assessment_id = (int) $this->input->post('assessment_id', TRUE);
        $rate = $this->rate_limit_consume('warga_submit', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $assessment_id,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Batas pengiriman pengajuan tercapai. Silakan coba lagi nanti.',
                $this->input->is_ajax_request()
            );
            return;
        }
        $result = $this->Housing_assessment_model->submit_owned_assessment(
            $assessment_id,
            (int) $this->get_user_id(),
            (int) $this->input->post('recommendation_id', TRUE),
            Warga_ruleset::VERSION
        );
        $this->session->set_flashdata(
            ! empty($result['success']) ? 'success' : 'error',
            ! empty($result['success'])
                ? 'Pengajuan berhasil dikirim dengan tiket ' . $result['ticket_code'] . '.'
                : ($result['message'] ?? 'Pengajuan belum dapat dikirim.')
        );
        redirect(! empty($result['success']) ? 'akun' : 'warga/pendataan');
    }

    private function start_revision()
    {
        $queue_id = (int) $this->input->post('queue_id', TRUE);
        $rate = $this->rate_limit_consume('warga_start_revision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $queue_id,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Batas permintaan perbaikan tercapai. Silakan coba lagi nanti.',
                $this->input->is_ajax_request()
            );
            return;
        }
        $result = $this->Housing_assessment_model->start_revision(
            $queue_id,
            (int) $this->get_user_id()
        );
        $this->session->set_flashdata(
            ! empty($result['success']) ? 'success' : 'error',
            ! empty($result['success'])
                ? 'Salinan perbaikan siap. Data pengajuan lama tetap tersimpan.'
                : ($result['message'] ?? 'Perbaikan belum dapat dimulai.')
        );
        redirect(! empty($result['success']) ? 'warga/pendataan' : 'akun');
    }


    private function draft_data()
    {
        $data = [];
        foreach (['assessment_track', 'housing_status_code', 'land_title_code', 'has_other_land', 'has_other_house', 'house_area_m2', 'occupant_count', 'family_count', 'assistance_source_code', 'assistance_year', 'area_condition_code', 'owns_candidate_land', 'candidate_land_title_code', 'candidate_land_origin_code', 'land_owner_relationship_code', 'land_length_m', 'land_width_m', 'land_area_m2', 'foundation_condition_code', 'column_condition_code', 'beam_condition_code', 'sloof_condition_code', 'ceiling_condition_code', 'roof_frame_condition_code', 'floor_material_code', 'floor_condition_code', 'wall_material_code', 'wall_condition_code', 'roof_material_code', 'roof_condition_code', 'has_window', 'has_ventilation', 'water_source_code', 'has_bathroom_latrine', 'latrine_type_code', 'feces_disposal_code', 'septic_distance_code', 'lighting_source_code', 'cooking_fuel_code', 'location_accuracy_m'] as $field) {
            if ($this->input->post($field, TRUE) !== NULL) {
                $data[$field] = $this->input->post($field, TRUE);
            }
        }
        foreach (['candidate_land_address', 'location_lat', 'location_lng'] as $field) {
            if ($this->input->post($field, TRUE) !== NULL) {
                $data[$field] = $this->input->post($field, TRUE);
            }
        }
        return $data;
    }

    private function profile_corrections($user_id)
    {
        $profile = $this->Housing_assessment_model->get_owned_profile($user_id);
        if ( ! $profile) { return NULL; }
        $data = $profile;
        $provenance = json_decode($profile['field_provenance_json'] ?? '{}', TRUE) ?: [];
        foreach (['family_card_number', 'full_name', 'address', 'phone', 'birth_date', 'tax_number', 'gender_code', 'marital_status_code', 'education_code', 'occupation_code', 'income_band_code', 'has_savings', 'self_help_capability_code', 'self_help_amount'] as $field) {
            $value = $this->input->post($field, TRUE);
            if ($value !== NULL && (string) $value !== (string) ($profile[$field] ?? '')) {
                $data[$field] = $value;
                $previous = $provenance[$field] ?? NULL;
                $previous_source = is_array($previous) ? ($previous['source'] ?? '') : $previous;
                $provenance[$field] = [
                    'source' => in_array($previous_source, ['simulation', 'api', 'citizen_correction'], TRUE)
                        && (string) ($profile[$field] ?? '') !== '' ? 'citizen_correction' : 'citizen',
                    'changed_at' => date('c'),
                ];
            }
        }
        $data['source_mode'] = $profile['source_mode'];
        return ['data' => $data, 'provenance' => $provenance];
    }

    private function adjacent_step($step, $direction, $track)
    {
        $steps = self::STEPS;
        if ($track === 'existing_house') {
            $steps = array_values(array_diff($steps, ['candidate_land']));
        } elseif ($track === 'candidate_land') {
            $steps = array_values(array_diff($steps, ['building_condition', 'sanitation']));
        } elseif ($track === 'financing') {
            $steps = array_values(array_diff($steps, ['building_condition', 'candidate_land', 'sanitation']));
        }
        $i = array_search($step, $steps, TRUE);
        return $steps[max(0, min(count($steps) - 1, $i + ($direction === 'back' ? -1 : 1)))];
    }

    private function evidence_kinds($track)
    {
        if ($track === 'existing_house') return ['self_photo','house_front_photo','house_side_photo','roof_photo','floor_photo','wall_photo','latrine_photo','land_photo'];
        /* `land_transfer_proof` & `recipient_photo` DICABUT 5 Agt 2026 (revisi
           dinas butir A9). Ini whitelist UNGGAH — mencabutnya di sini berarti
           keduanya tidak bisa lagi dikirim, bukan sekadar tidak ditampilkan.

           SENGAJA TIDAK dicabut dari `Housing_assessment_model::EVIDENCE_KINDS`:
           itu daftar jenis yang SAH ADA, dan berkas yang sudah terlanjur
           tersimpan harus tetap terbaca. Keputusan user: berhenti mengumpulkan,
           jangan hapus yang lama. */
        if ($track === 'candidate_land') return ['candidate_land_photo','id_card_photo','family_card_photo','land_owner_family_card_photo'];
        return ['id_card_photo','family_card_photo'];
    }

    private function flash_errors(array $errors)
    {
        $this->session->set_flashdata('warga_errors', $errors);
    }

    private function valid_date($value)
    {
        $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function assessment_track($housing_status, $owns_candidate_land)
    {
        if ($housing_status === 'owned') { return 'existing_house'; }
        return $owns_candidate_land === '1' ? 'candidate_land' : 'financing';
    }

    private function recommendation_input_hash(array $assessment, array $profile)
    {
        $input = [
            'ruleset_version' => Warga_ruleset::VERSION,
            'profile' => [
                'welfare_decile' => $profile['welfare_decile'] ?? NULL,
                'income_band_code' => $profile['income_band_code'] ?? NULL,
                'self_help_capability_code' => $profile['self_help_capability_code'] ?? NULL,
            ],
            'assessment' => [
                'assessment_track' => $assessment['assessment_track'] ?? NULL,
                'housing_status_code' => $assessment['housing_status_code'] ?? NULL,
                'has_other_house' => $assessment['has_other_house'] ?? NULL,
                'owns_candidate_land' => $assessment['owns_candidate_land'] ?? NULL,
                'candidate_land_address_present' => ! empty($assessment['candidate_land_address']),
                'candidate_land_title_code' => $assessment['candidate_land_title_code'] ?? NULL,
                'candidate_land_origin_code' => $assessment['candidate_land_origin_code'] ?? NULL,
                'land_length_m' => $assessment['land_length_m'] ?? NULL,
                'land_width_m' => $assessment['land_width_m'] ?? NULL,
                'land_area_m2' => $assessment['land_area_m2'] ?? NULL,
                'foundation_condition_code' => $assessment['foundation_condition_code'] ?? NULL,
                'column_condition_code' => $assessment['column_condition_code'] ?? NULL,
                'beam_condition_code' => $assessment['beam_condition_code'] ?? NULL,
                'roof_frame_condition_code' => $assessment['roof_frame_condition_code'] ?? NULL,
                'floor_condition_code' => $assessment['floor_condition_code'] ?? NULL,
                'wall_condition_code' => $assessment['wall_condition_code'] ?? NULL,
                'roof_condition_code' => $assessment['roof_condition_code'] ?? NULL,
                'water_source_code' => $assessment['water_source_code'] ?? NULL,
                'latrine_type_code' => $assessment['latrine_type_code'] ?? NULL,
            ],
        ];
        return hash('sha256', json_encode($input, JSON_UNESCAPED_SLASHES));
    }

    private function step_errors($step)
    {
        $errors = [];
        if ($step === 'citizen_data') {
            foreach (['family_card_number' => 'Nomor KK', 'full_name' => 'Nama lengkap', 'birth_date' => 'Tanggal lahir', 'address' => 'Alamat'] as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') { $errors[$field] = $label . ' wajib diisi.'; }
            }
            $kk = preg_replace('/\D+/', '', (string) $this->input->post('family_card_number', TRUE));
            if ($kk !== '' && ! preg_match('/^\d{16}$/', $kk)) { $errors['family_card_number'] = 'Nomor KK harus 16 digit.'; }
            if ($this->input->post('birth_date', TRUE) !== NULL && ! $this->valid_date($this->input->post('birth_date', TRUE))) { $errors['birth_date'] = 'Tanggal lahir tidak valid.'; }
            foreach (['gender_code', 'marital_status_code', 'education_code', 'occupation_code', 'income_band_code', 'self_help_capability_code'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') { $errors[$field] = 'Pilihan ini wajib diisi.'; }
            }
            $allowed = [
                'gender_code' => ['male', 'female'],
                'marital_status_code' => ['single', 'married', 'divorced'],
                'education_code' => ['no_certificate', 'elementary', 'junior_high', 'senior_high', 'diploma_1_3', 'bachelor', 'postgraduate'],
                'occupation_code' => ['farmer', 'horticulture', 'plantation', 'capture_fisher', 'aquaculture_fisher', 'breeder', 'forestry_agriculture_other', 'mining', 'daily_laborer', 'electricity_gas', 'construction_worker', 'trader', 'hotel_restaurant', 'driver', 'information_communication', 'finance_insurance', 'educator', 'health_worker', 'civil_servant', 'scavenger', 'military_police', 'private_employee', 'contract_worker', 'retired', 'unemployed', 'other'],
                'income_band_code' => ['lt_1_8', '1_9_2_1', '2_2_2_6', '2_7_3_1', '3_2_3_6', '3_7_4_2', 'gt_4_2', '4_2_6', '6_8', 'gt_8'],
                'self_help_capability_code' => ['capable', 'not_capable'],
            ];
            foreach ($allowed as $field => $options) {
                $value = (string) $this->input->post($field, TRUE);
                if ($value !== '' && ! in_array($value, $options, TRUE)) { $errors[$field] = 'Pilihan tidak valid.'; }
            }
        }
        if ($step === 'housing_family') {
            foreach (['housing_status_code' => 'Status rumah', 'area_condition_code' => 'Kawasan'] as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') { $errors[$field] = $label . ' wajib dipilih.'; }
            }
            foreach (['occupant_count' => 'Jumlah penghuni', 'family_count' => 'Jumlah keluarga'] as $field => $label) {
                if (filter_var($this->input->post($field, TRUE), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === FALSE) { $errors[$field] = $label . ' minimal 1.'; }
            }
            $area = $this->input->post('house_area_m2', TRUE);
            if ($area !== NULL && $area !== '' && (! is_numeric($area) || (float) $area <= 0)) { $errors['house_area_m2'] = 'Luas rumah harus lebih dari nol.'; }
            $allowed = [
                'housing_status_code' => ['owned', 'rent', 'rent_free', 'official', 'staying', 'other'],
                'land_title_code' => ['certificate_unspecified', 'hm', 'hgb', 'letter_c', 'letter_d', 'village_letter', 'notarial_deed', 'other'],
                'area_condition_code' => ['drought', 'slum', 'disaster_prone', 'riverbank', 'railway', 'poor_other', 'good'],
                'assistance_source_code' => ['apbn_bsps', 'apbn', 'apbd_prov', 'apbd_kab', 'csr', 'village_fund', 'bsps_kl', 'bankab', 'baznas', 'other'],
                'has_other_land' => ['0', '1'],
                'has_other_house' => ['0', '1'],
                'owns_candidate_land' => ['0', '1'],
            ];
            foreach ($allowed as $field => $options) {
                $value = (string) $this->input->post($field, TRUE);
                if ($value !== '' && ! in_array($value, $options, TRUE)) { $errors[$field] = 'Pilihan tidak valid.'; }
            }
            if ((string) $this->input->post('housing_status_code', TRUE) !== 'owned'
                && ! in_array((string) $this->input->post('owns_candidate_land', TRUE), ['0', '1'], TRUE)) {
                $errors['owns_candidate_land'] = 'Kepemilikan calon lahan wajib dipilih.';
            }
            $year = (string) $this->input->post('assistance_year', TRUE);
            if ($year !== '' && ( ! ctype_digit($year) || (int) $year < 1900 || (int) $year > (int) date('Y'))) {
                $errors['assistance_year'] = 'Tahun bantuan tidak valid.';
            }
        }
        if ($step === 'building_condition') {
            foreach (['foundation_condition_code','column_condition_code','beam_condition_code','roof_frame_condition_code','floor_material_code','floor_condition_code','wall_material_code','wall_condition_code','roof_material_code','roof_condition_code'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            }
            $condition = ['good','minor_damage','moderate_damage','severe_damage_or_absent'];
            $allowed = [
                'foundation_condition_code'=>$condition, 'column_condition_code'=>$condition,
                'beam_condition_code'=>$condition, 'sloof_condition_code'=>$condition,
                'ceiling_condition_code'=>$condition, 'roof_frame_condition_code'=>$condition,
                'floor_condition_code'=>$condition, 'wall_condition_code'=>$condition,
                'roof_condition_code'=>$condition,
                'floor_material_code'=>['marble_granite','ceramic','parquet_vinyl_carpet','tile_terrazzo','high_quality_wood','cement_plaster','bamboo','low_quality_wood','soil','other'],
                'wall_material_code'=>['wall','plaster_grc','wood','woven_bamboo','log','bamboo','other'],
                'roof_material_code'=>['concrete','ceramic','metal','clay_tile','asbestos','zinc','shingle','bamboo','thatch','other'],
            ];
            $this->validate_options($allowed, $errors);
        }
        if ($step === 'candidate_land') {
            foreach (['candidate_land_address','candidate_land_title_code','candidate_land_origin_code','land_length_m','land_width_m'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            }
            foreach (['land_length_m','land_width_m'] as $field) if (!is_numeric($this->input->post($field, TRUE)) || (float)$this->input->post($field, TRUE) <= 0) $errors[$field] = 'Ukuran harus lebih dari nol.';
            $this->validate_options([
                'candidate_land_title_code'=>['hm','hgb','letter_c','letter_d','village_letter','notarial_deed','other'],
                'candidate_land_origin_code'=>['owned','inheritance','grant','purchase'],
                'land_owner_relationship_code'=>['parent','other'],
            ], $errors);
        }
        if ($step === 'sanitation') {
            foreach (['water_source_code','lighting_source_code','cooking_fuel_code'] as $field) if (trim((string)$this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            $this->validate_options([
                'has_window'=>['0','1'], 'has_ventilation'=>['0','1'],
                'water_source_code'=>['bottled','refill','piped','pdam','retail_piped','well','spring','rain','other_unfit'],
                'latrine_type_code'=>['swan_neck','plengsengan','pit','none'],
                'feces_disposal_code'=>['septic_tank','ipal','water_body','ground_hole','open_land'],
                'septic_distance_code'=>['lt_10','gte_10'],
                'lighting_source_code'=>['pln','pln_unmetered','non_pln','none'],
                'cooking_fuel_code'=>['electric_gas','kerosene','charcoal_wood','other'],
            ], $errors);
        }
        if ($step === 'location_evidence') {
            $lat=$this->input->post('location_lat', TRUE); $lng=$this->input->post('location_lng', TRUE);
            if (!is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90) $errors['location_lat']='Latitude tidak valid.';
            if (!is_numeric($lng) || (float)$lng < -180 || (float)$lng > 180) $errors['location_lng']='Longitude tidak valid.';
        }
        return $errors;
    }

    private function validate_options(array $fields, array &$errors)
    {
        foreach ($fields as $field => $allowed) {
            $value = (string) $this->input->post($field, TRUE);
            if ($value !== '' && ! in_array($value, $allowed, TRUE)) {
                $errors[$field] = 'Pilihan tidak valid.';
            }
        }
    }
}
