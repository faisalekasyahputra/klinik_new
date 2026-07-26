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

        // Status = FILTER dengan nilai default, BUKAN klausa WHERE mati. Dulu
        // where('status_verifikasi','Pending') dipaku di sini, sehingga setiap
        // pengajuan yang sudah diputuskan lenyap dari jangkauan admin: tidak ada
        // cara memantau siapa yang diminta perbaikan tapi tak kunjung mengirim
        // ulang, dan satu-satunya jalan membukanya lagi adalah menebak URL
        // detail/<id>. Roadmap T1b butir 1.
        //
        // Nilai defaultnya dibaca dari registry (pending_where) supaya "apa arti
        // belum diproses" tetap satu deklarasi — dipakai badge sidebar sekaligus
        // query ini, tidak lagi ditulis ulang literalnya di dua tempat.
        $modul = $this->config->item('dashboard_modules')['srp2_verifikasi'] ?? [];
        $status_default = $modul['pending_where']['status_verifikasi'] ?? 'Pending';

        $status_pilihan = ['Pending', 'Draft', 'Diterima', 'Ditolak'];
        $status_filter  = (string) $this->input->get('status');
        if ( ! in_array($status_filter, $status_pilihan, TRUE) && $status_filter !== 'semua') {
            $status_filter = $status_default;
        }
        $data['status_filter']  = $status_filter;
        $data['status_pilihan'] = $status_pilihan;

        // from() di depan, lalu count_all_results('', FALSE) — kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->from('srp2_registrations');
        if ($status_filter !== 'semua') { $this->db->where('status_verifikasi', $status_filter); }
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

        // 'Draft' = aksi "Minta Perbaikan": membuka kembali pengajuan supaya
        // pemohon bisa memperbaiki dokumennya, TANPA mencap "Ditolak" di
        // riwayatnya. Memakai status yang sudah ada (Draft = bisa diedit, belum
        // dikirim) sehingga tidak perlu status/migrasi baru.
        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak', 'Draft'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $reg = $this->db->get_where('srp2_registrations', ['id' => (int) $id])->row();
        if ( ! $reg) { show_404(); }

        // Transisi status ditegakkan di SERVER, bukan di view. Sebelumnya proses()
        // membaca $reg lalu tidak pernah menguji status lamanya: satu POST rakitan
        // bisa menerbitkan Draft berisi 0 dokumen ke direktori publik lengkap
        // dengan reviewed_by. Satu-satunya penjaga adalah kondisi if di detail.php
        // — itu UI, bukan otorisasi. Roadmap T1a butir 6.
        $transisi_sah = [
            'Pending'  => ['Diterima', 'Ditolak', 'Draft'],
            'Diterima' => ['Draft', 'Ditolak'],
            'Ditolak'  => ['Draft'],
            'Draft'    => [],
        ];
        $asal = (string) $reg->status_verifikasi;
        if ( ! in_array($status, $transisi_sah[$asal] ?? [], TRUE)) {
            $this->session->set_flashdata('error', 'Pengajuan berstatus "' . $asal . '" tidak bisa diubah menjadi "' . $status . '".');
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        // Catatan wajib untuk kedua keputusan yang mengembalikan pekerjaan ke
        // pemohon — tanpa alasan, dia tidak tahu apa yang harus diperbaiki.
        $catatan = trim((string) $this->input->post('catatan_admin', TRUE));
        if (in_array($status, ['Ditolak', 'Draft'], TRUE) && $catatan === '') {
            $pesan = $status === 'Ditolak'
                ? 'Catatan wajib diisi saat menolak pengajuan.'
                : 'Catatan wajib diisi — jelaskan apa yang harus diperbaiki pemohon.';
            $this->session->set_flashdata('error', $pesan);
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $update = [
            'status_verifikasi' => $status,
            'reviewed_by'       => $this->get_user_id(),
            'reviewed_at'       => date('Y-m-d H:i:s'),
        ];
        // catatan_admin hanya ditulis untuk keputusan yang MEMBAWA catatan.
        // Saat Diterima, kolomnya sengaja TIDAK disentuh: dulu di-NULL-kan,
        // sehingga alasan "dulu diminta perbaikan karena X" hilang permanen —
        // dan tidak ada tabel log lain yang menyimpannya. Roadmap T1b butir 4.
        if ($status !== 'Diterima') { $update['catatan_admin'] = $catatan; }

        // SATU TRANSAKSI untuk seluruh keputusan. Sebelumnya tiga penulisan
        // berjalan lepas tanpa satu pun nilai balik diperiksa, sementara flash
        // sukses tetap disetel: nama bentrok UNIQUE di direktori membuat insert
        // gagal, insert_id() jadi 0, UPDATE registrasi ditolak FK, status tetap
        // Pending — dan admin membaca "Pengajuan diterima". Di production
        // db_debug mati sehingga seluruh rantai itu senyap. Melanggar §0d.
        // Pola trans_start/trans_complete/trans_status mengikuti User_model.php:36.
        $this->db->trans_start();

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
        } elseif ($status === 'Ditolak' && $reg->certified_developer_id) {
            // Ditolak setelah pernah Diterima: cabut dari direktori publik.
            // Dulu blok direktori hanya jalan untuk 'Diterima', sehingga
            // pengecualian yang SENGAJA dibuat untuk "Minta Perbaikan" ikut
            // menutupi cabang ini — perusahaan yang resmi ditolak tetap tampil
            // "Bersertifikat" di halaman publik. Dipisah eksplisit di kode,
            // bukan diandalkan pada komentar. Roadmap T1a butir 7.
            $this->db->where('id', $reg->certified_developer_id)
                ->update('srp2_certified_developers', ['status_aktif' => 0]);
        }
        // 'Draft' (Minta Perbaikan) sengaja TIDAK menyentuh direktori: pengembangnya
        // tetap tersertifikasi, yang diperbaiki cuma kelengkapan dokumen. Pencabutan
        // dari daftar publik tetap keputusan terpisah lewat halaman Direktori SRP2.

        // Status asal ikut di WHERE: dengan begitu affected_rows() === 0 TIDAK
        // ambigu lagi (asal selalu != tujuan karena transisi sudah divalidasi),
        // jadi 0 baris pasti berarti gagal atau kalah balapan dengan admin lain.
        $this->db->where('id', (int) $id)
            ->where('status_verifikasi', $asal)
            ->update('srp2_registrations', $update);
        $baris_terubah = $this->db->affected_rows();

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE || $baris_terubah === 0) {
            log_message('error', 'Admin_Srp2::proses gagal — id=' . (int) $id . ' ' . $asal . '->' . $status . ' baris=' . $baris_terubah);
            $this->session->set_flashdata('error', 'Keputusan GAGAL disimpan dan sudah dibatalkan seluruhnya — tidak ada perubahan yang tersimpan. '
                . ($status === 'Diterima'
                    ? 'Penyebab paling sering: nama perusahaan "' . $reg->nama_perusahaan . '" sudah dipakai baris lain di direktori bersertifikat.'
                    : 'Coba lagi; bila terus gagal, laporkan beserta ID pengajuan ' . (int) $id . '.'));
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $pesan_sukses = [
            'Diterima' => 'Pengajuan diterima — pengembang masuk direktori publik.',
            'Ditolak'  => 'Pengajuan ditolak, catatan sudah dikirim ke pengembang.'
                . ($reg->certified_developer_id ? ' Pengembang juga dicabut dari direktori publik.' : ''),
            'Draft'    => 'Pengajuan dibuka kembali untuk diperbaiki. Pengembang melihat catatan Anda di dashboardnya.',
        ];
        $this->session->set_flashdata('success', $pesan_sukses[$status]);
        redirect('Admin_Srp2/pending');
    }

    /**
     * Sajikan satu dokumen SRP2 ke admin. Endpoint ber-guard (Admin_Controller),
     * berkas dibaca dari penyimpanan privat — tidak pernah lewat path publik.
     * Menutup PRD FR-11.
     */
    public function lihat_dokumen($id = NULL, $document_key = NULL) {
        if ( ! is_numeric($id) || empty($document_key)) { show_404(); }

        $doc = $this->db->where(['registration_id' => (int) $id, 'document_key' => $document_key])
            ->get('srp2_documents')->row();
        if ( ! $doc) { show_404(); }

        // Lewat serve_private_file() supaya lokasi akar & pengamanan nama file
        // (basename) ikut satu jalur dengan endpoint berkas lainnya.
        $this->serve_private_file('srp2', (int) $id, $doc->stored_name, $doc->mime_type);
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

        // Hasil query DIPERIKSA, bukan diasumsikan — pola sukses karangan yang
        // sama seperti proses() sebelum T1a. Kolom nama ber-UNIQUE, jadi bentrok
        // adalah kegagalan yang paling mungkin terjadi; di production db_debug
        // mati sehingga insert/update gagal hanya mengembalikan FALSE dan alur
        // berjalan terus sampai menyetel "Daftar pengembang diperbarui".
        $ok = $id
            ? $this->db->where('id', $id)->update('srp2_certified_developers', $payload)
            : $this->db->insert('srp2_certified_developers', $payload);

        if ($ok === FALSE) {
            log_message('error', 'Admin_Srp2::save gagal — id=' . $id . ' nama=' . $name);
            $this->session->set_flashdata('error', 'Gagal menyimpan: nama perusahaan "' . $name . '" kemungkinan sudah dipakai baris lain di direktori. Tidak ada perubahan yang tersimpan.');
            redirect('Admin_Srp2'); return;
        }
        $this->session->set_flashdata('success', 'Daftar pengembang diperbarui.'); redirect('Admin_Srp2');
    }

    public function delete($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || !is_numeric($id)) { show_404(); }
        $this->db->where('id', (int) $id)->delete('srp2_certified_developers');
        $this->session->set_flashdata('success', 'Pengembang dihapus dari daftar.'); redirect('Admin_Srp2');
    }
}
