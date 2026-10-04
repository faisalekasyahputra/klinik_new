<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warga extends MY_Controller {

    // UAT warga No. 9: NIK -> data awal -> rekomendasi -> data pelengkap.
    private const STEPS = ['find_data', 'housing_family', 'preliminary_recommendation', 'housing_family_detail', 'building_condition', 'candidate_land', 'sanitation', 'location_evidence', 'review'];
    private const STEP_LABELS = ['Masukkan NIK', 'Data untuk Rekomendasi', 'Hasil rekomendasi', 'Lengkapi data'];

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
            $provenance = kunci_tersimpan_ke_baru(json_decode($profile['asal_isian_json'] ?? '{}', TRUE) ?: []);
            foreach ($provenance as $field => $meta) {
                $provenance[$field] = is_array($meta) ? ($meta['source'] ?? 'citizen') : $meta;
            }
            if ($assessment && ! empty($assessment['rekaman_simperum_id'])) {
                foreach ($this->Housing_assessment_model->source_snapshot_prefill($assessment['rekaman_simperum_id']) as $field => $source_value) {
                    if (array_key_exists($field, $assessment) && $assessment[$field] !== NULL) {
                        $provenance[$field] = (string) $assessment[$field] === (string) $source_value
                            ? $assessment['mode_sumber'] : 'citizen_correction';
                    }
                }
            }
        }
        /* Lookup otomatis dari NIK akun (26 Sep 2026) DICABUT: data SIMPERUM baru terbuka sesudah
           nama akun dan tanggal lahir cocok, dan tanggal lahir hanya diketik warga di Cek NIK.
           NIK akun tetap mengisi kolom Cek NIK (blok nik_dari_akun di bawah). */
        $old_input = $this->session->flashdata('warga_old_input') ?: [];
        // SIMPERUM tidak selalu menyediakan nomor HP. Gunakan profil akun sendiri
        // sebagai isian awal, tanpa mengganti nilai/koreksi yang sudah disimpan.
        if ($logged_in_warga && $profile && empty($profile['phone'])
            && !in_array($provenance['phone'] ?? '', ['citizen', 'citizen_correction'], TRUE)) {
            $account = $this->db->select('no_hp')->get_where('usr_akun', ['id' => $user_id])->row();
            $profile['phone'] = html_entity_decode((string) ($account->no_hp ?? ''), ENT_QUOTES, 'UTF-8');
            $provenance['phone'] = 'account';
        }
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
        // NIK yang diisi warga saat daftar/onboarding (usr_akun.nik, terenkripsi) langsung
        // mengisi kolom Cek NIK, jadi warga cukup klik tanpa mengetik ulang (26 Sep 2026).
        $nik_dari_akun = FALSE;
        if ($logged_in_warga && empty($old_input['nik']) && empty($profile['nik'])) {
            $akun = $this->db->select('nik')->get_where('usr_akun', ['id' => $user_id])->row();
            $this->load->library('encryption_lib');
            $nik_akun = preg_replace('/\D+/', '', (string) $this->encryption_lib->decrypt((string) ($akun->nik ?? '')));
            if (strlen($nik_akun) === 16) {
                $old_input['nik'] = $nik_akun;
                $nik_dari_akun = TRUE;
            }
        }
        $this->render('pages/warga/pendataan', [
            'title' => 'Pendataan Warga',
            'is_logged_in' => $logged_in_warga,
            'nik_dari_akun' => $nik_dari_akun,
            'assessment' => $assessment,
            'preliminary_matrix' => json_decode($assessment['preliminary_matrix'] ?? 'null', TRUE),
            'matrix_fields' => Matriks_program_ruleset::FORM_FIELDS,
            'profile' => $profile,
            'values' => array_merge($assessment ?: [], $profile ?: [], $old_input),
            'lookup' => $this->session->flashdata('warga_lookup'),
            'errors' => $this->session->flashdata('warga_errors') ?: [],
            'step' => $assessment['langkah_sekarang'] ?? 'find_data',
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
            'review_summary' => ['desil_kesejahteraan'=>$profile['desil_kesejahteraan']??NULL,'jalur_penilaian'=>$assessment['jalur_penilaian']??NULL,'mode_sumber'=>$assessment['mode_sumber']??NULL],
        ]);
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
    public function lihat_bukti($penilaian_id = NULL, $jenis_berkas = NULL)
    {
        if ( ! $this->guard_login_warga()) { return; }
        if ( ! is_numeric($penilaian_id) || empty($jenis_berkas)) { show_404(); return; }
        $files = $this->Housing_assessment_model->get_owned_files(
            (int) $penilaian_id, (int) $this->get_user_id()
        );
        $file = $files[$jenis_berkas] ?? NULL;
        if ( ! $file) { show_404(); return; }

        $this->serve_private_file(
            'warga_assessment', $file['storage_assessment_id'], $file['path_privat'], $file['mime_type']
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
     * Auth::_redirect_after_login()). NIK di sesi itu hanya mengisi kolom
     * NIK onboarding dan Cek NIK; NIK baru terikat ke akun sesudah
     * diverifikasi dengan nama akun + tanggal lahir di lookup().
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
            'message' => 'Data ditemukan. Masuk atau daftar untuk melanjutkan, lalu verifikasi NIK dengan tanggal lahir di langkah ini.',
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

        /* Tanggal lahir dicabut dari layar ini 14 Agt 2026 dan DIKEMBALIKAN 3 Okt 2026 sebagai
           bagian bukti kepemilikan NIK (nama akun + tanggal lahir, lihat di bawah). Dua batas AKUN
           (warga_lookup_jam/harian, tanpa dimensi nik) menahan penelusuran banyak NIK,
           `warga_lookup` menahan permintaan beruntun untuk satu NIK, dan `verifikasi_nik` di
           gateway menahan tebakan nama/tanggal lahir yang gagal per akun dan per NIK. */
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
        $birth_date = trim((string) $this->input->post('birth_date', TRUE));
        $errors = [];
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $errors['nik'] = 'NIK harus 16 digit.';
        }
        if ( ! $this->valid_date($birth_date)) {
            $errors['birth_date'] = 'Tanggal lahir wajib diisi sesuai KTP.';
        }
        if ($errors) {
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik]);
            $this->flash_errors($errors);
            redirect('warga/pendataan');
            return;
        }

        /* Bukti kepemilikan NIK (keputusan pemilik produk, 3 Okt 2026): nama lengkap akun dan
           tanggal lahir dicocokkan dengan data SIMPERUM untuk NIK itu SEBELUM data apa pun diikat
           atau ditampilkan. Pencocokan, batas percobaan gagal, dan penolakan NIK milik akun lain
           ada di Simperum_gateway::lookup(), satu pintu untuk semua pemanggil yang mengikat. */
        $result = $this->simperum_gateway->lookup($nik, $birth_date, $account_id);
        $this->session->set_flashdata('warga_lookup', $result);
        if (($result['status'] ?? '') !== 'found') {
            /* Respons 'not_found' dari Simperum_gateway TIDAK menyertakan
               NIK di $result['data'] (cuma rekaman_id/cache_hit - lihat
               Simperum_gateway::from_snapshot()). Simpan NIK-nya di
               warga_old_input (pola sama seperti gagal validasi format di
               atas) supaya kotak "isi manual" di view tahu NIK mana yang
               barusan dicoba, tanpa mekanisme session baru. */
            $this->session->set_flashdata('warga_old_input', ['nik' => $nik]);
            // Permintaan klaim yang menunggu tinjauan Super Admin bukan galat.
            $this->session->set_flashdata(($result['code'] ?? '') === 'klaim_ditinjau' ? 'info' : 'error',
                $result['message'] ?? 'Data belum dapat ditemukan.');
            redirect('warga/pendataan');
            return;
        }

        // Bikin/lanjutkan draft + maju ke step "Data Warga" - dipindah ke
        // Housing_assessment_model::bootstrap_draft_from_lookup() 14 Agt
        // 2026 supaya Auth::_redirect_after_login() bisa memakai logika
        // yang SAMA PERSIS (bukan disalin) untuk warga yang cek NIK anonim
        // lalu login/daftar.
        $user_id = (int) $this->get_user_id();
        $this->session->unset_userdata('warga_pending_nik');
        $bootstrapped = $this->Housing_assessment_model->bootstrap_draft_from_lookup($user_id, $result);
        if (empty($bootstrapped['success'])) {
            $this->session->set_flashdata('error', $bootstrapped['message']);
            redirect('warga/pendataan');
            return;
        }
        $this->session->set_flashdata('success', 'NIK terverifikasi. Data awal tersimpan. Silakan periksa dan lengkapi pendataan.');
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
        $penilaian_id = (int) $this->input->post('penilaian_id', TRUE);
        $versi_kunci = filter_var($this->input->post('versi_kunci', TRUE), FILTER_VALIDATE_INT);
        $draft = $this->Housing_assessment_model->get_owned_assessment($penilaian_id, $user_id);
        if ( ! $draft || $versi_kunci === FALSE || (int) $draft['versi_kunci'] !== $versi_kunci) {
            $this->session->set_flashdata('error', 'Draft sudah berubah atau tidak dapat diakses. Muat ulang data terbaru.');
            redirect('warga/pendataan');
            return;
        }

        $step = (string) $this->input->post('step', TRUE);
        if ( ! in_array($step, self::STEPS, TRUE) || $step !== $draft['langkah_sekarang']) {
            show_404();
            return;
        }
        if (($step === 'building_condition' || $step === 'sanitation') && $draft['jalur_penilaian'] !== 'existing_house') { show_404(); return; }
        if ($step === 'candidate_land' && $draft['jalur_penilaian'] !== 'candidate_land') { show_404(); return; }
        $direction = $this->input->post('direction', TRUE) === 'back' ? 'back' : 'next';
        $errors = $direction === 'next' ? $this->step_errors($step) : [];
        if ($errors) {
            $old_input = $this->input->post(NULL, TRUE);
            unset($old_input['action'], $old_input['direction'], $old_input['penilaian_id'], $old_input['versi_kunci']);
            $this->session->set_flashdata('warga_old_input', $old_input);
            $this->flash_errors($errors);
            redirect('warga/pendataan');
            return;
        }
        $data = $direction === 'back' ? [] : $this->draft_data($step);
        if ($direction === 'next' && $step === 'housing_family_detail' && $draft['jalur_penilaian'] === 'candidate_land') {
            $data['punya_lahan_calon'] = $this->input->post('tanah_lain', TRUE);
        }
        if ($direction === 'next' && $step === 'housing_family') {
            $milik_sendiri = in_array((string) $this->input->post('matriks_rumah_sekarang', TRUE), ['house_owned', 'house_disaster_affected'], TRUE);
            $data['jalur_penilaian'] = $milik_sendiri ? 'existing_house' : 'candidate_land';
            $data['kepemilikan_rumah'] = $milik_sendiri ? 'owned' : 'other';
        }
        // Kompatibilitas draft lama yang sudah telanjur sampai langkah detail
        // saat jalur_penilaian masih undetermined.
        if ($direction === 'next' && $step === 'housing_family_detail'
            && ($draft['jalur_penilaian'] ?? 'undetermined') === 'undetermined') {
            $data['jalur_penilaian'] = $this->jalur_penilaian(
                (string) $this->input->post('kepemilikan_rumah', TRUE)
            );
        }
        if ($direction === 'next' && $step === 'candidate_land') {
            $data['luas_lahan_m2'] = round((float) $data['panjang_lahan_m'] * (float) $data['lebar_lahan_m'], 2);
        }
        $data['langkah_sekarang'] = $this->adjacent_step($step, $direction, $data['jalur_penilaian'] ?? $draft['jalur_penilaian']);
        $profile_change = $direction === 'next' && in_array($step, ['housing_family', 'housing_family_detail'], TRUE)
            ? $this->profile_corrections($user_id, $step) : NULL;
        if ($direction === 'next' && in_array($step, ['housing_family', 'housing_family_detail'], TRUE) && $profile_change === NULL) {
            $this->session->set_flashdata('error', 'Profil warga tidak ditemukan.');
            redirect('warga/pendataan');
            return;
        }
        $recommendations = NULL;
        $recommendation_hash = NULL;
        // Matriks awal dan evaluasi lanjutan memiliki hasil terpisah.
        if ($direction === 'next' && in_array($step, ['housing_family', 'housing_family_detail', 'location_evidence'], TRUE)) {
            $profile = $this->Housing_assessment_model->get_owned_profile($user_id) ?: [];
            if ($profile_change !== NULL) {
                $profile = array_merge($profile, $profile_change['data'] ?? []);
            }
            $effective = array_merge($draft, $data);
            // Keputusan pemilik produk 27 Sep 2026: SIMPERUM tidak mengirim desil, jadi selama
            // desil profil kosong dipakai desil turunan pendapatan (rentang Sheet3 yang sama
            // dengan tampilan rekomendasi awal). Desil resmi dari sumber tetap menang.
            if (empty($profile['desil_kesejahteraan'])) {
                $profile['desil_kesejahteraan'] = $this->matriks_program_ruleset->decile_for_monthly_income($profile['penghasilan_bulanan'] ?? NULL);
            }
            if ($step === 'housing_family') {
                $data['preliminary_matrix'] = json_encode(
                    $this->matriks_program_ruleset->preliminary($effective, $profile),
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
            } else {
                $recommendations = [];
                foreach ($this->warga_ruleset->route_candidates($profile['desil_kesejahteraan'] ?? NULL) as $code) {
                    $recommendations[] = $this->warga_ruleset->evaluate($code, $effective, $profile) + [
                        'program_code' => $code,
                        'versi_aturan' => Warga_ruleset::VERSION,
                    ];
                }
            }
            $recommendation_hash = $this->recommendation_input_hash($effective, $profile);
        }
        $updated = $this->Housing_assessment_model->save_owned_step(
            $penilaian_id, $user_id, $versi_kunci, $data,
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
        $penilaian_id = (int) $this->input->post('penilaian_id', TRUE);
        $draft = $this->Housing_assessment_model->get_owned_assessment($penilaian_id, $user_id);
        $kind = (string) $this->input->post('jenis_berkas', TRUE);
        $allowed = $this->evidence_kinds($draft['jalur_penilaian'] ?? '');
        if ( ! $draft || ! in_array($kind, $allowed, TRUE)) {
            show_404(); return;
        }
        // Kartu bukti mengunggah satu foto per permintaan lewat fetch dan meminta
        // JSON secara eksplisit (Accept), supaya halaman tidak di-reload. Sekadar
        // X-Requested-With tidak cukup: pemanggil lama mengirimnya dan tetap
        // mengharapkan flashdata + redirect.
        $jawab = function ($ok, $pesan) use ($penilaian_id, $user_id, $kind) {
            if (strpos((string) $this->input->get_request_header('Accept', TRUE), 'application/json') === FALSE) {
                $this->session->set_flashdata($ok ? 'success' : 'error', $pesan);
                redirect('warga/pendataan'); return;
            }
            $data = ['status' => $ok ? 'ok' : 'error', 'message' => $pesan];
            if ($ok) {
                $f = $this->Housing_assessment_model->get_owned_files($penilaian_id, $user_id)[$kind] ?? [];
                $data['ukuran'] = number_format(((int) ($f['ukuran_byte'] ?? 0)) / 1024, 0, ',', '.') . ' KB';
                $data['waktu'] = tgl_id($f['created_at'] ?? '', TRUE);
            }
            $this->output->set_content_type('application/json')->set_output(json_encode($data));
        };
        if (empty($_FILES[$kind]['tmp_name'])) {
            $jawab(FALSE, 'Pilih berkas JPG/PNG terlebih dahulu.'); return;
        }
        $file = $_FILES[$kind];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ( ! in_array($mime, ['image/jpeg', 'image/png'], TRUE)
            || ! $this->strip_image_metadata($file['tmp_name'], $mime)) {
            $jawab(FALSE, 'Bukti harus berupa JPG/PNG yang valid.'); return;
        }
        $sha256 = hash_file('sha256', $file['tmp_name']);
        $file['size'] = filesize($file['tmp_name']);
        $error = NULL;
        $stored = $this->store_private_upload($kind, 'warga_assessment', $penilaian_id, $error);
        if ($stored === FALSE) {
            $jawab(FALSE, $error ?: 'Berkas belum dapat diunggah.'); return;
        }
        $saved = $this->Housing_assessment_model->replace_owned_file(
            $penilaian_id, $user_id, $kind, $stored, $file['name'], $mime, $file['size'], $sha256
        );
        $dir = $this->private_upload_dir('warga_assessment', $penilaian_id);
        if (empty($saved['success'])) {
            @unlink($dir . $stored);
            $jawab(FALSE, $saved['message']); return;
        }
        if (!empty($saved['old_path']) && $saved['old_path'] !== $stored) { @unlink($dir . basename($saved['old_path'])); }
        $jawab(TRUE, 'Berkas tersimpan.');
    }

    private function submit()
    {
        if ( ! $this->guard_login_warga()) { return; }
        $penilaian_id = (int) $this->input->post('penilaian_id', TRUE);
        $rate = $this->rate_limit_consume('warga_submit', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $penilaian_id,
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
            $penilaian_id,
            (int) $this->get_user_id(),
            (int) $this->input->post('rekomendasi_id', TRUE),
            Warga_ruleset::VERSION
        );
        if ( ! empty($result['success']) && ! empty($result['notification_needed'])) {
            $queue = $this->db->select('kabupaten_id')->get_where('sf_antrean_pengajuan', [
                'id' => (int) $result['antrean_id'],
            ])->row_array();
            $judul = 'Pengajuan warga baru';
            $isi   = 'Ada pengajuan bantuan perumahan yang menunggu peninjauan.';
            $tag   = 'warga-' . (int) $result['antrean_id'];
            $this->notify_admin_push([['role' => 'admin']], $judul, $isi, 'Admin?status=pending', $tag);
            // Admin kab/kota tidak boleh masuk Admin/ (akses_ditolak), jadi tautannya ke antrean wilayahnya sendiri.
            if ( ! empty($queue['kabupaten_id'])) {
                $this->notify_admin_push([['role' => 'admin_kabkota', 'kabupaten_id' => (int) $queue['kabupaten_id']]],
                    $judul, $isi, 'Admin_Kabkota', $tag);
            }
        }
        $this->session->set_flashdata(
            ! empty($result['success']) ? 'success' : 'error',
            ! empty($result['success'])
                ? 'Pengajuan berhasil dikirim dengan tiket ' . $result['kode_tiket'] . '.'
                : ($result['message'] ?? 'Pengajuan belum dapat dikirim.')
        );
        redirect(! empty($result['success']) ? 'akun' : 'warga/pendataan');
    }

    private function start_revision()
    {
        if ( ! $this->guard_login_warga()) { return; }
        $antrean_id = (int) $this->input->post('antrean_id', TRUE);
        $rate = $this->rate_limit_consume('warga_start_revision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $antrean_id,
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
            $antrean_id,
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


    private function draft_data($step)
    {
        // Setiap POST hanya mengubah medan pada langkah yang sudah divalidasi.
        $fields = [
            'housing_family' => array_merge(['matriks_rumah_sekarang', 'kawasan_perumahan'], array_keys(Matriks_program_ruleset::FORM_FIELDS)),
            'housing_family_detail' => ['kepemilikan_rumah', 'kepemilikan_lahan', 'tanah_lain', 'rumah_lain', 'luas_rumah', 'jml_penghuni', 'jml_kk', 'bantuan_perumahan', 'tahun_intervensi'],
            'building_condition' => ['kondisi_pondasi', 'kondisi_kolom', 'kondisi_balok', 'kondisi_sloof', 'kondisi_plafon', 'kondisi_rangka', 'bahan_lantai', 'kondisi_lantai', 'bahan_dinding', 'kondisi_dinding', 'bahan_atap', 'kondisi_atap'],
            'candidate_land' => ['candidate_land_address', 'status_lahan_calon', 'asal_lahan_calon', 'hubungan_pemilik_lahan', 'panjang_lahan_m', 'lebar_lahan_m'],
            'sanitation' => ['ada_jendela', 'ada_ventilasi', 'sumber_air', 'penggunaan_kamar_mandi', 'jenis_kloset', 'pembuangan_tinja', 'jarak_septic_tank', 'penerangan', 'bahan_bakar_masak'],
            'location_evidence' => ['location_lat', 'location_lng', 'akurasi_lokasi_m'],
        ];
        $data = [];
        foreach ($fields[$step] ?? [] as $field) {
            if ($this->input->post($field, TRUE) !== NULL) $data[$field] = $this->input->post($field, TRUE);
        }
        if ($step === 'sanitation') $data['kamar_mandi'] = ($data['penggunaan_kamar_mandi'] ?? '') === 'none' ? 0 : 1;
        return $data;
    }

    private function profile_corrections($user_id, $step)
    {
        $profile = $this->Housing_assessment_model->get_owned_profile($user_id);
        if ( ! $profile) { return NULL; }
        $data = $profile;
        $provenance = kunci_tersimpan_ke_baru(json_decode($profile['asal_isian_json'] ?? '{}', TRUE) ?: []);
        $fields = $step === 'housing_family'
            ? ['phone', 'birth_date', 'jenis_kelamin', 'status_perkawinan', 'pendidikan', 'pekerjaan', 'stabilitas_pekerjaan', 'penghasilan_bulanan']
            : ['family_card_number', 'full_name', 'address', 'tax_number', 'punya_tabungan', 'mampu_swadaya'];
        foreach ($fields as $field) {
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
        $data['mode_sumber'] = $profile['mode_sumber'];
        return ['data' => $data, 'provenance' => $provenance];
    }

    private function adjacent_step($step, $direction, $track)
    {
        if ($step === 'preliminary_recommendation' && $track === 'undetermined' && $direction !== 'back') {
            return 'housing_family_detail';
        }
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
        if ($track === 'existing_house') return ['self_photo','house_front_photo','house_side_photo','roof_photo','floor_photo','wall_photo','latrine_photo'];
        /* Riwayat: `land_transfer_proof` & `recipient_photo` dicabut 5 Agt 2026 (revisi dinas A9).
           UAT 2026 (warga/pengembang #9, cabang bukan milik sendiri) meminta "Bukti Pindah Tangan"
           lagi, jadi `land_transfer_proof` DIKEMBALIKAN (157e275, dikonfirmasi 23 Sep 2026).
           `recipient_photo` tetap dicabut. Keduanya tetap di EVIDENCE_KINDS supaya berkas lama terbaca. */
        if ($track === 'candidate_land') return ['candidate_land_photo','land_transfer_proof'];
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

    private function jalur_penilaian($housing_status)
    {
        if ($housing_status === 'owned') { return 'existing_house'; }
        return 'candidate_land';
    }

    private function recommendation_input_hash(array $assessment, array $profile)
    {
        $input = [
            'versi_aturan' => Warga_ruleset::VERSION,
            'profile' => [
                'desil_kesejahteraan' => $profile['desil_kesejahteraan'] ?? NULL,
                'kelompok_penghasilan' => $profile['kelompok_penghasilan'] ?? NULL,
                'penghasilan_bulanan' => $profile['penghasilan_bulanan'] ?? NULL,
                'mampu_swadaya' => $profile['mampu_swadaya'] ?? NULL,
            ],
            'assessment' => [
                'jalur_penilaian' => $assessment['jalur_penilaian'] ?? NULL,
                'kepemilikan_rumah' => $assessment['kepemilikan_rumah'] ?? NULL,
                'rumah_lain' => $assessment['rumah_lain'] ?? NULL,
                'punya_lahan_calon' => $assessment['punya_lahan_calon'] ?? NULL,
                'candidate_land_address_present' => ! empty($assessment['candidate_land_address']),
                'status_lahan_calon' => $assessment['status_lahan_calon'] ?? NULL,
                'asal_lahan_calon' => $assessment['asal_lahan_calon'] ?? NULL,
                'panjang_lahan_m' => $assessment['panjang_lahan_m'] ?? NULL,
                'lebar_lahan_m' => $assessment['lebar_lahan_m'] ?? NULL,
                'luas_lahan_m2' => $assessment['luas_lahan_m2'] ?? NULL,
                'kondisi_pondasi' => $assessment['kondisi_pondasi'] ?? NULL,
                'kondisi_kolom' => $assessment['kondisi_kolom'] ?? NULL,
                'kondisi_balok' => $assessment['kondisi_balok'] ?? NULL,
                'kondisi_rangka' => $assessment['kondisi_rangka'] ?? NULL,
                'kondisi_lantai' => $assessment['kondisi_lantai'] ?? NULL,
                'kondisi_dinding' => $assessment['kondisi_dinding'] ?? NULL,
                'kondisi_atap' => $assessment['kondisi_atap'] ?? NULL,
                'sumber_air' => $assessment['sumber_air'] ?? NULL,
                'jenis_kloset' => $assessment['jenis_kloset'] ?? NULL,
            ],
        ];
        // Kunci lama dipakai supaya sidik masukan yang sama tetap sama sebelum dan sesudah migrasi 072.
        return hash('sha256', json_encode(kunci_tersimpan_ke_lama($input), JSON_UNESCAPED_SLASHES));
    }

    private function step_errors($step)
    {
        $errors = [];
        if ($step === 'housing_family') {
            $this->validate_options([
                'matriks_rumah_sekarang' => ['house_owned', 'house_none_or_rent', 'house_rent_or_staying', 'house_restricted_area', 'house_disaster_affected'],
                'kawasan_perumahan' => ['drought', 'slum', 'disaster_prone', 'riverbank', 'railway', 'poor_other', 'good'],
            ], $errors);
            foreach (Matriks_program_ruleset::FORM_FIELDS as $field => [$label, $options]) {
                $this->validate_options([$field => array_keys($options)], $errors);
            }
            foreach (['matriks_rumah_sekarang', 'kawasan_perumahan'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Pilihan ini wajib diisi.';
            }
        }
        if (in_array($step, ['housing_family', 'housing_family_detail'], TRUE)) {
            /* Validasi eks-step 'citizen_data' (Data Warga), DIPINDAH ke sini
               24 Agt 2026 - step-nya sudah dihapus & digabung ke form ini,
               lihat komentar STEPS di atas. Rekomendasi awal tidak memakai
               identitas administratif maupun demografi. Field tersebut boleh
               dilengkapi nanti; validasinya tetap dijalankan bila warga
               memang mengisi nilainya. */
            $required = $step === 'housing_family'
                ? ['phone'=>'Nomor HP','birth_date'=>'Tanggal lahir','jenis_kelamin'=>'Jenis kelamin','status_perkawinan'=>'Status perkawinan','pendidikan'=>'Pendidikan','pekerjaan'=>'Pekerjaan']
                : ['family_card_number'=>'Nomor KK','full_name'=>'Nama','address'=>'Alamat'];
            foreach ($required as $field=>$label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = $label . ' wajib diisi.';
            }            $kk = preg_replace('/\D+/', '', (string) $this->input->post('family_card_number', TRUE));
            if ($kk !== '' && ! preg_match('/^\d{16}$/', $kk)) { $errors['family_card_number'] = 'Nomor KK harus 16 digit.'; }
            if (trim((string) $this->input->post('birth_date', TRUE)) !== '' && ! $this->valid_date($this->input->post('birth_date', TRUE))) { $errors['birth_date'] = 'Tanggal lahir tidak valid.'; }
            foreach (($step === 'housing_family' ? ['stabilitas_pekerjaan'] : ['mampu_swadaya', 'punya_tabungan']) as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') { $errors[$field] = 'Pilihan ini wajib diisi.'; }
            }
            $penghasilan_bulanan = trim((string) $this->input->post('penghasilan_bulanan', TRUE));
            if ($step === 'housing_family' && ($penghasilan_bulanan === '' || ! ctype_digit($penghasilan_bulanan) || (float) $penghasilan_bulanan > 999999999999)) { $errors['penghasilan_bulanan'] = 'Pendapatan per bulan wajib berupa angka rupiah.'; }
            $citizen_allowed = [
                'jenis_kelamin' => ['male', 'female'],
                'status_perkawinan' => ['single', 'married', 'divorced'],
                'pendidikan' => ['no_certificate', 'elementary', 'junior_high', 'senior_high', 'diploma_1_3', 'bachelor', 'postgraduate'],
                'stabilitas_pekerjaan' => ['permanent', 'non_permanent'],
                'punya_tabungan' => ['0', '1'],
                'pekerjaan' => ['farmer', 'horticulture', 'plantation', 'capture_fisher', 'aquaculture_fisher', 'breeder', 'forestry_agriculture_other', 'mining', 'daily_laborer', 'electricity_gas', 'construction_worker', 'trader', 'hotel_restaurant', 'driver', 'information_communication', 'finance_insurance', 'educator', 'health_worker', 'civil_servant', 'scavenger', 'military_police', 'private_employee', 'contract_worker', 'retired', 'unemployed', 'other'],
                'kelompok_penghasilan' => ['lt_1_8', '1_9_2_1', '2_2_2_6', '2_7_3_1', '3_2_3_6', '3_7_4_2', 'gt_4_2', '4_2_6', '6_8', 'gt_8'],
                'mampu_swadaya' => ['capable', 'not_capable'],
            ];
            foreach ($citizen_allowed as $field => $options) {
                $value = (string) $this->input->post($field, TRUE);
                if ($value !== '' && ! in_array($value, $options, TRUE)) { $errors[$field] = 'Pilihan tidak valid.'; }
            }
            if ($step === 'housing_family') { return $errors; }
            if (trim((string) $this->input->post('kepemilikan_rumah', TRUE)) === '') {
                $errors['kepemilikan_rumah'] = 'Status rumah wajib dipilih.';
            }
            foreach (['jml_penghuni' => 'Jumlah penghuni', 'jml_kk' => 'Jumlah keluarga'] as $field => $label) {
                $number = trim((string) $this->input->post($field, TRUE));
                if ($number !== '' && filter_var($number, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === FALSE) { $errors[$field] = $label . ' minimal 1.'; }
            }
            $area = $this->input->post('luas_rumah', TRUE);
            if ($area !== NULL && $area !== '' && (! is_numeric($area) || (float) $area <= 0)) { $errors['luas_rumah'] = 'Luas rumah harus lebih dari nol.'; }
            $allowed = [
                'kepemilikan_rumah' => ['owned', 'rent', 'rent_free', 'official', 'staying', 'other'],
                'kepemilikan_lahan' => ['certificate_unspecified', 'hm', 'hgb', 'letter_c', 'letter_d', 'village_letter', 'notarial_deed', 'other'],
                'kawasan_perumahan' => ['drought', 'slum', 'disaster_prone', 'riverbank', 'railway', 'poor_other', 'good'],
                'bantuan_perumahan' => ['apbn_bsps', 'apbn', 'apbd_prov', 'apbd_kab', 'csr', 'village_fund', 'bsps_kl', 'bankab', 'baznas', 'already_habitable', 'other'],
                'tanah_lain' => ['0', '1'],
                'rumah_lain' => ['0', '1'],
                'punya_lahan_calon' => ['0', '1'],
            ];
            foreach ($allowed as $field => $options) {
                $value = (string) $this->input->post($field, TRUE);
                if ($value !== '' && ! in_array($value, $options, TRUE)) { $errors[$field] = 'Pilihan tidak valid.'; }
            }
            if ((string) $this->input->post('kepemilikan_rumah', TRUE) === 'owned') {
                foreach (['kepemilikan_lahan', 'tanah_lain', 'rumah_lain'] as $field) {
                    if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi untuk rumah milik sendiri.';
                }
                if ($area === NULL || $area === '') $errors['luas_rumah'] = 'Luas rumah wajib diisi.';
            } elseif ( ! in_array((string) $this->input->post('tanah_lain', TRUE), ['0', '1'], TRUE)) {
                $errors['tanah_lain'] = 'Kepemilikan tanah lain wajib dipilih.';
            }
            $year = (string) $this->input->post('tahun_intervensi', TRUE);
            if ($year !== '' && ( ! ctype_digit($year) || (int) $year < 1900 || (int) $year > (int) date('Y'))) {
                $errors['tahun_intervensi'] = 'Tahun bantuan tidak valid.';
            }
        }
        if ($step === 'building_condition') {
            foreach (['kondisi_pondasi','kondisi_kolom','kondisi_balok','kondisi_rangka','kondisi_lantai','kondisi_dinding','kondisi_atap'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            }
            $condition = ['good','minor_damage','moderate_damage','severe_damage_or_absent'];
            $allowed = [
                'kondisi_pondasi'=>$condition, 'kondisi_kolom'=>$condition,
                'kondisi_balok'=>$condition, 'kondisi_sloof'=>$condition,
                'kondisi_plafon'=>$condition, 'kondisi_rangka'=>$condition,
                'kondisi_lantai'=>$condition, 'kondisi_dinding'=>$condition,
                'kondisi_atap'=>$condition,
                'bahan_lantai'=>['marble_granite','ceramic','parquet_vinyl_carpet','tile_terrazzo','high_quality_wood','cement_plaster','bamboo','low_quality_wood','soil','other'],
                'bahan_dinding'=>['wall','plaster_grc','wood','woven_bamboo','log','bamboo','other'],
                'bahan_atap'=>['concrete','ceramic','metal','clay_tile','asbestos','zinc','shingle','bamboo','thatch','other'],
            ];
            $this->validate_options($allowed, $errors);
        }
        if ($step === 'candidate_land') {
            foreach (['candidate_land_address','status_lahan_calon','asal_lahan_calon','panjang_lahan_m','lebar_lahan_m'] as $field) {
                if (trim((string) $this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            }
            foreach (['panjang_lahan_m','lebar_lahan_m'] as $field) if (!is_numeric($this->input->post($field, TRUE)) || (float)$this->input->post($field, TRUE) <= 0) $errors[$field] = 'Ukuran harus lebih dari nol.';
            $this->validate_options([
                'status_lahan_calon'=>['hm','hgb','letter_c','letter_d','village_letter','notarial_deed','other'],
                'asal_lahan_calon'=>['owned','inheritance','grant','purchase'],
                'hubungan_pemilik_lahan'=>['parent','other'],
            ], $errors);
        }
        if ($step === 'sanitation') {
            foreach (['penggunaan_kamar_mandi','sumber_air','penerangan','bahan_bakar_masak'] as $field) if (trim((string)$this->input->post($field, TRUE)) === '') $errors[$field] = 'Field ini wajib diisi.';
            $this->validate_options([
                'ada_jendela'=>['0','1'], 'ada_ventilasi'=>['0','1'], 'penggunaan_kamar_mandi'=>['own','shared','none'],
                'sumber_air'=>['bottled','refill','piped','pdam','retail_piped','well','well_protected','well_unprotected','spring','spring_unprotected','surface_water','rain','other_unfit'],
                'jenis_kloset'=>['swan_neck','plengsengan','pit','none'],
                'pembuangan_tinja'=>['septic_tank','ipal','water_body','ground_hole','open_land'],
                'jarak_septic_tank'=>['lt_10','gte_10'],
                'penerangan'=>['pln','pln_unmetered','non_pln','none'],
                'bahan_bakar_masak'=>['electric_gas','kerosene','charcoal_wood','other'],
            ], $errors);
        }
        if ($step === 'location_evidence') {
            // Permintaan user 17 Agt 2026: seluruh isian di langkah ini opsional.
            // Koordinat dan bukti foto sama-sama bisa menyusul - warga yang
            // sedang di lokasi berbeda dari rumahnya, atau kameranya tidak
            // aktif, tidak boleh mentok di langkah ini. Pola "validasi cuma
            // kalau diisi" sudah dipakai field opsional lain di fungsi ini
            // (luas_rumah, tahun_intervensi) - lat/lng mengikuti pola yang
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
