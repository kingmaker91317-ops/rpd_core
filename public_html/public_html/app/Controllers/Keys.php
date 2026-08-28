<?php

namespace App\Controllers;

use App\Models\GameModel;
use App\Models\HistoryModel;
use App\Models\KeysModel;
use App\Models\UserModel;
use App\Models\KeyFreeSettingsModel;
use Config\Services;
use CodeIgniter\I18n\Time;

class Keys extends BaseController
{
    protected $userModel, $model, $user, $keyColumn;

    public function __construct()
{
    $this->userModel = new UserModel();
    $this->user = $this->userModel->getUser();
    $this->model = new KeysModel();
    $this->keyColumn = $this->model->getKeyColumn();
    $this->time = new \CodeIgniter\I18n\Time;

    /* ------- Game list theo phân quyền ------- */
    $gameModel = new GameModel();
    $userGameModel = new \App\Models\UserGameModel();
    $this->game_list = [];

    // Admin thấy tất cả games
    if ($this->user->level == 1) {
        $dbGames = $gameModel->findAll();
    } else {
        // Level 2, 3: CHỈ lấy games được gán cho user cụ thể
        // KHÔNG có fallback - nếu không được gán thì không thấy game nào
        $userGameIds = $userGameModel->getUserGames($this->user->id_users);
        
        if (!empty($userGameIds)) {
            $dbGames = $gameModel->whereIn('id', $userGameIds)->findAll();
        } else {
            // Không có game nào được gán → không thấy game nào
            $dbGames = [];
        }
    }
    
    foreach ($dbGames as $game) {
        $this->game_list[$game['package']] = $game['name'];
    }

    // -------------------------
    // PHÂN QUYỀN GAME LIST
    // -------------------------

    if ($this->user->level == 1) {
        // ADMIN: được ALL + LKTEAM + database games
        $this->game_list = array_merge([
            'ALL' => 'ALL Games',
         //   'LKTEAM' => 'LKTEAM',
        ], $this->game_list);

    } elseif ($this->user->level == 3) {
        // TENANT: chỉ được LKTEAM + database games (không có ALL)
        $this->game_list = array_merge([
         //   'LKTEAM' => 'LKTEAM',
        ], $this->game_list);

    }
    // LEVEL 2 (Reseller) → chỉ game từ DB theo quyền

    // Thời hạn & giá (duration in hours)
    $this->duration = [
        24 => '1 Day — 20k/Device',
        168 => '7 Days — 50k/Device',
        336 => '14 Days — 80k/Device',
        720 => '30 Days — 120k/Device',
    ];

    $this->price = [
        24 => 20,
        168 => 50,
        336 => 80,
        720 => 120
    ];

    $this->key_levels = [
        2 => 'VIP'
    ];
}


    public function index()
{
    $model = $this->model;
    $user = $this->user;

    // Phân quyền xem key
    if ($user->level == 1) {
        // Admin xem tất cả
        $keys = $model->findAll();
    } else if ($user->level == 3) {
        // Tenant xem key của mình v của Reseller của mình
        $resellers = $this->userModel->where('uplink', $user->username)->findColumn('username');
        if ($resellers) {
            $keys = $model->whereIn('registrator', array_merge([$user->username], $resellers))->findAll();
        } else {
            $keys = $model->where('registrator', $user->username)->findAll();
        }
    } else {
        // Reseller chỉ xem key của mình
        $keys = $model->where('registrator', $user->username)->findAll();
    }

    $data = [
        'title' => 'Keys',
        'user' => $user,
        'keylist' => $keys,
        'time' => $this->time,
        'key_levels' => $this->key_levels
    ];
    return view('Keys/list', $data);
}

    public function api_get_keys()
    {
        // ? API for DataTable Keys
        $model = $this->model;
        return $model->API_getKeys();
    }

    public function api_key_reset()
{
    sleep(1);
    $model = $this->model;
    $keys = $this->request->getGet('userkey');
    $reset = $this->request->getGet('reset');
    $db_key = $model->getKeys($keys);
    $user = $this->user;

    $rules = [];
    if ($db_key) {
        $total = $db_key->UID ? explode(',', $db_key->UID) : [];
        $rules = ['devices_total' => count($total), 'devices_max' => (int) $db_key->max_devices];
        
        if ($reset) {
            // Kiểm tra quyền reset
            $canReset = false;
            if ($user->level == 1) {
                $canReset = true;
            } else if ($user->level == 3) {
                $resellers = $this->userModel->where('uplink', $user->username)->findColumn('username');
                $canReset = $db_key->registrator == $user->username || 
                           ($resellers && in_array($db_key->registrator, $resellers));
            } else {
                $canReset = $db_key->registrator == $user->username;
            }

            if ($canReset) {
                $model->set([
                        'devices' => NULL,
                        'UID' => NULL,
                        'uid_keys_reset_count' => (int) ($db_key->uid_keys_reset_count ?? 0) + 1,
                        'uid_keys_reset_date' => date('Y-m-d H:i:s'),
                    ])
                    ->where('username', $keys)
                    ->update();
                $rules = ['reset' => true, 'devices_total' => 0, 'devices_max' => $db_key->max_devices];
            }
        }
    }

    $data = [
        'registered' => $db_key ? true : false,
        'keys' => $keys,
    ];

    $real_response = array_merge($data, $rules);
    return $this->response->setJSON($real_response);
}

public function api_key_delete()
{
    sleep(1);
    $model = $this->model;
    $keys = $this->request->getGet('userkey');
    $delete = $this->request->getGet('delete');
    $db_key = $model->getKeys($keys);

    $rules = [];
    $user = $this->user;
    if ($delete) {
        if ($user->level == 1) {
            // Admin xóa được tt cả
            $model->where('username', $keys)->delete();
            $rules = ['delete' => true];
        } else if ($user->level == 3) {
            // Tenant xóa được key ca mình và của Seller do mnh tạo
            $userModel = new UserModel();
            $sellerUsernames = $userModel->where('uplink', $user->username)->findColumn('username');
            $sellerUsernames[] = $user->username;
            
            if (in_array($db_key->registrator, $sellerUsernames)) {
                $model->where('username', $keys)->delete();
                $rules = ['delete' => true];
            }
        } else if ($user->level == 2 && $db_key->registrator == $user->username) {
            // Seller xóa đợc key của mình
            $model->where('username', $keys)->delete();
            $rules = ['delete' => true];
        }
    }

    $data = [
        'registered' => $db_key ? true : false,
        'keys' => $keys,
    ];

    $real_response = array_merge($data, $rules);
    return $this->response->setJSON($real_response);
}


public function edit_key($key = false)
{
    if ($this->request->getPost()) return $this->edit_key_action();
    
    if (!$key) {
        return redirect()->to('keys')->with('msgDanger', "Key not specified.");
    }

    // Use username as identifier instead of id
    $dKey = $this->model->getKeys($key, 'username');
    $user = $this->user;
    
    if (!$dKey) {
        return redirect()->to('keys')->with('msgDanger', "Key no longer exists.");
    }

    // Kiểm tra quyền chỉnh sửa
    $canEdit = false;
    if ($user->level == 1) {
        $canEdit = true;
    } else if ($user->level == 3) {
        $resellers = $this->userModel->where('uplink', $user->username)->findColumn('username');
        $canEdit = $dKey->registrator == $user->username || 
                  ($resellers && in_array($dKey->registrator, $resellers));
    } else {
        $canEdit = $dKey->registrator == $user->username;
    }

    if (!$canEdit) {
        return redirect()->to('keys')->with('msgDanger', "Access denied!");
    }

    // Nu là Tenant và key là DEV, khng cho php sửa
    if ($user->level == 3 && $dKey->game == 'DEV') {
        return redirect()->to('keys')->with('msgDanger', "You don't have permission to edit DEV keys!");
    }

    $validation = Services::validation();
    $data = [
        'title' => 'Key',
        'user' => $user,
        'key' => $dKey,
        'game_list' => $this->game_list,
        'time' => $this->time,
        'key_info' => getDevice($dKey->UID),
        'messages' => setMessage('Please carefully edit information'),
        'validation' => $validation,
        'key_levels' => $this->key_levels,
        'key_column' => $this->keyColumn,
        // Phân quyền hiển thị các trường
        'can_edit' => [
            'game' => ($user->level == 1 || $user->level == 3),
            'username' => ($user->level == 1 || $user->level == 3),
            'key_level' => ($user->level == 1 || $user->level == 3),
            'duration' => true, // Hiển thị cho tất cả
            'max_devices' => true, // Hiển thị cho tất cả
            'status' => true,
            'registrator' => ($user->level == 1 || $user->level == 3),
            'expired_date' => true, // Hiển thị cho tất cả
            'devices' => ($user->level == 1 || $user->level == 3),
        ],
        // Phân quyền SỬA các trường (chỉ admin mới sửa được)
        'can_modify' => [
            'duration' => ($user->level == 1),
            'max_devices' => ($user->level == 1),
            'expired_date' => ($user->level == 1)
        ]
    ];
    return view('Keys/key_edit', $data);
}

    private function edit_key_action()
{
    // Use original_username to identify the key (in case username is changed)
    $originalUsername = $this->request->getPost('original_username');
    $user = $this->user;
    $dKey = $this->model->getKeys($originalUsername, 'username');

    if (!$dKey) {
        return redirect()->to('keys')->with('msgDanger', "Key no longer exists.");
    }

    // Kiểm tra quyền chỉnh sửa
    $canEdit = false;
    if ($user->level == 1) {
        // Admin full quyền
        $canEdit = true;
    } else if ($user->level == 3) {
        // Tenant quản lý key ca mình v của Reseller
        $resellers = $this->userModel->where('uplink', $user->username)->findColumn('username');
        $canEdit = $dKey->registrator == $user->username || 
                  ($resellers && in_array($dKey->registrator, $resellers));
    } else {
        // Reseller chỉ sa được status ca key mình
        $canEdit = $dKey->registrator == $user->username;
    }

    if (!$canEdit) {
        return redirect()->to('keys')->with('msgDanger', "Access denied!");
    }

        // Kiểm tra và setup rules
        if ($user->level == 1) {
        // ADMIN - Full quyền
        $game = $this->request->getPost('game');

        $form_rules = [
            'game' => [
                'label' => 'Games',
                'rules' => 'required|string',
            ],
            'username' => [
                'label' => 'User keys',
                'rules' => "required|is_unique[users.username,username,{$dKey->username}]|alpha_numeric",
                'errors' => ['is_unique' => '{field} has been taken.']
            ],
            'key_level' => [
                'label' => 'Key Level',
                'rules' => 'required|integer|in_list[1,2]'
            ],
            'duration' => [
                'label' => 'duration',
                'rules' => 'required|numeric|greater_than_equal_to[1]'
            ],
            'max_devices' => [
                'label' => 'devices',
                'rules' => 'required|numeric|greater_than_equal_to[1]'
            ],
            'status' => [
                'label' => 'status',
                'rules' => 'required|integer|in_list[0,1]'
            ],
            'registrator' => [
                'label' => 'registrator',
                'rules' => 'permit_empty|alpha_numeric_space|min_length[4]'
            ],
            'expired_date' => [
                'label' => 'expired',
                'rules' => 'permit_empty|valid_date[Y-m-d H:i:s]'
            ],
            'devices' => [
                'label' => 'device list',
                'rules' => 'permit_empty'
            ]
        ];

        $expiredInput = $this->request->getPost('expired_date');
        $expiredValue = $expiredInput ?: NULL;
        $maxDevicesValue = $this->request->getPost('max_devices');

        $data_saves = [
            'game' => $game,
            'username' => $this->request->getPost('username'),
            'key_level' => $this->request->getPost('key_level'),
            'duration' => $this->request->getPost('duration'),
            'max_devices' => $maxDevicesValue,
            'device_limit' => $maxDevicesValue,
            'status' => $this->request->getPost('status'),
            'registrator' => $this->request->getPost('registrator'),
            'expired_date' => $expiredValue,
            'expired' => $expiredValue,
            'devices' => $this->request->getPost('devices'),
            'UID' => $this->request->getPost('devices')
        ];
    } else if ($user->level == 3) {
        // TENANT - Không được sửa duration, max_devices, expired_date
        $game = $this->request->getPost('game');
        
        // Tenant không được sửa key DEV
        if ($game == 'DEV') {
            return redirect()->to('keys')->with('msgDanger', "You don't have permission to edit DEV keys!");
        }

        $form_rules = [
            'game' => [
                'label' => 'Games',
                'rules' => 'required|string',
            ],
            'username' => [
                'label' => 'User keys',
                'rules' => "required|is_unique[users.username,username,{$dKey->username}]|alpha_numeric",
                'errors' => ['is_unique' => '{field} has been taken.']
            ],
            'key_level' => [
                'label' => 'Key Level',
                'rules' => 'required|integer|in_list[1,2]'
            ],
            'status' => [
                'label' => 'status',
                'rules' => 'required|integer|in_list[0,1]'
            ],
            'registrator' => [
                'label' => 'registrator',
                'rules' => 'permit_empty|alpha_numeric_space|min_length[4]'
            ],
            'devices' => [
                'label' => 'device list',
                'rules' => 'permit_empty'
            ]
        ];

        $data_saves = [
            'game' => $game,
            'username' => $this->request->getPost('username'),
            'key_level' => $this->request->getPost('key_level'),
            'status' => $this->request->getPost('status'),
            'registrator' => $this->request->getPost('registrator'),
            'devices' => $this->request->getPost('devices'),
            'UID' => $this->request->getPost('devices')
        ];
    } else {
        // RESELLER - Chỉ được sửa status
        $form_rules = [
            'status' => [
                'label' => 'status',
                'rules' => 'required|integer|in_list[0,1]'
            ]
        ];

        $data_saves = [
            'status' => $this->request->getPost('status')
        ];
    }

    if (!$this->validate($form_rules)) {
        return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the errors');
    }

    // Update using username as identifier
    $this->model->where('username', $originalUsername)->set($data_saves)->update();
    return redirect()->back()->with('msgSuccess', 'Key has been updated successfully!');
}
    public function generate()
    {
        if ($this->request->getPost())
            return $this->generate_action();

        $user = $this->user;
        $validation = Services::validation();

        $message = setMessage("<i class='bi bi-wallet'></i> Total Amount $$user->saldo");
        if ($user->saldo <= 0) {
            $message = setMessage("Please top up the admin.", 'warning');
        }

        // Get games info with require_password for dynamic form
        $gameModel = new GameModel();
        $gamesInfo = [];
        foreach ($this->game_list as $package => $name) {
            if ($package !== 'ALL' && $package !== 'LKTEAM' && $package !== 'DEV') {
                $gameInfo = $gameModel->getGame($package);
                if ($gameInfo) {
                    $gamesInfo[$package] = [
                        'require_password' => isset($gameInfo->require_password) && $gameInfo->require_password == 1
                    ];
                } else {
                    $gamesInfo[$package] = ['require_password' => false];
                }
            } else {
                // For special games like ALL, LKTEAM, DEV - default to false
                $gamesInfo[$package] = ['require_password' => false];
            }
        }
        
        $data = [
            'title' => 'Generate',
            'user' => $user,
            'time' => $this->time,
            'game' => $this->game_list,
            'games_info' => $gamesInfo,
            'duration' => $this->duration,
            'price' => json_encode($this->price),
            'messages' => $message,
            'validation' => $validation,
            'key_levels' => $this->key_levels
        ];
        return view('Keys/generate', $data);
    }

    private function generate_action()
{
    $user = $this->user;
    $game = $this->request->getPost('game');
    $quantity = (int) $this->request->getPost('quantity') ?: 1;
    
    // Kiểm tra quyền vi game type
    if (($game == 'DEV' && $user->level != 1) || 
        ($game == 'ALL' && $user->level > 3)) {
        return redirect()->back()->withInput()
               ->with('msgDanger', 'You do not have permission for this game type');
    }

    $maxd = $this->request->getPost('max_devices');
    $drtn = $this->request->getPost('duration');
    $key_level = $this->request->getPost('key_level');
    $getPrice = getPrice($this->price, $drtn, $maxd) * $quantity;

    $custom_username = trim((string) $this->request->getPost('username'));
    $custom_password = trim((string) $this->request->getPost('password'));

    // Check if game requires password
    $gameModel = new GameModel();
    $gameInfo = null;
    $requiresPassword = false;
    
    if ($game !== 'ALL' && $game !== 'LKTEAM' && $game !== 'DEV') {
        $gameInfo = $gameModel->getGame($game);
        if ($gameInfo && isset($gameInfo->require_password)) {
            $requiresPassword = (bool)$gameInfo->require_password;
        }
    }

    $game_list = implode(",", array_keys($this->game_list));
    $form_rules = [
        'game' => [
            'label' => 'Games',
            'rules' => "required|string|in_list[$game_list]",
            'errors' => [
                'alpha_numeric_space' => 'Invalid characters.'
            ],
        ],
        'key_level' => [
            'label' => 'Key Level',
            'rules' => 'required|integer|in_list[1,2]',
            'errors' => [
                'integer' => 'Invalid key level.',
                'in_list' => 'Choose between FREE or VIP.'
            ]
        ],
        'duration' => [
            'label' => 'duration',
            'rules' => 'required|numeric|greater_than_equal_to[1]',
            'errors' => [
                'greater_than_equal_to' => 'Minimum {field} is invalid.',
                'numeric' => 'Invalid day {field}.'
            ]
        ],
        'max_devices' => [
            'label' => 'devices',
            'rules' => 'required|numeric|greater_than_equal_to[1]',
            'errors' => [
                'greater_than_equal_to' => 'Minimum {field} is invalid.',
                'numeric' => 'Invalid max of {field}.'
            ]
        ],
        'username' => [
            'label' => 'Username',
            'rules' => 'permit_empty|min_length[4]|max_length[32]|regex_match[/^[0-9A-Za-z_.\-]+$/]|is_unique[users.username]',
            'errors' => [
                'min_length' => 'Username must have at least 4 characters.',
                'max_length' => 'Username is too long.',
                'regex_match' => 'Username may only contain letters, numbers, dot, dash or underscore.',
                'is_unique' => 'Username has already been taken.'
            ]
        ],
    ];
    
    // Add password validation only if game requires it
    if ($requiresPassword) {
        $form_rules['password'] = [
            'label' => 'Password',
            'rules' => 'required|min_length[6]|max_length[64]',
            'errors' => [
                'required' => 'Password is required for this game.',
                'min_length' => 'Password must have at least 6 characters.',
                'max_length' => 'Password is too long.'
            ]
        ];
    } else {
        // Password is optional for games that don't require it
        $form_rules['password'] = [
            'label' => 'Password',
            'rules' => 'permit_empty|min_length[6]|max_length[64]',
            'errors' => [
                'min_length' => 'Password must have at least 6 characters.',
                'max_length' => 'Password is too long.'
            ]
        ];
    }

    $validation = Services::validation();
    $reduceCheck = ($user->saldo - $getPrice);

    if ($reduceCheck < 0) {
        $validation->setError('duration', 'Insufficient balance');
        return redirect()->back()->withInput()->with('msgWarning', 'Please top up the admin.');
    } else {
        if (!$this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check for errors');
        } else {
            $msg = "Created successfully.";
            $generated_keys = [];
            $generated_accounts = [];
            $durationHours = max(1, (int) $drtn);
            $creationTime = Time::now();
            $createdAtValue = $creationTime->toDateTimeString();

            for ($i = 0; $i < $quantity; $i++) {
                $usernameValue = !empty($custom_username) && $i == 0 ? $custom_username : random_string('alnum', 10);
                // Only generate password if game requires it
                if ($requiresPassword) {
                    $passwordValue = !empty($custom_password) && $i == 0 ? $custom_password : random_string('alnum', 12);
                } else {
                    // If game doesn't require password, use empty string or don't set it
                    $passwordValue = !empty($custom_password) && $i == 0 ? $custom_password : '';
                }

                $data_response = [
                    'game' => $game,
                    'username' => $usernameValue,
                    'password' => $passwordValue,
                    'key_level' => $key_level,
                    'duration' => $drtn,
                    'max_devices' => $maxd,
                    'device_limit' => $maxd,
                    'UID' => '',
                    'devices' => '',
                    'uid_keys_reset_count' => 0,
                    'uid_keys_reset_date' => $createdAtValue,
                    'expired_date' => NULL,
                    'expired' => NULL,
                    'status' => 1,
                    'registrator' => $user->username,
                ];

                $idKeys = $this->model->insert($data_response);
                $this->userModel->update(session('userid'), ['saldo' => $reduceCheck]);

                $history = new HistoryModel();
                $history->insert([
                    'keys_id' => $idKeys,
                    'user_do' => $user->username,
                    'info' => "$game|" . substr($usernameValue, 0, 5) . "|$drtn|$maxd"
                ]);

                $generated_keys[] = $usernameValue;
                $generated_accounts[] = [
                    'username' => $usernameValue,
                    'password' => $passwordValue,
                ];
            }

            $other_response = [
                'game' => $game,
                'duration' => $drtn,
                'key_level' => $key_level,
                'max_devices' => $maxd,
                'fees' => $getPrice
            ];

            if ($quantity == 1) {
                // Lưu theo danh sách chung để luôn có thể copy dễ dàng
                session()->setFlashdata(array_merge([
                    'generated_keys' => $generated_keys,
                    'generated_accounts' => $generated_accounts
                ], $other_response));
            } else {
                session()->setFlashdata(array_merge([
                    'generated_keys' => $generated_keys,
                    'generated_accounts' => $generated_accounts
                ], $other_response));
            }
            return redirect()->back()->with('msgSuccess', $msg);
        }
    }
}
    
    protected $lists = ['can_generate_key', 'last_free_key_time', 'bypass_step'];

public function free()
{
   $admin_username = $this->request->getGet('admin');
   
   if (!$admin_username) {
       // Hiển thị view khi truy cập trực tiếp
       return view('Keys/free_no_admin', [
           'title' => 'Key Free',
           'user' => $this->user,
           'time' => $this->time
       ]);
   }

   // Lấy thng tin admin/tenant từ username 
   $admin = $this->userModel->where('username', $admin_username)
                           ->whereIn('level', [1, 3])  // Cho phép cả admin và tenant
                           ->first();
   
   if (!$admin) {
       return redirect()->to('/')->with('msgDanger', 'Invalid admin user');  
   }

   $settingsModel = new KeyFreeSettingsModel();
   $settings = $settingsModel->where('admin_id', $admin['id_users'])->first();

   if (!$settings || $settings['status'] != 1) {
       return view('Keys/free_disabled', [
           'message' => 'Key free system is currently disabled.'
       ]);
   }

   if ($this->request->getMethod() == "post") {
       $game = $this->request->getPost('game');
       
       $shortlinks = array_filter(explode("\n", $settings['shortlinks']));
       if (empty($shortlinks)) {
           return redirect()->to('keys/free?admin='.$admin_username)
                  ->with('msgDanger', 'No shortlinks configured.');
       }

       // Lưu số bước đếm vào session
       $maxSteps = $settings['max_keys_per_day'];

       session()->set([
           $this->lists[0] => true,
           $this->lists[1] => time(),
           'free_game' => $game,
           'admin_settings' => $settings,
           'admin_username' => $admin_username,
           'max_steps' => $maxSteps
       ]);
       
       return redirect()->to(trim($shortlinks[0]));
   }

   session()->remove($this->lists);
   session()->remove('free_game');
   session()->remove('admin_settings');
   session()->remove('admin_username');
   
   $gameModel = new GameModel();
   $games = $gameModel->findAll();

   return view('Keys/free', [
       'link_total' => $settings ? count(array_filter(explode("\n", $settings['shortlinks']))) : 0,
       'games' => $games,
       'settings' => $settings
   ]);
}

public function free_action()
{
  $settings = session()->get('admin_settings');
  $admin_username = session()->get('admin_username');
  
  // Check session
  if (!$settings || !$admin_username || !session()->get($this->lists[0])) {
      return view('Keys/free_error', [
          'title' => 'Access Denied',
          'message' => 'Direct access to key generation is not allowed. Please follow the proper steps.',
          'user' => $this->user,
          'time' => $this->time
      ]);
  }

  $maxDevices = $settings['max_devices'];
  $duration = $settings['key_duration'];
  $key_level = 1;
  $minWaitTime = 25;

  $game = session()->get('free_game');
  $lastFreeKeyTime = session()->get($this->lists[1], time());

  if ($lastFreeKeyTime && time() - $lastFreeKeyTime < $minWaitTime) {
      session()->remove($this->lists);
      session()->remove('free_game');
      session()->remove('admin_settings');
      session()->remove('admin_username');
      return redirect()->to('keys/free?admin='.$admin_username)
             ->with('msgDanger', 'Please wait before trying to get another free key.');
  }

  $shortlinks = array_filter(explode("\n", $settings['shortlinks']));
  $bypassStep = session()->get($this->lists[2], 0) + 1;
  $maxSteps = session()->get('max_steps', count($shortlinks));
  
  if ($bypassStep < $maxSteps) {
      session()->set([$this->lists[1] => time(), $this->lists[2] => $bypassStep]);
      return redirect()->to(trim($shortlinks[$bypassStep % count($shortlinks)]));
  } elseif ($bypassStep == $maxSteps) {
      if (session()->get($this->lists[0], false)) {
          $gameModel = new GameModel();
          $gameInfo = $gameModel->where('package', $game)->first();

          $license = random_string('alnum', 16);
          $durationHours = max(1, (int) $duration);
          $creationTime = Time::now();
          $createdAtValue = $creationTime->toDateTimeString();
          $data_response = [
              'game' => $game,
              'username' => $license,
              'duration' => $duration,
              'max_devices' => $maxDevices,
              'device_limit' => $maxDevices,
              'key_level' => $key_level,
              'registrator' => $admin_username,
              'UID' => '',
              'devices' => '',
              'uid_keys_reset_count' => 0,
              'uid_keys_reset_date' => $createdAtValue,
              'expired_date' => NULL,
              'expired' => NULL,
              'status' => 1
          ];
          
          $idKeys = $this->model->insert($data_response);
          $history = new HistoryModel();
          $history->insertHistory([
              'keys_id' => $idKeys,
              'user_do' => $settings['admin_id'],
              'info' => "$game|" . substr($license, 0, 5) . "|$duration|$maxDevices"
          ]);

          session()->remove($this->lists);
          session()->remove('free_game');
          session()->remove('admin_settings');
          session()->remove('admin_username');
          session()->remove('max_steps');
          session()->setFlashdata([
              'username' => $license,
              'game' => $gameInfo['name'],
              'duration' => $duration,
              'max_devices' => $maxDevices
          ]);

          return redirect()->to('keys/free?admin='.$admin_username)
                 ->with('msgSuccess', 'Congratulations! You have received a free key.');
      }
  }

  session()->remove($this->lists);
  session()->remove('free_game'); 
  session()->remove('admin_settings');
  session()->remove('admin_username');
  session()->remove('max_steps');
  return redirect()->to('keys/free?admin='.$admin_username)
         ->with('msgDanger', 'There may be an error, please try again.');
}
}
