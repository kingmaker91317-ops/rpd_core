<?php
/**
 * server.php - PHP API Server cho protocol AES-256-GCM + RSA-2048
 *
 * Protocol (theo phân tích từ Frida):
 *   1. Client tạo random AES-256 key (32 bytes) + random IV (12 bytes)
 *   2. Client AES-GCM encrypt request JSON → ciphertext + tag (16 bytes)
 *   3. Client RSA encrypt AES key bằng public key server → 256 bytes
 *   4. Client POST JSON: { "m": base64(ciphertext+tag), "v": base64(iv), "p": base64(rsa_encrypted_key) }
 *   5. Server RSA decrypt → lấy AES key
 *   6. Server AES-GCM decrypt → lấy request JSON
 *   7. Server xử lý, build response JSON
 *   8. Server AES-GCM encrypt response (cùng AES key + new random IV)
 *   9. Server trả: { "m": base64(ciphertext+tag), "v": base64(resp_iv), "c": "success", "e": timestamp }
 *
 * Cách chạy:
 *   php -S 0.0.0.0:8080 server.php
 *
 * Key files cần có cùng thư mục:
 *   private.pem  - RSA private key (đã gen bằng gen_rsa.py)
 *   public.pem   - RSA public key
 */

error_reporting(E_ALL);

// ============================================================================
// CẤU HÌNH
// ============================================================================
define('PRIVATE_KEY_FILE', __DIR__ . '/private.pem');
define('PUBLIC_KEY_FILE', __DIR__ . '/public.pem');

// Danh sách games trả về cho client
$GAMES_CONFIG = [
    [
        'name'         => '8 Ball Pool',
        'package'      => 'com.miniclip.eightballpool',
        'version'      => '56.18.2',
        'image'        => 'https://connect.buykey.today/img/8ball.png',
        'lib_url'      => 'https://connect.buykey.today/download/libnms_56.18.2.so',
        'lib_name'     => 'mini_56.18.2',
        'obb_needed'   => 'no',
        'last_updated' => date('F d'),
    ],
];

// Danh sách user_key hợp lệ (database đơn giản)
$VALID_USERS = [
    'MVP-1D-850e99dfd9edd3b0b206139b721b75d6' => [
        'expire_date' => '2026-12-31 23:59:59',
    ],
    // Thêm user khác ở đây:
    // 'MVP-XX-xxxxx' => ['expire_date' => '2026-06-01 00:00:00'],
];


// ============================================================================
// CRYPTO FUNCTIONS
// ============================================================================

/**
 * RSA decrypt bằng private key (PKCS1v15 padding - Java default).
 * @return string|false
 */
function rsaDecrypt(string $encrypted)
{
    $privKey = openssl_pkey_get_private(file_get_contents(PRIVATE_KEY_FILE));
    if (!$privKey) {
        return false;
    }

    $decrypted = '';
    $ok = openssl_private_decrypt($encrypted, $decrypted, $privKey, OPENSSL_PKCS1_PADDING);
    return $ok ? $decrypted : false;
}

/**
 * AES-256-GCM decrypt.
 * $data = ciphertext || tag (16 bytes cuối là tag)
 * $iv   = 12 bytes
 * $key  = 32 bytes
 * @return string|false
 */
function aesGcmDecrypt(string $data, string $iv, string $key)
{
    if (strlen($data) < 16) {
        return false;
    }

    $tagLen = 16;
    $ciphertext = substr($data, 0, -$tagLen);
    $tag        = substr($data, -$tagLen);

    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $plaintext;
}

/**
 * AES-256-GCM encrypt.
 * Trả về: ciphertext || tag (16 bytes)
 * @return string|false
 */
function aesGcmEncrypt(string $plaintext, string $iv, string $key)
{
    $tag = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',  // AAD (empty, khớp với Frida log: aad=None)
        16   // Tag length
    );

    if ($ciphertext === false) {
        return false;
    }

    // Java Cipher.doFinal() trả về ciphertext||tag, nên server cũng phải vậy
    return $ciphertext . $tag;
}


// ============================================================================
// REQUEST PROCESSING
// ============================================================================

/**
 * Parse và decrypt request từ client.
 *
 * Input JSON format (giả định, cần verify với raw HTTP body):
 * {
 *     "m": base64(aes_gcm_ciphertext + tag),   // encrypted request body
 *     "v": base64(iv_12_bytes),                  // AES-GCM IV
 *     "p": base64(rsa_encrypted_aes_key)         // RSA encrypted AES-256 key
 * }
 *
 * ⚠️ LƯU Ý: Format gốc có thể có header bytes trước mỗi field.
 *    Nếu client gốc không gửi đúng format này, cần hook raw HTTP body
 *    để xác định chính xác rồi sửa hàm này.
 * @return array|false
 */
function decryptRequest(array $requestJson)
{
    // 1. RSA decrypt AES key
    $encryptedKey = base64_decode($requestJson['p'] ?? '');
    if (empty($encryptedKey)) {
        return ['error' => 'Missing field p (encrypted AES key)'];
    }

    // Theo log thật: p thường = 8-byte header + 256-byte RSA block (tổng 264)
    if (strlen($encryptedKey) > 256) {
        $encryptedKey = substr($encryptedKey, -256);
    }

    $aesKey = rsaDecrypt($encryptedKey);
    if ($aesKey === false || strlen($aesKey) !== 32) {
        return ['error' => 'RSA decrypt failed - wrong key or format'];
    }

    // 2. Get IV
    $iv = base64_decode($requestJson['v'] ?? '');
    if (strlen($iv) !== 12) {
        // Theo log thật: v thường = 7-byte header + 12-byte IV (tổng 19)
        if (strlen($iv) > 12) {
            // Thử: last 12 bytes
            $iv = substr($iv, -12);
        } else {
            return ['error' => 'Invalid IV length: ' . strlen($iv) . ' (expected 12)'];
        }
    }

    // 3. AES-GCM decrypt request body
    $encryptedBody = base64_decode($requestJson['m'] ?? '');
    if (empty($encryptedBody)) {
        return ['error' => 'Missing field m (encrypted body)'];
    }

    // Theo log thật: m thường = 8-byte header + (ciphertext||tag), tổng 519 bytes
    $plaintext = false;
    if (strlen($encryptedBody) > 8 + 16) {
        $plaintext = aesGcmDecrypt(substr($encryptedBody, 8), $iv, $aesKey);
    }

    // Nếu fail, thử thêm các offset phổ biến
    if ($plaintext === false) {
        foreach ([0, 7, 8, 12, 16] as $offset) {
            if (strlen($encryptedBody) > $offset + 16) {
                $plaintext = aesGcmDecrypt(substr($encryptedBody, $offset), $iv, $aesKey);
                if ($plaintext !== false) {
                    break;
                }
            }
        }
    }

    if ($plaintext === false) {
        return ['error' => 'AES-GCM decrypt failed'];
    }

    $requestData = json_decode($plaintext, true);
    if ($requestData === null) {
        return ['error' => 'Decrypted data is not valid JSON: ' . substr($plaintext, 0, 100)];
    }

    return [
        'data'    => $requestData,
        'aes_key' => $aesKey,
        'raw_p'   => $requestJson['p'] ?? '',
    ];
}


/**
 * Build và encrypt response.
 */
function buildEncryptedResponse(array $requestData, string $aesKey, array $gamesConfig, array $validUsers, string $rawP = ''): string
{
    $userKey = $requestData['user_key'] ?? '';
    $callApp = $requestData['call_app'] ?? '';

    // Kiểm tra user
    $userConfig = $validUsers[$userKey] ?? null;
    if (!$userConfig) {
        // User không hợp lệ - vẫn trả encrypted response
        $responseData = [
            'status'  => false,
            'rng'     => time(),
            'call_app' => $callApp,
            'user_key' => $userKey,
            'message'  => 'Invalid or expired user key',
        ];
    } else {
        // User hợp lệ
        $responseData = [
            'status'      => true,
            'rng'         => time(),
            'call_app'    => $callApp,
            'user_key'    => $userKey,
            'token'       => bin2hex(random_bytes(16)),
            'expire_date' => $userConfig['expire_date'],
            'games'       => $gamesConfig,
        ];
    }

    $responsePlaintext = json_encode($responseData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Encrypt response với cùng AES key, nhưng IV mới
    $responseIv = random_bytes(12);
    $encrypted  = aesGcmEncrypt($responsePlaintext, $responseIv, $aesKey);

    if ($encrypted === false) {
        // Fallback: trả plaintext nếu encrypt fail
        return json_encode(['c' => 'error', 'message' => 'Encrypt failed']);
    }

    // Wrapper theo log thật:
    // - m: 8-byte header + encrypted payload
    // - v: 7-byte header + iv(12)
    // - p: giữ nguyên raw p từ request (tương thích parser client)
    $wrappedM = random_bytes(8) . $encrypted;
    $wrappedV = random_bytes(7) . $responseIv;

    $respP = $rawP;
    if (empty($respP)) {
        $respP = base64_encode(random_bytes(8) . random_bytes(256));
    }

    // Package response JSON
    $responseJson = [
        'm' => base64_encode($wrappedM),
        'v' => base64_encode($wrappedV),
        'p' => $respP,
        'c' => 'success',
        'e' => time(),
        'n' => base64_encode(hash('sha256', $responsePlaintext, true)),  // nonce/hash
    ];

    return json_encode($responseJson);
}


// ============================================================================
// MAIN HANDLER
// ============================================================================

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// Check key files exist
if (!file_exists(PRIVATE_KEY_FILE)) {
    http_response_code(500);
    echo json_encode(['error' => 'Private key not found. Run gen_rsa.py first.']);
    exit;
}

// Read POST body
$body = file_get_contents('php://input');

if (empty($body)) {
    // GET request - show status
    echo json_encode([
        'status'  => 'online',
        'server'  => 'MVP Auth Server',
        'time'    => date('Y-m-d H:i:s'),
        'version' => '1.0',
    ]);
    exit;
}

// Parse request JSON
$requestJson = json_decode($body, true);
if ($requestJson === null) {
    // Có thể request body không phải JSON (format gốc khác)
    // Log raw body để debug
    file_put_contents(__DIR__ . '/debug_raw_request.bin', $body);
    file_put_contents(__DIR__ . '/debug_raw_request_hex.txt', bin2hex($body));

    http_response_code(400);
    echo json_encode([
        'error'   => 'Cannot parse request body as JSON',
        'hint'    => 'Raw body saved to debug_raw_request.bin for analysis',
        'body_len' => strlen($body),
        'body_hex_start' => bin2hex(substr($body, 0, 50)),
    ]);
    exit;
}

// Log request cho debug
$logEntry = date('[Y-m-d H:i:s]') . " Request from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
file_put_contents(__DIR__ . '/server.log', $logEntry, FILE_APPEND);

// Decrypt request
$result = decryptRequest($requestJson);

if (isset($result['error'])) {
    // Decrypt failed - log chi tiết
    $logEntry = date('[Y-m-d H:i:s]') . " Decrypt error: " . $result['error'] . "\n";
    $logEntry .= "  Fields present: " . implode(', ', array_keys($requestJson)) . "\n";
    if (isset($requestJson['m'])) {
        $mData = base64_decode($requestJson['m']);
        $logEntry .= "  m decoded length: " . strlen($mData) . " bytes\n";
        $logEntry .= "  m first 32 hex: " . bin2hex(substr($mData, 0, 32)) . "\n";
    }
    if (isset($requestJson['v'])) {
        $vData = base64_decode($requestJson['v']);
        $logEntry .= "  v decoded length: " . strlen($vData) . " bytes\n";
        $logEntry .= "  v hex: " . bin2hex($vData) . "\n";
    }
    if (isset($requestJson['p'])) {
        $pData = base64_decode($requestJson['p']);
        $logEntry .= "  p decoded length: " . strlen($pData) . " bytes\n";
    }
    file_put_contents(__DIR__ . '/server.log', $logEntry, FILE_APPEND);

    // Trả lỗi (encrypted nếu có AES key, plaintext nếu không)
    http_response_code(400);
    echo json_encode([
        'error' => $result['error'],
        'hint'  => 'Check server.log for details',
    ]);
    exit;
}

// Decrypt thành công
$requestData = $result['data'];
$aesKey      = $result['aes_key'];
$rawP        = $result['raw_p'] ?? '';

// Log request data
$logEntry = date('[Y-m-d H:i:s]') . " Decrypted request:\n";
$logEntry .= "  user_key : " . ($requestData['user_key'] ?? 'N/A') . "\n";
$logEntry .= "  call_app : " . ($requestData['call_app'] ?? 'N/A') . "\n";
$logEntry .= "  nonce    : " . ($requestData['nonce'] ?? 'N/A') . "\n";
file_put_contents(__DIR__ . '/server.log', $logEntry, FILE_APPEND);

// Build và encrypt response
$encryptedResponse = buildEncryptedResponse($requestData, $aesKey, $GAMES_CONFIG, $VALID_USERS, $rawP);

echo $encryptedResponse;
