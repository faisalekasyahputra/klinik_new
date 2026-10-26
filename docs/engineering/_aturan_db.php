<?php
/**
 * Pembantu suite: mesin rekomendasi dengan aturan dari DB lokal (migrasi 084 dan 085) untuk uji yang memakai
 * Warga_ruleset dan Matriks_program_ruleset tanpa CodeIgniter. Tanpa kode versi = versi terbit, sama seperti
 * aplikasi. Hanya DB lokal; panggil require library lebih dulu.
 */
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php';

function aturan_db_uji() {
    static $db = NULL;
    if ($db !== NULL) { return $db; }
    $env = [];
    foreach (file(env_berkas_path(dirname(__DIR__, 2)), FILE_IGNORE_NEW_LINES) as $l) {
        if (strpos($l, '=') === FALSE || ltrim($l)[0] === '#') { continue; }
        [$k, $v] = explode('=', $l, 2);
        $env[trim($k)] = trim($v, " \t\"'");
    }
    if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
        fwrite(STDERR, "aturan_db_uji: hanya untuk DB lokal\n");
        exit(1);
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
    $db->set_charset('utf8mb4');
    return $db;
}

function aturan_kode_aktif_uji() {
    return aturan_db_uji()->query("SELECT kode FROM aturan_versi WHERE status = 'terbit' ORDER BY diterbitkan_pada DESC, id DESC LIMIT 1")->fetch_row()[0];
}

function aturan_baris_uji($sql, $kode) {
    $st = aturan_db_uji()->prepare($sql);
    $st->bind_param('s', $kode);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function mesin_matriks_uji($kode = NULL) {
    $kode = $kode ?? aturan_kode_aktif_uji();
    return new Matriks_program_ruleset(Matriks_program_ruleset::dari_baris_db(
        aturan_baris_uji('SELECT * FROM aturan_matriks_baris WHERE versi = ? ORDER BY urutan', $kode)), $kode);
}

function mesin_warga_uji($kode = NULL) {
    $kode = $kode ?? aturan_kode_aktif_uji();
    return new Warga_ruleset(Warga_ruleset::dari_baris_db(aturan_baris_uji('SELECT p.kode_program, a.desil_sasaran, a.kandidat_tanpa_desil,
        a.jenis_cek, a.kode_alasan FROM aturan_program a JOIN sf_program p ON p.id = a.program_id WHERE a.versi = ? ORDER BY a.urutan', $kode)), $kode);
}
