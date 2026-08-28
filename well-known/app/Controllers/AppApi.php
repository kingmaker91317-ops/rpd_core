<?php

namespace App\Controllers;

use App\Models\HistoryModel;
use App\Models\KeysModel;
use App\Models\UserModel;
use Config\Services;

class AppApi extends BaseController
{
    protected $userModel, $keysModel;
    protected $game_list, $duration, $price;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->keysModel = new KeysModel();
        
        $this->game_list = [
            'Freefire' => 'Rapid Core',
        ];

        $this->duration = [
            24 => '1 Day',
            168 => '7 Days',
            336 => '14 Days',
            720 => '30 Days',
        ];

        $this->price = [
            24 => 0.5,
            168 => 1,
            336 => 2,
            720 => 4,
            1440 => 8,
        ];
    }

    public function index()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Rapid Core App API is running'
        ]);
    }

    private function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        $user = $this->userModel->getUser($username, 'username');
        if (!$user || !password_verify(create_password($password, false), $user->password)) {
            return false;
        }
        return $user;
    }

    public function login()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = $json['username'] ?? '';
        $password = $json['password'] ?? '';

        $user = $this->authenticate($username, $password);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid username or password'
            ])->setStatusCode(401);
        }

        return $this->response->setJSON([
            'success' => true,
            'user' => [
                'username' => $user->username,
                'level' => getLevel($user->level),
                'saldo' => (float)$user->saldo
            ]
        ]);
    }

    public function get_config()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = $json['username'] ?? '';
        $password = $json['password'] ?? '';

        $user = $this->authenticate($username, $password);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        return $this->response->setJSON([
            'success' => true,
            'level' => getLevel($user->level),
            'saldo' => (float)$user->saldo,
            'games' => $this->game_list,
            'durations' => $this->duration,
            'prices' => $this->price
        ]);
    }

    public function generate()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = $json['username'] ?? '';
        $password = $json['password'] ?? '';

        $user = $this->authenticate($username, $password);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $game = $json['game'] ?? '';
        $maxd = (int)($json['max_devices'] ?? 1);
        $drtn = (int)($json['duration'] ?? 24);
        $method = $json['key_method'] ?? 'random';
        $bulkCount = ($method == 'custom') ? 1 : (int)($json['bulk_count'] ?? 1);
        $prefix = $json['prefix'] ?? 'KEY';
        $cuslicense = $json['cuslicense'] ?? '';

        if (!isset($this->game_list[$game])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid game selected'
            ])->setStatusCode(400);
        }

        if ($user->level == 2 && $maxd > 10) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Admin can set a maximum of 10 devices per key'
            ])->setStatusCode(400);
        }

        if (!isset($this->price[$drtn])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid duration selected'
            ])->setStatusCode(400);
        }

        $singlePrice = getPrice($this->price, $drtn, $maxd);
        $totalCost = $singlePrice * $bulkCount;

        if ($user->saldo < $totalCost) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Insufficient balance. Cost: ₹' . number_format($totalCost, 2) . ', Balance: ₹' . number_format($user->saldo, 2)
            ])->setStatusCode(400);
        }

        if ($method == 'custom') {
            if (empty($cuslicense) || strlen($cuslicense) < 4 || strlen($cuslicense) > 19) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Custom key must be between 4 and 19 characters'
                ])->setStatusCode(400);
            }
            $exists = $this->keysModel->where('user_key', $cuslicense)->first();
            if ($exists) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This Custom Key has already been taken'
                ])->setStatusCode(400);
            }
        }

        $generatedKeys = [];
        $history = new HistoryModel();

        for ($i = 0; $i < $bulkCount; $i++) {
            if ($method == 'custom') {
                $license = $cuslicense;
            } else {
                $license = strtoupper($prefix . "-" . bin2hex(random_bytes(4)));
            }

            $data_response = [
                'game' => $game,
                'user_key' => $license,
                'duration' => $drtn,
                'max_devices' => $maxd,
                'registrator' => $user->username,
                'admin_id' => $user->id_users
            ];
            
            $idKeys = $this->keysModel->insert($data_response);
            
            if ($idKeys) {
                $gameLabel = isset($this->game_list[$game]) ? $this->game_list[$game] : $game;
                $generatedKeys[] = [
                    'key' => $license,
                    'game' => $game,
                    'game_name' => $gameLabel,
                    'duration' => $drtn,
                    'max_devices' => $maxd,
                ];
                $history->insert([
                    'keys_id' => $idKeys,
                    'user_do' => $user->username,
                    'info' => "$game|" . substr($license, 0, 8) . "|$drtn|$maxd"
                ]);
            }
        }

        // Deduct balance
        $newBalance = $user->saldo - $totalCost;
        $this->userModel->update($user->id_users, ['saldo' => $newBalance]);

        return $this->response->setJSON([
            'success' => true,
            'message' => count($generatedKeys) . ' key(s) generated successfully',
            'generated_keys' => $generatedKeys,
            'new_balance' => (float)$newBalance
        ]);
    }

    public function reset_key()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = $json['username'] ?? '';
        $password = $json['password'] ?? '';

        $user = $this->authenticate($username, $password);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        $keyStr = $json['key'] ?? '';
        if (empty($keyStr)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Key parameter is required'
            ])->setStatusCode(400);
        }

        $dbKey = $this->keysModel->where('user_key', $keyStr)->first();
        if (!$dbKey) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Key not found'
            ])->setStatusCode(404);
        }

        // Check permission: Owner (level == 1) or user is the registrator of the key
        if ($user->level != 1 && $dbKey['registrator'] !== $user->username) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to reset this key'
            ])->setStatusCode(403);
        }

        // Reset devices
        $this->keysModel->update($dbKey['id_keys'], ['devices' => null]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Devices reset successfully for key: ' . $keyStr
        ]);
    }

    public function create_user()
    {
        $json = $this->request->getJSON(true) ?: $this->request->getPost();
        $username = $json['username'] ?? '';
        $password = $json['password'] ?? '';

        $user = $this->authenticate($username, $password);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        if ($user->level != 1 && $user->level != 2) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access Denied: Only Owner or Admin can create users'
            ])->setStatusCode(403);
        }

        $new_username = $json['new_username'] ?? '';
        $new_password = $json['new_password'] ?? '';
        $set_saldo = (int)($json['set_saldo'] ?? 0);
        $accExpire = (int)($json['acc_expire'] ?? 30);
        $accLevel = (int)($json['acc_level'] ?? 3);

        // Validation
        if (empty($new_username) || strlen($new_username) < 4 || strlen($new_username) > 25) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username must be between 4 and 25 characters'
            ])->setStatusCode(400);
        }

        if (empty($new_password) || strlen($new_password) < 6 || strlen($new_password) > 45) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password must be between 6 and 45 characters'
            ])->setStatusCode(400);
        }

        // Check if username already exists
        $exists = $this->userModel->where('username', $new_username)->first();
        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Username is already taken'
            ])->setStatusCode(400);
        }

        // Constraints for Level 2 Admin
        if ($user->level == 2) {
            if ($accLevel != 3) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Admins are only allowed to create Reseller accounts'
                ])->setStatusCode(400);
            }
            if ($set_saldo > 30) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Admins are not allowed to assign more than 30 points to new Resellers'
                ])->setStatusCode(400);
            }
        }

        if ($set_saldo < 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Point balance cannot be negative'
            ])->setStatusCode(400);
        }

        // Dates
        $now = \CodeIgniter\I18n\Time::now();
        $expiration_date = \CodeIgniter\I18n\Time::now()->addDays($accExpire);
        $password_hash = create_password($new_password);

        $insertData = [
            'username'        => $new_username,
            'reset_link_token'=> '',
            'exp_date'        => '',
            'level'           => $accLevel,
            'saldo'           => $set_saldo,
            'status'          => 1,
            'uplink'          => $user->username,
            'password'        => $password_hash,
            'user_ip'         => $this->request->getIPAddress(),
            'created_at'      => $now,
            'updated_at'      => null,
            'expiration_date' => $expiration_date,
        ];

        if ($this->userModel->insert($insertData)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => "User {$new_username} successfully created"
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create user, please try again'
            ])->setStatusCode(500);
        }
    }
}
