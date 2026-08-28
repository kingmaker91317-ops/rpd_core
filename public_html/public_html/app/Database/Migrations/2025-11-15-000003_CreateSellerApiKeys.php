<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSellerApiKeys extends Migration
{
    public function up()
    {
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        if ($db->tableExists('seller_api_keys')) {
            return;
        }

        $forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'status' => [
                'type'    => 'TINYINT',
                'default' => 1,
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $forge->addKey('id', true);
        $forge->addKey('user_id');
        $forge->addKey('api_key');

        $forge->createTable('seller_api_keys', true);
    }

    public function down()
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('seller_api_keys', true);
    }
}
