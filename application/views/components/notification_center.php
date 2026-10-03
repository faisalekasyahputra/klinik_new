<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Tombol pengarah untuk galat: flashdata 'galat_aksi' = [[label, rute internal|NULL, utama?], ...].
// Rute hanya path internal (huruf, angka, _ - /); NULL = tombol tutup. Ditampilkan sebagai dialog.
$kpkp_aksi = [];
foreach ((array) $this->session->flashdata('galat_aksi') as $a) {
    if ( ! is_array($a) || ! is_string($a[0] ?? NULL) || trim($a[0]) === '') { continue; }
    $rute = $a[1] ?? NULL;
    if ($rute !== NULL && ! preg_match('#^[A-Za-z0-9_/\-]{1,100}$#D', (string) $rute)) { continue; }
    $kpkp_aksi[] = ['label' => strip_tags($a[0]), 'url' => $rute === NULL ? NULL : base_url($rute), 'utama' => ! empty($a[2])];
}

$kpkp_notifications = [];
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $message = $this->session->flashdata($type);
    if (is_scalar($message) && trim((string) $message) !== '') {
        $item = ['type' => $type, 'message' => strip_tags((string) $message)];
        if ($type === 'error' && $kpkp_aksi) { $item['aksi'] = $kpkp_aksi; }
        // Judul singkat yang menyebut masalahnya (Auth::_galat), menggantikan "Terjadi kesalahan".
        $kpkp_judul = $type === 'error' ? $this->session->flashdata('galat_judul') : NULL;
        if (is_string($kpkp_judul) && trim($kpkp_judul) !== '') { $item['title'] = strip_tags($kpkp_judul); }
        $kpkp_notifications[] = $item;
    }
}
$kpkp_notifications_json = json_encode(
    $kpkp_notifications,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<div class="kpkp-notification-region" data-kpkp-notification-region aria-label="Notifikasi" aria-live="polite" aria-relevant="additions text"></div>
<script type="application/json" data-kpkp-flash-notifications><?= $kpkp_notifications_json ?: '[]' ?></script>
