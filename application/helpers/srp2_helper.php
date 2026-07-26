<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar dokumen persyaratan SRP2 — SATU sumber kebenaran dipakai wizard
 * pemohon (Pengembang::dokumen_persyaratan()) dan verifikasi admin
 * (Admin_Srp2::detail()). Key harus persis sama dengan document_key yang
 * tersimpan di srp2_documents — jangan diubah tanpa migrasi data.
 */
function srp2_dokumen_persyaratan() {
    return [
        'form_1'  => 'Form 1 – Surat Permohonan SRP2',
        'form_2a' => 'Form 2.A – Data Administrasi dan Identitas Pengembang',
        'form_2b' => 'Form 2.B – Data Administrasi dan Data Pengurus',
        'form_3'  => 'Form 3 – Pernyataan Bukan ASN',
        'form_4'  => 'Form 4 – Laporan Keuangan dan Data Kepemilikan',
        'form_5'  => 'Form 5 – Ketersediaan SDM Penanggung Jawab Teknis',
        'form_6'  => 'Form 6 – Pengalaman Pekerjaan',
        'form_6b' => 'Form 6B – Rekomendasi Perusahaan Baru',
        'form_7'  => 'Form 7 – Kesanggupan Penyampaian Laporan',
        'form_8'  => 'Form 8 – Kebenaran Data',
        'form_9'  => 'Form 9 – Pakta Integritas',
        'form_10' => 'Form 10 – BA Verifikasi dan Validasi',
        'form_11' => 'Form 11 – BA Klasifikasi dan Kualifikasi',
        'form_13' => 'Form 13 – Laporan Pembangunan Perumahan',
    ];
}
