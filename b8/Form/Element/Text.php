<?php

namespace b8\Form\Element;

use b8\Form\Input;
use b8\View;

class Text extends Input
{
    protected function onPreRender(&$view)
    {
        parent::onPreRender($view);
        $view->type = 'text';
    }
}
