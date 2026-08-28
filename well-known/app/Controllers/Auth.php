<?php

namespace App\Controllers;

use App\Models\CodeModel;
use App\Models\UserModel;
use CodeIgniter\Config\Services;

class Auth extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // LOGIN PAGE
    public function login()
    {
        if (session()->has('userid')) return redirect()->to('dashboard');

        if ($this->request->getPost()) return $this->login_action();

        if (session()->has('otp_pending_userid')) return redirect()->to('verify-otp');

        return view('Auth/login', [
            'title' => 'Login',
            'validation' => Services::validation()
        ]);
    }

    // LOGIN ACTION
    private function login_action()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $stay_log = $this->request->getPost('stay_log');

        $form_rules = [
            'username' => 'required|alpha_numeric|min_length[4]|max_length[25]|is_not_unique[users.username]',
            'password' => 'required|min_length[3]|max_length[45]',
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->route('login')->withInput()->with('msgDanger', 'Please check the form.');
        }

        $cekUser = $this->userModel->getUser($username, 'username');

        if (!$cekUser || !password_verify(create_password($password, false), $cekUser->password)) {
            return redirect()->route('login')->withInput()->with('msgDanger', 'Wrong username or password.');
        }

        // If user is Owner (level == 1) or Admin (level == 2), require OTP verification
        if ($cekUser->level == 1 || $cekUser->level == 2) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
            session()->set([
                'otp_pending_userid' => $cekUser->id_users,
                'otp_pending_uname'  => $cekUser->username,
                'otp_code'           => $otp,
                'otp_expires'        => time() + 300, // 5 minutes validity
                'otp_stay_log'       => $stay_log,
            ]);

            $sent = $this->sendTelegramOTP($cekUser, $otp);

            $msg = $sent 
                ? 'OTP verification code sent to your Telegram! Please enter it to complete login.' 
                : 'OTP verification code generated. Please check your Telegram account.';

            return redirect()->to('verify-otp')->with('msgSuccess', $msg);
        }

        // For Reseller users (level == 3)
        $time = new \CodeIgniter\I18n\Time;
        session()->set([
            'userid' => $cekUser->id_users,
            'unames' => $cekUser->username,
            'time_login' => $stay_log ? $time::now()->addHours(24) : $time::now()->addMinutes(30),
            'time_since' => $time::now(),
        ]);

        return redirect()->to('dashboard');
    }

    // OTP VERIFY PAGE & ACTION
    public function verify_otp()
    {
        if (session()->has('userid')) return redirect()->to('dashboard');
        if (!session()->has('otp_pending_userid')) return redirect()->to('login');

        if ($this->request->getPost()) {
            $submitted_otp = trim($this->request->getPost('otp_code'));
            $session_otp   = session()->get('otp_code');
            $otp_expires   = session()->get('otp_expires');

            if (time() > $otp_expires) {
                return redirect()->to('verify-otp')->with('msgDanger', 'OTP code has expired. Please click resend to get a new code.');
            }

            if ($submitted_otp !== $session_otp) {
                return redirect()->to('verify-otp')->with('msgDanger', 'Invalid OTP code. Please try again.');
            }

            // OTP Verified successfully - Complete Login
            $userid   = session()->get('otp_pending_userid');
            $unames   = session()->get('otp_pending_uname');
            $stay_log = session()->get('otp_stay_log');

            // Clear temporary OTP session data
            session()->remove(['otp_pending_userid', 'otp_pending_uname', 'otp_code', 'otp_expires', 'otp_stay_log']);

            $time = new \CodeIgniter\I18n\Time;
            session()->set([
                'userid' => $userid,
                'unames' => $unames,
                'time_login' => $stay_log ? $time::now()->addHours(24) : $time::now()->addMinutes(30),
                'time_since' => $time::now(),
            ]);

            return redirect()->to('dashboard')->with('msgSuccess', '2FA authentication verified successfully.');
        }

        return view('Auth/verify_otp', [
            'title' => 'Verify 2FA OTP',
            'validation' => Services::validation()
        ]);
    }

    // RESEND OTP ACTION
    public function resend_otp()
    {
        if (session()->has('userid')) return redirect()->to('dashboard');
        if (!session()->has('otp_pending_userid')) return redirect()->to('login');

        $userid = session()->get('otp_pending_userid');
        $cekUser = $this->userModel->getUser($userid);

        if (!$cekUser) {
            session()->remove(['otp_pending_userid', 'otp_pending_uname', 'otp_code', 'otp_expires', 'otp_stay_log']);
            return redirect()->to('login');
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));
        session()->set('otp_code', $otp);
        session()->set('otp_expires', time() + 300);

        $sent = $this->sendTelegramOTP($cekUser, $otp);

        $msg = $sent 
            ? 'A new OTP code has been sent to your Telegram account.' 
            : 'New OTP code generated. Please check your Telegram account.';

        return redirect()->to('verify-otp')->with('msgSuccess', $msg);
    }

    // SEND TELEGRAM OTP HELPER
    private function sendTelegramOTP($user, $otp)
    {
        // Dedicated 2FA Telegram Bot Token ONLY
        $botToken = env('2FA_TELEGRAM_BOT_TOKEN') ?: env('TELEGRAM_2FA_BOT_TOKEN');

        // Dedicated 2FA Chat ID set strictly by Owner
        $chatId   = !empty($user->two_factor_chat_id) 
                 ? $user->two_factor_chat_id 
                 : ($user->level == 1 ? env('OWNER_TELEGRAM_ID') : '');

        if (empty($botToken) || empty($chatId) || $botToken === 'YOUR_BOT_TOKEN_HERE' || $chatId === 'YOUR_OWNER_ID_HERE') {
            log_message('warning', '2FA Telegram Bot Token or 2FA Chat ID is not configured for user ID: ' . $user->id_users);
            return false;
        }

        $ip = $this->request->getIPAddress();
        $timeStr = date('Y-m-d h:i:s A');
        $roleLabel = ($user->level == 1) ? 'OWNER' : 'ADMIN';

        $message = "🔐 <b>RAPIDCORE {$roleLabel} 2FA VERIFICATION CODE</b>\n\n"
                 . "Your OTP Code: <code>{$otp}</code>\n\n"
                 . "<b>Login Details:</b>\n"
                 . "👤 <b>User:</b> {$user->username} ({$roleLabel})\n"
                 . "🌐 <b>IP Address:</b> <code>{$ip}</code>\n"
                 . "⏰ <b>Time:</b> {$timeStr}\n\n"
                 . "⚠️ <i>This OTP expires in 5 minutes. Do not share this code with anyone.</i>";

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'chat_id'    => $chatId,
            'text'       => $message,
            'parse_mode' => 'HTML'
        ];

        try {
            $client = \Config\Services::curlrequest();
            $response = $client->post($url, [
                'json' => $data,
                'http_errors' => false,
                'timeout' => 5
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            log_message('error', 'Failed to send OTP via Telegram: ' . $e->getMessage());
            return false;
        }
    }

    // REGISTER PAGE
    public function register()
    {
        if (session()->has('userid')) return redirect()->to('dashboard');

        if ($this->request->getPost()) return $this->register_action();

        return view('Auth/register', [
            'title' => 'Register',
            'validation' => Services::validation()
        ]);
    }

    // REGISTER ACTION
    public function register_action()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $password2 = $this->request->getPost('password2');
        $referral = $this->request->getPost('referral');

        $form_rules = [
            'username' => 'required|alpha_numeric|min_length[4]|max_length[25]|is_unique[users.username]',
            'password' => 'required|min_length[3]|max_length[45]',
            'password2' => 'required|matches[password]',
            'referral' => 'required|min_length[6]|alpha_numeric'
        ];

        if (!$this->validate($form_rules)) {
            return redirect()->route('register')->withInput()->with('msgDanger', 'Please check the form.');
        }

        $mCode = new CodeModel();
        $rCheck = $mCode->checkCode($referral);

        if (!$rCheck || $rCheck->used_by) {
            return redirect()->route('register')->withInput()->with('msgDanger', 'Invalid or used referral code.');
        }

        $hashPassword = create_password($password);
        $data_register = [
            'username' => $username,
            'password' => $hashPassword,
            'saldo' => $rCheck->set_saldo ?: 0,
            'uplink' => $rCheck->created_by
        ];

        $ids = $this->userModel->insert($data_register, true);
        if ($ids) $mCode->useReferral($referral, $username);

        return redirect()->to('login')->with('msgSuccess', 'Registration successful!');
    }

    // LOGOUT
    public function logout()
    {
        session()->remove(['userid', 'unames', 'time_login', 'time_since', 'otp_pending_userid', 'otp_pending_uname', 'otp_code', 'otp_expires', 'otp_stay_log']);
        session()->setFlashdata('msgSuccess', 'Logout successful.');
        return redirect()->to('login');
    }
}