<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
	require SYSTEMPATH . 'Config/Routes.php';
}


$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);


$routes->get('dbg', 'Auth::index');
$routes->get('logout', 'Auth::logout');
$routes->get('dashboard', 'User::index');
$routes->match(['get', 'post'], '/', 'Auth::login');
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->match(['get', 'post'], 'register', 'Auth::register');//Server



//
$routes->match(['get', 'post'], 'settings', 'User::settings');
$routes->match(['get', 'post'], 'Server', 'User::Server');
$routes->match(['get', 'post'], 'Server/moduleSettings', 'User::moduleSettings');
$routes->match(['get', 'post'], 'Server/lib-online', 'User::Server');

$routes->match(['get', 'post'], 'New', 'Home::index');

$routes->group('keys', function ($routes) {
	$routes->match(['get', 'post'], '/', 'Keys::index');
	$routes->match(['get', 'post'], 'generate', 'Keys::generate');
	$routes->get('reset', 'Keys::api_key_reset');
	$routes->get('delete', 'Keys::api_key_delete');
	$routes->get('edit/(:segment)', 'Keys::edit_key/$1');
	$routes->post('edit', 'Keys::edit_key');
	$routes->match(['get', 'post'], 'api', 'Keys::api_get_keys');
	$routes->get('delExp', 'Keys::delExpkeys');
	$routes->match(['get', 'post'], 'admin/key-free-settings', 'KeyFreeSettings::index', ['filter' => 'admin']);
	$routes->match(['get', 'post'], 'rename', 'User::moduleName');
	//Key Free
	$routes->group('free', function ($routes) {
        $routes->match(['get', 'post'], '/', 'Keys::free');
        $routes->get('new', 'Keys::free_action');
    });
	$routes->get('(:segment)', 'Keys::edit_key/$1');
});

$routes->group('admin', ['filter' => 'admin'], function ($routes) {
    $routes->match(['get', 'post'], 'create-referral', 'User::ref_index');
    $routes->match(['get', 'post'], 'manage-users', 'User::manage_users');
    $routes->match(['get', 'post'], 'user/(:num)', 'User::user_edit/$1');
    $routes->match(['get', 'post'], 'lib-online', 'User::lib');
    $routes->get('user_delete/(:num)', 'User::user_delete/$1');
    $routes->post('user_update-saldo', 'User::update_saldo');
    $routes->match(['get', 'post'], 'seller-contracts', 'SellerContracts::index');
    $routes->post('seller-contracts/update/(:num)', 'SellerContracts::update/$1');
    /* --------------------------- Admin API Grouping -------------------------- */
    $routes->group('api', function ($routes) {
        $routes->match(['get', 'post'], 'users', 'User::api_get_users');
    });
});

// FILE MANAGER //
$routes->group('libOnline', function ($routes) {
    $routes->get('/', 'LibOnline::index');
    $routes->get('delete/(:segment)', 'LibOnline::delete/$1');
    $routes->get('download/(:segment)', 'LibOnline::download/$1');
    $routes->post('upload', 'LibOnline::upload');
});

// GAMES MANAGER //
$routes->group('games', ['filter' => 'admin'], function ($routes) {
    $routes->get('/', 'Games::index');
    $routes->post('add', 'Games::add');
    $routes->post('edit/(:num)', 'Games::edit/$1');
    $routes->get('toggle/(:num)', 'Games::toggle/$1');
    $routes->post('maintenance/(:num)', 'Games::maintenance/$1');
    $routes->get('delete/(:num)', 'Games::delete/$1');
    $routes->match(['get', 'post'], 'assign/(:num)', 'Games::assignUsers/$1');
});

$routes->match(['get', 'post'], 'connect', 'Connect::index');
$routes->match(['get', 'post'], 'verify', 'VerifyLib::index');
$routes->match(['get', 'post'], 'verifyPassword', 'FirebaseAuth::verifyPassword');


$routes->get('uploads/module_icons/(:any)', function($filename) {
    $path = ROOTPATH . 'public/uploads/module_icons/' . $filename;
    if (file_exists($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png':
                $type = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $type = 'image/jpeg';
                break;
            case 'gif':
                $type = 'image/gif';
                break;
            case 'webp':
                $type = 'image/webp';
                break;
            case 'svg':
                $type = 'image/svg+xml';
                break;
            default:
                $type = 'application/octet-stream';
        }
        header('Content-Type: ' . $type);
        readfile($path);
        exit;
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->get('uploads/menu-logos/(:any)', function($filename) {
    $path = ROOTPATH . 'public/uploads/menu-logos/' . $filename;
    if (file_exists($path)) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'png':
                $type = 'image/png';
                break;
            case 'jpg':
            case 'jpeg':
                $type = 'image/jpeg';
                break;
            case 'gif':
                $type = 'image/gif';
                break;
            case 'webp':
                $type = 'image/webp';
                break;
            case 'svg':
                $type = 'image/svg+xml';
                break;
            default:
                $type = 'application/octet-stream';
        }
        header('Content-Type: ' . $type);
        readfile($path);
        exit;
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
