<?php

namespace b8;

class Model
{
	protected $_data        = array();
	protected $_modified    = array();

	public function __construct($initialData = array())
	{
		if(is_array($initialData))
		{
			$this->_data = array_merge($this->_data, $initialData);
		}
	}

	public function toArray($depth = 2, $currentDepth = 0)
	{
		if(isset(static::$sleepable) && is_array(static::$sleepable) && count(static::$sleepable))
		{
			$sleepable = static::$sleepable;
		}
		else
		{
			$sleepable = array_keys($this->_getters);
		}

		$rtn = array();
		foreach($sleepable as $property)
		{
			$rtn[$property] = $this->_propertyToArray($property, $currentDepth, $depth);
		}

		return $rtn;
	}

	protected function _propertyToArray($property, $currentDepth, $depth)
	{
		$rtn = null;

		if(array_key_exists($property, $this->_getters))
		{
			$method = $this->_getters[$property];
			$rtn    = $this->{$method}();

			if(is_object($rtn) || is_array($rtn))
			{
				$rtn = ($depth > $currentDepth) ? $this->_valueToArray($rtn, $currentDepth, $depth) : null;
			}
		}

		return $rtn;
	}

	protected function _valueToArray($value, $currentDepth, $depth)
	{
		$rtn = null;
		if(!is_null($value))
		{
			if(is_object($value) && method_exists($value, 'toArray'))
			{
				$rtn = $value->toArray($depth, $currentDepth + 1);
			}
			elseif($value instanceof DateTime)
			{
				$rtn = array('date' => (string)$value);
			}
			elseif(is_array($value))
			{
				$childArray = array();

				foreach($value as $key => $value)
				{
					$childArray[$key] = $this->_valueToArray($value, $currentDepth + 1, $depth);
				}

				$rtn = $childArray;
			}
			else
			{

				if(is_string($value) && !mb_check_encoding($value, 'UTF-8'))
				{
					$value = mb_convert_encoding($value, 'UTF-8');
				}

				$rtn = $value;
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

	public function setValues(array $values)
	{
		foreach($values as $key => $value)
		{
			if(isset($this->_setters[$key]))
			{
				$func = $this->setters[$key];

				if($value === 'null')
				{
					$value = null;
				}
				elseif($value === 'true')
				{
					$value = true;
				}
				elseif($value === 'false')
				{
					$value = false;
				}

				$this->{$func}($value);
			}
		}
	}
}
