<?php

namespace b8;

if (!defined('B8_PATH')) {
    define('B8_PATH', dirname(__FILE__) . '/');
}

class Config
{
    protected static $instance;

    public static function getInstance()
    {
        return self::$instance;
    }

    /**
    * @var array
    */
    protected $config = array();

    public function __construct(array $settings = array())
    {
        $this->setArray($settings);

        self::$instance = $this;
    }

    /**
    * Get a configuration value by key, returning a default value if not set.
    * @param $key string
    * @param $default mixed
    * @return mixed
    */
    public function get($key, $default = null)
    {
        if(isset($this->config[$key]))
        {
            return $this->config[$key];
        }

        return $default;
    }

    /**
    * Set a value by key.
    * @param $key string
    * @param $value mixed
    */
    public function set($key, $value = null)
    {
        $this->config[$key] = $value;
    }

    /**
    * Set an array of values.
    */
    public function setArray($array)
    {
        $this->config = array_merge($this->config, $array);
    }

    /**
    * Short-hand syntax for get()
    * @see Config::get()
    */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
    * Short-hand syntax for set()
    * @see Config::set()
    */
    public function __set($key, $value = null)
    {
        return $this->set($key, $value);
    }

    /**
    * Is set
    */
    public function __isset($key)
    {
        return isset($this->config[$key]);
    }

    /**
    * Unset
    */
    public function __unset($key)
    {
        unset($this->config[$key]);
    }
}