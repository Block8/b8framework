<?php

namespace b8\Form;

use b8\Form\Element;
use b8\View;

class FieldSet extends Element
{
    /** @var Element[] $children */
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

                if (method_exists($field, 'getMultiple') && $field->getMultiple()) {
                    if (isset($values[$fieldName])) {
                        $field->setValue($values[$fieldName]);
                    }
                } else if (isset($vals[$fieldName])) {
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
        $this->children[$field->getId()] = $field;
        $field->setParent($this);
    }

    public function validate(&$errors = [])
    {
        $rtn = true;

        foreach ($this->children as $child) {
            if (!$child->validate($errors)) {
                $rtn = false;
            }
        }

        return $rtn;
    }

    protected function onPreRender(&$view)
    {
        $rendered = array();

        foreach ($this->children as $child) {
            $rendered[] = $child->render();
        }

        $view->childFields = $this->children;
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

    public function find($fieldName) {
        if (!empty($this->children[$fieldName])) {
            return $this->children[$fieldName];
        }

        foreach ($this->children as $child) {
            if ($child->getName() == $fieldName) {
                return $child;
            }

            if ($child instanceof FieldSet) {
                $field = $child->find($fieldName);

                if (!empty($field)) {
                    return $field;
                }
            }
        }

        return null;
    }

    public function removeChild($fieldName)
    {
        unset($this->children[$fieldName]);
    }
}
