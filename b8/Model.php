<?php

namespace b8;

use b8\Exception\ValidationException;
use b8\Cache;

class Model
{
    public static $sleepable = array();
    protected $getters = array();
    protected $setters = array();
    protected $data = array();
    protected $modified = array();
    protected $tableName;
    protected $cache;
    protected $validationEnabled = true;

    public function __construct($initialData = array())
    {
        if (method_exists($this, 'init')) {
            $this->init();
        }
        
        if (is_array($initialData)) {
            $this->data = array_merge($this->data, $initialData);
        }
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function toArray($depth = 2, $currentDepth = 0)
    {
        if (isset(static::$sleepable) && is_array(static::$sleepable) && count(static::$sleepable)) {
            $sleepable = static::$sleepable;
        } else {
            $sleepable = array_keys($this->getters);
        }

        $rtn = array();
        foreach ($sleepable as $property) {
            $rtn[$property] = $this->propertyToArray($property, $currentDepth, $depth);
        }

        return $rtn;
    }

    protected function propertyToArray($property, $currentDepth, $depth)
    {
        $rtn = null;

        if (array_key_exists($property, $this->getters)) {
            $method = $this->getters[$property];
            $rtn = $this->{$method}();

            if (is_object($rtn) || is_array($rtn)) {
                $rtn = ($depth > $currentDepth) ? $this->valueToArray($rtn, $currentDepth, $depth) : null;
            }
        }

        return $rtn;
    }

    protected function valueToArray($value, $currentDepth, $depth)
    {
        $rtn = null;
        if (!is_null($value)) {
            if (is_object($value) && method_exists($value, 'toArray')) {
                $rtn = $value->toArray($depth, $currentDepth + 1);
            } elseif (is_array($value)) {
                $childArray = array();

                foreach ($value as $k => $v) {
                    $childArray[$k] = $this->valueToArray($v, $currentDepth + 1, $depth);
                }

                $rtn = $childArray;
            } else {
                $rtn = (is_string($value) && !mb_check_encoding($value, 'UTF-8')) ? mb_convert_encoding(
                    $value,
                    'UTF-8'
                ) : $value;
            }
        }

        return $rtn;
    }

    public function getDataArray()
    {
        return $this->data;
    }

    public function getModified()
    {
        return $this->modified;
    }

    protected function setModified($column)
    {
        $this->modified[$column] = $column;
    }

    public function setValues(array $values, $validate = true)
    {
        if (!$validate) {
            $this->disableValidation();
        }

        foreach ($values as $key => $value) {
            if (isset($this->setters[$key])) {
                $func = $this->setters[$key];

                if ($value === 'null') {
                    $value = null;
                } elseif ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }

                $this->{$func}($value);
            }
        }
    }

    //----------------
    // Validation
    //----------------
    protected function validateString($name, $value)
    {
        if (!$this->validationEnabled) {
            return true;
        }

        if (!is_string($value) && !is_null($value)) {
            throw new ValidationException($name . ' must be a string.');
        }
    }

    protected function validateInt($name, &$value)
    {
        if (!$this->validationEnabled) {
            return true;
        }

        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }

        if (!is_numeric($value) && !is_null($value)) {
            throw new ValidationException($name . ' must be an integer.');
        }

        if (!is_int($value) && !is_null($value)) {
            $value = (int)$value;
        }
    }

    protected function validateFloat($name, &$value)
    {
        if (!$this->validationEnabled) {
            return true;
        }

        if (!is_numeric($value) && !is_null($value)) {
            throw new ValidationException($name . ' must be a float.');
        }

        if (!is_float($value) && !is_null($value)) {
            $value = (float)$value;
        }
    }

    protected function validateDate($name, &$value)
    {
        if (!$this->validationEnabled) {
            return true;
        }
        
        if (is_string($value)) {
            $value = empty($value) ? null : new \DateTime($value);
        }

        if ((!is_object($value) || !($value instanceof \DateTime)) && !is_null($value)) {
            throw new ValidationException($name . ' must be a date object.');
        }


        $value = empty($value) ? null : $value->format('Y-m-d H:i:s');
    }

    protected function validateNotNull($name, &$value)
    {
        if (!$this->validationEnabled) {
            return true;
        }

        if (is_null($value)) {
            throw new ValidationException($name . ' must not be null.');
        }
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->getters)) {
            $getter = $this->getters[$key];
            return $this->{$getter}();
        }

        return null;
    }

    public function __set($key, $value)
    {
        if (array_key_exists($key, $this->setters)) {
            $setter = $this->setters[$key];
            return $this->{$setter}($value);
        }
    }

    public function disableValidation()
    {
        $this->validationEnabled = false;
    }
}
