<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/Rekam_Pbg.php';

/** Rekam Data - BPHTB. Seluruh alurnya milik Rekam_Pbg; yang beda hanya jenis dan pembacanya. */
class Rekam_Bphtb extends Rekam_Pbg {

    protected $jenis = 'bphtb';
    protected $label = 'BPHTB';
}
