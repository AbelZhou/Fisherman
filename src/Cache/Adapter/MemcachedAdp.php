<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 18:47
 */

namespace Fisherman\Cache\Adapter;


use Fisherman\Cache\CacheAbstract;
use Fisherman\Cache\CacheInterface;
use Memcached;

class MemcachedAdp extends CacheAbstract implements CacheInterface {
    private $mem = null;

    /**
     * MemcachedAdp constructor.
     * @param $cache_config
     */
    function __construct($cache_config) {
        $this->mem = new Memcached ();
        foreach ($cache_config as $config) {
            $this->mem->addserver($config ['host'], $config ['port'], $config ['weight']);
        }
    }

    /**
     * 设置一个cache缓存
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    function set($key, $value, $expire) {
        // TODO: Implement set() method.
        $obj = serialize($value);
        if ($key == false) {
            return false;
        }
        $result = $this->mem->replace($key, $obj, $expire);
        if (!$result) {
            $result = $this->mem->set($key, $obj, $expire);
        }
        return $result;
    }

    /**
     * 取出一个缓存key的值
     * @param string $key
     * @return mixed
     */
    function get($key) {
        // TODO: Implement get() method.
        $result = $this->mem->get($key);
        if (!empty($result)) {
            $result = unserialize($result, array());
        }
        return $result;
    }

    /**
     * 删除一个缓存
     * @param string $key
     * @return boolean
     */
    function delete($key) {
        // TODO: Implement delete() method.
        return $this->mem->delete($key);
    }
}