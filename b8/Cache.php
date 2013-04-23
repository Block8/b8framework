<?php
namespace b8;
use b8\Registry;

/**
 * @package    b8
 * @subpackage Cache
 */

class Cache
{
	protected static $instance = null;
	protected $useCache = true;

	/**
	 * Get the singleton instance of this cache object.
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor - Protected to prevent this class being instantiated multiple times.
	 */
	protected function __construct()
	{
	}

	/**
	 * Get item from the cache:
	 */
	public function get($key)
	{
		if(Registry::getInstance()->get('DisableCaching', false) || !function_exists('apc_fetch'))
		{
			return null;
		}

		$success = false;
		$rtn     = apc_fetch($key, $success);

		if(!$success)
		{
			$rtn = null;
		}

		return $rtn;
	}

	/**
	 * Add an item to the cache:
	 */
	public function set($key, $value, $ttl = 0)
	{
		if(Registry::getInstance()->get('DisableCaching', false) || !function_exists('apc_store'))
		{
			return false;
		}

		return apc_store($key, $value, $ttl);
	}
}