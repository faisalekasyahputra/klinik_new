<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function housing_queue_statuses()
{
    return [
        'pending'  => ['label' => 'Menunggu Verifikasi', 'badge' => 'pending'],
        'needs_revision' => ['label' => 'Perlu Perbaikan', 'badge' => 'process'],
        'approved' => ['label' => 'Disetujui', 'badge' => 'ok'],
        'rejected' => ['label' => 'Ditolak', 'badge' => 'reject'],
    ];
}

/**
 * Nama tampil antrean TANPA program (program_id NULL, migrasi 086): rumah sudah layak huni bila sumber bantuannya
 * "already_habitable", selain itu tidak ada program yang cocok (mis. disaring matriks, pilihan B dinas 27 Okt 2026).
 */
function label_tanpa_program($bantuan_perumahan)
{
    return $bantuan_perumahan === 'already_habitable' ? 'Hunian sudah layak huni' : 'Belum ada program yang cocok';
}

function housing_queue_can_transition($from, $to)
{
    $allowed = [
        'pending'  => ['approved', 'rejected'],
        'approved' => ['rejected'],
        'rejected' => ['approved'],
    ];

    return isset($allowed[$from]) && in_array($to, $allowed[$from], TRUE);
}
