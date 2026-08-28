<?php

namespace App\Models;

use CodeIgniter\Model;

class SellerApiKeyModel extends Model
{
    protected $table      = 'seller_api_keys';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'api_key',
        'status',
        'credit_balance',
        'last_used_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;

    public function getByApiKey(string $apiKey)
    {
        return $this->where([
            'api_key' => $apiKey,
            'status'  => 1,
        ])->first();
    }
}
