<?php

namespace b8;

class Registry
{
	protected static $instance	= null;
	protected $data				= array();
	protected $params			= null;

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
			$vars = '';
			if(strlen(trim($vars = file_get_contents('php://input'))) === 0)
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