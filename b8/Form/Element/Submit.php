<?php

namespace b8\Form\Element;

use b8\View;

class Submit extends Button
{
    protected $value = 'Submit';

    public function render($viewFile = null)
    {
        return parent::render(($viewFile ? $viewFile : 'Button'));
    }

    protected function onPreRender(&$view)
    {
        parent::onPreRender($view);
        $view->type = 'submit';
    }
}
