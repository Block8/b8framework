<?php

namespace b8\Helper;

trait KeyValue
{
    protected $data = [];

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }

    public function contains($key)
    {
        return array_key_exists($key, $this->data);
    }
}