<?php

namespace App\Controllers;

use App\Models\KeysModel;

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

function writeLog($message) {
    $time = date('Y-m-d H:i:s');
    error_log("[$time] $message");
}

class Randiapi extends BaseController
{
    protected $model;
    private $encryptionKey = "kukushibu";
    private $hmacSecret = "hexblade_hmac_secret_v1_9988";

    public function __construct()
    {
        include('conn.php');
        $this->model = new KeysModel();
        writeLog("=== 🔥 DIRECT LOGIN API STARTED ===");
    }

    // =============================================
    // HMAC SIGNATURE GENERATOR (WITH DOMAIN BINDING)
    // =============================================
    private function generateSignature($token, $ts, $nonce) {
        $domain = "rapidcore.fun";
        $raw = $token . '|' . $ts . '|' . $nonce . '|' . $domain;
        return hash_hmac('sha256', $raw, $this->hmacSecret);
    }

    // =============================================
    // DYNAMIC ENCRYPTION (MATCHES C++ ALGORITHM)
    // =============================================
    private function getDynamicKey($nonce) {
        $raw = $nonce . "_hexblade_dyn_salt_8899";
        $h0 = 0x6a09e667; $h1 = 0xbb67ae85; $h2 = 0x3c6ef372; $h3 = 0xa54ff53a;
        $h4 = 0x510e527f; $h5 = 0x9b05688c; $h6 = 0x1f83d9ab; $h7 = 0x5be0cd19;

        for ($i = 0; $i < strlen($raw); $i++) {
            $c = ord($raw[$i]);
            $h0 = (($h0 ^ $c) * 0x01000193) & 0xFFFFFFFF;
            $h1 = (($h1 ^ ($c + $i)) * 0x01000193) & 0xFFFFFFFF;
            $h2 = (($h2 ^ ($c * 3)) * 0x01000193) & 0xFFFFFFFF;
            $h3 = (($h3 ^ ($c + 7)) * 0x01000193) & 0xFFFFFFFF;
            $h4 = (($h4 ^ ($c ^ 0xAA)) * 0x01000193) & 0xFFFFFFFF;
            $h5 = (($h5 ^ ($c + 13)) * 0x01000193) & 0xFFFFFFFF;
            $h6 = (($h6 ^ ($c * 5)) * 0x01000193) & 0xFFFFFFFF;
            $h7 = (($h7 ^ ($c ^ 0x55)) * 0x01000193) & 0xFFFFFFFF;
        }

        return sprintf("%08x%08x%08x%08x%08x%08x%08x%08x", $h0, $h1, $h2, $h3, $h4, $h5, $h6, $h7);
    }

    private function encryptData($data, $nonce = '') {
        $json = json_encode($data);
        $encrypted = "";
        
        if (!empty($nonce)) {
            $key = $this->getDynamicKey($nonce);
        } else {
            $key = $this->encryptionKey;
        }
        
        for($i = 0; $i < strlen($json); $i++) {
            $encrypted .= $json[$i] ^ $key[$i % strlen($key)];
        }
        
        return base64_encode($encrypted);
    }

    // =============================================
    // GENERATE UNIQUE TOKEN
    // =============================================
    private function generateUniqueToken($prefix = '') {
        return $prefix . md5(uniqid(mt_rand(), true) . microtime(true) . rand(10000, 99999));
    }

    public function index()
    {
        // Block direct access via /Randiapi or /randiapi URL
        $uri = service('request')->getUri()->getPath();
        if (strtolower(trim($uri, '/')) === 'randiapi') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        writeLog("=== 📡 REQUEST RECEIVED ===");
        
        header('Content-Type: text/plain');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Access-Control-Allow-Origin: *");
        
        // GET PARAMETERS
        $uKey  = $_GET['key'] ?? '';
        $sDev  = $_GET['device_id'] ?? '';
        $nonce = $_GET['nonce'] ?? '';

        writeLog("Key: " . substr($uKey, 0, 10) . "...");
        writeLog("Device: " . substr($sDev, 0, 10) . "...");
        writeLog("Nonce: " . $nonce);

        // =============================================
        // VALIDATE PARAMETERS
        // =============================================
        if (empty($uKey)) {
            echo $this->encryptData(['error' => '❌ Key required'], $nonce);
            return;
        }

        // Device ID is optional (if empty, treat as default device)
        if (empty($sDev)) {
            $sDev = 'default_device';
        }

        if (empty($nonce)) {
            echo $this->encryptData(['error' => '❌ Nonce required'], $nonce);
            return;
        }

        // =============================================
        // CHECK KEY IN DATABASE
        // =============================================
        $findKey = $this->model->getKeysGame(['user_key' => $uKey, 'game' => 'FreeFire']);
        if (!$findKey) {
            $findKey = $this->model->getKeysGame(['user_key' => $uKey]);
        }
        
        if (!$findKey) {
            writeLog("❌ Invalid key: " . $uKey);
            echo $this->encryptData(['error' => '❌ Invalid key'], $nonce);
            return;
        }

        // Check Key Status
        if (isset($findKey->status) && $findKey->status != 1) {
            writeLog("❌ Key locked: " . $uKey);
            echo $this->encryptData(['error' => '❌ Key Locked/Disabled'], $nonce);
            return;
        }

        // =============================================
        // CHECK & SET EXPIRY
        // =============================================
        $time = new \CodeIgniter\I18n\Time;
        $expired = $findKey->expired_date;

        if (!$expired) {
            $duration = $findKey->duration ?? 24;
            $setExpired = $time::now()->addHours($duration);
            $this->model->update($findKey->id_keys, ['expired_date' => $setExpired]);
            $expired = $setExpired;
            writeLog("✅ First use - Expire date set to: " . $setExpired);
        }
        
        if ($expired && !$time::now()->isBefore($expired)) {
            writeLog("❌ Key expired: " . $uKey);
            echo $this->encryptData(['error' => '❌ Key expired'], $nonce);
            return;
        }

        // =============================================
        // DEVICE CHECK
        // =============================================
        $devices = $findKey->devices ?? '';
        $max_dev = $findKey->max_devices ?? 1;
        $deviceArray = array_filter(array_map('trim', explode(',', $devices)));
        $isNewDevice = !in_array($sDev, $deviceArray);

        // Single device check
        if ($max_dev == 1) {
            $currentDevice = $devices ?? '';
            
            if (empty($currentDevice) || $currentDevice == 'null') {
                if (!empty($sDev)) {
                    $this->model->update($findKey->id_keys, ['devices' => $sDev]);
                    $currentDevice = $sDev;
                    writeLog("✅ First device added: " . $sDev);
                }
            } else {
                if (empty($sDev)) {
                    echo $this->encryptData(['error' => '❌ Device ID required'], $nonce);
                    return;
                }
                
                if ($sDev !== $currentDevice) {
                    writeLog("❌ Device mismatch: " . $sDev . " vs " . $currentDevice);
                    echo $this->encryptData(['error' => '❌ Device Not Match'], $nonce);
                    return;
                }
            }
        } else {
            // Multiple devices
            if ($isNewDevice) {
                if (count($deviceArray) >= $max_dev) {
                    writeLog("❌ Max devices reached: " . count($deviceArray) . "/" . $max_dev);
                    echo $this->encryptData(['error' => '❌ Max devices reached'], $nonce);
                    return;
                }
                
                $deviceArray[] = $sDev;
                $this->model->update($findKey->id_keys, ['devices' => implode(',', $deviceArray)]);
                writeLog("✅ New device added: " . $sDev);
            }
        }

        // =============================================
        // GENERATE TOKEN, ts & HMAC SIGNATURE
        // =============================================
        $token = $this->generateUniqueToken('TOKEN_');
        $ts = time(); // Current timestamp for session expiry
        $sig = $this->generateSignature($token, $ts, $nonce);

        // Resolve seller name
        $sellerName = 'RAPIDCORE';
        if (isset($findKey->registrator) && !empty($findKey->registrator)) {
            $sellerName = $findKey->registrator;
        } elseif (isset($findKey->reseller) && !empty($findKey->reseller)) {
            $sellerName = $findKey->reseller;
        } elseif (isset($findKey->created_by) && !empty($findKey->created_by)) {
            $sellerName = $findKey->created_by;
        }
        
        writeLog("✅ Login successful for: " . $uKey);
        writeLog("🎫 Token: " . $token);
        writeLog("⏰ ts: " . $ts);
        writeLog("🔐 Sig: " . $sig);
        writeLog("🏪 Seller: " . $sellerName);

        // Format expired_date as standard string (Y-m-d H:i:s)
        $expiredStr = (string)$expired;

        // =============================================
        // SUCCESS RESPONSE
        // =============================================
        $response = [
            'status'       => 'success',
            'token'        => $token,
            'message'      => '✅ Login successful',
            'expired_date' => $expiredStr,
            'verified_at'  => $ts,
            'user_key'     => $uKey,
            'device_id'    => $sDev,
            'ts'           => $ts,
            'nonce'        => $nonce,
            'sig'          => $sig,
            'seller_name'  => $sellerName
        ];

        echo $this->encryptData($response, $nonce);

    }
}
