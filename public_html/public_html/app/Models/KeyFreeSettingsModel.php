<?php

namespace App\Models;

use CodeIgniter\Model;

class KeyFreeSettingsModel extends Model
{
    protected $table = 'key_free_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'admin_id',
        'max_keys_per_day',
        'key_duration', 
        'max_devices',
        'shortlinks',
        'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}