<?php

namespace b8;

/**
 * @package b8
 * @subpackage Cache
 */
class Cache
{
    /**
     * @var \b8\Type\Cache
     */
    protected static $instance;

    /**
     * LEGACY: Older apps will expect an APC cache in return.
     * @deprecated
     * @return \b8\Cache\ApcCache
     */
    public static function getInstance()
    {
        return self::getCache();
    }

    /**
     * @return \b8\Type\Cache
     */
    public static function getCache()
    {
        if (!isset(self::$instance)) {
            $apcCache = '\\b8\\Cache\\ApcCache';
            $requestCache = '\\b8\\Cache\\RequestCache';

            if ($apcCache::isEnabled()) {
                self::$instance = new $apcCache();
            } else {
                self::$instance = new $requestCache();
            }
        }

        return self::$instance;
    }
}
