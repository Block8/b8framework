<?php

namespace b8\Form\Element;

use b8\View;
use b8\Form\Input;

class Checkbox extends Input
{
    protected $checked;
    protected $checkedValue;

    public function getCheckedValue()
    {
        return $this->checkedValue;
    }

    public function setCheckedValue($value)
    {
        $this->checkedValue = $value;
        return $this;
    }

    public function setValue($value)
    {
        if (is_bool($value) && $value == true) {
            $this->value = $this->getCheckedValue();
            $this->checked = true;
            return;
        }

        if ($value == $this->getCheckedValue()) {
            $this->value = $this->getCheckedValue();
            $this->checked = true;
            return;
        }

        $this->value = $value;
        $this->checked = false;
        return $this;
    }

    public function onPreRender(&$view)
    {
        parent::onPreRender($view);
        $view->checkedValue = $this->getCheckedValue();
        $view->checked = $this->checked;
    }
}
