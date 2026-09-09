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

    /**
     * BUTIR 20 PUTARAN 2 (susulan) - permintaan user 16 Agt 2026: "Cek status
     * pengajuan dari frontend dihapus aja. Dipindah ke dashboardnya masing2".
     *
     * Endpoint INI - bukan cek_status_pengajuan() di bawah - yang sebenarnya
     * membocorkan data: halaman itu sudah lama jadi redirect stub, tapi
     * formulir "Cek status tanpa login" di pages/program/success_antrean.php
     * masih hidup dan memanggil endpoint ini langsung, lewat rute yang tidak
     * pernah ikut dicabut. Persis dua alasan yang sudah tercatat di komentar
     * cek_status_pengajuan(): dua tempat untuk satu hal (dashboard sudah
     * punya "Status Pengajuan"), DAN permukaan penelusuran (kode tiket
     * berpola tetap + 4 digit NIK cuma 10.000 kemungkinan - rate limit
     * menahan tebakan cepat, bukan menutup celahnya).
     *
     * TIDAK di-404-kan, alasan SAMA dengan di bawah - alamatnya sudah
     * tertanam di halaman yang mungkin masih ter-cache di peramban orang.
     * Yang beda: TIDAK ADA data pengajuan mana pun yang dicek di sini lagi -
     * get_housing_queue_by_ticket() (yang jadi sumber celahnya) sudah
     * dihapus dari model, bukan sekadar tidak dipanggil. Endpoint ini
     * sekarang cuma menjawab "sudah pindah", apa pun yang dikirim.
     */
    public function cek_tiket() {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $this->output
            ->set_status_header(410)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'error',
                'message' => 'Cek status lewat tiket sudah tidak tersedia di sini. Masuk ke akun Anda untuk melihat status pengajuan.',
                'redirect_url' => base_url('Auth/login'),
            ]));
    }

    /**
     * BUTIR 20 PUTARAN 2: layar cek status DICABUT dari situs publik.
     *
     * Dulu halaman ini meminta nomor tiket plus empat digit terakhir NIK, lalu
     * mengembalikan status pengajuan siapa pun yang cocok. Dua alasan
     * mencabutnya, dan yang kedua tidak disebut dinas tapi lebih berat:
     *
     *   1. Dua tempat untuk satu hal. Dashboard tiap peran sudah memuat
     *      "Status Pengajuan", dan menyediakannya lagi di luar membuat orang
     *      ragu mana yang benar - salah satu sumber kebingungan butir 24.
     *   2. Ia permukaan penelusuran. Nomor tiket berpola tetap dan empat digit
     *      NIK hanya sepuluh ribu kemungkinan; siapa pun yang punya keduanya
     *      bisa memeriksa pengajuan orang lain tanpa pernah masuk.
     *
     * TIDAK di-404-kan, dan itu disengaja. Alamatnya sudah pernah tersebar
     * (tab beranda, tautan yang dibagikan). Halaman hilang tanpa jejak membuat
     * orang mengira layanannya mati; diarahkan ke dashboardnya membuat mereka
     * sampai ke tempat yang benar. Yang belum masuk lewat gerbang login, jadi
     * sesudah masuk ia langsung mendarat di sana.
     */
    public function cek_status_pengajuan() {
        if ( ! $this->session->userdata('is_logged')) {
            $this->gerbang_login();
            return;
        }
        redirect('akun');
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
     * Mengubah pendapatan bulanan menjadi kelompok desil simulasi berdasarkan
     * Matriks Variabel Penentuan Program Perumahan, Sheet 3.
     */
    private function desil_dari_penghasilan($penghasilan)
    {
        if ($penghasilan <= 1500000) {
            return [1, 'Desil 1 (Sangat Miskin)'];
        }
        if ($penghasilan <= 2200000) {
            return [2, 'Desil 2–3 (Miskin)'];
        }
        if ($penghasilan <= 2800000) {
            return [4, 'Desil 4 (Rentan Miskin)'];
        }
        if ($penghasilan <= 8500000) {
            return [5, 'Desil 5–8 (MBR)'];
        }
        return [9, 'Desil 9–10 (Non-MBR)'];
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

        /* Matriks Variabel Penentuan Program Perumahan, Sheet 3 (18 Agu 2026):
         * rentang pendapatan menentukan kelompok desil simulasi awal. Rentang
         * D2–3 dan D5–8 sengaja disimpan sebagai label, bukan dipalsukan sebagai
         * satu desil resmi; angka perwakilan hanya dipakai agar filter program
         * yang sudah ada dapat bekerja. Keputusan bantuan tetap diverifikasi.
         */
        [$desil, $desil_label] = $this->desil_dari_penghasilan($penghasilan);

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
            'desil_label' => $desil_label,
            'kode_program_target' => $kode_program_target,
            'is_eligible_for_target' => $is_eligible_for_target,
            'eligible_programs' => $eligible_programs
        ];

        $this->session->set_userdata('solusi_pembiayaan_hasil', [
            'desil' => $desil,
            'desil_label' => $desil_label,
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
        // Istilah "pengajuan" disapu dari jalur diagnosa (revisi dinas 3 Agt 2026);
        // tiketnya tetap terbit karena tindak lanjut petugas tidak berubah.
        $this->session->set_flashdata('success', 'Data Anda berhasil disimpan. Nomor tiket: ' . $ticket_code);
        redirect('Program/success');
    }

    public function success() {
        $data['title'] = 'Pengajuan Berhasil - Klinik PKP';
        $data['ticket_code'] = $this->session->flashdata('ticket_code');
        
        $data['content'] = $this->load->view('pages/program/success_antrean', $data, TRUE);
        $this->load->view('layouts/main', $data);
    }
}
