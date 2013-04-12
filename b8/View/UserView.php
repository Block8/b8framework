<?php

namespace b8\View;
use b8\View;

class UserView extends View
{
	public function __construct($viewCode)
	{
		$this->viewCode = $viewCode;
	}

	public function render()
	{
		$rtn = $this->viewCode;
		$rtn = $this->_parseLoops($rtn);
		$rtn = $this->_parseIfs($rtn);
		$rtn = $this->_parseVars($rtn);
		$rtn = $this->_parseHelpers($rtn);

		return $rtn;
	}

	protected function _parseIfs($rtn)
	{
		$rtn = preg_replace_callback('/\{if ([a-zA-Z]+):([a-zA-Z0-9_]+)\}\r?\n?(.*?)\r?\n?\{\/if\}\r?\n?/smu', array($this, '_doParseHelperIf'), $rtn);
		$rtn = preg_replace_callback('/\{ifnot ([a-zA-Z]+):([a-zA-Z0-9_]+)\}\r?\n?(.*?)\r?\n?\{\/ifnot\}\r?\n?/smu', array($this, '_doParseHelperIfNot'), $rtn);
		$rtn = preg_replace_callback('/\{if ([a-zA-Z0-9_\.]+)\}\r?\n?(.*?)\r?\n?\{\/if\}\r?\n?/smu', array($this, '_doParseIf'), $rtn);
		$rtn = preg_replace_callback('/\{ifnot ([a-zA-Z0-9_\.]+)\}\r?\n?(.*?)\r?\n?\{\/ifnot\}\r?\n?/smu', array($this, '_doParseIfNot'), $rtn);

		return $rtn;
	}

	protected function _parseLoops($rtn)
	{
		$rtn = preg_replace_callback('/\{loop ([a-zA-Z0-9\_\.]+)\}\r?\n?(.*?)\r?\n?\{\/loop\}/smu', array($this, '_doParseLoop'), $rtn);

		return $rtn;
	}

	protected function _parseHelpers($rtn)
	{
		$rtn = preg_replace_callback('/\{@([a-zA-Z]+)\:([a-zA-Z0-9\_]+)\}/', array($this, '_doParseHelper'), $rtn);

		return $rtn;
	}

	protected function _doParseHelper($var)
	{
		$helper   = $var[1];
		$property = $var[2];

		return isset($this->{$helper}()->{$property}) ? $this->{$helper}()->{$property} : '';
	}

	protected function _parseVars($rtn)
	{
		$rtn = preg_replace_callback('/\{@([a-zA-Z0-9\_\.]+)\}/', array($this, '_doParseVar'), $rtn);

		return $rtn;
	}

	protected function _doParseVar($var)
	{
		if($var[1] == 'year')
		{
			return date('Y');
		}

		return $this->_processVariableName($var[1]);
	}

	protected function _doParseIf($var)
	{
		$working  = $this->_processVariableName($var[1]);
		$content  = $var[2];

		return $working ? $content : '';
	}

	protected function _doParseIfNot($var)
	{
		$working  = $this->_processVariableName($var[1]);
		$content  = $var[2];

		return $working ? '' : $content;
	}

	protected function _doParseHelperIf($var)
	{
		$helper   = $var[1];
		$property = $var[2];
		$content  = $var[3];

		return isset($this->{$helper}()->{$property}) && !empty($this->{$helper}()->{$property}) ? $content : '';
	}

	protected function _doParseHelperIfNot($var)
	{
		$helper   = $var[1];
		$property = $var[2];
		$content  = $var[3];

		return !isset($this->{$helper}()->{$property}) || empty($this->{$helper}()->{$property}) ? $content : '';
	}

	protected function _doParseLoop($var)
	{
		$working    = $this->_processVariableName($var[1]);

		if(is_null($working))
		{
			return '';
		}

		if(!is_array($working))
		{
			$working = array($working);
		}

		$rtn = '';
		foreach($working as $item)
		{
			$contentView       = new self($var[2]);
			$contentView->parent = $this;
			$contentView->item = $item;

			$rtn .= $contentView->render();
		}

		return $rtn;
	}

	protected function _processVariableName($varName)
	{
		$varPart    = explode('.', $varName);
		$thisPart   = array_shift($varPart);

		if(!isset($this->{$thisPart}))
		{
			return null;
		}

		$working    = $this->{$thisPart};

		while(count($varPart))
		{
			$thisPart   = array_shift($varPart);

			if(is_object($working) && isset($working->{$thisPart}))
			{
				$working = $working->{$thisPart};
				continue;
			}

			if(is_array($working) && isset($working[$thisPart]))
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

			return null;
		}

		return $working;
	}
}