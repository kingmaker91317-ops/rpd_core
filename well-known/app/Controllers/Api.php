<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\KeysModel;
use App\Models\UserModel;

class Api extends Controller
{
    public function index() {
        return $this->response->setJSON([
            'status' => 'active',
            'message' => 'TrinityX API System with Custom Key Support'
        ]);
    }

    private function validateSeller($key) {
        $userModel = new UserModel();
        // Master key 'ayushbabu' support
        if ($key === 'ayushbabu') {
            return $userModel->where('username', 'admin')->first() ?? $userModel->where('level', 1)->first();
        }
        return $userModel->where('seller_key', $key)->where('status', 1)->first();
    }

    public function createKey() {
        $seller_key = $this->request->getVar('seller_key');
        $dbUser = $this->validateSeller($seller_key);

        if (!$dbUser) return $this->response->setJSON(['error' => 'Invalid Seller Key'])->setStatusCode(401);

        $game = $this->request->getVar('game');
        $hours = $this->request->getVar('hours');
        
        // CHECK FOR CUSTOM KEY
        $custom_key = $this->request->getVar('custom_key');
        
        $keysModel = new KeysModel();
        
        // Agar custom_key di hai toh wo use karo, warna random generate karo
        if (!empty($custom_key)) {
            // Check if key already exists
            $exists = $keysModel->where('user_key', $custom_key)->first();
            if ($exists) {
                return $this->response->setJSON(['error' => 'This custom key already exists!'])->setStatusCode(400);
            }
            $keyString = $custom_key;
        } else {
            $keyString = 'KEY-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
        }

        $expired_date = (new \CodeIgniter\I18n\Time)::now()->addHours(intval($hours));

        $max_devices = (int)($this->request->getVar('max_devices') ?? 1);
        if (isset($dbUser['level']) && $dbUser['level'] == 2 && $max_devices > 10) {
            return $this->response->setJSON([
                'status' => 'error',
                'error' => 'Admin can set a maximum of 10 devices per key'
            ])->setStatusCode(400);
        }

        $data = [
            'game' => $game,
            'user_key' => $keyString,
            'duration' => $hours,
            'expired_date' => $expired_date,
            'max_devices' => $max_devices,
            'status' => 1,
            'registrator' => $dbUser['username']
        ];

        $keysModel->insert($data);
        return $this->response->setJSON([
            'status' => 'success',
            'key' => $keyString,
            'expires' => $expired_date->toDateTimeString()
        ]);
    }

    public function resetKey() {
        $dbUser = $this->validateSeller($this->request->getVar('seller_key'));
        if (!$dbUser) return $this->response->setStatusCode(401);

        $target = $this->request->getVar('target_key');
        $keysModel = new KeysModel();
        $keyData = $keysModel->where('user_key', $target)->first();

        if ($keyData && ($dbUser['level'] == 1 || $keyData['registrator'] == $dbUser['username'])) {
            $keysModel->set('devices', NULL)->where('user_key', $target)->update();
            return $this->response->setJSON(['status' => 'success', 'message' => 'Reset Successful']);
        }
        return $this->response->setJSON(['error' => 'No Permission or Key Not Found']);
    }

    public function deleteKey() {
        $dbUser = $this->validateSeller($this->request->getVar('seller_key'));
        if (!$dbUser) return $this->response->setStatusCode(401);

        $target = $this->request->getVar('target_key');
        $keysModel = new KeysModel();
        
        if ($keysModel->where('user_key', $target)->delete()) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Key Deleted']);
        }
        return $this->response->setJSON(['error' => 'Delete Failed']);
    }
}