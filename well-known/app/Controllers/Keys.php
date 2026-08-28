<?php

namespace App\Controllers;

use App\Models\HistoryModel;
use App\Models\KeysModel;
use App\Models\UserModel;
use Config\Services;

class Keys extends BaseController
{
    protected $userModel, $model, $user,$userId;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->user = $this->userModel->getUser();
        $this->model = new KeysModel();
        $this->time = new \CodeIgniter\I18n\Time;

     $this->userId=session()->get('userid');
        /* ------- Game ------- */
              $this->game_list = [
    'Freefire' => 'Rapid Core',
     //'Reaper' => 'Reaper X Pro Crked',
];

        $this->duration = [
            24 => '1 Days &mdash; $0.5 / Credits',
            168 => '7 Days &mdash; $1 / Credits',
            336 => '14 Days &mdash; $2 / Credits',
            720 => '30 Days &mdash; $3 / Credits',
           // 1440 => '60 Days &mdash; $8 / Credits',
            
        ];

        $this->price = [
            24 => 0.5,
            168 => 1,
            336 => 2,
            720 => 3,
            1440 => 8,
        ];
    }
    
    public function reset_all_devices()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('keys_code');

        // Reset devices for all rows
        $builder->set('devices', null)
                ->where('devices IS NOT NULL')
                ->update();

        $affected = $db->affectedRows();

        return $this->response->setJSON([
            'success' => true,
            'affected' => $affected
        ]);
    }

    public function pause_all_keys()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('keys_code');
        $builder->set('status', 0)->update();
        return $this->response->setJSON([
            'success' => true,
            'affected' => $db->affectedRows()
        ]);
    }

    public function unpause_all_keys()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('keys_code');
        $builder->set('status', 1)->update();
        return $this->response->setJSON([
            'success' => true,
            'affected' => $db->affectedRows()
        ]);
    }

    public function delete_all_keys()
    {
        $db = \Config\Database::connect();
        $db->table('keys_code')->truncate();
        return $this->response->setJSON([
            'success' => true
        ]);
    }

public function add_days()
{
    $db = \Config\Database::connect();

    try {
        // Get input from request (GET because you use $.getJSON)
        $days = (int) $this->request->getGet('days');
        $game = $this->request->getGet('game'); // 'ALL', 'PUBG', 'FREEFIRE', etc.

        // Fallback if empty or 0
        if ($days <= 0) {
            $days = 1;
        }

        // Build the SQL dynamically
        $params = [
            'days' => $days,
        ];

        $sql = "UPDATE `keys_code`
                SET `expired_date` = DATE_ADD(`expired_date`, INTERVAL :days: DAY)
                WHERE `expired_date` IS NOT NULL
                  AND `expired_date` != ''
                  AND `expired_date` != '0000-00-00 00:00:00'";

        // Filter by game if not ALL
        if (!empty($game) && $game !== 'ALL') {
            $sql .= " AND `game` = :game:";
            $params['game'] = $game;
        }

        $db->query($sql, $params);
        $affected = $db->affectedRows();

        return $this->response->setJSON([
            'success'  => ($affected > 0),
            'affected' => (int)$affected,
            'days'     => $days,
            'game'     => $game ?: 'ALL',
        ]);
    } catch (\Exception $e) {
        return $this->response->setJSON([
            'success' => false,
            'error'   => $e->getMessage(),
        ]);
    }
}
    
    public function index()
    {
        $model = $this->model;
        $user = $this->user;

        if ($user->level != 1) {
            $keys = $model->where('registrator', $user->username)
                ->findAll();
        } else {
            $keys = $model->select('user_key')->findAll() ;
        }
        $data = [
            'title' => 'Keys',
            'user' => $user,
            'keylist' => $keys,
            'time' => $this->time,
        ];
        return view('Keys/list', $data);
    }
    
public function download_all_Keys(){
    $model = $this->model;
    $user = $this->user;
    $keys = $model->select('user_key')->findAll();
    $data='';
    for($i=0;$i<count($keys);$i++){
        $data.=$keys[$i]['user_key']."\n";
    }
   /* write_file('Newkeys.txt', $data);*/
    $this->downloadFile('Newkeys.txt');
}

   
public function download_new_Keys(){
    $this->downloadFile('new.txt');
}

    function downloadFile($yourFile){
        // $yourFile = "newName.txt";
        $file = @fopen($yourFile, "rb");

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=Allkeys.txt');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($yourFile));
        while (!feof($file)) {
            print(@fread($file, 1024 * 8));
            ob_flush();
            flush();
        }
}
public function alterKeys(){
    $model=$this->model;
    $data=$model->where('expired_date <',  date('Y-m-d H:i:s'))->delete();

    return redirect()->back()->with('msgSuccess', 'success');
}

public function deleteKeys(){
    echo  date('Y-m-d H:i:s');
    $model=$this->model;
    $data=$model->emptyTable('keys_code');

    return redirect()->back()->with('msgSuccess', 'success');
}

public function resetAllKeys(){
    
    $model=$this->model;
    $keys = $this->request->getGet('userkey');
    $data=$model->where('user_key', $keys)->delete();
print_r($data);

    return redirect()->back()->with('msgSuccess', 'success');
}
//delete wasted keys
public function startDate(){
    echo  date('Y-m-d H:i:s');
    $model=$this->model;
    $data=$model->where('expired_date ='.null)->delete();

    return redirect()->back()->with('msgSuccess', 'success');
    
}
    public function api_get_keys()
    {
        // ? API for DataTable Keys
        $model = $this->model;
        return $model->API_getKeys();
    }

    
    public function deleteExpired(){
    echo  date('Y-m-d H:i:s');
    $model=$this->model;
    $data=$model->where('expired_date <',  date('Y-m-d H:i:s'))->delete();
    return redirect()->back()->with('msgSuccess', 'success');
}
//delete wasted keys
public function deleteUnused(){
    echo  date('Y-m-d H:i:s');
    $model=$this->model;
    $data=$model->where('expired_date ='.null)->delete();
    return redirect()->back()->with('msgSuccess', 'success');
    
}

    public function api_key_reset()
    {
        sleep(1);
        $model = $this->model;
        $keys = $this->request->getGet('userkey');
        $reset = $this->request->getGet('reset');
        $db_key = $model->getKeys($keys);

        $rules = [];
        if ($db_key) {
            $total = $db_key->devices ? explode(',', $db_key->devices) : [];
            $rules = ['devices_total' => count($total), 'devices_max' => (int) $db_key->max_devices];
            $user = $this->user;
            if ($db_key->devices and $reset) {
                if ($user->level == 1 or $db_key->registrator == $user->username) {
                    $model->set('devices', NULL)
                        ->where('user_key', $keys)
                        ->update();
                    $rules = ['reset' => true, 'devices_total' => 0, 'devices_max' => $db_key->max_devices];
                }
            } else {
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
        $msgDanger = "The user key no longer exists.";
        if ($key) {
            $dKey = $this->model->getKeys($key, 'id_keys');
            $user = $this->user;
            if ($dKey) {
                if ($user->level == 1 or $dKey->registrator == $user->username) {
                    $validation = Services::validation();
                    $data = [
                        'title' => 'Key',
                        'user' => $user,
                        'key' => $dKey,
                        'game_list' => $this->game_list,
                        'time' => $this->time,
                        'key_info' => getDevice($dKey->devices),
                        'messages' => setMessage('Please carefuly edit information'),
                        'validation' => $validation,
                    ];
                    return view('Keys/key_edit', $data);
                } else {
                    $msgDanger = "Restricted to this user key.";
                }
            }
        }
        return redirect()->to('keys')->with('msgDanger', $msgDanger);
    }

    private function edit_key_action()
    {
        $keys = $this->request->getPost('id_keys');
        $user = $this->user;
        $dKey = $this->model->getKeys($keys, 'id_keys');
        $game = implode(",", array_keys($this->game_list));

        if (!$dKey) {
            $msgDanger = "The user key no longer exists~";
        } else {
            if ($user->level == 1 or $dKey->registrator == $user->username) {
                $form_reseller = [
                    'status' => [
                        'label' => 'status',
                        'rules' => 'required|integer|in_list[0,1]',
                        'erros' => [
                            'integer' => 'Invalid {field}.',
                            'in_list' => 'Choose between list.'
                        ]
                    ]
                ];
                $form_admin = [
                    'id_keys' => [
                        'label' => 'keys',
                        'rules' => 'required|is_not_unique[keys_code.id_keys]|numeric',
                        'errors' => [
                            'is_not_unique' => 'Invalid keys.'
                        ],
                    ],
                    'game' => [
                        'label' => 'Games',
                        'rules' => "required|alpha_numeric_space|in_list[$game]",
                        'errors' => [
                            'alpha_numeric_space' => 'Invalid characters.'
                        ],
                    ],
                    'user_key' => [
                        'label' => 'User keys',
                        'rules' => "required|is_unique[keys_code.user_key,user_key,$dKey->user_key]|alpha_numeric",
                        'errors' => [
                            'is_unique' => '{field} has been taken.'
                        ],
                    ],
                    'duration' => [
                  'label' => 'duration',
                  'rules' => 'required|numeric|greater_than_equal_to[1]',
                  'errors' => [
                     'greater_than_equal_to' => 'Minimum {field} is invalid.',
                      'numeric' => 'Invalid hour {field}.'
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
                    'registrator' => [
                        'label' => 'registrator',
                        'rules' => 'permit_empty|alpha_numeric_space|min_length[4]'
                    ],
                    'expired_date' => [
                        'label' => 'expired',
                        'rules' => 'permit_empty|valid_date[Y-m-d H:i:s]',
                        'errors' => [
                            'valid_date' => 'Invalid {field} date.',
                        ]
                    ],
                    'devices' => [
                        'label' => 'device list',
                        'rules' => 'permit_empty'
                    ]
                ];

                if ($user->level == 1 || $user->level == 2) {
                    // Admin & Owner full rules.
                    if ($user->level == 2) {
                        $form_admin['max_devices'] = [
                            'label' => 'devices',
                            'rules' => 'required|numeric|greater_than_equal_to[1]|less_than_equal_to[10]',
                            'errors' => [
                                'greater_than_equal_to' => 'Minimum {field} is invalid.',
                                'less_than_equal_to' => 'Admin can set a maximum of 10 devices per key.',
                                'numeric' => 'Invalid max of {field}.'
                            ]
                        ];
                    }

                    $form_rules = array_merge($form_reseller, $form_admin);
                    $devices = $this->request->getPost('devices');
                    $max_devices = (int)$this->request->getPost('max_devices');

                    if ($user->level == 2 && $max_devices > 10) {
                        return redirect()->back()->withInput()->with('msgDanger', 'Admin can set a maximum of 10 devices per key.');
                    }

                    $data_saves = [
                        'game' => $this->request->getPost('game'),
                        'user_key' => $this->request->getPost('user_key'),
                        'duration' => $this->request->getPost('duration'),
                        'max_devices' => $max_devices,
                        'status' => $this->request->getPost('status'),
                        'registrator' => $this->request->getPost('registrator'),
                        'expired_date' => $this->request->getPost('expired_date') ?: NULL,
                        'devices' => setDevice($devices, $max_devices),
                    ];
                } else {
                    // Reseller just status rules, you can set manually later.
                    $form_rules = $form_reseller;
                    $data_saves = ['status' => $this->request->getPost('status')];
                }

                if (!$this->validate($form_rules)) {
                    return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the error');
                } else {
                    // * Data Updates
                    $this->model->update($dKey->id_keys, $data_saves);
                    return redirect()->back()->with('msgSuccess', 'User key successfuly updated!');
                }
            } else {
                $msgDanger = "Restricted to this user key~";
            }
        }
        return redirect()->to('keys')->with('msgDanger', $msgDanger);
    }

        public function generate()
    {
        if ($this->request->getPost())
            return $this->generate_action();

        $user = $this->user;
        $validation = Services::validation();

        $message = setMessage("<i class='bi bi-wallet'></i> Total Saldo $$user->saldo");
        if ($user->saldo <= 0) {
            $message = setMessage("Please top up to your beloved admin.", 'warning');
        }

        $data = [
            'title' => 'Generate',
            'user' => $user,
            'time' => $this->time,
            'game' => $this->game_list,
            'duration' => $this->duration,
            'price' => json_encode($this->price),
            'messages' => $message,
            'validation' => $validation,
        ];
        return view('Keys/generate', $data);
    }
    

    private function generate_action()
{
    $user = $this->user;
    $game = $this->request->getPost('game');
    $maxd = $this->request->getPost('max_devices');
    $drtn = $this->request->getPost('duration');
    
    // Support for Bulk and Method selection
    $method = $this->request->getPost('key_method');
    $bulkCount = ($method == 'custom') ? 1 : (int)($this->request->getPost('bulk_count') ?: 1);
    $prefix = $this->request->getPost('prefix') ?: 'KEY';
    $cuslicense = $this->request->getPost('cuslicense');

    $singlePrice = getPrice($this->price, $drtn, $maxd);
    $totalCost = $singlePrice * $bulkCount;

    $game_list = implode(",", array_keys($this->game_list));

    // Admin (level == 2) can set max 10 devices per key. Owner (level == 1) has unlimited.
    $isAdmin = ($user->level == 2);
    $adminDeviceLimit = 10;

    // Dynamic Validation Rules
    $deviceMaxRule = $isAdmin
        ? "required|numeric|greater_than_equal_to[1]|less_than_equal_to[$adminDeviceLimit]"
        : 'required|numeric|greater_than_equal_to[1]';

    $form_rules = [
        'game' => [
            'label' => 'Games',
            'rules' => "required|alpha_numeric_space|in_list[$game_list]",
            'errors' => ['alpha_numeric_space' => 'Invalid characters.']
        ],
        'duration' => [
            'label' => 'duration',
            'rules' => 'required|numeric|greater_than_equal_to[1]',
            'errors' => ['numeric' => 'Invalid day {field}.']
        ],
        'max_devices' => [
            'label' => 'devices',
            'rules' => $deviceMaxRule,
            'errors' => [
                'less_than_equal_to' => "Admin can set a maximum of {$adminDeviceLimit} devices per key.",
            ]
        ]
    ];

    // Add specific validation for Custom key if selected
    if ($method == 'custom') {
        $form_rules['cuslicense'] = [
            'label' => 'Custom Key',
            'rules' => 'required|alpha_numeric|min_length[4]|max_length[19]|is_unique[keys_code.user_key]',
            'errors' => [
                'required'    => 'The Custom Key field cannot be empty.',
                'is_unique'   => 'This Custom Key has already been taken.'
            ]
        ];
    }

    $validation = Services::validation();
    $reduceCheck = ($user->saldo - $totalCost);

    if ($reduceCheck < 0) {
        $validation->setError('duration', 'Insufficient balance');
        return redirect()->back()->withInput()->with('msgWarning', 'Insufficient balance. Please top up.');
    }

    if (!$this->validate($form_rules)) {
        return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the validation errors.');
    }

    // Extra hard-cap safety check for Admin
    if ($isAdmin && (int)$maxd > $adminDeviceLimit) {
        return redirect()->back()->withInput()->with('msgDanger', "Admin can set a maximum of {$adminDeviceLimit} devices per key.");
    }

    $generatedKeys = [];
    $history = new HistoryModel();

    // Start Generation Loop
    for ($i = 0; $i < $bulkCount; $i++) {
        if ($method == 'custom') {
            $license = $cuslicense;
        } else {
            // Random generation: PREFIX-XXXXX-XXXXX
            $license = strtoupper($prefix . "-" . bin2hex(random_bytes(4)));
        }

        $data_response = [
            'game' => $game,
            'user_key' => $license,
            'duration' => $drtn,
            'max_devices' => $maxd,
            'registrator' => $user->username,
            'admin_id' => $this->userId
        ];
        
        $idKeys = $this->model->insert($data_response);
        
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

    // Update user balance once
    $this->userModel->update(session('userid'), ['saldo' => $reduceCheck]);

    $msg = count($generatedKeys) . " license(s) generated successfully.";
    session()->setFlashdata([
        'generated_keys' => $generatedKeys,
        'fees' => $totalCost
    ]);

    return redirect()->back()->with('msgSuccess', $msg);
}
 
}