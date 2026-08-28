<?php

include 'init.php';

// initialization
$crypter = Crypter::init();
$privatekey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
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
-----END RSA PRIVATE KEY-----
EOD;

// ECDSA P-256 private key for SHA256withECDSA signing
$ecdsaPrivateKey = <<<EOD
-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIIHXPaDMtXOjGLG60MYARZFFjnuQ1lSIp3NzlFfBro5foAoGCCqGSM49
AwEHoUQDQgAE7bc459GeZ8+eqmg8M8tYDcP7n11XCRzA5KXkMLhk8zUXzN/niy5m
cvFWk0hK5qyGkDNCpzZlmkZ9wISZyvig3w==
-----END EC PRIVATE KEY-----
EOD;

function aes_ecb_encrypt_b64($data, $key) {
    if (strlen($key) !== 16) {
        throw new RuntimeException("AES key must be 16 bytes");
    }
    $cipher = openssl_encrypt($data, "AES-128-ECB", $key, OPENSSL_RAW_DATA);
    if ($cipher === false) {
        throw new RuntimeException("AES encrypt failed");
    }
    return base64_encode($cipher);
}

function sign_ecdsa_sha256($data, $ecdsaPrivateKeyPem) {
    $key = openssl_pkey_get_private($ecdsaPrivateKeyPem);
    if ($key === false) {
        throw new RuntimeException("ECDSA private key invalid: " . openssl_error_string());
    }
    $ok = openssl_sign($data, $sig, $key, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        throw new RuntimeException("ECDSA sign failed");
    }
    return base64_encode($sig);
}

function tokenResponse($data){
    global $crypter, $privatekey;
    $data = toJson($data);
    $datahash = sha256($data);
    
    $acktoken = array(
        "Data" => profileEncrypt($data, $datahash),
        "Sign" => toBase64($crypter->signByPrivate($privatekey, $data)),
        "Hash" => $datahash
    );
    return toBase64(toJson($acktoken));
}

// token data
$token = fromBase64($_POST['token']);
$tokarr = fromJson($token, true);

// Data section decrypter
$encdata = $tokarr['Data'];
$decdata = trim($crypter->decryptByPrivate($privatekey, fromBase64($encdata)));
$data = fromJson($decdata);

// Username Validator
$uname = $data["app_Us"];
if ($uname == null || preg_match("([a-zA-Z0-9]+)", $uname) === 0) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Usuário inválido",
        "SubscriptionLeft" => 0
    );
    PlainDie(tokenResponse($ackdata));
}

// Password Validator
$pass = $data["app_Pa"];
if ($pass == null || preg_match("([a-zA-Z0-9]+)", $pass) === 0) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Senha inválida",
        "SubscriptionLeft" => 0
    );
    PlainDie(tokenResponse($ackdata));
}

// Query user
$query = $con->query("SELECT * FROM `users` WHERE `username` = '".$con->real_escape_string($uname)."' AND `password` = '".$con->real_escape_string($pass)."'");
if ($query->num_rows < 1) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Usuário e/ou senha incorreta!",
        "SubscriptionLeft" => 0
    );
    PlainDie(tokenResponse($ackdata));
}

$res = $query->fetch_assoc();

// Check if user is banned
if (isset($res["tlstatus"]) && strtolower($res["tlstatus"]) === "band") {
    $con->query("INSERT INTO banned_log (username, ip, attempt_time) VALUES ('".$con->real_escape_string($uname)."', '".$_SERVER['REMOTE_ADDR']."', NOW())");
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Usuário banido!",
        "SubscriptionLeft" => 0
    );
    PlainDie(tokenResponse($ackdata));
}

// First-time registration
if ($res["registered"] == NULL) {
    $con->query("UPDATE `users` SET `registered` = CURRENT_TIMESTAMP WHERE `username` = '".$con->real_escape_string($uname)."' AND `password` = '".$con->real_escape_string($pass)."'");
}

$uidup = $data["app_ID"];

// --- Multi-device UID handling ---
$device_limit = isset($res['device_limit']) ? (int)$res['device_limit'] : 1;
$storedUIDs = isset($res['UID']) ? trim($res['UID']) : '';
$firstDevice = ($storedUIDs === '' || $storedUIDs === null);

if ($firstDevice) {
    $uids = [];
} else {
    $uids = array_filter(array_map('trim', explode(',', $storedUIDs)));
}

if (!in_array($uidup, $uids, true)) {
    if (count($uids) >= $device_limit) {
        $ackdata = array(
            "Status" => "Failed",
            "MessageString" => "Limite de dispositivos atingido",
            "SubscriptionLeft" => 0
        );
        PlainDie(tokenResponse($ackdata));
    }

    $uids[] = $uidup;
    $newUIDs = implode(',', $uids);

    $durationHours = isset($res['duration']) && (int)$res['duration'] > 0 ? (int)$res['duration'] : 24;
    $registeredAt = $res['registered'] ?: date('Y-m-d H:i:s');
    $expiresAtValue = $res['expired'];
    if ($firstDevice) {
        $expiresAtValue = date('Y-m-d H:i:s', strtotime($registeredAt) + ($durationHours * 3600));
    }

    $setParts = ["`UID` = '".$con->real_escape_string($newUIDs)."'"];
    if ($firstDevice) {
        $setParts[] = "`expired` = '".$con->real_escape_string($expiresAtValue)."'";
    }

    $updateSql = sprintf(
        "UPDATE `users` SET %s WHERE `username` = '%s' AND `password` = '%s'",
        implode(', ', $setParts),
        $con->real_escape_string($uname),
        $con->real_escape_string($pass)
    );
    $con->query($updateSql);

    if ($firstDevice) {
        $res['expired'] = $expiresAtValue;
    }
}

// Expiration check
$expiresAt = strtotime($res['expired']);
if (time() > $expiresAt) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Assinatura expirada",
        "SubscriptionLeft" => 0
    );
    PlainDie(tokenResponse($ackdata));
}

// Days left calculation
$Asabsa = date_create($res["expired"]);
$datadehoje = date_create();
$resultado = date_diff($Asabsa, $datadehoje);
$dias = date_interval_format($resultado, '%a');

// Success response with AES-encrypted Loader
$aesKey = "22P9ULFDKPJ70G46";
$loaderRaw = readFileData('Bur');
$loaderEncrypted = $loaderRaw !== "" ? aes_ecb_encrypt_b64($loaderRaw, $aesKey) : "";

$ackdata = array(
    "Status" => "Success",
    "Loader" => $loaderEncrypted,
    "MessageString" => [
        "Cliente" => $uname,
        "Dias" => $dias
    ],
    "CurrUser" => $uname,
    "CurrPass" => $pass,
    "CurrToken" => "",
    "CurrVersion" => "2.0",
    "SubscriptionLeft" => (int)$dias
);

echo tokenResponse($ackdata);
