<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\KeysModel;

class Login extends Controller
{
    public function index()
    {
        // Support JSON input or POST parameters
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        
        $key = trim($json['user_key'] ?? $json['key'] ?? $json['game_key'] ?? $this->request->getVar('user_key') ?? $this->request->getVar('key') ?? '');
        $hwid = trim($json['hwid'] ?? $json['device_id'] ?? $json['serial'] ?? $this->request->getVar('hwid') ?? $this->request->getVar('device_id') ?? '');
        $game = trim($json['game'] ?? $this->request->getVar('game') ?? 'Freefire');

        if (empty($key)) {
            return $this->response->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'License Key is required'
            ])->setStatusCode(400);
        }

        if (empty($hwid)) {
            return $this->response->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'Hardware ID (HWID) is required'
            ])->setStatusCode(400);
        }

        $keysModel = new KeysModel();
        $keyData = $keysModel->where('user_key', $key)->first();

        if (!$keyData) {
            return $this->response->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'Invalid License Key'
            ])->setStatusCode(400);
        }

        // Check if key is blocked / inactive
        if (isset($keyData['status']) && (int)$keyData['status'] !== 1) {
            return $this->response->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'License Key is blocked or inactive'
            ])->setStatusCode(403);
        }

        $now = \CodeIgniter\I18n\Time::now();

        // 1. FIRST TIME ACTIVATION
        if (empty($keyData['expired_date']) || $keyData['expired_date'] === '0000-00-00 00:00:00') {
            $durationHours = (int)($keyData['duration'] ?? 24);
            if ($durationHours <= 0) {
                $durationHours = 24;
            }
            $expiredTime = $now->addHours($durationHours);
            $expiredDateStr = $expiredTime->toDateTimeString();

            $keysModel->update($keyData['id_keys'], [
                'expired_date' => $expiredDateStr
            ]);
            $keyData['expired_date'] = $expiredDateStr;
        }

        // 2. EXPIRATION CHECK
        $expiredTime = \CodeIgniter\I18n\Time::parse($keyData['expired_date']);
        if ($now->getTimestamp() > $expiredTime->getTimestamp()) {
            return $this->response->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'License Key has expired on ' . $keyData['expired_date']
            ])->setStatusCode(403);
        }

        // 3. HWID / DEVICE BINDING
        $registeredDevices = [];
        if (!empty($keyData['devices'])) {
            $registeredDevices = array_filter(array_map('trim', explode(',', $keyData['devices'])));
        }

        $maxDevices = (int)($keyData['max_devices'] ?? 1);
        if ($maxDevices <= 0) {
            $maxDevices = 1;
        }

        if (!in_array($hwid, $registeredDevices)) {
            if (count($registeredDevices) >= $maxDevices) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Device limit reached (' . count($registeredDevices) . '/' . $maxDevices . ')'
                ])->setStatusCode(403);
            }
            // Bind new device
            $registeredDevices[] = $hwid;
            $newDevicesStr = implode(',', array_unique($registeredDevices));
            $keysModel->update($keyData['id_keys'], [
                'devices' => $newDevicesStr
            ]);
            $keyData['devices'] = $newDevicesStr;
        }

        $remainingSeconds = max(0, $expiredTime->getTimestamp() - $now->getTimestamp());
        $token = hash_hmac('sha256', $key . $hwid . $remainingSeconds, 'GreedClientSecretKey2026!');

        return $this->response->setJSON([
            'status' => 'success',
            'success' => true,
            'message' => 'Login Successful',
            'data' => [
                'user_key' => $keyData['user_key'],
                'game' => $keyData['game'] ?? 'Freefire',
                'expired_date' => $keyData['expired_date'],
                'remaining_seconds' => $remainingSeconds,
                'max_devices' => $maxDevices,
                'used_devices' => count($registeredDevices),
                'registrator' => $keyData['registrator'] ?? 'Admin',
                'auth_token' => $token
            ]
        ]);
    }
}
