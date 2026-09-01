<?php
/**
 * Penjelajah wizard warga yang MEMBACA FORMULIR SUNGGUHAN.
 *
 * Dipakai bersama oleh harness perjalanan warga. Bukan skrip yang berdiri
 * sendiri; tidak melakukan apa pun kalau di-`require` tanpa dipanggil.
 *
 * KENAPA MEMBACA FORM, BUKAN MENULISKAN DAFTAR MEDAN. Wizard ini sudah
 * beberapa kali berubah bentuk (step `citizen_data` dibuang di migrasi 049,
 * lima kolom matriks ditambahkan di migrasi 045-048) dan dokumentasinya
 * TIDAK selalu ikut. Harness yang menghafal daftar medan akan merah tiap kali
 * wizard-nya bergeser, dan merahnya menuduh fitur padahal yang usang harness
 * itu sendiri. Yang dilakukan di sini: buka halamannya, baca `<input>`,
 * `<select>`, `<textarea>` yang benar-benar ada, isi seadanya yang sah, kirim
 * balik. Wizard berubah, penjelajah ini ikut tanpa disentuh.
 *
 * Yang SENGAJA tidak ditebak: nilai `<select>` diambil dari `<option>` yang
 * tersedia, bukan dikarang. Mengarang kode enum berarti menguji penolakan
 * validasi, bukan alur yang benar.
 */

/**
 * Parser memakai DOMDocument, BUKAN regex.
 *
 * Versi pertama memakai regex dan gagal dengan cara yang tidak terbaca: nama
 * `<select>` tertangkap tapi daftar `<option>`-nya kosong, sehingga ketujuh
 * medan matrix_* terkirim kosong dan wizard menolak "wajib dipilih" tanpa
 * petunjuk apa pun. Pola yang persis sama berjalan benar di luar fungsi,
 * jadi waktu terbuang untuk mengejar hantu. DOMDocument bagian dari stdlib
 * PHP, memang untuk pekerjaan ini, dan menghapus seluruh kelas bug itu.
 */
if ( ! function_exists('wizard_dom')) {
    function wizard_dom($html) {
        $doc = new DOMDocument();
        /* HTML halaman ini tidak selalu XML-valid dan itu wajar; peringatan
           libxml dimatikan supaya keluaran uji tidak tenggelam olehnya. */
        $sebelumnya = libxml_use_internal_errors(TRUE);
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);
        return $doc;
    }
}

if ( ! function_exists('wizard_form')) {
    /**
     * Node <form> wizard. Dikenali dari kehadiran medan `step` di dalamnya:
     * halaman pendataan juga memuat form login/daftar (modal), dan mengambil
     * form pertama begitu saja berarti mengirim kredensial, bukan draft.
     */
    function wizard_form($html) {
        $doc = wizard_dom($html);
        foreach ($doc->getElementsByTagName('form') as $form) {
            foreach ($form->getElementsByTagName('input') as $in) {
                if ($in->getAttribute('name') === 'step') { return $form; }
            }
        }
        return NULL;
    }
}

if ( ! function_exists('wizard_step')) {
    /** Step yang sedang aktif menurut formulirnya sendiri. */
    function wizard_step($html) {
        $form = $html instanceof DOMElement ? $html : wizard_form($html);
        if ($form === NULL) { return NULL; }
        foreach ($form->getElementsByTagName('input') as $in) {
            if ($in->getAttribute('name') === 'step') { return $in->getAttribute('value'); }
        }
        return NULL;
    }
}

if ( ! function_exists('wizard_medan')) {
    /**
     * Bangun payload POST dari satu formulir wizard.
     *
     * @param DOMElement $form  Node formulir dari wizard_form().
     * @param array      $paksa Nilai yang dipaksakan (mis. action=save).
     */
    function wizard_medan($form, array $paksa = []) {
        if ( ! $form instanceof DOMElement) { return $paksa; }
        $out = [];

        foreach ($form->getElementsByTagName('input') as $in) {
            $nama = $in->getAttribute('name');
            if ($nama === '') { continue; }
            $tipe = strtolower($in->getAttribute('type') ?: 'text');
            $nilai = $in->getAttribute('value');

            if (in_array($tipe, ['submit', 'button', 'file', 'reset'], TRUE)) { continue; }
            if ($tipe === 'checkbox') {
                /* Checkbox tak tercentang memang tidak terkirim. Dicentang di
                   sini supaya jalur "ya" ikut terlewati; kalau ada checkbox
                   yang berbahaya kalau menyala, kirim namanya lewat $paksa. */
                $out[$nama] = $nilai !== '' ? $nilai : '1';
                continue;
            }
            if ($tipe === 'radio') {
                // Satu nilai per nama; yang pertama menang, jangan ditumpuk.
                if ( ! array_key_exists($nama, $out)) { $out[$nama] = $nilai; }
                continue;
            }
            if ($tipe === 'hidden' || $nilai !== '') { $out[$nama] = $nilai; continue; }

            if ($tipe === 'number') {
                $out[$nama] = wizard_angka_wajar($nama);
            } elseif ($tipe === 'date') {
                $out[$nama] = '1990-01-01';
            } else {
                $out[$nama] = 'Uji ' . $nama;
            }
        }

        foreach ($form->getElementsByTagName('select') as $sel) {
            $nama = $sel->getAttribute('name');
            if ($nama === '') { continue; }
            $tersedia = [];
            foreach ($sel->getElementsByTagName('option') as $opt) {
                $v = $opt->getAttribute('value');
                if (trim($v) !== '') { $tersedia[] = $v; }
            }
            $out[$nama] = wizard_pilih_opsi($nama, $tersedia);
        }

        foreach ($form->getElementsByTagName('textarea') as $ta) {
            $nama = $ta->getAttribute('name');
            if ($nama === '') { continue; }
            $isi = trim($ta->textContent);
            $out[$nama] = $isi !== '' ? $isi : 'Diisi oleh harness uji.';
        }

        return $paksa + $out;
    }
}

if ( ! function_exists('wizard_angka_wajar')) {
    /**
     * Angka yang MASUK AKAL per nama medan, bukan 1 untuk semuanya.
     * Luas 0 atau jumlah penghuni 0 bisa ditolak validasi atau menggeser
     * hasil rekomendasi, dan uji yang merah karena angka konyol tidak
     * memberi tahu apa pun tentang alurnya.
     */
    function wizard_angka_wajar($nama) {
        if (strpos($nama, 'area') !== FALSE || strpos($nama, 'length') !== FALSE
            || strpos($nama, 'width') !== FALSE) { return '36'; }
        if (strpos($nama, 'count') !== FALSE) { return '4'; }
        if (strpos($nama, 'year') !== FALSE) { return '2024'; }
        if (strpos($nama, 'amount') !== FALSE || strpos($nama, 'income') !== FALSE
            || strpos($nama, 'penghasilan') !== FALSE) { return '2500000'; }
        if (strpos($nama, 'accuracy') !== FALSE) { return '10'; }
        return '1';
    }
}

if ( ! function_exists('wizard_step')) {
    /** Step yang sedang aktif menurut formulirnya sendiri. */
    function wizard_step($html) {
        $form = wizard_form($html);
        if ($form === NULL) { return NULL; }
        return preg_match('/name="step"\s+value="([^"]*)"/', $form, $m) ? $m[1] : NULL;
    }
}

if ( ! function_exists('wizard_pilih_opsi')) {
    /**
     * Pilih nilai `<option>` yang MENGARAH ke hasil yang bisa diajukan.
     *
     * Kenapa bukan sekadar opsi pertama: opsi pertama untuk seluruh medan
     * menghasilkan profil yang sah tapi TIDAK LOLOS satu program pun, dan
     * step `review` lalu tidak merender radio `recommendation_id` sama sekali
     * (pendataan.php merendernya hanya saat status `eligible`/`potential`).
     * Akibatnya submit ditolak "rekomendasi tidak dapat diajukan" - benar
     * menurut aturan, tapi tidak menguji apa pun soal alur pengajuan.
     *
     * Sasarannya RTLH, karena syaratnya paling sedikit menurut
     * Warga_ruleset::evaluate(): track `existing_house` (jalur bawaan draft
     * manual) plus minimal satu kondisi bangunan `moderate_damage` atau
     * `severe_damage_or_absent` sudah cukup untuk `eligible` (SIM_RTLH_DAMAGE).
     *
     * Preferensi HANYA dipakai kalau nilainya memang ditawarkan halaman;
     * kalau tidak ada, jatuh kembali ke opsi pertama. Jadi wizard yang
     * berganti kode enum tidak membuat penjelajah ini mengarang nilai.
     */
    function wizard_pilih_opsi($nama, array $tersedia) {
        if ( ! $tersedia) { return ''; }

        $prefer = [];
        if (strpos($nama, 'condition_code') !== FALSE) {
            $prefer = ['severe_damage_or_absent', 'moderate_damage'];
        } elseif ($nama === 'water_source_code') {
            $prefer = ['other_unfit'];
        } elseif ($nama === 'latrine_type_code') {
            $prefer = ['none'];
        } elseif ($nama === 'self_help_capability_code') {
            $prefer = ['capable'];
        }

        foreach ($prefer as $p) {
            if (in_array($p, $tersedia, TRUE)) { return $p; }
        }
        return $tersedia[0];
    }
}
