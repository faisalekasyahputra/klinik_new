<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Housing_assessment_model extends CI_Model {

    private const SOURCE_MODES = ['simulation', 'api', 'manual'];
    private const TRACKS = ['undetermined', 'existing_house', 'candidate_land', 'financing'];
    private const SNAPSHOT_STATUSES = ['found', 'not_found', 'error'];
    private const EVIDENCE_KINDS = ['self_photo','house_front_photo','house_side_photo','roof_photo','floor_photo','wall_photo','latrine_photo','land_photo','candidate_land_photo','land_transfer_proof','recipient_photo','id_card_photo','family_card_photo','land_owner_family_card_photo'];

    private const PROFILE_FIELDS = [
        'jenis_kelamin', 'status_perkawinan', 'pendidikan',
        'pekerjaan', 'stabilitas_pekerjaan', 'penghasilan_bulanan',
        'kelompok_penghasilan', 'desil_kesejahteraan',
        'punya_tabungan', 'mampu_swadaya', 'nilai_swadaya',
    ];

    private const DRAFT_FIELDS = [
        'langkah_sekarang', 'jalur_penilaian', 'kepemilikan_rumah', 'kepemilikan_lahan',
        'tanah_lain', 'rumah_lain', 'luas_rumah',
        'jml_penghuni', 'jml_kk', 'bantuan_perumahan',
        'tahun_intervensi', 'kawasan_perumahan', 'punya_lahan_calon',
        'status_lahan_calon', 'asal_lahan_calon',
        'hubungan_pemilik_lahan', 'panjang_lahan_m', 'lebar_lahan_m',
        'luas_lahan_m2', 'kondisi_pondasi', 'kondisi_kolom',
        'kondisi_balok', 'kondisi_sloof',
        'kondisi_plafon', 'kondisi_rangka',
        'bahan_lantai', 'kondisi_lantai', 'bahan_dinding',
        'kondisi_dinding', 'bahan_atap', 'kondisi_atap',
        'ada_jendela', 'ada_ventilasi', 'sumber_air',
        'kamar_mandi', 'penggunaan_kamar_mandi', 'jenis_kloset', 'pembuangan_tinja',
        'jarak_septic_tank', 'penerangan', 'bahan_bakar_masak',
        'akurasi_lokasi_m',
        // 5 field xlsx "MATRIKS VARIABEL PENENTUAN PROGRAM PERUMAHAN.xlsx"
        // Sheet4 (kolom D/E/F/G/I bertanda '*') - permintaan user 23 Agt
        // 2026, migrasi 045. Field BARU, bukan menimpa kepemilikan_rumah/
        // kepemilikan_lahan/kawasan_perumahan yang sudah ada - lihat
        // docblock migrasi 045 untuk alasannya.
        'matriks_kepemilikan_lahan', 'matriks_rumah_sekarang',
        'matriks_kondisi_lingkungan', 'matriks_pekerjaan_keuangan',
        'matriks_status_keluarga',
        // Ke-7, migrasi 047 (menyusul terpisah 23 Agt 2026) - kolom A xlsx
        // ("Pendapatan / Gaji"), BUKAN menimpa kelompok_penghasilan yang sudah
        // ada di step "Data Warga" - lihat docblock migrasi 047.
        'matriks_penghasilan',
        // Ke-8, migrasi 048 - kolom C xlsx ("Status DTKS"), dibutuhkan
        // mesin pencocokan 20 baris matriks (Matriks_program_ruleset).
        'matriks_status_dtks',
    ];

    /** Isian logis => kolom terenkripsinya di sf_penilaian_perumahan. */
    private const ISIAN_TERENKRIPSI = [
        'candidate_land_address' => 'alamat_lahan_calon_ciphertext',
        'location_lat' => 'geo_lat_ciphertext',
        'location_lng' => 'geo_lng_ciphertext',
        'preliminary_matrix' => 'matriks_awal_ciphertext',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('encryption_lib');
        $this->load->library('Warga_ruleset');
        $this->load->helper('housing_queue');
    }

    /** Satu pesan untuk NIK milik akun lain; tidak pernah menyebut akun pemiliknya. */
    public const PESAN_NIK_TERIKAT = 'NIK ini sudah terhubung dengan akun lain. Jika ini NIK Anda, sampaikan melalui menu Aduan agar Dinas Perakim dapat memeriksanya.';

    /**
     * Penjaga satu NIK satu akun. NULL bila $user_id boleh memakai NIK ini, selain itu hasil fail().
     * NIK juga terkunci ke akun lewat usr_akun.nik_lookup_hash (onboarding Auth dan Pengaturan):
     * pemilik yang belum mengisi pendataan belum punya baris sf_profil_warga (UAT warga #8).
     */
    public function cek_ikatan_nik($user_id, $nik_hash)
    {
        $user_id = (int) $user_id;
        // Keadaan akun sendiri lebih dulu: jawabannya tidak bergantung pada akun lain mana pun.
        $existing = $this->db->select('nik_lookup_hash')
            ->get_where('sf_profil_warga', ['user_id' => $user_id])
            ->row_array();
        if ($existing && ! hash_equals((string) $existing['nik_lookup_hash'], (string) $nik_hash)) {
            return $this->fail('account_already_bound', 'Akun Anda sudah terhubung dengan NIK lain. Gunakan NIK yang sama dengan pendataan sebelumnya.');
        }
        $bound = $this->db->select('user_id')
            ->get_where('sf_profil_warga', ['nik_lookup_hash' => $nik_hash])
            ->row_array();
        $bound_account = $this->db->where('nik_lookup_hash', $nik_hash)
            ->where('id !=', $user_id)->count_all_results('usr_akun') > 0;
        if ($bound_account || ($bound && (int) $bound['user_id'] !== $user_id)) {
            return $this->fail('nik_already_bound', self::PESAN_NIK_TERIKAT);
        }
        return NULL;
    }

    /** Untuk isian NIK tanpa tanggal lahir (onboarding, Profil Saya): sama untuk ikatan terverifikasi atau belum. */
    public const PESAN_NIK_TERIKAT_BUKTIKAN = 'NIK ini sudah terhubung dengan akun lain. Jika ini NIK Anda, buktikan kepemilikannya di langkah Cek NIK menu Pendataan (nama akun dan tanggal lahir sesuai KTP), atau sampaikan melalui menu Aduan.';

    /**
     * Klaim NIK oleh pemilik yang sudah lolos verifikasi nama + tanggal lahir (keputusan pemilik
     * produk, 3 Okt 2026): ikatan yang BELUM terverifikasi di akun lain dilepas lalu NIK diikat ke
     * akun $user_id. Ikatan terverifikasi (sf_profil_warga.confirmed_at) tidak pernah dilepas.
     *
     * WAJIB di dalam transaksi pemanggil (Simperum_gateway::from_snapshot), yang sesudahnya
     * menyimpan profil $user_id dan jejak audit pada transaksi yang sama. Urutan menjaga UNIQUE
     * nik_lookup_hash: pemegang lama dikosongkan dulu, baru akun baru diisi.
     *
     * Akun lama tidak dihapus dan tetap bisa masuk. Yang berubah hanya yang melekat ke NIK itu:
     * usr_akun.nik dikosongkan; profil pendataannya dihapus (kolom NIK-nya NOT NULL, isinya
     * identitas pemilik NIK); cermin SIMPERUM NIK itu dilepas; draft pendataan yang belum dikirim
     * dihapus seperti saat akun dihapus (Data_erasure: draft bukan arsip, isinya data rumah dan
     * identitas pemilik NIK), berkas buktinya disapu pemanggil SESUDAH commit lewat 'draft_ids'.
     * Pengajuan yang sudah masuk antrean TIDAK disentuh: itu catatan dinas (salinan profilnya
     * tersimpan di penilaian), petugas yang memutuskan lewat jejak audit.
     */
    public function pindahkan_ikatan_nik($user_id, $nik)
    {
        $user_id = (int) $user_id;
        $nik_hash = $this->encryption_lib->deterministic_hash($nik);
        $nik_ciphertext = $this->encrypt_value($nik);
        if ($user_id < 1 || $nik_hash === '' || ! $this->encryption_lib->is_encrypted($nik_ciphertext)) {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }
        $profil = $this->db->query('SELECT id, user_id, confirmed_at FROM sf_profil_warga WHERE nik_lookup_hash = ? AND user_id != ? FOR UPDATE',
            [$nik_hash, $user_id])->row_array();
        $akun = array_map('intval', array_column($this->db->query('SELECT id FROM usr_akun WHERE nik_lookup_hash = ? AND id != ? FOR UPDATE',
            [$nik_hash, $user_id])->result_array(), 'id'));
        if ($profil && $profil['confirmed_at'] !== NULL) {
            return $this->fail('nik_already_bound', self::PESAN_NIK_TERIKAT);
        }
        $dari = array_values(array_unique(array_merge($akun, $profil ? [(int) $profil['user_id']] : [])));
        $draft_ids = [];
        if ($profil) {
            $draft_ids = array_map('intval', array_column($this->db->select('id')->get_where('sf_penilaian_perumahan',
                ['profil_warga_id' => (int) $profil['id'], 'status' => 'draft'])->result_array(), 'id'));
            if ($draft_ids) {
                $this->db->where_in('id', $draft_ids)->delete('sf_penilaian_perumahan');
            }
            $this->db->delete('sf_profil_warga', ['id' => (int) $profil['id']]);
        }
        if ($akun) {
            $this->db->where_in('id', $akun)->update('usr_akun', ['nik' => NULL, 'nik_lookup_hash' => NULL]);
        }
        $this->db->where('nik_lookup_hash', $nik_hash)->where('user_id !=', $user_id)->delete('sf_data_simperum');
        $this->db->where('id', $user_id)->where('nik_lookup_hash IS NULL', NULL, FALSE)
            ->update('usr_akun', ['nik' => $nik_ciphertext, 'nik_lookup_hash' => $nik_hash]);
        $pengajuan = $dari ? $this->db->where_in('user_id', $dari)
            ->where_in('status_antrean', ['pending', 'needs_revision'])->count_all_results('sf_antrean_pengajuan') : 0;
        return ['success' => TRUE, 'dari' => $dari, 'draft_ids' => $draft_ids, 'pengajuan_berjalan' => $pengajuan];
    }

    /**
     * Tandai NIK profil akun ini terverifikasi: nama akun dan tanggal lahir cocok dengan data
     * SIMPERUM (Simperum_gateway::lookup()). Kolom confirmed_at (migrasi 018) belum pernah dipakai
     * sebelumnya, jadi NULL = belum terverifikasi, termasuk semua profil sebelum 3 Okt 2026.
     */
    public function tandai_nik_terverifikasi($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)
            ->update('sf_profil_warga', ['confirmed_at' => date('Y-m-d H:i:s')]);
    }

    public function save_profile($user_id, array $data, array $provenance = [])
    {
        $user_id = (int) $user_id;
        $nik = preg_replace('/\D+/', '', (string) ($data['nik'] ?? ''));
        $name = trim((string) ($data['full_name'] ?? ''));
        $mode_sumber = (string) ($data['mode_sumber'] ?? 'simulation');

        if ($user_id < 1 || ! preg_match('/^\d{16}$/', $nik) || $name === ''
            || ! in_array($mode_sumber, self::SOURCE_MODES, TRUE)) {
            return $this->fail('invalid_profile', 'Data profil warga tidak valid.');
        }
        if ( ! $this->encryption_ready()) {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $nik_hash = $this->encryption_lib->deterministic_hash($nik);
        if ($nik_hash === '') {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $ikatan = $this->cek_ikatan_nik($user_id, $nik_hash);
        if ($ikatan !== NULL) {
            return $ikatan;
        }
        $existing = $this->db->select('id')
            ->get_where('sf_profil_warga', ['user_id' => $user_id])
            ->row_array();

        $row = [
            'user_id' => $user_id,
            'mode_sumber' => $mode_sumber,
            'nik_ciphertext' => $this->encrypt_value($nik),
            'nik_lookup_hash' => $nik_hash,
            'no_kk_ciphertext' => $this->encrypt_optional($data['family_card_number'] ?? NULL),
            'no_kk_lookup_hash' => $this->hash_optional($data['family_card_number'] ?? NULL),
            'nama_ciphertext' => $this->encrypt_value($name),
            'alamat_ciphertext' => $this->encrypt_optional($data['address'] ?? NULL),
            'no_hp_ciphertext' => $this->encrypt_optional($data['phone'] ?? NULL),
            'tanggal_lahir_ciphertext' => $this->encrypt_optional($data['birth_date'] ?? NULL),
            'npwp_ciphertext' => $this->encrypt_optional($data['tax_number'] ?? NULL),
            'asal_isian_json' => $this->encode_json(kunci_tersimpan_ke_lama($provenance)),
        ];

        foreach (self::PROFILE_FIELDS as $field) {
            $row[$field] = array_key_exists($field, $data) ? $data[$field] : NULL;
        }

        if ($row['asal_isian_json'] === FALSE || $this->contains_unencrypted_sensitive($row)) {
            return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
        }

        $this->db->trans_start();
        if ($existing) {
            $this->db->where('id', (int) $existing['id'])->update('sf_profil_warga', $row);
            $profile_id = (int) $existing['id'];
        } else {
            $this->db->insert('sf_profil_warga', $row);
            $profile_id = (int) $this->db->insert_id();
        }
        $this->db->trans_complete();

        return $this->db->trans_status()
            ? ['success' => TRUE, 'profile_id' => $profile_id]
            : $this->fail('write_failed', 'Profil warga belum dapat disimpan.');
    }

    public function store_source_snapshot(
        $nik,
        $mode_sumber,
        $kunci_rekaman_sumber,
        $status_respons,
        $payload,
        array $meta = []
    ) {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)
            || ! in_array($mode_sumber, self::SOURCE_MODES, TRUE)
            || ! in_array($status_respons, self::SNAPSHOT_STATUSES, TRUE)) {
            return $this->fail('invalid_snapshot', 'Snapshot sumber tidak valid.');
        }
        if ( ! $this->encryption_ready()) {
            return $this->fail('encryption_unavailable', 'Snapshot sumber belum dapat disimpan.');
        }

        $nik_hash = $this->encryption_lib->deterministic_hash($nik);
        $payload_json = $payload === NULL ? NULL : $this->encode_json(kunci_tersimpan_ke_lama($payload));
        if ($payload !== NULL && $payload_json === FALSE) {
            return $this->fail('invalid_payload', 'Payload sumber tidak dapat disimpan.');
        }
        $muatan_ciphertext = $payload_json === NULL ? NULL : $this->encrypt_value($payload_json);
        if ($nik_hash === '' || ($payload_json !== NULL && ! $this->encryption_lib->is_encrypted($muatan_ciphertext))) {
            return $this->fail('encryption_unavailable', 'Snapshot sumber belum dapat disimpan.');
        }

        $fetched_at = $meta['fetched_at'] ?? date('Y-m-d H:i:s');
        $default_expiry = $status_respons === 'found'
            ? '+30 days'
            : ($status_respons === 'not_found' ? '+1 day' : '+15 minutes');
        $expires_at = $meta['expires_at'] ?? date('Y-m-d H:i:s', strtotime($default_expiry));
        $row = [
            'nik_lookup_hash' => $nik_hash,
            'mode_sumber' => $mode_sumber,
            'kunci_rekaman_sumber' => $kunci_rekaman_sumber ?: NULL,
            'status_respons' => $status_respons,
            'versi_api' => $meta['versi_api'] ?? NULL,
            'http_status' => isset($meta['http_status']) ? (int) $meta['http_status'] : NULL,
            'kode_galat' => $meta['error_code'] ?? NULL,
            'muatan_ciphertext' => $muatan_ciphertext,
            'muatan_sha256' => $payload_json === NULL ? NULL : hash('sha256', $payload_json),
            'fetched_at' => $fetched_at,
            'expires_at' => $expires_at,
            'requested_by' => empty($meta['requested_by']) ? NULL : (int) $meta['requested_by'],
        ];

        return $this->db->insert('sf_rekaman_simperum', $row)
            ? ['success' => TRUE, 'rekaman_id' => (int) $this->db->insert_id()]
            : $this->fail('write_failed', 'Snapshot sumber belum dapat disimpan.');
    }

    public function get_active_source_snapshot($nik, $mode_sumber)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! preg_match('/^\d{16}$/', $nik)
            || ! in_array($mode_sumber, self::SOURCE_MODES, TRUE)
            || ! $this->encryption_ready()) {
            return NULL;
        }

        $row = $this->db
            ->where('nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
            ->where('mode_sumber', $mode_sumber)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('sf_rekaman_simperum')
            ->row_array();
        if ( ! $row) {
            return NULL;
        }

        $payload_json = $row['muatan_ciphertext'] === NULL
            ? NULL : $this->encryption_lib->decrypt($row['muatan_ciphertext']);
        $payload = $payload_json === NULL ? [] : kunci_tersimpan_ke_baru(json_decode($payload_json, TRUE));
        if ( ! is_array($payload)) {
            return NULL;
        }

        $row['payload'] = $payload;
        unset($row['muatan_ciphertext']);
        return $row;
    }

    /** Field rekaman GetDataRTLH -> kolom kode mentah sf_data_simperum (migrasi 064). */
    private const KOLOM_CERMIN = [
        'TahunIntervensi' => 'tahun_intervensi', 'SumberDanaID' => 'sumber_dana_id', 'AtapID' => 'atap_id',
        'LantaiID' => 'lantai_id', 'DindingID' => 'dinding_id', 'KondisiAtap' => 'kondisi_atap',
        'KondisiLantai' => 'kondisi_lantai', 'KondisiDinding' => 'kondisi_dinding',
        'JenisKelamin' => 'jenis_kelamin', 'TahunLahir' => 'tahun_lahir', 'Pendidikan' => 'pendidikan',
        'Pekerjaan' => 'pekerjaan', 'Penghasilan' => 'penghasilan', 'BantuanPerumahan' => 'bantuan_perumahan',
        'KawasanPerumahan' => 'kawasan_perumahan', 'KepemilikanLahan' => 'kepemilikan_lahan',
        'KepemilikanRumah' => 'kepemilikan_rumah', 'TanahLain' => 'tanah_lain', 'RumahLain' => 'rumah_lain',
        'LuasRumah' => 'luas_rumah', 'JmlPenghuni' => 'jml_penghuni', 'JmlKK' => 'jml_kk',
        'AdaPondasi' => 'ada_pondasi', 'KondisiKolom' => 'kondisi_kolom', 'KondisiBalok' => 'kondisi_balok',
        'KondisiRangka' => 'kondisi_rangka', 'AdaJendela' => 'ada_jendela', 'AdaVentilasi' => 'ada_ventilasi',
        'SumberAir' => 'sumber_air', 'Penerangan' => 'penerangan', 'LetakSanitasi' => 'letak_sanitasi',
        'KamarMandi' => 'kamar_mandi', 'JarakSepticTank' => 'jarak_septic_tank', 'MampuSwadaya' => 'mampu_swadaya',
    ];

    /**
     * NIK ini milik akun warga $user_id sendiri DAN sudah terverifikasi (nama akun + tanggal lahir
     * cocok, sf_profil_warga.confirmed_at)? Hanya NIK seperti itu yang boleh masuk cermin
     * sf_data_simperum (ikut terbuka lewat ekspor data akun). NIK yang sekadar diketik di
     * onboarding/Profil Saya (usr_akun.nik) belum membuktikan kepemilikan apa pun.
     */
    public function nik_terikat_akun($user_id, $nik)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ((int) $user_id < 1 || ! preg_match('/^\d{16}$/', $nik) || ! $this->encryption_ready()) {
            return FALSE;
        }
        return $this->db->from('sf_profil_warga p')
            ->join('usr_akun u', 'u.id = p.user_id')
            ->where('p.user_id', (int) $user_id)->where('u.peran', 'warga')
            ->where('p.nik_lookup_hash', $this->encryption_lib->deterministic_hash($nik))
            ->where('p.confirmed_at IS NOT NULL', NULL, FALSE)
            ->count_all_results() === 1;
    }

    /**
     * Upsert cermin data SIMPERUM (migrasi 064) dari hasil GET. Cermin data DINAS: koreksi warga
     * tidak pernah menulis ke sini, koreksi tetap di sf_profil_warga/sf_penilaian_perumahan.
     *
     * - found tanpa raw_record (fixture lama SIM-xx) DILEWATI: fixture itu bukan bentuk rekaman
     *   dinas, dan baris found berkolom kosong akan terhitung "tercocokkan" tanpa data apa pun.
     * - not_found dicatat dengan kolom data NULL, supaya penyegaran mingguan tahu.
     * - error tidak pernah menimpa baris yang sudah ada; tanpa baris, dicatat status saja dan
     *   next_refresh_at = sekarang supaya dicoba lagi pada putaran berikutnya.
     *
     * @return bool TRUE kalau baris ditulis.
     */
    public function cermin_data_simperum($user_id, $nik, $rekaman_id, $status_respons, array $payload, $mode_sumber, $fetched_at = NULL)
    {
        $nik = preg_replace('/\D+/', '', (string) $nik);
        if ( ! in_array($status_respons, self::SNAPSHOT_STATUSES, TRUE)
            || ! in_array($mode_sumber, self::SOURCE_MODES, TRUE)) {
            return FALSE;
        }
        if ( ! $this->nik_terikat_akun($user_id, $nik)) {
            // Akun ini tidak lagi memegang NIK tersebut (mis. NIK akun diganti): baris lamanya dilepas.
            if ((int) $user_id > 0 && preg_match('/^\d{16}$/', $nik) && $this->encryption_ready()) {
                $this->db->delete('sf_data_simperum', [
                    'user_id' => (int) $user_id,
                    'nik_lookup_hash' => $this->encryption_lib->deterministic_hash($nik),
                ]);
            }
            return FALSE;
        }
        $hash = $this->encryption_lib->deterministic_hash($nik);
        $raw = $payload['source']['raw_record'] ?? NULL;
        if ($status_respons === 'found' && ! is_array($raw)) {
            return FALSE;
        }
        if ($status_respons === 'error'
            && $this->db->where('nik_lookup_hash', $hash)->count_all_results('sf_data_simperum') > 0) {
            return FALSE;
        }
        $raw = ($status_respons === 'found') ? $raw : [];
        $teks = static function ($value, $max) {
            $value = trim((string) $value);
            return $value === '' ? NULL : mb_substr($value, 0, $max);
        };

        $fetched_at = $fetched_at ?: date('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');
        $row = [
            'user_id' => (int) $user_id,
            'nik_lookup_hash' => $hash,
            'nik_ciphertext' => $this->encrypt_value($nik),
            'nama_ciphertext' => $this->encrypt_optional($raw['Nama'] ?? NULL),
            'alamat_ciphertext' => $this->encrypt_optional($raw['Alamat'] ?? NULL),
            'geo_lat_ciphertext' => $this->encrypt_optional($raw['GeoLat'] ?? NULL),
            'geo_lng_ciphertext' => $this->encrypt_optional($raw['GeoLng'] ?? NULL),
            'idbdt' => $teks($raw['IDBDT'] ?? NULL, 64),
            'kode_dagri' => $teks($raw['KodeDagri'] ?? NULL, 20),
            'kabupaten_id' => $status_respons === 'found' && ! empty($payload['location']['kabupaten_id'])
                ? (int) $payload['location']['kabupaten_id'] : NULL,
        ];
        // FK ke kabupaten (migrasi 069): kode wilayah dari KodeDagri SIMPERUM yang tidak ada di
        // tabel kabupaten dicatat NULL, supaya upsert cermin tidak ditolak seluruhnya.
        if ($row['kabupaten_id'] !== NULL && ! $this->db->where('id', $row['kabupaten_id'])->count_all_results('kabupaten')) {
            $row['kabupaten_id'] = NULL;
        }
        foreach (self::KOLOM_CERMIN as $field => $kolom) {
            $row[$kolom] = $teks($raw[$field] ?? NULL, 20);
        }
        $row += [
            'status_respons' => $status_respons,
            'mode_sumber' => $mode_sumber,
            'rekaman_id' => $rekaman_id ? (int) $rekaman_id : NULL,
            'fetched_at' => $fetched_at,
            'next_refresh_at' => $status_respons === 'error'
                ? $now : date('Y-m-d H:i:s', strtotime($fetched_at . ' +7 days')),
            'updated_at' => $now,
        ];
        foreach (['nik_ciphertext', 'nama_ciphertext', 'alamat_ciphertext', 'geo_lat_ciphertext', 'geo_lng_ciphertext'] as $kolom) {
            if ($row[$kolom] !== NULL && ! $this->encryption_lib->is_encrypted($row[$kolom])) {
                return FALSE;
            }
        }

        // Satu pernyataan upsert: dua permintaan bersamaan untuk NIK yang sama tidak bisa saling tabrak UNIQUE.
        $ubah = [];
        foreach (array_keys($row) as $kolom) {
            $ubah[] = $kolom . ' = VALUES(' . $kolom . ')';
        }
        return (bool) $this->db->query(
            $this->db->insert_string('sf_data_simperum', $row + ['created_at' => $now])
            . ' ON DUPLICATE KEY UPDATE ' . implode(', ', $ubah)
        );
    }

    /**
     * Daftar penyegaran mingguan (Simperum_segarkan): baris cermin yang jatuh tempo, lalu akun warga
     * ber-NIK yang belum punya baris. Digabung lewat user_id, bukan nik_lookup_hash (collation beda).
     *
     * @return array [['user_id' => int, 'nik' => string], ...]
     */
    public function antrean_segarkan_simperum($batas)
    {
        $batas = max(0, (int) $batas);
        if ($batas < 1 || ! $this->encryption_ready()) {
            return [];
        }
        $rows = $this->db->select('user_id, nik_ciphertext AS nik')
            ->where('next_refresh_at <=', date('Y-m-d H:i:s'))
            ->order_by('next_refresh_at', 'ASC')->limit($batas)
            ->get('sf_data_simperum')->result_array();
        if (count($rows) < $batas) {
            // Hanya NIK terverifikasi (lihat nik_terikat_akun), supaya akun yang belum terverifikasi
            // tidak memenuhi antrean selamanya dan menggeser akun yang berhak.
            $rows = array_merge($rows, $this->db->select('p.user_id, p.nik_ciphertext AS nik')
                ->from('sf_profil_warga p')
                ->join('usr_akun u', 'u.id = p.user_id')
                ->where('u.peran', 'warga')
                ->where('p.confirmed_at IS NOT NULL', NULL, FALSE)
                ->where('NOT EXISTS (SELECT 1 FROM sf_data_simperum d WHERE d.user_id = p.user_id)', NULL, FALSE)
                ->order_by('p.user_id', 'ASC')->limit($batas - count($rows))
                ->get()->result_array());
        }
        $antrean = [];
        foreach ($rows as $r) {
            $nik = preg_replace('/\D+/', '', (string) $this->encryption_lib->decrypt((string) $r['nik']));
            if (strlen($nik) === 16) {
                $antrean[] = ['user_id' => (int) $r['user_id'], 'nik' => $nik];
            }
        }
        return $antrean;
    }

    public function create_draft(
        $user_id,
        $profile_id,
        $kabupaten_id,
        $track = 'undetermined',
        $mode_sumber = 'simulation',
        $rekaman_id = NULL,
        $versi_sebelumnya_id = NULL
    ) {
        $user_id = (int) $user_id;
        $profile_id = (int) $profile_id;
        $kabupaten_id = (int) $kabupaten_id;

        if ($user_id < 1 || $profile_id < 1 || $kabupaten_id < 1
            || ! in_array($track, self::TRACKS, TRUE)
            || ! in_array($mode_sumber, self::SOURCE_MODES, TRUE)) {
            return $this->fail('invalid_draft', 'Data draft tidak valid.');
        }

        $profile = $this->db->select('nik_lookup_hash, mode_sumber')
            ->get_where('sf_profil_warga', ['id' => $profile_id, 'user_id' => $user_id])
            ->row_array();
        $scope_exists = $this->db->where('id', $kabupaten_id)
            ->count_all_results('kabupaten') === 1;
        if ( ! $profile || ! $scope_exists || $profile['mode_sumber'] !== $mode_sumber) {
            return $this->fail('ownership_or_scope_invalid', 'Profil atau wilayah tidak valid.');
        }
        if ($rekaman_id) {
            $snapshot = $this->db->select('nik_lookup_hash, mode_sumber')
                ->get_where('sf_rekaman_simperum', ['id' => (int) $rekaman_id])
                ->row_array();
            if ( ! $snapshot
                || $snapshot['mode_sumber'] !== $mode_sumber
                || ! hash_equals($profile['nik_lookup_hash'], $snapshot['nik_lookup_hash'])) {
                return $this->fail('snapshot_invalid', 'Snapshot sumber tidak valid.');
            }
        }

        $no_versi = 1;
        if ($versi_sebelumnya_id) {
            $previous = $this->db->select('id, user_id, no_versi')
                ->get_where('sf_penilaian_perumahan', ['id' => (int) $versi_sebelumnya_id])
                ->row_array();
            if ( ! $previous || (int) $previous['user_id'] !== $user_id) {
                return $this->fail('previous_version_invalid', 'Versi sebelumnya tidak valid.');
            }
            $no_versi = (int) $previous['no_versi'] + 1;
        }

        $row = [
            'user_id' => $user_id,
            'profil_warga_id' => $profile_id,
            'versi_sebelumnya_id' => $versi_sebelumnya_id ? (int) $versi_sebelumnya_id : NULL,
            'kabupaten_id' => $kabupaten_id,
            'jalur_penilaian' => $track,
            'status' => 'draft',
            'langkah_sekarang' => 'find_data',
            'no_versi' => $no_versi,
            'versi_kunci' => 0,
            'rekaman_simperum_id' => $rekaman_id ? (int) $rekaman_id : NULL,
            'mode_sumber' => $mode_sumber,
        ];

        return $this->db->insert('sf_penilaian_perumahan', $row)
            ? [
                'success' => TRUE,
                'penilaian_id' => (int) $this->db->insert_id(),
                'versi_kunci' => 0,
            ]
            : $this->fail('write_failed', 'Draft belum dapat dibuat.');
    }

    public function update_owned_draft($penilaian_id, $user_id, $expected_lock_version, array $data)
    {
        $penilaian_id = (int) $penilaian_id;
        $user_id = (int) $user_id;
        $expected_lock_version = (int) $expected_lock_version;

        $safe = [];
        foreach (self::DRAFT_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $safe[$field] = $data[$field];
            }
        }
        foreach (self::ISIAN_TERENKRIPSI as $field => $column) {
            if (array_key_exists($field, $data)) {
                if ( ! $this->encryption_ready()) {
                    return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
                }
                if ($field === 'preliminary_matrix' && $data[$field] !== NULL) {
                    // Format tersimpan dibekukan dengan kunci lama (lihat kunci_tersimpan_helper).
                    $data[$field] = json_encode(kunci_tersimpan_ke_lama(json_decode((string) $data[$field], TRUE)),
                        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                }
                $safe[$column] = $this->encrypt_optional($data[$field]);
                if ($safe[$column] !== NULL && ! $this->encryption_lib->is_encrypted($safe[$column])) {
                    return $this->fail('encryption_unavailable', 'Data sensitif belum dapat disimpan.');
                }
            }
        }
        if (isset($safe['jalur_penilaian']) && ! in_array($safe['jalur_penilaian'], self::TRACKS, TRUE)) {
            return $this->fail('invalid_track', 'Cabang assessment tidak valid.');
        }
        if ($penilaian_id < 1 || $user_id < 1 || empty($safe)) {
            return $this->fail('invalid_update', 'Perubahan draft tidak valid.');
        }

        $safe['versi_kunci'] = $expected_lock_version + 1;
        $updated = $this->db
            ->where('id', $penilaian_id)
            ->where('user_id', $user_id)
            ->where('status', 'draft')
            ->where('versi_kunci', $expected_lock_version)
            ->update('sf_penilaian_perumahan', $safe);

        if ( ! $updated || $this->db->affected_rows() !== 1) {
            return $this->fail(
                'stale_or_not_owned',
                'Draft sudah berubah atau tidak dapat diakses. Muat ulang data terbaru.'
            );
        }

        return ['success' => TRUE, 'versi_kunci' => $expected_lock_version + 1];
    }

    public function get_owned_assessment($penilaian_id, $user_id)
    {
        $row = $this->db
            ->where('id', (int) $penilaian_id)
            ->where('user_id', (int) $user_id)
            ->get('sf_penilaian_perumahan')
            ->row_array();
        return $this->decrypt_assessment($row);
    }

    public function get_latest_owned_draft($user_id)
    {
        $row = $this->db
            ->where('user_id', (int) $user_id)
            ->where('status', 'draft')
            ->order_by('updated_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get('sf_penilaian_perumahan')
            ->row_array();
        return $this->decrypt_assessment($row);
    }

    public function get_owned_profile($user_id)
    {
        $row = $this->db->get_where('sf_profil_warga', ['user_id' => (int) $user_id])->row_array();
        if ( ! $row || ! $this->encryption_ready()) {
            return NULL;
        }

        foreach ([
            'nik' => 'nik_ciphertext', 'family_card_number' => 'no_kk_ciphertext',
            'full_name' => 'nama_ciphertext', 'address' => 'alamat_ciphertext',
            'phone' => 'no_hp_ciphertext', 'birth_date' => 'tanggal_lahir_ciphertext',
            'tax_number' => 'npwp_ciphertext',
        ] as $name => $column) {
            $row[$name] = $row[$column] === NULL ? NULL : $this->encryption_lib->decrypt($row[$column]);
            unset($row[$column]);
        }
        return $row;
    }

    /**
     * Bikin/lanjutkan draft dari HASIL lookup Simperum_gateway yang sudah
     * `status==='found'` - dipindah dari Warga::lookup() 14 Agt 2026 (logika
     * APA ADANYA, bukan ditulis ulang) supaya bisa dipakai ULANG dari
     * Auth::_redirect_after_login() juga: warga yang cek NIK ANONIM lalu
     * login/daftar sekarang langsung mendarat di step "Data Warga", bukan
     * balik ke "Temukan Data" dan harus cek ulang manual (itu jurang yang
     * disadari & diterima saat pengikatan NIK-ke-akun pertama kali dibuat,
     * sekarang ditutup).
     *
     * @param int   $user_id
     * @param array $result  balikan Simperum_gateway::lookup(), WAJIB
     *                       $result['status'] === 'found' (tidak diperiksa
     *                       ulang di sini - itu tanggung jawab pemanggil).
     * @return array ['success'=>bool, 'message'=>string] - kontrak sama
     *               dengan create_draft()/update_owned_draft() di berkas
     *               ini, supaya pemanggil bisa memperlakukannya sama.
     */
    public function bootstrap_draft_from_lookup($user_id, array $result)
    {
        $user_id = (int) $user_id;
        $profile = $this->get_owned_profile($user_id);
        $draft = $this->get_latest_owned_draft($user_id);
        $previous_draft = NULL;
        $mode_sumber = (string) ($result['mode_sumber'] ?? 'simulation');
        $rekaman_id = (int) ($result['data']['rekaman_id'] ?? 0);
        if ($draft && (
            ($draft['mode_sumber'] ?? '') !== $mode_sumber
            || (int) ($draft['rekaman_simperum_id'] ?? 0) !== $rekaman_id
        )) {
            $previous_draft = $draft;
            $draft = NULL;
        }
        if ( ! $draft && $profile) {
            $kabupaten_id = $this->source_snapshot_kabupaten_id($rekaman_id);
            $created = $kabupaten_id
                ? $this->create_draft(
                    $user_id,
                    $profile['id'],
                    $kabupaten_id,
                    'undetermined',
                    $mode_sumber,
                    $rekaman_id,
                    $previous_draft['id'] ?? NULL
                )
                : ['success' => FALSE, 'message' => 'Wilayah sumber belum dapat dipakai untuk membuat draft.'];
            if (empty($created['success'])) {
                return $created;
            }
            $draft = $this->get_owned_assessment($created['penilaian_id'], $user_id);
        }
        if ($draft && $draft['langkah_sekarang'] === 'find_data') {
            $started = $this->update_owned_draft(
                $draft['id'], $user_id, $draft['versi_kunci'],
                /* 'housing_family' - sejak 24 Agt 2026 step pertama sesudah
                   find_data (step 'citizen_data' dihapus & digabung ke
                   'housing_family_detail', lihat komentar STEPS di Warga.php). */
                ['langkah_sekarang' => 'housing_family'] + $this->source_snapshot_prefill($rekaman_id)
            );
            if (empty($started['success'])) {
                return $started;
            }
        }
        return ['success' => TRUE];
    }

    /**
     * Bootstrap profil+draft dari isian manual - dipakai saat NIK warga
     * TIDAK ditemukan di SIMPERUM (status 'not_found'), warga tetap ingin
     * lanjut pendataan tanpa data sumber. Meniru struktur
     * bootstrap_draft_from_lookup() di atas, bedanya kabupaten_id
     * diturunkan dari 4 digit pertama NIK (kode wilayah Kemendagri, format
     * sama dengan kabupaten.id - lihat Simperum_gateway::normalize_api_record())
     * bukan dari payload snapshot SIMPERUM (memang tidak ada), dan tidak
     * ada rekaman_id/prefill sama sekali.
     */
    public function bootstrap_manual_draft($user_id, $nik, $full_name)
    {
        $user_id = (int) $user_id;
        $nik = preg_replace('/\D+/', '', (string) $nik);
        $full_name = trim((string) $full_name);

        if ($user_id < 1 || ! preg_match('/^\d{16}$/', $nik) || $full_name === '') {
            return $this->fail('invalid_manual_entry', 'NIK atau nama lengkap tidak valid.');
        }

        /* Cakupan wilayah DICEK LEBIH DULU, SEBELUM save_profile() - kalau
           belum ada draft sama sekali. Urutan sebaliknya (profil dulu,
           baru cek cakupan) pernah dicoba dan TERBUKTI BERMASALAH: profil
           sudah terlanjur tersimpan untuk NIK di luar cakupan, lalu
           save_profile()'s account_already_bound menolak percobaan
           BERIKUTNYA dengan NIK yang BENAR - warga terkunci total, tidak
           bisa memperbaiki kesalahan ketik sendiri. Kalau draft SUDAH ada
           (akun sudah pernah lolos cakupan sebelumnya), lewati - urusan
           NIK berbeda pada akun yang sudah terikat sudah ditangani
           save_profile() sendiri (account_already_bound). */
        $draft = $this->get_latest_owned_draft($user_id);
        $kabupaten_id = NULL;
        if ( ! $draft) {
            $kabupaten_id = (int) substr($nik, 0, 4);
            $scope_exists = $kabupaten_id > 0
                && $this->db->where('id', $kabupaten_id)->count_all_results('kabupaten') === 1;
            if ( ! $scope_exists) {
                return $this->fail(
                    'out_of_scope',
                    'NIK menunjukkan wilayah di luar cakupan layanan ini. Hubungi Dinas Perakim setempat.'
                );
            }
        }

        $saved = $this->save_profile(
            $user_id,
            ['nik' => $nik, 'full_name' => $full_name, 'mode_sumber' => 'manual'],
            ['full_name' => 'citizen']
        );
        if (empty($saved['success'])) {
            return $saved;
        }

        if ( ! $draft) {
            $created = $this->create_draft($user_id, $saved['profile_id'], $kabupaten_id, 'undetermined', 'manual');
            if (empty($created['success'])) {
                return $created;
            }
            $draft = $this->get_owned_assessment($created['penilaian_id'], $user_id);
        }
        if ($draft && $draft['langkah_sekarang'] === 'find_data') {
            $started = $this->update_owned_draft(
                $draft['id'], $user_id, $draft['versi_kunci'],
                ['langkah_sekarang' => 'housing_family']
            );
            if (empty($started['success'])) {
                return $started;
            }
        }
        return ['success' => TRUE];
    }

    public function source_snapshot_kabupaten_id($rekaman_id)
    {
        if ( ! $this->encryption_ready()) {
            return NULL;
        }
        $row = $this->db->select('muatan_ciphertext')
            ->get_where('sf_rekaman_simperum', ['id' => (int) $rekaman_id])
            ->row_array();
        $payload = $row ? kunci_tersimpan_ke_baru(json_decode($this->encryption_lib->decrypt($row['muatan_ciphertext']), TRUE)) : NULL;
        $kabupaten_id = is_array($payload) ? (int) ($payload['location']['kabupaten_id'] ?? 0) : 0;
        return $kabupaten_id > 0 ? $kabupaten_id : NULL;
    }

    public function source_snapshot_prefill($rekaman_id)
    {
        if ( ! $this->encryption_ready()) { return []; }
        $row = $this->db->select('muatan_ciphertext')->get_where('sf_rekaman_simperum', ['id' => (int) $rekaman_id])->row_array();
        $payload = $row ? kunci_tersimpan_ke_baru(json_decode($this->encryption_lib->decrypt($row['muatan_ciphertext']), TRUE)) : [];
        $prefill = [];
        foreach (['housing', 'structure', 'sanitation', 'location'] as $group) {
            foreach ((array) ($payload[$group] ?? []) as $field => $value) {
                if (in_array($field, self::DRAFT_FIELDS, TRUE)
                    || in_array($field, ['location_lat', 'location_lng'], TRUE)) {
                    $prefill[$field] = $value;
                }
            }
        }
        return $prefill;
    }

    public function save_owned_step(
        $penilaian_id,
        $user_id,
        $expected_lock_version,
        array $draft_data,
        ?array $profile_data = NULL,
        array $provenance = [],
        ?array $recommendations = NULL,
        $recommendation_hash = NULL
    )
    {
        $this->db->trans_begin();
        $updated = $this->update_owned_draft($penilaian_id, $user_id, $expected_lock_version, $draft_data);
        if (empty($updated['success'])) {
            $this->db->trans_rollback();
            return $updated;
        }
        if ($profile_data !== NULL) {
            $profile = $this->save_profile($user_id, $profile_data, $provenance);
            if (empty($profile['success'])) {
                $this->db->trans_rollback();
                return $profile;
            }
        }
        if ($recommendations !== NULL) {
            $saved = $this->write_recommendations(
                $penilaian_id,
                $recommendations,
                (string) $recommendation_hash
            );
            if (empty($saved['success'])) {
                $this->db->trans_rollback();
                return $saved;
            }
        }
        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->fail('write_failed', 'Draft belum dapat disimpan.');
        }
        $this->db->trans_commit();
        return $updated;
    }

    public function replace_recommendations($penilaian_id, $user_id, array $items, $hash)
    {
        $assessment = $this->get_owned_assessment($penilaian_id, $user_id);
        if ( ! $assessment || $assessment['status'] !== 'draft') {
            return $this->fail('not_owned', 'Draft tidak dapat diakses.');
        }
        $this->db->trans_begin();
        $saved = $this->write_recommendations($penilaian_id, $items, (string) $hash);
        if (empty($saved['success']) || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return empty($saved['success'])
                ? $saved : $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
        }
        $this->db->trans_commit();
        return $saved;
    }

    public function get_owned_recommendations($penilaian_id, $user_id, $versi_aturan = NULL)
    {
        $query = $this->db
            ->select('r.id rekomendasi_id,p.kode_program program_code,p.nama_program program_name,p.deskripsi_singkat program_description,r.status_kelayakan,r.kode_alasan_json,r.versi_aturan')
            ->from('sf_rekomendasi_penilaian r')
            ->join('sf_program p', 'p.id=r.program_id')
            ->join('sf_penilaian_perumahan a', 'a.id=r.penilaian_id')
            ->where('a.id', (int) $penilaian_id)
            ->where('a.user_id', (int) $user_id);
        if ($versi_aturan !== NULL) {
            $query->where('r.versi_aturan', (string) $versi_aturan);
        }
        $rows = $query
            ->order_by('r.id', 'ASC')
            ->get()
            ->result_array();
        foreach ($rows as &$row) {
            $row['reason_codes'] = json_decode($row['kode_alasan_json'] ?? '[]', TRUE) ?: [];
            unset($row['kode_alasan_json']);
        }
        unset($row);
        return $rows;
    }

    public function submit_owned_assessment($penilaian_id, $user_id, $rekomendasi_id, $versi_aturan)
    {
        $penilaian_id = (int) $penilaian_id;
        $user_id = (int) $user_id;
        $rekomendasi_id = (int) $rekomendasi_id;
        $kunci_pengajuan = hash('sha256', implode(':', ['warga', $user_id, $penilaian_id, $rekomendasi_id, $versi_aturan]));
        $existing = $this->db->get_where('sf_antrean_pengajuan', ['kunci_pengajuan' => $kunci_pengajuan])->row_array();
        if ($existing) { return $this->queue_result($existing); }

        $assessment = $this->db->get_where('sf_penilaian_perumahan', [
            'id' => $penilaian_id, 'user_id' => $user_id, 'status' => 'draft',
        ])->row_array();
        $recommendation = $this->db
            ->select('r.id, r.program_id, r.status_kelayakan, p.aktif')
            ->from('sf_rekomendasi_penilaian r')
            ->join('sf_program p', 'p.id=r.program_id')
            ->where(['r.id' => $rekomendasi_id, 'r.penilaian_id' => $penilaian_id, 'r.versi_aturan' => $versi_aturan])
            ->get()->row_array();
        if ( ! $assessment || ! $recommendation
            || ! in_array($recommendation['status_kelayakan'], ['eligible', 'potential'], TRUE)
            || (int) $recommendation['aktif'] !== 1 || empty($assessment['kabupaten_id'])
            || $versi_aturan !== Warga_ruleset::VERSION
            || Warga_ruleset::STATUS !== 'active'
            || strtotime(Warga_ruleset::EFFECTIVE_FROM) > time()
            || $assessment['langkah_sekarang'] !== 'review') {
            return $this->fail('submission_invalid', 'Draft atau rekomendasi tidak dapat diajukan.');
        }
        $profile = $this->get_owned_profile($user_id);
        if ( ! $profile) { return $this->fail('profile_missing', 'Profil warga tidak tersedia.'); }
        $profile_snapshot = $this->encrypt_value($this->encode_json(kunci_tersimpan_ke_lama($profile)));
        if ( ! $this->encryption_lib->is_encrypted($profile_snapshot)) {
            return $this->fail('encryption_unavailable', 'Snapshot profil belum dapat disimpan.');
        }

        $this->db->trans_begin();
        $updated = $this->db->where([
            'id' => $penilaian_id, 'user_id' => $user_id, 'status' => 'draft',
        ])->update('sf_penilaian_perumahan', [
            'status' => 'submitted', 'submitted_at' => date('Y-m-d H:i:s'),
            'salinan_profil_ciphertext' => $profile_snapshot,
        ]);
        if ( ! $updated || $this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            $existing = $this->db->get_where('sf_antrean_pengajuan', ['kunci_pengajuan' => $kunci_pengajuan])->row_array();
            return $existing ? $this->queue_result($existing) : $this->fail('stale_draft', 'Draft sudah berubah.');
        }

        if ( ! empty($assessment['versi_sebelumnya_id'])) {
            $queue = $this->db->get_where('sf_antrean_pengajuan', [
                'user_id' => $user_id, 'penilaian_id' => (int) $assessment['versi_sebelumnya_id'],
                'status_antrean' => 'needs_revision',
            ])->row_array();
            if ( ! $queue || ! $this->db->where(['id' => $queue['id'], 'status_antrean' => 'needs_revision'])
                ->update('sf_antrean_pengajuan', [
                    'penilaian_id' => $penilaian_id, 'rekomendasi_id' => $rekomendasi_id,
                    'program_id' => $recommendation['program_id'], 'kunci_pengajuan' => $kunci_pengajuan,
                    'status_antrean' => 'pending', 'catatan_admin' => NULL,
                    'reviewed_by' => NULL, 'reviewed_at' => NULL, 'updated_at' => date('Y-m-d H:i:s'),
                ]) || $this->db->affected_rows() !== 1) {
                $this->db->trans_rollback();
                return $this->fail('revision_stale', 'Revisi tidak dapat dikirim ulang.');
            }
            $history_inserted = $this->db->insert('sf_riwayat_keputusan_antrean', [
                'antrean_id' => $queue['id'], 'status_awal' => 'needs_revision',
                'status_akhir' => 'pending', 'catatan' => NULL, 'pelaku_id' => $user_id,
            ]);
            if ( ! $history_inserted) {
                $this->db->trans_rollback();
                return $this->fail('write_failed', 'Riwayat pengajuan belum dapat disimpan.');
            }
            $superseded = $this->db->where([
                'id' => (int) $assessment['versi_sebelumnya_id'], 'status' => 'submitted',
            ])->update('sf_penilaian_perumahan', ['status' => 'superseded']);
            if ( ! $superseded || $this->db->affected_rows() !== 1) {
                $this->db->trans_rollback();
                return $this->fail('revision_stale', 'Versi sebelumnya sudah berubah.');
            }
            $antrean_id = (int) $queue['id']; $ticket = $queue['kode_tiket'];
        } else {
            $ticket = $this->generate_ticket_code();
            $inserted = $this->db->insert('sf_antrean_pengajuan', [
                'kode_tiket' => $ticket, 'user_id' => $user_id, 'kabupaten_id' => $assessment['kabupaten_id'],
                'penilaian_id' => $penilaian_id, 'rekomendasi_id' => $rekomendasi_id,
                'kunci_pengajuan' => $kunci_pengajuan, 'program_id' => $recommendation['program_id'],
                // Identitas TIDAK disalin ke antrean: dibaca lewat penilaian_id (sf_profil_warga terenkripsi).
                'status_antrean' => 'pending', 'mode_sumber' => $assessment['mode_sumber'],
            ]);
            if ( ! $inserted) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Pengajuan belum dapat disimpan.'); }
            $antrean_id = (int) $this->db->insert_id();
            if ( ! $this->db->insert('sf_riwayat_keputusan_antrean', [
                'antrean_id' => $antrean_id, 'status_awal' => NULL,
                'status_akhir' => 'pending', 'catatan' => NULL, 'pelaku_id' => $user_id,
            ])) {
                $this->db->trans_rollback();
                return $this->fail('write_failed', 'Riwayat pengajuan belum dapat disimpan.');
            }
        }
        if ( ! $this->db->trans_status()) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Pengajuan belum dapat disimpan.'); }
        $this->db->trans_commit();
        return ['success' => TRUE, 'antrean_id' => $antrean_id, 'kode_tiket' => $ticket,
            'penilaian_id' => $penilaian_id, 'notification_needed' => TRUE];
    }

    public function resubmit_revision($penilaian_id, $user_id, $rekomendasi_id, $versi_aturan)
    {
        return $this->submit_owned_assessment($penilaian_id, $user_id, $rekomendasi_id, $versi_aturan);
    }

    public function start_revision($antrean_id, $user_id)
    {
        $this->db->trans_begin();
        $queue = $this->db->query("SELECT * FROM sf_antrean_pengajuan
            WHERE id=? AND user_id=? AND status_antrean='needs_revision' FOR UPDATE",
            [(int) $antrean_id, (int) $user_id])->row_array();
        if ( ! $queue || empty($queue['penilaian_id'])) {
            $this->db->trans_rollback();
            return $this->fail('revision_unavailable', 'Revisi tidak tersedia.');
        }
        $existing = $this->db->get_where('sf_penilaian_perumahan', [
            'user_id' => (int) $user_id, 'versi_sebelumnya_id' => (int) $queue['penilaian_id'], 'status' => 'draft',
        ])->row_array();
        if ($existing) {
            $this->db->trans_commit();
            return ['success' => TRUE, 'antrean_id' => (int) $antrean_id, 'kode_tiket' => $queue['kode_tiket'], 'penilaian_id' => (int) $existing['id']];
        }

        $source = $this->db->get_where('sf_penilaian_perumahan', [
            'id' => (int) $queue['penilaian_id'], 'user_id' => (int) $user_id, 'status' => 'submitted',
        ])->row_array();
        if ( ! $source) {
            $this->db->trans_rollback();
            return $this->fail('revision_source_missing', 'Versi pengajuan tidak tersedia.');
        }
        foreach (['id', 'created_at', 'updated_at', 'submitted_at'] as $field) unset($source[$field]);
        $source['versi_sebelumnya_id'] = (int) $queue['penilaian_id'];
        $source['no_versi'] = (int) $source['no_versi'] + 1;
        $source['status'] = 'draft'; $source['langkah_sekarang'] = 'housing_family';
        $source['versi_kunci'] = 0; $source['salinan_profil_ciphertext'] = NULL;

        if ( ! $this->db->insert('sf_penilaian_perumahan', $source)) {
            $this->db->trans_rollback(); return $this->fail('write_failed', 'Draft revisi belum dapat dibuat.');
        }
        $new_id = (int) $this->db->insert_id();
        $this->db->query("INSERT INTO sf_berkas_penilaian
            (penilaian_id,jenis_berkas,path_privat,nama_asli_ciphertext,mime_type,ukuran_byte,sha256,uploaded_by,created_at,verified_by,verified_at)
            SELECT ?,jenis_berkas,path_privat,nama_asli_ciphertext,mime_type,ukuran_byte,sha256,uploaded_by,NOW(),NULL,NULL
            FROM sf_berkas_penilaian WHERE penilaian_id=?", [$new_id, (int) $queue['penilaian_id']]);
        if ( ! $this->db->trans_status()) { $this->db->trans_rollback(); return $this->fail('write_failed', 'Draft revisi belum dapat dibuat.'); }
        $this->db->trans_commit();
        return ['success' => TRUE, 'antrean_id' => (int) $antrean_id, 'kode_tiket' => $queue['kode_tiket'], 'penilaian_id' => $new_id];
    }

    public function transition_queue($antrean_id, $from, $to, $reviewer_id, $kabupaten_id = NULL, $catatan = '')
    {
        $catatan = trim((string) $catatan);
        $this->db->select('id,penilaian_id,status_antrean')->where('id', (int) $antrean_id);
        if ($kabupaten_id !== NULL) $this->db->where('kabupaten_id', (int) $kabupaten_id);
        $queue = $this->db->get('sf_antrean_pengajuan')->row_array();
        if ($queue && empty($from) && empty($queue['penilaian_id'])) {
            $from = $queue['status_antrean'];
        }
        if ( ! $queue || $queue['status_antrean'] !== $from) {
            return $this->fail('stale_or_out_of_scope', 'Pengajuan sudah berubah atau di luar wilayah.');
        }

        $assessment_flow = ! empty($queue['penilaian_id']);
        $valid = $assessment_flow
            ? $from === 'pending' && in_array($to, ['needs_revision', 'approved', 'rejected'], TRUE)
            : $to !== 'needs_revision' && housing_queue_can_transition($from, $to);
        if ( ! $valid || (in_array($to, ['needs_revision', 'rejected'], TRUE) && $catatan === '')) {
            return $this->fail('invalid_transition', 'Perubahan status tidak valid.');
        }
        $this->db->trans_begin();
        $this->db->where(['id' => (int) $antrean_id, 'status_antrean' => $from]);
        if ($kabupaten_id !== NULL) $this->db->where('kabupaten_id', (int) $kabupaten_id);
        $ok = $this->db
            ->update('sf_antrean_pengajuan', [
                'status_antrean' => $to, 'catatan_admin' => $catatan ?: NULL,
                'reviewed_by' => (int) $reviewer_id, 'reviewed_at' => date('Y-m-d H:i:s'),
            ]);
        if ( ! $ok || $this->db->affected_rows() !== 1
            || ! $this->db->insert('sf_riwayat_keputusan_antrean', [
                'antrean_id' => (int) $antrean_id, 'status_awal' => $from, 'status_akhir' => $to,
                'catatan' => $catatan ?: NULL, 'pelaku_id' => (int) $reviewer_id,
            ]) || ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return $this->fail('stale_or_out_of_scope', 'Pengajuan sudah berubah atau di luar wilayah.');
        }
        $this->db->trans_commit();
        return ['success' => TRUE, 'antrean_id' => (int) $antrean_id];
    }

    public function get_latest_owned_flow($user_id)
    {
        return $this->db->where('user_id', (int) $user_id)->order_by('updated_at', 'DESC')
            ->limit(1)->get('sf_antrean_pengajuan')->row_array();
    }

    /**
     * Riwayat perjalanan satu pengajuan, untuk PEMILIKNYA.
     *
     * Barisnya sudah lama ditulis submit/transition/revisi tapi tidak pernah
     * ditampilkan ke pemohon - dia hanya melihat satu status terakhir dan
     * menunggu dalam gelap. WHERE user_id di join menjaga kepemilikan.
     */
    public function get_owned_timeline($antrean_id, $user_id)
    {
        return $this->db
            ->select('h.status_awal, h.status_akhir, h.catatan, h.created_at')
            ->from('sf_riwayat_keputusan_antrean h')
            ->join('sf_antrean_pengajuan q', 'q.id = h.antrean_id')
            ->where(['h.antrean_id' => (int) $antrean_id, 'q.user_id' => (int) $user_id])
            ->order_by('h.id', 'ASC')
            ->get()->result_array();
    }

    public function get_scoped_queue_detail($antrean_id, $kabupaten_id = NULL)
    {
        $this->db->select('q.*,p.kode_program,p.nama_program,r.status_kelayakan,r.versi_aturan,r.kode_alasan_json')
            ->from('sf_antrean_pengajuan q')
            ->join('sf_rekomendasi_penilaian r', 'r.id=q.rekomendasi_id', 'left')
            ->join('sf_program p', 'p.id=q.program_id', 'left')
            ->where('q.id', (int) $antrean_id);
        if ($kabupaten_id !== NULL) $this->db->where('q.kabupaten_id', (int) $kabupaten_id);
        $queue = $this->db->get()->row_array();
        if ( ! $queue) return NULL;
        $assessment = $this->decrypt_assessment($this->db->get_where('sf_penilaian_perumahan', ['id' => $queue['penilaian_id']])->row_array());
        $profile = [];
        if (is_array($assessment) && ! empty($assessment['salinan_profil_ciphertext'])) {
            $profile = kunci_tersimpan_ke_baru(json_decode($this->encryption_lib->decrypt($assessment['salinan_profil_ciphertext']), TRUE) ?: []);
            unset($assessment['salinan_profil_ciphertext']);
        }
        $recommendation = [
            'rekomendasi_id' => $queue['rekomendasi_id'],
            'status_kelayakan' => $queue['status_kelayakan'],
            'versi_aturan' => $queue['versi_aturan'],
            'reason_codes' => json_decode($queue['kode_alasan_json'] ?? '[]', TRUE) ?: [],
        ];
        unset($queue['status_kelayakan'], $queue['versi_aturan'], $queue['kode_alasan_json']);
        return ['queue' => $queue, 'assessment' => $assessment,
            'profile_snapshot' => $profile, 'recommendation' => $recommendation];
    }

    public function get_scoped_queue_files($antrean_id, $kabupaten_id = NULL)
    {
        $queue = $this->get_scoped_queue_detail($antrean_id, $kabupaten_id);
        if ( ! $queue || empty($queue['queue']['penilaian_id'])) return [];
        return $this->files_with_storage_owner((int) $queue['queue']['penilaian_id']);
    }

    private function write_recommendations($penilaian_id, array $items, $hash)
    {
        if ( ! preg_match('/^[a-f0-9]{64}$/', $hash)) {
            return $this->fail('invalid_recommendation_hash', 'Input evaluasi rekomendasi tidak valid.');
        }
        if (empty($items)) {
            return ['success' => TRUE];
        }

        $versions = [];
        $programs = [];
        foreach ($items as $item) {
            $code = (string) ($item['program_code'] ?? '');
            $version = (string) ($item['versi_aturan'] ?? '');
            $status = (string) ($item['status_kelayakan'] ?? '');
            if ($code === '' || $version === ''
                || ! in_array($status, ['eligible', 'potential', 'not_eligible', 'needs_data'], TRUE)) {
                return $this->fail('invalid_recommendation', 'Hasil evaluasi rekomendasi tidak valid.');
            }
            $program = $this->db->select('id')->get_where('sf_program', ['kode_program' => $code])->row_array();
            if ( ! $program) {
                return $this->fail('program_not_found', 'Program rekomendasi tidak tersedia.');
            }
            $programs[$code] = (int) $program['id'];
            $versions[$version] = TRUE;
        }

        foreach (array_keys($versions) as $version) {
            if ( ! $this->db->where('penilaian_id', (int) $penilaian_id)
                ->where('versi_aturan', $version)
                ->delete('sf_rekomendasi_penilaian')) {
                return $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
            }
        }
        foreach ($items as $item) {
            if ( ! $this->db->insert('sf_rekomendasi_penilaian', [
                'penilaian_id' => (int) $penilaian_id,
                'program_id' => $programs[$item['program_code']],
                'versi_aturan' => $item['versi_aturan'],
                'status_kelayakan' => $item['status_kelayakan'],
                'kode_alasan_json' => $this->encode_json($item['reason_codes'] ?? []),
                'masukan_sha256' => $hash,
                'evaluated_at' => date('Y-m-d H:i:s'),
            ])) {
                return $this->fail('write_failed', 'Rekomendasi belum dapat disimpan.');
            }
        }
        return ['success' => TRUE];
    }

    public function replace_owned_file($penilaian_id, $user_id, $jenis_berkas, $path_privat, $original_name, $mime, $size, $sha256)
    {
        $owned = $this->get_owned_assessment($penilaian_id, $user_id);
        if ( ! $owned || $owned['status'] !== 'draft' || !in_array($jenis_berkas, self::EVIDENCE_KINDS, TRUE) || ! $this->encryption_ready()) {
            return $this->fail('not_owned_or_unavailable', 'Draft tidak dapat diakses.');
        }
        $old = $this->db->get_where('sf_berkas_penilaian', ['penilaian_id' => (int) $penilaian_id, 'jenis_berkas' => $jenis_berkas])->row_array();
        $row = ['penilaian_id' => (int) $penilaian_id, 'jenis_berkas' => $jenis_berkas, 'path_privat' => $path_privat,
            'nama_asli_ciphertext' => $this->encrypt_value($original_name), 'mime_type' => $mime,
            'ukuran_byte' => (int) $size, 'sha256' => $sha256, 'uploaded_by' => (int) $user_id];
        $ok = $old ? $this->db->where('id', $old['id'])->update('sf_berkas_penilaian', $row) : $this->db->insert('sf_berkas_penilaian', $row);
        $old_path = $old['path_privat'] ?? NULL;
        if ($old_path && $this->db->where('path_privat', $old_path)->where('id !=', $old['id'])
            ->count_all_results('sf_berkas_penilaian') > 0) {
            $old_path = NULL;
        }
        return $ok ? ['success' => TRUE, 'old_path' => $old_path] : $this->fail('write_failed', 'Berkas belum dapat disimpan.');
    }

    public function get_owned_files($penilaian_id, $user_id)
    {
        if ( ! $this->get_owned_assessment($penilaian_id, $user_id)) {
            return [];
        }
        $rows = $this->files_with_storage_owner((int) $penilaian_id);
        $files = [];
        foreach ($rows as $row) {
            $files[$row['jenis_berkas']] = $row;
        }
        return $files;
    }

    private function files_with_storage_owner($penilaian_id)
    {
        return $this->db->select('f.id,f.jenis_berkas,f.path_privat,f.mime_type,f.ukuran_byte,f.created_at,
                (SELECT MIN(f2.penilaian_id) FROM sf_berkas_penilaian f2
                 WHERE f2.path_privat=f.path_privat AND f2.sha256=f.sha256) storage_assessment_id', FALSE)
            ->from('sf_berkas_penilaian f')->where('f.penilaian_id', (int) $penilaian_id)
            ->get()->result_array();
    }

    private function encrypt_value($value)
    {
        return $this->encryption_lib->encrypt((string) $value);
    }

    /**
     * Pendataan awal di satu wilayah (daftar revisi dinas 23 Sep 2026: "langkah ketiga harus
     * tersimpan, 4 opsional dan bisa dipantau admin"). Draft yang sudah menyimpan rekomendasi
     * awal (langkah 3) tetapi belum dikirim, jadi admin kab/kota bisa menindaklanjuti warga yang
     * berhenti sebelum melengkapi data. Hanya baca; nama, HP, dan program didekripsi di sini.
     *
     * @return array [rows, total]
     */
    public function pendataan_awal_wilayah($kabupaten_id, $limit, $offset)
    {
        $dasar = function () use ($kabupaten_id) {
            return $this->db->from('sf_penilaian_perumahan a')
                ->where('a.kabupaten_id', (int) $kabupaten_id)
                ->where('a.status', 'draft')
                ->where('a.matriks_awal_ciphertext IS NOT NULL', NULL, FALSE);
        };
        $total = (int) $dasar()->count_all_results();
        $rows = $dasar()
            ->select('a.id, a.user_id, a.langkah_sekarang, a.jalur_penilaian, a.updated_at, a.matriks_awal_ciphertext,
                      p.nama_ciphertext, p.no_hp_ciphertext')
            ->join('sf_profil_warga p', 'p.user_id = a.user_id', 'left')
            ->order_by('a.updated_at', 'DESC')->limit((int) $limit, (int) $offset)
            ->get()->result_array();
        $siap = $this->encryption_ready();
        foreach ($rows as &$r) {
            $buka = function ($c) use ($siap) { return ($siap && $c !== NULL) ? $this->encryption_lib->decrypt($c) : NULL; };
            $r['full_name'] = $buka($r['nama_ciphertext']);
            $r['phone'] = $buka($r['no_hp_ciphertext']);
            $matriks = json_decode((string) $buka($r['matriks_awal_ciphertext']), TRUE);
            $r['programs'] = array_values(array_filter(array_map(function ($i) { return $i['program_name'] ?? NULL; }, $matriks['items'] ?? [])));
            unset($r['nama_ciphertext'], $r['no_hp_ciphertext'], $r['matriks_awal_ciphertext']);
        }
        unset($r);
        return [$rows, $total];
    }

    private function decrypt_assessment($row)
    {
        if (!$row || !$this->encryption_ready()) return $row;
        foreach (self::ISIAN_TERENKRIPSI as $name => $column) {
            $row[$name] = ($row[$column] ?? NULL) === NULL ? NULL : $this->encryption_lib->decrypt($row[$column]);
            unset($row[$column]);
        }
        if ($row['preliminary_matrix'] !== NULL) {
            $matriks = json_decode((string) $row['preliminary_matrix'], TRUE);
            $row['preliminary_matrix'] = is_array($matriks)
                ? json_encode(kunci_tersimpan_ke_baru($matriks), JSON_UNESCAPED_UNICODE) : $row['preliminary_matrix'];
        }
        return $row;
    }

    private function encrypt_optional($value)
    {
        $value = trim((string) $value);
        return $value === '' ? NULL : $this->encrypt_value($value);
    }

    private function hash_optional($value)
    {
        $value = preg_replace('/\D+/', '', (string) $value);
        return $value === '' ? NULL : $this->encryption_lib->deterministic_hash($value);
    }

    private function contains_unencrypted_sensitive(array $row)
    {
        foreach ([
            'nik_ciphertext', 'no_kk_ciphertext', 'nama_ciphertext',
            'alamat_ciphertext', 'no_hp_ciphertext', 'tanggal_lahir_ciphertext',
            'npwp_ciphertext',
        ] as $field) {
            if ($row[$field] !== NULL && ! $this->encryption_lib->is_encrypted($row[$field])) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function encryption_ready()
    {
        $key = getenv('KPKP_DATA_KEY');
        $pepper = getenv('KPKP_DATA_PEPPER');
        return is_string($key)
            && preg_match('/^[a-f0-9]{64}$/i', $key)
            && is_string($pepper)
            && trim($pepper) !== '';
    }

    private function encode_json($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json;
    }

    private function generate_ticket_code()
    {
        do {
            $ticket = 'PKP-';
            for ($i = 0; $i < 6; $i++) {
                $ticket .= 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'[random_int(0, 31)];
            }
        } while ($this->db->where('kode_tiket', $ticket)->count_all_results('sf_antrean_pengajuan'));
        return $ticket;
    }

    private function queue_result(array $queue)
    {
        return ['success' => TRUE, 'antrean_id' => (int) $queue['id'],
            'kode_tiket' => $queue['kode_tiket'], 'penilaian_id' => (int) $queue['penilaian_id']];
    }

    private function fail($code, $message)
    {
        return ['success' => FALSE, 'code' => $code, 'message' => $message];
    }
}
