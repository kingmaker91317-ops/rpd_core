<?php

// app/Models/FileModel.php

namespace App\Models;

use CodeIgniter\Model;

class FileModel extends Model
{
    protected $table = 'libonline';
    protected $primaryKey = 'id';
    protected $allowedFields = ['game_package', 'name', 'type', 'architecture', 'version', 'status'];
}
