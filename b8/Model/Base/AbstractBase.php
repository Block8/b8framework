<?php

namespace b8\Model\Base;

class AbstractBase
{
	protected $data	    = array();
	protected $modified = array();
	
	public function __construct($initialData = array())
	{
	    if(is_array($initialData))
	    {
			// Merge in the passed data:
			$this->data = array_merge($this->data, $initialData);
	    }
	}
	
	public function toArray($depth = 2, $currentDepth = 0)
	{
		if(isset(static::$sleepable) && is_array(static::$sleepable))
		{
			$sleepable = static::$sleepable;
		}
		else
		{
			$sleepable = array_merge(array_keys($this->data), array_keys($this->sleep));
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
	    if(array_key_exists($property, $this->sleep))
	    {
		    if($depth > $currentDepth)
		    {
			    $method = $this->sleep[$property];

			    if(!method_exists($this, $method))
			    {
				continue;
			    }

			    $obj = $this->$method();
			    $rtn = $this->_valueToArray($obj, $currentDepth, $depth);				
		    }
	    }
	    elseif(array_key_exists($property, $this->data))
	    {
			$value = $this->data[$property];
			
			if(is_string($value) && !mb_check_encoding($value, 'UTF-8'))
			{
				$value = mb_convert_encoding($value, 'UTF-8');
			}
			
		    $rtn = $value;
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
			if(isset($this->setters[$key]))
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
