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

function housing_queue_can_transition($from, $to)
{
    $allowed = [
        'pending'  => ['approved', 'rejected'],
        'approved' => ['rejected'],
        'rejected' => ['approved'],
    ];

    return isset($allowed[$from]) && in_array($to, $allowed[$from], TRUE);
}
