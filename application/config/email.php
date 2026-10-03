<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Setelan pustaka Email CodeIgniter. Kredensial dari .env; dipakai libraries/Otp_pendaftaran.php. */
$config['protocol']     = 'smtp';
$config['smtp_host']    = getenv('SMTP_HOST') ?: '';
$config['smtp_port']    = (int) (getenv('SMTP_PORT') ?: 587);
$config['smtp_user']    = getenv('SMTP_USER') ?: '';
$config['smtp_pass']    = getenv('SMTP_PASS') ?: '';
$config['smtp_crypto']  = getenv('SMTP_CRYPTO') ?: 'tls'; // tls = STARTTLS (587), ssl = 465
$config['smtp_timeout'] = 10;
$config['mailtype']     = 'text';
$config['charset']      = 'utf-8';
$config['newline']      = "\r\n";
$config['crlf']         = "\r\n";
