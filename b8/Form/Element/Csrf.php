<?php

namespace b8\Form\Element;

use b8\View;

class Csrf extends Hidden
{
    public function validate()
    {
        if ($this->value != $_COOKIE[$this->getName()]) {
            return false;
        }

        return true;
    }

    protected function onPreRender(&$view)
    {
        parent::onPreRender($view);
        $csrf = md5(microtime(true));
        $view->csrf = $csrf;
        setcookie($this->getName(), $csrf);
    }
}
