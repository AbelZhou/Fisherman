<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:01
 */

namespace Fisherman\Cache;

use Exception;
use Fisherman\Cache\Adapter\MemcachedAdp;
use Fisherman\Core\Config;

class CacheFactory{
    private $cache_config;
    private $adapter;

    /**
     * 获得Factory对象
     * @param string $cache_adapter
     * @throws Exception
     */
    function __construct($cache_adapter='memcached'){
        $config = Config::getFile("cache");
        if(!isset($config[$cache_adapter])){
            throw new Exception('Can not found the cache adapter:'.$cache_adapter);
        }
        $this->cache_config = $config[$cache_adapter];
        $this->adapter = $cache_adapter;
    }

    /**
     * @return CacheInterface|CacheAbstract|null
     */
    function getCacheAdapter(){
        $adapter = null;
        switch ($this->adapter){
            case 'memcached':
                $adapter = new MemcachedAdp($this->cache_config);
                break;
            default:
        }
        return $adapter;
    }
}