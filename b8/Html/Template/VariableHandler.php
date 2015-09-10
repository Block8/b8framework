<?php

namespace b8\Html\Template;

use b8\Config;
use b8\Helper\KeyValue;
use b8\Type\NumberValue;

class VariableHandler
{
    use KeyValue;

    protected $functions = [];
    protected $variableModifiers = [];

    public function getVariable($name)
    {
        // Check if we're calling a function:
        $rtn = $this->isFunctionCall($name);

        if (!is_null($rtn)) {
            return $rtn;
        }

        // Check if it is just a literal value:
        $rtn = $this->isLiteral($name);
        if (!is_null($rtn)) {
            return $rtn;
        }

        // Check if we're calling a helper:
        $rtn = $this->isHelperCall($name);
        if (!is_null($rtn)) {
            return $rtn;
        }

        // Try to process it as a variable:
        $rtn = $this->isVariable($name);
        if (!is_null($rtn)) {
            return $rtn;
        }

        return null;
    }

    protected function isFunctionCall($varName)
    {
        if (preg_match('/^([a-zA-Z0-9_-]+)\(/', $varName, $matches)) {
            $varName = substr($varName, strlen($matches[0]));
            $args = $this->processFunctionArguments($varName);

            return $this->executeTemplateFunction($matches[1], $args);
        }

        return null;
    }

    protected function isMethodCall($varName, $working)
    {
        if (preg_match('/^([a-zA-Z0-9_-]+)\(/', $varName, $matches)) {
            $varName = substr($varName, strlen($matches[0]));
            $args = $this->processFunctionArguments($varName);

            return call_user_func_array([$working, $matches[1]], $args);
        }

        return null;
    }

    protected function isLiteral($varName)
    {
        // Test if it is just a string:
        if (substr($varName, 0, 1) === '\'' && substr($varName, -1) === '\'') {
            return substr($varName, 1, -1);
        }

        // Test if it is just a number:
        if (is_numeric($varName)) {
            return $varName;
        }

        // Test if it is a boolean:
        if ($varName === 'true' || $varName === 'false') {
            return ($varName === 'true') ? true : false;
        }

        return null;
    }

    protected function isHelperCall($varName)
    {
        if (strpos($varName, ':') !== false) {
            list($helper, $property) = explode(':', $varName);

            $helper = $this->template->{$helper}();

            if (property_exists($helper, $property) || method_exists($helper, '__get')) {
                return $helper->{$property};
            }
        }

        return null;
    }

    protected function isVariable($varName)
    {
        $varPart = explode('.', $varName);
        $thisPart = array_shift($varPart);

        if (!$this->contains($thisPart)) {
            return null;
        }

        $working = $this->get($thisPart);

        while (count($varPart)) {
            $thisPart = array_shift($varPart);

            if (is_numeric($working)) {
                $working = new NumberValue($working);
            }

            if (is_object($working)) {

                $methodResult = $this->isMethodCall($thisPart, $working);

                if (!is_null($methodResult)) {
                    $working = $methodResult;
                    continue;
                }

                // Check if we're working with an actual property:
                if (property_exists($working, $thisPart) || method_exists($working, '__get')) {
                    $result = $working->{$thisPart};

                    if (!is_null($result)) {
                        $working = $result;
                        continue;
                    }
                }


            }

            if (is_array($working) && array_key_exists($thisPart, $working)) {
                $working = $working[$thisPart];
                continue;
            }

            $modifier = $this->isModifier($thisPart, $working);
            if (!is_null($modifier)) {
                $working = $modifier;
                break;
            }

            return null;
        }

        return $working;
    }

    protected function isModifier($thisPart, $working)
    {
        $operations = [
            'isNumeric' => 'is_numeric',
            'isString' => 'is_string',
            'isBool' => 'is_bool',
            'isObject' => 'isObject',
            'toLowerCase' => 'strtolower',
            'toUpperCase' => 'strtoupper',
            'toUcWords' => 'ucwords',
            'toHash' => 'md5',
            'toUrl' => function ($value) {
                $value = strtolower($value);
                $value = preg_replace('/([^a-z0-9]+)/', '-', $value);

                if (substr($value, 0, 1) == '-') {
                    $value = substr($value, 1);
                }

                if (substr($value, -1) == '-') {
                    $value = substr($value, 0, strlen($value) - 1);
                }

                return $value;
            },
            'toCurrency' => function ($value) {
                return number_format($value, 2);
            },
            'toJson' => function ($value) {
                return json_encode($value);
            },
            'toFormattedDate' => function (\DateTime $value) {
                $format = Config::getInstance()->get('app.date_format', 'Y-m-d H:i');
                return $value->format($format);
            },
            'toYesNo' => function ($value) {
                return ($value ? 'Yes' : 'No');
            },
            'output' => 'var_dump',
            'count' => 'count',
            'toInt' => 'intval',
        ];

        if (array_key_exists($thisPart, $operations)) {
            $func = $operations[$thisPart];
            return $func($working);
        }

        return null;
    }

    public function getVariables()
    {
        return $this->data;
    }

    protected function processFunctionArguments(&$args)
    {
        $matches = array();
        $returnArgs = array();

        do {
            if (preg_match('/^\s*([a-zA-Z0-9_-]+)\(/', $args, $matches)) {
                $args = substr($args, strlen($matches[0]));
                $processedArgs = $this->processFunctionArguments($args);
                $returnArgs[] = $this->executeTemplateFunction($matches[1], $processedArgs);

                if (strlen($args) && substr($args, 0, 1) == ')') {
                    $args = substr($args, 1);
                }
            } elseif (preg_match('/^\s*(true|false|[0-9]+|\'[^\']+\'|[a-zA-Z0-9\._-]+);?,?\s*/', $args, $matches)) {
                $returnArgs[] = $this->getVariable($matches[1]);
                $args = substr($args, strlen($matches[0]));
            } else {
                break;
            }

        } while (!empty($args));

        return $returnArgs;
    }

    protected function executeTemplateFunction($function, $args)
    {
        if (array_key_exists($function, $this->functions)) {
            return call_user_func_array($this->functions[$function], $args);
        }

        return null;
    }

    public function addFunction($name, callable $callback)
    {
        $this->functions[$name] = $callback;
    }
}
