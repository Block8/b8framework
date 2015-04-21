<?php

namespace b8\Form\Element;

use b8\View;
use b8\Form\Input;

class Select extends Input
{
    protected $options = array();
    protected $multiple = false;

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function setMultiple($allowMultiple = false)
    {
        $this->multiple = $allowMultiple;
    }

    public function getMultiple()
    {
        return $this->multiple;
    }

    protected function onPreRender(&$view)
    {
        parent::onPreRender($view);

        if ($this->multiple) {
            $options = [];

            foreach ($this->options as $key => $value) {
                $selected = false;

                if (!is_null($this->value) && is_array($this->value)) {
                    $selected = in_array($key, $this->value);
                } elseif (!is_null($this->value)) {
                    $selected = ($this->value == $key);
                }

                $options[$key] = ['title' => $value, 'selected' => $selected];
            }

            $view->options = $options;
            $view->multiple = true;
        } else {
            $options = [];


            foreach ($this->options as $key => $value) {
                $options[$key] = ['title' => $value, 'selected' => ($key == $this->value)];
            }

            $view->options = $options;
            $view->multiple = false;
        }
    }
}
