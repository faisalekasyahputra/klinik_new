<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fondasi form warga komprehensif.
 *
 * Data akun tetap di usr_users; tabel di sini menyimpan profil domain,
 * snapshot sumber, draft/revisi assessment, evidence, dan hasil ruleset.
 * Kolom yang menunjuk usr_users.id wajib INT signed.
 */
class Migration_Add_warga_assessment_foundation extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sf_citizen_profiles` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT NOT NULL,
              `source_mode` VARCHAR(20) NOT NULL DEFAULT 'simulation',
              `nik_ciphertext` TEXT NOT NULL,
              `nik_lookup_hash` CHAR(64) NOT NULL,
              `family_card_ciphertext` TEXT NULL,
              `family_card_lookup_hash` CHAR(64) NULL,
              `full_name_ciphertext` TEXT NOT NULL,
              `address_ciphertext` TEXT NULL,
              `phone_ciphertext` TEXT NULL,
              `birth_date_ciphertext` TEXT NULL,
              `tax_number_ciphertext` TEXT NULL,
              `gender_code` VARCHAR(30) NULL,
              `marital_status_code` VARCHAR(30) NULL,
              `education_code` VARCHAR(30) NULL,
              `occupation_code` VARCHAR(50) NULL,
              `income_band_code` VARCHAR(30) NULL,
              `welfare_decile` TINYINT UNSIGNED NULL,
              `has_savings` TINYINT(1) NULL,
              `self_help_capability_code` VARCHAR(30) NULL,
              `self_help_amount` DECIMAL(15,2) NULL,
              `field_provenance_json` LONGTEXT NULL,
              `confirmed_at` DATETIME NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_sf_citizen_profiles_user` (`user_id`),
              UNIQUE KEY `uq_sf_citizen_profiles_nik_hash` (`nik_lookup_hash`),
              KEY `idx_sf_citizen_profiles_kk_hash` (`family_card_lookup_hash`),
              CONSTRAINT `fk_sf_citizen_profiles_user`
                FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sf_simperum_snapshots` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `nik_lookup_hash` CHAR(64) NOT NULL,
              `source_mode` VARCHAR(20) NOT NULL,
              `source_record_key` VARCHAR(100) NULL,
              `response_status` VARCHAR(20) NOT NULL,
              `api_version` VARCHAR(50) NULL,
              `http_status` SMALLINT UNSIGNED NULL,
              `error_code` VARCHAR(80) NULL,
              `payload_ciphertext` LONGTEXT NULL,
              `payload_sha256` CHAR(64) NULL,
              `fetched_at` DATETIME NOT NULL,
              `expires_at` DATETIME NOT NULL,
              `requested_by` INT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_sf_snapshots_nik_fetched` (`nik_lookup_hash`, `fetched_at`),
              KEY `idx_sf_snapshots_cache` (`nik_lookup_hash`, `response_status`, `expires_at`),
              KEY `idx_sf_snapshots_requested_by` (`requested_by`),
              CONSTRAINT `fk_sf_snapshots_requested_by`
                FOREIGN KEY (`requested_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sf_housing_assessments` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT NULL,
              `citizen_profile_id` BIGINT UNSIGNED NULL,
              `previous_version_id` BIGINT UNSIGNED NULL,
              `kabupaten_id` INT UNSIGNED NULL,
              `assessment_track` VARCHAR(30) NOT NULL DEFAULT 'undetermined',
              `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
              `current_step` VARCHAR(40) NOT NULL DEFAULT 'find_data',
              `version_no` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
              `lock_version` INT UNSIGNED NOT NULL DEFAULT 0,
              `simperum_snapshot_id` BIGINT UNSIGNED NULL,
              `source_mode` VARCHAR(20) NOT NULL DEFAULT 'simulation',
              `profile_snapshot_ciphertext` LONGTEXT NULL,
              `field_provenance_json` LONGTEXT NULL,
              `housing_status_code` VARCHAR(40) NULL,
              `land_title_code` VARCHAR(40) NULL,
              `has_other_land` TINYINT(1) NULL,
              `has_other_house` TINYINT(1) NULL,
              `house_area_m2` DECIMAL(10,2) NULL,
              `occupant_count` SMALLINT UNSIGNED NULL,
              `family_count` SMALLINT UNSIGNED NULL,
              `assistance_source_code` VARCHAR(50) NULL,
              `assistance_year` SMALLINT UNSIGNED NULL,
              `area_condition_code` VARCHAR(50) NULL,
              `owns_candidate_land` TINYINT(1) NULL,
              `candidate_land_address_ciphertext` TEXT NULL,
              `candidate_land_title_code` VARCHAR(40) NULL,
              `candidate_land_origin_code` VARCHAR(40) NULL,
              `land_owner_relationship_code` VARCHAR(40) NULL,
              `land_length_m` DECIMAL(10,2) NULL,
              `land_width_m` DECIMAL(10,2) NULL,
              `land_area_m2` DECIMAL(12,2) NULL,
              `foundation_condition_code` VARCHAR(40) NULL,
              `column_condition_code` VARCHAR(40) NULL,
              `beam_condition_code` VARCHAR(40) NULL,
              `sloof_condition_code` VARCHAR(40) NULL,
              `ceiling_condition_code` VARCHAR(40) NULL,
              `roof_frame_condition_code` VARCHAR(40) NULL,
              `floor_material_code` VARCHAR(50) NULL,
              `floor_condition_code` VARCHAR(40) NULL,
              `wall_material_code` VARCHAR(50) NULL,
              `wall_condition_code` VARCHAR(40) NULL,
              `roof_material_code` VARCHAR(50) NULL,
              `roof_condition_code` VARCHAR(40) NULL,
              `has_window` TINYINT(1) NULL,
              `has_ventilation` TINYINT(1) NULL,
              `water_source_code` VARCHAR(50) NULL,
              `has_bathroom_latrine` TINYINT(1) NULL,
              `latrine_type_code` VARCHAR(50) NULL,
              `feces_disposal_code` VARCHAR(50) NULL,
              `septic_distance_code` VARCHAR(20) NULL,
              `lighting_source_code` VARCHAR(40) NULL,
              `cooking_fuel_code` VARCHAR(40) NULL,
              `location_lat_ciphertext` TEXT NULL,
              `location_lng_ciphertext` TEXT NULL,
              `location_accuracy_m` DECIMAL(10,2) NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `submitted_at` DATETIME NULL,
              PRIMARY KEY (`id`),
              KEY `idx_sf_assessments_owner_status` (`user_id`, `status`),
              KEY `idx_sf_assessments_scope_status` (`kabupaten_id`, `status`),
              KEY `idx_sf_assessments_profile` (`citizen_profile_id`),
              KEY `idx_sf_assessments_previous` (`previous_version_id`),
              KEY `idx_sf_assessments_snapshot` (`simperum_snapshot_id`),
              CONSTRAINT `fk_sf_assessments_user`
                FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL,
              CONSTRAINT `fk_sf_assessments_profile`
                FOREIGN KEY (`citizen_profile_id`) REFERENCES `sf_citizen_profiles` (`id`) ON DELETE SET NULL,
              CONSTRAINT `fk_sf_assessments_previous`
                FOREIGN KEY (`previous_version_id`) REFERENCES `sf_housing_assessments` (`id`) ON DELETE SET NULL,
              CONSTRAINT `fk_sf_assessments_kabupaten`
                FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id`) ON DELETE SET NULL,
              CONSTRAINT `fk_sf_assessments_snapshot`
                FOREIGN KEY (`simperum_snapshot_id`) REFERENCES `sf_simperum_snapshots` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sf_assessment_files` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `assessment_id` BIGINT UNSIGNED NOT NULL,
              `file_kind` VARCHAR(50) NOT NULL,
              `private_path` VARCHAR(500) NOT NULL,
              `original_name_ciphertext` TEXT NULL,
              `mime_type` VARCHAR(100) NOT NULL,
              `size_bytes` BIGINT UNSIGNED NOT NULL,
              `sha256` CHAR(64) NOT NULL,
              `uploaded_by` INT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `verified_by` INT NULL,
              `verified_at` DATETIME NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_sf_assessment_files_kind` (`assessment_id`, `file_kind`),
              KEY `idx_sf_assessment_files_uploaded_by` (`uploaded_by`),
              KEY `idx_sf_assessment_files_verified_by` (`verified_by`),
              CONSTRAINT `fk_sf_assessment_files_assessment`
                FOREIGN KEY (`assessment_id`) REFERENCES `sf_housing_assessments` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_sf_assessment_files_uploaded_by`
                FOREIGN KEY (`uploaded_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL,
              CONSTRAINT `fk_sf_assessment_files_verified_by`
                FOREIGN KEY (`verified_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sf_assessment_recommendations` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `assessment_id` BIGINT UNSIGNED NOT NULL,
              `program_id` INT NOT NULL,
              `ruleset_version` VARCHAR(40) NOT NULL,
              `eligibility_status` VARCHAR(30) NOT NULL,
              `reason_codes_json` LONGTEXT NULL,
              `input_snapshot_sha256` CHAR(64) NOT NULL,
              `evaluated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_sf_recommendations_run`
                (`assessment_id`, `program_id`, `ruleset_version`),
              KEY `idx_sf_recommendations_program` (`program_id`),
              CONSTRAINT `fk_sf_recommendations_assessment`
                FOREIGN KEY (`assessment_id`) REFERENCES `sf_housing_assessments` (`id`) ON DELETE CASCADE,
              CONSTRAINT `fk_sf_recommendations_program`
                FOREIGN KEY (`program_id`) REFERENCES `sf_programs` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $this->db->query("
            ALTER TABLE `sf_housing_queue`
              ADD COLUMN IF NOT EXISTS `assessment_id` BIGINT UNSIGNED NULL AFTER `id`,
              ADD COLUMN IF NOT EXISTS `source_mode` VARCHAR(20) NOT NULL DEFAULT 'legacy'
                AFTER `data_survey_json`;
        ");

        if ( ! $this->_key_exists('sf_housing_queue', 'uq_sf_queue_assessment')) {
            $this->db->query("
                ALTER TABLE `sf_housing_queue`
                  ADD UNIQUE KEY `uq_sf_queue_assessment` (`assessment_id`);
            ");
        }
        if ( ! $this->_constraint_exists('sf_housing_queue', 'fk_sf_queue_assessment')) {
            $this->db->query("
                ALTER TABLE `sf_housing_queue`
                  ADD CONSTRAINT `fk_sf_queue_assessment`
                    FOREIGN KEY (`assessment_id`) REFERENCES `sf_housing_assessments` (`id`) ON DELETE SET NULL;
            ");
        }
    }

    public function down()
    {
        if ($this->_constraint_exists('sf_housing_queue', 'fk_sf_queue_assessment')) {
            $this->db->query("
                ALTER TABLE `sf_housing_queue`
                  DROP FOREIGN KEY `fk_sf_queue_assessment`;
            ");
        }
        $this->db->query("
            ALTER TABLE `sf_housing_queue`
              DROP COLUMN IF EXISTS `assessment_id`,
              DROP COLUMN IF EXISTS `source_mode`;
        ");

        $this->db->query("DROP TABLE IF EXISTS `sf_assessment_recommendations`;");
        $this->db->query("DROP TABLE IF EXISTS `sf_assessment_files`;");
        $this->db->query("DROP TABLE IF EXISTS `sf_housing_assessments`;");
        $this->db->query("DROP TABLE IF EXISTS `sf_simperum_snapshots`;");
        $this->db->query("DROP TABLE IF EXISTS `sf_citizen_profiles`;");
    }

    private function _key_exists($table, $key_name)
    {
        return (bool) $this->db
            ->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])
            ->row();
    }

    private function _constraint_exists($table, $constraint_name)
    {
        return (bool) $this->db->query("
            SELECT 1
              FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
             LIMIT 1
        ", [$table, $constraint_name])->row();
    }
}

