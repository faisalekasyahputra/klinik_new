<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kabkota extends Admin_Kabkota_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Housing_assessment_model');
    }

    public function index()
    {
        $data['title'] = 'Antrean Wilayah Saya'; // = label sidebar
        $data['scope_label'] = $this->db->where('id', $this->my_kabupaten_id)
            ->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
        $data['action_url'] = 'Admin_Kabkota/update_status';
        $data['empty_text'] = 'Belum ada antrean di wilayah Anda.';
        $data['base_url'] = 'Admin_Kabkota';
        $data += $this->antrean_table_data($this->my_kabupaten_id);
        // Cakupan wilayah: kabupaten_id dari sesi (Admin_Kabkota_Controller), bukan dari permintaan.
        $data['tercocokkan_simperum'] = $this->db->table_exists('sf_data_simperum')
            ? (int) $this->db->where('status_respons', 'found')->where('kabupaten_id', $this->my_kabupaten_id)
                ->count_all_results('sf_data_simperum') : 0;

        $this->render_scoped_admin('admin/antrean/dashboard', $data);
    }

    /** Pendataan awal warga di wilayah ini: sudah menyimpan rekomendasi awal, belum dikirim. */
    public function pendataan_awal()
    {
        // ponytail: offset dihitung dari ?page sebelum total diketahui; halaman di luar jangkauan
        // cukup tampil kosong, tidak perlu query hitung terpisah.
        [$rows, $total] = $this->Housing_assessment_model->pendataan_awal_wilayah(
            $this->my_kabupaten_id, 25, (max(1, (int) $this->input->get('page')) - 1) * 25);
        $table = array_merge($this->table_state([], NULL), $this->paginate_state($total));
        $data = [
            'title' => 'Pendataan Awal Warga',
            'scope_label' => $this->db->where('id', $this->my_kabupaten_id)->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
            'rows' => $rows, 'table' => $table, 'pager' => $table, 'base_url' => 'Admin_Kabkota/pendataan_awal',
        ];
        $this->render_scoped_admin('admin/antrean/pendataan_awal', $data);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }

        $antrean_id = (int) $this->input->post('antrean_id');
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $antrean_id,
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
            $antrean_id,
            $this->input->post('status_awal', TRUE),
            $this->input->post('status', TRUE),
            $this->get_user_id(),
            $this->my_kabupaten_id,
            $this->input->post('catatan_admin', TRUE)
        );

        $this->session->set_flashdata(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Keputusan pengajuan berhasil disimpan.' : $result['message']
        );
        redirect('Admin_Kabkota');
    }

    /**
     * Berkas yang memuat identitas warga (KTP, KK, foto diri, bukti pindah tangan yang memuat nama
     * pihak). Selama sakelar B2 menunggu keputusan, berkas ini tidak tersaji untuk admin kab/kota,
     * sama dengan nama/NIK/KK yang diganti data contoh. Foto kondisi rumah dan lahan tetap tersaji,
     * sejalan dengan data kondisi rumah di detail yang juga tidak disamarkan: yang ditunda kebijakan
     * adalah identitas dan keadaan ekonomi, bukan keadaan bangunan.
     */
    private const BERKAS_IDENTITAS = ['id_card_photo', 'family_card_photo', 'land_owner_family_card_photo', 'self_photo', 'recipient_photo', 'land_transfer_proof'];

    private function identitas_ditunda()
    {
        $this->config->load('kebijakan_data', TRUE, TRUE);
        return $this->config->item('identitas_warga_kabkota', 'kebijakan_data') === 'menunggu_keputusan';
    }

    public function detail($antrean_id)
    {
        $data = $this->assessment_detail_data($antrean_id, $this->my_kabupaten_id);
        if ( ! $data) { show_404(); return; }
        // Sakelar B2 (config/kebijakan_data.php) berlaku juga di detail, bukan hanya di daftar antrean.
        if ($this->identitas_ditunda()) {
            $contoh = [
                'full_name' => 'Warga Contoh ' . str_pad((string) (int) $antrean_id, 3, '0', STR_PAD_LEFT),
                'address' => 'Alamat contoh - menunggu keputusan dinas', 'birth_date' => '1980-01-01',
                'family_card_number' => '0000000000000000', 'phone' => '080000000000',
                'desil_kesejahteraan' => (string) (1 + ((int) $antrean_id % 10)),
            ];
            $data['profile'] = $contoh + $data['profile'];
            foreach (['identity', 'socioeconomic'] as $bagian) {
                if (isset($data['source_snapshot'][$bagian]) && is_array($data['source_snapshot'][$bagian])) {
                    $data['source_snapshot'][$bagian] = array_intersect_key($contoh, $data['source_snapshot'][$bagian]) + $data['source_snapshot'][$bagian];
                }
            }
            // Isian matriks yang menggambarkan keadaan ekonomi/keluarga, bukan bangunan.
            foreach (['matriks_penghasilan', 'matriks_pekerjaan_keuangan', 'matriks_status_dtks', 'matriks_status_keluarga'] as $k) {
                unset($data['assessment'][$k]);
            }
            $data['evidence'] = array_values(array_filter($data['evidence'] ?? [],
                fn($f) => ! in_array($f['jenis_berkas'] ?? '', self::BERKAS_IDENTITAS, TRUE)));
        }
        $data += ['title' => 'Detail Penilaian Warga', 'back_url' => 'Admin_Kabkota', 'action_url' => 'Admin_Kabkota/update_status', 'evidence_url' => 'Admin_Kabkota/evidence'];
        $this->render_scoped_admin('admin/antrean/detail', $data);
    }

    public function evidence($antrean_id, $jenis_berkas)
    {
        if ($this->identitas_ditunda() && in_array((string) $jenis_berkas, self::BERKAS_IDENTITAS, TRUE)) { show_404(); return; }
        $file = $this->scoped_queue_file($antrean_id, $jenis_berkas, $this->my_kabupaten_id);
        if ( ! $file) { show_404(); return; }
        $this->serve_private_file('warga_assessment', $file['storage_assessment_id'], $file['path_privat'], $file['mime_type']);
    }

}
