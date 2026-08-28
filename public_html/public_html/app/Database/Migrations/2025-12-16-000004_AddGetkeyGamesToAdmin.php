<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGetkeyGamesToAdmin extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('admin')) {
            return;
        }

        if ($this->db->fieldExists('getkey_games', 'admin')) {
            return;
        }

        $this->forge->addColumn('admin', [
            'getkey_games' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'CSV game IDs allowed for GetKey (subset of assigned games)',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->tableExists('admin') && $this->db->fieldExists('getkey_games', 'admin')) {
            $this->forge->dropColumn('admin', 'getkey_games');
        }
    }
}

