<?php

namespace b8\Form\Element;

use b8\Form\Input;
use b8\View;

class Button extends Input
{
    public function validate(&$errors = [])
    {
        return true;
    }

    protected function onPreRender(&$view)
    {
        parent::onPreRender($view);
        $view->type = 'button';
    }
}
