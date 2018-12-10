<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 18:50
 */
namespace Fisherman\Cache;
interface CacheInterface {
    /**
     * 设置一个cache缓存
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    function set($key, $value, $expire);

    /**
     * 取出一个缓存key的值
     * @param string $key
     * @return mixed
     */
    function get($key);

    /**
     * 删除一个缓存
     * @param string $key
     * @return boolean
     */
    function delete($key);
}