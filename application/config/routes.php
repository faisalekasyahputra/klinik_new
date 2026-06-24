<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'index';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// --- Auth Routes (clean URLs) ---
$route['login']                    = 'Auth/login';
$route['register']                 = 'Auth/register';
$route['forgot-password']          = 'Auth/forgot_password';
$route['verify/(:any)']            = 'Auth/verify_email/$1';
$route['onboarding']               = 'Auth/onboarding';

// --- Pengaturan User ---
$route['akun']                     = 'Pengaturan/index';
$route['akun/update']              = 'Pengaturan/update_profile';
$route['akun/delete']              = 'Pengaturan/delete_account';

// --- Clean URLs for Index controller ---
$route['umum']                     = 'Index/umum';
$route['detail_perum/(:any)']      = 'Index/detail_perum/$1';
$route['profil']                   = 'Index/profil';
$route['tugas_pokok']              = 'Index/tugas_pokok';
$route['struktur']                 = 'Index/struktur';
$route['pengembang']               = 'Index/pengembang';
$route['kemitraan']                = 'Index/kemitraan';
$route['listkabupaten']            = 'Index/listkabupaten';
$route['simulasi_kpr']             = 'Index/simulasi_kpr';

// --- Bank Desain & Data Spasial ---
$route['materia']                  = 'Index/materia';
$route['sebaran']                  = 'Index/sebaran';
$route['sebaran_rusun']            = 'Index/sebaran_rusun';
$route['profil_kumuh']             = 'Index/profil_kumuh';
$route['sebaran_sdgs']             = 'Index/sebaran_sdgs';

// --- Pertanahan ---
$route['info_tanah']               = 'Index/info_tanah';
$route['sertifikasi']              = 'Index/sertifikasi';
$route['sengketa']                 = 'Index/sengketa';
$route['bank_tanah']               = 'Index/bank_tanah';

// --- User Profile ---
$route['pengaturan']               = 'Index/pengaturan';

// --- AJAX Endpoints ---
$route['ajax_articles']            = 'Index/ajax_articles';
$route['ajax_house_designs']       = 'Index/ajax_house_designs';
$route['ajax_perumahan']           = 'Index/ajax_perumahan';
$route['cari_wil']                 = 'Index/cari_wil';
$route['load_more']                = 'Index/load_more';

// --- API Integrations ---
$route['sikaper']                  = 'Sikaper/index';
