<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 20:22
 */

namespace Fisherman\Model;

use Fisherman\Cache\CacheFactory;
use Fisherman\Core\Config;
use Fisherman\DB\DBFactory;
use Fisherman\DB\DBInterface;

class BaseModel {
    /**
     * @var DBInterface
     */
    protected $db_adapter = null;
    protected $cache_adapter = null;
    /**
     * @var Dao
     */
    protected $dao = null;

    /**
     * @param $db_tag
     */
    protected function _init($db_tag) {
        $config = Config::getFile("config");
        $db_factory = new DBFactory($db_tag);
        $this->db_adapter = $db_factory->getDBAdapter();
        $cache_factory = new CacheFactory();
        $this->cache_adapter = $cache_factory->getCacheAdapter();
        $this->dao = new Dao($db_tag,$config["db"],$config["cache"]);
    }
}