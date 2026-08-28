<?php

namespace App\Controllers;

use App\Models\Server;
use App\Models\Status;
use App\Models\Feature;
use CodeIgniter\Controller;



class Home extends BaseController
{

    public function index()
    {
        echo view('intro');
    }

    public function migrate()
    {
        $db = \Config\Database::connect();
        
        if ($db->tableExists('users')) {
            if (!$db->fieldExists('telegram_id', 'users')) {
                $db->query("ALTER TABLE `users` ADD `telegram_id` VARCHAR(50) NULL DEFAULT NULL AFTER `password`");
                echo "Successfully added 'telegram_id' to users table.<br>";
            } else {
                echo "Column 'telegram_id' already exists in users table.<br>";
            }
            
            if (!$db->fieldExists('seller_key', 'users')) {
                $db->query("ALTER TABLE `users` ADD `seller_key` VARCHAR(100) NULL DEFAULT NULL AFTER `telegram_id`");
                echo "Successfully added 'seller_key' to users table.<br>";
            } else {
                echo "Column 'seller_key' already exists in users table.<br>";
            }

            // Create reset_links table if not exists
            if (!$db->tableExists('reset_links')) {
                $db->query("CREATE TABLE `reset_links` (
                    `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `token` VARCHAR(255) NOT NULL,
                    `type` ENUM('temporary', 'permanent') NOT NULL DEFAULT 'temporary',
                    `expires_at` DATETIME NULL,
                    `user_id` INT(11) NOT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `token` (`token`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                echo "Successfully created 'reset_links' table.<br>";
            } else {
                echo "Table 'reset_links' already exists.<br>";
            }
        } else {
            echo "Users table does not exist.<br>";
        }
    }
}
