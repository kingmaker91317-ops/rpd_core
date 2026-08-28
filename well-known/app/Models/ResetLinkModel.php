<?php

namespace App\Models;

use CodeIgniter\Model;

class ResetLinkModel extends Model
{
    protected $table      = 'reset_links';
    protected $primaryKey = 'id';
    protected $allowedFields = ['token', 'type', 'expires_at', 'user_id'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getValidToken($token)
    {
        return $this->where('token', $token)
                    ->groupStart()
                        ->where('expires_at >', date('Y-m-d H:i:s'))
                        ->orWhere('expires_at', null)
                    ->groupEnd()
                    ->first();
    }
}
