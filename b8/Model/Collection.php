<?php

namespace b8\Model;

use ArrayAccess;
use Iterator;
use b8\Model;

class Collection implements ArrayAccess, Iterator
{
    use CollectionArrayAccess;

    /** @var \b8\Model[] $items */
    protected $items = [];

    public $count = 0;

    public function __construct(array $items = array())
    {
        foreach ($items as $key => $item) {
            $this->add($key, $item);
        }
    }

    /**
     * @param $key
     * @return Model|null
     */
    public function get($key)
    {
        if ($this->contains($key)) {
            return $this->items[$key];
        }

        return null;
    }

    /**
     * @param $key
     * @param Model $value
     * @return $this
     */
    public function add($key, Model $value)
    {
        $this->items[$key] = $value;
        $this->count = count($this->items);

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function remove($key)
    {
        if ($this->contains($key)) {
            unset($this->items[$key]);
        }

        $this->count = count($this->items);

        return $this;
    }


    /**
     * @param $key
     * @return bool
     */
    public function contains($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param callable $filter
     * @return static
     */
    public function where(callable $filter)
    {
        $filtered = array_filter($this->items, $filter);
        return new static($filtered);
    }

    public function sort(callable $sort)
    {
        uasort($this->items, $sort);
        return $this;
    }

    public function first()
    {
        $rtn = null;

        if (array_key_exists(0, $this->items)) {
            $rtn = $this->items[0];
        }

        return $rtn;
    }
}