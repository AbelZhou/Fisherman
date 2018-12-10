<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/6
 * Time: 16:06
 */

namespace Fisherman\Model;


use Fisherman\Cache\CacheFactory;
use Fisherman\DB\DBFactory;
use RuntimeException;

class Dao {
    private $db_adapter = null;
    private $cache_adapter = null;
    private $cache = true;
    private $tag = '';
    private $key = '';
    private $is_set_key = false;
    private $expire = 300;
    private $cache_prefix = 'prefix_';
    private $is_reader = true;
    private $null_cache_through = false; //设定是否允许缓存穿透
    private $null_cache_through_expire = 5; //穿透阈值
    private $in_transaction = false;
    private $sql = '';
    private $data = [];

    /**
     * Dao constructor.
     * @param $db_tag
     */
    public function __construct($dbTag, $dbConfig, $cacheConfig) {
        $dbEngine = "mysql";
        $cacheEngine = "memcached";
        if (!empty($dbConfig)) {
            if (!empty($dbConfig["engine"])) {
                $dbEngine = $dbConfig["engine"];
            }
        }
        if (!empty($cacheConfig)) {
            if (!empty($cacheConfig["engine"])) {
                $cacheEngine = $cacheConfig["engine"];
            }
            if (!empty($cacheConfig["prefix"])) {
                $this->cache_prefix = $cacheConfig["prefix"];
            }
            if (!empty($cacheConfig["expire"]) && (int)$cacheConfig['expire'] > 0) {
                $this->expire = (int)$cacheConfig['expire'];
            }
        }

        $db_factory = new DBFactory ($dbTag, $dbEngine);
        $this->db_adapter = $db_factory->getDBAdapter();
        $cache_factory = new CacheFactory ($cacheEngine);
        $this->cache_adapter = $cache_factory->getCacheAdapter();

    }

    /**
     * 不使用cache
     * @return $this
     */
    public function noCache() {
        $this->cache = false;
        return $this;
    }

    /**
     * 允许空数据缓存透传
     * @return $this
     */
    public function enableNullCacheThrough() {
        $this->null_cache_through = true;
        return $this;
    }

    /**
     * 设定缓存过期时间
     *
     * @param int $sec
     * @return $this
     */
    public function expire($sec) {
        $this->expire = $sec;
        return $this;
    }

    /**
     * 设置缓存tag
     * @param $cache_tag
     * @return $this
     */
    public function setTag($cache_tag) {
        $this->tag = $this->cache_prefix . $cache_tag;
        return $this;
    }

    /**
     * 设置单独查询key
     * 适合按照UID ID 等进行查询的key 不纳入tag缓存管理体系
     * @param $key_str
     * @return $this
     */
    public function setKey($key_str) {
        $this->key = $key_str;
        $this->is_set_key = true;
        return $this;
    }

    /**
     * 获得链接 默认为读库
     *
     * @param boolean $is_reader
     *            是否使用读库 默认true
     * @return $this
     */
    public function conn($is_reader = TRUE) {
        if ($this->in_transaction) {
            return $this;
        }
        $this->is_reader = $is_reader;
        return $this;
    }

    /**
     * 装载加载sql
     *
     * @param string $sql
     *            sql语句
     * @param array $data
     *            预编译参数
     * @return $this
     */
    public function preparedSql($sql, $data) {
        $this->sql = $sql;
        $this->data = $data;
        $this->db_adapter->setData($sql, $data);
        return $this;
    }

    /**
     * 获得受影响的行数
     *
     * @return number
     */
    public function affectedCount() {
        $this->_connection();
        $res = $this->db_adapter->affectedCount();
        $this->afterChange(!empty($res));
        $this->_clearCacheSetting();
        return $res;
    }

    /**
     * 获得最后一条插入的id
     *
     * @return string
     */
    public function lastInsertId() {
        $this->_connection();
        $res = $this->db_adapter->lastInsertId();
        $this->afterChange(!empty($res));
        $this->_clearCacheSetting();
        return $res;
    }

    /**
     * 获得一条记录
     *
     * @return mixed
     */
    public function fetchOne() {
        // 设定当前执行语句缓存key
        $this->buildKey($this->sql, $this->data, 'FetchOne');

        $cache_res = $this->beginQuery();
        //非nocache情况下直接return结果
        if ($cache_res !== false) {
            return $cache_res;
        }
        $this->_connection();
        $res = $this->db_adapter->fetchOne();
        //无数据res==false
        $this->afterQuery($res);
        $this->_clearCacheSetting();
        return $res;
    }

    /**
     * 获得全部数据
     *
     * @return mixed:
     */
    public function fetchAll() {
        // 设定当前执行语句缓存key
        $this->buildKey($this->sql, $this->data, 'FetchAll');

        $cache_res = $this->beginQuery();
        //非nocache情况下直接return结果
        if ($cache_res !== false) {
            return $cache_res;
        }
        $this->_connection();
        $res = $this->db_adapter->fetchAll();
        //无数据res==array()
        $this->afterQuery($res);
        $this->_clearCacheSetting();
        return $res;
    }

    /**
     * 获得指定列号的数据
     *
     * @param int $column_num
     * @return string
     */
    public function fetchColumn($column_num) {
        // 设定当前执行语句缓存key
        $this->buildKey($this->sql, $this->data, 'FetchColumn');
        $cache_res = $this->beginQuery();
        //非nocache情况下直接return结果
        if ($cache_res !== false) {
            return $cache_res;
        }

        $this->_connection();
        $res = $this->db_adapter->fetchColumn($column_num);
        $this->afterQuery($res);
        $this->_clearCacheSetting();
        return $res;
    }

    /**
     * 执行一条sql语句
     *
     * @return boolean
     */
    public function excute() {
        $this->_connection();
        return $this->db_adapter->execute();
    }

    /**
     * 开启事务
     *
     * @return $this
     */
    public function beginTransaction() {
        $this->in_transaction = true;
        $this->_connection();
        $this->db_adapter->beginTransaction();
        return $this;
    }

    /**
     * 回滚事务
     *
     * @return mixed
     */
    public function rollback() {
        $res = $this->db_adapter->rollback();
        $this->_clearCacheSetting(true);
        return $res;
    }

    /**
     * 提交事务
     *
     * @return mixed
     */
    public function commit() {
        $res = $this->db_adapter->commit();
        $this->_clearCacheSetting(true);
        return $res;
    }

    /**
     * 清除Tag
     * @param string $tag
     * @return boolean
     */
    public function clearTag($tag) {
        return $this->cache_adapter->delete($this->cache_prefix . $tag);
    }

    /**
     * 构造key
     *
     * @param string $sql
     * @param array $data
     * @param $optag
     */
    private function buildKey($sql, $data, $optag) {
        if (!$this->cache) {
            return;
        }

        if ($this->cache && empty ($this->tag)) {
            throw new RuntimeException ('The cache tag is null.');
        }
        if ($this->is_set_key) {
            $this->key = $this->tag . $this->key;
            return;
        }

        // 取出Tag随机码
        $tag_val = $this->cache_adapter->get($this->tag);
        // 创建Tag随机码 或 重置tag存活时间
        $tag_val = $this->buildTagVal($tag_val);
        $key = $tag_val . md5($sql . json_encode($data) . $optag);
        $this->key = $key;
    }

    /**
     * 设定Tag随机码
     * @param $tag_val
     * @return string
     */
    private function buildTagVal($tag_val) {
        if (empty ($tag_val)) {
            $tag_val = time() . mt_rand(1, 99999);
        }
        $this->cache_adapter->set($this->tag, $tag_val, $this->expire);
        return $tag_val;
    }

    /**
     * 查询前
     * @return array|mixed|null|string
     */
    private function beginQuery() {
        if (!$this->cache) {
            return false;
        }
        //本地环境允许穿透
//        if (defined("RUNTIME_ENV") && RUNTIME_ENV == "local") {
//            $this->enableNullCacheThrough();
//        }

        $res = $this->cache_adapter->get($this->key);
        if (is_array($res)) {
            //如果数组为空切允许缓存穿透 return false 继续查库
            if (empty($res) && $this->null_cache_through) {
                return false;
            }
            return $res;
        }
        //return 字符串以及false
        return $res;
    }

    /**
     * 查询后
     *
     * @param mixed $data
     */
    private function afterQuery($data) {
        // nocache 直接return
        if (!$this->cache) {
            return;
        }

        // no key return
        if (empty($this->key)) {
            return;
        }

        // 空数据 重新定义数据为空数组 用以重定义查库false情况
        if (empty($data)) {
            //允许缓存穿透 不存储数据 重置穿透设置
            if ($this->null_cache_through) {
                return;
            }
            $data = array();
        }

        //缓存时间定义
        $expire = empty($data) ? $this->null_cache_through_expire : $this->expire;
        $this->cache_adapter->set($this->key, $data, $expire);
    }

    /**
     * 改变之后
     * @param bool $clear_cache
     */
    private function afterChange($clear_cache = false) {
        if (empty($this->tag)) {
            return;
        }
        if ($clear_cache) {
            $this->cache_adapter->delete($this->tag);
            //如果是自定义key 则删除自定义单独存贮的KEY
            if ($this->is_set_key) {
                $this->cache_adapter->delete($this->tag . $this->key);
            }
        }
    }

    /**
     * 检测链接
     */
    private function _connection() {
        $this->db_adapter->getConn($this->is_reader);
    }


    /**
     * 还原当前配置信息
     * @param bool $force
     */
    private function _clearCacheSetting($force = false) {
        if (!$this->in_transaction || $force) {
            $this->null_cache_through = false;
            $this->null_cache_through_expire = 5;
            $this->is_reader = true;
            $this->cache = true;
            $this->is_set_key = false;
            $this->tag = '';
            $this->key = '';
            $this->sql = '';
            $this->data = array();
            $this->expire = 300;
            $this->in_transaction = false;
        }
    }
}