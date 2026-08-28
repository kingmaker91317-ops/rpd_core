<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

// Show errors directly in the HTTP response for easier debugging.
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
error_reporting(E_ALL);

$privateKeyPath = __DIR__ . "/keys/private.pem";

$privatePem = file_exists($privateKeyPath) ? file_get_contents($privateKeyPath) : "";

// Database connection
$dbHost = "localhost";
$dbUser = "mbktunp_hama";
$dbPass = "mbktunp_hama";
$dbName = "mbktunp_hama";

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    throw new RuntimeException("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

function output_error(string $message, array $context = []): void
{
    http_response_code(500);
    echo json_encode(
        [
            "error" => $message,
            "context" => $context,
        ],
        JSON_UNESCAPED_SLASHES
    );
}

set_exception_handler(function (Throwable $e): void {
    output_error($e->getMessage(), [
        "type" => get_class($e),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
    ]);
    exit;
});

set_error_handler(function (int $severity, string $message, string $file, int $line): void {
    // Convert PHP errors to exceptions so they bubble up.
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && ($err["type"] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        output_error($err["message"], [
            "file" => $err["file"] ?? "",
            "line" => $err["line"] ?? 0,
        ]);
    }
});

function b64_decode_strict(string $s): string
{
    $out = base64_decode($s, true);
    if ($out === false) {
        throw new RuntimeException("invalid base64");
    }
    return $out;
}

function xor_str(string $data, string $key): string
{
    $out = "";
    $klen = strlen($key);
    if ($klen === 0) {
        return $data;
    }
    $dlen = strlen($data);
    for ($i = 0; $i < $dlen; $i++) {
        $out .= chr(ord($data[$i]) ^ ord($key[$i % $klen]));
    }
    return $out;
}

function sha256_hex(string $data): string
{
    return strtoupper(hash("sha256", $data));
}

function rsa_private_decrypt_b64(string $b64, string $privatePem): string
{
    if ($privatePem === "") {
        throw new RuntimeException("private key is empty");
    }
    $key = openssl_pkey_get_private($privatePem);
    if ($key === false) {
        throw new RuntimeException("private key invalid: " . openssl_error_string());
    }
    $cipher = b64_decode_strict($b64);
    $ok = openssl_private_decrypt($cipher, $plain, $key, OPENSSL_PKCS1_PADDING);
    if (!$ok) {
        $err = openssl_error_string();
        throw new RuntimeException("RSA decrypt failed: " . ($err ?: "unknown OpenSSL error"));
    }
    return $plain;
}

function rsa_public_encrypt_b64(string $plain, string $publicPem): string
{
    $ok = openssl_public_encrypt($plain, $cipher, $publicPem, OPENSSL_PKCS1_PADDING);
    if (!$ok) {
        throw new RuntimeException("RSA encrypt failed");
    }
    return base64_encode($cipher);
}

function parse_request_payload(string $plain, string $hash, string $dadosB64): ?array
{
    $req = json_decode($plain, true);
    if (is_array($req)) {
        return $req;
    }

    $b64 = base64_decode($plain, true);
    if ($b64 !== false) {
        $req = json_decode($b64, true);
        if (is_array($req)) {
            return $req;
        }
    }

    if ($hash !== "") {
        $xor = xor_str($plain, $hash);
        $req = json_decode($xor, true);
        if (is_array($req)) {
            return $req;
        }

        if ($b64 !== false) {
            $xor = xor_str($b64, $hash);
            $req = json_decode($xor, true);
            if (is_array($req)) {
                return $req;
            }
        }
    }

    return null;
}

function load_loader_bytes(string $b64Path, array $binPaths): string
{
    if (file_exists($b64Path)) {
        $data = file_get_contents($b64Path);
        if ($data !== false) {
            $raw = base64_decode(trim($data), true);
            if ($raw !== false) {
                return $raw;
            }
            return (string)$data;
        }
    }
    foreach ($binPaths as $path) {
        if (!file_exists($path)) {
            continue;
        }
        $data = file_get_contents($path);
        if ($data !== false) {
            return $data;
        }
    }
    return "";
}

function aes_ecb_encrypt_b64(string $data, string $key): string
{
    if (strlen($key) !== 16) {
        throw new RuntimeException("AES key must be 16 bytes");
    }
    $cipher = openssl_encrypt($data, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    if ($cipher === false) {
        throw new RuntimeException("AES encrypt failed");
    }
    return base64_encode($cipher);
}

function sign_payload(string $payloadJson, string $privatePem): string
{
    if ($privatePem === "") {
        return "";
    }
    $key = openssl_pkey_get_private($privatePem);
    if ($key === false) {
        throw new RuntimeException("private key invalid: " . openssl_error_string());
    }
    $ok = openssl_sign($payloadJson, $sig, $key, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        return "";
    }
    return base64_encode($sig);
}

function build_profile_response(array $payload, string $privatePem): string
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException("json encode failed");
    }
    $hashKey = sha256_hex($json);
    $xor = xor_str($json, $hashKey);
    $dadosB64 = base64_encode($xor);
    // Client verifies SHA256withRSA over the plaintext JSON (len=80 in logs).
    $signB64 = sign_payload($json, $privatePem);
    $out = [
        "Dados_hk" => $dadosB64,
        "Sign_hk" => $signB64,
        "Hash_hk" => $hashKey,
    ];
    $resp = json_encode($out, JSON_UNESCAPED_SLASHES);
    if ($resp === false) {
        throw new RuntimeException("json encode failed");
    }
    // Client expects the entire response to be base64-encoded.
    return base64_encode($resp);
}

function verify_user_exists(mysqli $mysqli, string $username): bool
{
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function verify_uid_for_user(mysqli $mysqli, string $username, string $uid): bool
{
    if ($uid === "") {
        return true; // Allow empty UID
    }
    
    // Get current UID and device_limit from database
    $stmt = $mysqli->prepare("SELECT UID, max_devices FROM users WHERE username = ?");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        return false;
    }
    
    $currentUid = (string)($row['UID'] ?? '');
    $deviceLimit = (int)($row['max_devices'] ?? 1);
    
    // If UID is empty or NULL in DB, allow login (first time)
    if ($currentUid === '' || $currentUid === null) {
        return true;
    }
    
    // Check if current UID matches exactly or is in the comma-separated list
    $uidList = explode(',', $currentUid);
    $uidList = array_map('trim', $uidList);
    $uidList = array_filter($uidList); // Remove empty entries
    
    // If UID already exists, allow login
    if (in_array($uid, $uidList, true)) {
        return true;
    }
    
    // If number of UIDs is less than device_limit, allow new UID
    if (count($uidList) < $deviceLimit) {
        return true;
    }
    
    // Max devices reached
    return false;
}

function update_user_uid(mysqli $mysqli, string $username, string $uid): void
{
    if ($uid === "") {
        return;
    }
    
    // Get current UID
    $stmt = $mysqli->prepare("SELECT UID FROM users WHERE username = ?");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        return;
    }
    
    $currentUid = (string)($row['UID'] ?? '');
    
    // If UID is empty or NULL, set it (first login)
    if ($currentUid === '' || $currentUid === null) {
        $stmt = $mysqli->prepare("UPDATE users SET UID = ? WHERE username = ?");
        if (!$stmt) {
            throw new RuntimeException("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("ss", $uid, $username);
        $stmt->execute();
        $stmt->close();
        return;
    }
    
    // Check if UID already exists
    $uidList = explode(',', $currentUid);
    $uidList = array_map('trim', $uidList);
    
    if (in_array($uid, $uidList, true)) {
        return; // UID already exists, no need to update
    }
    
    // Add new UID to the list
    $newUid = $currentUid . ',' . $uid;
    $stmt = $mysqli->prepare("UPDATE users SET UID = ? WHERE username = ?");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $mysqli->error);
    }
    $stmt->bind_param("ss", $newUid, $username);
    $stmt->execute();
    $stmt->close();
}

try {
    $tok = $_POST["tokserver_hk"] ?? "";
    if ($tok === "") {
        http_response_code(400);
        echo json_encode(["error" => "missing tokserver_hk"]);
        exit;
    }

    $tokJson = b64_decode_strict($tok);
    $tokData = json_decode($tokJson, true);
    if (!is_array($tokData)) {
        http_response_code(400);
        echo json_encode(["error" => "invalid tokserver_hk json"]);
        exit;
    }

    $tokCli = (string)($tokData["Tok_hk"] ?? "");
    $dadosB64 = (string)($tokData["Dados_hk"] ?? "");
    $hash = (string)($tokData["Hash_hk"] ?? "");
    if ($dadosB64 === "" || $privatePem === "") {
        http_response_code(400);
        echo json_encode(["error" => "missing Dados_hk or private key"]);
        exit;
    }

    $plain = rsa_private_decrypt_b64($dadosB64, $privatePem);
    $req = parse_request_payload($plain, $hash, $dadosB64);
    if (!is_array($req)) {
        http_response_code(400);
        echo json_encode(["error" => "invalid request payload"]);
        exit;
    }

    $uid = (string)($req["Uid_hk"] ?? "");
    $user = (string)($req["User_hk"] ?? "");
    if ($user === "") {
        $user = $uid;
    }
    if ($tokCli === "") {
        $tokCli = (string)($req["Tok_hk"] ?? "");
    }
    
    // Verify user exists in database
    if ($user === "") {
        $payload = [
            "ConnectSt_hk" => "Failed",
            "MessageFromSv" => "Missing User_hk.",
            "ExpireDays" => "0",
        ];
        echo build_profile_response($payload, $privatePem);
        exit;
    }

    // Check if username exists
    if (!verify_user_exists($mysqli, $user)) {
        $payload = [
            "ConnectSt_hk" => "Failed",
            "MessageFromSv" => "User not found.",
            "ExpireDays" => "0",
        ];
        echo build_profile_response($payload, $privatePem);
        exit;
    }

    // Check if UID is valid for this user
    if (!verify_uid_for_user($mysqli, $user, $uid)) {
        $payload = [
            "ConnectSt_hk" => "Failed",
            "MessageFromSv" => "Max devices reached.",
            "ExpireDays" => "0",
        ];
        echo build_profile_response($payload, $privatePem);
        exit;
    }

    // Update UID in database
    update_user_uid($mysqli, $user, $uid);

    $aesKey = "22P9ULFDKPJ70G46";
    $loaderRaw = load_loader_bytes(
        __DIR__ . "/CacheLoader",
        []
    );
    $loaderB64 = $loaderRaw === "" ? "" : aes_ecb_encrypt_b64($loaderRaw, $aesKey);
    $currToken = $uid;
    $apkVersion = (string)($req["ApkVersion_hk"] ?? "1.3");

    $payload = [
        "ConnectSt_hk" => "HasBeenSucceeded",
        "MessageFromSv" => "",
        "Bolsonaro" => $loaderB64,
        "ExpireDays" => "0",
        "Logged_UserHK" => $user,
        "CurrToken" => $currToken,
        "ApkVersion_hk" => $apkVersion,
        "Logged_TokHK" => $tokCli,
    ];
    echo build_profile_response($payload, $privatePem);
} catch (Throwable $e) {
    output_error($e->getMessage(), [
        "type" => get_class($e),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
    ]);
} finally {
    if (isset($mysqli)) {
        $mysqli->close();
    }
}
