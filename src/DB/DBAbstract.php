<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:35
 */
namespace Fisherman\DB;



abstract class DBAbstract{
    protected $_writer;
    protected $_reader;
    protected $_isReader = true;
    private static $_reader_dbs = array();

    /**
     * database initialize
     * @param $db_config
     * @param string $db_tags
     */
    protected function _init($db_config,$db_tags='') {
        if (empty ( $db_config ['writer'] )) {
            throw new \RuntimeException ( 'Can not found the database writer config.' );
        }
        if (empty ( $db_config ['reader'] ) || ! is_array ( $db_config ['reader'] )) {
            throw new \RuntimeException ( 'Can not found the database reader config.' );
        }
        $this->_writer = $db_config ['writer'];
        if(!empty($db_tags) && isset(self::$_reader_dbs[$db_tags])){
            $this->_reader = self::$_reader_dbs[$db_tags];
        }else{
            $readerIndex = mt_rand ( 0, count ( $db_config ['reader'] ) - 1 );
            $this->_reader = $db_config ['reader'] [$readerIndex];
            self::$_reader_dbs[$db_tags] = $db_config ['reader'] [$readerIndex];
        }
    }

    /**
     * 连接
     */
    abstract protected function _connect($isReader = true);

    /**
     * 断开连接
     */
    abstract protected function _close();
}