<?php
// Lightweight endpoint to fetch menu customization based on username
// POST: data=BASE64(IV[12] + CIPHERTEXT + TAG[16]) with {"username": "xxx"}

// Master key and IV (must match native-lib.cpp and login_v2.php)
$MASTER_KEY = 'P6@xR1tM9vQ4#nZ2L8sT0yK3wH5!cD7u';
$MASTER_IV = 'F2#nV8qL3tW!6mJ9sX0b';
$AUTH_KEY = 'gR4#pQ8!m2ZxL7sVnK5tC1wB0hF6yJ3';

// Database config
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
    if (!is_string($nonce) || !preg_match('/^[a-f0-9]{32}$/', $nonce)) {
        return false;
    }
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
        return false;
    }
}

function respond($menuName, $menuSubtitle, $menuLogo, $masterKey, $masterIv, $authKey) {
    $sigBase = $menuName . "|" . $menuSubtitle . "|" . $menuLogo;
    $sig = hmac_sig($sigBase, $authKey);

    $payload = json_encode([
        "menu_name" => $menuName,
        "menu_subtitle" => $menuSubtitle,
        "menu_logo" => $menuLogo,
        "sig" => $sig
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
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$req = json_decode($plain, true);
if (!is_array($req)) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$username = trim($req['username'] ?? '');
$timestamp = intval($req['ts'] ?? 0);
$nonce = trim($req['nonce'] ?? '');

if ($username === '') {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Anti-replay: check timestamp (within 5 minutes)
$currentTime = round(microtime(true) * 1000);
if (abs($currentTime - $timestamp) > 300000) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if ($nonce === '') {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Database lookup
try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Anti-replay: consume per-request nonce (reject replays)
try {
    init_nonce_table($pdo);
    if (!consume_nonce($pdo, "menu", $username, $nonce, $timestamp)) {
        respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
    }
} catch (Exception $e) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

// Fetch user and get registrator
$stmt = $pdo->prepare("SELECT registrator FROM users WHERE username = :username LIMIT 1");
$stmt->execute([":username" => $username]);
$user = $stmt->fetch();

if (!$user) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

if (empty($user['registrator'])) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$registrator = $user['registrator'];

// Lookup admin info
$adminStmt = $pdo->prepare("SELECT fullname, menu_name, menu_subtitle, menu_logo FROM admin WHERE username = :registrator LIMIT 1");
$adminStmt->execute([":registrator" => $registrator]);
$admin = $adminStmt->fetch();

if (!$admin) {
    respond("Mr Light", "", "", $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
}

$menuName = "Mr Light";
$menuSubtitle = "";
$menuLogo = "";

if ($admin) {
    $menuName = !empty($admin['menu_name']) ? $admin['menu_name'] : (!empty($admin['fullname']) ? $admin['fullname'] : "Mr Light");
    $menuSubtitle = $admin['menu_subtitle'] ?? "";
    $menuLogo = $admin['menu_logo'] ?? "";
}

respond($menuName, $menuSubtitle, $menuLogo, $MASTER_KEY, $MASTER_IV, $AUTH_KEY);
