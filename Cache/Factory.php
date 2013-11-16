<?php

namespace Kryn\CmsBundle\Cache;

use Kryn\CmsBundle\Configuration\Cache;

class Factory {

    public static function createFast(){
        $class = '\Kryn\CmsBundle\Cache\\';

        if (function_exists('apc_store')) {
            $class .= 'Apc';
        } else if (function_exists('xcache_set')) {
            $class .= 'XCache';
        } else if (function_exists('wincache_ucache_get')) {
            $class .= 'WinCache';
        } else {
            $class .= 'Files';
        }

        $cacheConfig = new Cache();
        $cacheConfig->setClass($class);
        return new $class($cacheConfig);
    }
}