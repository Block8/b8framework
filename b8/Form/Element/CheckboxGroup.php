<?php

namespace b8\Form\Element;

use b8\Form\FieldSet;

class CheckboxGroup extends FieldSet
{
    protected $options = array();

    public function setOptions(array $options)
    {
        $this->options = $options;
        foreach($options as $key => $value) {
            $checkbox = Checkbox::create($this->getName()."_checkbox_".$key, $value, false);
            $checkbox->setName($this->getName() . '['.$key.']');
            $checkbox->setId($this->getName() . '_' . $key);
            $checkbox->setCheckedValue($key);
            $this->addField($checkbox); 
        }
    }
}
