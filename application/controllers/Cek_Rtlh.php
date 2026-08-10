<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cek RTLH - apakah satu NIK terdaftar di data Rumah Tidak Layak Huni SIMPERUM.
 *
 * Revisi dinas 3 Agt 2026, butir 11.
 *
 * WAJIB LOGIN, dan gerbangnya di konstruktor - bukan sekadar tautannya
 * disembunyikan. Dua alasan, dan yang kedua yang menentukan:
 *
 * 1. Ini data yang menandai KEMISKINAN. "NIK X terdaftar RTLH" adalah kalimat
 *    tentang keadaan rumah seseorang, dan bukan miliknya yang bertanya.
 * 2. Repo ini SUDAH punya aturan itu, dan aturan itu terlihat disengaja:
 *    `Program::api_cek_simperum()` publik, tetapi begitu `simperum_mode`
 *    bernilai `api` ia menolak dengan 409 - "Pencarian SIMPERUM nyata hanya
 *    tersedia melalui Wizard Baru Warga" - dan wizard itu mensyaratkan login.
 *    Membangun layar publik yang memanggil API yang sama berarti membatalkan
 *    kontrol itu lewat pintu baru.
 *
 * ⚠️ NIK + TANGGAL LAHIR BUKAN DUA RAHASIA. Tanggal lahir TERKANDUNG di dalam
 * NIK (digit 7-12, ddmmyy, +40 pada hari untuk perempuan) - dan
 * `Simperum_gateway::birth_date_matches()` memang menghitungnya dari situ saat
 * sumbernya tidak memuat tanggal lahir. Jadi meminta keduanya menambah NOL
 * entropi terhadap orang yang sudah memegang NIK-nya; ia berguna sebagai
 * pencegah salah ketik, bukan sebagai anti-enumerasi. Yang benar-benar
 * menahan di sini adalah gerbang login + batas laju per akun. Jangan mencabut
 * keduanya dengan alasan "kan sudah ada tanggal lahir".
 */
class Cek_Rtlh extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if ( ! $this->is_logged_in()) {
            $this->session->set_flashdata('error',
                'Silakan login terlebih dahulu untuk memakai Cek Data Rumah.');
            $this->gerbang_login();
        }
    }

    public function index()
    {
        $this->render('pages/golek_omah/cek_rtlh', [
            'judul'  => '',
            'hasil'  => $this->session->flashdata('rtlh_hasil'),
            'isian'  => $this->session->flashdata('rtlh_isian') ?: [],
        ]);
    }

    /**
     * POST → periksa → redirect (PRG). Hasilnya lewat flashdata supaya
     * memuat-ulang halaman tidak mengulang pencarian: tiap pencarian menyentuh
     * API luar dan menulis satu snapshot, jadi refresh yang mengulanginya bukan
     * sekadar tidak rapi.
     */
    public function periksa()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $tgl = trim((string) $this->input->post('tgl_lahir', TRUE));

        // Isian dikembalikan supaya orang tidak mengetik ulang 16 digit setelah
        // satu salah ketik. NIK-nya sendiri tidak pernah ikut ke URL.
        $this->session->set_flashdata('rtlh_isian', ['nik' => $nik, 'tgl_lahir' => $tgl]);

        if ( ! preg_match('/^\d{16}$/', $nik) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
            $this->session->set_flashdata('error', 'NIK harus 16 digit dan tanggal lahir wajib diisi.');
            redirect('Cek_Rtlh');
            return;
        }

        $rate = $this->rate_limit_consume('rtlh_cek', ['account_id' => (int) $this->get_user_id()]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate, 'Terlalu banyak pencarian. Silakan coba lagi nanti.');
            return;
        }

        $this->load->library('simperum_gateway');
        /**
         * `$requested_by` sengaja NULL, BUKAN id pengguna.
         *
         * Mengisinya membuat `lookup()` memanggil `save_profile()` dan MENIMPA
         * profil pendataan warga orang itu dengan data NIK yang barusan dicari -
         * termasuk kalau yang dicari NIK orang lain. Cek cepat tidak boleh punya
         * efek samping pada data pengajuan; wizard `Warga::pendataan()` yang
         * memang berwenang menulis ke sana.
         */
        $hasil = $this->simperum_gateway->lookup($nik, $tgl, NULL);

        $this->session->set_flashdata('rtlh_hasil', [
            'status'     => $hasil['status'],
            'pesan'      => $hasil['message'],
            'simulasi'   => ! empty($hasil['simulation']),
            'profil'     => $hasil['data']['profile'] ?? NULL,
            'nik_ekor'   => substr($nik, -4),
        ]);
        /* Kode aksi `rtlh_dicek` SENGAJA TIDAK ikut diganti meski labelnya
           berubah: penyaring jejak audit memakai kode ini, dan menggantinya
           memutus entri lama dari entri baru. Yang berubah cuma kalimat yang
           dibaca manusia. */
        $this->catat_audit('rtlh_dicek',
            'Cek Data Rumah untuk NIK berakhiran ' . substr($nik, -4) . ' - hasil: ' . $hasil['status'],
            'simperum', NULL, ['status' => $hasil['status'], 'mode' => $hasil['source_mode'] ?? NULL]);

        redirect('Cek_Rtlh');
    }
}
