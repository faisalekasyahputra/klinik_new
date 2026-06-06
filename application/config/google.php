<?php
defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Google OAuth 2.0 Configuration
 * 
 * Kredensial dibaca dari file .env melalui getenv().
 * Jika environment variable tidak tersedia, fallback ke nilai kosong
 * sehingga error akan terdeteksi saat login, bukan saat load config.
 */
$config['google']['client_id']        = getenv('GOOGLE_CLIENT_ID') ?: '';
$config['google']['client_secret']    = getenv('GOOGLE_CLIENT_SECRET') ?: '';
$config['google']['redirect_uri']     = getenv('GOOGLE_REDIRECT_URI') ?: '';
$config['google']['scopes']           = array('email', 'profile');