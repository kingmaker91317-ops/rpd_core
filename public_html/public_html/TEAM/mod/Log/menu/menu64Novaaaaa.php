<?php
// Database configuration
const DB_HOST = 'localhost';
const DB_USER = 'mbktunp_hama';
const DB_PASS = 'mbktunp_hama';
const DB_NAME = 'mbktunp_hama';

const HMAC_KEY = 'LKTEAM_SECRET_KEY_HMAC';
const REQUEST_TOKEN = 'LKTEAM_SECRET_KEY_HMAC';


function create_response_signature($data_b64) {
    return hash_hmac('sha256', $data_b64, HMAC_KEY, false);
}

function authenticate_client($uid, $username) {
    if (empty($uid) || empty($username)) {
        return ['authenticated' => false, 'error' => 'Missing parameters', 'debug' => 'uid or username empty'];
    }
    
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return ['authenticated' => false, 'error' => 'Database connection failed', 'debug' => $conn->connect_error];
    }
    
    // Query user from database
    $stmt = $conn->prepare("SELECT active_devices, max_devices FROM users WHERE username = ?");
    if (!$stmt) {
        $conn->close();
        return ['authenticated' => false, 'error' => 'Query preparation failed', 'debug' => $conn->error];
    }
    
    $stmt->bind_param('s', $username);
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['authenticated' => false, 'error' => 'Query execution failed', 'debug' => $stmt->error];
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['authenticated' => false, 'error' => 'token invalid', 'debug' => "username '$username' not found"];
    }
    
    $row = $result->fetch_assoc();
    $active_devices = $row['active_devices'] ?? '';
    $max_devices = (int)($row['max_devices'] ?? 2);
    $stmt->close();
    
    error_log("DEBUG: uid from request: '$uid'");
    error_log("DEBUG: active_devices: '$active_devices'");
    error_log("DEBUG: max_devices: $max_devices");
    
    // Parse active devices list
    $device_list = [];
    if (!empty($active_devices)) {
        $device_list = array_filter(array_map('trim', explode(',', $active_devices)));
    }
    
    // Check if UID already exists
    if (in_array($uid, $device_list, true)) {
        error_log("DEBUG: UID already registered - authentication success");
        $conn->close();
        return ['authenticated' => true, 'debug' => 'UID already registered'];
    }
    
    // UID is new - check if we can add more devices
    if (count($device_list) >= $max_devices) {
        error_log("DEBUG: Device limit reached ($max_devices). Current devices: " . count($device_list));
        $conn->close();
        return ['authenticated' => false, 'error' => 'token invalid', 'debug' => "Device limit reached ($max_devices)"];
    }
    
    // Add new UID to active devices
    $device_list[] = $uid;
    $updated_active_devices = implode(',', $device_list);
    
    error_log("DEBUG: Adding new device. Updated list: '$updated_active_devices'");
    
    // Update active_devices
    $update_stmt = $conn->prepare("UPDATE users SET active_devices = ? WHERE username = ?");
    if (!$update_stmt) {
        $conn->close();
        return ['authenticated' => false, 'error' => 'Update preparation failed', 'debug' => $conn->error];
    }
    
    $update_stmt->bind_param('ss', $updated_active_devices, $username);
    if (!$update_stmt->execute()) {
        $update_stmt->close();
        $conn->close();
        return ['authenticated' => false, 'error' => 'Update execution failed', 'debug' => $update_stmt->error];
    }
    
    $update_stmt->close();
    $conn->close();
    
    error_log("DEBUG: New device registered successfully");
    return ['authenticated' => true, 'debug' => 'New device registered'];
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
