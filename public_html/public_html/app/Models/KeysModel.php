<?php

namespace App\Models;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Model;
use CodeIgniter\Validation\ValidationInterface;
use \Hermawan\DataTables\DataTable;

class KeysModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $keyColumn  = 'id';
    protected $allowedFields = [
        'game',
        'username',
        'password',
        'key_level',
        'duration',
        'expired_date',
        'expired',
        'max_devices',
        'device_limit',
        'devices',
        'UID',
        'status',
        'registrator',
        'uid_keys_reset_count',
        'uid_keys_reset_date',
        'registered',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;

    public function __construct(?ConnectionInterface $db = null, ?ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
        $this->keyColumn = $this->detectKeyColumn();
        $this->primaryKey = $this->keyColumn;
    }

    private function detectKeyColumn(): string
    {
        $fields = $this->db ? array_map('strtolower', $this->db->getFieldNames($this->table) ?? []) : [];
        foreach (['id_users', 'id_keys', 'id'] as $candidate) {
            if (in_array(strtolower($candidate), $fields, true)) {
                return $candidate;
            }
        }
        return $this->keyColumn;
    }

    public function getKeyColumn(): string
    {
        return $this->keyColumn;
    }

    public function getKeys($key = false, $where = 'username')
    {
        return $this->where($where, $key)
            ->get()
            ->getRowObject();
    }

    public function getKeysGame($where)
    {
        return $this->where($where)
            ->get()
            ->getRowObject();
    }

    public function API_getKeys()
    {
        $connect = db_connect();
        $builder = $connect->table($this->table);

        $userModel = new UserModel();
        $user = $userModel->getUser();

        // Filter chỉ lấy keys (có registrator), không lấy user accounts
        $builder->where('registrator IS NOT NULL');
        $builder->where('registrator !=', '');
        
        // Phân quyền xem key trong API
        if ($user->level == 1) {
            // Admin xem tất cả keys - không cần filter thêm
        } 
        else if ($user->level == 3) {
            // Tenant xem key của mình và của Reseller của mình
            $resellers = $userModel->where('uplink', $user->username)->findColumn('username');
            if ($resellers) {
                $builder->groupStart()
                        ->where('registrator', $user->username)
                        ->orWhereIn('registrator', $resellers)
                        ->groupEnd();
            } else {
                $builder->where('registrator', $user->username);
            }
        } 
        else {
            // Reseller chỉ xem key của mình
            $builder->where('registrator', $user->username);
        }

        $builder->select("
            {$this->keyColumn} as key_id,
            game,
            username,
            key_level,
            duration,
            expired_date as expired,
            max_devices,
            UID as devices,
            status,
            registrator,
            created_at,
            updated_at
        ");

        return DataTable::of($builder)
            ->setSearchableColumns([$this->keyColumn, 'game', 'username', 'key_level', 'duration', 'expired_date', 'max_devices', 'UID', 'registrator'])
            ->format('status', function ($value) {
                return ($value ? "Active" : "Inactive");
            })
            ->format('key_level', function ($value) {
                return intval($value);
            })
            ->format('duration', function ($value) {
                return formatDuration($value);
            })
            ->format('devices', function ($value) {
                if ($value) {
                    $e = explode(',', reduce_multiples($value, ",", true));
                }
                return $value ? count($e) : 0;
            })
            ->format('expired', function ($value) {
                if (!$value) return '';
                $time = new \CodeIgniter\I18n\Time;
                return $time::parse($value)->toLocalizedString('d MMM yy - H:m');
            })
            ->toJson(true);
    }
}
