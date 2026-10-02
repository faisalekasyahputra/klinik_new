<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Membersihkan salinan nilai sensitif segera setelah pemakaiannya selesai. */
class Sensitive_buffer {
    public function wipe(&$value) {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $this->wipe($item);
            }
            unset($item);
            $value = [];
            return;
        }

        if (!is_string($value)) {
            $value = NULL;
            return;
        }

        if ($value !== '') {
            if (extension_loaded('sodium') && function_exists('sodium_memzero')) {
                sodium_memzero($value);
            } else {
                // PHP tanpa ekstensi sodium: timpa buffer lokal sebisa mungkin.
                // Salinan internal PHP/ekstensi tidak dapat dijamin ikut tertimpa.
                for ($i = 0, $n = strlen($value); $i < $n; $i++) {
                    $value[$i] = "\0";
                }
            }
        }
        $value = NULL;
    }
}
