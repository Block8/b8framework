<?php

namespace b8\Store;
use b8\Config;

class Factory
{
	/**
	 * @var \b8\Store\Factory
	 */
	protected static $instance;

	/**
	 * A collection of the stores currently loaded by the factory.
	 * @var \b8\Store[]
	 */
	protected $loadedStores = array();

	/**
	 * @return Factory
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
	 * @param $storeName string Store name (should match a model name).
	 *
	 * @return \b8\Store
	 */
	public static function getStore($storeName)
	{
		$factory = self::getInstance();
		return $factory->loadStore($storeName);
	}

	protected function __construct()
	{
	}

	/**
	 * @param $store
	 *
	 * @return \b8\Store;
	 */
	public function loadStore($store)
	{
		if(!isset($this->loadedStores[$store]))
		{
			$class = Config::getInstance()->get('b8.app.namespace') . '\\Store\\' . $store . 'Store';
			$obj   = new $class();

			$this->loadedStores[$store] = $obj;
		}

		return $this->loadedStores[$store];
	}
}
