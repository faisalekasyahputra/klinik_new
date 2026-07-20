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
            'program' => $program
        ];

        // Load view using main layout
        $this->render('pages/program/diagnosa', $data);
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

        $insert_data = [
            'user_id' => $user_id,
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
            $this->session->set_flashdata('success', 'Pengajuan berhasil direkam ke dalam Antrean. Petugas akan memvalidasi data Anda.');
        } else {
            $this->session->set_flashdata('error', 'Gagal memproses pengajuan. Silakan coba lagi.');
        }

        redirect('Program/success');
    }

    public function success() {
        $data['title'] = 'Pengajuan Berhasil - Klinik PKP';
        
        $data['content'] = $this->load->view('pages/program/success_antrean', $data, TRUE);
        $this->load->view('layouts/main', $data);
    }
}
