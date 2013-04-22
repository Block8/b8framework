<?php

namespace b8;

if(!defined('B8_PATH'))
{
	define('B8_PATH', dirname(__FILE__) . '/');
}

class Registry
{
	/**
	 * @var \b8\Registry
	 */
	protected static $_instance;
	protected $_data    = array();
	protected $_params  = null;

	/**
	 * @return Registry
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public static function forceReset()
	{
		self::$_instance = null;
	}

	protected function __construct()
	{
	}

	public function get($key, $default = null)
	{
		if(isset($this->_data[$key]))
		{
			return $this->_data[$key];
		}

		return $default;
	}

	public function set($key, $value)
	{
		$this->_data[$key] = $value;
	}

	public function setArray($array)
	{
		$this->_data = array_merge($this->_data, $array);
	}


	public function getParams()
	{
		if(is_null($this->_params))
		{
			$this->parseInput();
		}

		return $this->_params;
	}

	public function getParam($key, $default)
	{
		if(is_null($this->_params))
		{
			$this->parseInput();
		}

		if(isset($this->_params[$key]))
		{
			return $this->_params[$key];
		}
		else
		{
			return $default;
		}
	}

	public function setParam($key, $value)
	{
		$this->_params[$key] = $value;
	}

	public function unsetParam($key)
	{
		unset($this->_params[$key]);
	}

	public function parseInput()
	{
		$params = $_REQUEST;

		if(!isset($_SERVER['REQUEST_METHOD']) || in_array($_SERVER['REQUEST_METHOD'], array('PUT', 'DELETE')))
		{
			$vars = file_get_contents('php://input');

			if(!is_string($vars) || strlen(trim($vars)) === 0)
			{
				$vars = '';
			}

			$inputData = array();
			parse_str($vars, $inputData);

			$params = array_merge($params, $inputData);
		}

		$this->_params = $params;
	}
}