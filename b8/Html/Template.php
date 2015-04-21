<?php

namespace b8\Html;

use b8\Html\Template\VariableHandler;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;

class Template
{
    /**
     * @var string $templateString
     */
    protected $templateString;

    /**
     * @var DomDocument $templateDocument
     */
    protected $templateDocument;

    /**
     * @var DomXPath $templateXPath
     */
    protected $templateXPath;

    /**
     * @var b8\Html\Template\VariableHandler
     */
    protected $variableHandler;

    /**
     * @var string
     */
    protected $templateName;

    public static function loadPartial($template)
    {
        $rtn = '<!-- Missing Partial: ' . $template . ' -->';

        if (file_exists($template . '.html')) {
            $rtn = file_get_contents($template . '.html');
            $rtn = static::processIncludes($rtn);
        }

        return $rtn;
    }


    public function __construct($templateString, $name = 'string')
    {
        $this->templateString = static::processIncludes($templateString);
        $this->variableHandler = new VariableHandler();

        $this->templateName = $name;
    }

    public function render()
    {
        if (empty($this->templateDocument)) {
            $this->compile();
        }

        return $this->templateDocument->saveHTML();
    }

    protected static function processIncludes($template)
    {
        $class = get_called_class();

        return preg_replace_callback('/\{\@include ([a-zA-Z0-9\/\-\_]+)\}/', function ($value) use ($class) {
            return $class::loadPartial($value[1]);
        }, $template);
    }

    protected function compile()
    {
        $this->templateDocument = new DOMDocument();

        if (!empty($this->templateString)) {
            libxml_use_internal_errors(true);
            $this->templateDocument->loadHTML($this->templateString, LIBXML_HTML_NODEFDTD);
            libxml_use_internal_errors(false);
        }

        $this->templateXPath = new DOMXPath($this->templateDocument);

        if ($this->templateDocument->hasChildNodes()) {
            foreach ($this->templateDocument->childNodes as $node) {
                $this->compileNode($node, $this->templateDocument);
            }

            $removals = $this->templateXPath->query('//*[@delete="1"]');

            foreach ($removals as $remove) {
                $remove->parentNode->removeChild($remove);
            }
        }
    }

    protected function compileNode(DOMNode &$node, DOMNode &$parent)
    {
        // Handle Ifs:
        if ($node instanceof DOMElement && $node->hasAttribute('if')) {
            $ifCondition = $node->getAttribute('if');

            if (empty($ifCondition)) {
                throw new \Exception('Elements with an if attribute must include an expression.');
            }

            if (!$this->ifCondition($ifCondition)) {
                // Mark for deletion and stop processing this node.
                $node->setAttribute('delete', 1);
                return;
            }

            $node->removeAttribute('if');
        }

        // Handle repeats:
        if ($node instanceof DOMElement && $node->hasAttribute('repeat')) {
            $repeatCondition = $node->getAttribute('repeat');

            if (empty($repeatCondition)) {
                throw new \Exception('Elements with a repeat attribute must include a variable.');
            }

            $node->removeAttribute('repeat');

            $variableName = 'value';
            if ($node->hasAttribute('as')) {
                $variableName = $node->getAttribute('as');
                $node->removeAttribute('as');
            }


            foreach ($this->repeatCondition($repeatCondition) as $key => $item) {
                $this->variableHandler->set($variableName, $item);
                $this->variableHandler->set('key', $key);

                $newNode = $node->cloneNode(true);
                $newNode =& $parent->insertBefore($newNode, $node);
                $this->compileNode($newNode, $parent);
            }

            $node->setAttribute('delete', 1);
            return;
        }

        if ($node instanceof DOMElement && $node->hasAttributes()) {
            $this->processAttributes($node);
        }

        if ($node instanceof DOMText) {
            $content = $node->nodeValue;
            $node->nodeValue = '';
            $replacements = [];

            $content = preg_replace_callback('/\{\@([^\}]+)\}/', function ($key) use (&$replacements) {
                $replacements[] = $this->variableHandler->getVariable($key[1]);
                return '{!!octo.split!!}';
            }, $content);

            $content = explode('{!!octo.split!!}', $content);

            foreach ($content as $part) {
                if (!empty($part)) {
                    $this->injectText($parent, $node, $part);
                }

                $replacement = array_shift($replacements);

                if (!empty($replacement)) {
                    $this->injectHtml($parent, $node, $replacement);
                }
            }
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $this->compileNode($child, $node);
            }
        }
    }

    /**
     * @param string $condition
     * @return bool
     */
    protected function ifCondition($condition)
    {
        $ifParts = array_map('trim', explode('&&', $condition));

        foreach ($ifParts as $condition) {
            if (!$this->innerIfCondition($condition)) {
                return false;
            }
        }

        return true;
    }

    protected function innerIfCondition($condition)
    {
        // if="variableName == someValue"
        $complexIfPattern = '/([a-zA-Z0-9_\-\(\):\s.\']+)\s+?([\!\=\<\>]+)?\s+?([a-zA-Z0-9\(\)_\-:\s.\']+)?/';

        // if="variableName"
        $simpleIfPattern = '/([a-zA-Z0-9_\-\(\):\s.]+)/';

        $vars = $this->variableHandler;

        $matches = array();

        if (preg_match($complexIfPattern, $condition, $matches)) {
            $left = is_numeric($matches[1]) ? intval($matches[1]) : $vars->getVariable($matches[1]);
            $right = is_numeric($matches[3]) ? intval($matches[3]) : $vars->getVariable($matches[3]);
            $operator = $matches[2];

            switch ($operator) {
                case '==':
                case '=':
                    return ($left == $right);

                case '!=':
                    return ($left != $right);

                case '>=':
                    return ($left >= $right);

                case '<=':
                    return ($left <= $right);

                case '>':
                    return ($left > $right);

                case '<':
                    return ($left < $right);
            }
        } elseif (preg_match($simpleIfPattern, $condition, $matches)) {
            $rtn = $vars->getVariable($condition);
            return  $rtn ? true : false;
        }
    }

    protected function repeatCondition($condition)
    {
        $rtn = $this->variableHandler->getVariable($condition);

        if (is_numeric($rtn)) {
            $count = $rtn;
            $rtn = [];

            for ($i = 0; $i < $count; $i++) {
                $rtn[] = $i;
            }
        }

        if (!is_array($rtn) && !($rtn instanceof \Iterator)) {
            throw new \Exception('Invalid repeat condition: ' . $condition);
        }

        return $rtn;
    }


    public function __set($key, $value)
    {
        return $this->variableHandler->set($key, $value);
    }

    public function __get($key)
    {
        return $this->variableHandler->get($key);
    }

    public function addFunction($name, callable $callback)
    {
        return $this->variableHandler->addFunction($name, $callback);
    }

    protected function injectHtml(DOMNode $parent, DOMNode $sibling, $string)
    {
        if (is_object($string) && method_exists($string, '__toString')) {
            $string = (string)$string;
        }

        if (!is_string($string)) {
            return;
        }

        if (strip_tags($string) == $string) {
            return $this->injectText($parent, $sibling, $string);
        }

        libxml_use_internal_errors(true);

        $insert = new DOMDocument();
        $insert->loadHTML('<octo id="octoinject">'.$string.'</octo>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $inject = $insert->getElementById("octoinject");

        if ($inject->hasChildNodes()) {
            foreach ($inject->childNodes as $insertNode) {
                $insertNode = $this->templateDocument->importNode($insertNode, true);
                $parent->insertBefore($insertNode, $sibling);
            }
        }

        libxml_use_internal_errors(false);
    }

    protected function injectText(DOMNode $parent, DOMNode $sibling, $string)
    {
        $textNode = new DOMText();
        $textNode->nodeValue = $string;

        $textNode = $this->templateDocument->importNode($textNode);
        $parent->insertBefore($textNode, $sibling);
    }

    public function __toString()
    {
        $matches = [];

        if (preg_match('/\<body\>(.*)\<\/body\>/is', $this->render(), $matches)) {
            return $matches[1];
        }

        return '';
    }


    protected function processAttributes(DOMNode &$node, $skip = [])
    {
        foreach ($node->attributes as $attribute) {
            if (in_array($attribute->name, $skip)) {
                continue;
            }

            if (substr($attribute->name, 0, 1) == '.') {
                $matches = [];

                if (preg_match('/^\(([^\}]+)\)([^\?]+)\?(.*)?/', $attribute->value, $matches)) {

                    if ($this->ifCondition($matches[1])) {
                        $node->setAttribute(substr($attribute->name, 1), $matches[2]);
                    } else {
                        $node->setAttribute(substr($attribute->name, 1), $matches[3]);
                    }
                } else if ($this->ifCondition($attribute->value)) {
                    $node->setAttribute(substr($attribute->name, 1), '');
                }

                $node->removeAttributeNode($attribute);
                return $this->processAttributes($node);
            }

            $value = preg_replace_callback('/\{\@([^\}]+)\}/', function ($key) {
                return $this->variableHandler->getVariable($key[1]);
            }, $attribute->value);

            $attribute->value = htmlentities($value);
        }
    }

    public function getContext()
    {
        return $this->variableHandler;
    }

    public function setContext(VariableHandler &$handler)
    {
        $this->variableHandler =& $handler;
    }
}