<?php

namespace b8\Controller\Base;

class AbstractController
{
	public function getParams()
	{
		return \b8\Registry::getInstance()->getParams();
	}

	public function getParam($key, $default = null)
	{
		return \b8\Registry::getInstance()->getParam($key, $default);
	}

	public function setParam($key, $value)
	{
		return \b8\Registry::getInstance()->setParam($key, $value);
	}

	public function unsetParam($key)
	{
		return \b8\Registry::getInstance()->unsetParam($key);
	}
}