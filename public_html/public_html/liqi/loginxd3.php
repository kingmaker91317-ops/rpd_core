<?php

// AES-256-CBC with static key and base64(IV + ciphertext)

define('ENCRYPTION_KEY', "HMod_ShellCache_Encryption_Key20");

header('Content-Type: application/json');

function pkcs7_unpad($data) {
    $len = strlen($data);
    if ($len === 0) {
        throw new Exception('empty payload');
    }
    $pad = ord($data[$len - 1]);
    if ($pad < 1 || $pad > 16) {
        throw new Exception('bad padding');
    }
    for ($i = 1; $i <= $pad; $i++) {
        if (ord($data[$len - $i]) !== $pad) {
            throw new Exception('bad padding');
        }
    }
    return substr($data, 0, $len - $pad);
}

function decrypt_encrypted_data($encryptedB64) {
    $raw = base64_decode($encryptedB64, true);
    if ($raw === false || strlen($raw) < 32 || (strlen($raw) % 16) !== 0) {
        throw new Exception('bad encrypted_data');
    }
    $iv = substr($raw, 0, 16);
    $ct = substr($raw, 16);
    $pt = openssl_decrypt($ct, 'AES-256-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
    if ($pt === false) {
        throw new Exception('decrypt failed');
    }
    return pkcs7_unpad($pt);
}

function encrypt_encrypted_data($plaintext) {
    $iv = random_bytes(16);
    $ct = openssl_encrypt($plaintext, 'AES-256-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($ct === false) {
        throw new Exception('encrypt failed');
    }
    return base64_encode($iv . $ct);
}

try {
    $body = trim((string)file_get_contents('php://input'));
    $encReq = '';
    $json = json_decode($body, true);
    if (is_array($json) && isset($json['encrypted_data'])) {
        $encReq = (string)$json['encrypted_data'];
    } else {
        // Fallback: client posts raw base64 without JSON wrapper
        $encReq = $body;
    }
    if ($encReq === '') {
        throw new Exception('missing encrypted_data');
    }

    $reqPlain = decrypt_encrypted_data($encReq);
    $reqObj = json_decode($reqPlain, true);
    if (!is_array($reqObj)) {
        throw new Exception('bad request payload');
    }

    $code = isset($reqObj['code']) ? (string)$reqObj['code'] : 'AOVVN';

    $respObj = [
        'status' => 'approved',
        'message' => 'Access granted (existing device)',
        'data' => [
            'code' => $code,
            'expires_at' => null,
        ],
    ];

    $respPlain = json_encode($respObj, JSON_UNESCAPED_SLASHES);
    $encResp = encrypt_encrypted_data($respPlain);

    // Match client expectation: raw base64 response without JSON wrapper
    echo $encResp;
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
