<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program extends Public_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Program_model');
    }

    /**
     * Halaman UI Wizard Diagnosa/Kalkulator Kelayakan
     */
    public function diagnosa($kode_program = 'umum') {
        if (!$kode_program) {
            show_404();
        }

        // Cek validitas program
        if ($kode_program === 'umum') {
            $program = [
                'id' => 0,
                'nama_program' => 'Umum (Cek Kelayakan Awal)',
                'kode_program' => 'umum'
            ];
        } else {
            $program = $this->Program_model->get_program_by_code($kode_program);
            if (!$program) {
                show_404();
            }
        }

        $data = [
            'title' => 'Klinik Diagnosa - ' . $program['nama_program'],
            'program' => $program,
            'kabupaten_list' => $this->db->order_by('nama', 'ASC')->get('kabupaten')->result(),
        ];

        // Load view using main layout
        $this->render('pages/program/diagnosa', $data);
    }

    /**
     * Diagnosa pembiayaan untuk hub Nggolek Omah.
     */
    public function solusi_pembiayaan() {
        $data = [
            'title' => 'Temukan Solusi Pembiayaan - Klinik PKP',
            'program' => [
                'id' => 0,
                'nama_program' => 'Solusi Pembiayaan Perumahan',
                'kode_program' => 'umum'
            ],
            'is_solusi_pembiayaan' => TRUE,
            'kabupaten_list' => $this->db->order_by('nama', 'ASC')->get('kabupaten')->result(),
        ];

        $this->render('pages/program/diagnosa', $data);
    }

    /**
     * Hasil rekomendasi pembiayaan sementara untuk satu sesi browser.
     */
    public function hasil_diagnosa() {
        $hasil = $this->session->userdata('solusi_pembiayaan_hasil');

        if (!$hasil || empty($hasil['created_at']) || (time() - (int) $hasil['created_at']) > 1800) {
            $this->session->unset_userdata('solusi_pembiayaan_hasil');
            $this->session->set_flashdata('error', 'Silakan lengkapi diagnosa terlebih dahulu untuk melihat rekomendasi program.');
            redirect('solusi_pembiayaan');
        }

        $data = [
            'title' => 'Hasil Diagnosa Pembiayaan - Klinik PKP',
            'hasil' => $hasil,
            'data_survey' => isset($hasil['data_survey']) ? $hasil['data_survey'] : []
        ];

        $this->render('pages/program/hasil_diagnosa', $data);
    }

    public function ajukan_solusi() {
        $this->simpan_pengajuan_warga('solusi_pembiayaan/hasil');
    }

    public function cek_tiket() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $rate = $this->rate_limit_inspect('ticket_lookup');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan. Silakan coba lagi sebentar.',
                TRUE
            );
            return;
        }

        $ticket_code = strtoupper(trim((string) $this->input->post('ticket_code', TRUE)));
        $nik_suffix = trim((string) $this->input->post('nik_suffix', TRUE));

        if (!preg_match('/^PKP-[A-Z0-9]{6}$/', $ticket_code) || !preg_match('/^\d{4}$/', $nik_suffix)) {
            $this->rate_limit_hit('ticket_lookup');
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Nomor tiket atau verifikasi tidak valid.']));
            return;
        }

        $pengajuan = $this->Program_model->get_housing_queue_by_ticket($ticket_code, $nik_suffix);
        if (!$pengajuan) {
            $this->rate_limit_hit('ticket_lookup');
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Data pengajuan tidak ditemukan.']));
            return;
        }

        $status_labels = housing_queue_statuses();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'ticket_code' => $ticket_code,
                'status_pengajuan' => isset($status_labels[$pengajuan['status_antrean']])
                    ? $status_labels[$pengajuan['status_antrean']]['label']
                    : 'Sedang diverifikasi',
                'created_at' => $pengajuan['created_at'],
                'updated_at' => $pengajuan['updated_at']
            ]));
    }

    public function cek_status_pengajuan() {
        $this->render('pages/program/cek_status_pengajuan', [
            'title' => 'Cek Status Pengajuan - Klinik PKP'
        ]);
    }

    /**
     * Lookup local-first. Controller tidak mengetahui fixture maupun API.
     */
    public function api_cek_simperum() {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $this->config->load('simperum', TRUE);
        if ($this->config->item('simperum_mode', 'simperum') === 'api') {
            $this->output
                ->set_status_header(409)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Pencarian SIMPERUM nyata hanya tersedia melalui Wizard Baru Warga.',
                    'code' => 'warga_wizard_required',
                    'redirect_url' => base_url('warga/pendataan'),
                ]));
            return;
        }

        $rate = $this->rate_limit_consume('simperum_lookup');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan. Silakan coba lagi sebentar.',
                TRUE
            );
            return;
        }

        $this->load->library('simperum_gateway');
        $result = $this->simperum_gateway->lookup(
            $this->input->post('nik', TRUE),
            $this->input->post('tgl_lahir', TRUE),
            $this->is_logged_in() ? $this->get_user_id() : NULL
        );
        if ($result['status'] === 'found') {
            $profile = $this->simperum_gateway->internal_profile();
            $this->session->set_userdata('solusi_pembiayaan_identitas', [
                'nik' => $profile['nik'],
                'nama_lengkap' => $profile['full_name'],
                'data_simperum_json' => json_encode([
                    'source_mode' => $result['source_mode'],
                    'snapshot_id' => $result['data']['snapshot_id'],
                ]),
                'created_at' => time(),
            ]);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => $result['status'] === 'found' ? 'success' : 'error',
                'message' => $result['message'],
                'source_mode' => $result['source_mode'],
                'simulation' => $result['simulation'],
                'code' => $result['code'],
                'data' => $result['data']['profile'] ?? NULL,
                'snapshot_id' => $result['data']['snapshot_id'] ?? NULL,
                'cache_hit' => $result['data']['cache_hit'] ?? FALSE,
                'missing_fields' => $result['data']['missing_fields'] ?? [],
                'eligible_programs' => [],
            ]));
    }

    /**
     * Endpoint untuk memproses ulang data survei dan menghitung Desil + Program
     */
    public function api_kalkulasi_program() {
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $penghasilan = (int) $this->input->post('penghasilan', TRUE);
        $status_kepemilikan = $this->input->post('status_kepemilikan', TRUE);
        $pekerjaan = $this->input->post('pekerjaan', TRUE);
        $kode_program_target = $this->input->post('kode_program_target', TRUE); // e.g. 'rtlh', 'umum'
        $alasan_pengajuan = trim((string) $this->input->post('alasan_pengajuan', TRUE));
        $user_id = $this->is_logged_in() ? $this->get_user_id() : NULL;
        $kabupaten_id = $this->Program_model->resolve_kabupaten_id(
            $user_id,
            $this->input->post('kabupaten_id', TRUE)
        );

        $pekerjaan_valid = ['PNS/TNI/POLRI', 'Karyawan Swasta', 'Wiraswasta', 'Pekerja Informal', 'Lainnya'];
        $kepemilikan_valid = ['Sewa/Kontrak', 'Numpang/Keluarga', 'Punya Lahan Belum Bangun', 'Punya Rumah Tidak Layak', 'Punya Rumah Layak'];

        if ($penghasilan < 0 || $penghasilan > 100000000 || $alasan_pengajuan === '' || ! $kabupaten_id
            || !in_array($pekerjaan, $pekerjaan_valid, TRUE) || !in_array($status_kepemilikan, $kepemilikan_valid, TRUE)) {
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Data survei tidak valid. Silakan periksa kembali isian Anda.']));
            return;
        }
        
        $this->load->library('smart_filter');

        // Kalkulasi dinamis desil berdasarkan penghasilan (Rule of thumb sederhana)
        $desil = 10; // Default
        if ($penghasilan <= 1500000) {
            $desil = 2; // Miskin Terbawah
        } elseif ($penghasilan <= 2500000) {
            $desil = 4; // Rentan Miskin
        } elseif ($penghasilan <= 5000000) {
            $desil = 6; // MBR Fixed Income
        } elseif ($penghasilan <= 8000000) {
            $desil = 8; // MBR Upper
        }

        $eligible_programs = $this->smart_filter->get_eligible_programs($desil, $status_kepemilikan);

        // Jika user datang dari halaman spesifik (bukan 'umum'), kita cek apakah dia lolos syarat program tersebut
        $is_eligible_for_target = false;
        if ($kode_program_target && $kode_program_target !== 'umum') {
            foreach ($eligible_programs as $prog) {
                if (isset($prog['kode']) && $prog['kode'] === $kode_program_target) {
                    $is_eligible_for_target = true;
                    break;
                }
            }
        } else {
            // Jika umum, maka selalu dianggap true asalkan ada program (yang mana selalu ada fallback)
            $is_eligible_for_target = true;
        }

        $response = [
            'status' => 'success',
            'desil' => $desil,
            'kode_program_target' => $kode_program_target,
            'is_eligible_for_target' => $is_eligible_for_target,
            'eligible_programs' => $eligible_programs
        ];

        $this->session->set_userdata('solusi_pembiayaan_hasil', [
            'desil' => $desil,
            'eligible_programs' => $eligible_programs,
            'kabupaten_id' => $kabupaten_id,
            'data_survey' => [
                'penghasilan' => $penghasilan,
                'pekerjaan' => $pekerjaan,
                'status_kepemilikan' => $status_kepemilikan,
                'alasan_pengajuan' => $alasan_pengajuan
            ],
            'created_at' => time()
        ]);

        if ($this->input->post('simpan_hasil', TRUE) === '1' && $kode_program_target === 'umum') {
            $response['redirect_url'] = base_url('solusi_pembiayaan/hasil');
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * Endpoint untuk memproses hasil kalkulator dan memasukkan ke Housing Queue
     */
    public function submit_antrean() {
        $this->simpan_pengajuan_warga('solusi_pembiayaan');
    }

    private function simpan_pengajuan_warga($gagal_redirect) {
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $rate = $this->rate_limit_consume('housing_submit');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Batas pengajuan tercapai. Silakan coba lagi nanti.',
                $this->input->is_ajax_request()
            );
            return;
        }

        $identitas = $this->session->userdata('solusi_pembiayaan_identitas');
        $hasil = $this->session->userdata('solusi_pembiayaan_hasil');
        $kode_program = trim((string) $this->input->post('program_kode', TRUE));
        $user_id = $this->is_logged_in() ? $this->get_user_id() : NULL;

        $result = $this->Program_model->create_housing_submission(
            is_array($identitas) ? $identitas : [],
            is_array($hasil) ? $hasil : [],
            $kode_program,
            $user_id
        );

        if (empty($result['success'])) {
            $this->session->set_flashdata('error', $result['message']);
            redirect($gagal_redirect);
            return;
        }

        $ticket_code = $result['ticket_code'];
        $this->session->unset_userdata(['solusi_pembiayaan_hasil', 'solusi_pembiayaan_identitas']);
        $this->session->set_flashdata('ticket_code', $ticket_code);
        $this->session->set_flashdata('success', 'Pengajuan berhasil direkam. Nomor tiket Anda: ' . $ticket_code);
        redirect('Program/success');
    }

    public function success() {
        $data['title'] = 'Pengajuan Berhasil - Klinik PKP';
        $data['ticket_code'] = $this->session->flashdata('ticket_code');
        
        $data['content'] = $this->load->view('pages/program/success_antrean', $data, TRUE);
        $this->load->view('layouts/main', $data);
    }
}
