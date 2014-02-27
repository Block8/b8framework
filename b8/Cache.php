<?php

namespace b8;

/**
 * @package b8
 * @subpackage Cache
 */
class Cache
{
    const TYPE_APC = 'ApcCache';
    const TYPE_REQUEST = 'RequestCache';

    protected static $instance = array();

    /**
     * LEGACY: Older apps will expect an APC cache in return.
     * @deprecated
     * @return \b8\Cache\ApcCache
     */
    public static function getInstance()
    {
        return self::getCache(self::TYPE_APC);
    }

    /**
     * @param string $type
     * @return \b8\Type\Cache
     */
    public static function getCache($type = self::TYPE_REQUEST)
    {
        if (!isset(self::$instance[$type])) {
            $class = '\\b8\\Cache\\' . $type;
            self::$instance[$type] = new $class();
        }

        return self::$instance[$type];
    }
}
