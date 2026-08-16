<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cek RTLH - apakah satu NIK terdaftar di data Rumah Tidak Layak Huni SIMPERUM.
 *
 * Revisi dinas 3 Agt 2026, butir 11.
 *
 * WAJIB LOGIN untuk MELIHAT HASIL (bukan lagi untuk membuka halamannya -
 * lihat catatan 14 Agt 2026 di bawah). Dua alasan awal kenapa hasilnya
 * tetap dijaga di balik login, dan yang kedua yang menentukan:
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
 *
 * GERBANG DILONGGARKAN 14 Agt 2026 - permintaan user, tombol "Cek Data Rumah"
 * di /golek_omah TIDAK LAGI mengusir tamu ke layar login. Ini BUKAN mencabut
 * proteksi di atas - dipertimbangkan dulu 2 opsi lain (buka penuh / batasi ke
 * NIK sendiri) sebelum user memilih pendekatan ini secara eksplisit:
 *
 *   - Tamu ANONIM boleh membuka halaman & mengetik NIK, TAPI hasilnya TIDAK
 *     PERNAH ditampilkan ke anonim - lihat periksa_anonim(). Submit-nya
 *     bahkan tidak memanggil Simperum_gateway sama sekali (tidak ada
 *     jawaban untuk disembunyikan = tidak ada oracle sama sekali buat
 *     anonim), cuma menyimpan NIK di session lalu mengajak masuk/daftar -
 *     pola SAMA PERSIS dengan Warga::lookup_anonim().
 *   - Begitu SUDAH login, perilakunya PERSIS SAMA seperti sebelum perubahan
 *     ini - siapa pun yang login tetap bisa memeriksa NIK siapa saja (tidak
 *     dibatasi ke NIK sendiri). Keputusan SADAR: user diberi tahu ini
 *     membuka celah "login dulu (akun apa saja) lalu cek NIK siapa saja
 *     tetap bisa" - dan memilih tetap begini, BUKAN kelalaian. Proteksi
 *     sungguhannya TETAP gerbang-login + batas laju per akun + jejak audit,
 *     PERSIS seperti paragraf di atas - anonim cuma tidak lagi diusir dari
 *     HALAMANNYA, bukan dari HASILNYA.
 */
class Cek_Rtlh extends MY_Controller {

    public function index()
    {
        /* Jaring pengaman: tamu anonim yang barusan mengetik NIK (lihat
           periksa_anonim()) lalu masuk/daftar (intended_url membawanya balik
           ke sini, lihat Auth::_redirect_after_login()) - NIK-nya diisikan
           lagi ke form supaya tidak perlu diketik ulang. Sengaja SATU KLIK
           tambahan (submit ulang), bukan otomatis dijalankan - pola sama
           dengan Warga::pendataan(), lihat komentarnya di sana. */
        $sudah_login = $this->is_logged_in();
        $isian = $this->session->flashdata('rtlh_isian') ?: [];
        if (empty($isian['nik']) && $sudah_login) {
            $pending_nik = $this->session->userdata('rtlh_pending_nik');
            if ( ! empty($pending_nik)) {
                $isian['nik'] = $pending_nik;
                $this->session->unset_userdata('rtlh_pending_nik');
            }
        }
        $this->render('pages/golek_omah/cek_rtlh', [
            'judul'       => '',
            'hasil'       => $this->session->flashdata('rtlh_hasil'),
            'isian'       => $isian,
            // View tidak bisa memanggil $this->is_logged_in() sendiri - $this
            // di dalam view yang di-include load->view() adalah CI_Loader,
            // bukan controller (cuma akses PROPERTY seperti $this->security
            // yang diproksi otomatis, method kustom tidak). Dikirim jadi
            // variabel biasa, sama pola dgn variabel lain di sini.
            'sudah_login' => $sudah_login,
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

        if ( ! $this->is_logged_in()) {
            $this->periksa_anonim();
            return;
        }

        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $tgl = trim((string) $this->input->post('tgl_lahir', TRUE));

        // Isian dikembalikan supaya orang tidak mengetik ulang 16 digit setelah
        // satu salah ketik. NIK-nya sendiri tidak pernah ikut ke URL.
        $this->session->set_flashdata('rtlh_isian', ['nik' => $nik, 'tgl_lahir' => $tgl]);

        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $this->session->set_flashdata('error', 'NIK harus 16 digit angka.');
            redirect('Cek_Rtlh');
            return;
        }

        /* BUTIR 5 PUTARAN 2: DUA batas, bukan satu, dan itu penggantinya.
           Tanggal lahir dulu menjadi pengaman anti-penelusuran di layar ini.
           Dinas memutuskan melepasnya (dikonfirmasi user 11 Agt 2026), jadi
           penggantinya dipasang bersamaan - kalau tidak, satu-satunya yang
           menghalangi orang memeriksa status kemiskinan tetangganya adalah
           kesabaran.

           Per JAM menahan sapuan cepat; per HARI menahan yang sabar. Batas
           per jam sendirian membiarkan 240 NIK diperiksa dalam sehari, dan
           orang yang menelusuri memang tidak buru-buru. */
        foreach ([
            ['rtlh_cek', 'Terlalu banyak pencarian dalam satu jam. Silakan coba lagi nanti.'],
            ['rtlh_cek_harian', 'Batas pencarian harian tercapai. Silakan lanjutkan besok.'],
        ] as [$policy, $pesan]) {
            $rate = $this->rate_limit_consume($policy, ['account_id' => (int) $this->get_user_id()]);
            if (empty($rate['success']) || empty($rate['allowed'])) {
                $this->rate_limit_reject($rate, $pesan);
                return;
            }
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
        $hasil = $this->simperum_gateway->lookup($nik, '', NULL, TRUE);

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

    /**
     * Cabang ANONIM dari periksa() - lihat komentar panjang di atas class.
     * TIDAK PERNAH memanggil Simperum_gateway: tidak ada jawaban yang
     * dihasilkan sama sekali untuk anonim, jadi tidak ada apa pun yang bisa
     * bocor lewat celah timing/oracle apa pun - beda dari sekadar
     * "menyembunyikan hasilnya di view", yang masih rawan disalahtafsir jadi
     * kebiasaan lain nanti. NIK-nya cuma disimpan di session supaya form
     * terisi lagi sesudah masuk/daftar (lihat index()) - TIDAK ditulis ke DB
     * mana pun, sama seperti Warga::lookup_anonim().
     */
    private function periksa_anonim()
    {
        $nik = preg_replace('/\D+/', '', (string) $this->input->post('nik', TRUE));
        $this->session->set_flashdata('rtlh_isian', ['nik' => $nik, 'tgl_lahir' => '']);

        if ( ! preg_match('/^\d{16}$/', $nik)) {
            $this->session->set_flashdata('error', 'NIK harus 16 digit angka.');
            redirect('Cek_Rtlh');
            return;
        }

        // Dimensi ip saja - tidak ada akun untuk dijadikan dimensi account.
        // Pola sama dengan Warga::lookup_anonim()/warga_lookup_anon.
        $rate = $this->rate_limit_consume('rtlh_cek_anon');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate,
                'Terlalu banyak percobaan. Silakan coba lagi sebentar, atau masuk/daftar akun untuk batas yang lebih longgar.');
            return;
        }

        $this->session->set_userdata('rtlh_pending_nik', $nik);
        $this->session->set_userdata('intended_url', 'Cek_Rtlh');
        $this->session->set_flashdata('rtlh_hasil', [
            'status' => 'login_required',
            'pesan'  => 'Masuk atau daftar akun terlebih dahulu untuk melihat hasil pemeriksaan NIK ini.',
        ]);
        redirect('Cek_Rtlh');
    }
}
