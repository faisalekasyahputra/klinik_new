<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tinjauan tahap dua KKN/Magang oleh admin bidang.
 *
 * Alur suratnya: mahasiswa mengirim surat pengantar -> sekretariat Disperakim
 * meneruskan atau menolak -> BIDANG tujuan memutuskan ->
 * surat balasan diunggah sekretariat. Layar ini meja yang kedua.
 *
 * Scope-nya datang dari `Admin_Bidang_Controller`: `$this->my_bidang_kode` diambil
 * dari sesi, bukan dari request. Yang menentukan pendaftaran mana yang terlihat
 * adalah `bidang_kode` pada pendaftarannya — kolom sungguhan sejak migrasi
 * 20260701000031, bukan pencocokan lewat nama.
 */
class Kemitraan_Bidang extends Admin_Bidang_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    public function index()
    {
        $data['title'] = 'Magang Bidang Saya';

        $table = $this->table_state([
            'kkn_magang_pendaftaran.created_at', 'usr_users.name',
            'kkn_magang_pendaftaran.instansi_asal', 'kkn_magang_pendaftaran.status',
        ], 'kkn_magang_pendaftaran.created_at');
        $data['base_url'] = 'Kemitraan_Bidang';

        // Disaring lewat KOLOM `bidang_kode` pada pendaftarannya. Baris lama
        // yang tidak menyebut bidang (KKN, atau pendaftaran sebelum migrasi 031)
        // tidak muncul di sini — dan memang seharusnya begitu: ia bukan
        // tanggung jawab bidang mana pun.
        $this->db->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.jenis', 'magang')
            ->where('kkn_magang_pendaftaran.bidang_kode', $this->my_bidang_kode);

        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('usr_users.name', $table['q'])->or_like('usr_users.email', $table['q'])
                ->or_like('kkn_magang_pendaftaran.instansi_asal', $table['q'])
                ->or_like('kkn_magang_pendaftaran.divisi_atau_tema', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa,
                usr_users.email AS email_mahasiswa')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_scoped_admin('admin/kemitraan_bidang/index', $data);
    }

    /**
     * Sajikan dokumen pendukung ke peninjau bidang.
     *
     * Guard-nya BUKAN sekadar "saya admin_bidang": barisnya harus benar-benar
     * milik bidang saya. Tanpa pemeriksaan itu, mengganti angka di URL berarti
     * membaca surat pengantar milik bidang lain — dan ini dokumen kependudukan.
     */
    public function lihat_dokumen($id = NULL, $berkas = 'surat')
    {
        if ( ! is_numeric($id)) { show_404(); }

        $kolom = ['surat' => 'file_surat_pengantar', 'proposal' => 'file_proposal'][$berkas] ?? NULL;
        if ($kolom === NULL) { show_404(); }

        $row = $this->baris_bidang_saya($id);
        if ( ! $row || empty($row->$kolom)) { show_404(); }

        $ext  = strtolower(pathinfo($row->$kolom, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('kemitraan', (int) $row->id, $row->$kolom, $mime);
    }

    public function proses($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->baris_bidang_saya($id);
        if ( ! $row) { show_404(); }

        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Kemitraan_Bidang');
            return;
        }

        // Hanya yang SUDAH diteruskan sekretariat. Kalau bidang boleh memutuskan
        // surat yang belum melewati meja pertama, tahap satu berhenti berarti
        // apa pun — dan diagram alurnya jadi hiasan.
        if ($row->status !== 'Ditinjau Bidang') {
            $this->session->set_flashdata('error', 'Pendaftaran ini tidak sedang menunggu tinjauan bidang (status: ' . html_escape($row->status) . ').');
            redirect('Kemitraan_Bidang');
            return;
        }

        // Jejaknya ditulis ke kolom TERSENDIRI, bukan menimpa reviewed_by —
        // pertanyaan "siapa yang meloloskan ini ke tahap dua" justru yang paling
        // sering ditanyakan ketika ada yang keliru.
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', [
            'status'             => $status,
            'catatan_bidang'     => trim((string) $this->input->post('catatan_admin', TRUE)),
            'reviewed_by_bidang' => $this->get_user_id(),
            'reviewed_at_bidang' => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'Keputusan bidang tersimpan.');
        redirect('Kemitraan_Bidang');
    }

    /** Ambil satu pendaftaran, hanya kalau bidang tujuannya adalah bidang saya. */
    private function baris_bidang_saya($id)
    {
        // Dicocokkan lewat KOLOM `bidang_kode`, bukan join nama divisi. Versi
        // sebelumnya bersandar pada `divisi.nama = divisi_atau_tema` — satu ganti
        // nama dan seluruh pendaftaran putus dari bidangnya, termasuk dari guard
        // ini, yang berarti bidang kehilangan akses ke berkasnya sendiri.
        return $this->db->get_where('kkn_magang_pendaftaran', [
            'id'          => (int) $id,
            'bidang_kode' => $this->my_bidang_kode,
        ])->row();
    }
}
