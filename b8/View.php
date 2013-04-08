<?php

namespace b8;
use b8\Exception\HttpException;

class View
{
	protected $_vars			= array();
	protected static $_helpers	= array();

	public function __construct($file, $path = null)
	{
		$viewPath = is_null($path) ? \b8\Registry::getInstance()->get('ViewPath') : $path;
		$viewFile = $viewPath . $file . '.phtml';

		if(!file_exists($viewFile))
		{
			throw new \Exception('View file does not exist: ' . $viewFile);
		}

		$this->viewFile = $viewFile;
	}

	public function __get($var)
	{
		return $this->_vars[$var];
	}

	public function __set($var, $val)
	{
		$this->_vars[$var] = $val;
	}

	public function __call($method, $params = array())
	{
		if(!isset(self::$_helpers[$method]))
		{
			$class						= '\\' . \b8\Registry::getInstance()->get('app_namespace') . '\\Helper\\' . $method;

            if(!class_exists($class))
            {
                $class = '\\b8\\View\\Helper\\' . $method;
            }

			if(!class_exists($class))
			{
				throw new HttpException\GeneralException('Helper class does not exist: ' . $class);
			}

			self::$_helpers[$method]	= new $class();
		}

		return self::$_helpers[$method];
	}

	public function render()
	{
		extract($this->_vars);

		ob_start();
		require($this->viewFile);
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}
}