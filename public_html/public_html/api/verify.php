<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Database configuration
$db_host = 'localhost';
$db_user = 'mbktunp_hama';
$db_pass = 'mbktunp_hama';
$db_name = 'mbktunp_hama';

// Connect to database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu']);
    die();
}

$conn->set_charset("utf8mb4");

// Get request data (JSON body or POST)
$rawBody = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true);

$action = '';
$username = '';
$device_id = '';

if (is_array($jsonBody)) {
    $action = isset($jsonBody['action']) ? sanitize($jsonBody['action']) : '';
    $username = isset($jsonBody['app_Us']) ? sanitize($jsonBody['app_Us']) : (isset($jsonBody['username']) ? sanitize($jsonBody['username']) : '');
    $device_id = isset($jsonBody['device_id']) ? sanitize($jsonBody['device_id']) : '';
} else {
    $action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
    $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
    $device_id = isset($_POST['device_id']) ? sanitize($_POST['device_id']) : '';
}

if ($action === 'verify') {
    handleVerify($conn, $username, $device_id);
} else {
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
}

$conn->close();

function handleVerify($conn, $username, $device_id) {
    // Validate input
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập']);
        return;
    }
    
    if (empty($device_id)) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: Không thể lấy ID thiết bị']);
        return;
    }
    
    // Check if user exists
    $query = "SELECT id, username, status, expired_date, devices, game FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn cơ sở dữ liệu']);
        return;
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập không tồn tại']);
        $stmt->close();
        return;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    $status = $user['status'];
    $expired_date = $user['expired_date'];
    $devices_json = $user['devices'];
    $game = isset($user['game']) ? strtoupper($user['game']) : '';
    
    // Check if game is FCMOBILE
    if ($game !== 'FCMOBILE') {
        echo json_encode(['success' => false, 'message' => 'Tài khoản này không được phép sử dụng trên FIFA Mobile']);
        return;
    }
    
    // Check if account is disabled (status = 0)
    if ($status == 0) {
        echo json_encode(['success' => false, 'message' => 'Tài khoản bị khóa. Liên hệ quản trị viên']);
        return;
    }
    
    // Check if account is expired
    if (!empty($expired_date)) {
        $exp_time = strtotime($expired_date);
        if ($exp_time && $exp_time < time()) {
            echo json_encode(['success' => false, 'message' => 'Tài khoản của bạn đã hết hạn']);
            return;
        }
    }
    
    // Parse devices JSON and check if device_id exists
    $devices_array = [];
    if (!empty($devices_json)) {
        $decoded = json_decode($devices_json, true);
        if (is_array($decoded)) {
            $devices_array = $decoded;
        }
    }
    
    // Check if device_id exists in devices array
    $device_found = false;
    foreach ($devices_array as $device) {
        if (isset($device['device_id']) && $device['device_id'] === $device_id) {
            $device_found = true;
            break;
        }
    }
    
    // Return verification result
    if ($device_found) {
        echo json_encode(['success' => true, 'message' => 'Xác minh thiết bị thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Thiết bị không được phép sử dụng tài khoản này']);
    }
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
