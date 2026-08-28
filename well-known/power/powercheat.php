<?php
// lkapi_pubg_strict.php
// Logic: Connect.php (Strict) | Security: LKAPI (RSA/AES/XOR)
header('Content-Type: text/plain');
error_reporting(0);
require_once __DIR__ . '/../config/db_config.php'; // Ensure $pdo is ready

// --- CONFIGURATION ---
$target_game = "PUBG"; 
$PRIVATE_KEY_FILE = 'power_private.pem'; // The key you generated

// 1. Load Private Key
$pk_content = file_get_contents($PRIVATE_KEY_FILE);
if (!$pk_content) die("Error: Private Key Missing");
$lkPrivateKey = openssl_get_privatekey($pk_content);

// 2. Response Function
function sendLkResponse($data, $isSuccess, $privKey, $origTok) {
    // Structure strictly matches LKAPI clients, but messages match Connect.php
    $res = [
        "ConnectSt_hk"  => $isSuccess ? "HasBeenSucceeded" : "Failed",
        "Logged_UserHK" => $data['user'] ?? "",
        "Logged_TokHK"  => $origTok,
        "MessageFromSv" => $data['msg'] ?? "", // This will hold the Connect.php error strings
        "piddaemon"     => "1",
        "Status_hk"     => $isSuccess ? "1" : "0",
        "time"          => time()
    ];

    $jsonStr = json_encode($res, JSON_UNESCAPED_SLASHES);
    
    // Sign
    openssl_sign($jsonStr, $signature, $privKey, OPENSSL_ALGO_SHA256);
    $hash = hash('sha256', $jsonStr);
    
    // Encrypt (XOR)
    $enc = "";
    $jLen = strlen($jsonStr); $hLen = strlen($hash);
    for ($i = 0; $i < $jLen; $i++) { $enc .= $jsonStr[$i] ^ $hash[$i % $hLen]; }

    // OUTPUT
    die(base64_encode(json_encode([
        "Dados_hk" => base64_encode($enc),
        "Hash_hk"  => $hash,
        "Sign_hk"  => base64_encode($signature)
    ])));
}

// 3. Input Handling (Decrypt)
$uKey = ''; $sDev = ''; $reqTok = "NATIVE_DRM";

if (isset($_POST['tokserver_hk'])) {
    $env = json_decode(base64_decode($_POST['tokserver_hk']), true);
    if (isset($env['Dados_hk'])) {
        openssl_private_decrypt(base64_decode($env['Dados_hk']), $dec, $lkPrivateKey, OPENSSL_PKCS1_PADDING);
        $req = json_decode($dec, true);
        
        $uKey = $req['User_hk'] ?? '';
        $sDev = $req['Uid_hk'] ?? '';
        $reqTok = $env['Tok_hk'] ?? $reqTok;
    }
}

// 4. Logic Implementation (Strictly Connect.php)

// A. Check Parameters
if (empty($uKey) || empty($sDev)) {
    sendLkResponse(["msg" => "INVALID PARAMETER", "user" => $uKey], false, $lkPrivateKey, $reqTok);
}

// B. Check Maintenance (Table: onoff)
$stmt = $pdo->query("SELECT * FROM onoff WHERE id=1 LIMIT 1");
$maint = $stmt->fetch();

// Logic from Connect.php: if status == 'on', return the message in 'myinput'
if ($maint && $maint['status'] == 'on') {
    sendLkResponse(["msg" => $maint['myinput'], "user" => $uKey], false, $lkPrivateKey, $reqTok);
}

// C. Find Key (Table: keys_code)
$stmt = $pdo->prepare("SELECT * FROM keys_code WHERE user_key = ? AND game = ? LIMIT 1");
$stmt->execute([$uKey, $target_game]);
$findKey = $stmt->fetch();

// Logic from Connect.php: If not found -> USER OR GAME NOT REGISTERED
if (!$findKey) {
    sendLkResponse(["msg" => "USER OR GAME NOT REGISTERED", "user" => $uKey], false, $lkPrivateKey, $reqTok);
}

// D. Check Status
// Logic from Connect.php: If status != 1 -> KEY LOCKED
if ($findKey['status'] != 1) {
    sendLkResponse(["msg" => "KEY LOCKED", "user" => $uKey], false, $lkPrivateKey, $reqTok);
}

// E. Device Management & Expiry Logic
$id_keys = $findKey['id_keys']; // Using primary key from Connect.php
$max_dev = $findKey['max_devices'];
$devicesStr = $findKey['devices'] ?? "";
$lsDevice = array_filter(explode(",", $devicesStr));
$duration = $findKey['duration'];
$expired_date = $findKey['expired_date'];

$doUpdate = false;
$deviceAllowed = false;

// 1. Device Check
if (in_array($sDev, $lsDevice)) {
    $deviceAllowed = true;
} else {
    // New Device
    if (count($lsDevice) < $max_dev) {
        $lsDevice[] = $sDev;
        $devicesStr = implode(",", $lsDevice);
        $doUpdate = true;
        $deviceAllowed = true;
    } else {
        // Logic from Connect.php -> MAXIMUM DEVICE REACHED
        sendLkResponse(["msg" => "MAXIMUM DEVICE REACHED", "user" => $uKey], false, $lkPrivateKey, $reqTok);
    }
}

// 2. Expiry Check
$new_expiry = $expired_date;

if (empty($expired_date)) {
    // Logic from Connect.php -> addHours($duration)
    $new_expiry = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
    $doUpdate = true;
} else {
    // Logic from Connect.php -> Check if now is before expired
    if (strtotime($expired_date) < time()) {
        sendLkResponse(["msg" => "KEY EXPIRADA!", "user" => $uKey], false, $lkPrivateKey, $reqTok);
    }
}

// F. Final Update & Success
if ($deviceAllowed) {
    if ($doUpdate) {
        $pdo->prepare("UPDATE keys_code SET devices=?, expired_date=? WHERE id_keys=?")
            ->execute([$devicesStr, $new_expiry, $id_keys]);
    }

    // Logic from Connect.php: Success
    // Note: Connect.php calculated a token here, but we are using the LKAPI secure response format.
    // We send "Success" so the client knows it passed.
    sendLkResponse(["msg" => "Success", "user" => $uKey], true, $lkPrivateKey, $reqTok);
}
?>