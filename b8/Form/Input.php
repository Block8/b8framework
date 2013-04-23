<?php

namespace b8\Form;
use b8\Form\Element,
	b8\View;

class Input extends Element
{
	protected $_required = false;
	protected $_pattern;
	protected $_validator;
	protected $_value;
	protected $_error;

	public function getValue()
	{
		return $this->_value;
	}

	public function setValue($value)
	{
		$this->_value = $value;
	}

	public function getRequired()
	{
		return $this->_required;
	}

	public function setRequired($required)
	{
		$this->_required = (bool)$required;
	}

	public function getValidator()
	{
		return $this->_validator;
	}

	public function setValidator(callable $validator)
	{
		$this->_validator = $validator;
	}

	public function getPattern()
	{
		return $this->_pattern;
	}

	public function setPattern($pattern)
	{
		$this->_pattern = $pattern;
	}

	public function validate()
	{
		if($this->getRequired() && empty($this->_value))
		{
			$this->_error = $this->getLabel() . ' is required.';
			return false;
		}

		if($this->getPattern() && !preg_match('/'.$this->getPattern().'/', $this->_value))
		{
			$this->_error = 'Invalid value entered.';
			return false;
		}

		$validator = $this->getValidator();

		if(is_callable($validator))
		{
			try
			{
				call_user_func_array($validator, array($this->_value));
			}
			catch(\Exception $ex)
			{
				$this->_error = $ex->getMessage();
				return false;
			}
		}

		return true;
	}

	protected function _onPreRender(View &$view)
	{
		$view->value    = $this->getValue();
		$view->error    = $this->_error;
		$view->pattern  = $this->_pattern;
		$view->required = $this->_required;
	}
}