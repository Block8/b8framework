<?php

namespace b8;

use b8\Config;
use b8\Http;
use b8\View;
use b8\Exception\HttpException;

class Application
{
    /**
     * @var \b8\Controller
     */
    protected $controller;

    /**
    * @var b8\Http\Request
    */
    protected $request;

    /**
    * @var b8\Http\Response
    */
    protected $response;

    /**
    * @var b8\Config
    */
    protected $config;

    /**
    * @var string
    */
    protected $action;

    /**
    * @var array
    */
    protected $actionParams;

    public function __construct(Config $config, Http\Request $request = null)
    {
        $this->config = $config;
        $this->response = new Http\Response();

        if (!is_null($request)) {
            $this->request = $request;
        } else {
            $this->request = new Http\Request();
        }
    }

    public function handleRequest()
    {
        if (!isset($this->controller) || !isset($this->action)) {
            $this->initRequest();
        }

        $this->controllerOutput = $this->controller->handleAction($this->action, $this->actionParams);
        return $this->response;
    }

    protected function initRequest()
    {
        $this->initController();
        $this->initAction();
    }

    protected function initController()
    {
        // Get controller name:
        $parts = $this->request->getPathParts();

        if (empty($parts[0])) {
            $parts[0] = $this->config->get('b8.app.default_controller', 'index');
        }

        $controller = str_replace('-', ' ', trim($parts[0]));
        $controller = ucwords($controller);
        $controller = str_replace(' ', '', $controller);

        $this->loadController($controller);
    }

    protected function initAction()
    {
        $parts = $this->request->getPathParts();
        $action = null;

        if (!empty($parts[1])) {
            $action = str_replace('-', ' ', trim($parts[1]));
            $action = ucwords($action);
            $action = str_replace(' ', '', $action);
            $action = lcfirst($action);
        }

        if (empty($action)) {
            $action = 'index';
        }

        $this->action = $action;
        $this->actionParams = array_slice($parts, 2);

        if (!$this->controller->hasAction($this->action)) {
            throw new HttpException\BadRequestException('Invalid action: ' . $this->action . ' does not exist.');
        }
    }

    protected function loadController($controllerName)
    {
        $this->controllerName   = $controllerName;
        $class                  = '\\' . $this->config->get('b8.app.namespace') . '\\Controller\\' . $controllerName . 'Controller';

        if (!class_exists($class)) {
            throw new HttpException\BadRequestException('Invalid controller ['.$class.']: ' . $this->controllerName .' does not exist.');
        }

        $this->controller = new $class($this->config, $this->request, $this->response);
        $this->controller->init();
    }
}
