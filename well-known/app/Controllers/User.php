<?php

namespace App\Controllers;

use App\Models\CodeModel;
use App\Models\Server;
use App\Models\Status;
use App\Models\_ftext;
use App\Models\Feature;
use App\Models\onoff;
use App\Models\HistoryModel;
use App\Models\UserModel;
use App\Models\ResetLinkModel;
use App\Models\KeysModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Controller;
use CodeIgniter\I18n\Time;

class User extends BaseController
{
    protected $model, $userid, $user;

    public function __construct()
    {
        $this->userid = session()->userid;
        $this->model = new UserModel();
        $this->user = $this->model->getUser($this->userid);
        $this->time = new \CodeIgniter\I18n\Time;
        
        $this->accExpire = [
           1 => '1 Day',
           7 => '7 Days',
           15 => '15 Days',
           30 => '30 Days',
           60 => '60 Days',
        ];
        
        $this->accLevel = [
           1 => 'Owner',
           2 => 'Admin',
           3 => 'Reseller',
        ];
    }

    public function index()
    {
        $db = \Config\Database::connect();
        $user = $this->user;
        
        // Stats for Keys
        $builder = $db->table('keys_code');
        if ($user->level != 1) {
            $builder->where('registrator', $user->username);
        }
        
        $total_keys = $builder->countAllResults(false);
        $active_keys = (clone $builder)->where('devices IS NOT NULL')->countAllResults();
        $unused_keys = (clone $builder)->where('devices IS NULL')->countAllResults();
        
        // Total users
        $total_users = $db->table('users')->countAllResults();

        // Resellers List (for Dashboard)
        $resellers = $db->table('users')
            ->select('users.*, COUNT(keys_code.id_keys) as managed_keys')
            ->join('keys_code', 'keys_code.registrator = users.username', 'left')
            ->groupBy('users.id_users')
            ->orderBy('managed_keys', 'DESC')
            ->limit(3)
            ->get()
            ->getResult();

        $historyModel = new HistoryModel();
        $data = [
            'title' => 'Dashboard',
            'user' => $user,
            'time' => $this->time,
            'history' => $historyModel->getAll(),
            'stats' => [
                'total_keys' => $total_keys,
                'active_keys' => $active_keys,
                'unused_keys' => $unused_keys,
                'total_users' => $total_users,
            ],
            'resellers' => $resellers,
            'role_label' => $this->accLevel[$user->level] ?? 'User',
            'accLevel' => $this->accLevel
        ];
        return view('User/dashboard', $data);
    }
    
     public function ref_index()
    {
        $user = $this->user;
        
        if ($this->request->getPost())
        if (($user->level == 1) || ($user->level == 2)){
		return $this->reff_action();
	     }
	     else {
	         
	         return redirect()->to('dashboard')->with('msgWarning','Access Denied!');
	     }

        $levels = $this->accLevel;
        if ($user->level == 2) {
            $levels = [3 => 'Reseller'];
        }

        $mCode = new CodeModel();
        $validation = Services::validation();
        $data = [
            'title' => 'Referral',
            'user' => $user,
            'time' => $this->time,
            'code' => $mCode->getCode(),
            'accExpire' => $this->accExpire,
            'accLevel' => $levels,
            'total_code' => $mCode->countAllResults(),
            'validation' => $validation
        ];
        return view('Admin/referral', $data);
    }
    

    private function reff_action()
    {
        $user       = $this->user;
        $validation = Services::validation();

        $set_saldo  = (int) $this->request->getPost('set_saldo');
        $accLevel   = (int) $this->request->getPost('accLevel');

        if ($user->level == 2) {
            if ($accLevel != 3) {
                return redirect()->back()->withInput()->with('msgDanger', 'Admins are only allowed to create Reseller accounts.');
            }
            if ($set_saldo > 30) {
                return redirect()->back()->withInput()->with('msgDanger', 'Admins are not allowed to assign more than 30 points to new Resellers.');
            }
        }
    
        // Validation rules
        $form_rules = [
            'username' => [
                'label'  => 'username',
                'rules'  => 'required|alpha_numeric|min_length[4]|max_length[25]|is_unique[users.username]',
                'errors' => [
                    'is_unique' => 'The {field} has taken by other.'
                ]
            ],
            'password' => [
                'label' => 'password',
                'rules' => 'required|min_length[6]|max_length[45]',
            ],
            'set_saldo' => [
                'label'  => 'saldo',
                'rules'  => 'required|numeric|max_length[11]|greater_than_equal_to[0]',
                'errors' => [
                    'greater_than_equal_to' => 'Invalid currency, cannot set to minus.'
                ]
            ],
            'accExpire' => [
                'label'  => 'Account Expiration',
                'rules'  => 'required|numeric|max_length[3]|greater_than_equal_to[1]',
                'errors' => [
                    'greater_than_equal_to' => 'Invalid Days, cannot set to expired.'
                ]
            ],
            'accLevel' => [
                'label'  => 'Account Level',
                'rules'  => 'required|numeric|in_list[1,2,3]',
                'errors' => [
                    'in_list' => 'Invalid {field}.'
                ]
            ],
        ];
    
        if (! $this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Failed, check the form');
        }
    
        // --- Get POST values ---
        $fullname   = $this->request->getPost('fullname');
        $username   = $this->request->getPost('username');
        $email      = $this->request->getPost('email');
        $password   = $this->request->getPost('password');
        $saldo      = (int) $this->request->getPost('set_saldo');
        $accExpire  = (int) $this->request->getPost('accExpire'); // 1, 7, 15, 30, 60
        $accLevel   = (int) $this->request->getPost('accLevel');  // 1=Owner,2=Admin,3=Reseller
    
        // --- Dates ---
        $now             = Time::now();
        $expiration_date = Time::now()->addDays($accExpire);
    
        // --- Password hashing ---
        // Follow your existing pattern:
        //  - create_password($plain, false) -> pre-hash
        //  - create_password($plain)        -> final hash for DB
        $password_hash = create_password($password); // this should match passwd_act()
    
        // --- Prepare data matching your `users` table ---
        $insertData = [
            'username'        => $username,
            'reset_link_token'=> '',              // empty for now
            'exp_date'        => '',              // empty for now
            'level'           => $accLevel,
            'saldo'           => $saldo,
            'status'          => 1,               // active by default
            'uplink'          => $user->username, // current logged-in user as referrer
            'password'        => $password_hash,
            'user_ip'         => $this->request->getIPAddress(),
            'created_at'      => $now,
            'updated_at'      => null,
            'expiration_date' => $expiration_date,
        ];

        if ($user->level == 1 && $this->request->getPost('two_factor_chat_id') !== null) {
            $insertData['two_factor_chat_id'] = trim($this->request->getPost('two_factor_chat_id'));
        }
    
        // --- Insert into DB ---
        if ($this->model->insert($insertData)) {
            $msg = "User <b>{$username}</b> successfully created.";
            return redirect()->back()->with('msgSuccess', $msg);
        } else {
            return redirect()->back()->withInput()->with('msgDanger', 'Failed to create user, please try again.');
        }
    }

  
    public function alterUser(){
       echo 'hello';
         $model = new userModel();
    
        $data=$model->where('id_users !=', 1)->delete();
    print_r($data);
     return redirect()->back()->with('msgSuccess', 'success');
    }
        
    

    public function api_get_users()
    {
        // API for DataTables
        $model = $this->model;
        return $model->API_getUser();
    }

    public function server_management()
    {
        $user  = $this->user;
        if ($user->level != 1)
            return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');

        $db = \Config\Database::connect();
        $onoff = $db->table('onoff')->where('id', 1)->get()->getRow();
        
        $active_keys = $db->table('keys_code')->where('status', 1)->countAllResults();
        $paused_keys = $db->table('keys_code')->where('status', 0)->countAllResults();

        $data = [
            'title' => 'Server Management',
            'user' => $user,
            'time' => $this->time,
            'onoff' => $onoff,
            'active_keys' => $active_keys,
            'paused_keys' => $paused_keys
        ];
        return view('Admin/server_management', $data);
    }

    public function save_maintenance()
    {
        $user = $this->user;
        if ($user->level != 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        $status = $this->request->getPost('status');
        $reason = $this->request->getPost('reason');

        $db = \Config\Database::connect();
        $db->table('onoff')->where('id', 1)->update([
            'status' => $status,
            'myinput' => $reason
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    public function delete_user_keys($id)
    {
        $user = $this->user;
        if ($user->level != 1 && $user->level != 2) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $db = \Config\Database::connect();
        $targetUser = $db->table('users')->where('id_users', $id)->get()->getRow();
        if (!$targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }
        
        if ($user->level == 2 && $targetUser->uplink !== $user->username) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $builder = $db->table('keys_code');
        $builder->where('registrator', $targetUser->username)->delete();
        
        return $this->response->setJSON([
            'success' => true,
            'affected' => $db->affectedRows(),
            'message' => 'All keys for ' . $targetUser->username . ' have been deleted.'
        ]);
    }

    public function pause_user_keys($id)
    {
        $user = $this->user;
        if ($user->level != 1 && $user->level != 2) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $db = \Config\Database::connect();
        $targetUser = $db->table('users')->where('id_users', $id)->get()->getRow();
        if (!$targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }
        
        if ($user->level == 2 && $targetUser->uplink !== $user->username) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $db->table('keys_code')->where('registrator', $targetUser->username)->set('status', 0)->update();
        
        return $this->response->setJSON([
            'success' => true,
            'affected' => $db->affectedRows(),
            'message' => 'All keys for ' . $targetUser->username . ' have been paused.'
        ]);
    }

    public function unpause_user_keys($id)
    {
        $user = $this->user;
        if ($user->level != 1 && $user->level != 2) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $db = \Config\Database::connect();
        $targetUser = $db->table('users')->where('id_users', $id)->get()->getRow();
        if (!$targetUser) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }
        
        if ($user->level == 2 && $targetUser->uplink !== $user->username) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access Denied']);
        }
        
        $db->table('keys_code')->where('registrator', $targetUser->username)->set('status', 1)->update();
        
        return $this->response->setJSON([
            'success' => true,
            'affected' => $db->affectedRows(),
            'message' => 'All keys for ' . $targetUser->username . ' have been resumed.'
        ]);
    }

    public function manage_users()
    {
        $user  = $this->user;
        if ($user->level != 1 && $user->level != 2)
            return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');

        $model = $this->model;
        $validation = Services::validation();

        if ($user->level == 2) {
            $user_list = $model->where('uplink', $user->username)->get()->getResultObject();
        } else {
            $user_list = $model->getUserList();
        }

        $data = [
            'title' => 'Users',
            'user' => $user,
            'user_list' => $user_list,
            'time' => $this->time,
            'validation' => $validation
        ];
        return view('Admin/users', $data);
    }

    public function user_delete($userid = false)
    {
        $user = $this->user;
        if ($user->level != 1 && $user->level != 2)
            return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');

        $model = new userModel();
        $target = $model->getUser($userid);
        if (!$target) {
            return redirect()->back()->with('msgDanger', 'User not found');
        }

        if ($user->level == 2 && $target->uplink !== $user->username) {
            return redirect()->back()->with('msgWarning', 'Access Denied!');
        }

        $model->where('id_users =', $userid)->delete();
        return redirect()->back()->with('msgSuccess', 'success');
    }
    
    public function user_edit($userid = false)
    {
        $user = $this->user;
        if ($user->level != 1 && $user->level != 2)
            return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');

        $model = $this->model;
        $target = $model->getUser($userid);
        if (!$target) {
            return redirect()->to('admin/manage-users')->with('msgDanger', 'User not found.');
        }

        if ($user->level == 2 && $target->uplink !== $user->username) {
            return redirect()->to('admin/manage-users')->with('msgWarning', 'Access Denied! You can only edit users you created.');
        }

        if ($this->request->getPost())
            return $this->user_edit_action();

        $model = $this->model;
        $validation = Services::validation();

        $target = $model->getUser($userid);
        $total_keys = 0;
        $active_keys = 0;
        $paused_keys = 0;

        if ($target) {
            $db = \Config\Database::connect();
            $total_keys = $db->table('keys_code')->where('registrator', $target->username)->countAllResults();
            $active_keys = $db->table('keys_code')->where('registrator', $target->username)->where('devices IS NOT NULL')->countAllResults();
            $paused_keys = $db->table('keys_code')->where('registrator', $target->username)->where('status', 0)->countAllResults();
        }

        if ($user->level == 2) {
            $user_list = $model->where('uplink', $user->username)->get()->getResultObject();
        } else {
            $user_list = $model->getUserList();
        }

        $data = [
            'title' => 'Settings',
            'user' => $user,
            'target' => $target,
            'user_list' => $user_list,
            'time' => $this->time,
            'validation' => $validation,
            'stats' => [
                'total_keys' => $total_keys,
                'active_keys' => $active_keys,
                'paused_keys' => $paused_keys,
            ]
        ];
        return view('Admin/user_edit', $data);
    }

    private function user_edit_action()
    {
        $model = $this->model;
        $userid = $this->request->getPost('user_id');

        $target = $model->getUser($userid);
        if (!$target) {
            $msg = "User no longer exists.";
            return redirect()->to('dashboard')->with('msgDanger', $msg);
        }

        $user = $this->user;
        if ($user->level != 1 && $user->level != 2) {
            return redirect()->to('dashboard')->with('msgWarning', 'Access Denied!');
        }

        if ($user->level == 2 && $target->uplink !== $user->username) {
            return redirect()->to('admin/manage-users')->with('msgWarning', 'Access Denied!');
        }

        $level = (int)$this->request->getPost('level');
        $uplink = $this->request->getPost('uplink');
        $saldo = (int)$this->request->getPost('saldo');

        if ($user->level == 2) {
            if ($level != 3) {
                return redirect()->back()->withInput()->with('msgDanger', 'Admins are only allowed to assign Reseller role.');
            }
            if ($uplink !== $target->uplink) {
                return redirect()->back()->withInput()->with('msgDanger', 'You are not allowed to change the Uplink reference.');
            }
            if ($saldo > 30) {
                return redirect()->back()->withInput()->with('msgDanger', 'Admins are not allowed to assign more than 30 points to Resellers.');
            }
        }

        $username = $this->request->getPost('username');

        $saldoRule = ($user->level == 2)
            ? 'permit_empty|numeric|max_length[11]|greater_than_equal_to[0]|less_than_equal_to[30]'
            : 'permit_empty|numeric|max_length[11]|greater_than_equal_to[0]';

        $form_rules = [
            'username' => [
                'label' => 'username',
                'rules' => "required|alpha_numeric|min_length[4]|max_length[25]|is_unique[users.username,username,$target->username]",
                'errors' => [
                    'is_unique' => 'The {field} has taken by other.'
                ]
            ],
            'fullname' => [
                'label' => 'name',
                'rules' => 'permit_empty|alpha_space|min_length[4]|max_length[155]',
                'errors' => [
                    'alpha_space' => 'The {field} only allow alphabetical characters and spaces.'
                ]
            ],
            'level' => [
                'label' => 'roles',
                'rules' => 'required|numeric|in_list[1,2,3]',
                'errors' => [
                    'in_list' => 'Invalid {field}.'
                ]
            ],
            'status' => [
                'label' => 'status',
                'rules' => 'required|numeric|in_list[1,2,3]',
                'errors' => [
                    'in_list' => 'Invalid {field} account.'
                ]
            ],
            'saldo' => [
                'label' => 'saldo',
                'rules' => $saldoRule,
                'errors' => [
                    'greater_than_equal_to' => 'Invalid currency, cannot set to minus.',
                    'less_than_equal_to' => 'Admins are not allowed to assign more than 30 points to Resellers.'
                ]
            ],
            'uplink' => [
                'label' => 'uplink',
                'rules' => 'required|alpha_numeric|is_not_unique[users.username,username,]',
                'errors' => [
                    'is_not_unique' => 'Uplink not registered anymore.'
                ]
            ],
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Something wrong! Please check the form');
        } else {
            $fullname = $this->request->getPost('fullname');
            $level = $this->request->getPost('level');
            $status = $this->request->getPost('status');
            $saldo = $this->request->getPost('saldo');
            $uplink = $this->request->getPost('uplink');
            $expiration = $this->request->getPost('expiration');
            $data_update = [
                'username' => $username,
                'fullname' => esc($fullname),
                'level' => $level,
                'status' => $status,
                'saldo' => (($saldo < 1) ? 0 : $saldo),
                'uplink' => $uplink,
                'expiration_date' => $expiration
            ];

            if ($user->level == 1 && $this->request->getPost('two_factor_chat_id') !== null) {
                $data_update['two_factor_chat_id'] = trim($this->request->getPost('two_factor_chat_id'));
            }

            $update = $model->update($userid, $data_update);
            if ($update) {
                return redirect()->back()->with('msgSuccess', "Successfuly update $target->username.");
            }
        }
    }

    public function settings()
    {
        if ($this->request->getPost('password_form'))
            return $this->passwd_act();

        if ($this->request->getPost('fullname_form'))
            return $this->fullname_act();

        $user = $this->user;
        
        // Auto-generate seller_key if missing
        if (empty($user->seller_key)) {
            $db = \Config\Database::connect();
            if ($db->fieldExists('seller_key', 'users')) {
                 $newKey = 'seller_' . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
                 $this->model->update($this->userid, ['seller_key' => $newKey]);
                 $user->seller_key = $newKey;
            }
        }
        
        $resetLinkModel = new ResetLinkModel();
        $resetLinks = $resetLinkModel->where('user_id', $this->userid)->orderBy('created_at', 'DESC')->findAll();

        $validation = Services::validation();
        $data = [
            'title' => 'Settings',
            'user' => $user,
            'time' => $this->time,
            'reset_links' => $resetLinks,
            'validation' => $validation
        ];
        
        return view('User/settings', $data);
    }
        
    public function lib()
    {
        $user  = $this->user;
        if ($this->request->getPost('lib_form'))
           return $this->lib();
        $user = $this->user;
        $validation = Services::validation();
        $data = [
            'title' => 'lib',
            'user' => $user,
            'time' => $this->time,
            'validation' => $validation
        ];
        return view('Server/lib', $data);
    }
        
    public function Server()
    {
        $user = $this->user;
        if (($user->level == 1) || ($user->level == 2)) 
        {
        
        if ($this->request->getPost('modname_form'))
            
            return $this->modname_act();
            
        if ($this->request->getPost('status_form'))
            return $this->status_act();
        }
        if ($user->level == 1)
        {
        if ($this->request->getPost('feature_form'))
            return $this->feature_act();
        if ($this->request->getPost('password_form'))
            return $this->passwd_act();
        }
        if (($user->level == 1) || ($user->level == 2)) 
        {
        if ($this->request->getPost('_ftext'))
            return $this->_ftext_act();

        if ($this->request->getPost('fullname_form'))
            return $this->fullname_act();

        }
        $user = $this->user;
        
        $validation = Services::validation();
        $data = [
            'title' => 'Server',
            'user' => $user,
            'time' => $this->time,
            'validation' => $validation
        ];
        
        //==================================Mod Name======================//
        
        $id = 1;
	    
	    $model= new Server();
	    
	    $data['row'] = $model->where('id',$id)->first();
	    
	     if (($user->level == 1) || ($user->level == 2)){
		return view('Server/Server',$data);
	     }
	     else {
	         
	         return redirect()->to('dashboard')->with('msgWarning','Access Denied');
	     }
    }
    
    private function _ftext_act()
    {
        $id = 1;
	    $model= new _ftext();
	    $myinput = $this->request->getPost('_ftext');
	    $status = $this->request->getPost('_ftextr');
	    $wow = '';
	if($status == "Safe"){
            $wow .= "Safe";
        }else{
            $wow .= "Anti-Cheat is High..!!";
        }
      $data = ['_ftext' => $myinput,'_status' => $wow];
	    $model->update($id,$data);
	    return redirect()->back()->with('msgSuccess', 'Successfuly Changed Mod Floating And Status.');
    }
    
    private function status_act()
    {
        $id = 1;
	    $model= new onoff();
	    $myinput = $this->request->getPost('myInput');
	    $wow = '';
	    if(isset($_POST['radios']) && $_POST['radios'] == 'on') 
        {
            $wow .= "on";
        }
        else
        {
            $wow .= "off";
        }
	    $data = [
	        'status' => $wow,
    	    'myinput' => $myinput
	    ];
	    $model->update($id, $data);
	    return redirect()->back()->with('msgSuccess', 'Mod Status Successfuly Changed.');
    }
    
    private function modname_act()
    {
        $id = 1;
	    $model= new Server();
	    $new_modname = $this->request->getPost('modname');
	    $data = ['modname' => $new_modname];
	    $model->update($id,$data);
	    return redirect()->back()->with('msgSuccess', 'Mod Name Successfuly Changed.');
    }
    
    private function feature_act()
    {
        $id = 1;
	    $model = new Feature();
//=================================================//
	    if(isset($_POST['ESP']) && $_POST['ESP'] == 'on') 
        {
            $new_espvalue = "on";
        }
        else
        {
            $new_espvalue = "off";
        }
//=================================================//
	    if(isset($_POST['Item']) && $_POST['Item'] == 'on') 
        {
            $new_Itemvalue = "on";
        }
        else
        {
            $new_Itemvalue = "off";
        }
//=================================================//
	    if(isset($_POST['AIM']) && $_POST['AIM'] == 'on') 
        {
            $new_aimvalue = "on";
        }
        else
        {
            $new_aimvalue = "off";
        }
//=================================================//
	    if(isset($_POST['SilentAim']) && $_POST['SilentAim'] == 'on') 
        {
            $new_SilentAimvalue = "on";
        }
        else
        {
            $new_SilentAimvalue = "off";
        }
//=================================================//
	    if(isset($_POST['BulletTrack']) && $_POST['BulletTrack'] == 'on') 
        {
            $new_BulletTrackvalue = "on";
        }
        else
        {
            $new_BulletTrackvalue = "off";
        }
//=================================================//
	    if(isset($_POST['Memory']) && $_POST['Memory'] == 'on') 
        {
            $new_Memoryvalue = "on";
        }
        else
        {
            $new_Memoryvalue = "off";
        }
//=================================================//
	    if(isset($_POST['Floating']) && $_POST['Floating'] == 'on') 
        {
            $new_Floatingvalue = "on";
        }
        else
        {
            $new_Floatingvalue = "off";
        }
//=================================================//
	    if(isset($_POST['Setting']) && $_POST['Setting'] == 'on') 
        {
            $new_Settingvalue = "on";
        }
        else
        {
            $new_Settingvalue = "off";
        }
//=================================================//
	    $data = [
    	    'ESP' => $new_espvalue,
    	    'Item' => $new_Itemvalue,
    	    'SilentAim' => $new_SilentAimvalue,
    	    'AIM' => $new_aimvalue,
    	    'BulletTrack' => $new_BulletTrackvalue,
    	    'Memory' => $new_Memoryvalue,
    	    'Floating' => $new_Floatingvalue,
    	    'Setting' => $new_Settingvalue
	    ];
	    $model->update($id,$data);
	    return redirect()->back()->with('msgSuccess', 'Mod Feature Stats Changed.');
    }
    
    private function passwd_act()
    {
        $current = $this->request->getPost('current');
        $password = $this->request->getPost('password');

        $user = $this->user;
        $currHash = create_password($current, false);
        $validation = Services::validation();

        if (!password_verify($currHash, $user->password)) {
            $msg = "Wrong current password.";
            $validation->setError('current', $msg);
        } elseif ($current == $password) {
            $msg = "Nothing to change.";
            $validation->setError('password', $msg);
        }

        $form_rules = [
            'current' => [
                'label' => 'current',
                'rules' => 'required|min_length[6]|max_length[45]',
            ],
            'password' => [
                'label' => 'password',
                'rules' => 'required|min_length[6]|max_length[45]',
            ],
            'password2' => [
                'label' => 'confirm',
                'rules' => 'required|min_length[6]|max_length[45]|matches[password]',
                'errors' => [
                    'matches' => '{field} not match, check the {field}.'
                ]
            ],
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Something wrong! Please check the form');
        } else {
            $newPassword = create_password($password);
            $this->model->update(session('userid'), ['password' => $newPassword]);
            return redirect()->back()->with('msgSuccess', 'Password Successfuly Changed.');
        }
    }
    
    private function fullname_act()
    {
        $user = $this->user;
        $newName = $this->request->getPost('fullname');

        if ($user->fullname == $newName) {
            $validation = Services::validation();
            $msg = "Nothing to change.";
            $validation->setError('fullname', $msg);
        }

        $form_rules = [
            'fullname' => [
                'label' => 'name',
                'rules' => 'required|alpha_space|min_length[4]|max_length[155]',
                'errors' => [
                    'alpha_space' => 'The {field} only allow alphabetical characters and spaces.'
                ]
            ]
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->back()->withInput()->with('msgDanger', 'Failed! Please check the form');
        } else {
            $this->model->update(session('userid'), ['fullname' => esc($newName)]);
            return redirect()->back()->with('msgSuccess', 'Account Detail Successfuly Changed.');
        }
    }
    public function api_reset($username)
    {
        $user = $this->user;
        
        // Security check: Only allow the user to reset their own key or owner
        if ($user->username !== $username && $user->level != 1) {
            return redirect()->to('dashboard')->with('msgDanger', 'Access Denied!');
        }
        
        // Get target user
        $target = $this->model->getUser($username, 'username');
        if (!$target) {
            return redirect()->back()->with('msgDanger', 'User not found.');
        }

        $newKey = 'seller_' . substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
        $this->model->update($target->id_users, ['seller_key' => $newKey]);
        
        return redirect()->back()->with('msgSuccess', 'API Key has been reset successfully.');
    }

    public function create_reset_link()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $type = $this->request->getPost('type');
        $expiration = $this->request->getPost('expiration');

        $token = bin2hex(random_bytes(32));
        $expires_at = null;

        if ($type === 'temporary') {
            $hours = (int) $expiration;
            $expires_at = date('Y-m-d H:i:s', strtotime("+$hours hours"));
        }

        $model = new ResetLinkModel();
        $data = [
            'token' => $token,
            'type' => $type,
            'expires_at' => $expires_at,
            'user_id' => $this->userid
        ];

        if ($model->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'url' => site_url('reset?token=' . $token),
                'expires_at' => $expires_at ? date('d/m/Y, H:i:s', strtotime($expires_at)) : 'Permanent'
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to create link']);
    }

    public function reset_portal()
    {
        $token = $this->request->getGet('token');
        if (!$token) {
            return "Invalid or missing token.";
        }

        $linkModel = new ResetLinkModel();
        $link = $linkModel->getValidToken($token);

        if (!$link) {
            return "This reset link is invalid or has expired.";
        }

        if ($this->request->getPost()) {
            return $this->process_reset_key($link);
        }

        return view('User/reset_portal', ['token' => $token]);
    }

    private function process_reset_key($link)
    {
        $userKey = $this->request->getPost('user_key');
        if (!$userKey) {
            return redirect()->back()->with('msgDanger', 'Please enter a license key.');
        }

        $keysModel = new KeysModel();
        $keyData = $keysModel->getKeys($userKey);

        if (!$keyData) {
            return redirect()->back()->with('msgDanger', 'Invalid license key.');
        }

        // Check if the key belongs to the link creator or if creator is admin
        $creator = $this->model->getUser($link['user_id']);
        if (!$creator) {
            return redirect()->back()->with('msgDanger', 'The creator of this link no longer exists.');
        }

        if ($creator->level != 1 && $keyData->registrator !== $creator->username) {
            return redirect()->back()->with('msgDanger', 'You cannot reset this key using this link.');
        }

        if (!$keyData->devices) {
            return redirect()->back()->with('msgWarning', 'This key is already reset.');
        }

        $history = new \App\Models\HistoryModel();
        if ($history->getResetCount($keyData->id_keys) >= 2) {
            return redirect()->back()->with('msgDanger', 'Limit reached: You can only reset this key twice in a 24-hour period.');
        }

        $keysModel->update($keyData->id_keys, ['devices' => NULL]);

        // Log history
        $history->insert([
            'keys_id' => $keyData->id_keys,
            'user_do' => 'Public Reset (' . $creator->username . ')',
            'info' => "HWID Reset via Link|{$userKey}"
        ]);

        return redirect()->back()->with('msgSuccess', 'HWID reset successfully! You can now use the key on a new device.');
    }

    public function delete_reset_link($id)
    {
        $model = new ResetLinkModel();
        $link = $model->find($id);

        if (!$link || $link['user_id'] != $this->userid) {
            return redirect()->back()->with('msgDanger', 'Link not found or access denied.');
        }

        $model->delete($id);
        return redirect()->back()->with('msgSuccess', 'Reset link deleted successfully.');
    }

    // ═══════════════════════════════════════════════════════
    //  TELEGRAM BOT — Per-User Token Settings
    // ═══════════════════════════════════════════════════════

    /**
     * Save the user's personal Telegram bot token.
     * Called via POST /settings/save-telegram (AJAX)
     */
    public function save_telegram_token()
    {
        $user        = $this->user;
        $token       = trim($this->request->getPost('telegram_bot_token') ?? '');
        $telegram_id = trim($this->request->getPost('telegram_id') ?? '');

        if (empty($token)) {
            // Allow clearing the token
            $db = \Config\Database::connect();
            $db->table('users')->where('id_users', $this->userid)->update([
                'telegram_bot_token' => null,
                'telegram_id'        => $telegram_id ?: null
            ]);
            return $this->response->setJSON(['success' => true, 'message' => 'Bot configuration saved.']);
        }

        // Basic format check: digits:alphanum (min length 25)
        if (!preg_match('/^\d+:[A-Za-z0-9_\-]{25,}$/', $token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid token format. Example: 123456789:ABCdef...']);
        }

        try {
            $db = \Config\Database::connect();
            $db->table('users')->where('id_users', $this->userid)->update([
                'telegram_bot_token' => $token,
                'telegram_id'        => $telegram_id ?: null
            ]);
            return $this->response->setJSON(['success' => true, 'message' => 'Bot token & Owner Chat ID saved successfully!']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()]);
        }
    }

    /**
     * Test the user's Telegram bot token via getMe API.
     * Called via POST /settings/test-telegram (AJAX)
     */
    public function test_telegram_token()
    {
        $token = trim($this->request->getPost('telegram_bot_token') ?? '');

        if (empty($token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Please enter a bot token first.']);
        }

        $client = \Config\Services::curlrequest();
        try {
            $res  = $client->get("https://api.telegram.org/bot{$token}/getMe", ['http_errors' => false]);
            $body = json_decode($res->getBody(), true);

            if (!empty($body['ok']) && $body['ok'] === true) {
                $botName     = $body['result']['first_name']    ?? 'Bot';
                $botUsername = $body['result']['username']      ?? '';
                return $this->response->setJSON([
                    'success'  => true,
                    'message'  => "✅ Connected! Bot: <b>{$botName}</b> (@{$botUsername})",
                    'bot_name' => $botName,
                    'bot_user' => $botUsername,
                ]);
            } else {
                $desc = $body['description'] ?? 'Unknown error';
                return $this->response->setJSON(['success' => false, 'message' => "❌ Invalid token: {$desc}"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Register the per-user webhook URL with Telegram automatically.
     * Called via POST /settings/register-webhook (AJAX)
     */
    public function register_telegram_webhook()
    {
        $user  = $this->user;
        $token = trim($this->request->getPost('telegram_bot_token') ?? '');

        if (empty($token)) {
            // Try to load from DB
            $dbUser = $this->model->getUser($this->userid);
            $token  = $dbUser->telegram_bot_token ?? '';
        }

        if (empty($token)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No bot token found. Please save your token first.']);
        }

        // Ensure seller_key exists
        $dbUser = $this->model->getUser($this->userid);
        if (empty($dbUser->seller_key)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Seller key not found. Please refresh settings.']);
        }

        $webhookUrl = rtrim(base_url(), '/') . '/webhook/' . $dbUser->seller_key;

        $client = \Config\Services::curlrequest();
        try {
            $res  = $client->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'json'        => ['url' => $webhookUrl],
                'http_errors' => false,
            ]);
            $body = json_decode($res->getBody(), true);

            if (!empty($body['ok']) && $body['ok'] === true) {
                return $this->response->setJSON([
                    'success'     => true,
                    'message'     => '✅ Webhook registered successfully!',
                    'webhook_url' => $webhookUrl,
                ]);
            } else {
                $desc = $body['description'] ?? 'Unknown error';
                return $this->response->setJSON(['success' => false, 'message' => "❌ Failed: {$desc}"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }
}