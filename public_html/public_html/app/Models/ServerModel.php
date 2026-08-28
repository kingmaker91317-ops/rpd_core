<?php

namespace App\Models;

use CodeIgniter\Model;

class ServerModel extends Model
{
    protected $table      = 'server';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'modname', 'status', 'myinput'];
    protected $channel = 11;

    public function getRow()
    {
        $result = $this->where('id', $this->channel)
            ->get()
            ->getRowObject();

        if (!$result) {
            $this->insert([
                'id' => $this->channel,
                'modname' => 'Khánh Mods',
                'status' => 'on',
                'myinput' => 'Server is under maintenance'
            ]);
            return $this->getRow();
        }
        return $result;
    }

    public function updateData($data)
    {
        $this->update($this->channel, $data);
    }
}
