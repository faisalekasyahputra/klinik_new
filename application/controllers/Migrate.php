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
     * CLI saja: `php index.php migrate ke <versi 14 digit>` - naik ATAU turun ke versi tertentu.
     * Dipakai untuk rollback rilis ber-migrasi (mis. 072 ke 071) tanpa skrip sementara di server.
     * Seperti index(), CI menandai sukses tanpa memeriksa query; baca hasilnya dari `migrate status`.
     */
    public function ke($versi = NULL)
    {
        if ( ! $this->input->is_cli_request() || ! preg_match('/^\d{14}$/', (string) $versi)) {
            show_404();
            return;
        }
        $result = $this->migration->version($versi);
        echo $result === FALSE
            ? 'Migrasi gagal: '.$this->migration->error_string()."\n"
            : "Migrasi sukses, versi skema sekarang: {$result}\n";
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
        // Enkripsi koneksi aplikasi -> server database (form keamanan poin 8.2).
        // Dibaca dari SESI koneksi yang sedang dipakai, bukan dari nilai env:
        // env bisa menyatakan 'verify' padahal koneksinya jatuh tanpa TLS.
        $ssl_versi  = $this->db->query("SHOW SESSION STATUS LIKE 'Ssl_version'")->row_array();
        $ssl_cipher = $this->db->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->row_array();
        echo 'Mode DB_SSL: '.transport_db_ssl_mode()."\n";
        echo 'Koneksi DB terenkripsi: '.(( ! empty($ssl_versi['Value']))
            ? $ssl_versi['Value'].' / '.($ssl_cipher['Value'] ?? '?')
            : 'TIDAK (tanpa TLS)')."\n";
        echo 'Total tabel: '.count($tables)."\n";
        echo 'migrations: '.(in_array('migrations', $tables) ? 'ADA' : 'TIDAK ADA')."\n";

        if (in_array('migrations', $tables)) {
            $row = $this->db->order_by('version', 'DESC')->limit(1)->get('migrations')->row();
            echo 'Versi migrasi tercatat: '.($row->version ?? 'NONE')."\n";
        }

        foreach ([
            'sys_batas_laju',
            // Migrasi 052 - langganan perangkat Web Push admin.
            'sys_langganan_notifikasi',
            // Migrasi 053 - privilege modul per akun admin ter-scope.
            'usr_hak_modul_admin',
            'srp2_pengajuan',
            'srp2_direktori_pengembang',
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
            // Migrasi 036 - kolom etalase. Kolom, bukan tabel: sf_program sudah
            // ada sejak awal, jadi keberadaan tabelnya nol bukti.
            // Migrasi 035.
            'forum_janji_temu',
            'kkn_magang_posisi',
        ] as $t) {
            echo $t.': '.(in_array($t, $tables) ? 'ADA' : 'TIDAK ADA')."\n";
        }

        // Migrasi 037 - masa berlaku sertifikat SRP2. Kolom, bukan tabel.
        if (in_array('srp2_direktori_pengembang', $tables)) {
            foreach (['sertifikat_terbit', 'sertifikat_berakhir'] as $k) {
                echo 'srp2_direktori_pengembang.'.$k.': '.
                    ($this->db->field_exists($k, 'srp2_direktori_pengembang') ? 'ADA' : 'TIDAK ADA')."
";
            }
        }

        // Migrasi 036 - kolom etalase program.
        if (in_array('sf_program', $tables)) {
            foreach (['lencana', 'syarat_utama', 'gambar', 'urutan', 'tampil_korsel'] as $k) {
                echo 'sf_program.'.$k.': '.
                    ($this->db->field_exists($k, 'sf_program') ? 'ADA' : 'TIDAK ADA')."
";
            }
            $n = (int) $this->db->where('tampil_korsel', 1)->count_all_results('sf_program');
            echo 'program tampil di korsel: '.$n.($n === 0 ? ' - beranda akan kehilangan etalasenya' : '')."
";
        }

        if (in_array('srp2_pengajuan', $tables)) {
            echo 'srp2_pengajuan.pengembang_id: '.
                ($this->db->field_exists('pengembang_id', 'srp2_pengajuan') ? 'ADA' : 'TIDAK ADA')."\n";
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
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aduan' AND COLUMN_NAME = 'bidang_kode'"
            )->row();
            $nullable = $k && $k->n === 'YES';
            $sentinel = $k && stripos((string) $k->d, 'umum') !== FALSE;
            echo 'aduan.bidang_kode NULL-able (migrasi 034): '
                .($nullable ? 'YA' : 'BELUM - kode baru akan gagal menyimpan aduan')."\n";
            echo "aduan.bidang_kode DEFAULT 'umum' dicabut: "
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
        if (in_array('usr_akun', $tables, TRUE)) {
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usr_akun'
                  AND INDEX_NAME = 'uq_usr_nik_lookup' LIMIT 1")->row();
            echo 'usr_akun NIK UNIQUE (migrasi 041): '
                .($uq === NULL ? 'TIDAK ADA - satu NIK bisa dipakai banyak akun'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIK'))."
";
            $isi = (int) $this->db->where('nik_lookup_hash IS NOT NULL', NULL, FALSE)
                ->count_all_results('usr_akun');
            echo "akun dengan NIK tercatat: {$isi}
";
        }

        /* SRP2 status/NPWP (migrasi 040). UNIQUE-nya yang diperiksa, bukan
           cuma kolomnya: tanpa `uq_srp2_npwp`, butir 8 ("satu NPWP satu
           pengembang") tidak ditegakkan apa pun - dan itu justru inti
           permintaannya. ALTER terpisah seperti itu gagal senyap saat db_debug
           mati (riwayat 031). */
        if (in_array('srp2_direktori_pengembang', $tables, TRUE)) {
            foreach (['status_sertifikasi', 'kabupaten_id', 'asosiasi',
                      'npwp_ciphertext', 'npwp_lookup_hash'] as $c) {
                echo "srp2_direktori_pengembang.{$c} (migrasi 040): "
                    .($this->db->field_exists($c, 'srp2_direktori_pengembang') ? 'ADA' : 'HILANG')."
";
            }
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_direktori_pengembang'
                  AND INDEX_NAME = 'uq_srp2_npwp' LIMIT 1")->row();
            echo 'srp2 NPWP UNIQUE (butir 8): '
                .($uq === NULL ? 'TIDAK ADA - NPWP kembar bisa masuk'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIQUE'))."
";
            $isi = (int) $this->db->where('npwp_lookup_hash IS NOT NULL', NULL, FALSE)
                ->count_all_results('srp2_direktori_pengembang');
            echo "pengembang dengan NPWP tercatat: {$isi}
";
        }

        /* NPWP pada pengajuan SRP2 (migrasi 056). Selain kedua kolom,
           indeks unik wajib ada agar satu NPWP tidak bisa dipakai dua akun. */
        if (in_array('srp2_pengajuan', $tables, TRUE)) {
            foreach (['npwp_ciphertext', 'npwp_lookup_hash'] as $c) {
                echo "srp2_pengajuan.{$c} (migrasi 056): "
                    .($this->db->field_exists($c, 'srp2_pengajuan') ? 'ADA' : 'HILANG')."\n";
            }
            $uq = $this->db->query("SELECT NON_UNIQUE nu FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_pengajuan'
                  AND INDEX_NAME = 'uq_srp2_registration_npwp' LIMIT 1")->row();
            echo 'pengajuan SRP2 NPWP UNIQUE (migrasi 056): '
                .($uq === NULL ? 'TIDAK ADA - NPWP kembar bisa masuk'
                    : ((int) $uq->nu === 0 ? 'TERPASANG' : 'ADA TAPI TIDAK UNIQUE'))."\n";
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
           `srp2_pengajuan.asosiasi` yang menyimpan STRING kode itu jadi
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

        /* Collation kolom `asosiasi` diperiksa TERPISAH dari tabelnya karena
           dipasang lewat ALTER tersendiri: kalau ALTER-nya gagal senyap,
           tabelnya tetap ADA dan kolomnya tetap ADA - yang rusak cuma JOIN-nya,
           dengan "Illegal mix of collations" yang muncul jauh kemudian di layar
           rekap, bukan saat migrasi.

           Diperiksa untuk SEMUA tabel yang menyimpan kode asosiasi (migrasi
           051), bukan cuma psu_serah_terima. Pemeriksaan lama di sini hanya
           menyorot satu kolom, dan itulah sebabnya
           srp2_direktori_pengembang.asosiasi meleset diam-diam tanpa satu pun
           baris keluaran yang menyebutnya. */
        $acuan = $this->db->query("SELECT COLLATION_NAME c FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_asosiasi'
              AND COLUMN_NAME = 'kode' LIMIT 1")->row();
        foreach (['psu_serah_terima', 'srp2_direktori_pengembang'] as $t) {
            if ( ! in_array($t, $tables, TRUE)) { continue; }
            $kol = $this->db->query("SELECT COLLATION_NAME c FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                  AND COLUMN_NAME = 'asosiasi' LIMIT 1", [$t])->row();
            echo $t.'.asosiasi collation (migrasi 051): '
                .($kol === NULL ? 'KOLOM HILANG'
                    : (($acuan !== NULL && $kol->c === $acuan->c)
                        ? 'SELARAS dengan srp2_asosiasi.kode ('.$kol->c.')'
                        : 'BEDA dari srp2_asosiasi.kode - JOIN akan gagal ('.$kol->c.')'))."\n";
        }

        if (in_array('psu_serah_terima', $tables, TRUE)) {
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

        /* Dashboard KKN universitas (migrasi 044). Kolom DAN tabel diperiksa
           terpisah karena keduanya dipasang lewat pernyataan ALTER/CREATE
           tersendiri - salah satunya bisa gagal senyap sementara yang lain
           sukses. FK diperiksa tersendiri lagi dengan alasan yang sama
           dengan psu_serah_terima di atas. */
        echo 'kkn_magang_pendaftaran.file_surat_simperum (migrasi 044): '
            .($this->db->field_exists('file_surat_simperum', 'kkn_magang_pendaftaran')
                ? 'ADA' : 'HILANG - dua-surat KKN akan fatal')."\n";
        if (in_array('kkn_peserta', $tables, TRUE)) {
            $fk = $this->db->query("SELECT CONSTRAINT_NAME n FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kkn_peserta'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->result();
            $terpasang = [];
            foreach ($fk as $f) { $terpasang[] = $f->n; }
            echo 'kkn_peserta fk_kkn_peserta_pendaftaran (migrasi 044): '
                .(in_array('fk_kkn_peserta_pendaftaran', $terpasang, TRUE) ? 'TERPASANG' : 'TIDAK ADA - roster bisa yatim')."\n";
            echo 'peserta KKN tercatat: '.(int) $this->db->count_all_results('kkn_peserta')."\n";
        } else {
            echo "kkn_peserta (migrasi 044): BELUM ADA - dashboard KKN akan fatal saat unggah roster\n";
        }

        /* Matriks variabel penentuan program (migrasi 045-048). Tujuh kolom
           baru di sf_penilaian_perumahan, dipasang lewat ADD COLUMN terpisah
           (045: 5 kolom, 047: 1, 048: 1) plus satu ALTER lebar kolom (046 -
           tidak menambah kolom baru, jadi tidak diperiksa tersendiri, cukup
           lewat keberadaan kolomnya di 045). Diperiksa satu-satu seperti
           migrasi 044 di atas: salah satu ADD COLUMN bisa gagal senyap
           sementara yang lain sukses. */
        $kolom_matriks = [
            'matriks_kepemilikan_lahan'        => 'migrasi 045',
            'matriks_rumah_sekarang'       => 'migrasi 045',
            'matriks_kondisi_lingkungan' => 'migrasi 045',
            'matriks_pekerjaan_keuangan'    => 'migrasi 045/046',
            'matriks_status_keluarga'        => 'migrasi 045/046',
            'matriks_penghasilan'                => 'migrasi 047',
            'matriks_status_dtks'                => 'migrasi 048',
        ];
        foreach ($kolom_matriks as $kolom => $ket) {
            echo "sf_penilaian_perumahan.{$kolom} ({$ket}): "
                .($this->db->field_exists($kolom, 'sf_penilaian_perumahan')
                    ? 'ADA' : 'HILANG - Hasil Rekomendasi Awal akan salah/kosong')."\n";
        }

        /* Migrasi 049 - migrasi DATA, bukan skema: step wizard 'citizen_data'
           dihapus (digabung ke 'housing_family_detail'), dan draft lama yang
           sempat berhenti tepat di step itu dipindah ke 'housing_family'
           supaya tidak macet (guard Warga::STEPS menolak step yang sudah
           tidak dikenal). Tidak ada field_exists() di sini - yang diperiksa
           ZERO baris tersisa, bukan keberadaan kolom. */
        if (in_array('sf_penilaian_perumahan', $tables, TRUE)) {
            $macet = (int) $this->db->where('langkah_sekarang', 'citizen_data')->count_all_results('sf_penilaian_perumahan');
            echo 'draft macet di citizen_data (migrasi 049, harus 0): '
                .($macet === 0 ? 'AMAN' : $macet.' BARIS MACET - warga ini akan gagal maju di wizard')."\n";
        }

        // Migrasi 050 - laporan akhir KKN. Kolom, bukan tabel.
        $bathroom = $this->db->query("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'sf_penilaian_perumahan' AND COLUMN_NAME = 'penggunaan_kamar_mandi'")->row_array();
        echo 'sf_penilaian_perumahan.penggunaan_kamar_mandi (migrasi 057): '
            .($bathroom && $bathroom['DATA_TYPE'] === 'varchar'
                && (int) $bathroom['CHARACTER_MAXIMUM_LENGTH'] === 20 && $bathroom['IS_NULLABLE'] === 'YES'
                ? 'ADA, VARCHAR(20) NULL' : 'HILANG ATAU BENTUK TIDAK SESUAI')."\n";
        echo 'kkn_magang_pendaftaran.file_laporan_akhir (migrasi 050): '
            .($this->db->field_exists('file_laporan_akhir', 'kkn_magang_pendaftaran')
                ? 'ADA' : 'HILANG - unggah laporan akhir KKN akan fatal')."\n";
        $matrix = $this->db->query("SELECT DATA_TYPE, IS_NULLABLE
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'sf_penilaian_perumahan' AND COLUMN_NAME = 'matriks_awal_ciphertext'")->row_array();
        echo 'sf_penilaian_perumahan.matriks_awal_ciphertext (migrasi 058): '
            .($matrix && $matrix['DATA_TYPE'] === 'mediumtext' && $matrix['IS_NULLABLE'] === 'YES'
                ? 'ADA, MEDIUMTEXT NULL' : 'HILANG ATAU BENTUK TIDAK SESUAI')."\n";
        // Migrasi 059/060 - sesi tunggal, validasi ID sesi, dan sandi 90 hari.
        foreach (['sesi_aktif_hash', 'sesi_aktif_id_hash', 'sesi_aktif_at', 'sandi_diganti_at', 'sandi_kedaluwarsa_at'] as $kolom) {
            echo 'usr_akun.'.$kolom.' (migrasi '.($kolom === 'sesi_aktif_id_hash' ? '060' : '059').'): '.
                ($this->db->field_exists($kolom, 'usr_akun') ? 'ADA' : 'HILANG - kontrol autentikasi belum aktif')."\n";
        }
        // Migrasi 061 (link dokumentasi KKN) dan migrasi 062 (tanggal sertifikat KKN oleh admin).
        // Migrasi 063 - dokumen Bank Data unggahan admin.
        echo 'sf_bank_data_dokumen (migrasi 063): '.($this->db->table_exists('sf_bank_data_dokumen') ? 'ADA' : 'HILANG')."
";
        // Migrasi 064 - cermin data SIMPERUM (hanya NIK terdaftar, diisi dari GET).
        echo 'sf_data_simperum (migrasi 064): '.($this->db->table_exists('sf_data_simperum') ? 'ADA' : 'HILANG')."\n";
        // Migrasi 065 - default asosiasi SQL NULL, bukan string 'NULL' peninggalan 051.
        $salah = (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'asosiasi' AND COLUMN_DEFAULT = \"'NULL'\"")->row('n');
        echo 'default asosiasi (migrasi 065): '.($salah ? $salah." kolom masih DEFAULT 'NULL'" : 'NULL')."\n";
        // Migrasi 066 - direktori SRP2 bertaut akun: kolom, UNIQUE user_id, dan FK-nya.
        foreach (['user_id', 'foto_profil', 'nib', 'no_keanggotaan', 'no_whatsapp', 'email_kontak'] as $kolom) {
            echo 'srp2_direktori_pengembang.'.$kolom.' (migrasi 066): '.
                ($this->db->field_exists($kolom, 'srp2_direktori_pengembang') ? 'ADA' : 'HILANG')."\n";
        }
        $fk066 = (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'srp2_direktori_pengembang'
              AND CONSTRAINT_NAME IN ('fk_srp2_direktori_user', 'uq_srp2_direktori_user')")->row('n');
        echo 'srp2 tautan akun UNIQUE + FK (migrasi 066): '.($fk066 === 2 ? 'TERPASANG' : 'TIDAK LENGKAP ('.$fk066.'/2)')."\n";
        // Migrasi 067 - PII antrean & NIK SRP2 terenkripsi: kolom polos HARUS hilang, pasangan
        // terenkripsi + indeks sidik HARUS ada. Hanya hitungan yang dicetak, tidak pernah nilai.
        $bentuk067 = [
            'sf_antrean_pengajuan' => [['nik_pengaju', 'nama_lengkap', 'data_simperum_json', 'data_survey_json'],
                ['nik_pengaju_ciphertext', 'nik_pengaju_lookup_hash', 'nama_lengkap_ciphertext', 'data_simperum_json_ciphertext', 'data_survey_json_ciphertext'],
                'idx_sf_queue_nik_lookup'],
            'srp2_pengajuan' => [['nik_ktp'], ['nik_ktp_ciphertext', 'nik_ktp_lookup_hash'], 'uq_srp2_registration_nik'],
        ];
        foreach ($bentuk067 as $tabel => [$polos, $sandi, $indeks]) {
            $sisa = array_filter($polos, function ($k) use ($tabel) { return $this->db->field_exists($k, $tabel); });
            $kurang = array_filter($sandi, function ($k) use ($tabel) { return ! $this->db->field_exists($k, $tabel); });
            $ada_indeks = (int) $this->db->query("SELECT COUNT(*) n FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?", [$tabel, $indeks])->row('n') > 0;
            echo $tabel.' PII terenkripsi (migrasi 067): '.( ! $sisa && ! $kurang && $ada_indeks
                ? 'TERPASANG, '.(int) $this->db->where($sandi[0].' IS NOT NULL', NULL, FALSE)->count_all_results($tabel).' baris berciphertext'
                : 'BELUM ('.($sisa ? 'kolom polos masih ada: '.implode(',', $sisa).'; ' : '')
                    .($kurang ? 'kolom hilang: '.implode(',', $kurang).'; ' : '').($ada_indeks ? '' : 'indeks '.$indeks.' hilang').')')."\n";
        }
        // Migrasi 068 - satu charset/collation: setiap tabel dan kolom string utf8mb4_unicode_ci.
        // Kolom ascii (ascii_bin, migrasi 052/053) pengecualian disengaja dan hanya dihitung.
        $c068 = $this->db->query("SELECT
            (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE') t,
            (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
                AND TABLE_COLLATION <> 'utf8mb4_unicode_ci') ts,
            (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_NAME IS NOT NULL
                AND CHARACTER_SET_NAME <> 'ascii') k,
            (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_NAME IS NOT NULL
                AND CHARACTER_SET_NAME <> 'ascii' AND COLLATION_NAME <> 'utf8mb4_unicode_ci') ks,
            (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND CHARACTER_SET_NAME = 'ascii') ka,
            (SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()) d")->row();
        echo 'charset/collation (migrasi 068): '.((int) $c068->ts === 0 && (int) $c068->ks === 0
            ? 'SERAGAM utf8mb4_unicode_ci ('.$c068->t.' tabel, '.$c068->k.' kolom string; '.$c068->ka.' kolom ascii disengaja)'
            : 'BELUM ('.$c068->ts.' dari '.$c068->t.' tabel dan '.$c068->ks.' dari '.$c068->k.' kolom menyimpang)')
            .'; default database '.$c068->d."\n";
        // Migrasi 069 - FK yang tadinya diandaikan kode + perapian indeks usr_akun. Daftar FK/indeks
        // dibaca dari berkas migrasinya sendiri supaya diagnostik ini tidak bisa menyimpang darinya.
        require_once APPPATH.'migrations/20260701000069_fk_indeks_integritas.php';
        $fk069 = []; $kurang069 = [];
        foreach ($this->db->query("SELECT CONSTRAINT_NAME n, DELETE_RULE d FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()")->result() as $r) { $fk069[$r->n] = $r->d; }
        foreach (Migration_Fk_indeks_integritas::FK as $nama => $def) {
            if (($fk069[$nama] ?? NULL) !== $def[4]) { $kurang069[] = $nama.(isset($fk069[$nama]) ? '='.$fk069[$nama] : ' hilang'); }
        }
        $idx069 = [];
        foreach ($this->db->query("SELECT TABLE_NAME t, INDEX_NAME n FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() GROUP BY TABLE_NAME, INDEX_NAME")->result() as $r) { $idx069[$r->t.'.'.$r->n] = TRUE; }
        // Nama tabel di konstanta 069 adalah nama sebelum migrasi 072; diterjemahkan lewat peta 072.
        require_once APPPATH.'migrations/20260701000072_penamaan_indonesia.php';
        foreach (Migration_Fk_indeks_integritas::INDEKS as $nama => $def) {
            if ( ! isset($idx069[Migration_Penamaan_indonesia::tabel($def[0]).'.'.$nama])) { $kurang069[] = 'indeks '.$nama.' hilang'; }
        }
        if (isset($idx069['usr_akun.idx_users_email'])) { $kurang069[] = 'indeks kembar idx_users_email masih ada'; }
        if ( ! isset($idx069['usr_akun.email'])) { $kurang069[] = 'UNIQUE email hilang'; }
        echo 'FK + indeks integritas (migrasi 069): '.($kurang069
            ? 'BELUM ('.implode('; ', $kurang069).')'
            : 'TERPASANG ('.count(Migration_Fk_indeks_integritas::FK).' FK, '.count(Migration_Fk_indeks_integritas::INDEKS).' indeks, email UNIQUE tunggal)')."\n";
        // Migrasi 070 - satu sumber data perusahaan: kolom perusahaan usr_akun dibuang.
        $sisa070 = array_values(array_filter(['nama_perusahaan', 'alamat_kantor', 'telp_kantor'],
            function ($k) { return $this->db->field_exists($k, 'usr_akun'); }));
        echo 'sumber tunggal perusahaan (migrasi 070): '.($sisa070 ? 'BELUM (usr_akun masih punya '.implode(', ', $sisa070).')'
            : 'TERPASANG (usr_akun tanpa kolom perusahaan; '.$this->db->where('user_id IS NOT NULL', NULL, FALSE)
                ->count_all_results('srp2_direktori_pengembang').' baris direktori tertaut akun)')."\n";
        // Migrasi 071 - tabel mati dibuang + CHECK kosakata status. Daftarnya dari konstanta migrasi.
        require_once APPPATH.'migrations/20260701000071_status_tertutup_tabel_mati.php';
        $kurang071 = array_map(function ($t) { return $t.' masih ada'; },
            array_values(array_filter(array_keys(Migration_Status_tertutup_tabel_mati::TABEL_MATI), [$this->db, 'table_exists'])));
        $cek071 = array_column($this->db->query("SELECT CONSTRAINT_NAME n FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'CHECK'")->result_array(), 'n');
        foreach (array_keys(Migration_Status_tertutup_tabel_mati::CEK) as $nama) {
            if ( ! in_array($nama, $cek071, TRUE)) { $kurang071[] = 'CHECK '.$nama.' hilang'; }
        }
        echo 'status tertutup + tabel mati (migrasi 071): '.($kurang071 ? 'BELUM ('.implode('; ', $kurang071).')'
            : 'TERPASANG ('.count(Migration_Status_tertutup_tabel_mati::CEK).' CHECK, '
                .count(Migration_Status_tertutup_tabel_mati::TABEL_MATI).' tabel mati tidak ada)')."\n";
        // Migrasi 072 - nama tabel/kolom Bahasa Indonesia + COMMENT setiap tabel. Daftar dari konstanta migrasinya.
        $kurang072 = [];
        foreach (Migration_Penamaan_indonesia::TABEL as $lama => $baru) {
            if ($this->db->table_exists($lama)) { $kurang072[] = 'tabel lama '.$lama.' masih ada'; }
            if ( ! $this->db->table_exists($baru)) { $kurang072[] = 'tabel '.$baru.' hilang'; }
        }
        $n_kolom072 = 0;
        foreach (Migration_Penamaan_indonesia::KOLOM as $lama => $peta) {
            $t = Migration_Penamaan_indonesia::tabel($lama);
            $ada = $this->db->table_exists($t) ? $this->db->list_fields($t) : [];
            foreach ($peta as $k_lama => $k_baru) {
                $n_kolom072++;
                if ( ! in_array($k_baru, $ada, TRUE)) { $kurang072[] = $t.'.'.$k_baru.' hilang'; }
                if ($k_lama !== $k_baru && in_array($k_lama, $ada, TRUE)) { $kurang072[] = $t.'.'.$k_lama.' masih ada'; }
            }
        }
        $tanpa_komentar = array_column($this->db->query("SELECT TABLE_NAME t FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_COMMENT = ''")->result_array(), 't');
        if ($tanpa_komentar) { $kurang072[] = count($tanpa_komentar).' tabel tanpa COMMENT ('.implode(', ', array_slice($tanpa_komentar, 0, 5)).')'; }
        echo 'nama Bahasa Indonesia (migrasi 072): '.($kurang072
            ? 'BELUM ('.implode('; ', array_slice($kurang072, 0, 10)).(count($kurang072) > 10 ? '; dan '.(count($kurang072) - 10).' lagi' : '').')'
            : 'TERPASANG ('.count(Migration_Penamaan_indonesia::TABEL).' tabel dan '.$n_kolom072.' kolom berganti nama, '
                .count(Migration_Penamaan_indonesia::KOMENTAR).' tabel ber-COMMENT)')."
";
        foreach (['link_dokumentasi' => '061', 'tanggal_sertifikat' => '062'] as $kolom => $no) {
            echo 'kkn_magang_pendaftaran.'.$kolom.' (migrasi '.$no.'): '.
                ($this->db->field_exists($kolom, 'kkn_magang_pendaftaran') ? 'ADA' : 'HILANG')."\n";
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
        $rekaman_id = NULL;
        $other_snapshot_id = NULL;
        $penilaian_id = NULL;
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

            $this->db->insert('usr_akun', [
                'email' => "uji_warga_r1_{$stamp}@example.test",
                'kata_sandi' => password_hash('UjiWargaR1!', PASSWORD_BCRYPT),
                'nama' => 'Warga Simulasi R1',
                'nama_pengguna' => "uji_warga_r1_{$stamp}",
                'peran' => 'warga',
                'status' => 'active',
                'profil_lengkap' => 1,
                'kabupaten_id' => 3374,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $user_id = (int) $this->db->insert_id();
            $check($user_id > 0, 'Akun sintetis dibuat');

            $profile = $this->Housing_assessment_model->save_profile($user_id, [
                'mode_sumber' => 'simulation',
                'nik' => $nik,
                'family_card_number' => '0000000000001001',
                'full_name' => 'Warga Simulasi R1',
                'address' => 'Alamat Sintetis R1',
                'birth_date' => '1980-01-01',
                'jenis_kelamin' => 'male',
                'desil_kesejahteraan' => 2,
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
                && strpos((string) $profile_row['nama_ciphertext'], 'Warga Simulasi') === FALSE,
                'Nama tidak tersimpan plaintext'
            );

            $snapshot = $this->Housing_assessment_model->store_source_snapshot(
                $nik,
                'simulation',
                'SIM-01',
                'found',
                ['fixture_id' => 'SIM-01', 'synthetic' => TRUE],
                ['versi_api' => 'simulation-v1', 'requested_by' => $user_id]
            );
            $rekaman_id = empty($snapshot['rekaman_id']) ? NULL : (int) $snapshot['rekaman_id'];
            $check(! empty($snapshot['success']), 'Snapshot simulasi tersimpan');

            $snapshot_row = $rekaman_id ? $this->db
                ->get_where('sf_rekaman_simperum', ['id' => $rekaman_id])
                ->row_array() : NULL;
            $check(
                $snapshot_row
                && strpos((string) $snapshot_row['muatan_ciphertext'], 'SIM-01') === FALSE
                && $this->encryption_lib->is_encrypted($snapshot_row['muatan_ciphertext']),
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
            $other_snapshot_id = empty($other_snapshot['rekaman_id'])
                ? NULL : (int) $other_snapshot['rekaman_id'];
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
                $rekaman_id
            );
            $penilaian_id = empty($draft['penilaian_id']) ? NULL : (int) $draft['penilaian_id'];
            $check(! empty($draft['success']), 'Draft assessment dibuat');

            $first_update = $this->Housing_assessment_model->update_owned_draft(
                $penilaian_id,
                $user_id,
                0,
                ['langkah_sekarang' => 'housing', 'kepemilikan_rumah' => 'owned']
            );
            $check(! empty($first_update['success']) && (int) $first_update['versi_kunci'] === 1,
                'Update pertama dengan versi_kunci 0 berhasil');

            $stale_update = $this->Housing_assessment_model->update_owned_draft(
                $penilaian_id,
                $user_id,
                0,
                ['langkah_sekarang' => 'structure']
            );
            $check(
                empty($stale_update['success']) && ($stale_update['code'] ?? '') === 'stale_or_not_owned',
                'Update kedua dengan lock lama ditolak'
            );

            $wrong_owner = $this->Housing_assessment_model->get_owned_assessment(
                $penilaian_id,
                $user_id + 999999
            );
            $check($wrong_owner === NULL, 'Assessment tidak terbaca sebagai user lain');
        } finally {
            if ($penilaian_id) {
                $this->db->delete('sf_penilaian_perumahan', ['id' => $penilaian_id]);
            }
            if ($rekaman_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $rekaman_id]);
            }
            if ($other_snapshot_id) {
                $this->db->delete('sf_rekaman_simperum', ['id' => $other_snapshot_id]);
            }
            // Profil tidak dihapus di sini dengan sengaja: FK
            // `fk_sf_citizen_profiles_user` sudah ON DELETE CASCADE, jadi baris
            // di bawah ini membawanya serta. Diperiksa, bukan diandaikan - lihat
            // check "Ikatan NIK uji dilepas" di bawah.
            if ($user_id) {
                $this->db->delete('usr_akun', ['id' => $user_id]);
            }
        }

        $leftovers = $this->db->like('email', 'uji_warga_r1_', 'after')
            ->count_all_results('usr_akun');
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

    public function simperum_probe($action = 'lookup', $nik_arg = NULL, $tgl_lahir = '1980-01-01')
    {
        if (ENVIRONMENT === 'production') {
            show_404();
            return;
        }

        /* NIK boleh dioper supaya probe ini juga bisa menguji mode `api` dengan
           NIK sungguhan, bukan hanya fixture simulasi. Tetap dev-only. */
        $nik = preg_match('/^[0-9]{16}$/', (string) $nik_arg) ? $nik_arg : '0000000000000001';
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
        echo json_encode($this->simperum_gateway->lookup($nik, $tgl_lahir)) . "\n";
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
            ->get('usr_akun')->row_array();
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
        $aktor = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('usr_akun')->row_array();
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
                    'path_privat' => 'uji/bnba-uji.pdf',
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
