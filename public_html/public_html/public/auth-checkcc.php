<?php
declare(strict_types=1);

require_once __DIR__ . '/Encrypt.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
header('Content-Type: application/json; charset=utf-8');

const DB_HOST = 'localhost';
const DB_USER = 'mbktunp_hama';
const DB_PASS = 'mbktunp_hama';
const DB_NAME = 'mbktunp_hama';
const BASE_URL = 'https://hackergammer.online/';
const REQUIRED_PACKAGE = 'com.garena.game.kgvo';

function db_connect(): mysqli
{
    $db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        error('Không kết nối được database');
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function logp($mes): void
{
    error_log('[auth-check] ' . $mes);
}

function urlCut(): string
{
    $url = $_SERVER['REQUEST_URI'] ?? '';
    $pos = strpos($url, '?');
    return $pos === false ? '' : substr($url, $pos + 1);
}

function error($mes): void
{
    logp($mes);
    exit(Encrypt::HMGENC(json_encode(["ketqua" => "error", "mes" => $mes], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
}

$db = db_connect();

$decoded = Encrypt::HMGDEC(urlCut());
if ($decoded === '') {
    error("Hãy Thử Lại");
}

$data = json_decode($decoded);
if (!$data || !isset($data->package) || !isset($data->key)) {
    error("ERROR 1");
}

$package = strtolower(trim((string)$data->package));
$key     = trim((string)$data->key);
$device  = trim((string)($data->deviceid ?? ''));

if ($device === '' || $key === '' || $package === '') {
    error("Thiếu dữ liệu");
}

if ($package !== REQUIRED_PACKAGE) {
    error("Sai package");
}

$stmt = $db->prepare('SELECT username, game, status, duration, expired_date, max_devices, devices FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $key);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    error("KEY Không Tồn Tại *");
}

$game = strtolower(trim((string)($row['game'] ?? '')));
if ($game !== '' && $game !== REQUIRED_PACKAGE) {
    error("Key không thuộc game này");
}

if ((int)($row['status'] ?? 0) !== 1) {
    error("Key đã bị khóa hoặc hết hạn");
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
    error("🕐 KEY Đã Hết Hạn Sử Dụng!");
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
        error("📲 Vượt Giới Hạn Thiết Bị");
    }
}

$devicesEncoded = json_encode(array_values($devicesList), JSON_UNESCAPED_SLASHES);
if ($needsExpiryUpdate || $deviceAdded) {
    $update = $db->prepare('UPDATE users SET expired_date = ?, expired = ?, devices = ?, UID = ?, updated_at = NOW() WHERE username = ?');
    $update->bind_param('sssss', $expiredValue, $expiredValue, $devicesEncoded, $devicesEncoded, $key);
    $update->execute();
    $update->close();
}

$urlLib = BASE_URL . 'lib/com.garena.game.kgvo/libcom.garena.game.kgvo_v11.so';

exit(Encrypt::HMGENC(json_encode([
    "ketqua"    => "thanhcong",
    "mes"       => "Tải Thành Công",
    "hansudung" => $expiredAt->format('Y-m-d H:i'),
    "beta"      => "khong",
    "urlLib"    => $urlLib,
    "vip"       => $devicesEncoded,
    "code"      => "DVLxKAII"
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));
