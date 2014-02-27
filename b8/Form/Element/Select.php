<?php

namespace b8\Form\Element;

use b8\View;
use b8\Form\Input;

class Select extends Input
{
    protected $_options = array();

    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    protected function onPreRender(View &$view)
    {
        parent::onPreRender($view);
        $view->options = $this->_options;
    }
}
