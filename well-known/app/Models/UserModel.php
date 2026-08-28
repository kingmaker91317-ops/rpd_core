<?php

namespace App\Models;

use CodeIgniter\Model;
use \Hermawan\DataTables\DataTable;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id_users';
    protected $allowedFields = ['username', 'fullname', 'saldo', 'level', 'status', 'uplink', 'password', 'telegram_id', 'seller_key', 'user_ip', 'expiration_date', 'telegram_bot_token', 'two_factor_chat_id'];
    protected $useTimestamps = true;

    public function __construct()
    {
        parent::__construct();
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('users') && !$db->fieldExists('two_factor_chat_id', 'users')) {
                $db->query("ALTER TABLE users ADD COLUMN two_factor_chat_id VARCHAR(100) NULL DEFAULT NULL");
            }
        } catch (\Exception $e) {
            log_message('error', 'Error adding two_factor_chat_id column: ' . $e->getMessage());
        }
    }
    
    /*=================================================================*/
    
    protected $table_m      = 'modname';
    protected $primaryKey_m = 'id';
    protected $allowedFields_m = ['modname'];
    
    
    /*=================================================================*/

    public function getUser($userid = false, $where = 'default')
    {
        $userid = $userid ?: session()->userid;
        $where = ($where == 'default' ? 'id_users' : $where);
        $wfind = $this->where($where, $userid)
            ->get()
            ->getFirstRow();
        return $wfind ?: NULL;
    }

    public function getUserList($select = "*")
    {
        $this->select($select);
        return $this->get()
            ->getResultObject();
    }

    public function API_getUser()
    {
        $connect = db_connect();
        $builder = $connect->table('users');

        $user = $this->getUser();
        if ($user->level != 1) {
            $builder->where('uplink', $user->username);
        }

        $builder->select('CONCAT(users.id_users) as id, username, fullname, saldo, level, status, uplink');

        return DataTable::of($builder)
            ->setSearchableColumns(['username', 'fullname', 'saldo', 'uplink'])
            ->format('fullname', function ($value) {
                return $value ? esc(word_limiter($value, 1, '')) : '';
            })
            ->format('level', function ($value) {
                return getLevel($value);
            })
            ->toJson(true);
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
}
