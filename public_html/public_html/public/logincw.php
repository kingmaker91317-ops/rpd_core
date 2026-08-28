<?php
header("Content-Type: text/plain; charset=utf-8");

$db_host = "localhost";
$db_user = "mbktunp_hama";
$db_pass = "mbktunp_hama";
$db_name = "mbktunp_hama";

$username = isset($_GET["key"]) ? trim($_GET["key"]) : "";
$uid = isset($_GET["uid"]) ? trim($_GET["uid"]) : "";
$device_id = isset($_GET["device_id"]) ? trim($_GET["device_id"]) : "";
$game = isset($_GET["game"]) ? trim($_GET["game"]) : "";

$log_file = __DIR__ . "/logincw.log";
function log_fail($reason, $extra = []) {
    global $log_file, $username, $uid, $device_id, $game;
    $context = array_merge([
        "reason" => $reason,
        "key" => $username,
        "uid" => $uid,
        "device_id" => $device_id,
        "game" => $game
    ], $extra);
    $line = date("Y-m-d H:i:s") . " | " . json_encode($context, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
    echo "failed";
    exit;
}

if ($username === "" || $uid === "" || $device_id === "" || $game === "") {
    log_fail("missing_params");
}

if ($game !== "com.haegin.playtogether2") {
    log_fail("invalid_game");
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    log_fail("db_connect_failed", ["error" => $conn->connect_error]);
}

$sql = "SELECT username, UID, status, expired_date, expired, active_devices, max_devices FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->close();
    log_fail("db_prepare_failed");
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    log_fail("user_not_found");
}

$row = $result->fetch_assoc();
$stmt->close();
$conn->close();

if ((int)$row["status"] === 2) {
    log_fail("status_blocked");
}

if ((int)$row["status"] !== 1) {
    log_fail("status_not_active");
}

if ($row["UID"] === null || $row["UID"] === "") {
    $conn2 = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn2->connect_error) {
        log_fail("db_connect_failed_uid_set", ["error" => $conn2->connect_error]);
    }
    $update = $conn2->prepare("UPDATE users SET UID = ?, active_devices = ?, updated_at = NOW() WHERE username = ? LIMIT 1");
    if (!$update) {
        $conn2->close();
        log_fail("uid_update_prepare_failed");
    }
    $active_devices = $device_id;
    $update->bind_param("sss", $uid, $active_devices, $username);
    $update->execute();
    $update->close();
    $conn2->close();
} else if ($row["UID"] !== $uid) {
    log_fail("uid_mismatch", ["db_uid" => $row["UID"]]);
}

$now = new DateTime("now");
if (!empty($row["expired_date"])) {
    $exp = DateTime::createFromFormat("Y-m-d H:i:s", $row["expired_date"]);
    if ($exp && $now > $exp) {
        log_fail("expired_date_passed", ["expired_date" => $row["expired_date"]]);
    }
} elseif (!empty($row["expired"])) {
    $exp = DateTime::createFromFormat("Y-m-d H:i:s", $row["expired"]);
    if ($exp && $now > $exp) {
        log_fail("expired_passed", ["expired" => $row["expired"]]);
    }
}

if (!empty($row["active_devices"])) {
    $list = "," . $row["active_devices"] . ",";
    $needle = "," . $device_id . ",";
    if (strpos($list, $needle) === false) {
        log_fail("device_not_allowed", ["active_devices" => $row["active_devices"]]);
    }
}

echo "success";
?>