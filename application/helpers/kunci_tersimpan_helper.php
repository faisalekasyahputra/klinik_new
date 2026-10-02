<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kunci JSON TERSIMPAN tidak ikut diganti nama oleh migrasi 072.
 *
 * Migrasi 072 mengganti nama kolom ke Bahasa Indonesia, dan kode wizard warga memakai nama
 * kolom yang sama sebagai nama isian. Beberapa struktur JSON menyimpan nama isian itu sebagai
 * KUNCI di dalam data, dan sebagian terenkripsi sehingga tidak bisa ditulis ulang lewat SQL:
 *   - sf_profil_warga.asal_isian_json (asal tiap isian, teks polos);
 *   - sf_penilaian_perumahan.salinan_profil_ciphertext (salinan profil saat diajukan, terenkripsi);
 *   - sf_penilaian_perumahan.matriks_awal_ciphertext (hasil matriks awal, terenkripsi);
 *   - sf_rekaman_simperum.muatan_ciphertext (muatan SIMPERUM ternormalisasi, terenkripsi);
 *   - sf_rekomendasi_penilaian.masukan_sha256 (sidik masukan; kunci ikut di-hash).
 * Keputusan: FORMAT TERSIMPAN DIBEKUKAN dengan kunci lama (data tidak berubah, rollback ke kode
 * lama tetap membaca baris baru). Tulis lewat kunci_tersimpan_ke_lama(), baca lewat
 * kunci_tersimpan_ke_baru(). Jangan menulis struktur di atas tanpa melewati fungsi ini.
 *
 * Peta di berkas ini SATU-SATUNYA tempat di application/ (selain berkas migrasi) yang boleh menyebut nama
 * kolom lama; uji_penamaan_indonesia.php mengecualikan berkas ini dan memeriksa peta ini
 * bijektif terhadap peta migrasi 072.
 */
if ( ! function_exists('kunci_tersimpan_peta')) {
function kunci_tersimpan_peta()
{
    return [
        'source_mode'                       => 'mode_sumber',
        'family_card_lookup_hash'           => 'no_kk_lookup_hash',
        'gender_code'                       => 'jenis_kelamin',
        'marital_status_code'               => 'status_perkawinan',
        'education_code'                    => 'pendidikan',
        'occupation_code'                   => 'pekerjaan',
        'employment_stability_code'         => 'stabilitas_pekerjaan',
        'monthly_income'                    => 'penghasilan_bulanan',
        'income_band_code'                  => 'kelompok_penghasilan',
        'welfare_decile'                    => 'desil_kesejahteraan',
        'has_savings'                       => 'punya_tabungan',
        'self_help_capability_code'         => 'mampu_swadaya',
        'self_help_amount'                  => 'nilai_swadaya',
        'field_provenance_json'             => 'asal_isian_json',
        'citizen_profile_id'                => 'profil_warga_id',
        'previous_version_id'               => 'versi_sebelumnya_id',
        'assessment_track'                  => 'jalur_penilaian',
        'current_step'                      => 'langkah_sekarang',
        'version_no'                        => 'no_versi',
        'lock_version'                      => 'versi_kunci',
        'simperum_snapshot_id'              => 'rekaman_simperum_id',
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
        'location_accuracy_m'               => 'akurasi_lokasi_m',
        'matrix_land_ownership_code'        => 'matriks_kepemilikan_lahan',
        'matrix_current_housing_code'       => 'matriks_rumah_sekarang',
        'matrix_environment_condition_code' => 'matriks_kondisi_lingkungan',
        'matrix_occupation_finance_code'    => 'matriks_pekerjaan_keuangan',
        'matrix_marital_family_code'        => 'matriks_status_keluarga',
        'matrix_income_code'                => 'matriks_penghasilan',
        'matrix_dtks_status'                => 'matriks_status_dtks',
        'bathroom_usage_code'               => 'penggunaan_kamar_mandi',
        'source_record_key'                 => 'kunci_rekaman_sumber',
        'response_status'                   => 'status_respons',
        'api_version'                       => 'versi_api',
        'assessment_id'                     => 'penilaian_id',
        'ruleset_version'                   => 'versi_aturan',
        'eligibility_status'                => 'status_kelayakan',
        'reason_codes_json'                 => 'kode_alasan_json',
        'snapshot_id'                       => 'rekaman_id',
    ];
}
}

/** Kunci lama (tersimpan) -> nama sekarang, rekursif; nilai daftar missing_fields ikut diterjemahkan. */
if ( ! function_exists('kunci_tersimpan_ke_baru')) {
function kunci_tersimpan_ke_baru($data)
{
    return kunci_tersimpan_terjemah($data, kunci_tersimpan_peta());
}
}

/** Nama sekarang -> kunci lama, untuk ditulis ke struktur tersimpan. */
if ( ! function_exists('kunci_tersimpan_ke_lama')) {
function kunci_tersimpan_ke_lama($data)
{
    return kunci_tersimpan_terjemah($data, array_flip(kunci_tersimpan_peta()));
}
}

if ( ! function_exists('kunci_tersimpan_terjemah')) {
function kunci_tersimpan_terjemah($data, array $peta)
{
    if ( ! is_array($data)) {
        return $data;
    }
    $hasil = [];
    foreach ($data as $kunci => $nilai) {
        if ($kunci === 'missing_fields' && is_array($nilai)) {
            $nilai = array_map(function ($v) use ($peta) { return is_string($v) && isset($peta[$v]) ? $peta[$v] : $v; }, $nilai);
        } elseif (is_array($nilai) && $kunci !== 'raw_record') {
            $nilai = kunci_tersimpan_terjemah($nilai, $peta);
        }
        $hasil[(is_string($kunci) && isset($peta[$kunci])) ? $peta[$kunci] : $kunci] = $nilai;
    }
    return $hasil;
}
}

/**
 * Nama tabel lama => nama sekarang. sys_jejak_audit.objek_tipe menyimpan NAMA TABEL sebagai kode
 * objek; baris yang ditulis sebelum migrasi 072 tetap memakai nama lama (nilai tidak diubah), jadi
 * label dan pencarian jejak audit menerjemahkannya lewat peta ini.
 */
if ( ! function_exists('kunci_tersimpan_tabel')) {
function kunci_tersimpan_tabel()
{
    return [
        'usr_users'                   => 'usr_akun',
        'usr_documents'               => 'usr_dokumen',
        'usr_admin_module_privileges' => 'usr_hak_modul_admin',
        'sys_settings'                => 'sys_pengaturan',
        'sys_rate_limits'             => 'sys_batas_laju',
        'sys_push_subscriptions'      => 'sys_langganan_notifikasi',
        'chat_rooms'                  => 'chat_ruang',
        'chat_messages'               => 'chat_pesan',
        'forum_likes'                 => 'forum_suka',
        'sf_housing_queue'            => 'sf_antrean_pengajuan',
        'sf_programs'                 => 'sf_program',
        'srp2_registrations'          => 'srp2_pengajuan',
        'srp2_documents'              => 'srp2_dokumen',
        'srp2_certified_developers'   => 'srp2_direktori_pengembang',
    ];
}
}

/** Kode objek audit (nama tabel) lama atau baru => nama sekarang. */
if ( ! function_exists('kunci_tersimpan_objek')) {
function kunci_tersimpan_objek($tipe)
{
    return kunci_tersimpan_tabel()[(string) $tipe] ?? (string) $tipe;
}
}
