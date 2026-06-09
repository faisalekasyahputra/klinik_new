<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Forum Helper
 * Fungsi keamanan dan sanitasi untuk Forum Diskusi Klinik PKP.
 */

if (!function_exists('contains_profanity')) {
    /**
     * Cek apakah teks mengandung kata terlarang.
     * @param string $text
     * @return array ['found' => bool, 'words' => string[]]
     */
    function contains_profanity($text) {
        $CI =& get_instance();
        $CI->config->load('profanity', TRUE);
        
        $words = $CI->config->item('profanity_words', 'profanity');
        $url_patterns = $CI->config->item('profanity_url_patterns', 'profanity');
        
        $found_words = [];
        $lower_text = mb_strtolower($text, 'UTF-8');
        
        foreach ($words as $word) {
            $pattern = '/\b' . preg_quote(mb_strtolower($word, 'UTF-8'), '/') . '\b/iu';
            if (preg_match($pattern, $lower_text)) {
                $found_words[] = $word;
            }
        }
        
        foreach ($url_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $found_words[] = '[URL mencurigakan]';
                break;
            }
        }
        
        return [
            'found' => count($found_words) > 0,
            'words' => $found_words
        ];
    }
}

if (!function_exists('sanitize_forum_input')) {
    /**
     * Bersihkan input forum dari tag HTML berbahaya.
     * @param string $text
     * @return string
     */
    function sanitize_forum_input($text) {
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = trim($text);
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text);
        return $text;
    }
}

if (!function_exists('check_forum_rate_limit')) {
    /**
     * Cek apakah user sudah melebihi batas posting.
     * @param string $table 'tb_diskusi' atau 'tb_komentar'
     * @param int $user_id ID user dari session
     * @param int $max_per_hour Maksimal posting per jam
     * @return bool TRUE jika masih boleh posting
     */
    function check_forum_rate_limit($table, $user_id, $max_per_hour = 5) {
        $CI =& get_instance();
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $count = $CI->db->where('user_id', $user_id)
                        ->where('created_at >=', $one_hour_ago)
                        ->count_all_results($table);
        
        return $count < $max_per_hour;
    }
}
