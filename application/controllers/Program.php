<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * KEPUTUSAN PEMILIK PRODUK 27 Sep 2026: jalur diagnosa LAMA (solusi_pembiayaan,
 * Program/diagnosa, api_cek_simperum, api_kalkulasi_program, submit_antrean)
 * dialihkan ke wizard warga `warga/pendataan`, supaya tidak ada lagi dua jalur
 * pengajuan. Di mode simulasi jalur lama menerbitkan tiket sf_housing_queue
 * tanpa login atau atas nama akun non-warga (mahasiswa, pengembang, akun tanpa
 * peran), tiket tamu tidak bisa dipantau siapa pun, dan api_cek_simperum
 * mengikat NIK ke akun non-warga.
 *
 * Alamatnya TIDAK di-404-kan: sudah tertanam di tautan, bookmark, dan halaman
 * yang mungkin masih ter-cache. GET dialihkan ke wizard; POST tidak memproses
 * apa pun lagi (lihat jalur_dipindah()). Diuji di
 * docs/engineering/uji_perjalanan_warga.php.
 */
class Program extends Public_Controller {

    public function diagnosa() {
        redirect('warga/pendataan');
    }

    public function solusi_pembiayaan() {
        redirect('warga/pendataan');
    }

    public function hasil_diagnosa() {
        redirect('warga/pendataan');
    }

    public function ajukan_solusi() {
        $this->jalur_dipindah();
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

    public function api_cek_simperum() {
        $this->jalur_dipindah();
    }

    public function api_kalkulasi_program() {
        $this->jalur_dipindah();
    }

    public function submit_antrean() {
        $this->jalur_dipindah();
    }

    /**
     * Jawaban tunggal untuk seluruh POST jalur lama (keputusan 27 Sep 2026).
     * Tidak membaca satu pun isian, tidak menyentuh sesi diagnosa, SIMPERUM,
     * maupun sf_housing_queue. Skrip lama (AJAX) mendapat 410 berisi tujuan
     * barunya; formulir biasa dialihkan dengan pesan info.
     */
    private function jalur_dipindah() {
        if ($this->input->is_ajax_request()) {
            $this->output
                ->set_status_header(410)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'code' => 'jalur_dipindah',
                    'message' => 'Diagnosa dipindah ke Pendataan Warga.',
                    'redirect' => site_url('warga/pendataan'),
                ]));
            return;
        }
        $this->session->set_flashdata('info', 'Diagnosa dipindah ke Pendataan Warga.');
        redirect('warga/pendataan');
    }

    public function success() {
        $data['title'] = 'Pengajuan Berhasil - Klinik PKP';
        $data['ticket_code'] = $this->session->flashdata('ticket_code');

        $data['content'] = $this->load->view('pages/program/success_antrean', $data, TRUE);
        $this->load->view('layouts/main', $data);
    }
}
