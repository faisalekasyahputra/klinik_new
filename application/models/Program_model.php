<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->helper('housing_queue');
    }

    /**
     * Program yang tampil di korsel etalase beranda - SATU-SATUNYA sumbernya.
     *
     * Sampai 5 Agt 2026 daftar ini hardcode di dalam JS komponen korsel, dan
     * `sf_programs` cuma dipakai untuk kelayakan. Dua tempat itu sudah melenceng
     * (judul berbeda antara katalog admin dan korsel), dan itulah yang membuat
     * layar Katalog Program lahir sebagai pembanding. Migrasi 036 memindahkan
     * yang DITAMPILKAN ke tabel; sejak itu korsel membaca dari sini.
     *
     * `is_active` IKUT MENGGERBANG. Program yang dinonaktifkan di Katalog
     * Program tidak boleh terus dipromosikan di beranda - kalau tidak, warga
     * mengeklik sesuatu yang pengajuannya sudah ditutup.
     */
    public function etalase() {
        if ( ! $this->db->table_exists('sf_programs')
            || ! $this->db->field_exists('tampil_korsel', 'sf_programs')) {
            // Migrasi 036 belum jalan. Bukan alasan menampilkan data lain -
            // dua sumber justru masalah yang sedang dibereskan.
            log_message('error', 'Program_model::etalase() - kolom etalase belum ada; jalankan migrasi 036.');
            return [];
        }
        $rows = $this->db->select('id, kode_program, nama_program, deskripsi_singkat,
                                   badge, syarat_utama, gambar')
            ->where(['tampil_korsel' => 1, 'is_active' => 1])
            ->order_by('urutan', 'ASC')->order_by('id', 'ASC')
            ->get('sf_programs')->result_array();

        // `db_debug` mati di production: query gagal mengembalikan array kosong
        // tanpa suara. Dicatat supaya kekosongan bisa ditelusuri, bukan ditebak.
        if ( ! $rows) {
            log_message('error', 'Program_model::etalase() - nol program etalase aktif.');
        }
        return $rows;
    }

    public function get_program_by_code($kode_program) {
        $this->db->where('kode_program', $kode_program);
        $this->db->where('is_active', 1);
        return $this->db->get('sf_programs')->row_array();
    }

    /**
     * Tentukan kabupaten_id yang boleh disimpan ke sf_housing_queue.
     *
     * URUTAN KEPERCAYAAN (jangan dibalik):
     *   1. Domisili user yang login (usr_users.kabupaten_id) - data terverifikasi,
     *      tidak bisa dipalsukan pemohon lewat form.
     *   2. Pilihan user di form, TAPI wajib cocok dengan baris nyata di tabel
     *      kabupaten - menutup nilai sembarang/ngawur.
     *   3. NULL kalau dua-duanya tidak tersedia (tamu tanpa pilihan valid).
     *
     * Kenapa penting: kolom ini yang jadi dasar WHERE di dashboard Admin_Kabkota.
     * Sebelum ini nilainya diambil mentah dari $_POST, jadi pemohon bisa
     * mengarahkan pengajuannya ke admin wilayah manapun, atau mengirim nilai
     * kosong/ngawur supaya barisnya tidak muncul di dashboard siapa pun.
     * Lihat docs/engineering/AUDIT_ROLE_ADMIN_SCOPED.md temuan #1 (tingkat Tinggi).
     *
     * ASUMSI PRODUK: untuk pemohon yang sudah login dan profilnya punya
     * kabupaten, domisili profil MENANG atas pilihan dropdown. Kalau nanti
     * kebijakannya "warga boleh mengajukan ke kabupaten lain", ubah di sini -
     * satu tempat, bukan tersebar di controller.
     */
    public function resolve_kabupaten_id($user_id = NULL, $requested_id = NULL) {
        if ( ! empty($user_id)) {
            $profil = $this->db->select('kabupaten_id')
                ->get_where('usr_users', ['id' => (int) $user_id])->row();
            if ($profil && ! empty($profil->kabupaten_id)) {
                return (int) $profil->kabupaten_id;
            }
        }

        $requested_id = (int) $requested_id;
        if ($requested_id > 0 && $this->db->where('id', $requested_id)->count_all_results('kabupaten') > 0) {
            return $requested_id;
        }

        return NULL;
    }

    /**
     * Satu-satunya pintu masuk baris sf_housing_queue. Pemanggil WAJIB
     * menyertakan key 'kabupaten_id' (boleh bernilai NULL) yang sudah lewat
     * resolve_kabupaten_id() - kalau lupa, dicatat ke log dan dipaksa NULL,
     * supaya kelalaian terlihat di log alih-alih diam-diam menghasilkan baris
     * yang tidak pernah muncul di dashboard admin manapun (kasus lama
     * Program::ajukan_solusi(), lihat AUDIT_ROLE_WARGA.md temuan #4).
     */
    public function insert_housing_queue($data) {
        if (empty($data['ticket_code'])) {
            $data['ticket_code'] = $this->generate_ticket_code();
        }
        if ( ! array_key_exists('kabupaten_id', $data)) {
            log_message('error', 'insert_housing_queue dipanggil tanpa kabupaten_id - baris tidak akan terlihat admin kabupaten manapun. Pakai resolve_kabupaten_id() di pemanggil.');
            return FALSE;
        }
        return $this->db->insert('sf_housing_queue', $data);
    }

    /**
     * Satu gerbang untuk kedua jalur pengajuan warga.
     * Identitas, hasil kelayakan, dan daftar program berasal dari sesi server.
     */
    public function create_housing_submission($identitas, $hasil, $kode_program, $user_id = NULL) {
        $now = time();
        if (empty($identitas['created_at']) || empty($hasil['created_at'])
            || $now - (int) $identitas['created_at'] > 1800
            || $now - (int) $hasil['created_at'] > 1800) {
            return ['success' => FALSE, 'code' => 'state_expired', 'message' => 'Sesi diagnosa kedaluwarsa. Silakan ulangi diagnosa.'];
        }

        $nik = trim((string) ($identitas['nik'] ?? ''));
        $nama = trim((string) ($identitas['nama_lengkap'] ?? ''));
        $survey = $hasil['data_survey'] ?? [];
        if ( ! preg_match('/^\d{16}$/', $nik) || $nama === '' || ! $this->valid_survey($survey)) {
            return ['success' => FALSE, 'code' => 'invalid_data', 'message' => 'Data identitas atau survei tidak valid. Silakan ulangi diagnosa.'];
        }

        $eligible = FALSE;
        foreach ((array) ($hasil['eligible_programs'] ?? []) as $program) {
            if (($program['kode'] ?? '') === $kode_program) {
                $eligible = TRUE;
                break;
            }
        }
        if ( ! $eligible) {
            return ['success' => FALSE, 'code' => 'program_not_eligible', 'message' => 'Program yang dipilih tidak berasal dari hasil diagnosa Anda.'];
        }

        $program = $this->get_program_by_code($kode_program);
        if ( ! $program) {
            return ['success' => FALSE, 'code' => 'program_unavailable', 'message' => 'Program pilihan belum tersedia untuk pengajuan.'];
        }

        $kabupaten_id = $this->resolve_kabupaten_id($user_id, $hasil['kabupaten_id'] ?? NULL);
        if ( ! $kabupaten_id) {
            return ['success' => FALSE, 'code' => 'wilayah_required', 'message' => 'Kabupaten/kota domisili wajib dipilih agar pengajuan sampai ke admin wilayah.'];
        }

        $ticket_code = $this->generate_ticket_code();
        $timestamp = date('Y-m-d H:i:s');
        $inserted = $this->insert_housing_queue([
            'ticket_code'        => $ticket_code,
            'user_id'            => $user_id ?: NULL,
            'kabupaten_id'       => $kabupaten_id,
            'program_id'         => (int) $program['id'],
            'nik_pengaju'        => $nik,
            'nama_lengkap'       => $nama,
            'data_simperum_json' => $identitas['data_simperum_json'] ?? NULL,
            'data_survey_json'   => json_encode($survey),
            'status_antrean'     => 'pending',
            'created_at'         => $timestamp,
            'updated_at'         => $timestamp,
        ]);

        return $inserted
            ? ['success' => TRUE, 'ticket_code' => $ticket_code]
            : ['success' => FALSE, 'code' => 'write_failed', 'message' => 'Pengajuan belum dapat disimpan. Silakan coba lagi.'];
    }

    public function transition_housing_queue($queue_id, $status, $reviewer_id, $kabupaten_id = NULL, $catatan = '') {
        $queue_id = (int) $queue_id;
        $catatan = trim((string) $catatan);

        $this->db->where('id', $queue_id);
        if ($kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $kabupaten_id);
        }
        $row = $this->db->get('sf_housing_queue')->row();

        if ( ! $row) {
            return ['success' => FALSE, 'code' => 'not_found', 'message' => 'Data pengajuan tidak ditemukan dalam kewenangan Anda.'];
        }
        if ( ! housing_queue_can_transition($row->status_antrean, $status)) {
            return ['success' => FALSE, 'code' => 'invalid_transition', 'message' => 'Perubahan status tersebut tidak diizinkan. Muat ulang halaman untuk melihat status terbaru.'];
        }
        if ($status === 'rejected' && $catatan === '') {
            return ['success' => FALSE, 'code' => 'note_required', 'message' => 'Catatan alasan penolakan wajib diisi.'];
        }

        $this->db->where('id', $queue_id)
            ->where('status_antrean', $row->status_antrean);
        if ($kabupaten_id !== NULL) {
            $this->db->where('kabupaten_id', (int) $kabupaten_id);
        }
        $updated = $this->db->update('sf_housing_queue', [
            'status_antrean' => $status,
            'catatan_admin'  => $status === 'rejected' ? $catatan : NULL,
            'reviewed_by'    => (int) $reviewer_id,
            'reviewed_at'    => date('Y-m-d H:i:s'),
        ]);

        if ( ! $updated || $this->db->affected_rows() !== 1) {
            return ['success' => FALSE, 'code' => 'write_failed', 'message' => 'Keputusan tidak tersimpan. Data mungkin sudah berubah; silakan muat ulang halaman.'];
        }

        return ['success' => TRUE, 'from' => $row->status_antrean, 'to' => $status];
    }

    private function valid_survey($survey) {
        $pekerjaan = ['PNS/TNI/POLRI', 'Karyawan Swasta', 'Wiraswasta', 'Pekerja Informal', 'Lainnya'];
        $kepemilikan = ['Sewa/Kontrak', 'Numpang/Keluarga', 'Punya Lahan Belum Bangun', 'Punya Rumah Tidak Layak', 'Punya Rumah Layak'];
        $penghasilan = $survey['penghasilan'] ?? NULL;

        return is_numeric($penghasilan)
            && (float) $penghasilan >= 0
            && (float) $penghasilan <= 100000000
            && in_array($survey['pekerjaan'] ?? '', $pekerjaan, TRUE)
            && in_array($survey['status_kepemilikan'] ?? '', $kepemilikan, TRUE)
            && trim((string) ($survey['alasan_pengajuan'] ?? '')) !== '';
    }

    public function generate_ticket_code() {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'PKP-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = $this->db->where('ticket_code', $code)->count_all_results('sf_housing_queue') > 0;
        } while ($exists);

        return $code;
    }

    public function get_housing_queue_by_ticket($ticket_code, $nik_suffix) {
        return $this->db
            ->select('status_antrean, created_at, updated_at')
            ->where('ticket_code', $ticket_code)
            ->where('RIGHT(nik_pengaju, 4) =', $nik_suffix, FALSE)
            ->get('sf_housing_queue')
            ->row_array();
    }

}
