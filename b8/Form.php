<?php

namespace b8;

use b8\Form\FieldSet;
use b8\View;

class Form extends FieldSet
{
    protected $action = '';
    protected $method = 'POST';

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    protected function onPreRender(View &$view)
    {
        $view->action = $this->getAction();
        $view->method = $this->getMethod();

        parent::onPreRender($view);
    }

    public function __toString()
    {
        return $this->render();
    }
}
