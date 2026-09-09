<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warga_ruleset {

    public const VERSION = 'SIM-2026-01';
    public const STATUS = 'active';
    public const EFFECTIVE_FROM = '2026-07-28 00:00:00';

    public function route_candidates($decile)
    {
        $decile = (int) $decile;
        if ($decile < 1 || $decile > 10) {
            return [];
        }
        if ($decile <= 3) {
            $candidates = ['rtlh', 'pb'];
        } elseif ($decile === 4) {
            $candidates = ['pb', 'omah_sekeng'];
        } elseif ($decile <= 8) {
            $candidates = ['flpp', 'oemah_lestari'];
        } else {
            $candidates = ['oemah_lestari'];
        }
        $candidates[] = 'rumah_apung';
        return $candidates;
    }

    public function evaluate($code, array $assessment, array $profile)
    {
        $decile = (int) ($profile['welfare_decile'] ?? 0);
        if ($decile < 1 || $decile > 10) {
            return $this->result('needs_data', ['SIM_DECILE_MISSING']);
        }
        if ($code === 'rumah_apung') {
            return $this->result('needs_data', ['SIM_RUMAH_APUNG_NEEDS_DATA']);
        }
        $target_deciles = [
            'rtlh' => [1, 2, 3],
            'pb' => [1, 2, 3, 4],
            'omah_sekeng' => [4],
            'flpp' => [5, 6, 7, 8],
            'oemah_lestari' => [5, 6, 7, 8, 9, 10],
        ];
        if ( ! isset($target_deciles[$code]) || ! in_array($decile, $target_deciles[$code], TRUE)) {
            return $this->result('not_eligible', ['SIM_DESIL_TIDAK_SESUAI']);
        }

        $track = $assessment['assessment_track'] ?? 'undetermined';
        if ($code === 'rtlh') {
            if ($track !== 'existing_house') {
                return $this->result('not_eligible', ['SIM_TRACK_TIDAK_SESUAI']);
            }
            $damaged = $this->has_value($assessment, [
                'foundation_condition_code', 'column_condition_code',
                'beam_condition_code', 'roof_frame_condition_code',
                'floor_condition_code', 'wall_condition_code', 'roof_condition_code',
            ], ['moderate_damage', 'severe_damage_or_absent']);
            $critical_sanitation = in_array($assessment['water_source_code'] ?? '', ['other_unfit'], TRUE)
                || in_array($assessment['latrine_type_code'] ?? '', ['none'], TRUE);
            if ($damaged) {
                return $this->result('eligible', ['SIM_RTLH_DAMAGE']);
            }
            return $critical_sanitation
                ? $this->result('eligible', ['SIM_RTLH_SANITATION'])
                : $this->result('needs_data', ['SIM_RTLH_DAMAGE']);
        }

        if ($code === 'pb') {
            $ready = $track === 'candidate_land'
                && ! empty($assessment['owns_candidate_land'])
                && ! empty($assessment['candidate_land_address'])
                && ! empty($assessment['candidate_land_title_code'])
                && ! empty($assessment['candidate_land_origin_code'])
                && (float) ($assessment['land_length_m'] ?? 0) > 0
                && (float) ($assessment['land_width_m'] ?? 0) > 0
                && (float) ($assessment['land_area_m2'] ?? 0) > 0;
            return $ready
                ? $this->result('eligible', ['SIM_PB_LAND_READY'])
                : $this->result('needs_data', ['SIM_PB_LAND_READY']);
        }

        if ($code === 'omah_sekeng') {
            if (empty($profile['self_help_capability_code'])) {
                return $this->result('needs_data', ['SIM_OMAH_DESIL4_SELF_HELP']);
            }
            if ($profile['self_help_capability_code'] !== 'capable') {
                return $this->result('not_eligible', ['SIM_OMAH_DESIL4_SELF_HELP']);
            }
            if ( ! in_array($track, ['existing_house', 'candidate_land'], TRUE)) {
                return $this->result('needs_data', ['SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI']);
            }
            return $this->result('needs_data', ['SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI']);
        }

        if ($track !== 'financing') {
            return $this->result('not_eligible', ['SIM_TRACK_TIDAK_SESUAI']);
        }
        if (empty($assessment['housing_status_code'])
            || ! array_key_exists('has_other_house', $assessment)
            || $assessment['has_other_house'] === NULL || $assessment['has_other_house'] === '') {
            return $this->result('needs_data', ['SIM_KEBUTUHAN_RUMAH_BELUM_LENGKAP']);
        }
        if ($assessment['housing_status_code'] === 'owned'
            || (string) $assessment['has_other_house'] !== '0') {
            return $this->result('not_eligible', ['SIM_KEBUTUHAN_RUMAH_TIDAK_MEMENUHI']);
        }
        if (empty($profile['income_band_code'])) {
            return $this->result('needs_data', ['SIM_INCOME_MISSING']);
        }
        return $this->result('potential', [
            $code === 'flpp' ? 'SIM_FLPP_INCOME' : 'SIM_OEMAH_INCOME',
        ]);
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
        return ['eligibility_status' => $status, 'reason_codes' => $reasons];
    }
}
