<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * UAT No. 18 (10 Sep 2026): hasil Cek Data Rumah tersedia tanpa login.
 * Tamu hanya melihat status pencarian dan intervensi; identitas tidak ikut.
 * Pembatas laju, CSRF, penyamaran NIK, dan pencatatan audit tetap berlaku.
 * Sumber saat ini GetDataRTLH; kategori Backlog belum tersedia dari adapter.
 */
class Cek_Rtlh extends MY_Controller {

    public function index()
    {
        $this->render('pages/golek_omah/cek_rtlh', [
            'judul'       => '',
            'hasil'       => $this->session->flashdata('rtlh_hasil'),
            'isian'       => $this->session->flashdata('rtlh_isian') ?: [],
            'sudah_login' => $this->is_logged_in(),
        ]);
    }

    /** POST -> hasil flashdata -> redirect; refresh tidak mengulang lookup. */
    public function periksa()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $this->session->set_flashdata('rtlh_isian', ['nik' => $nik]);
        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $this->session->set_flashdata('error', 'NIK harus 16 digit angka.');
            redirect('Cek_Rtlh');
            return;
        }

        $sudah_login = $this->is_logged_in();
        $policies = $sudah_login ? [
            ['rtlh_cek', 'Terlalu banyak pencarian dalam satu jam. Silakan coba lagi nanti.'],
            ['rtlh_cek_harian', 'Batas pencarian harian tercapai. Silakan lanjutkan besok.'],
        ] : [
            ['rtlh_cek_anon', 'Terlalu banyak pencarian dalam satu jam. Silakan coba lagi nanti.'],
        ];
        $context = $sudah_login ? ['account_id' => (int) $this->get_user_id()] : [];
        foreach ($policies as [$policy, $pesan]) {
            $rate = $this->rate_limit_consume($policy, $context);
            if (empty($rate['success']) || empty($rate['allowed'])) {
                $this->rate_limit_reject($rate, $pesan);
                return;
            }
        }

        $this->load->library('simperum_gateway');
        // NULL wajib: lookup untuk cek cepat tidak boleh menimpa profil
        // pendataan pemanggil dengan NIK yang sedang diperiksa.
        // TRUE hanya melepas tanggal lahir pada layar ini; penjaga NIK
        // yang cocok dengan respons API tetap ditegakkan oleh gateway.
        $hasil = $this->simperum_gateway->lookup($nik, '', NULL, TRUE);
        $profil = $hasil['data']['profile'] ?? NULL;
        if ( ! $sudah_login && $profil) {
            $profil = ['status_intervensi' => $profil['status_intervensi'] ?? 'Belum tersedia'];
        }
        $this->session->set_flashdata('rtlh_hasil', [
            'status'   => $hasil['status'],
            'pesan'    => $hasil['message'],
            'simulasi' => ! empty($hasil['simulation']),
            'profil'   => $profil,
            'nik_ekor' => substr($nik, -4),
        ]);
        $this->catat_audit('rtlh_dicek',
            'Cek Data Rumah untuk NIK berakhiran ' . substr($nik, -4) . ' - hasil: ' . $hasil['status'],
            'simperum', NULL, ['status' => $hasil['status'], 'mode' => $hasil['source_mode'] ?? NULL]);
        redirect('Cek_Rtlh');
    }
}
