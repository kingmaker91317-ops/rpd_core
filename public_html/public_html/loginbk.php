<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ===== CONFIG =====
$publicKey = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9EVm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
$validKey  = "test123";

// ===== INPUT =====
$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    sendJsonResponse(['status' => false, 'message' => 'Invalid JSON request'], 400);
}

$game = $input['game'] ?? null;
$key  = $input['key'] ?? null;
$hwid = $input['hwid'] ?? null;
$clientPublicKey = $input['publicKey'] ?? null;

if (!$game || !$key || !$hwid || !$clientPublicKey) {
    sendJsonResponse(['status' => false, 'message' => 'Missing parameters (game, key, hwid, publicKey)'], 400);
}

// ===== CHECK PUBLIC KEY =====
if (!hash_equals($publicKey, (string)$clientPublicKey)) {
    sendJsonResponse(['status' => false, 'message' => 'Invalid public key'], 401);
}

// ===== CHECK LOGIN KEY =====
if (!hash_equals($validKey, (string)$key)) {
    sendJsonResponse(['status' => false, 'message' => 'Invalid key'], 401);
}

// ===== SUCCESS =====
$successMessage = "Success";
$encryptedMessage = xorEncrypt($successMessage, extractKey($publicKey));

sendJsonResponse([
    'status' => true,
    'message' => 'Authentication successful',
    'auth' => [
        'message' => $encryptedMessage,
        'token_access' => $publicKey,
        'user_id' => 1,
        'game' => $game,
        'hwid' => $hwid
    ]
], 200);

// ===== HELPERS =====
function xorEncrypt(string $text, string $key): string {
    if ($key === '') $key = "000000000";
    $out = '';
    $klen = strlen($key);
    $tlen = strlen($text);
    for ($i = 0; $i < $tlen; $i++) {
        $out .= chr(ord($text[$i]) ^ ord($key[$i % $klen]));
    }
    return base64_encode($out);
}

function extractKey(string $publicKey): string {
    if (strlen($publicKey) < 98) return "000000000";
    $positions = [57, 61, 9, 46, 19, 32, 86, 97, 13];
    $key = '';
    foreach ($positions as $pos) $key .= $publicKey[$pos] ?? '';
    return $key !== '' ? $key : "000000000";
}

function sendJsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
