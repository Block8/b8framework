<?php

namespace b8\Form\Element;

use b8\View;

class TextArea extends Text
{
    protected $rows = 4;

    public function getRows()
    {
        return $this->rows;
    }

    public function setRows($rows)
    {
        $this->rows = $rows;
    }

    protected function onPreRender(View &$view)
    {
        parent::onPreRender($view);
        $view->rows = $this->getRows();
    }
}
