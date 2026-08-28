<?php

namespace App\Controllers;

use Exception;
use CodeIgniter\Controller;
use CodeIgniter\I18n\Time;

class VerifyLib extends BaseController
{
    private const STATIC_KEY = "Kz7YhPm2VxNj9WqL4Rc3Bs8tAg5vEuXy";

    private $keysModel;
    private $cipher;
    private $db;
    private $keyColumn;

    public function __construct()
    {
        $this->keysModel = new \App\Models\KeysModel();
        $this->keyColumn = $this->keysModel->getKeyColumn();
        $this->cipher = new AESCipher(self::STATIC_KEY); 
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        if ($this->request->getMethod() === 'get') {
            return $this->response->setJSON([
                'status' => true,
                'author' => 'Khánh Mods',
                'telegram' => 'https://t.me/khanhmodsxd', 
                'youtube' => 'null',
                'AES' => $this->cipher->encrypt('KhanhMods@123'),
                'message' => 'Welcome to Verify API!'
            ]);
        }

        if ($this->request->getMethod() === 'post') {
            return $this->handlePost();
        }

        return $this->returnError('INVALID REQUEST METHOD');
    }

    private function handlePost() {
        try {
            // Lấy dữ liệu đã mã hóa từ client
            $encrypted_data = $this->request->getPost('data');
            if (!$encrypted_data) {
                return $this->returnError('MISSING ENCRYPTED DATA');
            }

            // Giải mã dữ liệu
            $decrypted_data = $this->cipher->decrypt($encrypted_data);
            $data = json_decode($decrypted_data, true);
            
            if (!$data || !isset($data['id']) || !isset($data['game']) || !isset($data['key']) || !isset($data['device'])) {
                return $this->returnError('INVALID DATA FORMAT');
            }

            $id = $data['id'];
            $game = $data['game'];
            $key = $data['key'];
            $device = $data['device'];

            // Validate key và game
            $keyStatus = $this->validateKey($id, $game, $key, $device);
            if (!$keyStatus['status']) {
                $this->logActivity($key, $keyStatus['message']);
                return $this->returnError($keyStatus['message']); 
            }

            // Tạo token từ các thành phần
            $timestamp = time();
            $token = $this->generateToken($key, $device, $game, $id, $timestamp);
            
            log_message('info', 'Data before encryption: ' . json_encode([
            'key' => $key,
            'game_id' => $id,
            'game_package' => $game,
            'device' => $device,
            'exp' => $keyStatus['data']['expired_date'],
            'timestamp' => $timestamp
        ]));

            // Encrypt các thông tin nhạy cảm
            $encrypted_data = $this->cipher->encrypt(json_encode([
                'key' => $key,
                'game_id' => $id,
                'game_package' => $game,
                'device' => $device,
                'exp' => $keyStatus['data']['expired_date'],
                'timestamp' => $timestamp
            ]));

            return $this->response->setJSON([
                'status' => true,
                'message' => 'USER VERIFIED SUCCESSFULLY',
                'encrypted_data' => $encrypted_data,
                'token' => $token
            ]);

        } catch (Exception $e) {
            log_message('error', 'Verification error: ' . $e->getMessage());
            return $this->returnError('PROCESSING ERROR');
        }
    }

    private function generateToken($key, $device, $game, $id, $timestamp): string 
    {
        $data = $key . $device . $game . $id . $timestamp . self::STATIC_KEY;
        return sha1($data);
    }

    private function validateKey(string $id, string $game, string $key, string $device): array
{
    // Get key data
    $keyData = $this->keysModel->getKeysGame(['username' => $key]);
    if (!$keyData) {
        return ['status' => false, 'message' => 'USER KEY NOT FOUND'];
    }

    // Check key status
    if ($keyData->status != 1) {
        return ['status' => false, 'message' => 'KEY HAS BEEN BLOCKED'];
    }

    // Key DEV có full quyền, key ALL chỉ dùng được mọi game
    $isDEVKey = ($keyData->game === 'DEV');
    $isALLKey = ($keyData->game === 'ALL');

    // Validate game nếu không phải key DEV hoặc ALL
    if (!$isDEVKey && !$isALLKey && $keyData->game !== $game) {
        return ['status' => false, 'message' => 'INVALID GAME FOR THIS KEY'];
    }

    // Check game exists
    $gameData = $this->db->table('games')
        ->where(['id' => $id, 'package' => $game])
        ->get()
        ->getRow();

    if (!$gameData) {
        return ['status' => false, 'message' => 'GAME ID OR PACKAGE IS INVALID'];
    }

    // Check expiry
    $expiryStatus = $this->checkKeyExpiry($keyData);
    if (!$expiryStatus['status']) {
        return $expiryStatus;
    }

    // Check devices 
    $deviceStatus = $this->validateDevice($keyData, $device);
    if (!$deviceStatus['status']) {
        return $deviceStatus;
    }

    // Thêm thông tin loại key vào response
    return [
        'status' => true,
        'data' => [
            'expired_date' => $keyData->expired_date,
        ]
    ];
}

    private function checkKeyExpiry($key): array
    {
        $now = Time::now();
        
        if (!$key->expired_date) {
            if (!$key->duration) {
                return ['status' => false, 'message' => 'INVALID KEY DURATION'];
            }
            
            $expiredDate = $now->addDays($key->duration);
            $this->keysModel->update($key->{$this->keyColumn}, ['expired_date' => $expiredDate]);
            
            return ['status' => true, 'data' => ['expired_date' => $expiredDate]];
        }

        $expiredDate = new Time($key->expired_date);
        if ($now->isAfter($expiredDate)) {
            return ['status' => false, 'message' => 'KEY HAS EXPIRED'];
        }

        return ['status' => true, 'data' => ['expired_date' => $key->expired_date]];
    }

    private function validateDevice($key, $device): array
    {
        $devices = $key->devices ? explode(",", $key->devices) : [];

        if (in_array($device, $devices)) {
            return ['status' => true];
        }

        if (count($devices) >= $key->max_devices) {
            return ['status' => false, 'message' => 'MAXIMUM DEVICES REACHED']; 
        }

        $devices[] = $device;
        $devicesString = implode(",", array_unique($devices));
        $this->keysModel->update($key->{$this->keyColumn}, [
            'devices' => $devicesString,
            'UID'     => $devicesString,
        ]);

        return ['status' => true];
    }

    private function returnError(string $message)
    {
        return $this->response->setJSON([
            'status' => false,
            'message' => $message,
        ]);
    }
}

class AESCipher
{
    private $key;
    private $cipher;
    private $iv_length;

    public function __construct($key)
    {
        $this->key = $key;
        $this->cipher = $this->getSupportedCipher();
        $this->iv_length = openssl_cipher_iv_length($this->cipher);
    }

    private function getSupportedCipher()
    {
        $key_length = strlen($this->key);
        switch ($key_length) {
            case 16:
                return 'AES-128-CBC';
            case 24:
                return 'AES-192-CBC';
            case 32:
                return 'AES-256-CBC';
            default:
                throw new Exception('Unsupported key size');
        }
    }

    public function encrypt($plaintext)
    {
        $iv = openssl_random_pseudo_bytes($this->iv_length);
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key,  OPENSSL_RAW_DATA, $iv);
        $encrypted = base64_encode($iv . $ciphertext);
        return $encrypted;
    }

    public function decrypt($ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        $iv = substr($ciphertext, 0, $this->iv_length);
        $ciphertext = substr($ciphertext, $this->iv_length);
        $plaintext = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        return $plaintext;
    }
}

class Encryption {
    private $key;

    function __construct($key = null) {
        $this->key = $key ?? $this->generateKey();
    }

    function generateKey() {
        $keyLength = rand(8, 16);  
        $key = '';
        for ($i = 0; $i < $keyLength; $i++) {
            $key .= chr(rand(32, 126));  
        }
        return $key;
    }

    function encrypt($text) {
        $keyLength = strlen($this->key);
        $result = '';
        $result .= chr($keyLength);
        $result .= $this->key;
        for ($i = 0; $i < strlen($text); $i++) {
            $keyChar = $this->key[$i % $keyLength];
            $textChar = $text[$i];
            $result .= chr(ord($textChar) + ord($keyChar));
        }
        return $result;
    }

    function decrypt($encryptedText) {
        $keyLength = ord($encryptedText[0]);
        $key = substr($encryptedText, 1, $keyLength);
        $result = '';
        for ($i = $keyLength + 1; $i < strlen($encryptedText); $i++) {
            $keyChar = $key[($i - $keyLength - 1) % $keyLength];
            $encryptedChar = $encryptedText[$i];
            $result .= chr(ord($encryptedChar) - ord($keyChar));
        }
        return $result;
    }
}
