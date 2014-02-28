<?php

namespace b8\Form\Element;

use b8\View;
use b8\Form\Input;

class Select extends Input
{
    protected $options = array();

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    protected function onPreRender(View &$view)
    {
        parent::onPreRender($view);
        $view->options = $this->options;
    }
}
