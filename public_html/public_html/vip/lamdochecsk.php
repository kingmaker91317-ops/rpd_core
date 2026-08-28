<?php
const HMAC_KEY = 'LKTEAM_SECRET_KEY_HMAC';
const REQUEST_TOKEN = 'LKTEAM_SECRET_KEY_HMAC';


function create_response_signature($data_b64) {
    return hash_hmac('sha256', $data_b64, HMAC_KEY, false);
}

function authenticate_client($uid, $username) {
    if (empty($uid) || empty($username)) {
        return ['authenticated' => false, 'error' => 'Missing parameters'];
    }
    return ['authenticated' => true];
}

function generate_menu_data($uid, $username, $mode) {
    $menu_items = [
        ['t' => 'c', 'n' => 'Menu v7 FULL'],
        ['t' => 's', 'n' => 'Enable', 'i' => 1],
        ['t' => 's', 'n' => 'Aimbot Lock visible v2', 'i' => 5],
        ['t' => 's', 'n' => 'Aimbot Vip', 'i' => 3335],
        ['t' => 's', 'n' => 'Silent', 'i' => 2],
        ['t' => 's', 'n' => 'Ghost', 'i' => 883],
        ['t' => 'b', 'n' => 'Fov', 'a' => 0, 'b' => 360, 'u' => '%', 'i' => 4],
        ['t' => 'c', 'n' => 'Risk'],
        ['t' => 's', 'n' => 'Speed Hack', 'i' => 10],
        ['t' => 'c', 'n' => 'ESP'],
        ['t' => 's', 'n' => 'Draw Line', 'i' => 11],
        ['t' => 's', 'n' => 'Draw Box', 'i' => 12],
        ['t' => 's', 'n' => 'Draw Info', 'i' => 13],
        ['t' => 'b', 'n' => 'Draw Color', 'a' => 0, 'b' => 0, 'u' => 'Color', 'i' => 16]
    ];
    
    return [
        'status' => 'ok',
        'uid' => $uid,
        'username' => $username,
        'mode' => $mode,
        'menu' => $menu_items
    ];
}

$request_token = $_GET['token'] ?? $_POST['token'] ?? null;
$uid = $_GET['uid'] ?? $_POST['uid'] ?? null;
$username = $_GET['username'] ?? $_POST['username'] ?? null;
$mode = $_GET['mode'] ?? $_POST['mode'] ?? 'full';

$auth_result = authenticate_client($uid, $username, $mode);

if (!$auth_result['authenticated']) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => $auth_result['error']]);
    exit;
}

$menu_data = generate_menu_data($uid, $username, $mode);
$menu_json = json_encode($menu_data, JSON_UNESCAPED_SLASHES);
$data_b64 = base64_encode($menu_json);

$signature = create_response_signature($data_b64);

header('Content-Type: application/json');
echo json_encode([
    'data' => $data_b64,
    'sig' => $signature
], JSON_UNESCAPED_SLASHES);
