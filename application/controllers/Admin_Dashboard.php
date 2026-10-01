<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {

    /**
     * Rincian kartu per modul registry (config/dashboard_modules.php). Baris
     * pertama = angka utama kartu. Setiap `where` adalah aturan yang SAMA dengan
     * filter di layar tujuan `url`, supaya angka di kartu = jumlah baris yang
     * muncul saat angkanya diklik. String = kondisi mentah (tanpa escape).
     */
    private const KARTU = [
        'validasi_antrean' => ['label' => 'Antrean Perumahan', 'lihat' => 'Lihat antrean', 'url' => 'Admin', 'table' => 'sf_housing_queue', 'rincian' => [
            ['Menunggu', ['status_antrean' => 'pending'], 'Admin?status=pending'],
            ['Perlu perbaikan', ['status_antrean' => 'needs_revision'], 'Admin?status=needs_revision'],
            ['Disetujui', ['status_antrean' => 'approved'], 'Admin?status=approved'],
            ['Ditolak', ['status_antrean' => 'rejected'], 'Admin?status=rejected'],
        ]],
        'srp2_verifikasi' => ['label' => 'Sertifikasi SRP2', 'lihat' => 'Lihat pengajuan', 'url' => 'Admin_Srp2/pending', 'table' => 'srp2_registrations', 'rincian' => [
            ['Menunggu', ['status_verifikasi' => 'Pending'], 'Admin_Srp2/pending?status=Pending'],
            ['Diminta perbaikan', ['status_verifikasi' => 'Draft'], 'Admin_Srp2/pending?status=Draft'],
            // NULL: dihitung lewat Admin_Srp2::keadaan_berlaku(), lihat srp2_aktif().
            ['Bersertifikat aktif', NULL, 'Admin_Srp2'],
        ]],
        'aduan_semua' => ['label' => 'Aduan Warga', 'lihat' => 'Lihat aduan', 'url' => 'Admin_Aduan', 'table' => 'aduan', 'rincian' => [
            ['Belum diteruskan', 'bidang IS NULL', 'Admin_Aduan?bidang=belum'],
            ['Baru', ['status' => 'Baru'], 'Admin_Aduan?status=Baru'],
            ['Diproses', ['status' => 'Diproses'], 'Admin_Aduan?status=Diproses'],
            ['Selesai', ['status' => 'Selesai'], 'Admin_Aduan?status=Selesai'],
        ]],
        'kemitraan' => ['label' => 'KKN & Magang', 'lihat' => 'Lihat pendaftaran', 'url' => 'Admin_Kemitraan', 'table' => 'kkn_magang_pendaftaran', 'rincian' => [
            ['Diajukan', ['status' => 'Diajukan'], 'Admin_Kemitraan?status=Diajukan'],
            ['Ditinjau bidang', ['status' => 'Ditinjau Bidang'], 'Admin_Kemitraan?status=Ditinjau+Bidang'],
            ['Diterima', ['status' => 'Diterima'], 'Admin_Kemitraan?status=Diterima'],
        ]],
        'konsultasi_janji' => ['label' => 'Janji Temu', 'lihat' => 'Lihat janji temu', 'url' => 'Admin_Konsultasi', 'table' => 'forum_janji_temu', 'rincian' => [
            ['Diajukan', ['status' => 'diajukan'], 'Admin_Konsultasi?status=diajukan'],
            ['Ditawarkan', ['status' => 'ditawarkan'], 'Admin_Konsultasi?status=ditawarkan'],
            ['Disetujui', ['status' => 'disetujui'], 'Admin_Konsultasi?status=disetujui'],
        ]],
    ];

    public function __construct() {
        parent::__construct();
    }

    /**
     * Meja kerja superadmin. Semua angka di sini hasil query nyata; kalau suatu
     * metrik belum bisa dihitung, jangan diisi angka karangan.
     */
    public function index() {
        $data['title'] = 'Ringkasan Kerja'; // = label sidebar (dashboard_modules.php)

        // Kartu hanya untuk modul registry yang tampil bagi role aktif (enabled,
        // roles, scope), sama dengan sidebar, agar modul mati tidak muncul di sini.
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $modul_ada = $this->config->item('dashboard_modules') ?: [];
        $role = $this->current_role();
        $tampil = function ($key) use ($modul_ada, $role) {
            $m = $modul_ada[$key] ?? NULL;
            if ( ! $m || (array_key_exists('enabled', $m) && $m['enabled'] === FALSE)) { return FALSE; }
            if (empty($m['roles']) || ! in_array($role, $m['roles'], TRUE)) { return FALSE; }
            return empty($m['scope']) || ! empty($this->session->userdata($m['scope']));
        };

        $data['kartu_domain'] = [];
        foreach (self::KARTU as $key => $k) {
            if ( ! $tampil($key)) { continue; }
            $rincian = [];
            foreach ($k['rincian'] as [$label, $where, $url]) {
                if ($where === NULL) { $n = $this->srp2_aktif(); }
                elseif (is_string($where)) { $n = (int) $this->db->where($where, NULL, FALSE)->count_all_results($k['table']); }
                else { $n = (int) $this->db->where($where)->count_all_results($k['table']); }
                $rincian[] = ['label' => $label, 'n' => $n, 'url' => $url];
            }
            $data['kartu_domain'][] = [
                'label' => $k['label'], 'icon' => $modul_ada[$key]['icon'], 'url' => $k['url'],
                'lihat' => $k['lihat'], 'utama' => array_shift($rincian), 'rincian' => $rincian,
            ];
        }
        $data['rekam'] = $tampil('rekam_pantau') ? $this->ringkas_rekam_data() : NULL;

        // Akun per peran. Staf = ketiga peran admin; akun tanpa peran tidak dihitung.
        $per_peran = array_column($this->db->select('role, COUNT(*) AS n', FALSE)
            ->group_by('role')->get('usr_users')->result_array(), 'n', 'role');
        $data['akun_peran'] = [
            'Warga'       => (int) ($per_peran['warga'] ?? 0),
            'Pengembang'  => (int) ($per_peran['pengembang'] ?? 0),
            'Mahasiswa'   => (int) ($per_peran['mahasiswa'] ?? 0),
            'Universitas' => (int) ($per_peran['universitas'] ?? 0),
            'Staf'        => (int) ($per_peran['admin'] ?? 0) + (int) ($per_peran['admin_kabkota'] ?? 0)
                           + (int) ($per_peran['admin_bidang'] ?? 0),
        ];
        $hitung = fn($tabel) => $this->db->table_exists($tabel) ? (int) $this->db->count_all($tabel) : 0;
        $data['total_diskusi']   = $hitung('forum_diskusi');
        $data['total_psu']       = $hitung('psu_serah_terima');
        $data['total_bank_data'] = $hitung('sf_bank_data_dokumen');
        // Kunci hitung warga terdaftar: akun warga yang mengikat NIK (usr_users.nik_lookup_hash, UNIQUE).
        $data['warga_terdaftar'] = (int) $this->db->where('role', 'warga')
            ->where('nik_lookup_hash IS NOT NULL', NULL, FALSE)->count_all_results('usr_users');
        $data['tercocokkan_simperum'] = $this->db->table_exists('sf_data_simperum')
            ? (int) $this->db->where('response_status', 'found')->count_all_results('sf_data_simperum') : 0;
        $this->load->library('Security_alert');
        $data['peringatan_keamanan'] = $this->security_alert->ringkasan();

        // Hanya backlog aktif: riwayat yang sudah diputus bukan pekerjaan hari ini.
        $data['antrean_tanpa_wilayah'] = (int) $this->db
            ->where('kabupaten_id IS NULL', NULL, FALSE)
            ->where('status_antrean', 'pending')
            ->count_all_results('sf_housing_queue');

        $data['aktivitas'] = $this->aktivitas_terkini();

        $this->render_admin('admin/dashboard', $data);
    }

    /** Pengembang bersertifikat yang masa berlakunya masih aktif, menurut aturan Direktori SRP2. */
    private function srp2_aktif() {
        // Hanya memuat definisi kelas untuk metode statisnya; controller-nya tidak dijalankan.
        require_once APPPATH . 'controllers/Admin_Srp2.php';
        $n = 0;
        foreach ($this->db->select('status_sertifikasi, sertifikat_berakhir')
            ->get('srp2_certified_developers')->result() as $r) {
            if (Admin_Srp2::keadaan_berlaku($r->status_sertifikasi ?? '', $r->sertifikat_berakhir ?? '')[0] === 'aktif') { $n++; }
        }
        return $n;
    }

    /** Rekam Data triwulan berjalan, dihitung persis seperti Admin_Rekam_Data::index(). */
    private function ringkas_rekam_data() {
        $this->load->model('Rekam_data_model', 'rd');
        $tahun = (int) date('Y');
        $triwulan = (int) ceil((int) date('n') / 3);
        $hasil = ['tahun' => $tahun, 'triwulan' => $triwulan, 'domain' => [], 'diterima' => 0, 'perbaikan' => 0];
        foreach (['perumahan' => 'Perumahan', 'kawasan' => 'Kawasan'] as $domain => $label) {
            $d = ['label' => $label, 'total' => 0, 'masuk' => 0];
            foreach ($this->rd->pantau($domain, $tahun, $triwulan) as $r) {
                [$kunci] = $this->rd->keadaan_laporan($r);
                $d['total']++;
                if (in_array($kunci, ['menunggu', 'diterima', 'perbaikan'], TRUE)) { $d['masuk']++; }
                if ($kunci === 'diterima')  { $hasil['diterima']++; }
                if ($kunci === 'perbaikan') { $hasil['perbaikan']++; }
            }
            $hasil['domain'][] = $d;
        }
        return $hasil;
    }

    /**
     * Pengajuan baru lintas seluruh domain kerja superadmin. Setiap baris
     * membawa tujuan daftar yang sudah terfilter, bukan sekadar informasi mati.
     */
    private function aktivitas_terkini() {
        $items = [];

        foreach ($this->db->select('nama_lengkap, status_antrean, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('sf_housing_queue')->result() as $r) {
            $items[] = [
                'icon' => 'ph-ticket', 'jenis' => 'Antrean Perumahan',
                'judul' => trim((string) $r->nama_lengkap) !== '' ? $r->nama_lengkap : 'Pengajuan warga',
                'status' => $r->status_antrean, 'waktu' => $r->created_at,
                'url' => 'Admin?status=' . rawurlencode($r->status_antrean),
            ];
        }
        foreach ($this->db->select('judul, status, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('aduan')->result() as $r) {
            $items[] = [
                'icon' => 'ph-chat-centered-text', 'jenis' => 'Aduan',
                'judul' => $r->judul ?: 'Aduan warga', 'status' => $r->status, 'waktu' => $r->created_at,
                'url' => 'Admin_Aduan?status=' . rawurlencode($r->status),
            ];
        }
        // Label dan kolom waktu sama dengan Admin_Srp2/pending (kolom Dikirim =
        // updated_at), supaya satu pengajuan tidak tampil beda di dua layar.
        $this->load->helper('srp2');
        $label_srp2 = srp2_label_status();
        foreach ($this->db->select('nama_perusahaan, status_verifikasi, updated_at')
            ->order_by('updated_at', 'DESC')->limit(6)->get('srp2_registrations')->result() as $r) {
            $items[] = [
                'icon' => 'ph-seal-check', 'jenis' => 'Sertifikasi SRP2',
                'judul' => $r->nama_perusahaan ?: 'Pengajuan SRP2',
                'status' => $r->status_verifikasi, 'label' => $label_srp2[$r->status_verifikasi] ?? NULL,
                'waktu' => $r->updated_at,
                'url' => 'Admin_Srp2/pending?status=' . rawurlencode($r->status_verifikasi),
            ];
        }
        foreach ($this->db->select('instansi_asal, status, created_at')
            ->order_by('created_at', 'DESC')->limit(6)->get('kkn_magang_pendaftaran')->result() as $r) {
            $items[] = [
                'icon' => 'ph-graduation-cap', 'jenis' => 'KKN/Magang',
                'judul' => $r->instansi_asal ?: 'Pengajuan KKN/Magang',
                'status' => $r->status, 'waktu' => $r->created_at,
                'url' => 'Admin_Kemitraan?status=' . rawurlencode($r->status),
            ];
        }

        foreach ($this->db->select('jt.status, jt.created_at, d.judul_topik')
            ->from('forum_janji_temu jt')->join('forum_diskusi d', 'd.id_diskusi = jt.id_diskusi', 'left')
            ->order_by('jt.created_at', 'DESC')->limit(6)->get()->result() as $r) {
            $items[] = [
                'icon' => 'ph-calendar-check', 'jenis' => 'Janji Temu',
                'judul' => $r->judul_topik ?: 'Janji temu konsultasi',
                'status' => $r->status, 'waktu' => $r->created_at,
                'url' => 'Admin_Konsultasi?status=' . rawurlencode($r->status),
            ];
        }

        usort($items, fn($a, $b) => strtotime($b['waktu']) <=> strtotime($a['waktu']));
        return array_slice($items, 0, 6);
    }
}
