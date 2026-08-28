<?php

include 'init.php';

// initialization
$crypter = Crypter::init();
$privatekey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDXJPXlwyB8Oiei8wcwmJNqtfiApKeDz61LDOZaOCM0jB4ou7pW
DrDsN6do0u+wiVPydL4LlAVMDNWnzwsmhEctQZkKYyFPFfDaxBiUZafuMNuyOwpZ
KjPVCd+8PdNez1M0OGmFElkDNtDBw5n3T9wSdwvdUkm+DHvaYXniH86WfwIDAQAB
AoGALH+NiIcySd9xYUeo3glAvFqE2n6z0xR6QEKbxl9EENNGTaB+atxBBaqBKrIu
NzJANa6lvBlSeydQbZPqN342hil6j4m1TA9XX1BrUsev1eLueHXmzY11xCuG8RSh
RJTyzmxEcDXC8+KmIs94E0FEt9WyyPY7Cx8d6U1pJ+wCoZkCQQD6MHnMvbCmfVIm
MQce/jh3oBdI0Vm5Llnjb63c5emIu6bASfJ0oWgb2YKEji9EM6dKam7ahvbwGFmn
LQIzFYeTAkEA3CQfHXvkTTPpxTtm6xeFb7jBaPQ+fze5kBpXBbhsEvzx1AGl85uV
8Caawan1AbSZIpJPaz1EDspFzBnlIlRw5QJBAMHXtzb7rZeBN5nhmKvZujRtND01
/vdsQzblO2cQN326LnuDj7fvqrMRNn+vjk2gW7hbeGIV+dOSejv9sluUDcMCQEqm
tSZ6bOEBSbTof+4Y+9b3AB9nNApQC00ioX//GicWP6t1I6GIkT/u12v1BnCdnZBr
rSLgk8OwNBsSbPFHUUECQC4QrGtLBJEh/DJJsH91HDColH2gQSqExT5rbyGG4s7W
eaLPifKhIuxNa1Amanez1wY/WCjwEfEF3Q/Vgi4XpDI=
-----END RSA PRIVATE KEY-----
EOD;

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
        "MessageString" => "Usuario Invalido",
        "SubscriptionLeft" => "0"
    );
    PlainDie(tokenResponse($ackdata));
}

// Password Validator
$pass = $data["app_Pa"];
if ($pass == null || preg_match("([a-zA-Z0-9]+)", $pass) === 0) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Senha Invalida",
        "SubscriptionLeft" => "0"
    );
    PlainDie(tokenResponse($ackdata));
}

// Query user
$query = $con->query("SELECT * FROM `users` WHERE `username` = '".$con->real_escape_string($uname)."' AND `password` = '".$con->real_escape_string($pass)."'");
if ($query->num_rows < 1) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "User Pass Invalido",
        "SubscriptionLeft" => "0"
    );
    PlainDie(tokenResponse($ackdata));
}

$res = $query->fetch_assoc();

// Check if user is banned
if (isset($res["tlstatus"]) && strtolower($res["tlstatus"]) === "band") {
    $con->query("INSERT INTO banned_log (username, ip, attempt_time) VALUES ('".$con->real_escape_string($uname)."', '".$_SERVER['REMOTE_ADDR']."', NOW())");
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "Login has been Banned!",
        "SubscriptionLeft" => "0"
    );
    PlainDie(tokenResponse($ackdata));
}

// First-time registration
if ($res["registered"] == NULL) {
    $con->query("UPDATE `users` SET `registered` = CURRENT_TIMESTAMP WHERE `username` = '".$con->real_escape_string($uname)."' AND `password` = '".$con->real_escape_string($pass)."'");
}

$uidup = $data["app_ID"];

// --- Multi-device UID handling ---
// get device limit (default 1 nếu không có trường này)
$device_limit = isset($res['device_limit']) ? (int)$res['device_limit'] : 1;

// stored UIDs may be NULL, empty, or comma-separated string
$storedUIDs = isset($res['UID']) ? trim($res['UID']) : '';
$firstDevice = ($storedUIDs === '' || $storedUIDs === null);

if ($firstDevice) {
    $uids = [];
} else {
    // explode and normalize
    $uids = array_filter(array_map('trim', explode(',', $storedUIDs)));
}

// if UID already present -> ok (same device)
if (!in_array($uidup, $uids, true)) {
    // not present: check device limit
    if (count($uids) >= $device_limit) {
        $ackdata = array(
            "Status" => "Failed",
            "MessageString" => "Device limit reached",
            "SubscriptionLeft" => "0"
        );
        PlainDie(tokenResponse($ackdata));
    }

    // append new UID and update DB (escape for safety)
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

    // refresh row data to reflect new expiration
    if ($firstDevice) {
        $res['expired'] = $expiresAtValue;
    }
}

// Expiration check
$expiresAt = strtotime($res['expired']);
if (time() > $expiresAt) {
    $ackdata = array(
        "Status" => "Failed",
        "MessageString" => "User pass Invalido",
        "SubscriptionLeft" => "0"
    );
    PlainDie(tokenResponse($ackdata));
}

// Days left calculation
$Asabsa = date_create($res["expired"]);
$datadehoje = date_create();
$resultado = date_diff($Asabsa, $datadehoje);
$dias = date_interval_format($resultado, '%a');

// Success response
$ackdata = array(
    "Status" => "Success",
    "Loader" => base64_encode(readFileData('Bur')),
    "MessageString" => [
        "Cliente" => $uname,
        "Dias" => $dias
    ],
    "CurrUser" => $uname,
    "CurrPass" => $pass,
    "CurrToken" => "",
    "CurrVersion" => "2.0",
    "SubscriptionLeft" => "$dias"
);

echo tokenResponse($ackdata);
