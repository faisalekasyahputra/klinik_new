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
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $hasil = $this->session->userdata('solusi_pembiayaan_hasil');
        $identitas = $this->session->userdata('solusi_pembiayaan_identitas');
        $kode_program = $this->input->post('program_kode', TRUE);

        if (!$hasil || !$identitas || !$kode_program) {
            $this->session->set_flashdata('error', 'Sesi diagnosa tidak tersedia. Silakan ulangi diagnosa.');
            redirect('solusi_pembiayaan');
        }

        $selected = NULL;
        foreach ((array) $hasil['eligible_programs'] as $program) {
            if (isset($program['kode']) && $program['kode'] === $kode_program) {
                $selected = $program;
                break;
            }
        }

        if (!$selected) {
            $this->session->set_flashdata('error', 'Program yang dipilih tidak berasal dari hasil diagnosa Anda.');
            redirect('solusi_pembiayaan/hasil');
        }

        $now = date('Y-m-d H:i:s');
        $ticket_code = $this->Program_model->generate_ticket_code();
        $inserted = $this->Program_model->insert_housing_queue([
            'ticket_code' => $ticket_code,
            'user_id' => $this->is_logged_in() ? $this->get_user_id() : NULL,
            'program_id' => (int) $selected['id'],
            'nik_pengaju' => $identitas['nik'],
            'nama_lengkap' => $identitas['nama_lengkap'],
            'data_simperum_json' => $identitas['data_simperum_json'],
            'data_survey_json' => json_encode($hasil['data_survey']),
            'status_antrean' => 'pending',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $this->session->unset_userdata(['solusi_pembiayaan_hasil', 'solusi_pembiayaan_identitas']);
        if ($inserted) {
            $this->session->set_flashdata('ticket_code', $ticket_code);
        }
        $this->session->set_flashdata($inserted ? 'success' : 'error', $inserted
            ? 'Pengajuan berhasil dikirim. Nomor tiket Anda: ' . $ticket_code
            : 'Pengajuan belum dapat disimpan. Silakan coba lagi.');
        redirect('Program/success');
    }

    public function cek_tiket() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $ip_hash = hash('sha256', $this->input->ip_address());
        $retry_after = $this->Program_model->get_ticket_lookup_retry_after($ip_hash);
        if ($retry_after > 0) {
            $this->output
                ->set_status_header(429)
                ->set_header('Retry-After: ' . $retry_after)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Terlalu banyak percobaan. Silakan coba lagi sebentar.']));
            return;
        }

        $ticket_code = strtoupper(trim((string) $this->input->post('ticket_code', TRUE)));
        $nik_suffix = trim((string) $this->input->post('nik_suffix', TRUE));

        if (!preg_match('/^PKP-[A-Z0-9]{6}$/', $ticket_code) || !preg_match('/^\d{4}$/', $nik_suffix)) {
            $this->Program_model->record_ticket_lookup_failure($ip_hash);
            $this->output
                ->set_status_header(422)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Nomor tiket atau verifikasi tidak valid.']));
            return;
        }

        $pengajuan = $this->Program_model->get_housing_queue_by_ticket($ticket_code, $nik_suffix);
        if (!$pengajuan) {
            $this->Program_model->record_ticket_lookup_failure($ip_hash);
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Data pengajuan tidak ditemukan.']));
            return;
        }

        $status_labels = [
            'pending' => 'Menunggu verifikasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'success',
                'ticket_code' => $ticket_code,
                'status_pengajuan' => isset($status_labels[$pengajuan['status_antrean']])
                    ? $status_labels[$pengajuan['status_antrean']]
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
     * Endpoint MOCK API SIMPERUM (Dipanggil via AJAX dari UI Wizard)
     * Menerima NIK via POST dan mengembalikan data mock JSON.
     */
    public function api_cek_simperum() {
        // Hanya izinkan AJAX request
        if (!$this->input->is_ajax_request()) {
            exit('No direct script access allowed');
        }

        $nik = $this->input->post('nik', TRUE);
        $tgl_lahir = $this->input->post('tgl_lahir', TRUE);
        
        // Mock Response Delay (Simulasi network)
        sleep(1);

        // Fungsi bantuan untuk mensensor/masking teks
        $maskString = function($str) {
            $words = explode(' ', $str);
            $maskedWords = array_map(function($word) {
                $len = strlen($word);
                if ($len <= 2) return $word;
                return substr($word, 0, 1) . str_repeat('*', $len - 2) . substr($word, -1);
            }, $words);
            return implode(' ', $maskedWords);
        };

        $this->load->library('smart_filter');

        // Mock Logic: Simulasi NIK
        if ($nik === '3329000000000001' && $tgl_lahir === '1980-01-01') {
            // Skenario 1: Desil 4 (Omah Sekeng & Bansos PB)
            $desil = 4;
            $status_kepemilikan = 'Sewa/Kontrak';
            
            $eligible_programs = $this->smart_filter->get_eligible_programs($desil, $status_kepemilikan);

            $response = [
                'status' => 'success',
                'message' => 'Verifikasi berhasil. Data ditemukan.',
                'data' => [
                    'nik' => $nik,
                    'nama_lengkap' => $maskString('Budi Santoso'),
                    'alamat' => $maskString('Jl Pahlawan No 9 Kota Semarang Jawa Tengah'),
                    'penghasilan' => '3000000',
                    'pekerjaan' => 'Karyawan Swasta',
                    'status_kepemilikan' => $status_kepemilikan,
                    'desil' => $desil
                ],
                'eligible_programs' => $eligible_programs
            ];
        } elseif ($nik === '3329000000000002' && $tgl_lahir === '1990-12-31') {
            // Skenario 2: Hanya Data Diri (Data survei kosong, harus mengisi manual untuk tes Smart Filter)
            $response = [
                'status' => 'success',
                'message' => 'Verifikasi berhasil. Data diri ditemukan.',
                'data' => [
                    'nik' => $nik,
                    'nama_lengkap' => $maskString('Siti Aminah'),
                    'alamat' => $maskString('Jl Pemuda No 15 Kabupaten Demak Jawa Tengah'),
                    'penghasilan' => '',
                    'pekerjaan' => '',
                    'status_kepemilikan' => '',
                    'desil' => null
                ],
                'eligible_programs' => []
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Verifikasi gagal! NIK dan Tanggal Lahir tidak cocok atau tidak ditemukan.',
                'data' => null
            ];
        }

        if ($response['status'] === 'success' && $this->input->post('simpan_identitas', TRUE) === '1') {
            $this->session->set_userdata('solusi_pembiayaan_identitas', [
                'nik' => $nik,
                'nama_lengkap' => $response['data']['nama_lengkap'],
                'data_simperum_json' => json_encode($response['data'])
            ]);
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
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

        $pekerjaan_valid = ['PNS/TNI/POLRI', 'Karyawan Swasta', 'Wiraswasta', 'Pekerja Informal', 'Lainnya'];
        $kepemilikan_valid = ['Sewa/Kontrak', 'Numpang/Keluarga', 'Punya Lahan Belum Bangun', 'Punya Rumah Tidak Layak', 'Punya Rumah Layak'];

        if ($penghasilan < 0 || $penghasilan > 100000000 || !in_array($pekerjaan, $pekerjaan_valid, TRUE) || !in_array($status_kepemilikan, $kepemilikan_valid, TRUE)) {
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

        if ($this->input->post('simpan_hasil', TRUE) === '1' && $kode_program_target === 'umum') {
            $this->session->set_userdata('solusi_pembiayaan_hasil', [
                'desil' => $desil,
                'eligible_programs' => $eligible_programs,
                'data_survey' => [
                    'penghasilan' => $penghasilan,
                    'pekerjaan' => $pekerjaan,
                    'status_kepemilikan' => $status_kepemilikan,
                    'alasan_pengajuan' => $this->input->post('alasan_pengajuan', TRUE)
                ],
                'created_at' => time()
            ]);
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
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $program_id = $this->input->post('program_id', TRUE);
        $nik = $this->input->post('nik', TRUE);
        $nama_lengkap = $this->input->post('nama_lengkap', TRUE);
        
        // Data survey dari Kalkulator
        $data_survey = [
            'penghasilan' => $this->input->post('penghasilan', TRUE),
            'status_kepemilikan' => $this->input->post('status_kepemilikan', TRUE),
            'pekerjaan' => $this->input->post('pekerjaan', TRUE),
            'alasan_pengajuan' => $this->input->post('alasan_pengajuan', TRUE)
        ];

        // Data SIMPERUM (raw JSON yang diterima dari frontend)
        $data_simperum = $this->input->post('data_simperum', TRUE);

        $user_id = $this->is_logged_in() ? $this->get_user_id() : NULL;
        $kabupaten_id = (int) $this->input->post('kabupaten_id', TRUE);

        $ticket_code = $this->Program_model->generate_ticket_code();
        $insert_data = [
            'ticket_code' => $ticket_code,
            'user_id' => $user_id,
            'kabupaten_id' => $kabupaten_id ?: NULL,
            'program_id' => $program_id,
            'nik_pengaju' => $nik, // Idealnya dienkripsi, disesuaikan dengan aturan NFR-1.1
            'nama_lengkap' => $nama_lengkap,
            'data_simperum_json' => $data_simperum,
            'data_survey_json' => json_encode($data_survey),
            'status_antrean' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Memasukkan pengajuan ke database antrean
        $inserted = $this->Program_model->insert_housing_queue($insert_data);

        if ($inserted) {
            $this->session->set_flashdata('ticket_code', $ticket_code);
            $this->session->set_flashdata('success', 'Pengajuan berhasil direkam. Nomor tiket Anda: ' . $ticket_code);
        } else {
            $this->session->set_flashdata('error', 'Gagal memproses pengajuan. Silakan coba lagi.');
        }

        redirect('Program/success');
    }

    public function success() {
        $data['title'] = 'Pengajuan Berhasil - Klinik PKP';
        $data['ticket_code'] = $this->session->flashdata('ticket_code');
        
        $data['content'] = $this->load->view('pages/program/success_antrean', $data, TRUE);
        $this->load->view('layouts/main', $data);
    }
}
