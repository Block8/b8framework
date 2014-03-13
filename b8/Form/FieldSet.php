<?php

namespace b8\Form;

use b8\Form\Element;
use b8\View;

class FieldSet extends Element
{
    protected $children = array();

    public function getValues()
    {
        $rtn = array();

        foreach ($this->children as $field) {
            if ($field instanceof FieldSet) {
                $fieldName = $field->getName();

                if (empty($fieldName)) {
                    $rtn = array_merge($rtn, $field->getValues());
                } else {
                    $rtn[$fieldName] = $field->getValues();
                }
            } elseif ($field instanceof Input) {
                if ($field->getName()) {
                    $rtn[$field->getName()] = $field->getValue();
                }
            }
        }

        return $rtn;
    }

    public function setValues(array $values)
    {
        $vals = $this->flattenValues($values);

        foreach ($this->children as &$field) {
            if ($field instanceof FieldSet) {
                $fieldName = $field->getName();

                if (empty($fieldName) || !isset($vals[$fieldName])) {
                    $field->setValues($vals);
                } else {
                    $field->setValues($vals[$fieldName]);
                }
            } elseif ($field instanceof Input) {
                $fieldName = $field->getName();

                if (isset($vals[$fieldName])) {
                    $field->setValue($vals[$fieldName]);
                }
            }
        }
        
    }

    protected function flattenValues($values)
    {
        $vals = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subVal) {
                    $vals[$key . '[' . $subKey . ']'] = $subVal;
                }

                continue;
            }

            $vals[$key] = $value;
        }

        return $vals;
    }

    public function addField(Element $field)
    {
        $this->children[$field->getName()] = $field;
        $field->setParent($this);
    }

    public function validate()
    {
        $rtn = true;

        foreach ($this->children as $child) {
            if (!$child->validate()) {
                $rtn = false;
            }
        }

        return $rtn;
    }

    protected function onPreRender(View &$view)
    {
        $rendered = array();

        foreach ($this->children as $child) {
            $rendered[] = $child->render();
        }

        $view->children = $rendered;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getChild($fieldName)
    {
        return $this->children[$fieldName];
    }
}
