<?php

namespace App\Controllers;

use App\Models\CodeModel;
use App\Models\UserModel;
use CodeIgniter\Config\Services;

class Auth extends BaseController
{
    protected $user;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        /* ---------------------------- Debugmode --------------------------- */
        $a = $this->userModel->getUser(session('userid'));
        dd($a, session());
    }

    public function login()
    {
        if (session()->has('userid'))
            return redirect()->to('dashboard');

        if ($this->request->getPost())
            return $this->login_action();

        $data = [
            'title' => 'Login',
            'validation' => Services::validation(),
        ];
        return view('Auth/login', $data);
    }

    public function register()
    {
        if (session()->has('userid'))
            return redirect()->to('dashboard');

        if ($this->request->getPost())
            return $this->register_action();
        $data = [
            'title' => 'Register',
            'validation' => Services::validation(),
        ];
        return view('Auth/register', $data);
    }

    private function login_action()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $stay_log = $this->request->getPost('stay_log');

        $form_rules = [
            'username' => [
                'label' => 'username',
                'rules' => 'required|min_length[3]|max_length[50]',
            ],
            'password' => [
                'label' => 'password',
                'rules' => 'required|min_length[4]|max_length[50]',
            ],
            'stay_log' => [
                'rules' => 'permit_empty|max_length[3]'
            ]
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->route('login')->withInput()->with('msgDanger', '<strong>Failed!</strong> Please check the form.');
        } else {
            $validation = Services::validation();
            $cekUser = $this->userModel->getUser($username, 'username');
            if ($cekUser) {
                $hashPassword = create_password($password, false);
                if (password_verify($hashPassword, $cekUser->password) || password_verify($password, $cekUser->password) || md5($password) === $cekUser->password) {
                    // Check contract expiry if applicable
                    if (method_exists($this->userModel, 'isContractExpired') && $this->userModel->isContractExpired($cekUser)) {
                        return redirect()->route('login')->withInput()->with('msgDanger', '<strong>Error!</strong> Your contract has expired. Please contact admin.');
                    }

                    $time = new \CodeIgniter\I18n\Time;
                    $data = [
                        'userid' => $cekUser->id_users,
                        'unames' => $cekUser->username,
                        'time_login' => $stay_log ? $time::now()->addHours(24) : $time::now()->addMinutes(30),
                        'time_since' => $time::now(),
                    ];
                    session()->set($data);
                    return redirect()->to('dashboard');
                } else {
                    $validation->setError('password', 'Incorrect password, please try again.');
                    return redirect()->route('login')->withInput()->with('msgDanger', '<strong>Failed!</strong> Incorrect password.');
                }
            } else {
                return redirect()->route('login')->withInput()->with('msgDanger', '<strong>Failed!</strong> Username not found.');
            }
        }
    }

    public function register_action()
{
    $username = $this->request->getPost('username');
    $password = $this->request->getPost('password');
    $referral = $this->request->getPost('referral');

    $form_rules = [
        'username' => [
            'label' => 'username',
            'rules' => 'required|alpha_numeric|min_length[4]|max_length[25]|is_unique[admin.username]',
            'errors' => [
                'is_unique' => 'The {field} has been taken.'
            ]
        ],
        'password' => [
            'label' => 'password',
            'rules' => 'required|min_length[6]|max_length[45]',
        ],
        'password2' => [
            'label' => 'password',
            'rules' => 'required|min_length[6]|max_length[45]|matches[password]',
            'errors' => [
                'matches' => '{field} does not match, check the {field}.'
            ]
        ],
        'referral' => [
            'label' => 'referral',
            'rules' => 'required|min_length[6]|alpha_numeric',
        ]
    ];

    if (!$this->validate($form_rules)) {
        return redirect()->route('register')
               ->withInput()
               ->with('msgDanger', '<strong>Error</strong> Please check the form.');
    }

    $db = \Config\Database::connect();
    $referralCheck = $db->table('referral_code')
                       ->where('orig_code', $referral)
                       ->orWhere('code', create_password($referral, false))
                       ->get()
                       ->getRow();

    $validation = Services::validation();
    
    if (!$referralCheck) {
        $validation->setError('referral', 'Incorrect referral code, please try again.');
        return redirect()->route('register')
               ->withInput()
               ->with('msgDanger', '<strong>Error</strong> Invalid referral code.');
    }

    if ($referralCheck->used_by) {
        $validation->setError('referral', "Incorrect referral code, the code has already been used by &middot; $referralCheck->used_by.");
        return redirect()->route('register')
               ->withInput()
               ->with('msgDanger', '<strong>Error</strong> Referral code already used.');
    }

    // Kiểm tra xem mã referral có hết hạn không
    $codeModel = new CodeModel();
    if ($codeModel->isCodeExpired($referralCheck)) {
        $validation->setError('referral', 'Referral code has expired.');
        return redirect()->route('register')
               ->withInput()
               ->with('msgDanger', '<strong>Error</strong> Referral code has expired.');
    }

    // Tạo tài khoản với level từ ref code
    $hashPassword = create_password($password);
    $data_register = [
        'username' => $username,
        'password' => $hashPassword,
        'saldo' => $referralCheck->set_saldo ?: 0,
        'level' => $referralCheck->level,
        'uplink' => $referralCheck->created_by,
        'contract_expired_at' => $referralCheck->contract_expired_at ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $inserted = $this->userModel->insert($data_register);
    
    if ($inserted) {
        // Đánh dấu ref code đã được sử dụng
        $db->table('referral_code')
           ->where('id_reff', $referralCheck->id_reff)
           ->update([
                'used_by' => $username,
                'updated_at' => date('Y-m-d H:i:s')
           ]);

        return redirect()->to('login')
               ->with('msgSuccess', 'Registration successful! You can now login.');
    }

    return redirect()->route('register')
           ->withInput()
           ->with('msgDanger', '<strong>Error</strong> Failed to create account.');
}

    public function logout()
    {
        if (session()->has('userid')) {
            $unset = ['userid', 'unames', 'time_login', 'time_since'];
     
       session()->remove($unset);
            session()->setFlashdata('msgSuccess', 'Logout successful.');
        }
        return redirect()->to('login');
    }
}
