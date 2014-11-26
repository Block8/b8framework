<?php

namespace b8\View;

use b8\View;
use b8\View\Template\Parser;
use b8\View\Template\Variables;

class Template extends View
{
    public static $templateFunctions = array();
    protected static $extension = 'html';

    /**
     * @var Template\Parser
     */
    protected $parser;

    /**
     * @var string
     */
    protected $viewCode;

    public function __construct($viewCode)
    {
        $this->variables = new Variables($this);
        $this->parser = new Parser($this, $this->variables);
        $this->viewCode = $viewCode;

        if (!count(self::$templateFunctions)) {
            self::$templateFunctions = array(
                'include' => array($this, 'includeTemplate'),
                'call' => array($this, 'callHelperFunction'),
                'define' => array($this, 'defineVariable'),
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

            if (!is_array($args)) {
                $args = $this->processFunctionArguments($args);
            }

            return $handler($args, $this);
        }

        return null;
    }

    public function processFunctionArguments(&$args)
    {
        $matches = array();
        $returnArgs = array();

        do {
            if (preg_match('/^([a-zA-Z0-9_-]+)\:\s*([a-zA-Z0-9_-]+)\(/', $args, $matches)) {
                $args = substr($args, strlen($matches[0]));

                $returnArgs[$matches[1]] = $this->executeTemplateFunction($matches[2], $this->processFunctionArguments($args));

                if (strlen($args) && substr($args, 0, 1) == ')') {
                    $args = substr($args, 1);
                }
            } elseif (preg_match('/^([a-zA-Z0-9_-]+)\:\s*(true|false|[0-9]+|\"[^\"]+\"|[a-zA-Z0-9\._-]+);?\s*/', $args, $matches)) {
                $returnArgs[$matches[1]] = $this->variables->getVariable($matches[2]);
                $args = substr($args, strlen($matches[0]));
            } else {
                break;
            }

        } while (!empty($args));

        return $returnArgs;
    }

    public function includeTemplate($args, &$view)
    {
        $template = static::createFromFile($args['template']);

        unset($args['template']);

        foreach ($args as $key => $val) {
            $template->variables->set($key, $val);
        }

        return $template->render();
    }

    public function callHelperFunction($args)
    {
        $helper = $args['helper'];
        $function = $args['method'];

        return $this->{$helper}()->{$function}();
    }

    public function defineVariable($args)
    {
        foreach ($args as $key => $val) {
            $this->variables->set($key, $val);
        }
    }
}
