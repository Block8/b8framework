<?php

namespace b8;

use b8\Config;

class Database extends \PDO
{
	protected static $initialised   = false;
	protected static $servers       = array('read' => array(), 'write' => array());
	protected static $connections   = array('read' => null, 'write' => null);
	protected static $details       = array();

	/**
	* @deprecated
	*/
	public static function setReadServers($read)
	{
		$config = Config::getInstance();

		$settings = $config->get('database', array());
		$settings['servers']['read'] = $read;
		$config->set('database', $settings);
	}

	/**
	* @deprecated
	*/
	public static function setWriteServers($write)
	{
		$config = Config::getInstance();

		$settings = $config->get('database', array());
		$settings['servers']['write'] = $write;
		$config->set('database', $settings);
	}

	/**
	* @deprecated
	*/
	public static function setDetails($database, $username, $password)
	{
		$config               = Config::getInstance();
		$settings             = $config->get('database', array());
		$settings['name']     = $database;
		$settings['username'] = $username;
		$settings['password'] = $password;
		$config->set('database', $settings);
	}

	protected static function init()
	{
		$config   = Config::getInstance();
		$settings = $config->get('database', array());

		self::$servers['read']  = $settings['servers']['read'];
		self::$servers['write'] = $settings['servers']['write'];
		self::$details['db']    = $settings['name'];
		self::$details['user']  = $settings['username'];
		self::$details['pass']  = $settings['password'];
		self::$initialised      = true;
	}

	/**
	 * @param string $type
	 *
	 * @return \b8\Database
	 * @throws \Exception
	 */
	public static function getConnection($type = 'read')
	{
		if (!self::$initialised) {
			self::init();
		}

		if(is_null(self::$connections[$type]))
		{
			// Shuffle, so we pick a random server:
			shuffle(self::$servers[$type]);

			$connection = null;

			// Loop until we get a working connection:
			while(count(self::$servers[$type]))
			{
				// Pull the next server:
				$server = array_shift(self::$servers[$type]);

				// Try to connect:
				try
				{
					$connection = new self('mysql:host=' . $server . ';dbname=' . self::$details['db'],
						self::$details['user'],
						self::$details['pass'],
						array(
						     \PDO::ATTR_PERSISTENT         => false,
						     \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
						     \PDO::ATTR_TIMEOUT            => 2,
						     \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
						));
				}
				catch(\PDOException $ex)
				{
					$connection = false;
				}

				// Opened a connection? Break the loop:
				if($connection)
				{
					break;
				}
			}

			// No connection? Oh dear.
			if(!$connection && $type == 'read')
			{
				throw new \Exception('Could not connect to any ' . $type . ' servers.');
			}

			self::$connections[$type] = $connection;
		}

		return self::$connections[$type];
	}

	public function getDetails()
	{
		return self::$details;
	}
}
