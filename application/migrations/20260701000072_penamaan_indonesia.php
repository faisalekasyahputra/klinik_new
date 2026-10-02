<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penamaan Bahasa Indonesia: 14 tabel dan 181 kolom diganti nama, 45 tabel diberi COMMENT.
 *
 * Peta disetujui user (rencana penamaan ulang, Okt 2026). Aturannya: nama bisnis Bahasa
 * Indonesia; prefiks sf_ (warga dan perumahan), rd_, srp2_, usr_, sys_, kkn_, forum_, chat_
 * tetap; kolom yang punya padanan di sf_data_simperum memakai nama yang sama; PK selalu `id`,
 * penunjuk `<nama>_id` atau `<nama>_kode`; istilah teknis tetap Inggris (id, *_at, *_by,
 * *_ciphertext, *_lookup_hash, *_hash, *_json, mime_type, sha256, istilah Web Push, migrations).
 * NILAI di kolom tidak berubah.
 *
 * CARA: MariaDB 10.4 belum punya RENAME COLUMN, jadi tiap kolom diganti lewat CHANGE dengan
 * definisi yang DIBACA dari SHOW CREATE TABLE server itu sendiri saat migrasi berjalan (tipe,
 * NULL, DEFAULT, collation, COMMENT, AUTO_INCREMENT, ON UPDATE ikut apa adanya), jadi berlaku
 * sama di lokal 10.4 dan production 11.8. Ganti nama saja = perubahan metadata InnoDB.
 * FK, indeks, dan UNIQUE ikut kolomnya secara otomatis; NAMA constraint dan indeks sengaja
 * tidak diganti (mengganti nama FK berarti DROP+ADD yang memvalidasi ulang seluruh baris).
 * Dua CHECK migrasi 071 menyebut kolom yang diganti (ck_riwayat_antrean_dari/_ke): keduanya
 * dilepas sebelum CHANGE lalu dipasang lagi dengan ekspresi 071 untuk nama baru, supaya hasilnya
 * sama di server yang menyesuaikan CHECK sendiri (10.4) maupun yang tidak.
 *
 * KODE LAMA PATAH begitu up() jalan (semua query menyebut nama lama). Rilis: migrate lalu
 * push tanpa jeda; rollback: down() lalu kembalikan kode lama, lihat AGENTS.md catatan 072.
 * Kunci JSON tersimpan TIDAK diganti (lihat helpers/kunci_tersimpan_helper.php).
 *
 * up() dan down() idempoten per tabel: tabel yang sudah berganti nama, kolom yang sudah
 * berganti, dan CHECK yang sudah terpasang dilewati. PRA-CEK menolak sebelum DDL bila ada nama
 * baru yang sudah dipakai kolom lain.
 */
class Migration_Penamaan_indonesia extends CI_Migration {

    /** Tabel lama => tabel baru (14). */
    const TABEL = [
        'usr_users' => 'usr_akun',
        'usr_documents' => 'usr_dokumen',
        'usr_admin_module_privileges' => 'usr_hak_modul_admin',
        'sys_settings' => 'sys_pengaturan',
        'sys_rate_limits' => 'sys_batas_laju',
        'sys_push_subscriptions' => 'sys_langganan_notifikasi',
        'chat_rooms' => 'chat_ruang',
        'chat_messages' => 'chat_pesan',
        'forum_likes' => 'forum_suka',
        'sf_housing_queue' => 'sf_antrean_pengajuan',
        'sf_programs' => 'sf_program',
        'srp2_registrations' => 'srp2_pengajuan',
        'srp2_documents' => 'srp2_dokumen',
        'srp2_certified_developers' => 'srp2_direktori_pengembang',
    ];

    /** Tabel LAMA => [kolom lama => kolom baru] (181 kolom). */
    const KOLOM = [
        'usr_users' => [
            'name'                   => 'nama',
            'username'               => 'nama_pengguna',
            'password'               => 'kata_sandi',
            'avatar'                 => 'foto_profil',
            'email_token'            => 'token_email',
            'email_token_expiry'     => 'token_email_kedaluwarsa',
            'role'                   => 'peran',
            'profile_completed'      => 'profil_lengkap',
            'login_attempts'         => 'gagal_masuk',
            'locked_until'           => 'terkunci_sampai',
            'phone'                  => 'no_hp',
            'active_session_hash'    => 'sesi_aktif_hash',
            'active_session_id_hash' => 'sesi_aktif_id_hash',
            'active_session_at'      => 'sesi_aktif_at',
            'password_changed_at'    => 'sandi_diganti_at',
            'password_expires_at'    => 'sandi_kedaluwarsa_at',
        ],
        'usr_documents' => [
            'doc_type'  => 'jenis_dokumen',
            'file_name' => 'nama_berkas',
            'file_path' => 'path_berkas',
            'file_size' => 'ukuran_berkas',
        ],
        'usr_admin_module_privileges' => [
            'module_key' => 'kunci_modul',
            'allowed'    => 'diizinkan',
        ],
        'sys_settings' => [
            'key_name'  => 'kunci',
            'key_value' => 'nilai',
            'type'      => 'tipe',
        ],
        'sys_rate_limits' => [
            'limit_key'         => 'kunci',
            'window_started_at' => 'jendela_mulai_at',
            'failed_attempts'   => 'jumlah_gagal',
        ],
        'sys_push_subscriptions' => [
            'last_success_at' => 'terakhir_berhasil_at',
            'last_failure_at' => 'terakhir_gagal_at',
        ],
        'sys_jejak_audit' => [
            'actor_id'    => 'pelaku_id',
            'actor_email' => 'pelaku_email',
            'actor_role'  => 'pelaku_peran',
        ],
        'chat_rooms' => [
            'session_token' => 'token_sesi',
        ],
        'chat_messages' => [
            'chat_room_id' => 'ruang_id',
            'sender'       => 'pengirim',
            'message'      => 'pesan',
        ],
        'forum_diskusi' => [
            'id_diskusi'   => 'id',
            'nama_user'    => 'nama_pengguna',
            'email_user'   => 'email_pengguna',
            'ip_address'   => 'alamat_ip',
            'is_deleted'   => 'dihapus',
            'report_count' => 'jumlah_laporan',
            'view_count'   => 'jumlah_dilihat',
            'like_count'   => 'jumlah_suka',
        ],
        'forum_komentar' => [
            'id_komentar'  => 'id',
            'id_diskusi'   => 'diskusi_id',
            'reply_to'     => 'balasan_untuk_id',
            'ip_address'   => 'alamat_ip',
            'is_deleted'   => 'dihapus',
            'report_count' => 'jumlah_laporan',
            'like_count'   => 'jumlah_suka',
            'role'         => 'peran',
        ],
        'forum_janji_temu' => [
            'id_diskusi' => 'diskusi_id',
        ],
        'forum_laporan_komentar' => [
            'id_komentar' => 'komentar_id',
        ],
        'forum_likes' => [
            'target_type' => 'jenis_target',
        ],
        'aduan' => [
            'bidang' => 'bidang_kode',
        ],
        'rd_laporan' => [
            'current_step' => 'langkah_sekarang',
        ],
        'rd_perumahan_bnba' => [
            'private_path' => 'path_privat',
        ],
        'sf_bank_data_dokumen' => [
            'diunggah_oleh' => 'uploaded_by',
        ],
        'sf_housing_queue' => [
            'assessment_id'     => 'penilaian_id',
            'recommendation_id' => 'rekomendasi_id',
            'submission_key'    => 'kunci_pengajuan',
            'ticket_code'       => 'kode_tiket',
            'source_mode'       => 'mode_sumber',
        ],
        'sf_riwayat_keputusan_antrean' => [
            'queue_id'    => 'antrean_id',
            'from_status' => 'status_awal',
            'to_status'   => 'status_akhir',
            'note'        => 'catatan',
            'actor_id'    => 'pelaku_id',
        ],
        'sf_programs' => [
            'id_kategori'           => 'kategori_id',
            'batas_penghasilan_max' => 'batas_penghasilan_maks',
            'is_active'             => 'aktif',
            'badge'                 => 'lencana',
        ],
        'sf_rekomendasi_penilaian' => [
            'assessment_id'         => 'penilaian_id',
            'ruleset_version'       => 'versi_aturan',
            'eligibility_status'    => 'status_kelayakan',
            'reason_codes_json'     => 'kode_alasan_json',
            'input_snapshot_sha256' => 'masukan_sha256',
        ],
        'sf_berkas_penilaian' => [
            'assessment_id'            => 'penilaian_id',
            'file_kind'                => 'jenis_berkas',
            'private_path'             => 'path_privat',
            'original_name_ciphertext' => 'nama_asli_ciphertext',
            'size_bytes'               => 'ukuran_byte',
        ],
        'sf_rekaman_simperum' => [
            'source_mode'        => 'mode_sumber',
            'source_record_key'  => 'kunci_rekaman_sumber',
            'response_status'    => 'status_respons',
            'api_version'        => 'versi_api',
            'error_code'         => 'kode_galat',
            'payload_ciphertext' => 'muatan_ciphertext',
            'payload_sha256'     => 'muatan_sha256',
        ],
        'sf_data_simperum' => [
            'response_status' => 'status_respons',
            'source_mode'     => 'mode_sumber',
            'snapshot_id'     => 'rekaman_id',
        ],
        'sf_profil_warga' => [
            'source_mode'               => 'mode_sumber',
            'family_card_ciphertext'    => 'no_kk_ciphertext',
            'family_card_lookup_hash'   => 'no_kk_lookup_hash',
            'full_name_ciphertext'      => 'nama_ciphertext',
            'address_ciphertext'        => 'alamat_ciphertext',
            'phone_ciphertext'          => 'no_hp_ciphertext',
            'birth_date_ciphertext'     => 'tanggal_lahir_ciphertext',
            'tax_number_ciphertext'     => 'npwp_ciphertext',
            'gender_code'               => 'jenis_kelamin',
            'marital_status_code'       => 'status_perkawinan',
            'education_code'            => 'pendidikan',
            'occupation_code'           => 'pekerjaan',
            'employment_stability_code' => 'stabilitas_pekerjaan',
            'monthly_income'            => 'penghasilan_bulanan',
            'income_band_code'          => 'kelompok_penghasilan',
            'welfare_decile'            => 'desil_kesejahteraan',
            'has_savings'               => 'punya_tabungan',
            'self_help_capability_code' => 'mampu_swadaya',
            'self_help_amount'          => 'nilai_swadaya',
            'field_provenance_json'     => 'asal_isian_json',
        ],
        'sf_penilaian_perumahan' => [
            'citizen_profile_id'                => 'profil_warga_id',
            'previous_version_id'               => 'versi_sebelumnya_id',
            'assessment_track'                  => 'jalur_penilaian',
            'current_step'                      => 'langkah_sekarang',
            'version_no'                        => 'no_versi',
            'lock_version'                      => 'versi_kunci',
            'simperum_snapshot_id'              => 'rekaman_simperum_id',
            'source_mode'                       => 'mode_sumber',
            'profile_snapshot_ciphertext'       => 'salinan_profil_ciphertext',
            'field_provenance_json'             => 'asal_isian_json',
            'housing_status_code'               => 'kepemilikan_rumah',
            'land_title_code'                   => 'kepemilikan_lahan',
            'has_other_land'                    => 'tanah_lain',
            'has_other_house'                   => 'rumah_lain',
            'house_area_m2'                     => 'luas_rumah',
            'occupant_count'                    => 'jml_penghuni',
            'family_count'                      => 'jml_kk',
            'assistance_source_code'            => 'bantuan_perumahan',
            'assistance_year'                   => 'tahun_intervensi',
            'area_condition_code'               => 'kawasan_perumahan',
            'owns_candidate_land'               => 'punya_lahan_calon',
            'candidate_land_address_ciphertext' => 'alamat_lahan_calon_ciphertext',
            'candidate_land_title_code'         => 'status_lahan_calon',
            'candidate_land_origin_code'        => 'asal_lahan_calon',
            'land_owner_relationship_code'      => 'hubungan_pemilik_lahan',
            'land_length_m'                     => 'panjang_lahan_m',
            'land_width_m'                      => 'lebar_lahan_m',
            'land_area_m2'                      => 'luas_lahan_m2',
            'foundation_condition_code'         => 'kondisi_pondasi',
            'column_condition_code'             => 'kondisi_kolom',
            'beam_condition_code'               => 'kondisi_balok',
            'sloof_condition_code'              => 'kondisi_sloof',
            'ceiling_condition_code'            => 'kondisi_plafon',
            'roof_frame_condition_code'         => 'kondisi_rangka',
            'floor_material_code'               => 'bahan_lantai',
            'floor_condition_code'              => 'kondisi_lantai',
            'wall_material_code'                => 'bahan_dinding',
            'wall_condition_code'               => 'kondisi_dinding',
            'roof_material_code'                => 'bahan_atap',
            'roof_condition_code'               => 'kondisi_atap',
            'has_window'                        => 'ada_jendela',
            'has_ventilation'                   => 'ada_ventilasi',
            'water_source_code'                 => 'sumber_air',
            'has_bathroom_latrine'              => 'kamar_mandi',
            'latrine_type_code'                 => 'jenis_kloset',
            'feces_disposal_code'               => 'pembuangan_tinja',
            'septic_distance_code'              => 'jarak_septic_tank',
            'lighting_source_code'              => 'penerangan',
            'cooking_fuel_code'                 => 'bahan_bakar_masak',
            'location_lat_ciphertext'           => 'geo_lat_ciphertext',
            'location_lng_ciphertext'           => 'geo_lng_ciphertext',
            'location_accuracy_m'               => 'akurasi_lokasi_m',
            'matrix_land_ownership_code'        => 'matriks_kepemilikan_lahan',
            'matrix_current_housing_code'       => 'matriks_rumah_sekarang',
            'matrix_environment_condition_code' => 'matriks_kondisi_lingkungan',
            'matrix_occupation_finance_code'    => 'matriks_pekerjaan_keuangan',
            'matrix_marital_family_code'        => 'matriks_status_keluarga',
            'matrix_income_code'                => 'matriks_penghasilan',
            'matrix_dtks_status'                => 'matriks_status_dtks',
            'bathroom_usage_code'               => 'penggunaan_kamar_mandi',
            'preliminary_matrix_ciphertext'     => 'matriks_awal_ciphertext',
        ],
        'srp2_registrations' => [
            'certified_developer_id' => 'pengembang_id',
        ],
        'srp2_documents' => [
            'registration_id' => 'pengajuan_id',
            'document_key'    => 'kunci_dokumen',
            'original_name'   => 'nama_asli',
            'stored_name'     => 'nama_simpan',
            'file_size'       => 'ukuran_berkas',
        ],
    ];

    /** Tabel (nama BARU) => COMMENT tabel. Semua 45 tabel. */
    const KOMENTAR = [
        'aduan'                        => 'Master layanan: aduan warga beserta bidang penanganan (bidang_kode, diisi lewat triase superadmin), status, dan catatan petugas.',
        'bidang'                       => 'Master organisasi: lima bidang Disperakim Jateng (kode, nama); sumber tunggal daftar bidang untuk aduan, akun admin bidang, dan magang.',
        'chat_pesan'                   => 'chat_ = obrolan widget situs. Pesan per ruang obrolan (pengirim, isi pesan).',
        'chat_ruang'                   => 'chat_ = obrolan widget situs. Ruang obrolan per token sesi peramban beserta statusnya (bot atau petugas).',
        'forum_diskusi'                => 'forum_ = konsultasi warga. Topik konsultasi (privat untuk pemilik dan petugas) beserta penghitung suka, laporan, dan dilihat.',
        'forum_janji_temu'             => 'forum_ = konsultasi warga. Pengajuan janji temu tatap muka untuk satu topik konsultasi beserta alur statusnya.',
        'forum_komentar'               => 'forum_ = konsultasi warga. Balasan pada topik konsultasi, boleh berutas lewat balasan_untuk_id.',
        'forum_laporan_komentar'       => 'forum_ = konsultasi warga. Buku laporan komentar, satu baris per pelapor per komentar (UNIQUE).',
        'forum_suka'                   => 'forum_ = konsultasi warga. Tanda suka per akun pada topik atau komentar (jenis_target, target_id).',
        'kabupaten'                    => 'Master wilayah: 35 kabupaten/kota Jawa Tengah, id = kode wilayah Kemendagri 4 digit.',
        'kkn_magang_bidang'            => 'kkn_ = KKN dan magang mahasiswa. Kuota dan status buka magang per bidang.',
        'kkn_magang_pendaftaran'       => 'kkn_ = KKN dan magang mahasiswa. Pengajuan KKN atau magang beserta berkas surat, keputusan admin, dan tinjauan bidang.',
        'kkn_magang_posisi'            => 'kkn_ = KKN dan magang mahasiswa. Posisi magang yang dibuka tiap bidang beserta kuotanya.',
        'kkn_magang_slot'              => 'kkn_ = KKN dan magang mahasiswa. Slot bulan magang yang dibuka per bidang dan tahun.',
        'kkn_peserta'                  => 'kkn_ = KKN dan magang mahasiswa. Daftar peserta (NIM, nama) satu pengajuan KKN untuk sertifikat.',
        'migrations'                   => 'Catatan versi skema milik pustaka migrasi CodeIgniter; satu baris berisi versi terakhir yang terpasang.',
        'psu_serah_terima'             => 'psu_ = prasarana, sarana, dan utilitas perumahan. Catatan serah terima PSU dari pengembang ke pemerintah daerah.',
        'rd_kawasan_intervensi'        => 'rd_ = rekam data laporan kabupaten/kota. Kegiatan intervensi kawasan permukiman dalam satu laporan.',
        'rd_kawasan_ringkasan'         => 'rd_ = rekam data laporan kabupaten/kota. Ringkasan penanganan dan progres kawasan permukiman per laporan.',
        'rd_laporan'                   => 'rd_ = rekam data laporan kabupaten/kota. Kepala laporan triwulan per kabupaten, ranah, dan tahun beserta status tinjauan.',
        'rd_perumahan_baris'           => 'rd_ = rekam data laporan kabupaten/kota. Rencana dan realisasi unit serta anggaran per sumber dana dan program.',
        'rd_perumahan_bnba'            => 'rd_ = rekam data laporan kabupaten/kota. Lampiran BNBA (by name by address) laporan perumahan, berkas privat.',
        'rd_perumahan_program'         => 'rd_ = rekam data laporan kabupaten/kota. Program perumahan yang dilaporkan dalam satu laporan.',
        'sf_antrean_pengajuan'         => 'sf_ = warga dan perumahan. Antrean pengajuan bantuan perumahan warga (tiket, program, status verifikasi kab/kota).',
        'sf_bank_data_dokumen'         => 'sf_ = warga dan perumahan. Dokumen Bank Data yang diunggah admin untuk halaman publik.',
        'sf_berkas_penilaian'          => 'sf_ = warga dan perumahan. Berkas bukti foto satu penilaian rumah warga, disimpan privat.',
        'sf_data_simperum'             => 'sf_ = warga dan perumahan. Cermin data RTLH SIMPERUM per NIK akun warga, hanya disimpan dari GET dan tidak pernah dikirim ke SIMPERUM.',
        'sf_penilaian_perumahan'       => 'sf_ = warga dan perumahan. Draft dan versi penilaian rumah warga dari wizard pendataan (kondisi rumah, lahan, sanitasi, matriks).',
        'sf_profil_warga'              => 'sf_ = warga dan perumahan. Profil sosial ekonomi warga terikat NIK; identitas disimpan terenkripsi.',
        'sf_program'                   => 'sf_ = warga dan perumahan. Katalog program bantuan perumahan (kode, syarat, etalase beranda, status aktif).',
        'sf_program_kategori'          => 'sf_ = warga dan perumahan. Kategori pengelompokan katalog program.',
        'sf_rekaman_simperum'          => 'sf_ = warga dan perumahan. Rekaman respons sumber SIMPERUM per pencarian NIK, muatan terenkripsi dan berbatas waktu.',
        'sf_rekomendasi_penilaian'     => 'sf_ = warga dan perumahan. Hasil evaluasi kelayakan program untuk satu penilaian per versi aturan.',
        'sf_riwayat_keputusan_antrean' => 'sf_ = warga dan perumahan. Riwayat perubahan status antrean pengajuan beserta catatan dan pelakunya.',
        'srp2_asosiasi'                => 'srp2_ = Sertifikasi Registrasi Pengembang Perumahan. Master asosiasi pengembang.',
        'srp2_direktori_pengembang'    => 'srp2_ = Sertifikasi Registrasi Pengembang Perumahan. Direktori publik pengembang tersertifikasi, opsional tertaut akun.',
        'srp2_dokumen'                 => 'srp2_ = Sertifikasi Registrasi Pengembang Perumahan. Berkas persyaratan yang diunggah untuk satu pengajuan.',
        'srp2_pengajuan'               => 'srp2_ = Sertifikasi Registrasi Pengembang Perumahan. Pengajuan sertifikasi pengembang (data perusahaan dan keputusan admin).',
        'sys_batas_laju'               => 'sys_ = sistem. Ember pembatas laju per kunci (hash kebijakan dan dimensi), bukan data pribadi.',
        'sys_jejak_audit'              => 'sys_ = sistem. Jejak audit tindakan petugas dan akses data pribadi.',
        'sys_langganan_notifikasi'     => 'sys_ = sistem. Langganan Web Push per perangkat akun staf dan warga.',
        'sys_pengaturan'               => 'sys_ = sistem. Pengaturan konten situs berpola kunci dan nilai.',
        'usr_akun'                     => 'usr_ = akun pengguna. Akun login semua peran beserta cakupan wilayah atau bidang, sesi tunggal, dan masa berlaku sandi.',
        'usr_dokumen'                  => 'usr_ = akun pengguna. Berkas onboarding akun, disimpan privat.',
        'usr_hak_modul_admin'          => 'usr_ = akun pengguna. Hak modul per akun admin ter-scope yang diatur superadmin.',
    ];

    /** CHECK migrasi 071 yang menyebut kolom yang diganti nama di sini. */
    const CEK_KOLOM = ['ck_riwayat_antrean_dari', 'ck_riwayat_antrean_ke'];

    /** Nama tabel sesudah 072 untuk nama tabel lama (atau nama yang sama bila tidak berubah). */
    public static function tabel($lama)
    {
        return self::TABEL[$lama] ?? $lama;
    }

    /** Nama kolom sesudah 072 untuk pasangan (tabel lama, kolom lama). */
    public static function kolom($tabel_lama, $kolom)
    {
        return self::KOLOM[$tabel_lama][$kolom] ?? $kolom;
    }

    public function up()
    {
        $this->tanpa_debug(function () {
            $this->pra_cek(TRUE);
            foreach (self::KOMENTAR as $baru => $komentar) {
                $lama = array_search($baru, self::TABEL, TRUE) ?: $baru;
                $this->satu_tabel($lama, $baru, self::KOLOM[$lama] ?? [], $komentar);
            }
        });
    }

    public function down()
    {
        $this->tanpa_debug(function () {
            $this->pra_cek(FALSE);
            foreach (array_keys(self::KOMENTAR) as $baru) {
                $lama = array_search($baru, self::TABEL, TRUE) ?: $baru;
                $this->satu_tabel($baru, $lama, array_flip(self::KOLOM[$lama] ?? []), '');
            }
        });
    }

    /**
     * Ganti nama kolom $peta (asal => tujuan) dan COMMENT tabel, lalu ganti nama tabel $asal => $tujuan.
     * Tabel yang sudah bernama $tujuan dikerjakan di tempat (lanjutan migrasi yang terputus).
     */
    private function satu_tabel($asal, $tujuan, array $peta, $komentar)
    {
        $nama = $this->db->table_exists($asal) ? $asal : $tujuan;
        $def = $this->definisi($nama);
        $ubah = [];
        foreach ($peta as $dari => $ke) {
            if (isset($def[$dari])) {
                $ubah[] = 'CHANGE `' . $dari . '` `' . $ke . '` ' . $def[$dari];
            }
        }
        // Tabel riwayat tidak berganti nama, hanya kolom status_awal/status_akhir-nya.
        $cek = $nama === 'sf_riwayat_keputusan_antrean' ? $this->cek_071() : [];
        if ($ubah) {
            foreach (array_keys($cek) as $c) {
                if ($this->ada_cek($nama, $c)) { $this->wajib("ALTER TABLE `$nama` DROP CONSTRAINT `$c`"); }
            }
        }
        $ubah[] = 'COMMENT=' . $this->db->escape($komentar);
        $this->wajib("ALTER TABLE `$nama` " . implode(', ', $ubah));
        foreach ($cek as $c => $kolom_lama) {
            if ( ! $this->ada_cek($nama, $c)) {
                $kolom = $this->kolom_cek($nama, $kolom_lama);
                $this->wajib("ALTER TABLE `$nama` ADD CONSTRAINT `$c` CHECK ("
                    . Migration_Status_tertutup_tabel_mati::ekspresi($kolom, Migration_Status_tertutup_tabel_mati::CEK[$c][2]) . ')');
            }
        }
        if ($nama !== $tujuan) {
            $this->wajib("RENAME TABLE `$nama` TO `$tujuan`");
        }
    }

    /** CHECK 071 pada tabel riwayat => kolom LAMA yang diperiksanya. */
    private function cek_071()
    {
        require_once APPPATH . 'migrations/20260701000071_status_tertutup_tabel_mati.php';
        $hasil = [];
        foreach (self::CEK_KOLOM as $c) { $hasil[$c] = Migration_Status_tertutup_tabel_mati::CEK[$c][1]; }
        return $hasil;
    }

    /** Nama kolom yang berlaku SEKARANG di tabel itu untuk kolom lama $kolom_lama (sesudah CHANGE). */
    private function kolom_cek($tabel, $kolom_lama)
    {
        $baru = self::kolom('sf_riwayat_keputusan_antrean', $kolom_lama);
        return $this->db->field_exists($baru, $tabel) ? $baru : $kolom_lama;
    }

    /** Kolom => definisi lengkap tanpa nama, dibaca dari SHOW CREATE TABLE. */
    private function definisi($tabel)
    {
        $q = $this->db->query("SHOW CREATE TABLE `$tabel`");
        if ($q === FALSE) { throw new RuntimeException('Migrasi 072 gagal membaca ' . $tabel . ': ' . $this->db->error()['message']); }
        $def = [];
        foreach (explode("\n", (string) $q->row_array()['Create Table']) as $baris) {
            if (preg_match('/^\s+`([^`]+)` (.*?),?$/', $baris, $m)) { $def[$m[1]] = $m[2]; }
        }
        return $def;
    }

    /**
     * Arah naik ($naik): setiap tabel lama/baru harus ada, dan tiap kolom tujuan tidak boleh sudah
     * dipakai kolom lain yang bukan hasil penggantian ini. Pesan hanya menyebut nama, tanpa data.
     */
    private function pra_cek($naik)
    {
        $masalah = [];
        foreach (array_keys(self::KOMENTAR) as $baru) {
            $lama = array_search($baru, self::TABEL, TRUE) ?: $baru;
            [$asal, $tujuan] = $naik ? [$lama, $baru] : [$baru, $lama];
            $nama = $this->db->table_exists($asal) ? $asal : ($this->db->table_exists($tujuan) ? $tujuan : NULL);
            if ($nama === NULL) { $masalah[] = "tabel $asal tidak ada"; continue; }
            if ($asal !== $tujuan && $this->db->table_exists($asal) && $this->db->table_exists($tujuan)) {
                $masalah[] = "tabel $asal dan $tujuan sama-sama ada";
            }
            $peta = $naik ? (self::KOLOM[$lama] ?? []) : array_flip(self::KOLOM[$lama] ?? []);
            $def = $this->definisi($nama);
            foreach ($peta as $dari => $ke) {
                if (isset($def[$dari]) && isset($def[$ke]) && ! isset($peta[$ke])) { $masalah[] = "$nama.$ke sudah dipakai"; }
                if ( ! isset($def[$dari]) && ! isset($def[$ke])) { $masalah[] = "$nama.$dari tidak ada"; }
            }
        }
        if ($masalah) {
            throw new RuntimeException('Migrasi 072 ditolak: ' . implode('; ', $masalah));
        }
    }

    private function ada_cek($tabel, $nama)
    {
        $q = $this->db->query("SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = " . $this->db->escape($tabel) . " AND CONSTRAINT_NAME = " . $this->db->escape($nama)
            . " AND CONSTRAINT_TYPE = 'CHECK'");
        if ($q === FALSE) { throw new RuntimeException('Migrasi 072 gagal membaca CHECK: ' . $this->db->error()['message']); }
        return (int) $q->row('n') > 0;
    }

    /** db_debug mati supaya galat jadi pengecualian berpesan, dipulihkan walau gagal (pola 069-071). */
    private function tanpa_debug(callable $kerja)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        try { $kerja(); } finally { $this->db->db_debug = $debug; }
    }

    private function wajib($sql)
    {
        if ($this->db->query($sql) === FALSE) {
            throw new RuntimeException('Migrasi 072 gagal: ' . preg_replace('/\s+/', ' ', mb_substr($sql, 0, 300)) . ' (' . $this->db->error()['message'] . ')');
        }
    }
}
