<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

include 'init.php';

$crypter = Crypter::init();

function logLine($msg) {
    $file = __DIR__ . '/login_debug.log';
    $line = '[' . date('Y-m-d H:i:s') . '] otosan.php - ' . $msg . PHP_EOL;
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

$privatekey = <<<EOD
-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBANF5vcpEtIT2/xJI
pkLkRPAHCQWOTia3D5ZvnHw85Aa7EHn0eMiTug8AITzMk1J40bzdgjmXpKnJWNWg
Sa8YGMAvBrWXVKiV7BNqx3O/ouR/lXqZ3Tvi+eLIOnFyeGIOgZWvKj9suKy3762P
CpUzyGO/9xZD+K/lVf3kzQs4DX1pAgMBAAECgYBOmM07bZgGI98E7zli899h6GHc
Mc7k+97fZTVj8DzmwZ2nBLGlILi5FCVkeKX2VdrscHiCP4HLKv8n+KJtDi+Kjg1S
i04rrBaeuXAHx8Oh6mfOR3u9HzKPfVE1gGzvY+YUsUs0VVdMlkik2NqYEVK1JijX
tPepd163ip0xb8g3uQJBAP55rGYfsPd5K00Anng+TJDQ0Nx0cmQPPMkWW9vaOPcb
c3iiR8abCq5Pm/Uii7agKTpssax38KP67Xper7UN/sMCQQDSuwuPMAW7szHoMfe9
lOxnrnB8/Mz2lLYHy6BOB5TgH5rr7cnLWS7g3WWkUqzQOgv/WKbeWBlcIVZnV4QK
fKhjAkEAvuxm7lAEpAei9yjpvGlxZI1mxqAPWwcboftGfBKj/rH31qBanaWhQ9qy
th5vGFvd0tnODAoI397Z4Z+80GhppQJBALW25zvc/ESkPFfupqQLRGQPrx6IXDIR
gHpuY9iFfyJY/p2NmiJI0DzFjX1KcYzJUUkqaBJ5Q70HXluUwt7MKeMCQFQEXB2h
0JB/RtuZCeGGUSI8o5QihaaeOq1oeqLdkrAo8sdoDuxN0t3GBP9F8B5z7IbvPO9r
eyLs5Ncjj/kT4Wk=
-----END PRIVATE KEY-----
EOD;

function base64Encode($s) { return base64_encode($s); }
function base64Decode($s) { return base64_decode($s, true); }
function sha256Upper($s) { return strtoupper(hash('sha256', $s)); }

function authProfileEncrypt($plain, $key) {
    $out = '';
    $klen = strlen($key);
    for ($i = 0; $i < strlen($plain); $i++) {
        $out .= chr(ord($plain[$i]) ^ ord($key[$i % $klen]));
    }
    return base64Encode($out);
}

function rsaSign($data) {
    global $privatekey;
    $sig = '';
    openssl_sign($data, $sig, $privatekey, OPENSSL_ALGO_SHA256);
    return base64Encode($sig);
}

function failResponse($msg) {
    die(buildResponse([
        "Status"        => "Failed",
        "MessageString" => $msg,
        "Username"      => "",
        "SubscriptionLeft" => "",
        "Validade"      => "",
        "Vendedor"      => "",
        "RegisterDate"  => "",
        "Dias"          => ""
    ]));
}

function buildResponse($array) {
    $json = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hash = sha256Upper($json);
    $enc  = authProfileEncrypt($json, $hash);
    $sign = rsaSign($json);

    $final = [
        "Data" => $enc,
        "Sign" => $sign,
        "Hash" => $hash
    ];

    return base64Encode(json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

if (!isset($_POST['token']) && !isset($_POST['tokserver_hk'])) {
    logLine('Missing token params');
    failResponse("Invalid request");
}

$tokenParam = isset($_POST['token']) ? $_POST['token'] : $_POST['tokserver_hk'];
$tokenRaw = base64Decode($tokenParam);
$tokArr   = json_decode($tokenRaw, true);

if (!is_array($tokArr)) failResponse("Invalid token");

if (isset($tokArr['Data'], $tokArr['Hash'])) {
    $encData = $tokArr['Data'];
    $hashCli = $tokArr['Hash'];
} elseif (isset($tokArr['Dados_hk'], $tokArr['Hash_hk'])) {
    $encData = $tokArr['Dados_hk'];
    $hashCli = $tokArr['Hash_hk'];
} else {
    failResponse("Invalid token format");
}

$plain = $crypter->decryptByPrivate($privatekey, base64Decode($encData));
if ($plain === false) failResponse("Decrypt failed");

if (sha256Upper($plain) !== strtoupper($hashCli)) failResponse("Hash invalido");

$data  = json_decode($plain, true);

if (!is_array($data)) failResponse("Dados invalidos");

if (!empty($maintenance)) {
    failResponse("Servidor em manutencao");
}

$uname = $data['uname'] ?? null;
if ($uname == null || preg_match("([a-zA-Z0-9]+)", $uname) === 0) {
    failResponse("Usuario invalido.");
}

$cs = $data['cs'] ?? null;
if (!$cs) failResponse("Dados invalidos");

if (!isset($con) || !is_object($con) || !method_exists($con, 'real_escape_string')) {
    if (isset($conn) && is_object($conn) && method_exists($conn, 'real_escape_string')) {
        $con = $conn;
    } else {
        failResponse("DB connection missing");
    }
}

$uEsc = $con->real_escape_string($uname);
$csEsc = $con->real_escape_string($cs);
$where = "WHERE `username` = '$uEsc' AND `game` = 'LKTEAM'";

$q = $con->query("SELECT * FROM `users` $where");
if ($q === false) {
    logLine('DB query failed: ' . $con->error);
    failResponse("DB query failed");
}
$user = $q->fetch_assoc();
if (!is_array($user)) {
    logLine('User not found: ' . $uname);
    failResponse("Usuario nao encontrado.");
}

// Check user status (1 = active, 0 = inactive/banned) - must block before activation
$statusValue = (int)($user['status'] ?? -1);
logLine('User=' . ($user['username'] ?? '') . ' status=' . $statusValue . ' game=' . ($user['game'] ?? '') . ' endpoint=otosan');
if ($statusValue !== 1) {
    failResponse("du me may");
}

// === ACTIVACIÓN AUTOMÁTICA EN PRIMER LOGIN ===
$justActivated = false;
// Activate if expired_date is not set (even if created_at exists)
if (empty($user['expired_date']) && empty($user['expired'])) {
    $dias = (int)$user['duration'];
    if ($dias <= 0) failResponse("Licenca invalida.");

    $start = date("Y-m-d H:i:s");
    $end   = date("Y-m-d H:i:s", strtotime("+{$dias} hours"));

    $updateQuery = "
        UPDATE `users`
        SET created_at = '$start',
            expired_date = '$end',
            updated_at = '$start',
            status = 1
        $where
    ";
    
    $result = $con->query($updateQuery);
    if ($result === false) {
        failResponse("Falha ao ativar conta: " . $con->error);
    }

    $user['created_at'] = $start;
    $user['expired_date'] = $end;
    $user['expired'] = $end;
    $user['status'] = 1;
    $justActivated = true;
}

// === DEVICE MANAGEMENT ===
$maxDevices = (int)($user['max_devices'] ?? 1);
$currentDevices = !empty($user['devices']) ? json_decode($user['devices'], true) : [];
if (!is_array($currentDevices)) $currentDevices = [];

// Check if current device is already registered
if (!in_array($cs, $currentDevices)) {
    if (count($currentDevices) >= $maxDevices) {
        failResponse("Limite de dispositivos atingido.");
    }
    // Add new device
    $currentDevices[] = $cs;
    $devicesJson = json_encode($currentDevices, JSON_UNESCAPED_UNICODE);
    $devicesEsc = $con->real_escape_string($devicesJson);
    $con->query("UPDATE `users` SET `devices` = '$devicesEsc', `updated_at` = NOW() $where");
    $user['devices'] = $devicesJson;
}

// === VALIDACIÓN DE EXPIRACIÓN ===
$endDate = $user['expired_date'] ?? $user['expired'] ?? null;

// Check user status (1 = active, 0 = inactive/banned) - already handled above

// Only check expiration if expired_date is set
if (!empty($endDate)) {
    $endTs = strtotime($endDate);
    $nowTs = time();
    
    if ($endTs <= $nowTs) {
        $con->query("UPDATE `users` SET `status` = 0, `updated_at` = NOW() $where");
        failResponse("Sua licenca expirou.");
    }
} elseif (!$justActivated) {
    // No expiration date set and wasn't just activated - data issue
    failResponse("Data de expiracao nao definida.");
}

// Ensure endDate has value after activation
if (empty($endDate)) {
    $endDate = $user['expired_date'] ?? $user['expired'] ?? date("Y-m-d H:i:s");
}

$endTs = strtotime($endDate);
$nowTs = time();
$daysLeft = (int)round(($endTs - $nowTs) / 86400, 0);
if ($daysLeft < 0) {
    $daysLeft = 0;
}

if ($daysLeft === 0) {
    failResponse("Key Expired");
}

$response = [
    "Status"           => "Success",
    "MessageString"    => "",
    "Username"         => "CELICA",
    "SubscriptionLeft" => "2028-02-10 02:47:33",
    "Validade"         => "2028-02-10 02:47:33",
    "Vendedor"         => "CELICA",
    "RegisterDate"     => "2028-02-08 21:29:03",
    "Dias"             => "0"
];
echo buildResponse($response);
?>