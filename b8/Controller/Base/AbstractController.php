<?php

namespace b8\Controller\Base;
use b8\Registry;

class AbstractController
{
	public function getParams()
	{
	    return Registry::getInstance()->getParams();
	}

	public function getParam($key, $default = null)
	{
	    return Registry::getInstance()->getParam($key, $default);
	}

	public function setParam($key, $value)
	{
		return Registry::getInstance()->setParam($key, $value);
	}

	public function unsetParam($key)
	{
		return Registry::getInstance()->unsetParam($key);
	}
}