<?php
/**
 * Nexxa Project - Private Standalone Loader Delivery Service
 */

// ================= CONFIG LOG =================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

$FIXED_TOKEN = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9EVm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
$APP_SIGNATURE = "3836D249B410FA87E3D06E9A0902255749430626AABE5C5D5DA0E3591E6A1311";
$SHARED_SALT = "NexxaPrivateSharedSalt2026!";

// Simple XXTEA encryption implementation
function xxtea_encrypt_raw($str, $key) {
    if ($str == "") return "";
    $v = str2long($str, true);
    $k = str2long($key, false);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;
    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = 0;
    while (0 < $q--) {
        $sum = ($sum + $delta) & 0xffffffff;
        $e = ($sum >> 2) & 3;
        for ($p = 0; $p < $n; $p++) {
            $y = $v[$p + 1];
            $mx = ((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4))) ^ (($sum ^ $y) + ($k[($p & 3) ^ $e] ^ $z));
            $z = $v[$p] = ($v[$p] + $mx) & 0xffffffff;
        }
        $y = $v[0];
        $mx = ((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4))) ^ (($sum ^ $y) + ($k[($p & 3) ^ $e] ^ $z));
        $z = $v[$n] = ($v[$n] + $mx) & 0xffffffff;
    }
    return long2str($v, false);
}

function str2long($s, $w) {
    $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
    $v = array_values($v);
    if ($w) {
        $v[] = strlen($s);
    }
    return $v;
}

function long2str($v, $w) {
    $len = count($v);
    $n = ($len - 1) << 2;
    if ($w) {
        $m = $v[$len - 1];
        if (($m < $n - 3) || ($m > $n)) return false;
        $n = $m;
    }
    $s = "";
    for ($i = 0; $i < $len; $i++) {
        $s .= pack("V", $v[$i]);
    }
    if ($w) {
        return substr($s, 0, $n);
    }
    return $s;
}

function rc4($data, $key) {
    $s = array();
    for ($i = 0; $i < 256; $i++) {
        $s[$i] = $i;
    }
    $j = 0;
    for ($i = 0; $i < 256; $i++) {
        $j = ($j + $s[$i] + ord($key[$i % strlen($key)])) % 256;
        $x = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $x;
    }
    $i = 0;
    $j = 0;
    $res = '';
    for ($y = 0; $y < strlen($data); $y++) {
        $i = ($i + 1) % 256;
        $j = ($j + $s[$i]) % 256;
        $x = $s[$i];
        $s[$i] = $s[$j];
        $s[$j] = $x;
        $res .= $data[$y] ^ chr($s[($s[$i] + $s[$j]) % 256]);
    }
    return $res;
}

function returnError($message) {
    header("HTTP/1.1 403 Forbidden");
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => $message]);
    exit;
}

function int32($n) {
    while ($n >= 2147483648) $n -= 4294967296;
    while ($n <= -2147483649) $n += 4294967296;
    return (int)$n;
}

function xxtea_decrypt_raw($str, $key) {
    if ($str == "") return "";
    $v = str2long($str, false);
    $k = str2long($key, false);
    if (count($k) < 4) {
        for ($i = count($k); $i < 4; $i++) {
            $k[$i] = 0;
        }
    }
    $n = count($v) - 1;
    $z = $v[$n];
    $y = $v[0];
    $delta = 0x9E3779B9;
    $q = floor(6 + 52 / ($n + 1));
    $sum = int32($q * $delta);
    while ($sum != 0) {
        $e = ($sum >> 2) & 3;
        for ($p = $n; $p > 0; $p--) {
            $z = $v[$p - 1];
            $mx = int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ (int32($sum ^ $y) + int32($k[($p & 3) ^ $e] ^ $z)));
            $y = $v[$p] = int32($v[$p] - $mx);
        }
        $z = $v[$n];
        $mx = int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ (int32($sum ^ $y) + int32($k[($p & 3) ^ $e] ^ $z)));
        $y = $v[0] = int32($v[0] - $mx);
        $sum = int32($sum - $delta);
    }
    return long2str($v, true);
}

// Block standard browsers
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$clientHeader = $_SERVER['HTTP_X_API_CLIENT'] ?? '';
if (strpos($userAgent, 'NativeApp') === false && strpos($userAgent, 'AppNativeGuard') === false && $clientHeader !== 'NativeApp') {
    returnError("Access denied");
}

$rawBody = file_get_contents("php://input");
if (empty($rawBody)) {
    returnError("No payload");
}

$parts = explode('.', $rawBody);
if (count($parts) !== 3) {
    returnError("Invalid format");
}

$base64Ciphertext = $parts[0];
$timestamp = $parts[1];
$nonce = $parts[2];

// Decrypt request payload
$sessionKey = substr(hash('sha256', $FIXED_TOKEN . $APP_SIGNATURE . $timestamp . $nonce), 0, 16);
$rc4Key = substr(hash('sha256', $nonce . $timestamp . $FIXED_TOKEN . $APP_SIGNATURE), 0, 16);

if (abs(time() - intval($timestamp)) > 300) {
    returnError("Expired request");
}

$payloadToDecrypt = base64_decode($base64Ciphertext);
if (empty($payloadToDecrypt)) {
    returnError("Empty cipher");
}

$decryptedPayload = rc4($payloadToDecrypt, $rc4Key);
$len = strlen($decryptedPayload);
if ($len < 32) {
    returnError("Truncated packet");
}

$encryptedData = substr($decryptedPayload, 0, $len - 32);
$mac = substr($decryptedPayload, $len - 32);

$expectedMac = hash_hmac('sha256', $encryptedData, $sessionKey, true);
if (!hash_equals($expectedMac, $mac)) {
    returnError("MAC validation failed");
}

$decryptedJson = xxtea_decrypt_raw($encryptedData, $sessionKey);
// Clean trailing null padding if xxtea implementation padded
$decryptedJson = rtrim($decryptedJson, "\0");

$requestData = json_decode($decryptedJson, true);
if (!$requestData || !isset($requestData['session_token'])) {
    returnError("Invalid parameters");
}

$sessionToken = base64_decode($requestData['session_token']);
$tokenParts = explode('|', $sessionToken);
if (count($tokenParts) !== 4) {
    returnError("Invalid session token structure");
}

$uKey = $tokenParts[0];
$sDev = $tokenParts[1];
$tokenTs = $tokenParts[2];
$tokenSig = $tokenParts[3];

// Verify token signature to prevent forged token downloads
$expectedTokenSig = md5($uKey . $sDev . $tokenTs . $SHARED_SALT);
if ($expectedTokenSig !== $tokenSig) {
    returnError("Session signature invalid");
}

// Expiry validation: Token is valid for 5 minutes maximum
if (abs(time() - intval($tokenTs)) > 300) {
    returnError("Session token expired");
}

// Deliver Loader
$encLoader = @file_get_contents(__DIR__ . "/lol/kernel.kmods");
if (!$encLoader) {
    $encLoader = @file_get_contents(__DIR__ . "/kernel.kmods");
}
if (!$encLoader) {
    returnError("Loader file missing: kernel.kmods not found in root or lol/ folder");
}

$aesKey = "A5D8F2E7B3C6A1D9E0F4D2C3B5E6F7A8";
$decLoader = openssl_decrypt($encLoader, 'aes-256-ecb', $aesKey, OPENSSL_RAW_DATA);
if ($decLoader === false || empty($decLoader)) {
    $decLoader = $encLoader;
}

// Encrypt loader using raw XXTEA with current sessionKey
$rawEncLoader = xxtea_encrypt_raw($decLoader, $sessionKey);
$base64Load = base64_encode($rawEncLoader);

// Create Hardened Honeypot Response Payload
$responseData = [];

// Calculate dynamic key name for real Loader
$realKeyName = md5($timestamp . "LoaderSecurePayloadKeySeed2026" . $nonce);
$responseData[$realKeyName] = $base64Load;

// Populate response with 15 fake keys to confuse dumps/intercepts
for ($i = 0; $i < 15; $i++) {
    $fakeKey = md5($timestamp . "FakeKeySeed" . $i . $nonce);
    
    // Generate random realistic-looking binary/base64 chunk
    $randomSize = rand(2000, 5000);
    $randomBytes = "";
    for ($j = 0; $j < $randomSize; $j++) {
        $randomBytes .= chr(rand(0, 255));
    }
    $responseData[$fakeKey] = base64_encode($randomBytes);
}

// Shuffle response keys to make analysis harder
uksort($responseData, function() { return rand(-1, 1); });

$jsonResponse = json_encode($responseData);
$encryptedResponse = xxtea_encrypt_raw($jsonResponse, $sessionKey);

$responseMac = hash_hmac('sha256', $encryptedResponse, $sessionKey, true);
$payloadToEncrypt = $encryptedResponse . $responseMac;
$rc4Encrypted = rc4($payloadToEncrypt, $rc4Key);
$finalResponse = base64_encode($rc4Encrypted) . "." . $timestamp . "." . $nonce;

header("Content-Type: text/plain");
echo $finalResponse;
exit;

