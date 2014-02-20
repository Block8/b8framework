<?php

namespace b8\Http;

use b8\Config;
use b8\Http\Request;

class Router
{
    /**
     * @var \b8\Http\Request;
     */
    protected $request;

    /**
     * @var \b8\Http\Config;
     */
    protected $config;

    /**
     * @var array
     */
    protected $routes = array(array('route' => '/:controller/:action', 'callback' => null, 'defaults' => array()));

    public function __construct(Request $request, Config $config)
    {
        $this->request = $request;
        $this->config = $config;
    }

    public function register($route, callable $callback = null, $options = array())
    {
        array_unshift($this->routes, array('route' => $route, 'callback' => $callback, 'defaults' => $options));
    }

    public function dispatch()
    {
        foreach ($this->routes as $route) {
            $pathParts = $this->request->getPathParts();

            //-------
            // Set up default values for everything:
            //-------
            $thisController = $this->config->get('b8.app.default_controller', 'Default');
            $thisNamespace = 'Controller';
            $thisAction = 'index';

            if (array_key_exists('namespace', $route['defaults'])) {
                $thisNamespace = $route['defaults']['namespace'];
            }

            if (array_key_exists('controller', $route['defaults'])) {
                $thisController = $route['defaults']['controller'];
            }

            if (array_key_exists('action', $route['defaults'])) {
                $thisAction = $route['defaults']['action'];
            }

            $routeParts = explode('/', substr($route['route'], 1));
            $routeMatches = true;

            while (count($routeParts)) {
                $routePart = array_shift($routeParts);
                $pathPart = array_shift($pathParts);

                switch ($routePart) {
                    case ':namespace':
                        $thisNamespace = !is_null($pathPart) ? $pathPart : $thisNamespace;
                        break;
                    case ':controller':
                        $thisController = !is_null($pathPart) ? $pathPart : $thisController;
                        break;
                    case ':action':
                        $thisAction = !is_null($pathPart) ? $pathPart : $thisAction;
                        break;
                    default:
                        if ($routePart != $pathPart) {
                            $routeMatches = false;
                        }
                }

                if (!$routeMatches || !count($pathParts)) {
                    break;
                }
            }

            $thisArgs = $pathParts;

            if ($routeMatches) {
                return array('namespace' => $thisNamespace, 'controller' => $thisController, 'action' => $thisAction, 'args' => $thisArgs);
            }
        }

        return null;
    }
}