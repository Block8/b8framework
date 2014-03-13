<?php

namespace b8\View;

use b8\View;
use b8\View\Template\Parser;
use b8\View\Template\Variables;

class Template extends View
{
    public static $templateFunctions = array();
    protected static $extension = 'html';

    public function __construct($viewCode)
    {
        $this->variables = new Variables($this);
        $this->parser = new Parser($this);
        $this->viewCode = $viewCode;

        if (!count(self::$templateFunctions)) {
            self::$templateFunctions = array(
                'include' => array($this, 'includeTemplate'),
                'call' => array($this, 'callHelperFunction')
            );
        }
    }

    public static function createFromFile($file, $path = null)
    {
        if (!static::exists($file, $path)) {
            throw new \Exception('View file does not exist: ' . $file);
        }

        $viewFile = static::getViewFile($file, $path);
        return new static(file_get_contents($viewFile));
    }

    public static function createFromString($string)
    {
        return new static($string);
    }

    public function addFunction($name, $handler)
    {
        self::$templateFunctions[$name] = $handler;
    }

    public function removeFunction($name)
    {
        unset(self::$templateFunctions[$name]);
    }

    public function getFunctions()
    {
        return self::$templateFunctions;
    }

    public function render()
    {
        return $this->parser->parse($this->viewCode);
    }

    public function getVariable($variable)
    {
        return $this->variables->getVariable($variable);
    }

    public function executeTemplateFunction($function, $args)
    {
        if (array_key_exists($function, self::$templateFunctions)) {
            $handler = self::$templateFunctions[$function];
            $args = $this->processFunctionArguments($args);

            return $handler($args, $this);
        }

        return null;
    }

    protected function processFunctionArguments($args)
    {
        $rtn = array();

        $args = explode(';', $args);

        foreach ($args as $arg) {
            $arg = explode(':', $arg);

            if (count($arg) == 2) {

                $key = trim($arg[0]);
                $val = trim($arg[1]);

                if (strpos($val, ',') !== false) {
                    $val = explode(',', $val);
                }

                $rtn[$key] = $val;
            }
        }

        return $rtn;
    }

    public function includeTemplate($args, $view)
    {
        $template = static::createFromFile($view->getVariable($args['template']));

        if (isset($args['variables'])) {
            if (!is_array($args['variables'])) {
                $args['variables'] = array($args['variables']);
            }

            foreach ($args['variables'] as $variable) {

                $variable = explode('=>', $variable);
                $variable = array_map('trim', $variable);

                if (count($variable) == 1) {
                    $template->{$variable[0]} = $view->getVariable($variable[0]);
                } else {
                    $template->{$variable[1]} = $view->getVariable($variable[0]);
                }
            }
        }

        return $template->render();
    }

    public function callHelperFunction($args)
    {
        $helper = $args['helper'];
        $function = $args['method'];

        return $this->{$helper}()->{$function}();
    }
}
