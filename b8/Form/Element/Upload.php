<?php

namespace b8\Form\Element;

use b8\Form\Input;
use b8\View;

class Upload extends Input
{
    public function render($viewFile = null)
    {
        return parent::render(($viewFile ? $viewFile : 'Text'));
    }

    protected function onPreRender(View &$view)
    {
        parent::onPreRender($view);
        $view->type = 'file';
    }
}
