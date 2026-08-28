<?php

namespace App\Models;

use CodeIgniter\Model;
use \Hermawan\DataTables\DataTable;

class FuncationModel extends Model
{
    protected $table      = 'function_code';
    protected $primaryKey = 'id_path';
    protected $allowedFields = ['Online', 'Bullet', 'Esp', 'Aimbot', 'Memory', 'ModName', 'Maintenance', 'ftext', 'status', 'item', 'SilentAim', 'Setting', 'Hrs5', 'Days1', 'Days7', 'Days15', 'Days30', 'Days60', 'Currency'];

    protected $useTimestamps = true;


    public function Funcation($userid = false, $where = 'default')
    {
        $userid = $userid ?: 1;
        $where = ($where == 'default' ? 'id_path' : $where);
        $wfind = $this->where($where, $userid)
            ->get()
            ->getFirstRow();
        return $wfind ?: NULL;
    }
    
public function getFuncation($wherex)
    {
        return $this->where($wherex)
            ->get()
            ->getRowObject();
    }




}
