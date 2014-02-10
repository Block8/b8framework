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
		return $this->parse($this->viewCode);
	}

	protected function parse($string)
	{
		$lastCond = null;
		$keywords = array('ifnot', 'if', 'loop', '@', '/ifnot', '/if', '/loop');
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

						$str = array('type' => 'string', 'body' => '');
						$parent =& $current['parent'];

						if (substr($current['body'], (0 - strlen(PHP_EOL))) === PHP_EOL) {
							$current['body'] = substr($current['body'], 0, strlen($current['body']) - strlen(PHP_EOL));
						}

						$item['parent'] =& $parent;
						
						$parent['children'][] = $item;

						if ($keyword == '@') {
							// If we're processing a variable, add a string to the parent and move up to that as current.
							$parent['children'][] = $str;
							$current =& $parent['children'][count($parent['children']) - 1];
							$current['parent'] =& $parent;
						} elseif (substr($keyword, 0, 1) == '/') {
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
		return empty($val) ? null : $val;
	}

	protected function doParseIf($var, $stack)
	{
		$working  = $this->processVariableName($var);

		if ($working) {
			return $this->processStack($stack);
		}

		return '';
	}

	protected function doParseIfNot($var, $stack)
	{
		$working  = $this->processVariableName($var);

		if (!$working) {
			return $this->processStack($stack);
		}

		return '';
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
		foreach ($working as $item) {
			$itemWas = isset($this->item) ? $this->item : null;
			$this->parent = $this;
			$this->item = $item;
			$rtn .= $this->processStack($stack);
			$this->item = $itemWas;
		}

		return $rtn;
	}

	protected function processVariableName($varName)
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

			if ($thisPart == 'isNumeric')
			{
				return is_numeric($working);
			}

			return null;
		}

		return $working;
	}
}