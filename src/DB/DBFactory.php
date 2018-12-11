<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:02
 */

namespace Fisherman\DB;

use Fisherman\Core\Config;
use Fisherman\DB\Adapter\MySqlAdp;

class DBFactory{
    private $adapter = null;
    private $db_config = null;
    private $db_tag = null;

    /**
     * DBFactory constructor.
     * @param $db_tag
     * @param string $db_adapter
     */
    function __construct($db_tag,$db_adapter='mysql'){
        $config = Config::getFile("db");

        if(!isset($config[$db_tag])){
            throw new \RuntimeException('Can not found the db_config db_tag:'.$db_tag);
        }
        $this->db_config = $config[$db_tag];
        $this->adapter = $db_adapter;
        $this->db_tag = $db_tag;
    }


    /**
     *
     * @return DBInterface|null
     */
    public function getDBAdapter(){
        $adapter = null;
        switch ($this->adapter){
            case 'mysql':
                $adapter = new MySqlAdp($this->db_config,$this->db_tag);
                break;
            default:
        }
        return $adapter;
    }
}