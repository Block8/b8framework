<?php

namespace b8\Form;

use b8\Form\Element;
use b8\View;

class Input extends Element
{
    protected $required = false;
    protected $pattern;
    protected $validator;
    protected $value;
    protected $error;
    protected $customError = false;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getRequired()
    {
        return $this->required;
    }

    public function setRequired($required)
    {
        $this->required = (bool)$required;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function setValidator($validator)
    {
        if (is_callable($validator) || $validator instanceof \Closure) {
            $this->validator = $validator;
        }
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    public function validate()
    {
        if ($this->getRequired() && empty($this->value)) {
            $this->error = $this->getLabel() . ' is required.';
            return false;
        }

        if ($this->getPattern() && !preg_match('/' . $this->getPattern() . '/', $this->value)) {
            $this->error = 'Invalid value entered.';
            return false;
        }

        $validator = $this->getValidator();

        if (is_callable($validator)) {
            try {
                call_user_func_array($validator, array($this->value));
            } catch (\Exception $ex) {
                $this->error = $ex->getMessage();
                return false;
            }
        }

        if ($this->customError) {
            return false;
        }

        return true;
    }

    public function setError($message)
    {
        $this->customError = true;
        $this->error = $message;
    }

    protected function onPreRender(View &$view)
    {
        $view->value = $this->getValue();
        $view->error = $this->error;
        $view->pattern = $this->pattern;
        $view->required = $this->required;
    }
}
