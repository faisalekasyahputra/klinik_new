<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warga extends MY_Controller {

    /* 'housing_family_detail' DISISIPKAN 23 Agt 2026 tepat setelah
       'preliminary_recommendation' - permintaan user: field lama step
       'housing_family' (Status rumah dkk., lihat komentar lengkap di
       pendataan.php) DIPINDAH ke sini, bagian dari kelompok "Lengkapi
       data SIMPERUM" (bersama building_condition/candidate_land/
       sanitation/location_evidence/review), karena 'housing_family'
       sendiri sekarang berisi 6 field xlsx Sheet4 yang BEDA. Posisinya
       universal (tidak difilter track apa pun di adjacent_step() -
       semua track butuh field ini), makanya diletakkan SEBELUM
       percabangan building_condition/candidate_land, bukan di antaranya. */
    private const STEPS = ['find_data', 'citizen_data', 'housing_family', 'preliminary_recommendation', 'housing_family_detail', 'building_condition', 'candidate_land', 'sanitation', 'location_evidence', 'review'];
    private const STEP_LABELS = ['Masukkan NIK', 'Isi data sesuai matriks', 'Hasil rekomendasi', 'Lengkapi data SIMPERUM'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Housing_assessment_model');
        $this->load->library('Simperum_gateway');
        $this->load->library('Warga_ruleset');
        $this->load->library('Matriks_program_ruleset');
    }

    /**
     * Gerbang login+role, DIPINDAH dari __construct() 14 Agt 2026.
     *
     * Bukan lagi blanket guard di constructor - permintaan user: step
     * "Temukan Data" (pendataan() GET + lookup()) dibuka untuk pengunjung
     * ANONIM, supaya bisa cek NIK-nya dulu sebelum diminta akun. Method
     * yang menulis data sungguhan (save/upload/submit/start_revision) dan
     * lihat_bukti() (membuka berkas privat) TETAP wajib login+role warga -
     * mereka yang memanggil guard ini secara eksplisit di awal method.
     */
    private function guard_login_warga()
    {
        if ( ! $this->is_logged_in() || ! $this->has_role('warga')) {
            $this->session->set_flashdata('error', 'Akses pendataan hanya untuk akun warga.');
            // Gunakan satu gerbang agar pengunjung yang sesinya habis tetap
            // kembali ke pendataan setelah login; redirect telanjang di sini
            // membuang halaman asal dan memutus alur draft.
            $this->gerbang_login();
            return FALSE;
        }
        return TRUE;
    }

    public function pendataan()
    {
        if ($this->input->method() === 'post') {
            $this->handle_post();
            return;
        }

        // Anonim (atau login tapi bukan role warga - diusir seperti semula,
        // wizard ini bukan untuknya): tampilkan step "Temukan Data" kosong,
        // tanpa satu pun query milik akun. Login TAPI bukan warga tetap
        // diarahkan ke login seperti perilaku lama - wizard ini murni warga.
        if ($this->is_logged_in() && ! $this->has_role('warga')) {
            $this->guard_login_warga();
            return;
        }
        $logged_in_warga = $this->is_logged_in() && $this->has_role('warga');
        $user_id = $logged_in_warga ? (int) $this->get_user_id() : 0;

        $assessment = NULL;
        $profile = NULL;
        $provenance = [];
        if ($logged_in_warga) {
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
        }
        $old_input = $this->session->flashdata('warga_old_input') ?: [];
        /* Jaring pengaman 14 Agt 2026: kalau bootstrap draft di
           Auth::_redirect_after_login() gagal (mis. wilayah sumber belum
           bisa dipakai - lihat komentarnya) sehingga masih mendarat di
           step "Temukan Data" alih-alih "Data Warga", NIK yang barusan
           dicek TETAP terisi otomatis di sini - tidak perlu diketik ulang
           dari nol. `warga_pending_nik` di sesi ini SENGAJA TIDAK di-unset
           di Auth.php kalau gagal (lihat komentarnya di sana), jadi masih
           bisa dibaca. Isian flashdata (`warga_old_input`, hasil percobaan
           submit yang gagal validasi) tetap MENANG kalau ada - itu ketikan
           orangnya sendiri barusan. */
        if (empty($assessment) && empty($old_input['nik'])) {
            $pending_nik = $this->session->userdata('warga_pending_nik');
            if ( ! empty($pending_nik)) {
                $old_input['nik'] = $pending_nik;
            }
        }
        /* Rekomendasi xlsx (kolom J Sheet4) - permintaan user 23 Agt 2026:
           "Hasil Rekomendasi ... sesuaikan dengan xlsx kolom J". DIHITUNG
           ULANG di sini setiap render (bukan disimpan/di-cache ke DB
           seperti $recommendations dari Warga_ruleset.php) - fungsi murni
           dari 8 field matrix_*_code (draft) + welfare_decile (profil) +
           umur (dihitung dari tanggal lahir profil), jadi selalu konsisten
           dengan data terbaru tanpa perlu invalidasi cache. */
        $matriks_recommendation = [];
        if ($assessment) {
            $matriks_recommendation = $this->matriks_program_ruleset->match([
                'income_code' => $assessment['matrix_income_code'] ?? NULL,
                'welfare_decile' => $profile['welfare_decile'] ?? NULL,
                'dtks_code' => $assessment['matrix_dtks_status'] ?? NULL,
                'land_code' => $assessment['matrix_land_ownership_code'] ?? NULL,
                'housing_code' => $assessment['matrix_current_housing_code'] ?? NULL,
                'environment_code' => $assessment['matrix_environment_condition_code'] ?? NULL,
                'occupation_code' => $assessment['matrix_occupation_finance_code'] ?? NULL,
                'age_years' => $this->age_years_from_birth_date($profile['birth_date'] ?? NULL),
                'family_code' => $assessment['matrix_marital_family_code'] ?? NULL,
            ]);
        }
        $this->render('pages/warga/pendataan', [
            'title' => 'Pendataan Warga',
            'is_logged_in' => $logged_in_warga,
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
            'matriks_recommendation' => $matriks_recommendation,
        ]);
    }

    /**
     * Umur PENUH dalam tahun dari tanggal lahir - dipakai
     * Matriks_program_ruleset (aturan umur kolom H Sheet4) DAN tampilan
     * "Kategori Usia" di step 'housing_family' (lihat pendataan.php,
     * closure lokal di sana menghitung ulang dengan logika yang SAMA -
     * disalin, bukan dipanggil dari sini, karena view tidak punya akses
     * ke method controller; kalau batas kategori berubah, ubah DUA
     * tempat itu bersamaan).
     */
    private function age_years_from_birth_date($birth_date)
    {
        $birth_date = trim((string) $birth_date);
        if ($birth_date === '') { return NULL; }
        try {
            return (new DateTime($birth_date))->diff(new DateTime('today'))->y;
        } catch (Exception $e) {
            return NULL;
        }
    }

    /**
     * Pemohon membuka kembali bukti yang sudah dia unggah.
     *
     * Tanpa ini warga hanya melihat badge "Sudah tersimpan" tanpa cara
     * memastikan yang terunggah memang berkas yang benar - dan saat petugas
     * meminta perbaikan, dia tidak punya rujukan apa pun. Pola yang sama
     * sudah terbukti di Pengembang::lihat_dokumen_saya() (T3).
     *
     * Kepemilikan disaring get_owned_files() (WHERE user_id di model), jadi
     * assessment milik orang lain mengembalikan daftar kosong -> 404.
     */
    public function lihat_bukti($assessment_id = NULL, $file_kind = NULL)
    {
        if ( ! $this->guard_login_warga()) { return; }
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
        /* Tombol isi manual berada pada form pendataan utama. Form bersarang
           tidak valid di HTML dan membuat browser mengirim ulang lookup NIK
           (panggilan gateway yang lambat), bukan menyimpan draft manual. */
        $action = $this->input->post('manual_entry', TRUE) === '1'
            ? 'isi_manual' : (string) $this->input->post('action', TRUE);
        if ($action === '') {
            $action = $this->input->post('step', TRUE) === 'find_data' ? 'lookup' : 'save';
        }
        if ($action === 'lookup') {
            $this->lookup();
            return;
        }
        if ($action === 'isi_manual') {
            $this->isi_manual();
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

    /**
     * Cabang ANONIM dari lookup() - permintaan user 14 Agt 2026: "Temukan
     * Data" boleh dicoba tanpa akun, tapi TANPA menulis apa pun ke DB.
     * $requested_by=0 ke Simperum_gateway::lookup() sudah CUKUP untuk itu
     * (from_snapshot() hanya save_profile() kalau $requested_by terisi -
     * lihat komentarnya) - method ini tidak perlu menghindari model secara
     * manual, cuma tidak pernah membuat draft/profil sama sekali.
     *
     * Kalau ditemukan: NIK-nya (bukan hasil lengkapnya - cuma NIK) disimpan
     * ke session `warga_pending_nik`, dan `intended_url` diisi supaya kalau
     * orang ini login/daftar sebentar lagi, dia otomatis kembali ke sini
     * (mekanisme yang SAMA dipakai Auth::login() untuk alur lain, lihat
     * Auth::_redirect_after_login()). Auth::_redirect_after_login() yang
     * membaca `warga_pending_nik` itu nanti dan benar-benar mengikatnya ke
     * akun yang baru diketahui.
     */
    private function lookup_anonim()
    {
        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));

        // Dimensi `ip` saja - tidak ada akun untuk dijadikan dimensi
        // `account`. Sengaja LEBIH KETAT dari warga_lookup_jam/harian
        // (lihat rate_limits.php), bukan lebih longgar.
        $rate = $this->rate_limit_consume('warga_lookup_anon');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan pencarian data. Silakan coba lagi sebentar, atau masuk/daftar akun untuk batas yang lebih longgar.',
                $this->input->is_ajax_request()
            );
            return;
        }
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik]);
            $this->flash_errors(['nik' => 'NIK harus 16 digit.']);
            redirect('warga/pendataan');
            return;
        }

        // requested_by=0 -> Simperum_gateway TIDAK menulis profil/draft apa
        // pun, cuma mengembalikan hasil pencarian. $tanpa_tgl_lahir=TRUE,
        // sama seperti jalur login (lihat komentar di lookup()).
        $result = $this->simperum_gateway->lookup($nik, '', 0, TRUE);
        $status = (string) ($result['status'] ?? '');

        /* Permintaan user 14 Agt 2026: NIK genuinely TIDAK ADA di SIMPERUM
           (status 'not_found', bukan sekadar gagal jaringan/'error') ->
           arahkan ke pendaftaran akun, bukan cuma pesan error diam di
           tempat. "Pendaftaran" di aplikasi ini SELALU berarti Auth/register
           (lihat komentar Auth.php sendiri) - tidak ada halaman pendaftaran
           lain di ranah warga.
           `intended_url` ikut diisi SEPERTI jalur "ditemukan" - supaya
           sesudah daftar, orangnya kembali ke wizard ini (bisa coba NIK
           lain, atau lanjut mengisi manual - pesan asli Simperum_gateway
           untuk not_found memang berbunyi "Silakan isi data secara manual").
           STATUS LAIN ('error'/'invalid' - gagal jaringan, bukan "tidak
           ada") SENGAJA TIDAK ikut diarahkan ke sini: NIK-nya mungkin saja
           valid, cuma pengecekannya yang gagal - menyuruh daftar akun untuk
           kegagalan sesaat itu menyesatkan.

           `warga_pending_nik` DIISI JUGA di sini (bukan cuma di cabang
           "ditemukan" di bawah) - permintaan user susulan: kalau proses
           daftar/onboarding ini BERAWAL dari pengecekan NIK, field NIK di
           formulir onboarding (Auth::onboarding(), lihat prefill-nya di
           sana) langsung terisi, tidak perlu diketik ulang. Aman dipakai
           ulang oleh Auth::_redirect_after_login() nanti - NIK yang
           terkonfirmasi TIDAK ADA cuma membuat pemanggilan ulang gateway di
           sana kembali menjawab not_found dan berhenti SEBELUM
           save_profile() (lihat Simperum_gateway::from_snapshot()), jadi
           tidak ada yang tertulis ke sf_profil_warga - aman, bukan celah. */
        if ($status === 'not_found') {
            $this->session->set_userdata('warga_pending_nik', $nik);
            $this->session->set_userdata('intended_url', 'warga/pendataan');
            $this->session->set_flashdata('info', $result['message'] ?? 'NIK tidak ditemukan di data SIMPERUM. Silakan daftar akun untuk melanjutkan pendataan secara manual.');
            redirect('Auth/register');
            return;
        }
        if ($status !== 'found') {
            $this->session->set_flashdata('warga_lookup', $result);
            $this->session->set_flashdata('error', $result['message'] ?? 'Data belum dapat ditemukan.');
            redirect('warga/pendataan');
            return;
        }

        $this->session->set_userdata('warga_pending_nik', $nik);
        $this->session->set_userdata('intended_url', 'warga/pendataan');
        $this->session->set_flashdata('warga_lookup', [
            'status' => 'found_anonymous',
            'message' => 'Data ditemukan. Masuk untuk melanjutkan pendataan - NIK ini akan otomatis terhubung ke akun Anda.',
            'simulation' => ! empty($result['simulation']),
        ]);
        /* Form NIK anonim ditangkap JavaScript di pendataan.php. Redirect
           ini tidak dirender sebagai halaman penuh: tujuan Auth/login
           membuka modal masuk, sementara intended_url tetap sudah tercatat. */
        redirect('Auth/login');
    }

    private function lookup()
    {
        if ( ! $this->is_logged_in()) {
            $this->lookup_anonim();
            return;
        }
        if ( ! $this->guard_login_warga()) { return; }

        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $account_id = (int) $this->get_user_id();

        /* Tanggal lahir DICABUT dari layar ini 14 Agt 2026 - keputusan sadar
           user, dikonfirmasi paham risikonya (Simperum_gateway::lookup()
           menyebutnya "pengaman anti-penelusuran", bukan formalitas). Dua
           batas AKUN di bawah (warga_lookup_jam/harian, TANPA dimensi nik)
           yang menggantikan perannya - pola SAMA PERSIS dengan
           rtlh_cek/rtlh_cek_harian di Cek_Rtlh::proses() saat dinas mencabut
           tanggal lahir di sana lebih dulu. `warga_lookup` (per-NIK) TETAP
           dipanggil juga - dua-duanya, bukan saling gantikan: yang lama
           menahan brute-force SATU NIK, yang baru menahan penelusuran BANYAK
           NIK berbeda. */
        foreach ([
            ['warga_lookup_jam', 'Terlalu banyak percobaan pencarian data. Silakan coba lagi sebentar.'],
            ['warga_lookup_harian', 'Batas pencarian harian tercapai. Silakan lanjutkan besok.'],
        ] as [$policy, $pesan]) {
            $rate = $this->rate_limit_consume($policy, ['account_id' => $account_id]);
            if (empty($rate['success']) || empty($rate['allowed'])) {
                $this->rate_limit_reject($rate, $pesan, $this->input->is_ajax_request());
                return;
            }
        }
        $rate = $this->rate_limit_consume('warga_lookup', [
            'account_id' => $account_id,
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
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik]);
            $this->flash_errors(['nik' => 'NIK harus 16 digit.']);
            redirect('warga/pendataan');
            return;
        }

        // $tanpa_tgl_lahir=TRUE - lihat komentar di atas & Simperum_gateway::lookup().
        $result = $this->simperum_gateway->lookup($nik, '', $account_id, TRUE);
        $this->session->set_flashdata('warga_lookup', $result);
        if (($result['status'] ?? '') !== 'found') {
            /* Respons 'not_found' dari Simperum_gateway TIDAK menyertakan
               NIK di $result['data'] (cuma snapshot_id/cache_hit - lihat
               Simperum_gateway::from_snapshot()). Simpan NIK-nya di
               warga_old_input (pola sama seperti gagal validasi format di
               atas) supaya kotak "isi manual" di view tahu NIK mana yang
               barusan dicoba, tanpa mekanisme session baru. */
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik]);
            $this->session->set_flashdata('error', $result['message'] ?? 'Data belum dapat ditemukan.');
            redirect('warga/pendataan');
            return;
        }

        // Bikin/lanjutkan draft + maju ke step "Data Warga" - dipindah ke
        // Housing_assessment_model::bootstrap_draft_from_lookup() 14 Agt
        // 2026 supaya Auth::_redirect_after_login() bisa memakai logika
        // yang SAMA PERSIS (bukan disalin) untuk warga yang cek NIK anonim
        // lalu login/daftar.
        $user_id = (int) $this->get_user_id();
        $bootstrapped = $this->Housing_assessment_model->bootstrap_draft_from_lookup($user_id, $result);
        if (empty($bootstrapped['success'])) {
            $this->session->set_flashdata('error', $bootstrapped['message']);
            redirect('warga/pendataan');
            return;
        }
        $this->session->set_flashdata('success', 'Data awal tersimpan. Silakan periksa dan lengkapi pendataan.');
        redirect('warga/pendataan');
    }

    /**
     * Jalur "isi data secara manual" - dipakai warga yang SUDAH login saat
     * NIK-nya tidak ditemukan di SIMPERUM (lookup() di atas, status
     * 'not_found'). Wajib login+role warga (tidak ada jalur anonim - NIK
     * anonim yang not_found sudah diarahkan ke Auth/register sebelum
     * sampai sini). Hanya perlu NIK (dikirim ulang lewat field
     * tersembunyi, lihat pendataan.php) + nama lengkap; sisanya diisi
     * warga sendiri di step "Data Warga" seperti draft biasa.
     */
    private function isi_manual()
    {
        if ( ! $this->guard_login_warga()) { return; }

        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $full_name = trim((string) $this->input->post('full_name', TRUE));

        $errors = [];
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $errors['nik'] = 'NIK harus 16 digit.';
        }
        if ($full_name === '') {
            $errors['full_name'] = 'Nama lengkap wajib diisi.';
        }
        if ($errors) {
            /* warga_lookup (status 'not_found') yang membuat kotak "isi
               manual" tampil TADI sudah habis dipakai - flashdata sekali
               pakai, dikonsumsi GET yang merender formulir ini. Set ULANG
               di sini supaya kotaknya (dan pesan error di dalamnya) tetap
               tampil sesudah redirect balik - kalau tidak, warga mendarat
               di halaman yang terlihat kosong tanpa cara memperbaiki
               kesalahan ketiknya sendiri. */
            $this->session->set_flashdata('warga_lookup', [
                'status' => 'not_found',
                'message' => 'Data tidak ditemukan di SIMPERUM. Silakan isi data secara manual.',
            ]);
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik, 'full_name' => $full_name]);
            $this->flash_errors($errors);
            redirect('warga/pendataan');
            return;
        }

        $user_id = (int) $this->get_user_id();
        $result = $this->Housing_assessment_model->bootstrap_manual_draft($user_id, $nik, $full_name);
        if (empty($result['success'])) {
            $this->session->set_flashdata('error', $result['message'] ?? 'Data tidak dapat disimpan.');
            redirect('warga/pendataan');
            return;
        }
        $this->session->set_flashdata('success', 'Data awal tersimpan. Silakan lengkapi data warga.');
        redirect('warga/pendataan');
    }

    private function save()
    {
        if ( ! $this->guard_login_warga()) { return; }
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
        /* Dipindah dari step 'housing_family' 23 Agt 2026 - housing_status_code
           & owns_candidate_land (yang menentukan jalur ini) sekarang
           dikumpulkan di step 'housing_family_detail', bukan lagi
           'housing_family' (isi step itu sudah diganti field xlsx Sheet4).
           Track masih 'undetermined' selama step 'housing_family' &
           'preliminary_recommendation' - adjacent_step() tidak memfilter
           apa pun untuk track itu (lihat method-nya), jadi urutan step
           SEBELUM titik ini tidak terpengaruh, cuma percabangan SESUDAH
           'housing_family_detail' (building_condition/candidate_land/dst)
           yang baru butuh track ini. */
        if ($direction === 'next' && $step === 'housing_family_detail') {
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
        /* Rekomendasi awal tampil setelah matriks inti (Data Warga dan
           Rumah & Keluarga), kemudian hasil akhir diperbarui lagi sesudah
           data rinci serta bukti dilengkapi. Ini mewujudkan alur:
           NIK -> matriks -> hasil rekomendasi -> pelengkapan SIMPERUM. */
        if ($direction === 'next' && in_array($step, ['housing_family', 'location_evidence'], TRUE)) {
            $profile = $this->Housing_assessment_model->get_owned_profile($user_id) ?: [];
            if ($profile_change !== NULL) {
                $profile = array_merge($profile, $profile_change['data'] ?? []);
            }
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
        if ( ! $this->guard_login_warga()) { return; }
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
        if ( ! $this->guard_login_warga()) { return; }
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
        if ( ! $this->guard_login_warga()) { return; }
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
        foreach (['assessment_track', 'housing_status_code', 'land_title_code', 'has_other_land', 'has_other_house', 'house_area_m2', 'occupant_count', 'family_count', 'assistance_source_code', 'assistance_year', 'area_condition_code', 'owns_candidate_land', 'candidate_land_title_code', 'candidate_land_origin_code', 'land_owner_relationship_code', 'land_length_m', 'land_width_m', 'land_area_m2', 'foundation_condition_code', 'column_condition_code', 'beam_condition_code', 'sloof_condition_code', 'ceiling_condition_code', 'roof_frame_condition_code', 'floor_material_code', 'floor_condition_code', 'wall_material_code', 'wall_condition_code', 'roof_material_code', 'roof_condition_code', 'has_window', 'has_ventilation', 'water_source_code', 'has_bathroom_latrine', 'latrine_type_code', 'feces_disposal_code', 'septic_distance_code', 'lighting_source_code', 'cooking_fuel_code', 'location_accuracy_m', 'matrix_land_ownership_code', 'matrix_current_housing_code', 'matrix_environment_condition_code', 'matrix_occupation_finance_code', 'matrix_marital_family_code', 'matrix_income_code', 'matrix_dtks_status'] as $field) {
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
           dinas butir A9). Ini whitelist UNGGAH - mencabutnya di sini berarti
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
            /* Rekomendasi awal tidak memakai identitas administratif maupun
               demografi. Field tersebut boleh dilengkapi nanti; validasinya
               tetap dijalankan bila warga memang mengisi nilainya. */
            $kk = preg_replace('/\D+/', '', (string) $this->input->post('family_card_number', TRUE));
            if ($kk !== '' && ! preg_match('/^\d{16}$/', $kk)) { $errors['family_card_number'] = 'Nomor KK harus 16 digit.'; }
            if (trim((string) $this->input->post('birth_date', TRUE)) !== '' && ! $this->valid_date($this->input->post('birth_date', TRUE))) { $errors['birth_date'] = 'Tanggal lahir tidak valid.'; }
            foreach (['income_band_code', 'self_help_capability_code'] as $field) {
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
            /* Field xlsx Sheet4 (kolom D-I bertanda '*') - permintaan user
               23 Agt 2026, lihat komentar lengkap di pendataan.php. Semua
               WAJIB dipilih (beda dari step 'housing_family_detail' di
               bawah yang sebagian besar opsional) - enam field ini
               langsung menentukan rekomendasi awal, tidak ada makna
               "belum diisi" yang aman untuk matriks program. */
            foreach ([
                'matrix_income_code' => 'Gaji',
                'matrix_dtks_status' => 'Status DTKS',
                'matrix_land_ownership_code' => 'Kepemilikan Lahan',
                'matrix_current_housing_code' => 'Kepemilikan Rumah Saat Ini',
                'matrix_environment_condition_code' => 'Kondisi Lingkungan / Fisik Bangunan',
                'matrix_occupation_finance_code' => 'Pekerjaan / Kondisi Finansial',
                'matrix_marital_family_code' => 'Status Perkawinan / Keluarga',
            ] as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') {
                    $errors[$field] = $label . ' wajib dipilih.';
                }
            }
            // Kode "*_unrestricted" ("Tidak Dibatasi") DICABUT dari daftar
            // sah 23 Agt 2026, sejalan dengan opsi itu dihapus dari select
            // di pendataan.php - lihat komentar di sana. Submit yang tetap
            // mengirim kode itu (mis. request manual/lama) akan ditolak
            // sebagai "Pilihan tidak valid", bukan diam-diam diterima.
            $this->validate_options([
                'matrix_income_code' => ['income_0_1_5', 'income_1_5_2_2', 'income_2_2_2_8', 'income_2_8_8_5', 'income_2_8_10', 'income_gt_8_5', 'income_gt_10'],
                'matrix_dtks_status' => ['dtks_ya', 'dtks_belum'],
                'matrix_land_ownership_code' => ['land_none', 'land_legal'],
                'matrix_current_housing_code' => ['house_none_or_rent', 'house_rent_or_staying', 'house_restricted_area', 'house_disaster_affected', 'house_owned'],
                'matrix_environment_condition_code' => ['env_safe', 'env_relocation_zone', 'env_disaster_severe', 'env_disaster_moderate', 'env_slum_uninhabitable'],
                'matrix_occupation_finance_code' => ['work_stable_or_unstable_no_subsidy', 'work_can_save_irregular'],
                'matrix_marital_family_code' => ['family_single', 'family_married', 'family_multi_household', 'family_head_of_household'],
            ], $errors);
        }
        if ($step === 'housing_family_detail') {
            if (trim((string) $this->input->post('housing_status_code', TRUE)) === '') {
                $errors['housing_status_code'] = 'Status rumah wajib dipilih.';
            }
            foreach (['occupant_count' => 'Jumlah penghuni', 'family_count' => 'Jumlah keluarga'] as $field => $label) {
                $number = trim((string) $this->input->post($field, TRUE));
                if ($number !== '' && filter_var($number, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === FALSE) { $errors[$field] = $label . ' minimal 1.'; }
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
            if ((string) $this->input->post('housing_status_code', TRUE) !== 'owned') {
                if ( ! in_array((string) $this->input->post('owns_candidate_land', TRUE), ['0', '1'], TRUE)) {
                    $errors['owns_candidate_land'] = 'Kepemilikan calon lahan wajib dipilih.';
                }
                if ( ! in_array((string) $this->input->post('has_other_house', TRUE), ['0', '1'], TRUE)) {
                    $errors['has_other_house'] = 'Kepemilikan rumah lain wajib dipilih.';
                }
            }
            $year = (string) $this->input->post('assistance_year', TRUE);
            if ($year !== '' && ( ! ctype_digit($year) || (int) $year < 1900 || (int) $year > (int) date('Y'))) {
                $errors['assistance_year'] = 'Tahun bantuan tidak valid.';
            }
        }
        if ($step === 'building_condition') {
            foreach (['foundation_condition_code','column_condition_code','beam_condition_code','roof_frame_condition_code','floor_condition_code','wall_condition_code','roof_condition_code'] as $field) {
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
            // Permintaan user 17 Agt 2026: seluruh isian di langkah ini opsional.
            // Koordinat dan bukti foto sama-sama bisa menyusul - warga yang
            // sedang di lokasi berbeda dari rumahnya, atau kameranya tidak
            // aktif, tidak boleh mentok di langkah ini. Pola "validasi cuma
            // kalau diisi" sudah dipakai field opsional lain di fungsi ini
            // (house_area_m2, assistance_year) - lat/lng mengikuti pola yang
            // sama, bukan pengecualian baru.
            $lat = $this->input->post('location_lat', TRUE);
            $lng = $this->input->post('location_lng', TRUE);
            if ($lat !== NULL && $lat !== '' && (!is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90)) $errors['location_lat']='Latitude tidak valid.';
            if ($lng !== NULL && $lng !== '' && (!is_numeric($lng) || (float)$lng < -180 || (float)$lng > 180)) $errors['location_lng']='Longitude tidak valid.';
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
