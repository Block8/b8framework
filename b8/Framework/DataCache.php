<?php
namespace b8\Framework;

/**
 * Allows caching of data throughout the system to improve performance. Currently uses APC only.
 *
 * @package    b8
 * @subpackage Caching
 */

class DataCache
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
		$this->useCache = !(\b8\Registry::getInstance()->get('DisableCaching', false));
	}

	/**
	 * Get item from the cache:
	 */
	public function get($key)
	{
		if(!$this->useCache || !function_exists('apc_fetch'))
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
		if(!$this->useCache || !function_exists('apc_store'))
		{
			return false;
		}

		return apc_store($key, $value, $ttl);
	}
}