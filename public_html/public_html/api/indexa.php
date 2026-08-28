<?php
header('Content-Type: application/json; charset=utf-8');

define('AES_KEY_B64', '8o58wCOH9bLQEYSsPqmSqHPk4JPVmWM6Cfkrl7XYBik=');
define('DB_HOST', 'localhost');
define('DB_USER', 'arabemodz_lamdocheat');
define('DB_PASS', 'arabemodz_lamdocheat');
define('DB_NAME', 'arabemodz_lamdocheat');

function aes_key(): string
{
    return base64_decode(AES_KEY_B64);
}

function aes_encrypt(array $data): string
{
    $key = aes_key();
    $iv  = random_bytes(16);

    $plaintext = json_encode($data, JSON_UNESCAPED_UNICODE);

    $ciphertext = openssl_encrypt(
        $plaintext,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($iv . $ciphertext);
}

function aes_decrypt(string $base64)
{
    $key = aes_key();
    $raw = base64_decode($base64);

    if ($raw === false || strlen($raw) < 17) {
        return null;
    }

    $iv         = substr($raw, 0, 16);
    $ciphertext = substr($raw, 16);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'AES-256-CBC',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );

    return json_decode($plaintext, true);
}

function db_connect(): ?mysqli
{
    $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        return null;
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function format_iso(?string $value): string
{
    try {
        if (!$value) {
            return date(DATE_ATOM);
        }
        return (new DateTime($value))->format(DATE_ATOM);
    } catch (Throwable $e) {
        return date(DATE_ATOM);
    }
}

function request_base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function to_absolute_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }
    $value = '/' . ltrim($value, '/');
    return rtrim(request_base_url(), '/') . $value;
}

$mysqli = db_connect();
if (!$mysqli) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$input = file_get_contents('php://input');
$json  = json_decode($input, true);

if (!isset($json['data']) && !isset($json['encrypted_data'])) {
    echo json_encode(['error' => 'Missing encrypted data']);
    exit;
}

/* ================== DECRYPT REQUEST ================== */
$encrypted = isset($json['data']) ? $json['data'] : $json['encrypted_data'];
$request = aes_decrypt($encrypted);

if (!$request) {
    echo json_encode(['error' => 'Decrypt failed']);
    exit;
}

$device_id   = trim((string) ($request['device_id']   ?? ''));
$device_name = trim((string) ($request['device_name'] ?? ''));
$game_slug   = trim((string) ($request['game_slug']   ?? ''));
$key         = trim((string) ($request['key']         ?? ''));

$response = [
    'success' => false,
    'message' => 'Key không hợp lệ'
];

if ($key !== '') {
    $stmt = $mysqli->prepare("SELECT username, game, duration, expired_date, max_devices, devices, status, registrator, created_at, updated_at FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result  = $stmt->get_result();
    $keyData = $result->fetch_assoc();
    $stmt->close();

    if ($keyData) {
        // Only allow keys for game PLAY
        $gameValue = strtoupper(trim((string) ($keyData['game'] ?? '')));
        if ($gameValue !== 'PLAY') {
            $response['message'] = 'Key không thuộc game PLAY';
        } elseif ((int) ($keyData['status'] ?? 0) !== 1) {
            $response['message'] = 'Key đã bị khóa';
        } else {
            $now = new DateTime();
            $durationHours = max(1, (int) ($keyData['duration'] ?? 0));
            $needsExpiryUpdate = empty($keyData['expired_date']);
            $expiredValue = $needsExpiryUpdate
                ? (clone $now)->add(new DateInterval('PT' . $durationHours . 'H'))->format('Y-m-d H:i:s')
                : $keyData['expired_date'];

            try {
                $expiredAt = new DateTime($expiredValue);
            } catch (Throwable $e) {
                $expiredAt = (clone $now)->add(new DateInterval('PT' . $durationHours . 'H'));
                $expiredValue = $expiredAt->format('Y-m-d H:i:s');
                $needsExpiryUpdate = true;
            }

            if ($expiredAt < $now) {
                $response['message'] = 'Key đã hết hạn';
            } else {
                $deviceLimit = max(1, (int) ($keyData['max_devices'] ?? 1));
                $devicesList = array_values(array_filter(array_map('trim', explode(',', (string) ($keyData['devices'] ?? ''))), 'strlen'));
                $deviceAdded = false;
                $deviceLimitExceeded = false;

                if ($device_id !== '') {
                    if (!in_array($device_id, $devicesList, true)) {
                        if (count($devicesList) >= $deviceLimit) {
                            $deviceLimitExceeded = true;
                        } else {
                            $devicesList[] = $device_id;
                            $deviceAdded = true;
                        }
                    }
                }

                if ($deviceLimitExceeded) {
                    $response['message'] = 'Key đã đạt giới hạn thiết bị';
                } else {
                    $devicesString = implode(',', array_unique($devicesList));
                    $deviceCount = count($devicesList);

                    if ($needsExpiryUpdate && $deviceAdded) {
                        $update = $mysqli->prepare("UPDATE users SET expired_date = ?, expired = ?, devices = ?, UID = ?, updated_at = NOW() WHERE username = ?");
                        $update->bind_param('sssss', $expiredValue, $expiredValue, $devicesString, $devicesString, $key);
                        $update->execute();
                        $update->close();
                    } elseif ($needsExpiryUpdate) {
                        $update = $mysqli->prepare("UPDATE users SET expired_date = ?, expired = ?, updated_at = NOW() WHERE username = ?");
                        $update->bind_param('sss', $expiredValue, $expiredValue, $key);
                        $update->execute();
                        $update->close();
                    } elseif ($deviceAdded) {
                        $update = $mysqli->prepare("UPDATE users SET devices = ?, UID = ?, updated_at = NOW() WHERE username = ?");
                        $update->bind_param('sss', $devicesString, $devicesString, $key);
                        $update->execute();
                        $update->close();
                    }

                    // Lấy menu từ admin/seller tạo key
                    $menuLogo = '/icon/logo.png';
                    $menuName = 'PLAY PANEL';
                    $menuSubtitle = 'Contact admin to buy key';
                    $registrator = trim((string) ($keyData['registrator'] ?? ''));
                    if ($registrator !== '') {
                        $menuStmt = $mysqli->prepare("SELECT menu_logo, menu_name, menu_subtitle FROM admin WHERE username = ? LIMIT 1");
                        $menuStmt->bind_param('s', $registrator);
                        $menuStmt->execute();
                        $menuResult = $menuStmt->get_result()->fetch_assoc();
                        $menuStmt->close();
                        if ($menuResult) {
                            $menuLogo = $menuResult['menu_logo'] ?: $menuLogo;
                            $menuName = $menuResult['menu_name'] ?: $menuName;
                            $menuSubtitle = $menuResult['menu_subtitle'] ?: $menuSubtitle;
                        }
                    }
                    $menuLogoUrl = to_absolute_url($menuLogo);

                    $response = [
                        "success" => true,
                        "message" => "Key đã được kích hoạt trên thiết bị này.",
                        "is_existing" => true,
                        "data" => [
                            "key" => $key,
                            "game" => $keyData['game'] ?? $game_slug,
                            "expires_at" => format_iso($expiredValue),
                            "device" => [
                                "device_id" => $device_id,
                                "game_id" => 1,
                                "name" => $device_name,
                                "activated_at" => format_iso($keyData['created_at'] ?? null),
                                "last_login_at" => format_iso($now->format('Y-m-d H:i:s'))
                            ],
                            "devices_count" => $deviceCount,
                            "device_limit" => $deviceLimit,
                            "menu_setting" => [
                                "menu_logo" => $menuLogoUrl,
                                "menu_name" => $menuName,
                                "menu_subtitle" => $menuSubtitle
                            ]
                        ]
                    ];
                }
            }
        }
    }
}

echo json_encode([
    "encrypted_data" => aes_encrypt($response)
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
