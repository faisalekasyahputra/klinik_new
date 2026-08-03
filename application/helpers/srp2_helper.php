<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar dokumen persyaratan SRP2 — SATU sumber kebenaran dipakai wizard
 * pemohon (Pengembang::dokumen_persyaratan()) dan verifikasi admin
 * (Admin_Srp2::detail()). Key harus persis sama dengan document_key yang
 * tersimpan di srp2_documents — jangan diubah tanpa migrasi data.
 */
/**
 * Keterangan tambahan per formulir — apa yang harus DILAMPIRKAN atau dari mana
 * datanya didapat. Revisi dinas 3 Agt 2026.
 *
 * HELPER TERPISAH, bukan mengubah nilai `srp2_dokumen_persyaratan()` menjadi
 * array. Empat pemakai daftar itu (Pengembang.php:166, :227-237, :295, dan
 * Admin_Srp2.php:103 -> admin/srp2/detail.php) semuanya memperlakukan nilainya
 * sebagai string dan menyambungnya ke pesan. Mengubah bentuknya berarti
 * menyentuh empat tempat demi satu tampilan — dan salah satunya pesan validasi
 * yang akan berubah jadi "Array" tanpa satu galat pun.
 *
 * ⚠️ BELUM LENGKAP, DAN ITU DISENGAJA. Dinas baru memberi CONTOH: "missal form
 * 4, lampirkan ktp, melampirkan SPT pph dst" plus form 10 & 11. Kata "missal"
 * dan "dst" menandakan daftarnya belum utuh. Sebelas formulir sisanya
 * DIBIARKAN KOSONG — mengarang syarat dokumen resmi jauh lebih berbahaya
 * daripada membiarkannya kosong, karena pemohon menyiapkan berkas berdasarkan
 * apa yang tertulis di sini. Tambahkan hanya setelah daftar resminya diterima.
 */
function srp2_keterangan_persyaratan() {
    return [
        'form_4'  => 'Lampirkan KTP dan SPT PPh.',
        'form_10' => 'Data bisa didapatkan dari asosiasi.',
        'form_11' => 'Data bisa didapatkan dari asosiasi.',
    ];
}

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
