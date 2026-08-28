<?php
// api.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(["success" => false, "message" => "Method not allowed"], JSON_UNESCAPED_UNICODE);
  exit;
}

// Đọc raw body JSON
$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);

if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Invalid JSON"], JSON_UNESCAPED_UNICODE);
  exit;
}

// Lấy fields
$action      = (string)($data['action'] ?? '');
$api         = (string)($data['api'] ?? '');
$key         = (string)($data['key'] ?? '');
$hwid        = (string)($data['hwid'] ?? '');
$system_info = (string)($data['system_info'] ?? '');

// Validate cơ bản
if ($action !== 'validate_key') {
  echo json_encode(["success" => false, "message" => "Invalid action"], JSON_UNESCAPED_UNICODE);
  exit;
}

$ALLOWED_APIS = [
  "api_l27hyjm8zc",
  // "api_l27hyjm8zc", // nếu bạn muốn cho cả api này
];

if (!in_array($api, $ALLOWED_APIS, true)) {
  echo json_encode(["success" => false, "message" => "Invalid api"], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($key === '' || $hwid === '') {
  echo json_encode(["success" => false, "message" => "Missing key/hwid"], JSON_UNESCAPED_UNICODE);
  exit;
}

// ====== LOGIC XÁC THỰC KEY ======
// Demo: danh sách key hợp lệ (bạn có thể thay bằng DB)
$VALID_KEYS = [
  "7day-8S1ZDT" => [
    "expires_at" => "2027-12-31 23:59:59",
    // "bind_hwid" => "a1be140f72986fb8146700f8b260bd0ef1080a099db9e9ad37bfa295680505d0",
  ],
];

// Key tồn tại?
if (!isset($VALID_KEYS[$key])) {
  echo json_encode(["success" => false, "message" => "Invalid key"], JSON_UNESCAPED_UNICODE);
  exit;
}

// Check hạn (nếu dùng)
$expiresAt = $VALID_KEYS[$key]["expires_at"] ?? null;
if ($expiresAt && strtotime($expiresAt) !== false && time() > strtotime($expiresAt)) {
  echo json_encode(["success" => false, "message" => "Key expired"], JSON_UNESCAPED_UNICODE);
  exit;
}

// (Tuỳ chọn) Bind HWID nếu muốn khóa theo máy
if (isset($VALID_KEYS[$key]["bind_hwid"])) {
  if (!hash_equals((string)$VALID_KEYS[$key]["bind_hwid"], $hwid)) {
    echo json_encode(["success" => false, "message" => "HWID mismatch"], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// (Tuỳ chọn) log lại request để xem client gửi gì
// file_put_contents(__DIR__ . "/logs.txt",
//   "[".date("Y-m-d H:i:s")."] key=$key hwid=$hwid\n$system_info\n\n",
//   FILE_APPEND
// );

echo json_encode(["success" => true, "message" => "Valid key"], JSON_UNESCAPED_UNICODE);