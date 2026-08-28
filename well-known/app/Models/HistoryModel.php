<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoryModel extends Model
{
    protected $table      = 'history';
    protected $primaryKey = 'id_history';
    protected $allowedFields = ['keys_id', 'user_do', 'info'];
    protected $useTimestamps = true;

    public function getAll($limit = 10, $orderBy = "DESC")
    {
        return $this->limit($limit)
            ->orderBy('id_history', $orderBy)
            ->get()->getResultObject();
    }

    public function getResetCount($keys_id)
    {
        $timeLimit = date('Y-m-d H:i:s', time() - 86400);
        return $this->where('keys_id', $keys_id)
            ->where('created_at >=', $timeLimit)
            ->groupStart()
                ->like('info', 'HWID Reset')
                ->orLike('info', 'KEY_RESET_API')
            ->groupEnd()
            ->countAllResults();
    }
}
