<?php

function rc4($key, $data) {
    $S = range(0, 255);
    $j = 0;
    $keyLength = strlen($key);

    for ($i = 0; $i < 256; $i++) {
        $j = ($j + $S[$i] + ord($key[$i % $keyLength])) % 256;
        $tmp = $S[$i]; $S[$i] = $S[$j]; $S[$j] = $tmp;
    }

    $i = $j = 0;
    $out = '';

    for ($y = 0; $y < strlen($data); $y++) {
        $i = ($i + 1) % 256;
        $j = ($j + $S[$i]) % 256;
        $tmp = $S[$i]; $S[$i] = $S[$j]; $S[$j] = $tmp;
        $k = $S[($S[$i] + $S[$j]) % 256];
        $out .= chr(ord($data[$y]) ^ $k);
    }

    return $out;
}

function salted_rc4_encrypt($plaintext, $master_key) {
    $salt = random_bytes(8);
    $derived_key = hash('sha256', $master_key . $salt, false);
    return bin2hex($salt . rc4($derived_key, $plaintext));
}

function salted_rc4_decrypt($hex, $master_key) {
    $raw = hex2bin($hex);
    $salt = substr($raw, 0, 8);
    $derived_key = hash('sha256', $master_key . $salt, false);
    return rc4($derived_key, substr($raw, 8));
}

$MASTER_KEY = "R34P3R_X_PR0_V1_S3CUR1TY_M4ST3R";

// Database Configuration
$db_host = "localhost";
$db_user = "u160951123_Titooxxx1";
$db_pass = "Titooxxx1";
$db_name = "u160951123_Titooxxx1";

$raw = trim(file_get_contents("php://input"));

// Decrypt body if hex
$user_key = '';
$device_id = '';
$game = '';
$parsed = [];

if (ctype_xdigit($raw)) {
    parse_str(salted_rc4_decrypt($raw, $MASTER_KEY), $parsed);
    $user_key = trim($parsed['user_key'] ?? $parsed['key'] ?? '');
    $device_id = trim($parsed['device_id'] ?? $parsed['serial'] ?? $parsed['hwid'] ?? $parsed['uuid'] ?? '');
    $game = trim($parsed['game'] ?? '');
}

// Fallback JSON
if ($user_key === '') {
    $json_data = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $user_key = trim($json_data['user_key'] ?? $json_data['key'] ?? '');
        $device_id = trim($json_data['device_id'] ?? $json_data['serial'] ?? $json_data['hwid'] ?? $json_data['uuid'] ?? '');
        $game = trim($json_data['game'] ?? '');
    }
}

// Fallback form-encoded / $_POST
if ($user_key === '') {
    parse_str($raw, $parsed);
    $user_key = trim($parsed['user_key'] ?? $_POST['user_key'] ?? $parsed['key'] ?? $_POST['key'] ?? '');
    $device_id = trim($parsed['device_id'] ?? $_POST['device_id'] ?? $parsed['serial'] ?? $_POST['serial'] ?? $parsed['hwid'] ?? $_POST['hwid'] ?? $parsed['uuid'] ?? $_POST['uuid'] ?? '');
    $game = trim($parsed['game'] ?? $_POST['game'] ?? '');
}

if (empty($user_key)) {
    $response = [
        "status"  => false,
        "message" => "Wrong KEY, Please Enter Right KEY",
        "reason"  => "Wrong KEY, Please Enter Right KEY"
    ];
} else {
    // Connect to Database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        $response = [
            "status"  => false,
            "message" => "Database connection failed!",
            "reason"  => "Database connection failed!"
        ];
    } else {
        $conn->set_charset("utf8mb4");

        // A. Maintenance Check (Optional, safe with table check)
        $maintenance_active = false;
        $onoff_check = $conn->query("SHOW TABLES LIKE 'onoff'");
        if ($onoff_check && $onoff_check->num_rows > 0) {
            $mt_res = $conn->query("SELECT status, myinput FROM onoff WHERE id=1 LIMIT 1");
            if ($mt_res) {
                $mt = $mt_res->fetch_assoc();
                if ($mt && $mt['status'] === 'on') {
                    $maintenance_active = true;
                    $response = [
                        "status"  => false,
                        "message" => $mt['myinput'] ?: "Maintenance",
                        "reason"  => $mt['myinput'] ?: "Maintenance"
                    ];
                }
            }
        }

        if (!$maintenance_active) {
            // Default game value to FREEFIRE if empty
            $game_param = !empty($game) ? $game : "Reaper";

            // B. KEY Check (Query table: keys_code)
            $stmt = $conn->prepare("SELECT id_keys, status, duration, expired_date, max_devices, devices FROM keys_code WHERE user_key = ? AND game = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $user_key, $game_param);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $id_keys = intval($row['id_keys']);
                    $status = intval($row['status']);
                    $duration = intval($row['duration']);
                    $expired_date = $row['expired_date'];
                    $max_devices = intval($row['max_devices']);
                    $devices = $row['devices'];

                    // 1. Check if Key is Banned
                    if ($status !== 1) {
                        $response = [
                            "status"  => false,
                            "message" => "Key is Banned",
                            "reason"  => "Key is Banned"
                        ];
                    } else {
                        // 2. Activation and Expiry Logic
                        $current_time = time();
                        $expiry_timestamp_ms = 0;
                        $final_expiry_date = "";
                        $expired_state = false;

                        if (empty($expired_date) || strpos($expired_date, '0000') !== false) {
                            // First time activation
                            $new_expiry = date('Y-m-d H:i:s', strtotime("+$duration hours"));
                            $conn->query("UPDATE keys_code SET expired_date = '$new_expiry' WHERE id_keys = $id_keys");
                            $expiry_timestamp_ms = strtotime($new_expiry) * 1000;
                            $final_expiry_date = $new_expiry;
                        } else {
                            // Check existing expiration
                            $exp_ts = strtotime($expired_date);
                            if ($current_time > $exp_ts) {
                                $expired_state = true;
                                $response = [
                                    "status"  => false,
                                    "message" => "Key Expired",
                                    "reason"  => "Key Expired"
                                ];
                            } else {
                                $expiry_timestamp_ms = $exp_ts * 1000;
                                $final_expiry_date = $expired_date;
                            }
                        }

                        if (!$expired_state) {
                            // 3. Device Check / Limit Validation
                            $dev_list = array_filter(explode(",", (string)$devices));
                            $max_dev = $max_devices > 0 ? $max_devices : 1;
                            $device_error = false;

                            if (!in_array($device_id, $dev_list)) {
                                if (count($dev_list) < $max_dev) {
                                    if (!empty($device_id)) {
                                        $dev_list[] = $device_id;
                                        $new_dev_str = implode(",", $dev_list);
                                        $conn->query("UPDATE keys_code SET devices = '$new_dev_str' WHERE id_keys = $id_keys");
                                    }
                                } else {
                                    $device_error = true;
                                    $response = [
                                        "status"  => false,
                                        "message" => "Device Limit Reached",
                                        "reason"  => "Device Limit Reached"
                                    ];
                                }
                            }

                            if (!$device_error) {
                                // 4. Signature & Success Response Generation
                                $SALT = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
                                $MAGIC_DIFF_BD = 121591914;
                                $MAGIC_DIFF_BM = 2440;

                                $real_string = "PUBG-" . $user_key . "-" . $device_id . "-" . $SALT;
                                $token_val   = md5($real_string); 
                                $rand_os = rand(300000000, 1800000000);

                                $response = [
                                    "status"      => true,
                                    "message"     => "Login Success",
                                    "reason"      => "Login Success",
                                    "token"       => $token_val,
                                    "expired"     => $final_expiry_date,
                                    "max_devices" => $max_dev,
                                    "data" => [
                                        "real"  => $real_string,
                                        "token" => $token_val,
                                        "rng"   => time(),
                                        "ts"    => (string)$expiry_timestamp_ms,
                                        "loop"  => "9yrDZinK",
                                        "verml" => "196810631"
                                    ],
                                    "ext" => [
                                        "os" => (string)$rand_os,
                                        "bd" => (string)($rand_os + $MAGIC_DIFF_BD),
                                        "bm" => (string)(($rand_os + $MAGIC_DIFF_BD) - $MAGIC_DIFF_BM)
                                    ]
                                ];
                            }
                        }
                    }
                } else {
                    $response = [
                        "status"  => false,
                        "message" => "Key Not Found",
                        "reason"  => "Key Not Found"
                    ];
                }
                $stmt->close();
            } else {
                $response = [
                    "status"  => false,
                    "message" => "Database Query Error: " . $conn->error,
                    "reason"  => "Database Query Error"
                ];
            }
        }
        $conn->close();
    }
}

echo salted_rc4_encrypt(json_encode($response, JSON_UNESCAPED_UNICODE), $MASTER_KEY);