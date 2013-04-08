<?php

namespace b8\Framework;
use b8\Exception\HttpException;

class FrontController
{
	public $settings			= array();

	// String request path:
	protected $path				= '';

	// Array of request path, split by /
	protected $parts			= '';

	// Loaded controller class name:
	protected $controller		= null;

	// Loaded controller object:
	protected $controllerObject	= null;

	// String action (method) name:
	protected $action			= null;

	// Parameters to be passed to action():
	protected $params			= array();

	public function __construct()
	{
		if(isset($_SERVER['PATH_INFO']))
		{
			$path = explode('?', $_SERVER['PATH_INFO']);
		}

		if(isset($_SERVER['REQUEST_URI']))
		{
			$path = explode('?', $_SERVER['REQUEST_URI']);
		}

		$this->path			= $path[0];
		$this->parts		= explode('/', $this->path);
		$this->parts		= array_values(array_filter($this->parts));

		$this->controller	= $this->_getController();

		$registry = \b8\Registry::getInstance();
		$registry->set('requestPath', $this->path);
		$registry->set('requestParts', $this->parts);
		$registry->set('requestMethod', strtoupper($_SERVER['REQUEST_METHOD']));

		if(empty($this->controller) || !class_exists($this->controller))
		{
			throw new HttpException\BadRequestException('Invalid controller: ' . $this->controller .' does not exist.');
		}

		$controller				= $this->controller;
		$this->controllerObject	= new $controller();

		list($action, $params)	= $this->_getAction();
		$this->action			= $action;
		$this->params			= $params;

		$this->_beforeControllerInit();
		$this->controllerObject->init();
		$this->_onControllerInit();

		if(!isset($this->action) || (!method_exists($this->controllerObject, $this->action) && !method_exists($this->controllerObject, '__call')))
		{
			throw new HttpException\BadRequestException('Invalid action: ' . $this->action . ' does not exist.');
		}
	}

	public function handleRequest()
	{
		return call_user_func_array(array($this->controllerObject, $this->action), $this->params);
	}
	
	protected function _getController()
	{
		if(empty($this->parts[0]))
		{
			$this->parts[0] = \b8\Registry::getInstance()->get('DefaultController');
		}

		if(empty($this->parts[0]))
		{
			throw new HttpException\BadRequestException('All requests must pass a controller.');
		}

		$controller = str_replace('-', ' ', trim($this->parts[0]));
		$controller = ucwords($controller);
		$controller = str_replace(' ', '', $controller);		
		$aliases	= \b8\Registry::getInstance()->get('ControllerAliases');

		if(isset($aliases[$controller]))
		{
		    $controller = $aliases[$controller];
		}

		\b8\Registry::getInstance()->set('ControllerName', $controller);
		
		$controller = '\\'.\b8\Registry::getInstance()->get('app_namespace').'\\Controller\\' . $controller . 'Controller';

		return $controller;
	}

	protected function _getAction()
	{
		$action = null;

		if(!empty($this->parts[1]))
		{
			$action	= str_replace('-', ' ', trim($this->parts[1]));
			$action	= ucwords($action);
			$action	= str_replace(' ', '', $action);
			$action = lcfirst($action);
		}
		else
		{
			$action = 'index';
		}

		$params = array_slice($this->parts, 2);

		return array($action, $params);		
	}
}