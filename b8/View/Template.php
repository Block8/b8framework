<?php

namespace b8\View;
use b8\View;

class Template extends View
{
    protected $templateFunctions = array();
    protected static $extension = 'html';

	public function __construct($viewCode)
	{
		$this->viewCode = $viewCode;

        $this->templateFunctions = array('include' => array($this, 'includeTemplate'));
	}

    public static function createFromFile($file)
    {
        if (!self::exists($file)) {
            throw new \Exception('View file does not exist: ' . $file);
        }

        $viewFile = self::getViewFile($file);
        return new self(file_get_contents($viewFile));
    }

    public static function createFromString($string)
    {
        return new self($string);
    }

    public function addFunction($name, callable $handler)
    {
        $this->templateFunctions[$name] = $handler;
    }

    public function removeFunction($name)
    {
        unset($this->templateFunctions[$name]);
    }

	public function render()
	{
		return $this->parse($this->viewCode);
	}

	protected function parse($string)
	{
		$lastCond = null;
		$keywords = array('ifnot', 'if', 'else', 'for', 'loop', '@', '/ifnot', '/if', '/for', '/loop');

        foreach ($this->templateFunctions as $function => $handler) {
            $keywords[] = $function;
        }

		$stack = array('children' => array(array('type' => 'string', 'body' => '')));
		$stack['children'][0]['parent'] =& $stack;
		$current =& $stack['children'][0];

		while (!empty($string)) {
			$current['body'] .= $this->readUntil('{', $string);

			if (!empty($string)) {
				$gotKeyword = false;

				foreach($keywords as $keyword) {
					$kwLen = strlen($keyword) + 1;

					if (substr($string, 0, $kwLen) == '{' . $keyword) {
						$gotKeyword = true;
						$item = array('type' => $keyword, 'cond' => '', 'children' => '');
						$string = substr($string, $kwLen);

						$cond = trim($this->readUntil('}', $string));
						$item['cond'] = $cond;
						$lastCond = $cond;
						$string = substr($string, 1);

                        if (array_key_exists($keyword, $this->templateFunctions)) {
                            $item['function_name'] = $keyword;
                            $item['type'] = 'function';
                        }

						$str = array('type' => 'string', 'body' => '');
						$parent =& $current['parent'];

						if (substr($current['body'], (0 - strlen(PHP_EOL))) === PHP_EOL) {
							$current['body'] = substr($current['body'], 0, strlen($current['body']) - strlen(PHP_EOL));
						}

						$item['parent'] =& $parent;
						
						$parent['children'][] = $item;

						if ($keyword == '@' || $item['type'] == 'function') {
							// If we're processing a variable, add a string to the parent and move up to that as current.
							$parent['children'][] = $str;
							$current =& $parent['children'][count($parent['children']) - 1];
							$current['parent'] =& $parent;
						}  elseif (substr($keyword, 0, 1) == '/') {
							// If we're processing the end of a block (if/loop), add a string to the parent's parent and move up to that.
							$parent =& $parent['parent'];
							$parent['children'][] = $str;
							$current =& $parent['children'][count($parent['children']) - 1];
							$current['parent'] =& $parent;
						} else {
							$parent['children'][count($parent['children']) - 1]['children'][] = $str;
							$current =& $parent['children'][count($parent['children']) - 1]['children'][0];
							$current['parent'] =& $parent['children'][count($parent['children']) - 1];
						}

						break;
					}
				}

				if (!$gotKeyword) {
					$current['body'] .= substr($string, 0, 1);
					$string = substr($string, 1);
				}
			}
		}

		return $this->processStack($stack);
	}

	protected function processStack($stack)
	{
		$res = '';

		while (count($stack['children'])) {
			$current = array_shift($stack['children']);

			switch ($current['type']) {
				case 'string':
					$res .= $current['body'];
					break;

				case '@':
					$res .= $this->doParseVar($current['cond']);
					break;

				case 'if':
					$res .= $this->doParseIf($current['cond'], $current);
					break;

				case 'ifnot':
					$res .= $this->doParseIfNot($current['cond'], $current);
					break;

                case 'loop':
                    $res .= $this->doParseLoop($current['cond'], $current);
                    break;

                case 'for':
                    $res .= $this->doParseFor($current['cond'], $current);
                    break;

                case 'function':
                    $res .= $this->doParseFunction($current);
                    break;
			}
		}

		return $res;
	}

	protected function readUntil($until, &$string)
	{
		$read = '';

		while (!empty($string)) {
			$char = substr($string, 0, 1);

			if ($char == $until) {
				break;
			}

			$read .= $char;
			$string = substr($string, 1);
		}

		return $read;
	}

	protected function doParseVar($var)
	{
		if($var == 'year')
		{
			return date('Y');
		}

		$val = $this->processVariableName($var);
		return $val;
	}

	protected function doParseIf($condition, $stack)
	{
        if ($this->ifConditionIsTrue($condition)) {
            return $this->processStack($stack);
        } else {
            return '';
        }
	}

	protected function doParseIfNot($condition, $stack)
	{
        if (!$this->ifConditionIsTrue($condition)) {
            return $this->processStack($stack);
        } else {
            return '';
        }
	}

    protected function ifConditionIsTrue($condition)
    {
        $matches = array();

        if (preg_match('/([a-zA-Z0-9_.-]+)(\s+?([\!\=\<\>]+)?\s+?([a-zA-Z0-9_.-]+)?)?/', $condition, $matches)) {
            if (count($matches) == 2) {
                return $this->processVariableName($condition) ? true : false;
            }

            $left = is_numeric($matches[1]) ? intval($matches[1]) : $this->processVariableName($matches[1]);
            $right = is_numeric($matches[4]) ? intval($matches[4]) : $this->processVariableName($matches[4]);
            $operator = $matches[3];

            switch ($operator) {
                case '==':
                case '=':
                    return ($left == $right);

                case '!=':
                    return ($left != $right);

                case '>=':
                    return ($left >= $right);

                case '<=':
                    return ($left <= $right);

                case '>':
                    return ($left > $right);

                case '<':
                    return ($left < $right);
            }
        }
    }

	protected function doParseLoop($var, $stack)
	{
		$working    = $this->processVariableName($var);

		if(is_null($working))
		{
			return '';
		}

		if(!is_array($working))
		{
			$working = array($working);
		}

		$rtn = '';
		foreach ($working as $key => $val) {
            // Make sure we support nesting loops:
            $keyWas = isset($this->key) ? $this->key : null;
            $valWas = isset($this->value) ? $this->value : null;
            $itemWas = isset($this->item) ? $this->item : null;

            // Set up the necessary variables within the stack:
			$this->parent = $this;
			$this->item = $val;
            $this->key = $key;
            $this->value = $val;
			$rtn .= $this->processStack($stack);

            // Restore state for any parent nested loops:
            $this->item = $itemWas;
            $this->key = $keyWas;
            $this->value = $valWas;
		}

		return $rtn;
	}

    /**
     * Processes loops in templates, of the following styles:
     *
     * <code>
     * {for myarray.items}
     *     {@item.title}
     * {/for}
     * </code>
     *
     * Or:
     *
     * <code>
     * {for 0:pages.count; i++}
     *     <a href="/item/{@i}">{@i}</a>
     * {/for}
     * </code>
     *
     * @param $cond string The condition string for the loop, to be parsed (e.g. "myarray.items" or "0:pages.count; i++")
     * @param $stack string The child stack for this loop, to be processed for each item.
     * @return string
     * @throws \Exception
     */
    protected function doParseFor($cond, $stack)
    {
        // If this is a simple foreach loop, jump over to parse loop:
        if (strpos($cond, ';') === false) {
            return $this->doParseLoop($cond, $stack);
        }

        // Otherwise, process as a for loop:
        $parts = explode(';', $cond);
        $range = explode(':', trim($parts[0]));

        // Process range:
        $rangeLeft = $this->getForRangePart($range[0]);
        $rangeRight = $this->getForRangePart($range[1]);

        // Process variable & incrementor / decrementor:
        $parts[1] = trim($parts[1]);

        $matches = array();
        if (preg_match('/([a-zA-Z0-9_]+)(\+\+|\-\-)/', $parts[1], $matches)) {
            $varName = $matches[1];
            $direction = $matches[2] == '++' ? 'increment' : 'decrement';
        } else {
            throw new \Exception('Syntax error in for loop: ' . $cond);
        }

        $rtn = '';

        if ($direction == 'increment') {
            for ($i = $rangeLeft; $i < $rangeRight; $i++) {
                $this->parent = $this;
                $this->{$varName} = $i;
                $rtn .= $this->processStack($stack);
            }
        } else {
            for ($i = $rangeLeft; $i > $rangeRight; $i--) {
                $this->parent = $this;
                $this->{$varName} = $i;
                $rtn .= $this->processStack($stack);
            }
        }

        return $rtn;
    }

    protected function getForRangePart($part)
    {
        if (is_numeric($part)) {
            return intval($part);
        }

        $varPart = $this->processVariableName($part);

        if (is_numeric($varPart)) {
            return intval($varPart);
        }

        throw new \Exception('Invalid range in for loop: ' . $part);
    }

	public function processVariableName($varName)
	{
		// The variable could actually be a reference to a helper:
		if (strpos($varName, ':') !== false) {
			list($helper, $property) = explode(':', $varName);

			if (!empty($this->{$helper}()->{$property})) {
				return $this->{$helper}()->{$property};
			} else {
				return null;
			}
		}

		// Or not:
		$varPart    = explode('.', $varName);
		$thisPart   = array_shift($varPart);


		if(!array_key_exists($thisPart, $this->_vars))
		{
			return null;
		}

		$working    = $this->{$thisPart};

		while(count($varPart))
		{
			$thisPart   = array_shift($varPart);

			if(is_object($working) && property_exists($working, $thisPart))
			{
				$working = $working->{$thisPart};
				continue;
			}

			if(is_array($working) && array_key_exists($thisPart, $working))
			{
				$working = $working[$thisPart];
				continue;
			}

			if($thisPart == 'toLowerCase')
			{
				$working = strtolower($working);
				continue;
			}

			if($thisPart == 'toUpperCase')
			{
				$working = strtoupper($working);
				continue;
			}

			if ($thisPart == 'isNumeric')
			{
				return is_numeric($working);
			}

			return null;
		}

		return $working;
	}

    protected function doParseFunction($stack)
    {
        if (array_key_exists($stack['function_name'], $this->templateFunctions)) {
            $handler = $this->templateFunctions[$stack['function_name']];
            $args = $this->processFunctionArguments($stack['cond']);

            return $handler($args, $this);
        }

        return '';
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

    public function getVariable($variable)
    {
        return $this->processVariableName($variable);
    }

    protected function includeTemplate($args)
    {
        $template = self::createFromFile($args['template']);

        if (isset($args['variables'])) {
            if (!is_array($args['variables'])) {
                $args['variables'] = array($args['variables']);
            }

            foreach ($args['variables'] as $variable) {

                $variable = explode('=>', $variable);
                $variable = array_map('trim', $variable);

                if (count($variable) == 1) {
                    $template->{$variable[0]} = $this->processVariableName($variable[0]);
                } else {
                    $template->{$variable[1]} = $this->processVariableName($variable[0]);
                }
            }
        }

        return $template->render();
    }
}