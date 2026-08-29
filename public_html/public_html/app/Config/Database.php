<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
	/**
	 * The directory that holds the Migrations
	 * and Seeds directories.
	 *
	 * @var string
	 */
	public $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

	/**
	 * Lets you choose which connection group to
	 * use if no other is specified.
	 *
	 * @var string
	 */
	public $defaultGroup = 'default';

	/**
	 * The default database connection.
	 *
	 * @var array
	 */
	public $default = [
		'DSN'      => '',
		'hostname' => 'localhost',
		'username' => 'mbktunp_hama',
		'password' => 'mbktunp_hama',
		'database' => 'mbktunp_hama',
		'DBDriver' => 'MySQLi',
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'development'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	/**
	 * This database connection is used when
	 * running PHPUnit database tests.
	 *
	 * @var array
	 */
	public $tests = [
		'DSN'      => '',
		'hostname' => '127.0.0.1',
		'username' => 'mbktunp_hama',
		'password' => 'mbktunp_hama',
		'database' => 'mbktunp_hama',
		'DBDriver' => 'SQLite3',
		'DBPrefix' => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'development'),
		'charset'  => 'utf8',
		'DBCollat' => 'utf8_general_ci',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 3306,
	];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		if (getenv('database.default.hostname') || isset($_ENV['database.default.hostname'])) {
			$this->default['hostname'] = getenv('database.default.hostname') ?: $_ENV['database.default.hostname'];
		}
		if (getenv('database.default.username') || isset($_ENV['database.default.username'])) {
			$this->default['username'] = getenv('database.default.username') ?: $_ENV['database.default.username'];
		}
		if (getenv('database.default.password') || isset($_ENV['database.default.password'])) {
			$this->default['password'] = getenv('database.default.password') ?: $_ENV['database.default.password'];
		}
		if (getenv('database.default.database') || isset($_ENV['database.default.database'])) {
			$this->default['database'] = getenv('database.default.database') ?: $_ENV['database.default.database'];
		}

		if (ENVIRONMENT === 'testing')
		{
			$this->defaultGroup = 'tests';
		}
	}

	//--------------------------------------------------------------------

}
