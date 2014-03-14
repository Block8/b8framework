<?php

namespace b8\View\Template;

use b8\View\Template;

class Parser
{
    /**
     * @var \b8\View\Template
     */
    protected $template;

    /**
     * @var Variables
     */
    protected $variables;

    public function __construct(Template $template, Variables $variables)
    {
        $this->template = $template;
        $this->variables = $variables;
    }

    public function parse($string)
    {
        $lastCond = null;
        $keywords = array('ifnot', 'if', 'else', 'for', 'loop', '@', '/ifnot', '/if', '/for', '/loop');

        foreach (array_keys($this->template->getFunctions()) as $function) {
            $keywords[] = $function;
        }

        $stack = array('children' => array(array('type' => 'string', 'body' => '')));
        $stack['children'][0]['parent'] =& $stack;
        $current =& $stack['children'][0];

        while (!empty($string)) {
            $current['body'] .= $this->readUntil('{', $string);

            if (!empty($string)) {
                $gotKeyword = false;

                foreach ($keywords as $keyword) {
                    $kwLen = strlen($keyword) + 1;

                    if (substr($string, 0, $kwLen) == '{' . $keyword) {
                        $gotKeyword = true;
                        $item = array('type' => $keyword, 'cond' => '', 'children' => '');
                        $string = substr($string, $kwLen);

                        $cond = trim($this->readUntil('}', $string));

                        $item['cond'] = $cond;
                        $lastCond = $cond;
                        $string = substr($string, 1);

                        if (substr($string, 0, 1) == "\n") {
                            $string = substr($string, 1);
                        }

                        if (array_key_exists($keyword, $this->template->getFunctions())) {
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
                            // If we're processing a variable, add a string to the parent
                            // and move up to that as current.
                            $parent['children'][] = $str;
                            $current =& $parent['children'][count($parent['children']) - 1];
                            $current['parent'] =& $parent;
                        } elseif (substr($keyword, 0, 1) == '/') {
                            // If we're processing the end of a block (if/loop), add a string to the
                            // parent's parent and move up to that.
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
        if ($var == 'year') {
            return date('Y');
        }

        $val = $this->template->getVariable($var);
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

    protected function doParseLoop($var, $stack)
    {
        $working = $this->template->getVariable($var);

        if (is_null($working)) {
            return '';
        }

        if (!is_array($working)) {
            $working = array($working);
        }

        $rtn = '';
        foreach ($working as $key => $val) {
            // Make sure we support nesting loops:
            $keyWas = $this->template->get('key');
            $valueWas = $this->template->get('value');
            $itemWas = $this->template->get('item');


            // Set up the necessary variables within the stack:
            $parent = $this->variables->getVariables();

            $this->template->set('parent', $parent);
            $this->template->set('item', $val);
            $this->template->set('key', $key);
            $this->template->set('value', $val);

            $rtn .= $this->processStack($stack);

            // Restore state for any parent nested loops:
            $this->template->set('parent', null);
            $this->template->set('item', $itemWas);
            $this->template->set('key', $keyWas);
            $this->template->set('value', $valueWas);
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
     * @param $cond string The condition string for the loop.
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

        $varPart = $this->template->getVariable($part);

        if (is_numeric($varPart)) {
            return intval($varPart);
        }

        throw new \Exception('Invalid range in for loop: ' . $part);
    }

    protected function doParseFunction($stack)
    {
        return $this->template->executeTemplateFunction($stack['function_name'], $stack['cond']);
    }

    protected function ifConditionIsTrue($condition)
    {
        $matches = array();

        if (preg_match(
            '/([a-zA-Z0-9_\-\(\):\s.\"]+)\s+?([\!\=\<\>]+)?\s+?([a-zA-Z0-9\(\)_\-:\s.\"]+)?/',
            $condition,
            $matches
        )
        ) {
            $left = is_numeric($matches[1]) ? intval($matches[1]) : $this->template->getVariable($matches[1]);
            $right = is_numeric($matches[3]) ? intval($matches[3]) : $this->template->getVariable($matches[3]);
            $operator = $matches[2];

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
        } elseif (preg_match('/([a-zA-Z0-9_\-\(\):\s.]+)/', $condition, $matches)) {
            return $this->template->getVariable($condition) ? true : false;
        }
    }
}
