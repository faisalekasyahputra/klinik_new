<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warga_ruleset {

    public const VERSION = 'SIM-2026-02';
    public const STATUS = 'active';
    public const EFFECTIVE_FROM = '2026-07-28 00:00:00';

    /**
     * Aturan per program dari aturan_program (migrasi 084, tahap 1 aturan yang bisa diatur dinas), urut
     * `urutan`, berkunci kode program: desil[], tanpa_desil, cek (jenis_cek), alasan (kode_alasan). Tanpa argumen (dimuat
     * CodeIgniter) dibaca dari database versi VERSION; uji tanpa CodeIgniter memberikannya lewat dari_baris_db().
     */
    private $aturan;

    public function __construct($aturan = NULL)
    {
        if ($aturan === NULL) {
            $CI =& get_instance();
            $aturan = self::dari_baris_db($CI->db->select('p.kode_program, a.desil_sasaran, a.kandidat_tanpa_desil, a.jenis_cek, a.kode_alasan')
                ->from('aturan_program a')->join('sf_program p', 'p.id = a.program_id')
                ->where('a.versi', self::VERSION)->order_by('a.urutan')->get()->result_array());
        }
        if ($aturan === []) {
            throw new RuntimeException('Aturan program ' . self::VERSION . ' belum ada di database (jalankan migrasi 084).');
        }
        $this->aturan = $aturan;
    }

    /** Baris aturan_program + kode_program (urut) ke bentuk yang dipakai mesin, berkunci kode program. */
    public static function dari_baris_db(array $rows)
    {
        $hasil = [];
        foreach ($rows as $r) {
            $hasil[$r['kode_program']] = [
                'desil' => array_map('intval', explode(',', $r['desil_sasaran'])),
                'tanpa_desil' => (int) $r['kandidat_tanpa_desil'] === 1,
                'cek' => $r['jenis_cek'], 'alasan' => $r['kode_alasan'],
            ];
        }
        return $hasil;
    }

    public function route_candidates($decile)
    {
        $decile = (int) $decile;
        $candidates = [];
        foreach ($this->aturan as $code => $a) {
            // Desil kosong: kandidat awal untuk data manual; evaluate tetap meminta desil
            // terverifikasi dan tidak mengubahnya menjadi penerima yang layak.
            if ($decile < 1 || $decile > 10 ? $a['tanpa_desil'] : in_array($decile, $a['desil'], TRUE)) {
                $candidates[] = $code;
            }
        }
        return $candidates;
    }

    public function evaluate($code, array $assessment, array $profile)
    {
        $decile = (int) ($profile['desil_kesejahteraan'] ?? 0);
        if ($decile < 1 || $decile > 10) {
            return $this->result('needs_data', ['SIM_DECILE_MISSING']);
        }
        $aturan = $this->aturan[$code] ?? NULL;
        if ($aturan === NULL || ! in_array($decile, $aturan['desil'], TRUE)) {
            return $this->result('not_eligible', ['SIM_DESIL_TIDAK_SESUAI']);
        }
        $cek = $aturan['cek'];
        if ($cek === 'belum_tersedia') {
            return $this->result('needs_data', [$aturan['alasan']]);
        }

        $track = $assessment['jalur_penilaian'] ?? 'undetermined';
        if ($cek === 'rtlh_kerusakan') {
            if ($track !== 'existing_house') {
                return $this->result('not_eligible', ['SIM_TRACK_TIDAK_SESUAI']);
            }
            $damaged = $this->has_value($assessment, [
                'kondisi_pondasi', 'kondisi_kolom',
                'kondisi_balok', 'kondisi_rangka',
                'kondisi_lantai', 'kondisi_dinding', 'kondisi_atap',
            ], ['moderate_damage', 'severe_damage_or_absent']);
            $critical_sanitation = in_array($assessment['sumber_air'] ?? '', ['other_unfit'], TRUE)
                || in_array($assessment['jenis_kloset'] ?? '', ['none'], TRUE);
            if ($damaged) {
                return $this->result('eligible', ['SIM_RTLH_DAMAGE']);
            }
            return $critical_sanitation
                ? $this->result('eligible', ['SIM_RTLH_SANITATION'])
                : $this->result('needs_data', ['SIM_RTLH_DAMAGE']);
        }

        if ($cek === 'pb_lahan') {
            $ready = $track === 'candidate_land'
                && ! empty($assessment['punya_lahan_calon'])
                && ! empty($assessment['candidate_land_address'])
                && ! empty($assessment['status_lahan_calon'])
                && ! empty($assessment['asal_lahan_calon'])
                && (float) ($assessment['panjang_lahan_m'] ?? 0) > 0
                && (float) ($assessment['lebar_lahan_m'] ?? 0) > 0
                && (float) ($assessment['luas_lahan_m2'] ?? 0) > 0;
            return $ready
                ? $this->result('eligible', ['SIM_PB_LAND_READY'])
                : $this->result('needs_data', ['SIM_PB_LAND_READY']);
        }

        if ($cek === 'swadaya') {
            if (empty($profile['mampu_swadaya'])) {
                return $this->result('needs_data', ['SIM_OMAH_DESIL4_SELF_HELP']);
            }
            if ($profile['mampu_swadaya'] !== 'capable') {
                return $this->result('not_eligible', ['SIM_OMAH_DESIL4_SELF_HELP']);
            }
            if ( ! in_array($track, ['existing_house', 'candidate_land'], TRUE)) {
                return $this->result('needs_data', ['SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI']);
            }
            return $this->result('needs_data', ['SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI']);
        }

        if ($cek !== 'pembiayaan') {
            throw new RuntimeException("Jenis cek aturan program tidak dikenal: $cek ($code)");
        }
        // Cabang formulir bukan status kepemilikan: nonpemilik mengisi
        // candidate_land pada UAT terbaru, termasuk calon pembiayaan.
        if (! in_array($track, ['financing', 'candidate_land'], TRUE)) {
            return $this->result('not_eligible', ['SIM_TRACK_TIDAK_SESUAI']);
        }
        if (empty($assessment['kepemilikan_rumah'])
            || ! array_key_exists('rumah_lain', $assessment)
            || $assessment['rumah_lain'] === NULL || $assessment['rumah_lain'] === '') {
            return $this->result('needs_data', ['SIM_KEBUTUHAN_RUMAH_BELUM_LENGKAP']);
        }
        if ($assessment['kepemilikan_rumah'] === 'owned'
            || (string) $assessment['rumah_lain'] !== '0') {
            return $this->result('not_eligible', ['SIM_KEBUTUHAN_RUMAH_TIDAK_MEMENUHI']);
        }
        if (empty($profile['kelompok_penghasilan']) && ! isset($profile['penghasilan_bulanan'])) {
            return $this->result('needs_data', ['SIM_INCOME_MISSING']);
        }
        return $this->result('potential', [$aturan['alasan']]);
    }

    /**
     * Rumah milik sendiri yang sudah layak huni menurut isian warga (permintaan dinas 26 Okt 2026): ketujuh
     * kondisi bangunan yang dinilai RTLH terisi dan tidak ada yang rusak sedang/berat, serta sanitasi tidak
     * kritis. Kebalikan persis dari pemicu eligible RTLH di evaluate(), jadi keduanya tidak bisa sama-sama benar.
     */
    public static function hunian_layak(array $assessment)
    {
        if (($assessment['jalur_penilaian'] ?? '') !== 'existing_house') { return FALSE; }
        foreach (['kondisi_pondasi', 'kondisi_kolom', 'kondisi_balok', 'kondisi_rangka', 'kondisi_lantai', 'kondisi_dinding', 'kondisi_atap'] as $field) {
            if ( ! in_array($assessment[$field] ?? NULL, ['good', 'minor_damage'], TRUE)) { return FALSE; }
        }
        return ($assessment['sumber_air'] ?? '') !== 'other_unfit' && ($assessment['jenis_kloset'] ?? '') !== 'none';
    }

    private function has_value(array $data, array $fields, array $values)
    {
        foreach ($fields as $field) {
            if (in_array($data[$field] ?? NULL, $values, TRUE)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function result($status, array $reasons)
    {
        return ['status_kelayakan' => $status, 'reason_codes' => $reasons];
    }
}
