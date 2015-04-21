<?php

namespace b8\Form;

use b8\Form;
use b8\View;
use b8\Config;

abstract class Element
{
    protected $name;
    protected $elementId;
    protected $label;
    protected $css;
    protected $ccss;
    protected $parent;
    protected $viewLoader;

    public function __construct($name = null)
    {
        if (!is_null($name)) {
            $this->setName($name);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = strtolower(preg_replace('/([^a-zA-Z0-9_\-\[\]])/', '', $name));
    }

    public function getId()
    {
        return !$this->elementId ? 'element-' . $this->name : $this->elementId;
    }

    public function setId($elementId)
    {
        $this->elementId = $elementId;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getClass()
    {
        return $this->css;
    }

    public function setClass($class)
    {
        $this->css = $class;
        return $this;
    }

    public function getContainerClass()
    {
        return $this->ccss;
    }

    public function setContainerClass($class)
    {
        $this->ccss = $class;
    }

    public function setParent(Element $parent)
    {
        $this->parent = $parent;
        $this->viewLoader = $parent->getViewLoader();
    }

    public function render($viewFile = null)
    {

        if (is_null($viewFile)) {
            $class = explode('\\', get_called_class());
            $viewFile = end($class);
        }

        $viewLoader = $this->viewLoader;
        $view = $viewLoader($viewFile);
        $view->name = $this->getName();
        $view->id = $this->getId();
        $view->label = $this->getLabel();
        $view->css = $this->getClass();
        $view->ccss = $this->getContainerClass();
        $view->parent = $this->parent;

        $this->onPreRender($view);

        return $view;
    }

    /**
     * @param $viewFile
     * @return View
     */
    public function getView($viewFile)
    {
        $viewPath = Config::getInstance()->get('b8.view.path');

        if (file_exists($viewPath . 'Form/' . $viewFile . '.phtml')) {
            $view = new View('Form/' . $viewFile);
        } else {
            $view = new View($viewFile, B8_PATH . 'Form/View/');
        }

        return $view;
    }

    /**
     * @param callable $viewLoader
     */
    public function setViewLoader(callable $viewLoader)
    {
        $this->viewLoader = $viewLoader;
    }

    /**
     * @return callable
     */
    public function getViewLoader()
    {
        return $this->viewLoader;
    }

    abstract protected function onPreRender(&$view);
}
