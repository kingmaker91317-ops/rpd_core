<?php
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$ENC_KEY = '89203948209348203948209342345678';

function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData($data, $key) {
    if (empty($data)) return '';
    $raw = base64_decode($data);
    if (strlen($raw) < 17) return '';
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    return openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function sendResponse($data, $key) {
    $json = json_encode($data);
    echo encryptData($json, $key);
}

// Database configuration
$db_host = 'localhost';
$db_user = 'mbktunp_hama';
$db_pass = 'mbktunp_hama';
$db_name = 'mbktunp_hama';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    sendResponse(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu'], $ENC_KEY);
    die();
}

$conn->set_charset("utf8mb4");

// Get request data (JSON body or POST)
$rawBody = file_get_contents('php://input');
$jsonBody = null;

if (!empty($rawBody)) {
    // Try to decrypt first
    $decrypted = decryptData($rawBody, $ENC_KEY);
    if (!empty($decrypted)) {
        $jsonBody = json_decode($decrypted, true);
    } else {
        // If decryption failed, try direct JSON
        $jsonBody = json_decode($rawBody, true);
    }
} else {
    // Fall back to POST vars
    $jsonBody = json_decode(json_encode($_POST), true);
}

$action = '';
$username = '';
$device_id = '';

if (is_array($jsonBody)) {
    $action = isset($jsonBody['action']) ? sanitize($jsonBody['action']) : '';
    $username = isset($jsonBody['app_Us']) ? sanitize($jsonBody['app_Us']) : (isset($jsonBody['username']) ? sanitize($jsonBody['username']) : '');
    $device_id = isset($jsonBody['device_id']) ? sanitize($jsonBody['device_id']) : '';
}

if (empty($action)) {
    sendResponse(['success' => false, 'message' => 'Hành động không hợp lệ'], $ENC_KEY);
    $conn->close();
    exit;
}

if ($action === 'login') {
    handleLogin($conn, $username, $device_id, $ENC_KEY);
} else {
    sendResponse(['success' => false, 'message' => 'Hành động không hợp lệ'], $ENC_KEY);
}

$conn->close();

function handleLogin($conn, $username, $device_id, $key) {
    // Validate input
    if (empty($username)) {
        sendResponse(['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập'], $key);
        return;
    }
    
    if (empty($device_id)) {
        sendResponse(['success' => false, 'message' => 'Lỗi: Không thể lấy ID thiết bị'], $key);
        return;
    }
    
    if (strlen($username) < 3 || strlen($username) > 64) {
        sendResponse(['success' => false, 'message' => 'Tên đăng nhập không hợp lệ'], $key);
        return;
    }
    
    // Check if user exists and get account info
    $query = "SELECT id, username, status, expired_date, max_devices, device_limit, devices, duration FROM users WHERE username = ?";
    global $conn; // Access global connection in existing code usage or pass it. Wait, I passed $conn in param previously?
    // The previous code had `global $conn`? No, it passed `$conn`.
    // I am passing `$conn` in `handleLogin`.
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        sendResponse(['success' => false, 'message' => 'Lỗi truy vấn cơ sở dữ liệu'], $key);
        return;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(['success' => false, 'message' => 'Tên đăng nhập không tồn tại'], $key);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    $user_id = $user['id'];
    $status = $user['status'];
    $expired_date = $user['expired_date'];
    $max_devices = $user['max_devices'] ?? null;
    $device_limit = $user['device_limit'] ?? null;
    $devices_json = $user['devices'];
    $duration = $user['duration'] ?? 24;
    if ($max_devices === null || $max_devices === 0) {
        $max_devices = $device_limit ?? 1;
    }
    if ($duration <= 0) {
        $duration = 24;
    }
    
    // Check if account is disabled (status = 0)
    if ($status == 0) {
        sendResponse(['success' => false, 'message' => 'Tài khoản bị khóa. Liên hệ quản trị viên'], $key);
        return;
    }
    
    // Calculate expired_date if null (first login)
    if (empty($expired_date)) {
        $expired_date = date('Y-m-d H:i:s', strtotime("+$duration hours"));
        
        // Update expired_date in database
        $update_exp_query = "UPDATE users SET expired_date = ? WHERE id = ?";
        $update_exp_stmt = $conn->prepare($update_exp_query);
        $update_exp_stmt->bind_param("si", $expired_date, $user_id);
        $update_exp_stmt->execute();
        $update_exp_stmt->close();
    }
    
    // Check if account is expired
    if (!empty($expired_date)) {
        $exp_time = strtotime($expired_date);
        if ($exp_time && $exp_time < time()) {
            sendResponse(['success' => false, 'message' => 'Tài khoản của bạn đã hết hạn'], $key);
            return;
        }
    }
    
    // Parse devices JSON and check device limit
    $devices_array = [];
    if (!empty($devices_json)) {
        $decoded = json_decode($devices_json, true);
        if (is_array($decoded)) {
            $devices_array = $decoded;
        }
    }
    
    // Check if device_id already exists
    $device_exists = false;
    foreach ($devices_array as $device) {
        if (isset($device['device_id']) && $device['device_id'] === $device_id) {
            $device_exists = true;
            break;
        }
    }
    
    // If device doesn't exist and max devices reached
    if (!$device_exists && count($devices_array) >= $max_devices) {
        sendResponse(['success' => false, 'message' => 'Vượt quá giới hạn thiết bị. Vui lòng liên hệ quản trị viên'], $key);
        return;
    }
    
    // Add or update device
    if (!$device_exists) {
        $devices_array[] = [
            'device_id' => $device_id,
            'last_login' => date('Y-m-d H:i:s')
        ];
    } else {
        // Update last_login for existing device
        foreach ($devices_array as &$device) {
            if (isset($device['device_id']) && $device['device_id'] === $device_id) {
                $device['last_login'] = date('Y-m-d H:i:s');
                break;
            }
        }
    }
    
    // Update devices column
    $devices_json_new = json_encode($devices_array);
    $update_query = "UPDATE users SET devices = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $devices_json_new, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    sendResponse(['success' => true, 'message' => 'Đăng nhập thành công!'], $key);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
