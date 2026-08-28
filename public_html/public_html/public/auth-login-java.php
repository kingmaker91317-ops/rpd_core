<?php
declare(strict_types=1);

/**
 * Auth login (JSON) dùng thẳng DB panel (bảng users) để lấy key và hạn sử dụng.
 * - Key: cột username
 * - Hạn: expired_date nếu có, nếu chưa thì cộng duration (giờ) từ lúc đăng nhập
 * - Thiết bị: cột devices (ưu tiên JSON mảng, cho phép slot "n"), giới hạn max_devices
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

// Always return a predictable JSON structure so client won't crash even on error.
function respond_error(string $mes): never {
    respond([
        'ketqua' => 'error',
        'mes'    => $mes,
    ], 200);
}

function respond(array $payload, int $code = 200): never {
    // Trả 200 cả khi lỗi để client luôn đọc JSON (tránh coi là lỗi mạng)
    http_response_code($code === 200 ? 200 : 200);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"ketqua":"error","mes":"Loi ma hoa JSON"}';
    }
    echo $json;
    exit;
}

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Trả JSON thay vì văng app khi gặp warning/notice
    respond_error('Loi he thong, thu lai (E1)');
});
set_exception_handler(function (Throwable $e) {
    error_log("[login] Uncaught: " . $e->getMessage());
    respond_error('Loi he thong, thu lai (E2)');
});

const DB_HOST = 'localhost';
const DB_USER = 'mbktunp_hama';
const DB_PASS = 'mbktunp_hama';
const DB_NAME = 'mbktunp_hama';
const REQUIRED_PACKAGE = 'com.garena.game.kgvo';

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

function db_connect(): mysqli
{
    $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        error('Không kết nối được database');
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function error($mes)
{
    respond_error($mes);
}

function pick(array $row, string $key, string $default): string
{
    return isset($row[$key]) && $row[$key] !== '' ? (string)$row[$key] : $default;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$auth     = trim((string)($data['auth'] ?? ''));
$device   = trim((string)($data['device'] ?? ''));
$package  = strtolower(trim((string)($data['package'] ?? '')));
$phienban = (string)($data['phienban'] ?? '11');

if ($auth === '' || $device === '') {
    error('Thiếu thông tin đăng nhập');
}

$db = db_connect();

$stmt = $db->prepare('SELECT username, game, status, duration, expired_date, max_devices, devices, registrator FROM users WHERE username = ? LIMIT 1');
if (!$stmt) {
    error('Không chuẩn bị được truy vấn');
}
$stmt->bind_param('s', $auth);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    error('KEY Không Tồn Tại');
}

$game = strtolower(trim((string)($row['game'] ?? '')));
$requiredPackage = REQUIRED_PACKAGE;

if ($game !== $requiredPackage) {
    error('Key không thuộc game này');
}

if ((int)($row['status'] ?? 0) !== 1) {
    error('Key đã bị khóa');
}

// Hạn sử dụng
$now = new DateTime();
$durationHours = max(1, (int)($row['duration'] ?? 0));
$needsExpiryUpdate = empty($row['expired_date']);
$expiredValue = $needsExpiryUpdate
    ? (clone $now)->add(new DateInterval('PT' . $durationHours . 'H'))->format('Y-m-d H:i:s')
    : (string)$row['expired_date'];

try {
    $expiredAt = new DateTime($expiredValue);
} catch (Throwable $e) {
    $expiredAt = (clone $now)->add(new DateInterval('PT' . $durationHours . 'H'));
    $expiredValue = $expiredAt->format('Y-m-d H:i:s');
    $needsExpiryUpdate = true;
}

if ($expiredAt < $now) {
    error('Key đã hết hạn');
}

// Thiết bị
$deviceLimit = max(1, (int)($row['max_devices'] ?? 1));
$devicesRaw  = (string)($row['devices'] ?? '');
$devicesList = json_decode($devicesRaw, true);
if (!is_array($devicesList)) {
    $devicesList = array_values(array_filter(array_map('trim', explode(',', $devicesRaw)), 'strlen'));
}
$deviceAdded = false;
$deviceExists = in_array($device, $devicesList, true);
if (!$deviceExists) {
    $slotIndex = array_search('n', $devicesList, true);
    if ($slotIndex !== false) {
        $devicesList[$slotIndex] = $device;
        $deviceAdded = true;
    } elseif (count($devicesList) < $deviceLimit) {
        $devicesList[] = $device;
        $deviceAdded = true;
    } else {
        error('Key đã đạt giới hạn thiết bị');
    }
}
$devicesEncoded = json_encode(array_values($devicesList), JSON_UNESCAPED_SLASHES);
if ($needsExpiryUpdate || $deviceAdded) {
    $update = $db->prepare('UPDATE users SET expired_date = ?, expired = ?, devices = ?, UID = ?, updated_at = NOW() WHERE username = ?');
    if ($update) {
        $update->bind_param('sssss', $expiredValue, $expiredValue, $devicesEncoded, $devicesEncoded, $auth);
        $update->execute();
        $update->close();
    } else {
        error('Không lưu được thông tin thiết bị');
    }
}

// Lib base64: bắt buộc tồn tại file .so
$libPath = __DIR__ . '/../lib/' . $requiredPackage . '/lib' . $requiredPackage . '_v11.so';
if (!file_exists($libPath)) {
    error("Không tìm thấy file lib: " . $libPath);
}
$libBase64 = base64_encode((string)file_get_contents($libPath));

// Lấy menu/logo/subtitle theo seller (registrator)
$menuLogo = '/icon/logo.png';
$menuName = 'KISS MOD LQ';
$menuSubtitle = 'Contact admin to buy key';
$registrator = trim((string)($row['registrator'] ?? ''));
if ($registrator !== '' && strlen($registrator) <= 100) {
    $stmt = $db->prepare("SELECT menu_logo, menu_name, menu_subtitle FROM admin WHERE username = ? LIMIT 1");
    if (!$stmt) {
        error_log("[login] Admin query prepare failed: " . $db->error);
    } else {
        $stmt->bind_param('s', $registrator);
        $stmt->execute();
        $menuResult = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($menuResult) {
            $menuLogo = !empty($menuResult['menu_logo']) ? (string)$menuResult['menu_logo'] : $menuLogo;
            $menuName = !empty($menuResult['menu_name']) ? (string)$menuResult['menu_name'] : $menuName;
            $menuSubtitle = !empty($menuResult['menu_subtitle']) ? (string)$menuResult['menu_subtitle'] : $menuSubtitle;
        }
    }
}
$menuLogoUrl = to_absolute_url($menuLogo);

$musicList = [
    'https://hackergammer.online/public/files/music/vip.mp3',
    'https://hackergammer.online/public/files/music/NHẠC TRUNG QUỐC REMIX 2025 - NHẠC HOA REMIX HOT TIKTOK - FULL SET NHẠC TRUNG REMIX HAY 2025 [FYLgEiWtpyk].mp3',
    'https://hackergammer.online/public/files/music/Nonstop Mixtaple Part 4.mp3',
];

$response = [
    'ketqua'            => 'thanhcong',
    'mes'               => 'Đăng Nhập Thành Công',
    'auth'              => $auth,
    'device'            => $device,
    'package'           => $package,
    'hansudung'         => $expiredAt->format('Y-m-d H:i'),
    'beta'              => 'khong',
    'vip'               => $devicesEncoded,
    'code'              => 'DVLxKAII',
    'lib'               => $libBase64,
    'mp3'               => json_encode($musicList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    'mp3_2'             => 'W3sibmFtZSI6IlRtOXVjM1J2Y0NCTmFYaDBZWEJzWlNCUVlYSjBJRFE9IiwidXJsIjoiYUhSMGNITTZMeTlvWVdOclpYSm5ZVzF0WlhJdWIyNXNhVzVsTDNCMVlteHBZeTltYVd4bGN5OXRkWE5wWXk5T2IyNXpkRzl3SUUxcGVIUmhjR3hsSUZCaGNuUWdOQzV0Y0RNPSJ9XQ==',
    'bidanh'            => $menuName,
    'gameName'          => $menuSubtitle,
    'phienban'          => $phienban === '' ? '11' : $phienban,
    'icon'              => $menuLogoUrl,
    'icon_size'         => '45',
    'boder'             => '1',
    'text_size'         => '15',
    'menu_width'        => '280',
    'menu_height'       => '210',
    'background1_color' => 'A6000000',
    'background2_color' => 'A6000000',
    'color_title'       => 'F5F5F5',
    'toggle_text_color' => 'F5F5F5',
];

// Log successful login
error_log("[login] User: $auth | Device: $device | Time: " . date('Y-m-d H:i:s'));

$db->close();
respond($response, 200);
?>
