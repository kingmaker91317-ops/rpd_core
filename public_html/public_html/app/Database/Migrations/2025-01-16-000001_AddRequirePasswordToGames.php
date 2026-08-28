<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRequirePasswordToGames extends Migration
{
    public function up()
    {
        $fields = [
            'require_password' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'comment'    => 'Whether this game requires password when creating keys (1 = yes, 0 = no)'
            ]
        ];

        $this->forge->addColumn('games', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('games', 'require_password');
    }
}

