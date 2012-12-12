<?php

namespace b8\Store;

class Factory
{
	protected static $instance	= null;
	protected $loadedStores		= array();
	
	public static function getInstance()
	{
		if(!isset(self::$instance))
		{
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public static function getStore($storeName)
	{
		return self::getInstance()->loadStore($storeName);
	}
	
	protected function __construct()
	{
	}

	public function loadStore($store)
	{
		if(!isset($this->loadedStores[$store]))
		{
			$class		= \b8\Registry::getInstance()->get('app_namespace') . '\\Store\\'.$store.'Store'; 
			$obj		= new $class();	
				
			$this->loadedStores[$store] = $obj;
		}
		
		return $this->loadedStores[$store];
	}
}
