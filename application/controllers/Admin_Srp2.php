<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Srp2 extends Admin_Controller {
    public function index() {
        $data['title'] = 'Daftar Pengembang SRP2';
        $data['rows'] = $this->db->order_by('nama_perusahaan', 'ASC')->get('srp2_certified_developers')->result();
        $this->render_admin('admin/srp2/index', $data);
    }

    /**
     * Daftar pengajuan SRP2 yang menunggu keputusan — menutup gap dari
     * docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md Fase 1 (sebelumnya tidak
     * ada alur admin sama sekali untuk srp2_registrations, lihat
     * docs/engineering/AUDIT_ROLE_PENGEMBANG.md Temuan #1).
     */
    public function pending() {
        $data['title'] = 'Verifikasi SRP2';

        // Cari + urut + paginasi semuanya server-side (B8).
        $table = $this->table_state(['updated_at', 'nama_perusahaan', 'email'], 'updated_at');
        $data['base_url'] = 'Admin_Srp2/pending';

        // from() di depan, lalu count_all_results('', FALSE) — kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->from('srp2_registrations')->where('status_verifikasi', 'Pending');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama_perusahaan', $table['q'])->or_like('email', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_admin('admin/srp2/pending', $data);
    }

    /**
     * Detail satu pengajuan + status unggah 14 dokumen. Dokumen dibuka lewat
     * lihat_dokumen() (endpoint ber-guard), tidak pernah lewat path publik.
     */
    public function detail($id = NULL) {
        if ( ! is_numeric($id)) { show_404(); }
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id])->row();
        if ( ! $data['pendaftar']) { show_404(); }

        $this->load->helper('srp2');
        $data['dokumen_list'] = srp2_dokumen_persyaratan();

        $data['uploaded'] = [];
        foreach ($this->db->where('registration_id', (int) $id)->get('srp2_documents')->result() as $doc) {
            $data['uploaded'][$doc->document_key] = $doc;
        }

        $data['title'] = 'Detail Pengajuan SRP2';
        $this->render_admin('admin/srp2/detail', $data);
    }

    /**
     * Terima/tolak satu pengajuan — satu endpoint dengan field 'status',
     * pola yang sama persis dengan Admin_Kemitraan::proses() (bukan dua
     * method terima()/tolak() terpisah seperti draft awal PRD) supaya
     * komponen admin/components/review_form.php benar-benar dipakai ulang
     * tanpa modifikasi bentuk endpoint.
     *
     * Terima otomatis meng-upsert srp2_certified_developers berbasis
     * certified_developer_id (bukan insert baru tiap kali) — idempotent
     * terhadap approve berulang (PRD FR-10). Tolak wajib catatan_admin,
     * divalidasi SERVER (PRD FR-09), bukan cuma atribut required di HTML.
     */
    public function proses($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $reg = $this->db->get_where('srp2_registrations', ['id' => (int) $id])->row();
        if ( ! $reg) { show_404(); }

        $catatan = trim((string) $this->input->post('catatan_admin', TRUE));
        if ($status === 'Ditolak' && $catatan === '') {
            $this->session->set_flashdata('error', 'Catatan wajib diisi saat menolak pengajuan.');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $update = [
            'status_verifikasi' => $status,
            'catatan_admin'     => $status === 'Ditolak' ? $catatan : NULL,
            'reviewed_by'       => $this->get_user_id(),
            'reviewed_at'       => date('Y-m-d H:i:s'),
        ];

        if ($status === 'Diterima') {
            // Direktori publik srp2_certified_developers tetap tabel terpisah
            // (opsi b, docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) —
            // link berbasis ID, bukan pencocokan nama string seperti sebelumnya.
            $payload = [
                'nama_perusahaan' => $reg->nama_perusahaan,
                'alamat_kantor'   => $reg->alamat_kantor,
                'website'         => $reg->website,
                'instagram'       => $reg->instagram,
                'sosmed_lainnya'  => $reg->sosmed_lainnya,
                'status_aktif'    => 1,
            ];
            if ($reg->certified_developer_id) {
                $this->db->where('id', $reg->certified_developer_id)->update('srp2_certified_developers', $payload);
                $update['certified_developer_id'] = $reg->certified_developer_id;
            } else {
                $this->db->insert('srp2_certified_developers', $payload);
                $update['certified_developer_id'] = $this->db->insert_id();
            }
        }

        $this->db->where('id', (int) $id)->update('srp2_registrations', $update);

        $this->session->set_flashdata('success', $status === 'Diterima'
            ? 'Pengajuan diterima — pengembang masuk direktori publik.'
            : 'Pengajuan ditolak, catatan sudah dikirim ke pengembang.');
        redirect('Admin_Srp2/pending');
    }

    /**
     * Sajikan satu dokumen SRP2 ke admin. Endpoint ber-guard (Admin_Controller),
     * baca file dari private_uploads/ (di luar webroot) — tidak pernah lewat
     * path publik. Menutup PRD FR-11.
     */
    public function lihat_dokumen($id = NULL, $document_key = NULL) {
        if ( ! is_numeric($id) || empty($document_key)) { show_404(); }

        $doc = $this->db->where(['registration_id' => (int) $id, 'document_key' => $document_key])
            ->get('srp2_documents')->row();
        if ( ! $doc) { show_404(); }

        $path = dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR
            . 'srp2' . DIRECTORY_SEPARATOR . (int) $id . DIRECTORY_SEPARATOR . $doc->stored_name;
        if ( ! is_file($path)) { show_404(); }

        $this->output->set_content_type($doc->mime_type);
        readfile($path);
    }

    public function save() {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $id = (int) $this->input->post('id');
        $name = strtoupper(trim((string) $this->input->post('nama_perusahaan', TRUE)));
        if ($name === '' || strlen($name) > 180) { $this->session->set_flashdata('error', 'Nama perusahaan wajib diisi.'); redirect('Admin_Srp2'); return; }
        $urls = [];
        foreach (['website', 'instagram', 'sosmed_lainnya'] as $field) {
            $value = trim((string) $this->input->post($field, TRUE));
            if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], TRUE))) { $this->session->set_flashdata('error', 'Link ' . $field . ' harus menggunakan URL http/https yang valid.'); redirect('Admin_Srp2'); return; }
            $urls[$field] = $value ?: null;
        }
        $payload = array_merge(['nama_perusahaan' => $name, 'status_aktif' => $this->input->post('status_aktif') ? 1 : 0, 'alamat_kantor' => trim((string) $this->input->post('alamat_kantor', TRUE))], $urls);
        if ($id) { $this->db->where('id', $id)->update('srp2_certified_developers', $payload); } else { $this->db->insert('srp2_certified_developers', $payload); }
        $this->session->set_flashdata('success', 'Daftar pengembang diperbarui.'); redirect('Admin_Srp2');
    }

    public function delete($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || !is_numeric($id)) { show_404(); }
        $this->db->where('id', (int) $id)->delete('srp2_certified_developers');
        $this->session->set_flashdata('success', 'Pengembang dihapus dari daftar.'); redirect('Admin_Srp2');
    }
}
