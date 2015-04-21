<?php

namespace b8\Model;

trait CollectionArrayAccess
{
    protected $position = 0;

    public function __get($key)
    {
        return $this->get($key);
    }

    public function offsetExists($key)
    {
        return $this->contains($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        return $this->add($key, $value);
    }

    public function offsetUnset($key)
    {
        return $this->remove($key);
    }

    function rewind()
    {
        reset($this->items);
    }

    function current()
    {
        return current($this->items);
    }

    function key()
    {
        return key($this->items);
    }

    function next()
    {
        return next($this->items);
    }

    function valid()
    {
        return ($this->current() !== false) ? true : false;
    }
}