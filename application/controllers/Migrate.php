<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Runner migrasi skema DB. Hanya bisa diakses dari CLI atau localhost -
 * dipakai untuk menyamakan skema lokal & staging lewat application/migrations/,
 * menggantikan kebiasaan lama jalankan file .sql di docs/engineering/ manual satu-satu.
 */
class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        if ( ! $this->input->is_cli_request() && ! in_array($this->input->ip_address(), array('127.0.0.1', '::1')))
        {
            show_404();
        }

        $this->load->library('migration');
    }

    public function index()
    {
        $result = $this->migration->latest();

        if ($result === FALSE)
        {
            echo 'Migrasi gagal: '.$this->migration->error_string()."\n";
            return;
        }

        echo "Migrasi sukses, versi skema sekarang: {$result}\n";
    }

    /**
     * Diagnostik BACA SAJA - jalankan SEBELUM index() di lingkungan mana pun
     * yang keadaan migrasinya belum pasti (production khususnya). CI
     * migration->version() menandai migrasi sebagai berhasil TANPA memeriksa
     * nilai balik query di dalamnya (lihat system/libraries/Migration.php
     * baris ~302-309), jadi kalau tabel migrations ternyata tidak ada sama
     * sekali, latest() akan mencoba ulang migrasi 1..N dari nol dan bisa
     * menandai sukses walau CREATE TABLE-nya gagal senyap karena db_debug
     * mati di production. Method ini tidak mengubah apa pun - dipertahankan
     * sebagai alat baku, bukan sekali pakai, karena T6 dan role berikutnya
     * akan menghadapi masalah yang sama.
     */
    public function status()
    {
        $tables = $this->db->list_tables();
        // DATABASE MANA yang sedang dibaca - disebut lebih dulu, sebelum angka
        // apa pun. Tanpa baris ini keluaran lokal dan production tidak bisa
        // dibedakan sama sekali, dan dua kali sudah keluaran lokal dikira
        // pembacaan server. AGENTS.md §0a bahkan mensyaratkan angka skema hanya
        // boleh ditulis ulang setelah DIBACA DARI SERVER - syarat yang mustahil
        // dipenuhi kalau keluarannya sendiri tidak menyebut ia dari mana.
        echo 'DB: '.$this->db->hostname.' / '.$this->db->database."\n";
        echo 'Total tabel: '.count($tables)."\n";
        echo 'migrations: '.(in_array('migrations', $tables) ? 'ADA' : 'TIDAK ADA')."\n";

        if (in_array('migrations', $tables)) {
            $row = $this->db->order_by('version', 'DESC')->limit(1)->get('migrations')->row();
            echo 'Versi migrasi tercatat: '.($row->version ?? 'NONE')."\n";
        }

        foreach ([
            'sys_rate_limits',
            'srp2_registrations',
            'srp2_certified_developers',
            'sf_profil_warga',
            'sf_rekaman_simperum',
            'sf_penilaian_perumahan',
            'sf_berkas_penilaian',
            'sf_rekomendasi_penilaian',
            // Migrasi 026-031. Ditambahkan karena "Versi migrasi tercatat"
            // TIDAK membuktikan skemanya mendarat: CI menandai migrasi berhasil
            // tanpa memeriksa nilai balik query-nya, jadi dengan db_debug mati
            // di production sebuah CREATE TABLE yang gagal tetap tercatat
            // sukses. Daftar ini yang menjawabnya, bukan nomor versinya.
            'kkn_magang_bidang',
            'kkn_magang_slot',
            'kkn_magang_pendaftaran',
            // Migrasi 033.
            'sys_jejak_audit',
            // Migrasi 036 - kolom etalase. Kolom, bukan tabel: sf_programs sudah
            // ada sejak awal, jadi keberadaan tabelnya nol bukti.
            // Migrasi 035.
            'forum_janji_temu',
            'kkn_magang_posisi',
        ] as $t) {
            echo $t.': '.(in_array($t, $tables) ? 'ADA' : 'TIDAK ADA')."\n";
        }

        // Migrasi 037 - masa berlaku sertifikat SRP2. Kolom, bukan tabel.
        if (in_array('srp2_certified_developers', $tables)) {
            foreach (['sertifikat_terbit', 'sertifikat_berakhir'] as $k) {
                echo 'srp2_certified_developers.'.$k.': '.
                    ($this->db->field_exists($k, 'srp2_certified_developers') ? 'ADA' : 'TIDAK ADA')."
";
            }
        }

        // Migrasi 036 - kolom etalase program.
        if (in_array('sf_programs', $tables)) {
            foreach (['badge', 'syarat_utama', 'gambar', 'urutan', 'tampil_korsel'] as $k) {
                echo 'sf_programs.'.$k.': '.
                    ($this->db->field_exists($k, 'sf_programs') ? 'ADA' : 'TIDAK ADA')."
";
            }
            $n = (int) $this->db->where('tampil_korsel', 1)->count_all_results('sf_programs');
            echo 'program tampil di korsel: '.$n.($n === 0 ? ' - beranda akan kehilangan etalasenya' : '')."
";
        }

        if (in_array('srp2_registrations', $tables)) {
            echo 'srp2_registrations.certified_developer_id: '.
                ($this->db->field_exists('certified_developer_id', 'srp2_registrations') ? 'ADA' : 'TIDAK ADA')."\n";
        }

        // Kolom, bukan cuma tabel: migrasi 029 dan 031 menambah kolom pada
        // tabel yang SUDAH ada, jadi keberadaan tabelnya tidak membuktikan
        // apa-apa soal keduanya.
        if (in_array('kkn_magang_pendaftaran', $tables)) {
            foreach (['bidang_kode', 'reviewed_by_bidang', 'catatan_bidang', 'file_surat_balasan'] as $k) {
                echo 'kkn_magang_pendaftaran.'.$k.': '.
                    ($this->db->field_exists($k, 'kkn_magang_pendaftaran') ? 'ADA' : 'TIDAK ADA')."\n";
            }
        }
        if (in_array('kkn_magang_slot', $tables)) {
            echo 'kkn_magang_slot.bidang_kode: '.
                ($this->db->field_exists('bidang_kode', 'kkn_magang_slot') ? 'ADA' : 'TIDAK ADA')."\n";
        }
        // Divisi HARUS sudah lenyap - migrasi 031 membuangnya. Kalau masih ada,
        // migrasi itu tidak benar-benar tuntas meski versinya sudah 031.
        echo 'kkn_magang_divisi (harus TIDAK ADA): '.
            (in_array('kkn_magang_divisi', $tables) ? 'MASIH ADA - migrasi 031 belum tuntas' : 'sudah lenyap')."\n";

        /**
         * Migrasi 034 - dan ini jenis pemeriksaan yang BERBEDA dari semua di
         * atas. `field_exists('bidang', 'aduan')` akan menjawab ADA baik migrasi
         * ini jalan maupun tidak: kolomnya memang sudah ada sejak awal. Yang
         * berubah BENTUKNYA (NOT NULL -> NULL-able), jadi keberadaan tidak
         * membuktikan apa pun dan bentuknya harus dibaca dari information_schema.
         *
         * Ini pemeriksaan yang paling perlu dibaca sebelum kode barunya naik:
         * kalau migrasi ini TIDAK mendarat, `Umum::simpan_aduan()` menyisipkan
         * NULL ke kolom NOT NULL, `db_debug` mati di production membuatnya
         * mengembalikan FALSE tanpa suara, dan SETIAP pengiriman aduan gagal.
         */
        if (in_array('aduan', $tables)) {
            $k = $this->db->query(
                "SELECT IS_NULLABLE n, COLUMN_DEFAULT d FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aduan' AND COLUMN_NAME = 'bidang'"
            )->row();
            $nullable = $k && $k->n === 'YES';
            $sentinel = $k && stripos((string) $k->d, 'umum') !== FALSE;
            echo 'aduan.bidang NULL-able (migrasi 034): '
                .($nullable ? 'YA' : 'BELUM - kode baru akan gagal menyimpan aduan')."\n";
            echo "aduan.bidang DEFAULT 'umum' dicabut: "
                .($sentinel ? 'BELUM' : 'YA')."\n";
        }

        /* Posisi magang (migrasi 038). Yang diperiksa BUKAN sekadar tabelnya
           ada - FK-nya juga, karena `create_table` bisa berhasil sementara
           `ALTER ADD CONSTRAINT` yang menyusul gagal senyap saat db_debug mati
           (riwayat 031). Tanpa FK, posisi bisa menunjuk bidang yang sudah
           dihapus dan papan magang menampilkan lowongan tanpa induk. */
        if (in_array('kkn_magang_posisi', $tables, TRUE)) {
            $kolom = $this->db->query("SELECT COLUMN_NAME c FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kkn_magang_posisi'")->result();
            $punya = array_column($kolom, 'c');
            foreach (['bidang_kode', 'nama_posisi', 'kuota', 'aktif', 'urutan'] as $k) {
                echo "kkn_magang_posisi.{$k} (migrasi 038): "
                    .(in_array($k, $punya, TRUE) ? 'ADA' : 'HILANG')."\n";
            }
            $fk = $this->db->query("SELECT COUNT(*) n FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kkn_magang_posisi'
                  AND REFERENCED_TABLE_NAME = 'bidang'")->row('n');
            echo 'kkn_magang_posisi FK ke bidang: '
                .((int) $fk > 0 ? 'TERPASANG' : 'TIDAK ADA - posisi bisa yatim')."\n";
            echo 'posisi magang aktif: '
                .(int) $this->db->where('aktif', 1)->count_all_results('kkn_magang_posisi')."\n";
        } else {
            echo "kkn_magang_posisi (migrasi 038): BELUM ADA - layar Posisi Magang akan fatal\n";
        }

        /* NIK akun unik (migrasi 041). Yang diperiksa INDEKSNYA, bukan
           kolomnya: kolom nik/nik_lookup_hash sudah ada sejak lama, jadi
           keberadaannya nol bukti. Tanpa indeks unik, dua akun bisa mengaku
           NIK yang sama dan butir 8 tidak ditegakkan apa pun. */
        if (in_array('usr_users', $tables, TRUE)) {
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usr_users'
                  AND INDEX_NAME = 'uq_usr_nik_lookup' LIMIT 1")->row();
            echo 'usr_users NIK UNIQUE (migrasi 041): '
                .($uq === NULL ? 'TIDAK ADA - satu NIK bisa dipakai banyak akun'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIK'))."
";
            $isi = (int) $this->db->where('nik_lookup_hash IS NOT NULL', NULL, FALSE)
                ->count_all_results('usr_users');
            echo "akun dengan NIK tercatat: {$isi}
";
        }

        /* SRP2 status/NPWP (migrasi 040). UNIQUE-nya yang diperiksa, bukan
           cuma kolomnya: tanpa `uq_srp2_npwp`, butir 8 ("satu NPWP satu
           pengembang") tidak ditegakkan apa pun - dan itu justru inti
           permintaannya. ALTER terpisah seperti itu gagal senyap saat db_debug
           mati (riwayat 031). */
        if (in_array('srp2_certified_developers', $tables, TRUE)) {
            foreach (['status_sertifikasi', 'kabupaten_id', 'asosiasi',
                      'npwp_ciphertext', 'npwp_lookup_hash'] as $c) {
                echo "srp2_certified_developers.{$c} (migrasi 040): "
                    .($this->db->field_exists($c, 'srp2_certified_developers') ? 'ADA' : 'HILANG')."
";
            }
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_certified_developers'
                  AND INDEX_NAME = 'uq_srp2_npwp' LIMIT 1")->row();
            echo 'srp2 NPWP UNIQUE (butir 8): '
                .($uq === NULL ? 'TIDAK ADA - NPWP kembar bisa masuk'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIQUE'))."
";
            $isi = (int) $this->db->where('npwp_lookup_hash IS NOT NULL', NULL, FALSE)
                ->count_all_results('srp2_certified_developers');
            echo "pengembang dengan NPWP tercatat: {$isi}
";
        }

        /* Nomenklatur kawasan dirinci (migrasi 039). Ketiganya WAJIB NULL-able:
           kalau kelak ada yang menjadikannya NOT NULL, laporan yang hanya
           mengisi kegiatan langsung ditolak - dan 35 kabupaten/kota berhenti
           bisa melapor tanpa satu pun galat yang menjelaskan kenapa. */
        if (in_array('rd_kawasan_intervensi', $tables, TRUE)) {
            $kol = $this->db->query("SELECT COLUMN_NAME c, IS_NULLABLE n FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rd_kawasan_intervensi'
                  AND COLUMN_NAME IN ('nama_program','nama_sub_kegiatan','nama_pekerjaan')")->result();
            $peta = [];
            foreach ($kol as $k) { $peta[$k->c] = $k->n; }
            foreach (['nama_program', 'nama_sub_kegiatan', 'nama_pekerjaan'] as $c) {
                echo "rd_kawasan_intervensi.{$c} (migrasi 039): "
                    .( ! isset($peta[$c]) ? 'HILANG'
                        : ($peta[$c] === 'YES' ? 'ADA & opsional' : 'ADA TAPI WAJIB - laporan lama akan ditolak'))."\n";
            }
            $terisi = (int) $this->db->where('nama_program IS NOT NULL', NULL, FALSE)
                ->count_all_results('rd_kawasan_intervensi');
            echo "intervensi kawasan dengan program terpisah: {$terisi}\n";
        }

        /* SRP2 asosiasi (migrasi 042). Yang diperiksa UNIQUE-nya, bukan sekadar
           tabelnya: `create_table()` dan `ADD UNIQUE KEY` adalah DUA pernyataan
           terpisah di migrasi itu, dan ALTER yang kedua gagal SENYAP saat
           db_debug mati (riwayat 031). Tanpa `uq_srp2_asosiasi_kode`, dua
           asosiasi bisa memakai kode yang sama, dan JOIN dari
           `srp2_registrations.asosiasi` yang menyimpan STRING kode itu jadi
           ambigu tanpa satu pun galat. */
        if (in_array('srp2_asosiasi', $tables, TRUE)) {
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_asosiasi'
                  AND INDEX_NAME = 'uq_srp2_asosiasi_kode' LIMIT 1")->row();
            echo 'srp2_asosiasi UNIQUE kode (migrasi 042): '
                .($uq === NULL ? 'TIDAK ADA - kode asosiasi bisa dobel'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIK'))."\n";
            echo 'asosiasi terdaftar: '.(int) $this->db->count_all_results('srp2_asosiasi')."\n";
        } else {
            echo "srp2_asosiasi (migrasi 042): BELUM ADA - kolom Asosiasi di direktori SRP2 akan kosong\n";
        }

        /* PSU serah terima (migrasi 043). Dua hal diperiksa TERPISAH dari
           tabelnya karena keduanya dipasang lewat ALTER tersendiri:

           (a) Collation kolom `asosiasi`. Migrasi itu MENYELARASKANNYA ke
               `srp2_asosiasi.kode` dengan sengaja. Kalau ALTER-nya gagal
               senyap, tabelnya tetap ADA dan kolomnya tetap ADA - yang rusak
               cuma JOIN-nya, dengan "Illegal mix of collations" yang muncul
               jauh kemudian di layar rekap, bukan saat migrasi.

           (b) Kedua foreign key. Tanpa keduanya, baris PSU bisa menunjuk
               pengembang atau kabupaten yang sudah terhapus, dan tidak ada
               yang berteriak sampai ada yang membuka laporannya. */
        if (in_array('psu_serah_terima', $tables, TRUE)) {
            $kol = $this->db->query("SELECT COLLATION_NAME c FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'psu_serah_terima'
                  AND COLUMN_NAME = 'asosiasi' LIMIT 1")->row();
            $acuan = $this->db->query("SELECT COLLATION_NAME c FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_asosiasi'
                  AND COLUMN_NAME = 'kode' LIMIT 1")->row();
            echo 'psu_serah_terima.asosiasi collation (migrasi 043): '
                .($kol === NULL ? 'KOLOM HILANG'
                    : (($acuan !== NULL && $kol->c === $acuan->c)
                        ? 'SELARAS dengan srp2_asosiasi.kode ('.$kol->c.')'
                        : 'BEDA dari srp2_asosiasi.kode - JOIN akan gagal ('.$kol->c.')'))."\n";
            $fk = $this->db->query("SELECT CONSTRAINT_NAME n FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'psu_serah_terima'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->result();
            $terpasang = [];
            foreach ($fk as $f) { $terpasang[] = $f->n; }
            foreach (['fk_psu_pengembang', 'fk_psu_kabupaten'] as $nama) {
                echo "psu_serah_terima {$nama} (migrasi 043): "
                    .(in_array($nama, $terpasang, TRUE) ? 'TERPASANG' : 'TIDAK ADA - baris bisa yatim')."\n";
            }
            echo 'serah terima PSU tercatat: '.(int) $this->db->count_all_results('psu_serah_terima')."\n";
        } else {
            echo "psu_serah_terima (migrasi 043): BELUM ADA - layar PSU akan fatal\n";
        }
    }

    /**
     * Check mutasi R1 khusus lokal/DB uji. Membuat data sintetis lalu selalu
     * membersihkannya; sengaja bukan endpoint aplikasi warga.
     */
    public function uji_warga_r1()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->model('Housing_assessment_model');
        $this->load->library('encryption_lib');

        $total = 0;
        $failed = 0;
        $user_id = NULL;
        $snapshot_id = NULL;
        $other_snapshot_id = NULL;
        $assessment_id = NULL;
        $profile_id = NULL;
        $stamp = time();

        // NIK unik per jalan. Sebelumnya dipatok '0000000000000001' - NIK demo
        // SIMPERUM yang sama dengan yang dipakai layar dan harness lain. Begitu
        // ada satu akun mana pun yang mengikatnya, `save_profile()` di sini
        // membalas `nik_already_bound` dan TUJUH check runtuh berurutan. Itu
        // bukan regresi: penjaga NIK-ganda justru sedang bekerja benar. Yang
        // salah adalah uji yang mengandaikan dirinya pemilik tunggal sebuah NIK
        // di DB bersama.
        $nik = sprintf('99%014d', $stamp);
        $nik_lain = sprintf('98%014d', $stamp);

        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        try {
            foreach ([
                'sf_profil_warga',
                'sf_rekaman_simperum',
                'sf_penilaian_perumahan',
                'sf_berkas_penilaian',
                'sf_rekomendasi_penilaian',
            ] as $table) {
                $check($this->db->table_exists($table), "Tabel {$table} tersedia");
            }

            $this->db->insert('usr_users', [
                'email' => "uji_warga_r1_{$stamp}@example.test",
                'password' => password_hash('UjiWargaR1!', PASSWORD_BCRYPT),
                'name' => 'Warga Simulasi R1',
                'username' => "uji_warga_r1_{$stamp}",
                'role' => 'warga',
                'status' => 'active',
                'profile_completed' => 1,
                'kabupaten_id' => 3374,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $user_id = (int) $this->db->insert_id();
            $check($user_id > 0, 'Akun sintetis dibuat');

            $profile = $this->Housing_assessment_model->save_profile($user_id, [
                'source_mode' => 'simulation',
                'nik' => $nik,
                'family_card_number' => '0000000000001001',
                'full_name' => 'Warga Simulasi R1',
                'address' => 'Alamat Sintetis R1',
                'birth_date' => '1980-01-01',
                'gender_code' => 'male',
                'welfare_decile' => 2,
            ], ['full_name' => ['source' => 'simulation']]);
            $check(! empty($profile['success']), 'Model menyimpan profil');

            $profile_id = empty($profile['profile_id']) ? NULL : (int) $profile['profile_id'];
            $profile_row = $profile_id ? $this->db
                ->get_where('sf_profil_warga', ['id' => $profile_id])
                ->row_array() : NULL;
            $check(
                $profile_row
                && $profile_row['nik_ciphertext'] !== $nik
                && $this->encryption_lib->decrypt($profile_row['nik_ciphertext']) === $nik,
                'NIK tersimpan terenkripsi dan dapat didekripsi'
            );
            $check(
                $profile_row
                && strpos((string) $profile_row['full_name_ciphertext'], 'Warga Simulasi') === FALSE,
                'Nama tidak tersimpan plaintext'
            );

            $snapshot = $this->Housing_assessment_model->store_source_snapshot(
                $nik,
                'simulation',
                'SIM-01',
                'found',
                ['fixture_id' => 'SIM-01', 'synthetic' => TRUE],
                ['api_version' => 'simulation-v1', 'requested_by' => $user_id]
            );
            $snapshot_id = empty($snapshot['snapshot_id']) ? NULL : (int) $snapshot['snapshot_id'];
            $check(! empty($snapshot['success']), 'Snapshot simulasi tersimpan');

            $snapshot_row = $snapshot_id ? $this->db
                ->get_where('sf_rekaman_simperum', ['id' => $snapshot_id])
                ->row_array() : NULL;
            $check(
                $snapshot_row
                && strpos((string) $snapshot_row['payload_ciphertext'], 'SIM-01') === FALSE
                && $this->encryption_lib->is_encrypted($snapshot_row['payload_ciphertext']),
                'Payload snapshot terenkripsi'
            );

            $original_key = getenv('KPKP_DATA_KEY');
            putenv('KPKP_DATA_KEY=');
            try {
                $no_key = $this->Housing_assessment_model->store_source_snapshot(
                    '0000000000000097',
                    'simulation',
                    'SIM-97',
                    'not_found',
                    NULL
                );
            } finally {
                putenv('KPKP_DATA_KEY=' . $original_key);
            }
            $check(
                empty($no_key['success']) && ($no_key['code'] ?? '') === 'encryption_unavailable',
                'Penulisan ditolak saat kunci enkripsi tidak tersedia'
            );

            $other_snapshot = $this->Housing_assessment_model->store_source_snapshot(
                $nik_lain,
                'simulation',
                'SIM-98',
                'not_found',
                NULL
            );
            $other_snapshot_id = empty($other_snapshot['snapshot_id'])
                ? NULL : (int) $other_snapshot['snapshot_id'];
            $mismatched_draft = $this->Housing_assessment_model->create_draft(
                $user_id,
                (int) ($profile['profile_id'] ?? 0),
                3374,
                'existing_house',
                'simulation',
                $other_snapshot_id
            );
            $check(
                empty($mismatched_draft['success'])
                && ($mismatched_draft['code'] ?? '') === 'snapshot_invalid',
                'Snapshot dengan NIK berbeda ditolak'
            );

            $draft = $this->Housing_assessment_model->create_draft(
                $user_id,
                (int) ($profile['profile_id'] ?? 0),
                3374,
                'existing_house',
                'simulation',
                $snapshot_id
            );
            $assessment_id = empty($draft['assessment_id']) ? NULL : (int) $draft['assessment_id'];
            $check(! empty($draft['success']), 'Draft assessment dibuat');

            $first_update = $this->Housing_assessment_model->update_owned_draft(
                $assessment_id,
                $user_id,
                0,
                ['current_step' => 'housing', 'housing_status_code' => 'owned']
            );
            $check(! empty($first_update['success']) && (int) $first_update['lock_version'] === 1,
                'Update pertama dengan lock_version 0 berhasil');

            $stale_update = $this->Housing_assessment_model->update_owned_draft(
                $assessment_id,
                $user_id,
                0,
                ['current_step' => 'structure']
            );
            $check(
                empty($stale_update['success']) && ($stale_update['code'] ?? '') === 'stale_or_not_owned',
                'Update kedua dengan lock lama ditolak'
            );

            $wrong_owner = $this->Housing_assessment_model->get_owned_assessment(
                $assessment_id,
                $user_id + 999999
            );
            $check($wrong_owner === NULL, 'Assessment tidak terbaca sebagai user lain');
        } finally {
            if ($assessment_id) {
                $this->db->delete('sf_penilaian_perumahan', ['id' => $assessment_id]);
            }
            if ($snapshot_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $snapshot_id]);
            }
            if ($other_snapshot_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $other_snapshot_id]);
            }
            // Profil tidak dihapus di sini dengan sengaja: FK
            // `fk_sf_citizen_profiles_user` sudah ON DELETE CASCADE, jadi baris
            // di bawah ini membawanya serta. Diperiksa, bukan diandaikan - lihat
            // check "Ikatan NIK uji dilepas" di bawah.
            if ($user_id) {
                $this->db->delete('usr_users', ['id' => $user_id]);
            }
        }

        $leftovers = $this->db->like('email', 'uji_warga_r1_', 'after')
            ->count_all_results('usr_users');
        $check($leftovers === 0, 'Data uji dibersihkan');
        // Menjaga cascade-nya, bukan sekadar merapikan. Kalau FK profil pernah
        // kehilangan ON DELETE CASCADE-nya, ikatan NIK tertinggal dan uji ini
        // merah SELAMANYA mulai jalan berikutnya - gejala yang jauh lebih mahal
        // dibaca daripada satu check yang gagal di sini.
        $check(
            $this->db->where('nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
                ->count_all_results('sf_profil_warga') === 0,
            'Ikatan NIK uji dilepas - jalan berikutnya tidak terhalang'
        );

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    /**
     * Check gateway/cache R2 tanpa HTTP dan tanpa data permanen.
     */
    public function uji_warga_r2()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->library('simperum_gateway');
        $this->load->library('encryption_lib');

        $cases = [
            ['0000000000000001', '1980-01-01', 'found'],
            ['0000000000000098', '2000-01-01', 'not_found'],
            ['0000000000000099', '2000-01-01', 'error'],
        ];
        $hashes = array_map(function ($case) {
            return $this->encryption_lib->deterministic_hash($case[0]);
        }, $cases);
        $this->db->where_in('nik_lookup_hash', $hashes)->delete('sf_rekaman_simperum');

        $total = 0;
        $failed = 0;
        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        try {
            foreach ($cases as [$nik, $birth_date, $expected]) {
                $first = $this->simperum_gateway->lookup($nik, $birth_date);
                $second = $this->simperum_gateway->lookup($nik, $birth_date);
                $count = $this->db
                    ->where('nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
                    ->count_all_results('sf_rekaman_simperum');

                $check($first['status'] === $expected, "{$expected}: respons pertama benar");
                $check(empty($first['data']['cache_hit']), "{$expected}: respons pertama bukan cache");
                $check(! empty($second['data']['cache_hit']), "{$expected}: respons kedua dari cache");
                $check($count === 1, "{$expected}: hanya satu snapshot dibuat");
            }

            $public_json = json_encode($this->simperum_gateway->lookup(
                '0000000000000001',
                '1980-01-01'
            ));
            $check(
                strpos($public_json, 'Warga Simulasi RTLH') === FALSE
                && strpos($public_json, 'Alamat Sintetis Kota Semarang') === FALSE
                && strpos($public_json, '0000000000000001') === FALSE,
                'Respons gateway tidak memuat raw PII'
            );
        } finally {
            $this->db->where_in('nik_lookup_hash', $hashes)->delete('sf_rekaman_simperum');
        }

        $leftovers = $this->db->where_in('nik_lookup_hash', $hashes)
            ->count_all_results('sf_rekaman_simperum');
        $check($leftovers === 0, 'Snapshot uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    public function simperum_probe($action = 'lookup')
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $nik = '0000000000000001';
        $this->load->library('encryption_lib');
        $hash = $this->encryption_lib->deterministic_hash($nik);
        if ($action === 'reset') {
            $this->db->delete('sf_rekaman_simperum', ['nik_lookup_hash' => $hash]);
            echo "reset\n";
            return;
        }
        if ($action === 'count') {
            echo $this->db->where('nik_lookup_hash', $hash)
                ->count_all_results('sf_rekaman_simperum') . "\n";
            return;
        }

        $this->load->library('simperum_gateway');
        echo json_encode($this->simperum_gateway->lookup($nik, '1980-01-01')) . "\n";
    }

    /**
     * Check Rekam Data D1 - model, siklus status, scope wilayah.
     *
     *   php index.php migrate uji_rekam_data_d1
     *
     * Memakai tahun sentinel 2999 supaya tidak pernah bersinggungan dengan data
     * pelaporan sungguhan, dan menghapus seluruh jejaknya di `finally`.
     */
    public function uji_rekam_data_d1()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->model('Rekam_data_model', 'rd');

        $total  = 0;
        $failed = 0;
        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) {
                $failed++;
            }
        };

        // Tahun sentinel harus tetap di dalam rentang yang model anggap sah
        // (2020-2100), jadi 2099 - bukan 2999.
        $TAHUN = 2099;
        $kabs = $this->db->select('id')->order_by('id', 'ASC')->limit(2)
            ->get('kabupaten')->result_array();
        $aktor = $this->db->select('id')->order_by('id', 'ASC')->limit(1)
            ->get('usr_users')->row_array();
        if (count($kabs) < 2 || ! $aktor) {
            fwrite(STDERR, "Prasyarat gagal: butuh >=2 kabupaten dan >=1 pengguna.\n");
            exit(1);
        }
        $KAB   = (int) $kabs[0]['id'];
        $LAIN  = (int) $kabs[1]['id'];
        $AKTOR = (int) $aktor['id'];

        try {
            foreach (['rd_laporan', 'rd_perumahan_program', 'rd_perumahan_baris',
                'rd_perumahan_bnba', 'rd_kawasan_ringkasan', 'rd_kawasan_intervensi'] as $t) {
                $check($this->db->table_exists($t), "Tabel {$t} tersedia");
            }

            // ================================================================
            // SEPARUH PERUMAHAN BERKAS INI DIBUANG, bukan diperbaiki.
            //
            // Ia menguji bentuk PRA-wizard: gerbang per sumber dana
            // (`rd_perumahan_bagian`), kolom `unit`/`anggaran` tunggal, periode
            // `bulan`, dan pewarisan antar periode. Keempatnya tidak ada lagi
            // setelah migrasi 024. Penggantinya `uji_wizard_w2()` di berkas yang
            // sama: 34 pemeriksaan atas bentuk yang BENAR-BENAR dipakai, termasuk
            // yang di sini tidak pernah ada (rencana vs realisasi, gerbang per
            // program, kumulatif dihitung sekali).
            //
            // Memperbaikinya berarti punya dua suite model yang menguji hal yang
            // sama dengan kata-kata berbeda - dan yang kedua akan tertinggal
            // lagi pada perubahan berikutnya, persis seperti sekarang.
            //
            // Yang TIDAK dibuang: Kawasan. Modul itu tidak ditulis ulang, dan
            // tidak ada suite tingkat-model lain yang menyentuhnya. `uji_wizard_w2`
            // khusus Perumahan; D4 menguji Kawasan lewat HTTP, bukan lewat pintu
            // tulis model - jadi penolakan seperti "indikator tak dikenal" dan
            // "satuan diturunkan, tidak disimpan" hanya dijaga di sini.
            // ================================================================

            // --- kawasan: pintu tulis model ---------------------------------
            $k = $this->rd->ambil_atau_buat_draft('kawasan', $KAB, $TAHUN, 2);
            $lapk = (int) $k['laporan']['id'];
            $check(empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 0], $KAB)['success']),
                'Tidak ada progres tanpa catatan ditolak');
            $check( ! empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 1, 'total_luas_ha' => 12.75], $KAB)['success']),
                'Ringkasan kawasan tersimpan');
            $check(empty($this->rd->simpan_ringkasan($lapk,
                ['ada_penanganan' => 1, 'ada_progres' => 1, 'total_luas_ha' => -1], $KAB)['success']),
                'Luas negatif ditolak');

            $iv = [];
            foreach ([['drainase', 480000000, 60000000], ['air_minum', 212500000, 0],
                ['jalan_lingkungan', 675000000, 90000000]] as $n => $row) {
                $r = $this->rd->simpan_intervensi($lapk, [
                    'indikator' => $row[0], 'nama_kegiatan' => 'Kegiatan ' . ($n + 1),
                    'lokasi_teks' => 'RT 1 RW 1, Desa Uji, Kec. Uji',
                    'sumber_anggaran' => 'apbd_kabkota', 'volume' => 100.5,
                    'nilai_anggaran' => $row[1], 'nilai_padat_karya' => $row[2],
                ], NULL, $KAB);
                $iv[] = (int) ($r['intervensi_id'] ?? 0);
            }
            $check(count(array_filter($iv)) === 3, 'Tiga intervensi tersimpan');
            $check(empty($this->rd->simpan_intervensi($lapk, [
                'indikator' => 'tidak_ada', 'nama_kegiatan' => 'x', 'lokasi_teks' => 'x',
                'sumber_anggaran' => 'apbd_kabkota'], NULL, $KAB)['success']),
                'Indikator tak dikenal ditolak');

            $check(empty($this->rd->simpan_intervensi($lapk, [
                'indikator' => 'drainase', 'nama_kegiatan' => 'Scope', 'lokasi_teks' => 'x',
                'sumber_anggaran' => 'apbd_kabkota'], NULL, $LAIN)['success']),
                'Tulis intervensi dari kabupaten lain ditolak');

            $total_k = $this->rd->total_kawasan($lapk);
            $check($total_k['total_anggaran'] === 1367500000 && $total_k['total_padat_karya'] === 150000000,
                'Total anggaran & padat karya dihitung, bukan disimpan');

            $this->rd->hapus_intervensi($lapk, $iv[1], $KAB);
            $urutan = array_column($this->db->select('urutan')->order_by('urutan', 'ASC')
                ->get_where('rd_kawasan_intervensi', ['laporan_id' => $lapk])->result_array(), 'urutan');
            $check($urutan === ['1', '2'] || $urutan === [1, 2], 'Urutan dirapatkan setelah hapus');

            // --- rekap kawasan ----------------------------------------------
            $this->rd->transisi($lapk, 'draft', 'terkirim', $AKTOR, $KAB);
            $rk = $this->rd->rekap('kawasan', $TAHUN, 2, $KAB);
            $check(count($rk) === 1 && (int) $rk[0]['jumlah_intervensi'] === 2,
                'Rekap kawasan satu periode');
            $check($this->rd->rekap('kawasan', $TAHUN, 3, $KAB) === [],
                'Triwulan lain TIDAK ikut terbawa ke rekap');
            $check($this->rd->rekap('kawasan', $TAHUN, 2, $LAIN) === [],
                'Rekap ter-scope kabupaten');
        } finally {
            foreach ($this->db->select('id')->get_where('rd_laporan', ['tahun' => $TAHUN])->result_array() as $row) {
                $this->db->delete('rd_laporan', ['id' => (int) $row['id']]);
            }
        }

        $check($this->db->where('tahun', $TAHUN)->count_all_results('rd_laporan') === 0,
            'Data uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }

    /**
     * Check wizard W2 - pintu tulis model bentuk baru.
     *
     *   php index.php migrate uji_wizard_w2
     *
     * Tahun sentinel 2099 (harus di dalam rentang sah model 2020-2100), dan
     * seluruh jejaknya dihapus di akhir.
     */
    public function uji_wizard_w2()
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        $this->load->model('Rekam_data_model', 'rd');

        $total = 0; $failed = 0;
        $check = function ($condition, $label) use (&$total, &$failed) {
            $total++;
            echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
            if ( ! $condition) { $failed++; }
        };

        $TAHUN = 2099;
        $kabs = $this->db->select('id')->order_by('id', 'ASC')->limit(2)->get('kabupaten')->result_array();
        $aktor = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('usr_users')->row_array();
        if (count($kabs) < 2 || ! $aktor) {
            fwrite(STDERR, "Prasyarat gagal: butuh >=2 kabupaten dan >=1 pengguna.\n");
            exit(1);
        }
        $KAB = (int) $kabs[0]['id']; $LAIN = (int) $kabs[1]['id']; $AKTOR = (int) $aktor['id'];

        $bersihkan = function () use ($TAHUN) {
            foreach ($this->db->select('id')->get_where('rd_laporan', ['tahun' => $TAHUN])->result_array() as $r) {
                $this->db->delete('rd_laporan', ['id' => (int) $r['id']]);
            }
        };
        $bersihkan();

        try {
            // ---------------------------------------------------------- periode
            $check(empty($this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 5)['success']),
                'Triwulan 5 ditolak (rentang 1-4)');
            $check(empty($this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 0)['success']),
                'Triwulan 0 ditolak');

            $tw1 = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 1);
            $check( ! empty($tw1['success']), 'Draft TW1 dibuat');
            $ID1 = (int) $tw1['laporan']['id'];

            $check((int) $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 1)['laporan']['id'] === $ID1,
                'Idempoten: periode sama tidak melahirkan laporan kedua');

            $check($this->rd->laporan_periode('perumahan', $KAB, $TAHUN, 3) === NULL,
                'laporan_periode() tidak membuat apa pun untuk periode kosong');

            // --------------------------------------------------- gerbang program
            $check(empty($this->rd->simpan_gerbang_program($ID1, ['program_ngawur'], $KAB)['success']),
                'Program tidak dikenal ditolak');

            $g = $this->rd->simpan_gerbang_program($ID1, ['pk_rtlh', 'pb_rtlh'], $KAB);
            $check( ! empty($g['success']) && $g['dipilih'] === 2, 'Dua program dicentang');

            // --------------------------------------------------------- isi angka
            $check(empty($this->rd->simpan_sumber($ID1, 'pb_backlog', 'csr',
                    ['realisasi_unit' => 1, 'realisasi_anggaran' => 1000], $KAB)['success']),
                'Angka untuk program yang belum dicentang ditolak');

            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota',
                    ['realisasi_unit' => 0, 'realisasi_anggaran' => 5000000], $KAB)['success']),
                'REALISASI: anggaran tanpa unit ditolak');
            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota',
                    ['rencana_unit' => 0, 'rencana_anggaran' => 5000000], $KAB)['success']),
                'RENCANA: anggaran tanpa unit ditolak');
            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota',
                    ['realisasi_unit' => -3, 'realisasi_anggaran' => 0], $KAB)['success']),
                'Angka negatif ditolak');
            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota', [], $KAB)['success']),
                'Seluruh angka nol ditolak');

            $check( ! empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota',
                    ['rencana_unit' => 100, 'rencana_anggaran' => 2000000000,
                     'realisasi_unit' => 90, 'realisasi_anggaran' => 1800000000], $KAB)['success']),
                'Empat angka tersimpan');

            $this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbd_kabkota',
                ['rencana_unit' => 100, 'rencana_anggaran' => 2000000000,
                 'realisasi_unit' => 95, 'realisasi_anggaran' => 1900000000], $KAB);
            $check((int) $this->db->where(['laporan_id' => $ID1, 'program' => 'pk_rtlh',
                    'sumber_dana' => 'apbd_kabkota'])->count_all_results('rd_perumahan_baris') === 1,
                'Simpan ulang mengubah, tidak menggandakan');
            $check((int) $this->db->select('realisasi_unit')->get_where('rd_perumahan_baris',
                    ['laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota'])
                    ->row('realisasi_unit') === 95,
                'Nilai terbarui ke 95');

            // ----------------------------------------- pewarisan benar-benar nol
            // Dulu ini memeriksa `$tw1['diwarisi'] === 0` - angka yang dilaporkan
            // fungsi TENTANG DIRINYA SENDIRI. Pemeriksaan begitu tetap hijau
            // walaupun barisnya benar-benar tersalin, asal penghitungnya lupa
            // dinaikkan. Sekarang TW1 sudah berisi baris dan gerbang program,
            // jadi TW2 diperiksa dari isi tabelnya: kalau suatu hari pewarisan
            // kembali diam-diam, uji ini yang merah, bukan yang lain.
            $tw2 = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 2);
            $ID2 = (int) $tw2['laporan']['id'];
            $check($ID2 !== $ID1, 'TW2 laporan tersendiri');
            $check((int) $this->db->where('laporan_id', $ID2)->count_all_results('rd_perumahan_baris') === 0,
                'TW2 lahir KOSONG - nol baris angka diwarisi dari TW1');
            $check((int) $this->db->where('laporan_id', $ID2)->count_all_results('rd_perumahan_program') === 0,
                'TW2 lahir KOSONG - nol gerbang program diwarisi dari TW1');
            $check((int) $this->db->where('laporan_id', $ID1)->count_all_results('rd_perumahan_baris') > 0,
                'TW1 tetap berisi (pembanding sahih, bukan dua-duanya kebetulan kosong)');

            // Keterangan hanya untuk sumber tertentu
            $this->rd->simpan_sumber($ID1, 'pk_rtlh', 'csr',
                ['realisasi_unit' => 5, 'realisasi_anggaran' => 50000000, 'keterangan' => 'PT Uji'], $KAB);
            $check($this->db->select('keterangan')->get_where('rd_perumahan_baris',
                    ['laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'csr'])
                    ->row('keterangan') === 'PT Uji', 'Keterangan tersimpan untuk CSR');
            $this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbn_dak',
                ['realisasi_unit' => 5, 'realisasi_anggaran' => 50000000, 'keterangan' => 'diabaikan'], $KAB);
            $check($this->db->select('keterangan')->get_where('rd_perumahan_baris',
                    ['laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbn_dak'])
                    ->row('keterangan') === '', 'Keterangan diabaikan untuk sumber tanpa penyalur');

            // ------------------------------------------------------------ scope
            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbn_bsps',
                    ['realisasi_unit' => 1, 'realisasi_anggaran' => 0], $LAIN)['success']),
                'Kabupaten lain ditolak (scope sebagai gerbang)');

            // ----------------------------------------------------------- gerbang
            $check(in_array('pb_rtlh', $this->rd->program_tanpa_angka($ID1), TRUE),
                'Program dicentang tanpa angka terdeteksi');
            $check(empty($this->rd->kirim($ID1, $AKTOR, $KAB)['success']),
                'Kirim ditolak selama ada program kosong');

            // Mencabut centang menyapu angkanya
            $this->rd->simpan_gerbang_program($ID1, ['pk_rtlh'], $KAB);
            $check((int) $this->db->where('laporan_id', $ID1)->count_all_results('rd_perumahan_program') === 1,
                'Program dicabut hilang dari gerbang');
            $check($this->rd->program_tanpa_angka($ID1) === [], 'Nol program kosong tersisa');

            /* BNBA WAJIB sejak 5 Agt 2026 (butir C1). Gerbangnya diuji dua arah
               di sini juga - cek ini yang paling dekat ke modelnya, tanpa HTTP. */
            $check(empty($this->rd->kirim($ID1, $AKTOR, $KAB)['success']),
                'Kirim ditolak selama BNBA belum dilampirkan');
            $lampir_bnba = function ($laporan_id) {
                $this->db->replace('rd_perumahan_bnba', [
                    'laporan_id'   => (int) $laporan_id,
                    'nama_asli'    => 'bnba-uji.pdf',
                    'private_path' => 'uji/bnba-uji.pdf',
                    'mime_type'    => 'application/pdf',
                    'ukuran'       => 1024,
                ]);
            };
            $lampir_bnba($ID1);
            $check( ! empty($this->rd->kirim($ID1, $AKTOR, $KAB)['success']), 'Kirim diterima');

            $check(empty($this->rd->simpan_sumber($ID1, 'pk_rtlh', 'apbn_bsps',
                    ['realisasi_unit' => 1, 'realisasi_anggaran' => 0], $KAB)['success']),
                'Laporan terkirim terkunci dari perubahan');

            // -------------------------------------------------------- hapus baris
            $this->rd->minta_perbaikan($ID1, $AKTOR, 'perbaiki', 'perumahan');
            $check( ! empty($this->rd->hapus_sumber($ID1, 'pk_rtlh', 'apbn_dak', $KAB)['success']),
                'Hapus satu sumber dana berhasil');
            $check(empty($this->rd->hapus_sumber($ID1, 'pk_rtlh', 'apbn_dak', $KAB)['success']),
                'Hapus yang sudah tidak ada ditolak');
            $this->rd->kirim($ID1, $AKTOR, $KAB);

            // -------------------------------------------------------- KUMULATIF
            $tw2 = $this->rd->ambil_atau_buat_draft('perumahan', $KAB, $TAHUN, 2);
            $ID2 = (int) $tw2['laporan']['id'];
            $this->rd->simpan_gerbang_program($ID2, ['pk_rtlh'], $KAB);
            $this->rd->simpan_sumber($ID2, 'pk_rtlh', 'apbd_kabkota',
                ['rencana_unit' => 10, 'rencana_anggaran' => 100000000,
                 'realisasi_unit' => 5, 'realisasi_anggaran' => 50000000], $KAB);
            $lampir_bnba($ID2);
            $this->rd->kirim($ID2, $AKTOR, $KAB);

            $satu = 0; $dua = 0;
            foreach ($this->rd->rekap('perumahan', $TAHUN, 1, $KAB) as $r) {
                if ($r['sumber_dana'] === 'apbd_kabkota' && $r['program'] === 'pk_rtlh') { $satu = (int) $r['realisasi_unit']; }
            }
            foreach ($this->rd->rekap('perumahan', $TAHUN, 2, $KAB) as $r) {
                if ($r['sumber_dana'] === 'apbd_kabkota' && $r['program'] === 'pk_rtlh') { $dua = (int) $r['realisasi_unit']; }
            }
            $kum = 0;
            foreach ($this->rd->kumulatif($TAHUN, 2, $KAB) as $r) {
                if ($r['sumber_dana'] === 'apbd_kabkota' && $r['program'] === 'pk_rtlh') { $kum = (int) $r['realisasi_unit']; }
            }
            $check($satu === 95 && $dua === 5, "rekap() memberi angka SATU triwulan (TW1={$satu}, TW2={$dua})");
            $check($kum === 100, "kumulatif() menjumlahkan sekali saja: 95+5={$kum}");

            $kum1 = 0;
            foreach ($this->rd->kumulatif($TAHUN, 1, $KAB) as $r) {
                if ($r['sumber_dana'] === 'apbd_kabkota' && $r['program'] === 'pk_rtlh') { $kum1 = (int) $r['realisasi_unit']; }
            }
            $check($kum1 === 95, 'kumulatif() s.d. TW1 tidak memuat TW2');

        } finally {
            $bersihkan();
        }

        $check($this->db->where('tahun', $TAHUN)->count_all_results('rd_laporan') === 0,
            'Data uji dibersihkan');

        echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
        if ($failed > 0) {
            exit(1);
        }
    }
}
