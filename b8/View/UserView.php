<?php

namespace b8\View;

class UserView extends \b8\View
{
	public function __construct($viewCode)
	{
		$this->viewCode = $viewCode;
	}

	public function render()
	{
		$rtn = $this->viewCode;
		$rtn = $this->_parseIfs($rtn);
		$rtn = $this->_parseVars($rtn);
		$rtn = $this->_parseHelpers($rtn);

		return $rtn;
	}

	protected function _parseIfs($rtn)
	{
		$rtn = preg_replace_callback('/\{if ([a-zA-Z]+):([a-zA-Z0-9_]+)\}(.*?)\{\/if\}/smu', array($this, '_doParseIf'), $rtn);
		$rtn = preg_replace_callback('/\{ifnot ([a-zA-Z]+):([a-zA-Z0-9_]+)\}(.*?)\{\/ifnot\}/smu', array($this, '_doParseIfNot'), $rtn);
		return $rtn;
	}

	protected function _parseHelpers($rtn)
	{
		$rtn = preg_replace_callback('/\{@([a-zA-Z]+)\:([a-zA-Z0-9\_]+)\}/', array($this, '_doParseHelper'), $rtn);

		return $rtn;
	}

	protected function _doParseHelper($var)
	{
		$helper = $var[1];
		$property = $var[2];

		return isset($this->{$helper}()->{$property}) ? $this->{$helper}()->{$property} : '';
	}

	protected function _parseVars($rtn)
	{
		$rtn = preg_replace_callback('/\{@([a-zA-Z0-9\_]+)\}/', array($this, '_doParseVar'), $rtn);

		return $rtn;
	}

	protected function _doParseVar($var)
	{
		if($var[1] == 'year') return date('Y');
		
		return $this->{$var[1]};
	}

	protected function _doParseIf($var)
	{
		$helper		= $var[1];
		$property	= $var[2];
		$content	= $var[3];

		return isset($this->{$helper}()->{$property}) && !empty($this->{$helper}()->{$property}) ? $content : '';
	}

	protected function _doParseIfNot($var)
	{
		$helper		= $var[1];
		$property	= $var[2];
		$content	= $var[3];

		return !isset($this->{$helper}()->{$property}) || empty($this->{$helper}()->{$property}) ? $content : '';
	}
}