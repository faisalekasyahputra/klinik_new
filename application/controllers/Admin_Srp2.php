<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Srp2 extends Admin_Controller {

    /** Butir 7 putaran 2. Harus sama persis dengan ENUM migrasi 040. */
    const STATUS_SERTIFIKASI = ['belum_mendaftar', 'mendaftar', 'masih_proses', 'bersertifikat'];

    /**
     * Keadaan masa berlaku - DITURUNKAN, tidak pernah disimpan.
     *
     * Dinas minta penanda aktif/non-aktif dari masa berlaku sertifikat.
     * Menyimpannya berarti ada yang harus memperbaruinya tiap hari, dan yang
     * tidak diperbarui akan berkata "aktif" untuk sertifikat yang kedaluwarsa
     * kemarin - tanpa satu pun galat. Diturunkan berarti selalu benar.
     */
    public static function keadaan_berlaku($status, $berakhir) {
        if ($status !== 'bersertifikat')        { return ['belum', 'Belum bersertifikat']; }
        if (empty($berakhir))                   { return ['tak_tercatat', 'Masa berlaku belum tercatat']; }
        return $berakhir >= date('Y-m-d')
            ? ['aktif', 'Aktif']
            : ['kedaluwarsa', 'Non-aktif - masa berlaku habis'];
    }

    public function __construct() {
        parent::__construct();
        // upsert_direktori_publik() dipakai proses() - satu fungsi yang sama
        // dengan yang dipanggil saat pemohon mengubah data perusahaannya.
        $this->load->model('auth_model');
    }

    /**
     * Daftar ringkas direktori (permintaan pemilik produk 2 Okt 2026). Dulu tiap baris
     * adalah formulir sunting penuh; kini satu baris per perusahaan dengan tombol Ubah
     * ke halaman detail. Cari + urut + paginasi tetap server-side (B8).
     */
    public function index() {
        $data['title'] = 'Direktori SRP2'; // = label sidebar

        $table = $this->table_state(['nama_perusahaan', 'created_at', 'status_aktif', 'sertifikat_berakhir'], 'nama_perusahaan');
        $data['base_url'] = 'Admin_Srp2';

        // from() di depan, lalu count_all_results('', FALSE) - kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->select('c.id, c.nama_perusahaan, c.alamat_kantor, c.foto_profil, c.asosiasi, c.status_sertifikasi,'
                . ' c.sertifikat_terbit, c.sertifikat_berakhir, c.status_aktif, c.user_id, u.email AS akun_email, k.nama AS wilayah')
            ->from('srp2_certified_developers c')
            ->join('usr_users u', 'u.id = c.user_id', 'left')
            ->join('kabupaten k', 'k.id = c.kabupaten_id', 'left');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('c.nama_perusahaan', $table['q'])->or_like('c.alamat_kantor', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        // Kolom sort dari daftar putih table_state(), diberi alias karena usr_users juga punya created_at.
        $data['rows'] = $this->db->order_by('c.' . $table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_admin('admin/srp2/index', $data);
    }

    /** Formulir kosong untuk entri manual; satu view dengan halaman ubah. */
    public function tambah() {
        $this->render_admin('admin/srp2/ubah', [
            'title'     => 'Tambah Pengembang',
            'row'       => NULL,
            'kabupaten' => $this->db->select('id, nama')->order_by('nama', 'ASC')->get('kabupaten')->result(),
        ]);
    }

    /**
     * Detail/ubah satu entri direktori: profil, kontak, sertifikasi, dan akun pengembang
     * yang tertaut.
     *
     * NPWP dibuka HANYA di sini, untuk satu baris, dan aksesnya dicatat (poin 7.3).
     * Nilainya diisikan ke formulir supaya "Simpan" tidak menghapusnya diam-diam. Gagal
     * buka TIDAK disamarkan jadi kosong: kosong berarti "belum diisi", dan admin akan
     * mengetik ulang NPWP di atas data yang sebenarnya masih ada.
     */
    public function ubah($id = NULL) {
        if ( ! ctype_digit((string) $id)) { show_404(); }
        $row = $this->db->get_where('srp2_certified_developers', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        $row->npwp_plain = NULL;
        $row->npwp_rusak = FALSE;
        if ( ! empty($row->npwp_ciphertext)) {
            $this->load->library('encryption_lib');
            $this->catat_akses_data_pribadi('npwp_srp2', 'srp2_certified_developers', (string) (int) $id);
            $buka = $this->encryption_lib->decrypt($row->npwp_ciphertext);
            if ($buka === FALSE || $buka === NULL || $buka === '') { $row->npwp_rusak = TRUE; } else { $row->npwp_plain = $buka; }
        }

        $akun = NULL;
        if ($row->user_id) {
            $akun = $this->db->select('id, email, name, phone, status, active_session_at, password_changed_at, password_expires_at')
                ->get_where('usr_users', ['id' => (int) $row->user_id])->row();
        }

        $this->render_admin('admin/srp2/ubah', [
            'title'     => 'Ubah Pengembang',
            'row'       => $row,
            'akun'      => $akun,
            'kabupaten' => $this->db->select('id, nama')->order_by('nama', 'ASC')->get('kabupaten')->result(),
        ]);
    }

    /**
     * Daftar pengajuan SRP2 yang menunggu keputusan - menutup gap dari
     * docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md Fase 1 (sebelumnya tidak
     * ada alur admin sama sekali untuk srp2_registrations, lihat
     * docs/engineering/AUDIT_ROLE_PENGEMBANG.md Temuan #1).
     */
    public function pending() {
        $data['title'] = 'SRP2 dalam Pengajuan'; // = label sub-menu sidebar

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
        // belum diproses" tetap satu deklarasi - dipakai badge sidebar sekaligus
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

        // from() di depan, lalu count_all_results('', FALSE) - kalau tabelnya
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
        $this->catat_akses_data_pribadi('pengajuan_srp2', 'srp2_registrations', (string) (int) $id);   // poin 7.3
        // NIK pemohon terenkripsi sejak migrasi 067; dibuka hanya di detail yang aksesnya dicatat di atas.
        if ( ! empty($data['pendaftar']->nik_ktp_ciphertext)) {
            $this->load->library('encryption_lib');
            $data['pendaftar']->nik_ktp = $this->encryption_lib->decrypt($data['pendaftar']->nik_ktp_ciphertext) ?: NULL;
        }

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
     * Terima/tolak satu pengajuan - satu endpoint dengan field 'status',
     * pola yang sama persis dengan Admin_Kemitraan::proses() (bukan dua
     * method terima()/tolak() terpisah seperti draft awal PRD) supaya
     * komponen admin/components/review_form.php benar-benar dipakai ulang
     * tanpa modifikasi bentuk endpoint.
     *
     * Terima otomatis meng-upsert srp2_certified_developers berbasis
     * certified_developer_id (bukan insert baru tiap kali) - idempotent
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
        // - itu UI, bukan otorisasi. Roadmap T1a butir 6.
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
        // pemohon - tanpa alasan, dia tidak tahu apa yang harus diperbaiki.
        $catatan = trim((string) $this->input->post('catatan_admin', TRUE));
        if (in_array($status, ['Ditolak', 'Draft'], TRUE) && $catatan === '') {
            $pesan = $status === 'Ditolak'
                ? 'Catatan wajib diisi saat menolak pengajuan.'
                : 'Catatan wajib diisi - jelaskan apa yang harus diperbaiki pemohon.';
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
        // sehingga alasan "dulu diminta perbaikan karena X" hilang permanen -
        // dan tidak ada tabel log lain yang menyimpannya. Roadmap T1b butir 4.
        if ($status !== 'Diterima') { $update['catatan_admin'] = $catatan; }

        // Bentrok nama UNIQUE di direktori diperiksa SEBELUM transaksi: dulu baru ketahuan
        // lewat galat INSERT, yang di lingkungan db_debug hidup tampil sebagai SQL mentah
        // (HTTP 500) dan di production hanya ditebak lewat pesan umum (simulasi pengembang
        // 27 Sep 2026).
        if ($status === 'Diterima') {
            $bentrok = $this->db->where('nama_perusahaan', $reg->nama_perusahaan)
                ->where('id !=', (int) ($reg->certified_developer_id ?: 0))
                ->count_all_results('srp2_certified_developers');
            if ($bentrok > 0) {
                $this->session->set_flashdata('error', 'Pengajuan belum bisa diterima: nama perusahaan "' . $reg->nama_perusahaan
                    . '" sudah dipakai pengembang lain di direktori bersertifikat. Minta pemohon memperbaiki nama perusahaannya, atau rapikan baris direktori yang lama lebih dulu.');
                redirect('Admin_Srp2/detail/' . (int) $id);
                return;
            }
        }

        // SATU TRANSAKSI untuk seluruh keputusan. Sebelumnya tiga penulisan
        // berjalan lepas tanpa satu pun nilai balik diperiksa, sementara flash
        // sukses tetap disetel: nama bentrok UNIQUE di direktori membuat insert
        // gagal, insert_id() jadi 0, UPDATE registrasi ditolak FK, status tetap
        // Pending - dan admin membaca "Pengajuan diterima". Di production
        // db_debug mati sehingga seluruh rantai itu senyap. Melanggar §0d.
        // Pola trans_start/trans_complete/trans_status mengikuti User_model.php:36.
        $this->db->trans_start();

        if ($status === 'Diterima') {
            // Direktori publik srp2_certified_developers tetap tabel terpisah
            // (opsi b, docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md) -
            // link berbasis ID, bukan pencocokan nama string seperti sebelumnya.
            //
            // Lewat SATU fungsi upsert yang sama dengan yang dipakai saat pemohon
            // mengubah data perusahaannya - bukan dua salinan payload yang harus
            // diingat untuk diubah berbarengan.
            $cid = $this->auth_model->upsert_direktori_publik($reg);
            if ($cid) { $update['certified_developer_id'] = $cid; }
        } elseif ($status === 'Ditolak' && $reg->certified_developer_id) {
            // Ditolak setelah pernah Diterima: cabut dari direktori publik.
            // Dulu blok direktori hanya jalan untuk 'Diterima', sehingga
            // pengecualian yang SENGAJA dibuat untuk "Minta Perbaikan" ikut
            // menutupi cabang ini - perusahaan yang resmi ditolak tetap tampil
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
            log_message('error', 'Admin_Srp2::proses gagal - id=' . (int) $id . ' ' . $asal . '->' . $status . ' baris=' . $baris_terubah);
            $this->session->set_flashdata('error', 'Keputusan GAGAL disimpan dan sudah dibatalkan seluruhnya - tidak ada perubahan yang tersimpan. '
                . ($status === 'Diterima'
                    ? 'Penyebab paling sering: nama perusahaan "' . $reg->nama_perusahaan . '" sudah dipakai baris lain di direktori bersertifikat.'
                    : 'Coba lagi; bila terus gagal, laporkan beserta ID pengajuan ' . (int) $id . '.'));
            redirect('Admin_Srp2/detail/' . (int) $id);
            return;
        }

        $pesan_sukses = [
            'Diterima' => 'Pengajuan diterima - pengembang masuk direktori publik.',
            'Ditolak'  => 'Pengajuan ditolak, catatan sudah dikirim ke pengembang.'
                . ($reg->certified_developer_id ? ' Pengembang juga dicabut dari direktori publik.' : ''),
            'Draft'    => 'Pengajuan dibuka kembali untuk diperbaiki. Pengembang melihat catatan Anda di dashboardnya.',
        ];
        $this->catat_audit('srp2_keputusan', 'Keputusan SRP2 ' . $reg->nama_perusahaan . ': ' . $asal . ' -> ' . $status,
            'srp2_registrations', (string) (int) $id, [
                'status_lama' => $asal, 'status_baru' => $status,
                'catatan_lama' => $reg->catatan_admin, 'catatan_baru' => $status !== 'Diterima' ? $catatan : NULL,
            ]);
        $this->session->set_flashdata('success', $pesan_sukses[$status]);
        redirect('Admin_Srp2/pending');
    }

    /**
     * Sajikan satu dokumen SRP2 ke admin. Endpoint ber-guard (Admin_Controller),
     * berkas dibaca dari penyimpanan privat - tidak pernah lewat path publik.
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

    /**
     * Simpan entri direktori (id kosong = tambah). Dipakai halaman tambah dan ubah.
     *
     * HANYA medan yang BENAR-BENAR DIKIRIM yang masuk payload: medan tidak dikirim -> kolom
     * tidak disentuh, medan dikirim kosong -> kolom dikosongkan. Dulu payload selalu penuh
     * dan formulir yang tidak memuat `sosmed_lainnya` menge-NULL-kannya diam-diam (5 Agt
     * 2026); tanggal sertifikat akan bernasib sama. `status_aktif` pengecualian: checkbox
     * yang tidak dicentang memang tidak terkirim.
     */
    public function save() {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $id = (int) $this->input->post('id');
        $kembali = $id ? 'Admin_Srp2/ubah/' . $id : 'Admin_Srp2/tambah';
        $gagal = function ($pesan, $tujuan = NULL) use ($kembali) {
            $this->session->set_flashdata('error', $pesan);
            redirect($tujuan ?: $kembali);
        };

        $name = strtoupper(trim((string) $this->input->post('nama_perusahaan', TRUE)));
        if ($name === '' || strlen($name) > 180) { $gagal('Nama perusahaan wajib diisi (maksimal 180 karakter).'); return; }
        // Pola Admin_Magang_Posisi::simpan(): UPDATE ke id yang tidak ada menyentuh nol baris tanpa galat.
        $lama = $id ? $this->db->get_where('srp2_certified_developers', ['id' => $id])->row() : NULL;
        if ($id && ! $lama) { $gagal('Pengembang tidak ditemukan.', 'Admin_Srp2'); return; }
        // Nama UNIQUE: diperiksa lebih dulu supaya pesannya jelas, bukan menebak dari galat INSERT.
        if ($this->db->where('nama_perusahaan', $name)->where('id !=', $id)->count_all_results('srp2_certified_developers')) {
            $gagal('Nama perusahaan "' . $name . '" sudah dipakai baris lain di direktori.'); return;
        }

        $payload = [
            'nama_perusahaan' => $name,
            'status_aktif'    => $this->input->post('status_aktif') ? 1 : 0,
        ];

        // Medan profil & kontak: aturan bersama dengan Profil Perusahaan dan Profil Saya.
        $this->load->helper('srp2');
        $masukan = [];
        foreach (['alamat_kantor', 'website', 'instagram', 'sosmed_lainnya', 'nib', 'no_keanggotaan', 'no_whatsapp', 'email_kontak'] as $k) {
            if ($this->input->post($k) !== NULL) { $masukan[$k] = $this->input->post($k, TRUE); }
        }
        [$kontak, $galat] = srp2_bersihkan_profil($masukan);
        if ($galat !== NULL) { $gagal($galat); return; }
        $payload += $kontak;

        /* Masa berlaku (butir B1). Kosong -> NULL, bukan '': MariaDB tanpa STRICT
           mendaratkan '' pada kolom DATE sebagai '0000-00-00' tanpa galat. */
        $tanggal = [];
        foreach (['sertifikat_terbit', 'sertifikat_berakhir'] as $field) {
            if ($this->input->post($field) === NULL) { continue; }
            $v = trim((string) $this->input->post($field, TRUE));
            if ($v === '') { $payload[$field] = NULL; continue; }
            $d = DateTime::createFromFormat('!Y-m-d', $v);
            if ( ! $d || $d->format('Y-m-d') !== $v) { $gagal('Tanggal sertifikat harus berformat YYYY-MM-DD.'); return; }
            $payload[$field] = $tanggal[$field] = $v;
        }
        if ( ! empty($tanggal['sertifikat_terbit']) && ! empty($tanggal['sertifikat_berakhir'])
            && $tanggal['sertifikat_terbit'] > $tanggal['sertifikat_berakhir']) {
            $gagal('Tanggal terbit tidak boleh melewati tanggal akhir masa berlaku.'); return;
        }

        // Butir 7: status bertingkat - daftar tertutup, nilai di luar daftar DITOLAK.
        if ($this->input->post('status_sertifikasi') !== NULL) {
            $s = (string) $this->input->post('status_sertifikasi', TRUE);
            if ( ! in_array($s, self::STATUS_SERTIFIKASI, TRUE)) { $gagal('Status sertifikasi tidak dikenal.'); return; }
            $payload['status_sertifikasi'] = $s;
        }

        // Butir 7: kabupaten - divalidasi ke TABEL, bukan sekadar angka.
        if ($this->input->post('kabupaten_id') !== NULL) {
            $kab = (int) $this->input->post('kabupaten_id');
            if ($kab === 0) {
                $payload['kabupaten_id'] = NULL;
            } elseif ($this->db->where('id', $kab)->count_all_results('kabupaten')) {
                $payload['kabupaten_id'] = $kab;
            } else {
                $gagal('Kabupaten/kota tidak dikenal.'); return;
            }
        }

        // Butir 12: asosiasi - daftar tertutup srp2_daftar_asosiasi(), sama dengan formulir pengembang.
        if ($this->input->post('asosiasi') !== NULL) {
            $a = trim((string) $this->input->post('asosiasi', TRUE));
            if ($a === '') {
                $payload['asosiasi'] = NULL;
            } elseif (array_key_exists($a, srp2_daftar_asosiasi())) {
                $payload['asosiasi'] = $a;
            } else {
                $gagal('Asosiasi tidak dikenal.'); return;
            }
        }

        /* Butir 8: NPWP diperlakukan seperti NIK warga - nilai asli dienkripsi, keunikan
           lewat sidik deterministik atas ANGKA saja (titik/strip berbeda-beda tulisannya). */
        if ($this->input->post('npwp') !== NULL) {
            $npwp_mentah = preg_replace('/\D+/', '', (string) $this->input->post('npwp', TRUE));
            if ($npwp_mentah === '') {
                $payload['npwp_ciphertext']  = NULL;
                $payload['npwp_lookup_hash'] = NULL;
            } elseif (strlen($npwp_mentah) < 15 || strlen($npwp_mentah) > 16) {
                $gagal('NPWP harus 15 atau 16 digit angka.'); return;
            } else {
                $this->load->library('encryption_lib');
                $payload['npwp_ciphertext']  = $this->encryption_lib->encrypt($npwp_mentah);
                $payload['npwp_lookup_hash'] = $this->encryption_lib->deterministic_hash($npwp_mentah);
            }
        }

        // Foto paling akhir: berkas baru mendarat sesudah semua isian lolos validasi.
        $foto = $this->simpan_foto_srp2('foto_profil', $galat);
        if ($galat !== NULL) { $gagal($galat); return; }
        if ($foto !== NULL) { $payload['foto_profil'] = $foto; }

        // Hasil query DIPERIKSA: di production db_debug mati, jadi gagal hanya berupa FALSE.
        $this->db->trans_start();
        if ($id) {
            $this->db->where('id', $id)->update('srp2_certified_developers', $payload);
        } else {
            $this->db->insert('srp2_certified_developers', $payload);
            $id = (int) $this->db->insert_id();
        }
        $this->auth_model->sinkron_pengajuan_dari_direktori($id);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE || ! $id) {
            if ($foto !== NULL) { $this->hapus_foto_srp2($foto); }
            log_message('error', 'Admin_Srp2::save gagal - id=' . $id . ' nama=' . $name);
            $gagal('Gagal menyimpan: nama perusahaan atau NPWP kemungkinan sudah dipakai baris lain di direktori. Tidak ada perubahan yang tersimpan.');
            return;
        }
        if ($foto !== NULL && $lama) { $this->hapus_foto_srp2($lama->foto_profil); }

        // NPWP tidak ikut ke detail audit; cukup nama kolom yang dikirim.
        $this->catat_audit($lama ? 'srp2_direktori_diubah' : 'srp2_direktori_ditambah',
            'Direktori pengembang "' . $name . '" ' . ($lama ? 'diperbarui' : 'ditambahkan'),
            'srp2_certified_developers', (string) $id, ['kolom' => array_keys($payload)]);
        $this->session->set_flashdata('success', 'Daftar pengembang diperbarui.');
        redirect('Admin_Srp2/ubah/' . $id);
    }

    public function delete($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || ! ctype_digit((string) $id)) { show_404(); }
        $row = $this->db->select('nama_perusahaan, foto_profil')->get_where('srp2_certified_developers', ['id' => (int) $id])->row();
        // DELETE yang tidak cocok baris mana pun tetap mengembalikan TRUE, jadi
        // `affected_rows()` yang membedakan "terhapus" dari "id-nya memang tidak ada".
        if ( ! $this->db->where('id', (int) $id)->delete('srp2_certified_developers')
            || $this->db->affected_rows() !== 1) {
            $this->session->set_flashdata('error', 'Pengembang tidak ditemukan atau sudah dihapus.');
            redirect('Admin_Srp2'); return;
        }
        $this->hapus_foto_srp2($row->foto_profil ?? '');
        $this->catat_audit('srp2_direktori_dihapus', 'Direktori pengembang "' . ($row->nama_perusahaan ?? '') . '" dihapus',
            'srp2_certified_developers', (string) (int) $id);
        $this->session->set_flashdata('success', 'Pengembang dihapus dari daftar.'); redirect('Admin_Srp2');
    }

    // =====================================================================
    // AKUN PENGEMBANG TERTAUT (keputusan pemilik produk 2 Okt 2026): dinas membuatkan akun
    // untuk perusahaan yang diisi manual, supaya perusahaan memperbarui datanya sendiri
    // lewat Profil Perusahaan. Semua endpoint POST + CSRF global + batas laju global.
    // =====================================================================

    /** Baris direktori sasaran dari URL; 404 bila tidak ada atau bukan POST. */
    private function entri_sasaran($id) {
        if ($this->input->method(TRUE) !== 'POST' || ! ctype_digit((string) $id)) { show_404(); }
        $row = $this->db->get_where('srp2_certified_developers', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }
        return $row;
    }

    /**
     * Sandi dari formulir, atau sandi acak bila dikosongkan: 72 bit acak + akhiran yang
     * menjamin aturan sandi_kuat (huruf besar, angka, simbol).
     * @return array [sandi|NULL bila tidak kuat, dibangkitkan?]
     */
    private function sandi_dari_formulir() {
        $sandi = (string) $this->input->post('password');
        if ($sandi === '') { return [strtr(base64_encode(random_bytes(9)), '+/', 'Kx') . '-7Q', TRUE]; }
        $this->load->library('form_validation');
        return [$this->form_validation->sandi_kuat($sandi) ? $sandi : NULL, FALSE];
    }

    /**
     * "Buatkan akun": akun pengembang aktif yang onboarding-nya sudah lengkap, pengajuan
     * SRP2 Diterima yang menunjuk baris ini (supaya dashboard pengembang mengenalinya
     * sebagai bersertifikat), lalu baris direktori ditautkan. Satu transaksi.
     */
    public function buat_akun($id = NULL) {
        $row = $this->entri_sasaran($id);
        $kembali = 'Admin_Srp2/ubah/' . (int) $row->id;
        $gagal = function ($pesan) use ($kembali) { $this->session->set_flashdata('error', 'Akun belum dibuat: ' . $pesan); redirect($kembali); };

        if ($row->user_id) { $gagal('entri ini sudah tertaut ke akun lain. Lepas tautannya dulu bila ingin mengganti akun.'); return; }
        if (strlen($row->nama_perusahaan) > 150) { $gagal('nama perusahaan lebih dari 150 karakter, sedangkan data pengajuan SRP2 hanya menampung 150.'); return; }

        $email = strtolower(trim((string) $this->input->post('email', TRUE)));
        if ($email === '' || strlen($email) > 100 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) { $gagal('email tidak valid.'); return; }
        if ($this->db->where('email', $email)->count_all_results('usr_users')) { $gagal('email tersebut sudah terdaftar.'); return; }
        $nama_pj = trim((string) $this->input->post('nama_pj', TRUE));
        if (mb_strlen($nama_pj) > 150) { $gagal('nama penanggung jawab maksimal 150 karakter.'); return; }
        $this->load->helper('srp2');
        [$wa, $galat] = srp2_bersihkan_profil(['no_whatsapp' => $this->input->post('no_whatsapp', TRUE)]);
        if ($galat !== NULL) { $gagal($galat); return; }
        $wa = $wa['no_whatsapp'];
        [$sandi, $dibangkitkan] = $this->sandi_dari_formulir();
        if ($sandi === NULL) { $gagal('sandi awal harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol (atau kosongkan supaya dibuatkan otomatis).'); return; }

        // NPWP & NIB juga UNIQUE di pengajuan. NPWP yang sudah dipakai pengajuan lain berarti
        // perusahaan ini sudah punya akun sendiri: tautkan akun itu, jangan buat yang kedua.
        if ( ! empty($row->npwp_lookup_hash)
            && $this->db->where('npwp_lookup_hash', $row->npwp_lookup_hash)->count_all_results('srp2_registrations')) {
            $gagal('NPWP perusahaan ini sudah dipakai pengajuan SRP2 akun lain, kemungkinan perusahaan ini sudah mendaftar sendiri.'); return;
        }
        $nib = $row->nib && ! $this->db->where('nib', $row->nib)->count_all_results('srp2_registrations') ? $row->nib : NULL;

        $sekarang = date('Y-m-d H:i:s');
        $this->db->trans_begin();
        $this->db->insert('usr_users', [
            'name'              => $nama_pj !== '' ? $nama_pj : $row->nama_perusahaan,
            'username'          => $this->auth_model->generate_unique_username(strstr($email, '@', TRUE)),
            'email'             => $email,
            'password'          => password_hash($sandi, PASSWORD_BCRYPT),
            'role'              => 'pengembang',
            'kategori'          => 'pengembang',
            'status'            => 'active',
            'profile_completed' => 1,
            'email_verified_at' => $sekarang,
            'created_at'        => $sekarang,
            'phone'             => $wa,
        // Data perusahaan tetap di baris direktori ini (migrasi 070), tidak disalin ke akun.
        // Sandi awal diketahui admin, jadi wajib diganti di login pertama (keputusan 29 Sep 2026).
        ] + $this->auth_model->password_awal_fields());
        $uid = (int) $this->db->insert_id();
        $this->db->insert('srp2_registrations', [
            'user_id'                => $uid ?: NULL,
            'nama_peserta'           => $nama_pj !== '' ? $nama_pj : NULL,
            'no_whatsapp'            => $wa,
            'email'                  => $email,
            'nama_perusahaan'        => $row->nama_perusahaan,
            'nib'                    => $nib,
            'asosiasi'               => $row->asosiasi,
            'no_keanggotaan'         => $row->no_keanggotaan,
            'alamat_kantor'          => $row->alamat_kantor,
            'instagram'              => $row->instagram,
            'website'                => $row->website,
            'sosmed_lainnya'         => $row->sosmed_lainnya,
            'npwp_ciphertext'        => $row->npwp_ciphertext,
            'npwp_lookup_hash'       => $row->npwp_lookup_hash,
            'status_verifikasi'      => 'Diterima',
            'reviewed_by'            => $this->get_user_id(),
            'reviewed_at'            => $sekarang,
            'certified_developer_id' => (int) $row->id,
        ]);
        // user_id IS NULL ikut di WHERE: admin lain yang menautkan lebih dulu membuat ini nol baris.
        $this->db->where('id', (int) $row->id)->where('user_id IS NULL', NULL, FALSE)
            ->update('srp2_certified_developers', ['user_id' => $uid]);
        $tertaut = $this->db->affected_rows() === 1;

        if ( ! $uid || ! $tertaut || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            log_message('error', 'Admin_Srp2::buat_akun gagal - entri=' . (int) $row->id);
            $gagal('penyimpanan gagal dan sudah dibatalkan seluruhnya. Coba lagi.');
            return;
        }
        $this->db->trans_commit();

        // Sandinya TIDAK pernah masuk audit.
        $this->catat_audit('srp2_akun_dibuat', 'Membuatkan akun pengembang ' . $email . ' untuk "' . $row->nama_perusahaan . '"',
            'srp2_certified_developers', (string) (int) $row->id, ['user_id' => $uid, 'sandi_dibangkitkan' => $dibangkitkan]);
        $this->session->set_flashdata('success', 'Akun pengembang ' . $email . ' dibuat dan ditautkan. Serahkan email dan sandinya lewat jalur pribadi; sandi itu wajib diganti saat pertama masuk.');
        // Sandi acak ditampilkan SEKALI (flash) karena admin belum mengetahuinya.
        if ($dibangkitkan) { $this->session->set_flashdata('sandi_awal', $sandi); }
        redirect($kembali);
    }

    /** Reset sandi akun tertaut, pola Admin_Users::reset_sandi (wajib ganti, sesi diakhiri). */
    public function reset_sandi_akun($id = NULL) {
        $row = $this->entri_sasaran($id);
        $kembali = 'Admin_Srp2/ubah/' . (int) $row->id;
        $user = $row->user_id ? $this->db->get_where('usr_users', ['id' => (int) $row->user_id, 'role' => 'pengembang'])->row() : NULL;
        if ( ! $user) { $this->session->set_flashdata('error', 'Entri ini tidak tertaut ke akun pengembang.'); redirect($kembali); return; }

        [$sandi, $dibangkitkan] = $this->sandi_dari_formulir();
        if ($sandi === NULL) {
            $this->session->set_flashdata('error', 'Sandi baru harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol (atau kosongkan supaya dibuatkan otomatis).');
            redirect($kembali); return;
        }
        $this->db->where('id', (int) $user->id)->update('usr_users', [
            'password' => password_hash($sandi, PASSWORD_BCRYPT),
            'login_attempts' => 0, 'locked_until' => NULL,
            'active_session_hash' => NULL, 'active_session_id_hash' => NULL, 'active_session_at' => NULL,
        ] + $this->auth_model->password_awal_fields());

        $this->catat_audit('srp2_akun_sandi_direset', 'Mereset sandi akun pengembang ' . $user->email,
            'usr_users', (string) $user->id, ['entri_direktori' => (int) $row->id]);
        $this->session->set_flashdata('success', 'Sandi ' . $user->email . ' diganti dan sesinya diakhiri. Sampaikan lewat jalur pribadi; sandi itu wajib diganti saat masuk.');
        if ($dibangkitkan) { $this->session->set_flashdata('sandi_awal', $sandi); }
        redirect($kembali);
    }

    /**
     * Lepas tautan: baris direktori tidak lagi dimiliki akun itu. Akunnya TIDAK dihapus.
     * Pengajuan akun itu yang menunjuk baris ini ikut dilepas (certified_developer_id NULL);
     * kalau tidak, Profil Saya akun itu tetap menyalin datanya ke baris ini lewat
     * upsert_direktori_publik().
     */
    public function lepas_akun($id = NULL) {
        $row = $this->entri_sasaran($id);
        $kembali = 'Admin_Srp2/ubah/' . (int) $row->id;
        if ( ! $row->user_id) { $this->session->set_flashdata('error', 'Entri ini tidak tertaut ke akun mana pun.'); redirect($kembali); return; }
        $email = (string) $this->db->select('email')->get_where('usr_users', ['id' => (int) $row->user_id])->row('email');

        $this->db->trans_start();
        $this->db->where('id', (int) $row->id)->update('srp2_certified_developers', ['user_id' => NULL]);
        $this->db->where('certified_developer_id', (int) $row->id)->where('user_id', (int) $row->user_id)
            ->update('srp2_registrations', ['certified_developer_id' => NULL]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Tautan gagal dilepas. Coba lagi.'); redirect($kembali); return;
        }
        $this->catat_audit('srp2_akun_dilepas', 'Melepas tautan akun ' . $email . ' dari "' . $row->nama_perusahaan . '"',
            'srp2_certified_developers', (string) (int) $row->id, ['user_id' => (int) $row->user_id]);
        $this->session->set_flashdata('success', 'Tautan akun ' . $email . ' dilepas. Akunnya tetap ada, tetapi tidak bisa lagi mengubah entri ini.');
        redirect($kembali);
    }
}
