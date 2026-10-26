<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Housing_assessment_model');
    }

    public function index()
    {
        $data['title'] = 'Tinjau Antrean'; // = label sidebar
        $data['scope_label'] = 'Semua Wilayah';
        $data['action_url']  = 'Admin/update_status';
        $data['empty_text']  = 'Belum ada antrean yang masuk.';
        $data['base_url']    = 'Admin';
        // Sakelar B2 (config/kebijakan_data.php) hanya untuk admin kab/kota; superadmin melihat identitas asli.
        $data['identitas_utuh'] = TRUE;
        $this->catat_akses_data_pribadi('identitas_warga', 'sf_antrean_pengajuan', 'daftar');
        $data += $this->antrean_table_data(NULL);
        // Ringkasan peringatan keamanan otomatis (poin 10.5); hanya superadmin yang melihatnya.
        $this->load->library('Security_alert');
        $data['peringatan_keamanan'] = $this->security_alert->ringkasan();

        $this->render_admin('admin/antrean/dashboard', $data);
    }

    /** Label pekerjaan wizard, disalin dari pages/warga/pendataan.php (termasuk kode lama). */
    private const LABEL_PEKERJAAN = [
        'farmer' => 'Petani', 'breeder' => 'Peternak', 'mining' => 'Pertambangan/Penggalian',
        'daily_laborer' => 'Buruh Harian', 'construction_worker' => 'Tukang Bangunan', 'trader' => 'Pedagang',
        'hotel_restaurant' => 'Hotel dan Rumah Makan', 'driver' => 'Sopir', 'educator' => 'Guru/Dosen',
        'health_worker' => 'Dokter/Bidan/Apoteker', 'civil_servant' => 'PNS/BUMN/BUMD', 'scavenger' => 'Pemulung',
        'military_police' => 'TNI/Polri', 'private_employee' => 'Pegawai Swasta', 'contract_worker' => 'PHL/PTT',
        'retired' => 'Pensiunan', 'unemployed' => 'Tidak Bekerja', 'other' => 'Lainnya',
        'horticulture' => 'Petani', 'plantation' => 'Petani', 'capture_fisher' => 'Lainnya',
        'aquaculture_fisher' => 'Lainnya', 'forestry_agriculture_other' => 'Lainnya', 'electricity_gas' => 'Lainnya',
        'information_communication' => 'Pegawai Swasta', 'finance_insurance' => 'Pegawai Swasta',
    ];

    /**
     * Unduh antrean sebagai Excel (permintaan dinas 26 Okt 2026), dengan kabupaten, kecamatan, dan desa
     * rumah yang didata. Hanya superadmin (Admin_Controller), identitas utuh seperti layar Tinjau Antrean,
     * jadi aksesnya dicatat sebagai akses data pribadi DAN jejak audit, dan dibatasi laju per akun.
     * Mengikuti penyaring status dan "Belum terpetakan" di layar; tanpa halaman (semua baris).
     */
    public function unduh()
    {
        $rate = $this->rate_limit_consume('antrean_unduh', ['account_id' => (int) $this->get_user_id()]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->session->set_flashdata('error', 'Terlalu sering mengunduh. Silakan coba lagi nanti.');
            redirect('Admin');
            return;
        }
        $status = $this->input->get('status', TRUE);
        $status = in_array($status, ['pending', 'needs_revision', 'approved', 'rejected'], TRUE) ? $status : NULL;
        $tanpa_wilayah = $this->input->get('tanpa_wilayah', TRUE) === '1';

        $this->db->select('q.*, p.nama_program, a.salinan_profil_ciphertext, kb.nama kabupaten, kc.nama kecamatan, ds.nama desa')
            ->from('sf_antrean_pengajuan q')
            ->join('sf_program p', 'p.id = q.program_id', 'left')
            ->join('sf_penilaian_perumahan a', 'a.id = q.penilaian_id', 'left')
            ->join('kabupaten kb', 'kb.id = COALESCE(a.kabupaten_id, q.kabupaten_id)', 'left')
            ->join('kecamatan kc', 'kc.id = a.kecamatan_id', 'left')
            ->join('desa ds', 'ds.id = a.desa_id', 'left');
        if ($status) { $this->db->where('q.status_antrean', $status); }
        if ($tanpa_wilayah) { $this->db->where('q.kabupaten_id IS NULL', NULL, FALSE); }
        // ponytail: semua baris sekaligus; pecah per halaman bila antrean melewati puluhan ribu.
        $rows = $this->db->order_by('q.created_at', 'DESC')->get()->result();

        $label = housing_queue_statuses();
        $this->load->library('Matriks_program_ruleset');
        // Isian pendataan lengkap (permintaan dinas 26 Okt 2026), label sama dengan Detail Penilaian Warga.
        $this->config->load('label_pendataan', TRUE);
        $kolom = $this->config->item('label_pendataan_kolom', 'label_pendataan');
        $nilai = $this->config->item('label_pendataan_nilai', 'label_pendataan');
        $angka = $this->config->item('label_pendataan_angka', 'label_pendataan');
        $isi = [];
        foreach ($rows as $r) {
            $this->buka_pii_antrean($r);
            // ponytail: satu kueri detail per baris (dekripsi penilaian); cukup untuk ratusan baris antrean.
            $penilaian = $r->penilaian_id ? ($this->Housing_assessment_model->get_scoped_queue_detail((int) $r->id)['assessment'] ?? []) : [];
            $survey   = json_decode((string) $r->data_survey_json, TRUE) ?: [];
            $simperum = json_decode((string) $r->data_simperum_json, TRUE) ?: [];
            $profil = [];
            if ( ! empty($r->salinan_profil_ciphertext)) {
                $profil = kunci_tersimpan_ke_baru(json_decode((string) $this->encryption_lib->decrypt($r->salinan_profil_ciphertext), TRUE) ?: []);
            }
            $pekerjaan = $profil['pekerjaan'] ?? NULL;
            $penghasilan = $profil['penghasilan_bulanan'] ?? ($survey['penghasilan'] ?? NULL);
            $desil = $profil['desil_kesejahteraan'] ?? ($simperum['desil'] ?? NULL);
            // Desil kosong: mesin rekomendasi memakai desil turunan penghasilan (Warga::step), ditulis apa adanya dan diberi tanda.
            if (($desil === NULL || $desil === '') && is_numeric($penghasilan)) {
                $turunan = $this->matriks_program_ruleset->decile_for_monthly_income($penghasilan);
                $desil = $turunan ? $turunan . ' (dari penghasilan)' : NULL;
            }
            $isi[] = [
                $r->kode_tiket, date('d-m-Y H:i', strtotime($r->created_at)),
                $profil['full_name'] ?? $r->nama_lengkap, $profil['nik'] ?? $r->nik_pengaju,
                $r->nama_program ?? 'Hunian sudah layak huni', $label[$r->status_antrean]['label'] ?? $r->status_antrean,
                is_numeric($desil) ? (int) $desil : $desil,
                $pekerjaan !== NULL ? (self::LABEL_PEKERJAAN[$pekerjaan] ?? $pekerjaan) : ($survey['pekerjaan'] ?? NULL),
                is_numeric($penghasilan) ? (int) $penghasilan : $penghasilan,
                $r->kabupaten, $r->kecamatan, $r->desa, $profil['address'] ?? NULL,
            ];
            $baris = &$isi[count($isi) - 1];
            foreach (array_keys($kolom) as $k) {
                $v = $penilaian[$k] ?? NULL;
                $baris[] = ($v === NULL || $v === '') ? NULL
                    : (in_array($k, $angka, TRUE) && is_numeric($v) ? (float) $v + 0 : ($nilai[$k][(string) $v] ?? (string) $v));
            }
            $baris[] = $penilaian['location_lat'] ?? NULL;
            $baris[] = $penilaian['location_lng'] ?? NULL;
            $baris[] = $r->catatan_admin;
            unset($baris);
        }

        $this->catat_akses_data_pribadi('identitas_warga', 'sf_antrean_pengajuan', 'unduh', ['baris' => count($isi)]);
        $this->catat_audit('rekap_diunduh', 'Antrean pengajuan diunduh (' . count($isi) . ' baris)',
            'sf_antrean_pengajuan', NULL, ['status' => $status, 'tanpa_wilayah' => $tanpa_wilayah, 'baris' => count($isi)]);
        $this->kirim_spreadsheet('Antrean Pengajuan ' . date('Y-m-d'), 'Antrean', array_merge([
            'Kode tiket', 'Tanggal', 'Nama', 'NIK', 'Program', 'Status', 'Desil', 'Pekerjaan',
            'Penghasilan per bulan (Rp)', 'Kabupaten/Kota', 'Kecamatan', 'Desa/Kelurahan', 'Alamat',
        ], array_values($kolom), ['Lintang', 'Bujur', 'Catatan admin']), $isi);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }

        // S6 - jalur superadmin dulu MELEWATI pembatas laju yang sudah dipasang
        // di Admin_Kabkota::update_status(). Policy `admin_queue_decision` yang
        // sama dipakai di sini, bukan mekanisme kedua (§17 poin 15): dimensi
        // ip+account+object, jadi satu akun yang membanjiri satu antrean tetap
        // tertahan sekalipun ia superadmin.
        $antrean_id = (int) $this->input->post('antrean_id');
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id'  => $antrean_id,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak keputusan dalam waktu singkat. Silakan coba lagi sebentar.',
                $this->input->is_ajax_request()
            );
            return;
        }

        $result = $this->Housing_assessment_model->transition_queue(
            $this->input->post('antrean_id'),
            $this->input->post('status_awal', TRUE),
            $this->input->post('status', TRUE),
            $this->get_user_id(),
            NULL,
            $this->input->post('catatan_admin', TRUE)
        );

        $this->session->set_flashdata(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? 'Keputusan berhasil disimpan. Sinkronisasi ke SIMPERUM belum tersedia.'
                : $result['message']
        );
        redirect('Admin');
    }

    public function detail($antrean_id)
    {
        $data = $this->assessment_detail_data($antrean_id, NULL);
        if ( ! $data) { show_404(); return; }
        $data += ['title' => 'Detail Penilaian Warga', 'back_url' => 'Admin', 'action_url' => 'Admin/update_status', 'evidence_url' => 'Admin/evidence'];
        $this->render_admin('admin/antrean/detail', $data);
    }

    public function evidence($antrean_id, $jenis_berkas)
    {
        $file = $this->scoped_queue_file($antrean_id, $jenis_berkas, NULL);
        if ( ! $file) { show_404(); return; }
        $this->serve_private_file('warga_assessment', $file['storage_assessment_id'], $file['path_privat'], $file['mime_type']);
    }

}
