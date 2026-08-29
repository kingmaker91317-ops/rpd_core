<?php

namespace App\Models;

use CodeIgniter\Model;
use \Hermawan\DataTables\DataTable;

class UserModel extends Model
{
    protected $table      = 'admin';
    protected $primaryKey = 'id_users';
    protected $allowedFields = [
        'username',
        'fullname',
        'saldo',
        'level',
        'status',
        'uplink',
        'password',
        'user_ip',
        'api_token',
        'menu_logo',
        'menu_name',
        'menu_subtitle',
        'getkey_steps',
        'getkey_telegram',
        'getkey_buy_url',
        'getkey_buy_ib',
        'getkey_support_tele',
        'getkey_auto_buy',
        'shortlink_service',
        'getkey_games',
        'contract_expired_at'
    ];
    protected $useTimestamps = true;
    
    /*=================================================================*/
    
    protected $table_m      = 'modname';
    protected $primaryKey_m = 'id';
    protected $allowedFields_m = ['modname'];
    
    
    /*=================================================================*/

    public function getUser($userid = false, $where = 'default')
    {
        $userid = $userid ?: session()->userid;
        $where = ($where == 'default' ? 'id_users' : $where);
        try {
            $wfind = $this->db->table('admin')->where($where, $userid)->get()->getFirstRow();
        } catch (\Throwable $e) {
            $wfind = null;
        }
        if (!$wfind) {
            try {
                $wfind = $this->db->table('users')->where($where, $userid)->get()->getFirstRow();
            } catch (\Throwable $e) {
                $wfind = null;
            }
        }
        return $wfind ?: NULL;
    }

       public function getUserList($select = "*")
    {
        $user = $this->getUser();
        $this->select($select);
        
        // Tenant xem được Reseller của mình và chính mnh
        if ($user && $user->level == 3) {
            $this->groupStart()
                 ->where('uplink', $user->username)
                 ->where('level', 2)
                 ->orWhere('id_users', $user->id_users)
                 ->groupEnd();
        }
        
        return $this->get()
                   ->getResultObject();
    }

public function API_getUser()
{
    $connect = db_connect();
    $builder = $connect->table('admin');

    $user = $this->getUser();
    
    // Nếu là Tenant
    if ($user->level == 3) {
        $builder->groupStart()
                ->where('uplink', $user->username)
                ->where('level', 2)
                ->orWhere('id_users', $user->id_users)
                ->groupEnd();
    }
    // Nếu không phải Admin
    elseif ($user->level != 1) {
        $builder->where('uplink', $user->username);
    }

    $builder->select('
        admin.id_users as id, 
        username, 
        fullname, 
        saldo, 
        level, 
        status, 
        uplink,
        created_at,
        updated_at
    ');

    return DataTable::of($builder)
        ->setSearchableColumns(['username', 'fullname', 'saldo', 'uplink'])
        ->format('fullname', function ($value) {
            return $value ? esc(word_limiter($value, 1, '')) : '';
        })
        ->format('level', function ($value) {
            return getLevel($value);
        })
        ->format('created_at', function($value) {
            return date('Y-m-d H:i:s', strtotime($value));
        })
        ->format('updated_at', function($value) {
            return date('Y-m-d H:i:s', strtotime($value));
        })
        ->toJson(true);
}


    // Thêm method mi để Tenant quản lý Reseller
    public function updateReseller($userId, $data)
    {
        $user = $this->getUser();
        
        // Chỉ cho php Tenant update Reseller ca h
        if ($user->level == 3) {
            $reseller = $this->where('id_users', $userId)
                           ->where('uplink', $user->username)
                           ->where('level', 2)
                           ->first();
            
            if ($reseller) {
                // Không cho phép thay đổi level và uplink
                unset($data['level']);
                unset($data['uplink']);
                
                return $this->update($userId, $data);
            }
            return false;
        }
        
        // Admin có thể update tất c
        return $this->update($userId, $data);
    }

    // Thêm method kiểm tra quyền truy cập Reseller
    public function canAccessReseller($resellerId)
    {
        $user = $this->getUser();
        
        // Admin c thể truy cập tất cả
        if ($user->level == 1) {
            return true;
        }
        
        // Tenant ch truy cập được Reseller của mình
        if ($user->level == 3) {
            $reseller = $this->where('id_users', $resellerId)
                           ->where('uplink', $user->username)
                           ->where('level', 2)
                           ->first();
            return $reseller ? true : false;
        }
        
        return false;
    }

    public function checkAuthFilter()
    {
        $time = new \CodeIgniter\I18n\Time;
        $session = session();
        $time_ex = $session->time_login;
        if ($time::now()->isBefore($time_ex)) {
            $userCek = $this->getUser($session->userid);
            if ($userCek->level > 3) {
                $msg = 'Level account invalid!';
            } elseif ($userCek->status != 1) {
                $msg = 'Status account changed!';
            } else {
                return $userCek;
            }
        } else {
            $msg = 'Session account expired!';
        }
        return $this->AuthSessionLogout($msg);
    }

    public function AuthSessionLogout($msg = 'Session terminate')
    {
        $list = ['userid', 'unames', 'time_login', 'time_since'];
        session()->remove($list);
        return redirect()->to('login')->with('msgDanger', $msg);
    }

    /**
     * Kiểm tra hợp đồng của seller đã hết hạn chưa
     * @param object|array $seller - User object hoặc array
     * @return bool - true nếu hết hạn, false nếu còn hạn hoặc không set
     */
    public function isContractExpired($seller)
    {
        if (!$seller) return false;
        
        $seller = (array) $seller;
        
        // Level 1 (Admin) không bao giờ hết hạn
        if ($seller['level'] == 1) return false;
        
        // Nếu không set contract_expired_at thì không hết hạn
        if (empty($seller['contract_expired_at']) || $seller['contract_expired_at'] === null) {
            return false;
        }
        
        // Kiểm tra ngày hết hạn
        $expiredDate = strtotime($seller['contract_expired_at']);
        $now = time();
        
        return $expiredDate < $now;
    }
}

