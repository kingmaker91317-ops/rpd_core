<?php

namespace App\Controllers;

use App\Models\KeysModel;

// ================= CONFIG LOG =================
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

function writeLog($message)
{
    $time = date('Y-m-d H:i:s');
    error_log("[$time] $message");
}

class Connect extends BaseController
{
    protected $model, $game, $uKey, $sDev;
    protected $FIXED_TOKEN = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9EVm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
    protected $APP_SIGNATURE = "ACD0B6797D4D96DA551F793B2FB2D661DC0EC755C0DAEA0F82832746F694C8B8";

    public function __construct()
    {
        include('conn.php');
        //=================================================
        $sql1 = "select * from onoff where id=1";
        $result1 = mysqli_query($conn, $sql1);
        $userDetails1 = mysqli_fetch_assoc($result1);
        //=================================================
        $this->model = new KeysModel();
        //=================================================
        if ($userDetails1['status'] == 'on') {
            $this->maintenance = true;
        } else {
            $this->maintenance = false;
        }
        //=================================================
        $this->staticWords = "Vm8Lk7Uj2JmsjCPVPVjrLa7zgfx3uz9E";
    }

    public function index()
    {
        // ================= DETECT BROWSER =================
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isBrowser = preg_match('/(Mozilla|Chrome|Safari|Firefox|Edge|Opera)/i', $userAgent);

        if ($isBrowser && !isset($_SERVER['HTTP_X_API_CLIENT'])) {
            // Chỉ hiển thị lời chào cho browser
            header('Content-Type: text/html; charset=utf-8');
            echo 'Chào em nha';
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        header("Cache-Control: no-store, no-cache, must-revalidate");

        // Check if there's any body content (for text/plain raw requests)
        $rawBody = $this->request->getBody();
        if (!empty($rawBody) || $this->request->getPost() || $this->request->getJSON()) {
            return $this->index_post();
        } else {
            return $this->response->setJSON([
                "status" => false,
                "message" => "No data provided"
            ]);
        }
    }

    // Helper function to return RC4-encrypted error responses
    private function returnError($message, $status = false, $sKey = null, $rKey = null, $ts = null, $nc = null)
    {
        $errorData = [
            "status" => $status,
            "message" => $message,
            "reason" => $message // For Auth.h compatibility
        ];

        $jsonStr = json_encode($errorData);

        if ($sKey && $rKey && $ts && $nc) {
            $encXXTEA = $this->xxtea_encrypt_raw($jsonStr, $sKey);
            $rawMac = hash_hmac('sha256', $encXXTEA, $sKey, true);
            $payloadToEncrypt = $encXXTEA . $rawMac;
            $rc4Encrypted = $this->rc4($payloadToEncrypt, $rKey);
            $finalResponse = base64_encode($rc4Encrypted) . "." . $ts . "." . $nc;
        } else {
            $ts = time();
            $nc = "err_nonce_" . bin2hex(random_bytes(4));
            $sKey = substr(hash('sha256', $this->FIXED_TOKEN . $this->APP_SIGNATURE . $ts . $nc), 0, 16);
            $rKey = substr(hash('sha256', $nc . $ts . $this->FIXED_TOKEN . $this->APP_SIGNATURE), 0, 16);

            $encXXTEA = $this->xxtea_encrypt_raw($jsonStr, $sKey);
            $rawMac = hash_hmac('sha256', $encXXTEA, $sKey, true);
            $payloadToEncrypt = $encXXTEA . $rawMac;
            $rc4Encrypted = $this->rc4($payloadToEncrypt, $rKey);
            $finalResponse = base64_encode($rc4Encrypted) . "." . $ts . "." . $nc;
        }

        return $this->response
            ->setContentType('text/plain')
            ->setBody($finalResponse);
    }

    public function index_post()
    {
        $isMT = $this->maintenance;

        // Get raw input
        $rawInput = $this->request->getBody();
        if (empty($rawInput)) {
            return $this->returnError("No data provided");
        }

        $clientHeader = $this->request->getHeaderLine('X-API-Client');
        if ($clientHeader !== 'NativeApp') {
            return $this->returnError("Yêu cầu kết nối an toàn");
        }

        // Split raw input by '.'
        $parts = explode('.', $rawInput);
        if (count($parts) !== 3) {
            return $this->returnError("Invalid request format");
        }

        $base64Ciphertext = $parts[0];
        $timestamp = $parts[1];
        $nonce = $parts[2];

        // Re-generate keys using timestamp + nonce + FIXED_TOKEN + APP_SIGNATURE
        $sessionKey = substr(hash('sha256', $this->FIXED_TOKEN . $this->APP_SIGNATURE . $timestamp . $nonce), 0, 16);
        $rc4Key = substr(hash('sha256', $nonce . $timestamp . $this->FIXED_TOKEN . $this->APP_SIGNATURE), 0, 16);

        if (abs(time() - intval($timestamp)) > 300) {
            return $this->returnError("Request Expired", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        $payloadToDecrypt = base64_decode($base64Ciphertext);
        if (empty($payloadToDecrypt)) {
            return $this->returnError("Invalid payload", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        // Decrypt using RC4
        $decryptedPayload = $this->rc4($payloadToDecrypt, $rc4Key);
        $len = strlen($decryptedPayload);
        if ($len < 32) {
            return $this->returnError("Corrupted payload", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        $encryptedData = substr($decryptedPayload, 0, $len - 32);
        $mac = substr($decryptedPayload, $len - 32);

        $expectedMac = hash_hmac('sha256', $encryptedData, $sessionKey, true);
        if (!hash_equals($expectedMac, $mac)) {
            return $this->returnError("Invalid MAC", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        $decryptedJson = $this->xxtea_decrypt_raw($encryptedData, $sessionKey);
        $requestData = json_decode($decryptedJson, true);
        if (!$requestData) {
            return $this->returnError("Decryption Failed", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        // Map variables
        $game = $requestData['game'] ?? null;
        $uKey = $requestData['key'] ?? null;
        $sDev = $requestData['hwid'] ?? null;
        $publicKey = $requestData['publicKey'] ?? null;

        // ================= VALIDATE REQUEST =================
        if (empty($game) || empty($uKey) || empty($sDev) || empty($publicKey)) {
            return $this->returnError("Bad Parameter", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        if (strlen($uKey) > 36 || strlen($uKey) < 1) {
            return $this->returnError("Bad Parameter", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $game)) {
            return $this->returnError("Bad Parameter", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        // ================= VALIDATE TOKEN =================
        if ($publicKey !== $this->FIXED_TOKEN) {
            return $this->returnError("Invalid public key", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        if ($isMT) {
            return $this->returnError("Maintenance Mode Active", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        $model = $this->model;
        // Strictly query user key without guest key bypasses
        $findKey = $model->getKeysGame(['user_key' => $uKey, 'game' => $game]);

        if (!$findKey) {
            return $this->returnError("INVALID KEY", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        if ((int)$findKey->status !== 1) {
            return $this->returnError("KEY LOCKED", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        }

        $id_keys = $findKey->id_keys;
        $duration = (int)$findKey->duration;
        $expired = $findKey->expired_date;
        $now = date("Y-m-d H:i:s");

        if (empty($expired)) {
            $setExpired = date("Y-m-d H:i:s", strtotime("+{$duration} hours"));
            $model->update($id_keys, [
                'expired_date' => $setExpired,
                'devices' => $sDev
            ]);
            $expired = $setExpired;
        } else {
            if ($expired < $now) {
                return $this->returnError("KEY EXPIRED", false, $sessionKey, $rc4Key, $timestamp, $nonce);
            }
        }

        // Check HWID / Device Registration Limit
        $maxDevices = (int)($findKey->max_devices ?? 1);
        $registeredDevices = $findKey->devices ?? '';
        $deviceCheck = $this->checkDevicesAdd($sDev, $registeredDevices, $maxDevices);

        if ($deviceCheck === false) {
            return $this->returnError("MAX DEVICE REACHED", false, $sessionKey, $rc4Key, $timestamp, $nonce);
        } elseif (is_array($deviceCheck)) {
            $model->update($id_keys, ['devices' => $deviceCheck['devices']]);
        }

        $responseData = [
            'status' => true,
            'message' => 'LOGIN SUCCESSFUL',
            'data' => [
                'game' => $game,
                'user_key' => $uKey,
                'expired_date' => $expired,
                'max_devices' => $maxDevices,
                'modname' => 'SecureLoader'
            ]
        ];

        // Build encrypted response matching NativeApp HMAC pattern
        $jsonResponse = json_encode($responseData);
        $encryptedResponse = $this->xxtea_encrypt_raw($jsonResponse, $sessionKey);

        $responseMac = hash_hmac('sha256', $encryptedResponse, $sessionKey, true);
        $payloadToEncrypt = $encryptedResponse . $responseMac;

        $rc4Encrypted = $this->rc4($payloadToEncrypt, $rc4Key);

        $finalResponse = base64_encode($rc4Encrypted) . "." . $timestamp . "." . $nonce;

        return $this->response
            ->setContentType('text/plain')
            ->setBody($finalResponse);
    }

    // ================= XXTEA PHP IMPLEMENTATION Start =================
    private function long2str($v, $w)
    {
        $len = count($v);
        $n = ($len - 1) << 2;
        if ($w) {
            $m = $v[$len - 1];
            if (($m < $n - 3) || ($m > $n))
                return false;
            $n = $m;
        }
        $s = [];
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(implode('', $s), 0, $n);
        } else {
            return implode('', $s);
        }
    }

    private function str2long($s, $w)
    {
        $v = array_values(unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3)));
        if ($w) {
            $v[] = strlen($s);
        }
        return $v;
    }

    private function int32($n)
    {
        while ($n >= 2147483648)
            $n -= 4294967296;
        while ($n <= -2147483649)
            $n += 4294967296;
        return (int) $n;
    }

    private function xxtea_encrypt($str, $key)
    {
        if ($str == "")
            return "";
        $v = $this->str2long($str, true);
        $k = $this->str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++)
                $k[$i] = 0;
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        while (0 < $q--) {
            $sum = $this->int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$p & 3 ^ $e] ^ $z)));
                $z = $v[$p] = $this->int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$n & 3 ^ $e] ^ $z)));
            $z = $v[$n] = $this->int32($v[$n] + $mx);
        }
        return base64_encode($this->long2str($v, false));
    }

    private function xxtea_decrypt($str, $key)
    {
        if ($str == "")
            return "";
        $str = base64_decode($str);
        if (!$str)
            return "";
        $v = $this->str2long($str, false);
        $k = $this->str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++)
                $k[$i] = 0;
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = $this->int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$p & 3 ^ $e] ^ $z)));
                $y = $v[$p] = $this->int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[0 & 3 ^ $e] ^ $z)));
            $y = $v[0] = $this->int32($v[0] - $mx);
            $sum = $this->int32($sum - $delta);
        }
        return $this->long2str($v, true);
    }

    private function xxtea_encrypt_raw($str, $key)
    {
        if ($str == "")
            return "";
        $v = $this->str2long($str, true);
        $k = $this->str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++)
                $k[$i] = 0;
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        while (0 < $q--) {
            $sum = $this->int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$p & 3 ^ $e] ^ $z)));
                $z = $v[$p] = $this->int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$n & 3 ^ $e] ^ $z)));
            $z = $v[$n] = $this->int32($v[$n] + $mx);
        }
        return $this->long2str($v, false);
    }

    private function xxtea_decrypt_raw($str, $key)
    {
        if ($str == "")
            return "";
        $v = $this->str2long($str, false);
        $k = $this->str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++)
                $k[$i] = 0;
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = $this->int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[$p & 3 ^ $e] ^ $z)));
                $y = $v[$p] = $this->int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = $this->int32((($z >> 5 & 0x07ffffff) ^ ($y << 2)) + (($y >> 3 & 0x1fffffff) ^ ($z << 4)) ^ ($this->int32($sum ^ $y) + $this->int32($k[0 & 3 ^ $e] ^ $z)));
            $y = $v[0] = $this->int32($v[0] - $mx);
            $sum = $this->int32($sum - $delta);
        }
        return $this->long2str($v, true);
    }
    // ================= XXTEA PHP IMPLEMENTATION END =================

    // ================= RC4 PHP IMPLEMENTATION START =================
    private function rc4($data, $key)
    {
        $S = range(0, 255);
        $j = 0;
        $keyLen = strlen($key);

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $S[$i] + ord($key[$i % $keyLen])) % 256;
            $temp = $S[$i];
            $S[$i] = $S[$j];
            $S[$j] = $temp;
        }

        $i = 0;
        $k = 0;
        $result = '';
        $dataLen = strlen($data);

        for ($n = 0; $n < $dataLen; $n++) {
            $i = ($i + 1) % 256;
            $k = ($k + $S[$i]) % 256;
            $temp = $S[$i];
            $S[$i] = $S[$k];
            $S[$k] = $temp;
            $result .= chr(ord($data[$n]) ^ $S[($S[$i] + $S[$k]) % 256]);
        }

        return $result;
    }
    // ================= RC4 PHP IMPLEMENTATION END =================

    private function generateSessionKey($fixed, $ts)
    {
        $combined = $fixed . $ts;
        $key = ""; // Same logic as C++
        for ($i = 0; $i < 16; $i++) {
            if ($i < strlen($combined)) {
                $key .= $combined[$i];
            } else {
                $key .= chr($i * 17);
            }
        }
        return $key;
    }

    private function hmacBase64($key, $data)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }

    private function checkDevicesAdd($currentDevice, $registeredDevices, $maxDevices)
    {
        if (empty($currentDevice)) {
            return false;
        }

        // Parse registered devices (comma-separated)
        $deviceArray = array_filter(array_map('trim', explode(',', $registeredDevices)));

        // If current device already registered, allow login
        if (in_array($currentDevice, $deviceArray)) {
            return true; // No update needed
        }

        // Check if we can add a new device
        if (count($deviceArray) < $maxDevices) {
            $deviceArray[] = $currentDevice;
            return [
                'devices' => implode(',', $deviceArray)
            ];
        }

        // Maximum devices reached
        return false;
    }

    private function buildLegacyPayload($responseData)
    {
        $privateKeyPEM = "-----BEGIN RSA PRIVATE KEY-----\n" .
            "MIIEowIBAAKCAQEAl3744OVDqiHpNtePuba8sCJT29h7i5PMwiIx3fqa7ZLBsuTf\n" .
            "9zGxELp0sJaIR6pKacbqEVhUBNtficvlZoe2mlbppafu/oipdx1ErBBLOICd7jMh\n" .
            "0sIs6TEbifCR9C7G2bWBAUx3FyBakArmrnX9KCsuxhZ5dRDw4KQEeEfxR4o+9GeZ\n" .
            "pqaqmpKRNYBHZOc6Nr8R18zQRti5aBs5pne6PFUJ1Cl+BrklwDRL+zRZNiHDmq/l\n" .
            "Bslq7rSWdZiT0XxKCz0E7GnpFIOHq8uRDomTVjrLqJuea0NJ3VFFOB/d1poB/xVV\n" .
            "QBI3Bku0K6hPK6TiYlPgwYJxJWA5DObOzem86wIDAQABAoIBADahWfw3kKP8YI4f\n" .
            "Q7vzsq1NY7Imqb6WiMME87iZk17SijkJod4RNEnVAxle3zwAo57rVSL2GC48MYKO\n" .
            "XWYQ0H9tkgnjuiJdg8bpbgciRQ3WC52HIM5QqUNaKxUeBHPqnliJxECEo3laeG3z\n" .
            "EGafM7BViiNynU/i0Qog+1+oidCC8s9t0b1c8lM/VyNfejLJ60VRop+3TN82KMKX\n" .
            "ARvMEq6ZI1AmmHs0z66Z8QUUKrP7GyaLdTpU1KcE/bwmgvsOLPOZAIkOeZywqzrp\n" .
            "JCIEXgrBiANWsvlHQ+VUgDjZqjzbc9GvfdUtQhE5XcqXZpb/B1yfNYaAYC2trAif\n" .
            "pctt6gkCgYEAx3DtfMl9xafO0hDxoDuTxn+mTyyC8/n+P+Y65xxIWGgDOYCyuZug\n" .
            "2Lvih3H341BOcqASSAYtardnQ32SATMm5ateocnQe8cHcYulm7CgKspqWbVdYN/Y\n" .
            "HdH6R2SJqNG6XEY3RtQXg4uJH2uvvO16ETtZq4s6RWIp09nNayb4iJcCgYEAwnVP\n" .
            "9l/uSLj9HEKs26xwYmydYYr0c8YRDxfqyinDxM7JRgFQC7U6ZC7GeqjU/h+QUSq5\n" .
            "6OVVPo6+i8sqmX2pX/qhY5mtxgzNTBBv+aTWF/7pg3pu+LooUCnh9AfBogTYL/ky\n" .
            "d4gDKJJTlQr2A0udQakRNs654ZURhnZh0gWOBM0CgYBU7RaD070V5K4iL4rkg1oa\n" .
            "5ZJpFngw8hw9E4mzjgyUcL4mx1HTzZyBjggZSwOWrUSqdNU0DEVcsvpq99arYh/H\n" .
            "HuEuHaUEgC0AQcnkcSLS9dyxlJRNwyPhFt/vdLVVyPEFh/TT2U6l+k4Kri1oUutu\n" .
            "2QoHDUNj9jf/eTiKz053wwKBgGCxjM1p7kbjFZkT3nhtSoTmlWuHeP6Iorrqnalh\n" .
            "EyOt34+b332y5BIk4DTl9uNWNqDlqgQQ5U5yFHXW1Jv2TF6Zdd7c7/fnLb6A/e0Y\n" .
            "9gyAiQUDwGKVzq3t5Zk+sh4qYoWYWGvvigKGoGEgTixdURjxRMoICY4OXeSKwSF5\n" .
            "+aCRAoGBAIcjyjCU/XWSaAfqMuyYIpcJbS09OayxRNjr2sPqLNUBei2Dh9yHAxlI\n" .
            "q/wr9e0Td8/0ZkFe8z3o4wGPSvQ622YLnHCshtsEpIO9vBq+WstGUtMdHB2KJJnr\n" .
            "o3EnUTljqphLruVz+0G1sviLd6N9m1ApqEp1dp/QPZUbOx7auJEg\n" .
            "-----END RSA PRIVATE KEY-----";

        // Map root elements required by Auth.h
        $mapped = [
            "status" => (bool)($responseData['status'] ?? false),
            "maintenance" => (bool)($responseData['maintenance'] ?? false),
            "reason" => $responseData['message'] ?? ($responseData['reason'] ?? ''),
            "message" => $responseData['message'] ?? ($responseData['reason'] ?? ''),
            "data" => $responseData['data'] ?? null
        ];

        $jsonStr = json_encode($mapped);

        // Rolling XOR encryption ("RapidCoreSecurity")
        $xorKey = [0x52, 0x61, 0x70, 0x69, 0x64, 0x43, 0x6F, 0x72, 0x65, 0x53, 0x65, 0x63, 0x75, 0x72, 0x69, 0x74, 0x79];
        $encryptedBytes = "";
        $keyLen = count($xorKey);
        for ($i = 0; $i < strlen($jsonStr); $i++) {
            $encryptedBytes .= chr(ord($jsonStr[$i]) ^ $xorKey[$i % $keyLen]);
        }

        // SHA-256 with RSA signature
        $signature = "";
        if (!openssl_sign($encryptedBytes, $signature, $privateKeyPEM, OPENSSL_ALGO_SHA256)) {
            return "Cryptography Error";
        }

        return base64_encode($encryptedBytes) . "." . base64_encode($signature);
    }
}
