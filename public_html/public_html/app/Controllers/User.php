<?php

namespace App\Controllers;

use App\Models\CodeModel;
use App\Models\ServerModel;
use App\Models\HistoryModel;
use App\Models\GameModel;
use App\Models\UserGameModel;
use App\Models\UserModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Controller;

class User extends BaseController
{
    protected $model, $userid, $user;

    public function __construct()
    {
        $this->userid = session()->userid;
        $this->model = new UserModel();
        $this->user = $this->model->getUser($this->userid);
        $this->time = new \CodeIgniter\I18n\Time;
    }

    public function index()
    {
        $historyModel = new HistoryModel();
        
        $topSellers = [];
        try {
            $db = \Config\Database::connect();
            $topSellers = $db->query("
                SELECT 
                    a.id_users,
                    a.username,
                    COUNT(k.id_keys) as total_keys
                FROM users a
                LEFT JOIN keys_code k ON a.username = k.registrator
                WHERE a.level > 1
                GROUP BY a.id_users, a.username
                ORDER BY total_keys DESC
                LIMIT 5
            ")->getResultArray();
        } catch (\Throwable $e) {
            $topSellers = [];
        }
        
        $historyData = [];
        try {
            $historyData = $historyModel->getAll();
        } catch (\Throwable $e) {
            $historyData = [];
        }

        $data = [
            'title'      => 'Dashboard',
            'user'       => $this->user,
            'time'       => $this->time,
            'history'    => $historyData,
            'topSellers' => $topSellers,
        ];
        return view('User/dashboard', $data);
    }
    
// Trong User controller:
public function ref_index()
{
    $user = $this->user;
    
    // Chỉ Admin và Tenant mới có quyền truy cập
    if (!in_array($user->level, [1, 3])) {
        return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
    }

    if ($this->request->getPost()) {
        return $this->reff_action();
    }

    $db = \Config\Database::connect();
    $builder = $db->table('referral_code');

    // Tenant chỉ xem được ref của mình
    if ($user->level == 3) {
        $builder->where('created_by', $user->username);
    }

    $codes = $builder->orderBy('created_at', 'DESC')->get()->getResult();

    $validation = Services::validation();
    $data = [
        'title' => 'Referral',
        'user' => $user,
        'time' => $this->time,
        'codes' => $codes,
        'total_code' => count($codes),
        'validation' => $validation
    ];
    return view('Admin/referral', $data);
}

private function reff_action()
{
    $user = $this->user;
    
    // Validate level
    if (!in_array($user->level, [1, 3])) {
        return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
    }

    $form_rules = [
        'set_saldo' => [
            'label' => 'saldo',
            'rules' => 'required|numeric|max_length[11]|greater_than_equal_to[0]',
            'errors' => [
                'greater_than_equal_to' => 'Invalid currency, cannot set to minus.'
            ]
        ],
        'level' => [
            'label' => 'account level',
            'rules' => 'required|numeric|in_list[1,2,3]',
            'errors' => [
                'in_list' => 'Invalid account level'
            ]
        ],
        'contract_expired_at' => [
            'label' => 'contract expiry date',
            'rules' => 'permit_empty|valid_date[Y-m-d]'
        ]
    ];

    // Tenant chỉ c tạo Reseller
    if ($user->level == 3 && $this->request->getPost('level') != 2) {
        return redirect()->back()->withInput()->with('msgDanger', 'Tenant can only create Reseller accounts');
    }

    if (!$this->validate($form_rules)) {
        return redirect()->back()->withInput()->with('msgDanger', 'Failed, check the form');
    }

    $orig_code = random_string('alnum', 6);
    $codeHash = create_password($orig_code, false);
    
    $contractExpiredAt = $this->request->getPost('contract_expired_at');
    
    $db = \Config\Database::connect();
    $data = [
        'code' => $codeHash,
        'orig_code' => $orig_code,
        'set_saldo' => $this->request->getPost('set_saldo'),
        'level' => $this->request->getPost('level'),
        'created_by' => session('unames'),
        'contract_expired_at' => $contractExpiredAt ? ($contractExpiredAt . ' 23:59:59') : null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $inserted = $db->table('referral_code')->insert($data);
    
    if ($inserted) {
        $msg = "Referral Code: $orig_code";
        return redirect()->back()->with('msgSuccess', $msg);
    }

    return redirect()->back()
           ->withInput()
           ->with('msgDanger', 'Failed to create referral code');
}

  

    

    public function api_get_users()
    {
        // API for DataTables
        $model = $this->model;
        return $model->API_getUser();
    }
    
public function user_delete($userid)
{
    $user = $this->user;
    
    // Chỉ Admin và Tenant mới có quyền xóa
    if (!in_array($user->level, [1, 3])) {
        return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
    }

    $model = $this->model;
    $target = $model->getUser($userid);

    // Không thể tìm thấy user cần xóa
    if (!$target) {
        return redirect()->to('admin/manage-users')->with('msgWarning', 'User not found.');
    }

    // Không thể xóa chính mình
    if ($target->id_users == $user->id_users) {
        return redirect()->to('admin/manage-users')->with('msgWarning', 'You cannot delete your own account.');
    }

    // Tenant ch xóa được Reseller của mình
    if ($user->level == 3) {
        if ($target->level != 2 || $target->uplink != $user->username) {
            return redirect()->to('admin/manage-users')->with('msgWarning', 'You can only delete your own Resellers.');
        }
    }

    // Thực hiện xóa
    if ($model->delete($userid)) {
        return redirect()->to('admin/manage-users')
               ->with('msgSuccess', "User {$target->username} has been deleted successfully.");
    }

    return redirect()->to('admin/manage-users')
           ->with('msgDanger', 'Failed to delete user.');
}


// Sửa lại manage_users()
public function manage_users()
{
    $user = $this->user;
    // Cho phép c Admin và Tenant truy cập
    if (!in_array($user->level, [1, 3])) {
        return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
    }

    $model = $this->model;
    $validation = Services::validation();
    $data = [
        'title' => 'Users Management',
        'user' => $user,
        'user_list' => $model->getUserList(), // Model đã đưc sửa đ lc theo quyn
        'time' => $this->time,
        'validation' => $validation
    ];
    return view('Admin/users', $data);
}

public function user_edit($userid = false)
{
    $user = $this->user;
    if (!in_array($user->level, [1, 3])) {
        return redirect()->to('dashboard')->with('msgWarning', 'Access denied!');
    }

    $model = $this->model;
    $target = $model->getUser($userid);

    // Kiểm tra quyền truy cập
    if (!$target) {
        return redirect()->to('admin/manage-users')->with('msgWarning', 'User not found.');
    }

    // Tenant chỉ có thể edit Reseller của họ hoặc chính họ
    if ($user->level == 3 && $target->id_users != $user->id_users) {
        if ($target->level != 2 || $target->uplink != $user->username) {
            return redirect()->to('admin/manage-users')
                   ->with('msgWarning', 'You can only edit your own account or your Resellers.');
        }
    }

    if ($this->request->getPost()) {
        return $this->user_edit_action();
    }

    $validation = Services::validation();
    $data = [
        'title' => 'Edit User',
        'user' => $user,
        'target' => $target,
        'user_list' => $model->getUserList(),
        'time' => $this->time,
        'validation' => $validation,
    ];
    return view('Admin/user_edit', $data);
}

private function user_edit_action()
{
    $user = $this->user;
    $model = $this->model;
    $userid = $this->request->getPost('user_id');
    $target = $model->getUser($userid);

    if (!$target) {
        return redirect()->to('admin/manage-users')
               ->with('msgWarning', 'User not found.');
    }

    // Tenant chỉ có thể edit Reseller của họ hoặc chính họ
    if ($user->level == 3 && $target->id_users != $user->id_users) {
        if ($target->level != 2 || $target->uplink != $user->username) {
            return redirect()->to('admin/manage-users')
                   ->with('msgWarning', 'You can only edit your own account or your Resellers.');
        }
    }

    // Base validation rules cho mọi người
    $form_rules = [
        'username' => [
            'label' => 'username', 
            'rules' => "required|alpha_numeric|min_length[4]|max_length[25]|is_unique[admin.username,username,$target->username]",
            'errors' => ['is_unique' => 'The {field} has been taken.']
        ],
        'fullname' => [
            'label' => 'name',
            'rules' => 'permit_empty|min_length[4]|max_length[155]',
            'errors' => ['alpha_space' => 'The {field} only allows alphabetical characters and spaces.']
        ],
        'status' => [
            'label' => 'status',
            'rules' => 'required|numeric|in_list[0,1]',
            'errors' => ['in_list' => 'Invalid {field} account.']
        ],
        'saldo' => [
            'label' => 'saldo',
            'rules' => 'permit_empty|numeric|max_length[11]|greater_than_equal_to[0]',
            'errors' => ['greater_than_equal_to' => 'Invalid currency, cannot be negative.']
        ],
    ];

    // Thêm validation cho level nếu là Admin hoặc Tenant edit Reseller
    if ($user->level == 1 || ($user->level == 3 && $target->id_users != $user->id_users)) {
        $form_rules['level'] = [
            'label' => 'roles',
            'rules' => 'required|numeric|in_list[1,2,3]',
            'errors' => ['in_list' => 'Invalid {field}.']
        ];
    }

    // Chỉ Admin mới được sửa uplink
    if ($user->level == 1) {
        $form_rules['uplink'] = [
            'label' => 'uplink',
            'rules' => 'required|alpha_numeric|is_not_unique[admin.username,username,]',
            'errors' => ['is_not_unique' => 'Uplink not registered.']
        ];
    }

    if (!$this->validate($form_rules)) {
        return redirect()->back()->withInput()->with('msgDanger', 'Please check the form.');
    }

    // Base update data cho mọi người
    $data_update = [
        'username' => $this->request->getPost('username'),
        'fullname' => esc($this->request->getPost('fullname')),
        'status' => $this->request->getPost('status'),
        'saldo' => $this->request->getPost('saldo') ?: 0,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    // Thêm level nếu là Admin hoặc Tenant edit Reseller
    if ($user->level == 1 || ($user->level == 3 && $target->id_users != $user->id_users)) {
        $data_update['level'] = $this->request->getPost('level');
    }

    // Chỉ Admin mới được update uplink
    if ($user->level == 1) {
        $data_update['uplink'] = $this->request->getPost('uplink');
    }

    // Kiểm tra thêm cho Tenant
    if ($user->level == 3) {
        // Nếu đang edit chính mình, không cho đổi level
        if ($target->id_users == $user->id_users) {
            unset($data_update['level']);
        }
        // Nếu đang edit Reseller, chỉ cho set level = 2
        elseif ($this->request->getPost('level') != 2) {
            return redirect()->back()
                   ->withInput()
                   ->with('msgDanger', 'Tenant can only set Reseller level');
        }
    }

    $update = $model->update($userid, $data_update);
    if ($update) {
        return redirect()->to('admin/manage-users')
               ->with('msgSuccess', "Successfully updated {$target->username}");
    }

    return redirect()->back()
           ->withInput()
           ->with('msgDanger', 'Failed to update user.');
}


    public function settings()
    {
        if ($this->request->getPost('password_form'))
            return $this->passwd_act();

        if ($this->request->getPost('fullname_form'))
            return $this->fullname_act();

        $user = $this->user;
        $db = \Config\Database::connect();
        $hasGetkeyGames = $db->fieldExists('getkey_games', 'admin');
        $hasGetkeyBuyUrl = $db->fieldExists('getkey_buy_url', 'admin');
        $hasGetkeyBuyIb = $db->fieldExists('getkey_buy_ib', 'admin');
        $hasGetkeySupportTele = $db->fieldExists('getkey_support_tele', 'admin');
        $hasGetkeyAutoBuy = $db->fieldExists('getkey_auto_buy', 'admin');

        $gameModel = new GameModel();
        $userGameModel = new UserGameModel();
        if ($user->level == 1) {
            $availableGames = $gameModel->where('status', 'active')->orderBy('name', 'ASC')->findAll();
        } else {
            $userGameIds = $userGameModel->getUserGames($user->id_users);
            $availableGames = !empty($userGameIds)
                ? $gameModel->whereIn('id', $userGameIds)->where('status', 'active')->orderBy('name', 'ASC')->findAll()
                : [];
        }

        $selectedGetkeyGames = [];
        if ($hasGetkeyGames && !empty($user->getkey_games)) {
            $selectedGetkeyGames = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $user->getkey_games)))));
        }
        
        $validation = Services::validation();
        $data = [
            'title' => 'Settings',
            'user' => $user,
            'time' => $this->time,
            'validation' => $validation,
            'menu_logo_options' => $this->getMenuLogoOptions(),
            'getkey_link' => base_url('Getkey.php') . '?admin=' . urlencode($user->username),
            'getkey_games_enabled' => $hasGetkeyGames,
            'getkey_buy_url_enabled' => $hasGetkeyBuyUrl,
            'getkey_buy_ib_enabled' => $hasGetkeyBuyIb,
            'getkey_support_tele_enabled' => $hasGetkeySupportTele,
            'getkey_auto_buy_enabled' => $hasGetkeyAutoBuy,
            'getkey_games_available' => $availableGames,
            'getkey_games_selected' => $selectedGetkeyGames,
        ];

        return view('User/settings', $data);
    }
    
public function Server()
   {
       if ($this->user->level != 1) {
           return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
       }

       if ($this->request->getPost()) {
           return $this->server_action();
       }

       $data = [
           'title' => 'Server',
           'user' => $this->user,
           'time' => $this->time,
           'row' => (new ServerModel())->getRow(),
           'validation' => Services::validation()
       ];
       return view('Server/Server', $data);
   }

   private function server_action()
   {
       if ($this->user->level != 1) {
           return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
       }

       $modname = $this->request->getPost('modname');
       $myinput = $this->request->getPost('myInput');
       $status = $this->request->getPost('radios');
       $data = [
           'modname' => $modname,
           'myinput' => $myinput,
           'status' => $status == '1' ? 'on' : 'off'
       ];
       (new ServerModel())->updateData($data);
       return redirect()->back()->with('msgSuccess', 'Server Successfully Changed.');
   }
    
   
  

    private function passwd_act()
    {
        $validation = Services::validation();

        $form_rules = [
            'current' => [
                'label' => 'current password',
                'rules' => 'required|min_length[6]|max_length[64]'
            ],
            'password' => [
                'label' => 'new password',
                'rules' => 'required|min_length[6]|max_length[64]|matches[password2]'
            ],
            'password2' => [
                'label' => 'confirm password',
                'rules' => 'required|min_length[6]|max_length[64]|matches[password]'
            ],
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Something wrong! Please check the form');
        }

        $current = $this->request->getPost('current');
        $newPasswordPlain = $this->request->getPost('password');
        $user = $this->user;

        if (!password_verify(create_password($current, false), $user->password)) {
            $validation->setError('current', 'Wrong current password.');
            return redirect()->back()->withInput()->with('msgDanger', 'Something wrong! Please check the form');
        }

        if ($current === $newPasswordPlain) {
            $validation->setError('password', 'New password must be different from the current password.');
            return redirect()->back()->withInput()->with('msgDanger', 'Something wrong! Please check the form');
        }

        $newPassword = create_password($newPasswordPlain);
        $this->model->update(session('userid'), ['password' => $newPassword]);
        return redirect()->back()->with('msgSuccess', 'Password Successfully Changed.');
    }

    private function fullname_act()
    {
        $user = $this->user;
        $validation = Services::validation();
        $fullName = $this->request->getPost('fullname');
        $menuName = trim((string) $this->request->getPost('menu_name'));
        $menuSubtitle = trim((string) $this->request->getPost('menu_subtitle'));
        $menuLogo = trim((string) $this->request->getPost('menu_logo'));
        $apiToken = trim((string) $this->request->getPost('api_token'));
        $apiTokens = $this->request->getPost('api_tokens');
        if (!is_array($apiTokens)) {
            $apiTokens = [];
        }
        $shortlinkServices = $this->request->getPost('shortlink_services');
        if (!is_array($shortlinkServices)) {
            $shortlinkServices = [];
        }
        $getkeySteps = (int) $this->request->getPost('getkey_steps');
        $getkeyTelegram = trim((string) $this->request->getPost('getkey_telegram'));
        $getkeyBuyUrl = trim((string) $this->request->getPost('getkey_buy_url'));
        $getkeyBuyIb = trim((string) $this->request->getPost('getkey_buy_ib'));
        $getkeySupportTele = trim((string) $this->request->getPost('getkey_support_tele'));
        $getkeyAutoBuy = trim((string) $this->request->getPost('getkey_auto_buy'));
        $shortlinkService = strtolower(trim((string) $this->request->getPost('shortlink_service'))) ?: 'xlink';
        if ($shortlinkService === 'vuotlink.vip' || $shortlinkService === 'vuotlinkvip') {
            $shortlinkService = 'vuotlink';
        }
        if ($shortlinkService === 'linkx.me' || $shortlinkService === 'linkxme') {
            $shortlinkService = 'linkx';
        }
        $db = \Config\Database::connect();

        $form_rules = [
            'fullname' => [
                'label' => 'name',
                'rules' => 'required|alpha_space|min_length[4]|max_length[155]',
                'errors' => [
                    'alpha_space' => 'The {field} only allow alphabetical characters and spaces.'
                ]
            ],
            'menu_name' => [
                'label' => 'Menu Name',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'menu_subtitle' => [
                'label' => 'Menu Subtitle',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'menu_logo' => [
                'label' => 'Menu Logo',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'getkey_steps' => [
                'label' => 'GetKey Steps',
                'rules' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[10]'
            ],
            'getkey_telegram' => [
                'label' => 'GetKey Telegram',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'getkey_buy_url' => [
                'label' => 'GetKey Buy URL',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'getkey_buy_ib' => [
                'label' => 'GetKey Buy IB Link',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'getkey_support_tele' => [
                'label' => 'GetKey Support Telegram',
                'rules' => 'permit_empty|max_length[255]'
            ],
            'getkey_auto_buy' => [
                'label' => 'GetKey Auto Buy',
                'rules' => 'permit_empty|max_length[255]'
            ]
        ];

        // Validate upload (optional)
        $file = $this->request->getFile('menu_logo_file');
        if ($file && $file->isValid()) {
            $uploadRules = [
                'menu_logo_file' => [
                    'label' => 'Menu Logo File',
                    'rules' => 'uploaded[menu_logo_file]|is_image[menu_logo_file]|mime_in[menu_logo_file,image/jpg,image/jpeg,image/png,image/webp]|max_size[menu_logo_file,2048]',
                ],
            ];
            if (!$this->validate($uploadRules)) {
                return redirect()->back()->withInput()->with('msgDanger', 'Invalid image upload.');
            }
        }
        
        // Không validate URL format - chỉ validate length trong form_rules
        // Cho user toàn quyền điền bất kỳ text gì
        
        if (!$this->validate($form_rules)) {
            log_message('warning', 'Form validation failed: ' . json_encode($validation->getErrors()));
            return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the form');
        } else {
            $normalizeService = static function ($service) {
                $service = strtolower(trim((string) $service));
                if ($service === 'vuotlink.vip' || $service === 'vuotlinkvip') {
                    $service = 'vuotlink';
                }
                if ($service === 'linkx.me' || $service === 'linkxme') {
                    $service = 'linkx';
                }
                $allowed = ['xlink', 'linkx', 'yeumoney', 'sieuthiapi', 'funlink', 'layma', 'vuotlink', 'just2earn', 'nhapma'];
                return in_array($service, $allowed, true) ? $service : 'xlink';
            };

            // Kiểm tra và thêm columns nếu chưa tồn tại
            if (!$db->fieldExists('menu_name', 'admin')) {
                try {
                    $db->query("ALTER TABLE `admin` ADD COLUMN `menu_name` VARCHAR(255) DEFAULT NULL");
                    log_message('info', 'Created column menu_name');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to create menu_name: ' . $e->getMessage());
                }
            }
            if (!$db->fieldExists('getkey_buy_ib', 'admin')) {
                try {
                    $db->query("ALTER TABLE `admin` ADD COLUMN `getkey_buy_ib` VARCHAR(255) DEFAULT NULL");
                    log_message('info', 'Created column getkey_buy_ib');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to create getkey_buy_ib: ' . $e->getMessage());
                }
            }
            if (!$db->fieldExists('getkey_support_tele', 'admin')) {
                try {
                    $db->query("ALTER TABLE `admin` ADD COLUMN `getkey_support_tele` VARCHAR(255) DEFAULT NULL");
                    log_message('info', 'Created column getkey_support_tele');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to create getkey_support_tele: ' . $e->getMessage());
                }
            }
            if (!$db->fieldExists('getkey_auto_buy', 'admin')) {
                try {
                    $db->query("ALTER TABLE `admin` ADD COLUMN `getkey_auto_buy` VARCHAR(255) DEFAULT NULL");
                    log_message('info', 'Created column getkey_auto_buy');
                } catch (\Exception $e) {
                    log_message('error', 'Failed to create getkey_auto_buy: ' . $e->getMessage());
                }
            }

            $shortlinkChain = [];
            foreach ($shortlinkServices as $idx => $srv) {
                $token = trim((string) ($apiTokens[$idx] ?? ''));
                $service = $normalizeService($srv);

                if ($token === '' && $srv === '') {
                    continue;
                }

                if ($token === '') {
                    $validation->setError('api_tokens', 'Vui lòng nhập token cho từng dịch vụ rút gọn.');
                    return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the form');
                }

                $shortlinkChain[] = [
                    'service' => $service,
                    'token' => $token,
                ];
            }

            if (empty($shortlinkChain) && $apiToken !== '') {
                $shortlinkChain[] = [
                    'service' => $normalizeService($shortlinkService ?: 'xlink'),
                    'token' => $apiToken,
                ];
            }

            if (empty($shortlinkChain)) {
                $validation->setError('shortlink_services', 'Cần ít nhất 1 dịch vụ rút gọn và token.');
                return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the form');
            }

            $shortlinkChain = array_slice($shortlinkChain, 0, 4);
            $primaryService = $shortlinkChain[0]['service'] ?? 'xlink';
            $apiTokenToStore = count($shortlinkChain) > 1 ? json_encode($shortlinkChain, JSON_UNESCAPED_UNICODE) : ($shortlinkChain[0]['token'] ?? '');

            $logoToUse = $menuLogo;

            if ($file && $file->isValid() && !$file->hasMoved()) {
                $targetDir = FCPATH . 'uploads/menu-logos/';
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                $clientName = $file->getClientName();
                $ext = $file->getExtension();
                $base = pathinfo($clientName, PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
                if ($safeBase === '') {
                    $safeBase = 'logo';
                }
                $uploadedFileName = $safeBase . '.' . $ext;
                $i = 1;
                while (file_exists($targetDir . $uploadedFileName)) {
                    $uploadedFileName = $safeBase . '-' . $i . '.' . $ext;
                    $i++;
                }
                $file->move($targetDir, $uploadedFileName);
                $logoToUse = '/uploads/menu-logos/' . $uploadedFileName;
            }

            $updateData = [
                'fullname' => esc($fullName),
                'menu_name' => !empty($menuName) ? $menuName : null,
                'menu_subtitle' => !empty($menuSubtitle) ? $menuSubtitle : null,
                'menu_logo' => !empty($logoToUse) ? $logoToUse : null,
                'api_token' => !empty($apiTokenToStore) ? $apiTokenToStore : null,
                'getkey_steps' => $getkeySteps > 0 ? $getkeySteps : 1,
                'getkey_telegram' => !empty($getkeyTelegram) ? $getkeyTelegram : null,
            ];

            // Always update the 4 new getkey fields (columns should exist after auto-create above)
            $updateData['getkey_buy_url'] = !empty($getkeyBuyUrl) ? $getkeyBuyUrl : null;
            $updateData['getkey_buy_ib'] = !empty($getkeyBuyIb) ? $getkeyBuyIb : null;
            $updateData['getkey_support_tele'] = !empty($getkeySupportTele) ? $getkeySupportTele : null;
            $updateData['getkey_auto_buy'] = !empty($getkeyAutoBuy) ? $getkeyAutoBuy : null;

            // Only set service if column exists to avoid SQL errors on older schemas
            if ($db->fieldExists('shortlink_service', 'admin')) {
                $updateData['shortlink_service'] = $primaryService;
            }

            if ($db->fieldExists('getkey_games', 'admin')) {
                $selectedIds = $this->request->getPost('getkey_games') ?? [];
                if (!is_array($selectedIds)) {
                    $selectedIds = [$selectedIds];
                }
                $selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));

                $gameModel = new GameModel();
                $userGameModel = new UserGameModel();
                if ($user->level == 1) {
                    $allowedIds = array_map('intval', $gameModel->select('id')->where('status', 'active')->findColumn('id') ?? []);
                } else {
                    $allowedIds = array_map('intval', $userGameModel->getUserGames($user->id_users));
                }

                if (!empty($selectedIds) && !empty($allowedIds)) {
                    $allowedSet = array_fill_keys($allowedIds, true);
                    $selectedIds = array_values(array_filter($selectedIds, static fn ($id) => isset($allowedSet[$id])));
                } elseif (!empty($selectedIds) && empty($allowedIds) && $user->level != 1) {
                    $selectedIds = [];
                }

                $updateData['getkey_games'] = !empty($selectedIds) ? implode(',', $selectedIds) : null;
            }

            // Thực hiện update với error handling
            try {
                log_message('info', 'Attempting to update user: ' . session('userid') . ' with data: ' . json_encode(array_keys($updateData)));
                $updateResult = $this->model->update(session('userid'), $updateData);
                log_message('info', 'Update result: ' . ($updateResult ? 'success' : 'failed'));
                
                if ($updateResult) {
                    return redirect()->back()->with('msgSuccess', 'Account Detail Successfully Changed.');
                } else {
                    log_message('error', 'Update returned false for user: ' . session('userid'));
                    return redirect()->back()->withInput()->with('msgDanger', 'Failed to save changes. Please try again.');
                }
            } catch (\Exception $e) {
                log_message('error', 'User settings update error: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
                return redirect()->back()->withInput()->with('msgDanger', 'Error: ' . $e->getMessage());
            }
        }
    }
    public function moduleSettings() {
    $moduleSettingsModel = new \App\Models\ModuleSettingsModel();
    
    if ($this->request->getMethod() === 'post') {
        $modname = trim($this->request->getPost('modname'));
        
        if (empty($modname)) {
            $modname = "KMODs";
        }

        $data = ['modname' => $modname];
        
        // Handle icon upload
        if ($file = $this->request->getFile('icon')) {
            if ($file->isValid() && !$file->hasMoved()) {
                $validationRule = [
                    'icon' => [
                        'label' => 'Icon',
                        'rules' => 'uploaded[icon]|is_image[icon]|mime_in[icon,image/jpg,image/jpeg,image/png]|max_size[icon,1024]',
                    ],
                ];

                if ($this->validate($validationRule)) {
                    $newName = $file->getRandomName();
                    $file->move(ROOTPATH . 'public/uploads/module_icons', $newName);
                    $data['icon_path'] = 'uploads/module_icons/' . $newName;
                    
                    // Delete old icon if exists
                    $existingSettings = $moduleSettingsModel->getModuleSettings($this->userid);
                    if ($existingSettings && !empty($existingSettings['icon_path'])) {
                        unlink(ROOTPATH . 'public/' . $existingSettings['icon_path']);
                    }
                } else {
                    return redirect()->back()->with('msgDanger', 'Invalid file upload');
                }
            }
        }

        // Handle icon deletion
        if ($this->request->getPost('delete_icon')) {
            $existingSettings = $moduleSettingsModel->getModuleSettings($this->userid);
            if ($existingSettings && !empty($existingSettings['icon_path'])) {
                unlink(ROOTPATH . 'public/' . $existingSettings['icon_path']);
                $data['icon_path'] = null;
            }
        }

        $existingSettings = $moduleSettingsModel->getModuleSettings($this->userid);
        
        if ($existingSettings) {
            $moduleSettingsModel->update($existingSettings['id'], $data);
        } else {
            $data['user_id'] = $this->userid;
            $moduleSettingsModel->insert($data);
        }
                   
        return redirect()->to('Server/moduleSettings')
                        ->with('msgSuccess', 'Module settings updated successfully');
    }

    $data = [
        'title' => 'Module Settings',
        'user' => $this->user,
        'time' => $this->time,
        'validation' => \Config\Services::validation(),
        'moduleSettings' => $moduleSettingsModel->getModuleSettings($this->userid)
    ];

    return view('Server/ModuleSettings', $data);
}

    /**
     * Scan common image folders to build a select list for menu logos.
     */
    private function getMenuLogoOptions(): array
    {
        $paths = [
            FCPATH . 'images',
            FCPATH . 'image',
            FCPATH . 'icon',
            FCPATH . 'assets',
            FCPATH . 'uploads/menu-logos',
        ];

        $extAllow = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $options = [];
        foreach ($paths as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($iter as $file) {
                if ($file->isFile()) {
                    $ext = strtolower($file->getExtension());
                    if (!in_array($ext, $extAllow, true)) {
                        continue;
                    }
                    $full = $file->getPathname();
                    $webPath = '/' . ltrim(str_replace(FCPATH, '', $full), '/\\');
                    $options[$webPath] = $webPath;
                }
            }
        }
        ksort($options);
        return $options;
    }
}
