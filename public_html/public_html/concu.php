<?php
/**
 * QuanCheater Login Server - Simple Version
 * Version: 3.0 Lite
 * Date: 2025
 * No Database Required - Random Login Support
 */

// ===== CONFIGURATION =====
define('SECRET_KEY', 'quancheatervn02t10');
define('OUTER_KEY', 'quancheatervn_outer_key');
define('SIGN_KEY', 'quancheater_sign_v3');

// ===== HEADERS =====
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ===== LOG FUNCTION =====
function log_event($message, $level = 'INFO') {
    $log_file = __DIR__ . '/logs/login_' . date('Y-m-d') . '.log';
    @mkdir(__DIR__ . '/logs', 0755, true);
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] [$level] $message\n", FILE_APPEND);
}

// ===== RESPONSE FUNCTION =====
function send_response($code, $msg = '', $data = []) {
    $response = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    echo json_encode($response);
    exit;
}

// ===== ENCRYPTED RESPONSE =====
function send_encrypted($payload) {
    $inner = encrypt_aes(json_encode($payload), SECRET_KEY);
    if (empty($inner)) {
        send_response(500, 'Encrypt inner failed', []);
    }
    $outer = encrypt_aes($inner, OUTER_KEY);
    if (empty($outer)) {
        send_response(500, 'Encrypt outer failed', []);
    }
    echo json_encode(['data' => $outer]);
    exit;
}

// ===== SECURITY FUNCTIONS =====
function verify_signature($data, $sign, $key) {
    $computed_sign = hash_hmac('sha256', $data, $key);
    return hash_equals($computed_sign, $sign);
}

function encrypt_aes($data, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $key_hash = hash('sha256', $key, true);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key_hash, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) return '';
    return base64_encode($iv . $encrypted);
}

function decrypt_aes($data, $key) {
    try {
        $data = base64_decode($data, true);
        if ($data === false || strlen($data) < 16) return '';
        
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $key_hash = hash('sha256', $key, true);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key_hash, OPENSSL_RAW_DATA, $iv);
        return $decrypted === false ? '' : $decrypted;
    } catch (Exception $e) {
        return '';
    }
}


// ===== MAIN LOGIN LOGIC - SIMPLE VERSION =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_encrypted(['code' => 400, 'msg' => 'Invalid request method']);
}

$rq = isset($_POST['rq']) ? $_POST['rq'] : '';

if (empty($rq)) {
    log_event("Empty request", 'WARNING');
    send_encrypted(['code' => 400, 'msg' => 'Invalid request']);
}

try {
    // Decrypt outer layer
    $outer_decrypted = decrypt_aes($rq, OUTER_KEY);
    if (empty($outer_decrypted)) {
        log_event("Failed to decrypt outer layer", 'WARNING');
        send_encrypted(['code' => 400, 'msg' => 'Invalid encryption']);
    }
    
    // Decrypt inner layer
    $inner_decrypted = decrypt_aes($outer_decrypted, SECRET_KEY);
    if (empty($inner_decrypted)) {
        log_event("Failed to decrypt inner layer", 'WARNING');
        send_encrypted(['code' => 400, 'msg' => 'Invalid encryption']);
    }
    
    // Parse JSON
    $layer1 = json_decode($inner_decrypted, true);
    if (!$layer1 || !isset($layer1['data']) || !isset($layer1['sign'])) {
        log_event("Invalid layer structure", 'WARNING');
        send_encrypted(['code' => 400, 'msg' => 'Invalid request format']);
    }
    
    // Verify signature
    if (!verify_signature($layer1['data'], $layer1['sign'], SIGN_KEY)) {
        log_event("Invalid signature", 'WARNING');
        send_encrypted(['code' => 403, 'msg' => 'Invalid signature']);
    }
    
    // Decrypt payload
    $payload_decrypted = decrypt_aes($layer1['data'], SECRET_KEY);
    $payload = json_decode($payload_decrypted, true);
    
    if (!$payload || !isset($payload['game']) || !isset($payload['user_key']) || !isset($payload['serial'])) {
        log_event("Missing payload fields", 'WARNING');
        send_encrypted(['code' => 400, 'msg' => 'Missing required fields']);
    }
    
    $game = $payload['game'];
    $user_key = $payload['user_key'];
    $serial = $payload['serial'];
    
    // Verify game type
    if ($game !== 'ptg') {
        log_event("Invalid game type: $game", 'WARNING');
        send_encrypted(['code' => 403, 'msg' => 'Invalid game']);
    }
    
    // ===== SIMPLE RANDOM LOGIN - NO DATABASE =====
    // Generate random success rate (90% success)
    $random_success = (rand(1, 100) <= 90) ? true : false;
    
    if ($random_success) {
        // Generate token
        $token_raw = "ptg-" . $user_key . "-" . $serial . "-" . SECRET_KEY;
        $token = md5($token_raw);
        $rng = time();
        
        // Generate random expiry date (7-30 days from now)
        $exp_days = rand(7, 30);
        $expiry = date('Y-m-d H:i:s', strtotime("+$exp_days days"));
        
        // Generate random key type
        $key_type = (rand(1, 100) <= 30) ? 'vip' : 'vip';
        
        // Generate random creator name
        $creator_names = ['Admin', 'QuanCheater', 'User', 'Developer', 'Support'];
        $creator_name = $creator_names[array_rand($creator_names)];
        
        log_event("Successful login: $user_key (Type: $key_type)", 'INFO');
        
        // Send success response
        $response_data = [
            'token' => $token,
            'rng' => $rng,
            'EXP' => $expiry,
            'key_type' => $key_type,
            'creator_name' => $creator_name,
            'server_version' => '3.0 Lite'
        ];
        
        send_encrypted([
            'code' => 200,
            'data' => $response_data
        ]);
    } else {
        // Random error
        $errors = [
            [401, 'Key does not exist.'],
            [402, 'Key is locked.'],
            [405, 'Key has expired.'],
            [406, 'Device limit exceeded.'],
            [407, 'VPN/Proxy is not allowed.']
        ];
        
        $error = $errors[array_rand($errors)];
        log_event("Login failed: $user_key - " . $error[1], 'WARNING');
        send_encrypted(['code' => $error[0], 'msg' => $error[1]]);
    }
    
} catch (Exception $e) {
    log_event("Exception: " . $e->getMessage(), 'ERROR');
    send_encrypted(['code' => 500, 'msg' => 'Server error']);
}
?>
