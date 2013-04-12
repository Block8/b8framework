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
	protected static $instance;
	protected $data = array();
	protected $params = null;

	/**
	 * @return Registry
	 */
	public static function getInstance()
	{
		if(is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct()
	{
	}

	public function get($key, $default = null)
	{
		if(isset($this->data[$key]))
		{
			return $this->data[$key];
		}

		return $default;
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function setArray($array)
	{
		$this->data = array_merge($this->data, $array);
	}


	public function getParams()
	{
		if(is_null($this->params))
		{
			$this->parseInput();
		}

		return $this->params;
	}

	public function getParam($key, $default)
	{
		if(is_null($this->params))
		{
			$this->parseInput();
		}

		if(isset($this->params[$key]))
		{
			return $this->params[$key];
		}
		else
		{
			return $default;
		}
	}

	public function setParam($key, $value)
	{
		$this->params[$key] = $value;
	}

	public function unsetParam($key)
	{
		unset($this->params[$key]);
	}

	public function parseInput()
	{
		$params = $_REQUEST;

		if($_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
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

		$this->params = $params;
	}
}