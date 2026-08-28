<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

$routes->get('dbg', 'Auth::index');
$routes->get('logout', 'Auth::logout');
$routes->get('dashboard', 'User::index');
$routes->match(['get', 'post'], '/', function () {
    if (!empty($_GET['key']) && !empty($_GET['device_id'])) {
        $controller = new \App\Controllers\Randiapi();
        return $controller->index();
    }
    $controller = new \App\Controllers\Home();
    return $controller->index();
});
$routes->match(['get', 'post'], 'login', 'Auth::login');
$routes->match(['get', 'post'], 'register', 'Auth::register');
$routes->match(['get', 'post'], 'verify-otp', 'Auth::verify_otp');
$routes->get('resend-otp', 'Auth::resend_otp');

$routes->match(['get', 'post'], 'randiapi', function() {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->match(['get', 'post'], 'settings', 'User::settings');
$routes->post('settings/create-reset-link', 'User::create_reset_link');
$routes->get('settings/delete-reset-link/(:num)', 'User::delete_reset_link/$1');
$routes->get('settings/(:segment)/api/reset', 'User::api_reset/$1');
$routes->match(['get', 'post'], 'reset', 'User::reset_portal');
$routes->post('settings/save-telegram', 'User::save_telegram_token');
$routes->post('settings/test-telegram', 'User::test_telegram_token');
$routes->post('settings/register-webhook', 'User::register_telegram_webhook');
$routes->match(['get', 'post'], 'Server', 'User::Server');
$routes->match(['get', 'post'], 'New', 'Home::index');

// --- KEYS GROUP (UPDATED & SYNCED) ---
$routes->group('keys', function ($routes) {
    // Main List Page
    $routes->match(['get', 'post'], '/', 'Keys::index');
    
    // Generate Key
    $routes->match(['get', 'post'], 'generate', 'Keys::generate');
    
    // API for DataTable (Keys dikhane ke liye)
    $routes->get('api', 'Keys::api_get_keys');
    
    // Delete Functions (Menu Buttons)
    $routes->get('deleteUnused', 'Keys::deleteUnused');
    $routes->get('deleteExp', 'Keys::deleteExpired');
    
    // Action Buttons
    // 1. Device Reset (Single & Bulk)
    $routes->get('reset', 'Keys::api_key_reset');         // Single Key Reset ke liye
    $routes->get('resetMyDevices', 'Keys::resetMyDevices'); // Bulk Reset ke liye (Old Route)
    $routes->get('resetAll', 'Keys::resetAllKeys');       // Key Delete ke liye
    
    // Server Management AJAX Actions
    $routes->get('add_days', 'Keys::add_days');
    $routes->get('reset_all_devices', 'Keys::reset_all_devices');
    $routes->get('pause_all_keys', 'Keys::pause_all_keys');
    $routes->get('unpause_all_keys', 'Keys::unpause_all_keys');
    $routes->get('delete_all_keys', 'Keys::delete_all_keys');
    
    // 2. Edit, Block & Bulk Day Add
    $routes->post('edit', 'Keys::edit_key');              // Blocking aur Day Add handle karega
    $routes->get('(:num)', 'Keys::edit_key/$1');          // Single Edit View ke liye
});

// --- ADMIN GROUP ---
$routes->group('admin', ['filter' => 'admin'], function ($routes) {
	$routes->match(['get', 'post'], 'create-referral', 'User::ref_index');
	$routes->match(['get', 'post'], 'manage-users', 'User::manage_users');
	$routes->match(['get', 'post'], 'server-management', 'User::server_management');
	$routes->match(['get', 'post'], 'save-maintenance', 'User::save_maintenance');
	$routes->match(['get', 'post'], 'user/(:num)', 'User::user_edit/$1');
	$routes->get('user/delete/(:num)', 'User::user_delete/$1');
	$routes->match(['get', 'post'], 'delete-user-keys/(:num)', 'User::delete_user_keys/$1');
	$routes->match(['get', 'post'], 'pause-user-keys/(:num)', 'User::pause_user_keys/$1');
	$routes->match(['get', 'post'], 'unpause-user-keys/(:num)', 'User::unpause_user_keys/$1');
	
	$routes->group('api', function ($routes) {
		$routes->match(['get', 'post'], 'users', 'User::api_get_users');
	});
});

$routes->group('app-api', function ($routes) {
    $routes->get('/', 'AppApi::index');
    $routes->post('login', 'AppApi::login');
    $routes->post('config', 'AppApi::get_config');
    $routes->post('generate', 'AppApi::generate');
    $routes->post('reset', 'AppApi::reset_key');
    $routes->post('create-user', 'AppApi::create_user');
});

$routes->match(['get', 'post'], 'bsdkik', 'Bsdkik::index');
$routes->match(['get', 'post'], 'Rapidapi', 'RapidApi::index');
$routes->match(['get', 'post'], 'webhook', 'Telegram::index');
$routes->match(['get', 'post'], 'webhook/(:segment)', 'UserBot::index/$1');
$routes->match(['get', 'post'], 'botreset', 'Botreset::index');
$routes->match(['get', 'post'], 'botreset.php', 'Botreset::index');
$routes->match(['get', 'post'], 'connect', 'Connect::index');
$routes->match(['get', 'post'], 'safe', 'Safe::index');
$routes->match(['get', 'post'], 'login-key', 'Login::index');
$routes->match(['get', 'post'], 'api/login-key', 'Login::index');
/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
