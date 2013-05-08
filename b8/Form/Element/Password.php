<?php

namespace b8\Form\Element;
use b8\Form\Element\Text;

class Password extends Text
{
	public function render($viewFile = null)
	{
		return parent::render(($viewFile ? $viewFile : 'Text'));
	}

	protected function _onPreRender(View &$view)
	{
		parent::_onPreRender($view);
		$view->type = 'password';
	}
}