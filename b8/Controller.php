<?php

namespace b8;
use b8\Registry;

/**
 * b8 Abstract Controller class
 * @package b8
 */
class Controller
{
	/**
	 * Initialise the controller.
	 */
	public function init()
	{
	}

	/**
	 * Get a hash of incoming request parameters ($_GET, $_POST)
	 *
	 * @return array
	 */
	public function getParams()
	{
	    return Registry::getInstance()->getParams();
	}

	/**
	 * Get a specific incoming request parameter.
	 *
	 * @param      $key
	 * @param mixed $default    Default return value (if key does not exist)
	 *
	 * @return mixed
	 */
	public function getParam($key, $default = null)
	{
	    return Registry::getInstance()->getParam($key, $default);
	}

	/**
	 * Change the value of an incoming request parameter.
	 * @param $key
	 * @param $value
	 */
	public function setParam($key, $value)
	{
		Registry::getInstance()->setParam($key, $value);
	}

	/**
	 * Remove an incoming request parameter.
	 * @param $key
	 */
	public function unsetParam($key)
	{
		Registry::getInstance()->unsetParam($key);
	}
}