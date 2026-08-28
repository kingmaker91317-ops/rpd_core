<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối DB có retry ngắn để hạn chế lỗi max_user_connections
function open_db_with_retry($host, $user, $pass, $db, $retries = 2, $sleepMs = 300) {
    for ($i = 0; $i <= $retries; $i++) {
        $conn = @new mysqli($host, $user, $pass, $db);
        if (!$conn->connect_errno) {
            $conn->set_charset('utf8mb4');
            register_shutdown_function(function() use ($conn) {
                if ($conn && $conn->ping()) {
                    $conn->close();
                }
            });
            return $conn;
        }

        if ($conn->connect_errno == 1203 && $i < $retries) {
            usleep($sleepMs * 1000);
            continue;
        }

        throw new RuntimeException("Lỗi kết nối cơ sở dữ liệu: " . $conn->connect_error);
    }
}

$key = hex2bin('93f0a7cd6721b98d13d0a14c92c51a724ce97f89214bd33ed91e0f9922a53c64');
$iv  = hex2bin('7a91f3e4dac91e7f0f0b5e3c14398710');

function aes256_cbc_encrypt_b64($plaintext, $key, $iv) {
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        throw new RuntimeException('openssl_encrypt failed');
    }
    return base64_encode($ciphertext);
}

function aes256_cbc_decrypt_b64($b64, $key, $iv) {
    $ct = base64_decode($b64, true);
    if ($ct === false) return null;
    $pt = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return $pt;
}

function send_encrypted_response(array $payload, $key, $iv): void {
    $data = aes256_cbc_encrypt_b64(json_encode($payload, JSON_UNESCAPED_UNICODE), $key, $iv);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_error(string $message, $key, $iv): void {
    $err = [
        "status"  => "error",
        "message" => $message
    ];
    send_encrypted_response($err, $key, $iv);
}

$raw = file_get_contents('php://input');
$req = $raw ? json_decode($raw, true) : null;
if (!is_array($req)) {
    $req = [];
}

// Nếu client gửi dạng {"data":"..."} thì giải mã trước
if (isset($req['data'])) {
    $plain = aes256_cbc_decrypt_b64($req['data'], $key, $iv);
    $decoded = $plain ? json_decode($plain, true) : null;
    if (!is_array($decoded)) {
        send_error("Dữ liệu không hợp lệ", $key, $iv);
    }
    $req = $decoded;
}

$gkey = trim((string) ($req['gkey'] ?? ''));
$deviceID = trim((string) ($req['deviceID'] ?? ''));

if ($gkey === '') {
    send_error("Mã kích hoạt không hợp lệ", $key, $iv);
}
if ($deviceID === '') {
    send_error("Thiếu thiết bị", $key, $iv);
}

$db = null;
try {
    $db = open_db_with_retry("localhost", "mbktunp_hama", "mbktunp_hama", "mbktunp_hama");
} catch (Throwable $e) {
    send_error("Lỗi kết nối cơ sở dữ liệu", $key, $iv);
}

$gamePackage = 'com.haegin.playtogether';

$stmt = $db->prepare("SELECT id, username, game, status, duration, expired_date, expired, max_devices, device_limit, devices, UID, registered, created_at FROM users WHERE username = ? AND game = ? LIMIT 1");
if (!$stmt) {
    send_error("Lỗi hệ thống", $key, $iv);
}
$stmt->bind_param("ss", $gkey, $gamePackage);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    send_error("Mã kích hoạt không hợp lệ", $key, $iv);
}
$user = $result->fetch_assoc();
$stmt->close();

if ((int) $user['status'] !== 1) {
    send_error("Tài khoản đã bị khóa", $key, $iv);
}

$duration = (int) $user['duration'];
if ($duration <= 0) {
    send_error("Thời hạn không hợp lệ", $key, $iv);
}

$now = new DateTime('now');
$registeredRaw = trim((string) ($user['registered'] ?? ''));
$expiredRaw = trim((string) ($user['expired_date'] ?? ''));

if ($expiredRaw === '' || $expiredRaw === '0000-00-00 00:00:00') {
    $startDate = clone $now;
    $endDate = (clone $now)->modify('+' . $duration . ' hours');
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $endDate->format('Y-m-d H:i:s');

    if ($registeredRaw === '' || $registeredRaw === '0000-00-00 00:00:00') {
        $update = $db->prepare("UPDATE users SET expired_date = ?, expired = ?, registered = ? WHERE id = ?");
        if ($update) {
            $update->bind_param('sssi', $endDateStr, $endDateStr, $startDateStr, $user['id']);
            $update->execute();
            $update->close();
        }
        $registeredRaw = $startDateStr;
    } else {
        $update = $db->prepare("UPDATE users SET expired_date = ?, expired = ? WHERE id = ?");
        if ($update) {
            $update->bind_param('ssi', $endDateStr, $endDateStr, $user['id']);
            $update->execute();
            $update->close();
        }
    }
    $expiredRaw = $endDateStr;
} else {
    try {
        $endDate = new DateTime($expiredRaw);
    } catch (Exception $e) {
        send_error("Hạn sử dụng không hợp lệ", $key, $iv);
    }

    if ($registeredRaw === '' || $registeredRaw === '0000-00-00 00:00:00') {
        $startDate = clone $endDate;
        $startDate->modify('-' . $duration . ' hours');
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        $update = $db->prepare("UPDATE users SET registered = ? WHERE id = ?");
        if ($update) {
            $update->bind_param('si', $startDateStr, $user['id']);
            $update->execute();
            $update->close();
        }
        $registeredRaw = $startDateStr;
    } else {
        try {
            $startDate = new DateTime($registeredRaw);
        } catch (Exception $e) {
            $startDate = (clone $endDate)->modify('-' . $duration . ' hours');
            $registeredRaw = $startDate->format('Y-m-d H:i:s');
        }
    }

    if ($now > $endDate) {
        send_error("Hết hạn sử dụng", $key, $iv);
    }
}

$devicesRaw = trim((string) ($user['devices'] ?? ''));
if ($devicesRaw === '') {
    $devicesRaw = trim((string) ($user['UID'] ?? ''));
}
$devices = array_values(array_unique(array_filter(array_map('trim', explode(',', $devicesRaw)), 'strlen')));
$maxDevices = (int) ($user['max_devices'] ?? 0);
if ($maxDevices <= 0) {
    $maxDevices = (int) ($user['device_limit'] ?? 0);
}
if ($maxDevices <= 0) {
    $maxDevices = 1;
}

if (!in_array($deviceID, $devices, true)) {
    if (count($devices) >= $maxDevices) {
        send_error("Vượt quá số thiết bị cho phép", $key, $iv);
    }
    $devices[] = $deviceID;
    $devices = array_values(array_unique($devices));
    $devicesStr = implode(',', $devices);
    $updateDevices = $db->prepare("UPDATE users SET devices = ?, UID = ? WHERE id = ?");
    if ($updateDevices) {
        $updateDevices->bind_param('ssi', $devicesStr, $devicesStr, $user['id']);
        $updateDevices->execute();
        $updateDevices->close();
    }
}

if (!isset($startDate)) {
    $startDate = new DateTime($registeredRaw ?: 'now');
}
if (!isset($endDate)) {
    $endDate = new DateTime($expiredRaw ?: 'now');
}

$daySeconds = max(0, $endDate->getTimestamp() - $startDate->getTimestamp());
$day = (int) ceil($daySeconds / 86400);
if ($day < 0) {
    $day = 0;
}

// OK response
$response = [
    "status"     => "ok",
    "message"    => "Đăng nhập thành công",
    "gkey"       => $gkey,
    "deviceID"   => $deviceID,
    "day"        => (string) $day,
    "startDate"  => $startDate->format('Y-m-d H:i:s'),
    "endDate"    => $endDate->format('Y-m-d H:i:s'),
    "statusCode" => 0
];

send_encrypted_response($response, $key, $iv);
