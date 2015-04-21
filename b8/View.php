<?php

namespace b8;

use b8\Exception\HttpException;
use b8\View\Template\Variables;

class View
{
    protected static $helpers = array();
    protected static $extension = 'phtml';

    /**
     * @var View\Template\Variables
     */
    protected $variables;

    public function __construct($file, $path = null)
    {
        var_dump('View created: ' . $file);

        $this->variables = new Variables($this);

        if (!self::exists($file, $path)) {
            throw new \Exception('View file does not exist: ' . $file);
        }

        $this->viewFile = self::getViewFile($file, $path);
    }

    protected static function getViewFile($file, $path = null)
    {
        $viewPath = is_null($path) ? Config::getInstance()->get('b8.view.path') : $path;
        $fullPath = $viewPath . $file . '.' . static::$extension;

        return $fullPath;
    }

    public static function exists($file, $path = null)
    {
        if (!file_exists(self::getViewFile($file, $path))) {
            return false;
        }

        return true;
    }

    public function set($key, $val)
    {
        $this->variables->set($key, $val);
    }

    public function get($key)
    {
        return $this->variables->get($key);
    }

    public function __isset($var)
    {
        return $this->variables->contains($this->vars[$var]);
    }

    public function __get($var)
    {
        return $this->variables->get($var);
    }

    public function __set($var, $val)
    {
        return $this->variables->set($var, $val);
    }

    public function __call($method, $params = array())
    {
        if (!isset(self::$helpers[$method])) {
            $class = '\\' . Config::getInstance()->get('b8.app.namespace') . '\\Helper\\' . $method;

            if (!class_exists($class)) {
                $class = '\\b8\\View\\Helper\\' . $method;
            }

            if (!class_exists($class)) {
                throw new HttpException('Helper class does not exist: ' . $class);
            }

            self::$helpers[$method] = new $class($params);
            self::$helpers[$method]->view = $this;
        }

        return self::$helpers[$method];
    }

    public function render()
    {
        extract($this->variables->getVariables());

        ob_start();
        require($this->viewFile);
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function __toString()
    {
        return $this->render();
    }
}
