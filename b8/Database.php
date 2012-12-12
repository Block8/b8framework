<?php

namespace b8;

class Database
{
	protected static $servers		= array('read' => array(), 'write' => array());
	protected static $connections	= array('read' => null, 'write' => null);
	protected static $details		= array();

	public static function setReadServers($read)
	{
		self::$servers['read'] = $read;
	}
	
	public static function setWriteServers($write)
	{
		self::$servers['write'] = $write;
	}
	
	public static function setDetails($database, $username, $password)
	{
		self::$details = array('db' => $database, 'user' => $username, 'pass' => $password);
	}
	
	public static function getConnection($type = 'read')
	{
		if(is_null(self::$connections[$type]))
		{
			// Shuffle, so we pick a random server:
			shuffle(self::$servers[$type]);
			
			// Loop until we get a working connection:
			while(count(self::$servers[$type]))
			{
				// Pull the next server:
				$server	= array_shift(self::$servers[$type]);

				// Try to connect:
				$connection = false;
				try
				{
					$connection = @new \PDO('mysql:host=' . $server . ';dbname=' . self::$details['db'], 
														self::$details['user'], 
														self::$details['pass'], 
														array(
																\PDO::ATTR_PERSISTENT			=> false,
																\PDO::ATTR_ERRMODE				=> \PDO::ERRMODE_EXCEPTION,
																\PDO::ATTR_TIMEOUT				=> 2,
																\PDO::MYSQL_ATTR_INIT_COMMAND	=> 'SET NAMES \'UTF8\'',
															));
				}
				catch(\PDOException $ex)
				{
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
}
