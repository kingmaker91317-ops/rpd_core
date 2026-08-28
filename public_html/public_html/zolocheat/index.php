<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$aesKey = 'p9x4v7k2m1s8q0lz';
$aesIv  = 'u3t6w9y4b7n2d5hf';

$db_host = 'localhost';
$db_user = 'kissmodk_lamdozz';
$db_pass = 'kissmodk_lamdozz';
$db_name = 'kissmodk_lamdozz';
$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) {
    $response = [
        "m_status" => false,
        "message" => "Database connection failed"
    ];
    echo json_encode(["status" => encrypt_payload($response)]);
    exit;
}
$db->set_charset("utf8mb4");

function encrypt_payload($data) {
    global $aesKey, $aesIv;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $encrypted = openssl_encrypt($json, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIv);
    if ($encrypted === false) {
        return '';
    }
    return base64_encode($encrypted);
}

function decrypt_payload($encryptedData) {
    global $aesKey, $aesIv;
    try {
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) return [];
        $decrypted = openssl_decrypt($decoded, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIv);
        if ($decrypted === false) return [];
        $arr = json_decode($decrypted, true);
        return is_array($arr) ? $arr : [];
    } catch (Exception $e) {
        return [];
    }
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?: [];

$real_data = [];
if (!empty($data['status'])) {
    $real_data = decrypt_payload($data['status']);
}

$user_uuid = trim($real_data['user_uuid'] ?? $data['user_uuid'] ?? '');
$is_ran    = trim($real_data['is_ran'] ?? $data['is_ran'] ?? '');
$username  = trim($real_data['user_key'] ?? $data['user_key'] ?? '');

$call_app = null;
if (!empty($real_data['call_app'])) {
    $call_app = trim($real_data['call_app']);
} elseif (!empty($data['call_app'])) {
    $call_app = trim($data['call_app']);
}
if ($call_app === '') {
    $call_app = null;
}

if ($username === '') {
    $response = [
        "m_status" => false,
        "message" => "Please enter a valid licence"
    ];
    echo json_encode(["status" => encrypt_payload($response)]);
    exit;
}

$stmt = $db->prepare("SELECT username, expired, duration, UID, device_limit FROM users WHERE username = ? AND game = 'ZoloCheat'");
if (!$stmt) {
    $response = [
        "m_status" => false,
        "message" => "Database error"
    ];
    echo json_encode(["status" => encrypt_payload($response)]);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response = [
        "m_status" => false,
        "message" => "Please enter a valid licence"
    ];
    echo json_encode(["status" => encrypt_payload($response)]);
    exit;
}

$user = $result->fetch_assoc();
$expired = $user['expired'];
$duration = intval($user['duration']);
$uid_list = $user['UID'] ?? '';
$device_limit = intval($user['device_limit']);

if ($expired && strtotime($expired) < time()) {
    $response = [
        "m_status" => false,
        "message" => "License has expired"
    ];
    echo json_encode(["status" => encrypt_payload($response)]);
    exit;
}

$uid_array = $uid_list ? explode(',', $uid_list) : [];
$uid_array = array_map('trim', $uid_array);
$uid_array = array_filter($uid_array);

$device_count = count($uid_array);
$is_device_registered = in_array($user_uuid, $uid_array);

if (!$is_device_registered) {
    if ($device_count >= $device_limit) {
        $response = [
            "m_status" => false,
            "message" => "Device limit reached"
        ];
        echo json_encode(["status" => encrypt_payload($response)]);
        exit;
    }
    $uid_array[] = $user_uuid;
    $new_uid_list = implode(',', $uid_array);
    $update_stmt = $db->prepare("UPDATE users SET UID = ? WHERE username = ? AND game = 'ZoloCheat'");
    $update_stmt->bind_param("ss", $new_uid_list, $username);
    $update_stmt->execute();
    $update_stmt->close();
}

if (!$expired || $expired === '0000-00-00 00:00:00' || $expired === '') {
    $new_expired = date('Y-m-d H:i:s', time() + ($duration * 3600));
    $update_expired_stmt = $db->prepare("UPDATE users SET expired = ? WHERE username = ? AND game = 'ZoloCheat'");
    $update_expired_stmt->bind_param("ss", $new_expired, $username);
    $update_expired_stmt->execute();
    $update_expired_stmt->close();
} else {
    $new_expired = $expired;
}

$stmt->close();

$token = md5("PUBG-{$is_ran}-{$user_uuid}-z645wdfhdfdhdg234f");

$success_response = [
    "m_status"     => true,
    "token"        => $token,
    "mx_message"   => "PLAY SAFE AVOID REPORTS",
    "userstatus"   => "NSEIKOUSESEIKOSST",
    "user_key"     => $username,
    "expire_date"  => $new_expired,
    "rng"          => time(),
    "is_ran"       => $is_ran,
    "call_app"     => $call_app,
    "lib_key"      => "f8r2q6z1m7x0v4yb",
    "lib_iv"       => "t9n3c5h2k8w1s6dp",
    "e_g_cmat"     => 624,
    "e_cn_cmat"    => 624,
    "e_d_cmat"     => 640,
    "isnull"       => "0",
    "loader_link"  => "https://modpanel.online?download=zolo",
    "lastupdated"  => "10067"
];

$encrypted_response = encrypt_payload($success_response);

$db->close();

echo json_encode(["status" => $encrypted_response]);
exit;
?>
