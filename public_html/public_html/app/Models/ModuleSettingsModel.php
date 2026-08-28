<?php
namespace App\Models;
use CodeIgniter\Model;

class ModuleSettingsModel extends Model
{
    protected $table = 'module_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'modname', 'icon_path'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at'; 
    protected $updatedField = 'updated_at';

    public function getModuleSettings($userId) 
    {
        return $this->where('user_id', $userId)
                    ->select('id, modname, icon_path')
                    ->first();
    }
}