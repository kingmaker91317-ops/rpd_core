<?php
// AES-256-GCM login endpoint with SHA-256 key derivation
// POST: data=BASE64(IV[12] + CIPHERTEXT + TAG[16])

// Master key and IV (must match native-lib.cpp)
$MASTER_KEY = 'V9!Kp#rT7wZ2@N1sQx5Lm8Y0uP3eH6cD'; // Key string
$MASTER_IV = 'Qp8$L1mX9!tR7vB2eZr4'; // IV string - MUST match native-lib.cpp
$AUTH_KEY = 'aU7@kP9#xZ2mL6sQvT1cN5wR8yF3gH0'; // HMAC key for server auth

// Database config (update to your environment)
$DB_HOST = "localhost";
$DB_NAME = "mbktunp_hama";
$DB_USER = "mbktunp_hama";
$DB_PASS = "mbktunp_hama";
$DB_CHARSET = "utf8mb4";

function deriveKeyBytes($key, $length = 32) {
    if (empty($key)) {
        throw new Exception("Key is empty");
    }
    $derived = hash('sha256', trim($key), true);
    $result = substr($derived, 0, $length);
    
    // Ensure result is exactly the right length by padding with zeros if needed
    if (strlen($result) < $length) {
        $result .= str_repeat(chr(0), $length - strlen($result));
    }
    
    return $result;
}

function aes_decrypt_gcm($base64, $masterKey, $masterIv) {
    $combined = base64_decode($base64, true);
    if ($combined === false || strlen($combined) < 12 + 16) {
        return null;
    }

    $iv = substr($combined, 0, 12);
    $tag = substr($combined, -16);
    $ciphertext = substr($combined, 12, -16);

    $keyBytes = deriveKeyBytes(trim($masterKey), 32);
    $aad = $masterIv ?? "";

    $plain = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $aad
    );

    if ($plain === false) {
        return null;
    }

    return $plain;
}

function aes_encrypt_gcm($plain, $masterKey, $masterIv) {
    $keyBytes = deriveKeyBytes(trim($masterKey), 32);
    $iv = random_bytes(12);
    $aad = $masterIv ?? "";
    $tag = "";

    $ciphertext = openssl_encrypt(
        $plain,
        'aes-256-gcm',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        $aad
    );

    if ($ciphertext === false) {
        return null;
    }

    return base64_encode($iv . $ciphertext . $tag);
}

function hmac_sig($data, $key) {
    return base64_encode(hash_hmac('sha256', $data, $key, true));
}

function init_nonce_table(PDO $pdo) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS request_nonces (\n" .
        "  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
        "  scope VARCHAR(16) NOT NULL,\n" .
        "  principal VARCHAR(128) NOT NULL,\n" .
        "  nonce VARCHAR(64) NOT NULL,\n" .
        "  ts_ms BIGINT NOT NULL,\n" .
        "  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
        "  PRIMARY KEY (id),\n" .
        "  UNIQUE KEY uniq_nonce (scope, principal, nonce),\n" .
        "  INDEX idx_created_at (created_at)\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function consume_nonce(PDO $pdo, $scope, $principal, $nonce, $tsMs) {
    // Basic validation to avoid DB abuse
    if (!is_string($nonce) || !preg_match('/^[a-f0-9]{32}$/', $nonce)) {
        return false;
    }
    // Cleanup old entries (10 minutes)
    $pdo->exec("DELETE FROM request_nonces WHERE created_at < (NOW() - INTERVAL 10 MINUTE)");

    $stmt = $pdo->prepare(
        "INSERT INTO request_nonces (scope, principal, nonce, ts_ms) VALUES (:scope, :principal, :nonce, :ts_ms)"
    );

    try {
        $stmt->execute([
            ":scope" => $scope,
            ":principal" => $principal,
            ":nonce" => $nonce,
            ":ts_ms" => (int)$tsMs,
        ]);
        return true;
    } catch (PDOException $e) {
        // Duplicate nonce (replay) or other constraint issue
        return false;
    }
}

function respond($ok, $message, $expiry, $masterKey, $masterIv, $authKey, $menuName = "", $menuSubtitle = "", $menuLogo = "") {
    $sigBase = ($ok ? "1" : "0") . "|" . $message . "|" . $expiry;
    $sig = hmac_sig($sigBase, $authKey);

    $payload = json_encode([
        "ok" => $ok,
        "message" => $message,
        "expiry" => $expiry,
        "sig" => $sig,
        "menu_name" => $menuName,
        "menu_subtitle" => $menuSubtitle,
        "menu_logo" => $menuLogo
    ], JSON_UNESCAPED_UNICODE);

    $enc = aes_encrypt_gcm($payload, $masterKey, $masterIv);
    if ($enc === null) {
        http_response_code(500);
        echo "";
        exit;
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo $enc;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_POST['data'])) {
    http_response_code(400);
    exit;
}

$plain = aes_decrypt_gcm($_POST['data'], $MASTER_KEY, $MASTER_IV);
if ($plain === null) {
    respond(false, "Dữ liệu không hợp lệ hoặc đã bị giả mạo", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$req = json_decode($plain, true);
if (!is_array($req)) {
    respond(false, "Sai định dạng", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$username = trim($req['username'] ?? '');
$deviceId = trim($req['device_id'] ?? '');
$timestamp = intval($req['ts'] ?? 0);
$nonce = trim($req['nonce'] ?? '');

// Anti-replay: check timestamp (within 5 minutes)
$currentTime = round(microtime(true) * 1000);
if (abs($currentTime - $timestamp) > 300000) {
    respond(false, "Request timeout hoặc replay attack", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if ($username === '') {
    respond(false, "Thiếu username", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if ($deviceId === '') {
    respond(false, "Thiếu device_id", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if ($nonce === '') {
    respond(false, "Thiếu nonce", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}


// Database lookup
try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    respond(false, "DB error", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Anti-replay: consume per-request nonce (reject replays)
try {
    init_nonce_table($pdo);
    if (!consume_nonce($pdo, "login", $deviceId, $nonce, $timestamp)) {
        respond(false, "Request timeout hoặc replay attack", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
    }
} catch (Exception $e) {
    respond(false, "DB error", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Fetch user by username (and active status)
$stmt = $pdo->prepare("SELECT id, username, UID, status, expired_date, expired, max_devices, game, duration, registrator FROM users WHERE username = :username LIMIT 1");
$stmt->execute([":username" => $username]);
$user = $stmt->fetch();

if (!$user) {
    respond(false, "User not found", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Lookup admin info by registrator
$adminMenuName = "Mon Dora"; // default
$adminMenuSubtitle = "";
$adminMenuLogo = "";

if (!empty($user['registrator'])) {
    $adminStmt = $pdo->prepare("SELECT fullname, menu_name, menu_subtitle, menu_logo FROM admin WHERE username = :registrator LIMIT 1");
    $adminStmt->execute([":registrator" => $user['registrator']]);
    $admin = $adminStmt->fetch();
    
    if ($admin) {
        // Prioritize menu_name, fallback to fullname
        $adminMenuName = !empty($admin['menu_name']) ? $admin['menu_name'] : (!empty($admin['fullname']) ? $admin['fullname'] : "Mon Dora");
        $adminMenuSubtitle = $admin['menu_subtitle'] ?? "";
        $adminMenuLogo = $admin['menu_logo'] ?? "";
    }
}

if (intval($user['status']) !== 1) {
    respond(false, "Tài khoản bị khóa", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if (!isset($user['game']) || strtoupper(trim($user['game'])) !== 'FCMOBILE') {
    respond(false, "Sai game", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Determine expiry date
$expiryDate = $user['expired_date'] ?? $user['expired'] ?? null;
$expiryString = "";

// If first login (expiry is NULL), set expiry = now + duration (hours)
if (empty($expiryDate)) {
    $durationHours = intval($user['duration'] ?? 24);
    if ($durationHours <= 0) {
        $durationHours = 24;
    }
    $dt = new DateTime("now");
    $dt->modify("+{$durationHours} hours");
    $expiryDate = $dt->format('Y-m-d H:i:s');

    $updateExpiry = $pdo->prepare("UPDATE users SET expired_date = :expiry, updated_at = NOW() WHERE id = :id");
    $updateExpiry->execute([
        ":expiry" => $expiryDate,
        ":id" => $user['id']
    ]);
}

if (!empty($expiryDate)) {
    $expiryString = date('Y-m-d H:i:s', strtotime($expiryDate));
}

// Check expiry
$today = date('Y-m-d');
if ($expiryString === "" || $expiryString < $today) {
    respond(false, "Hết hạn", $expiryString, $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Bind device UID list (comma-separated) and enforce max_devices
$storedUid = $user['UID'] ?? "";
$maxDevices = intval($user['max_devices'] ?? 1);
if ($maxDevices <= 0) {
    $maxDevices = 1;
}

$uidList = [];
if (!empty($storedUid)) {
    $parts = array_map('trim', explode(',', $storedUid));
    foreach ($parts as $p) {
        if ($p !== '') {
            $uidList[] = $p;
        }
    }
}

// If device already registered, allow
if (!in_array($deviceId, $uidList, true)) {
    if (count($uidList) >= $maxDevices) {
        respond(false, "Sai thiết bị", $expiryString, $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
    }
    $uidList[] = $deviceId;
    $uidList = array_values(array_unique($uidList));
    $newUid = implode(',', $uidList);
    $update = $pdo->prepare("UPDATE users SET UID = :uid, updated_at = NOW() WHERE id = :id");
    $update->execute([
        ":uid" => $newUid,
        ":id" => $user['id']
    ]);
}

respond(true, "OK", $expiryString, $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
