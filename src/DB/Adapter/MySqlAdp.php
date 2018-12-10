<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:42
 */

namespace Fisherman\DB\Adapter;


use ArrayObject;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

use Fisherman\DB\DBAbstract;
use Fisherman\DB\DBInterface;
use Fisherman\Func\Log;


class MySqlAdp extends DBAbstract implements DBInterface {

    /**
     * @var PDO
     */
    private $pdo = null;
    /**
     * @var PDOStatement
     */
    private $stmt = null;
    private $prep_sql = array();
    private $prep_data = array();
    private static $pdo_arr = array();


    /**
     * pdo值类型
     *
     * @var ArrayObject
     */
    private $pdo_type = array(
        'integer' => PDO::PARAM_INT,
        'int' => PDO::PARAM_INT,
        'boolean' => PDO::PARAM_BOOL,
        'bool' => PDO::PARAM_BOOL,
        'string' => PDO::PARAM_STR,
        'null' => PDO::PARAM_NULL,
        'object' => PDO::PARAM_LOB,
        'float' => PDO::PARAM_STR,
        'double' => PDO::PARAM_STR
    );

    /**
     * MySqlAdp constructor.
     * @param $db_config
     * @param $db_tag
     * @throws \Exception
     */
    public function __construct($db_config, $db_tag) {
        $this->_init($db_config, $db_tag);
    }


    /**
     * close connection
     */
    protected function _close() {
        // TODO: Implement _close() method.
        if ($this->pdo !== null && !($this->pdo->inTransaction())) {
            $this->stmt = null;
            //$this->pdo = null;
            $this->prep_sql = '';
            $this->prep_data = array();
        }

    }

    /**
     * connection
     * @param bool $isReader
     * @return mixed
     */
    protected function _connect($isReader = true) {
        $config = $isReader ? $this->_reader : $this->_writer;
        try {
            $hash = md5($config ['host'] . $config['database'] . $config['port'] . $config ['user']);
            if (!isset(self::$pdo_arr[$hash]) || !$this->pdo_ping(self::$pdo_arr[$hash])) {
                $pdo = new PDO ("mysql:host={$config ['host']};dbname={$config['database']};port={$config['port']};charset={$config['charset']}", $config ['user'], $config ['password'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2
                ));
                self::$pdo_arr[$hash] = $pdo;
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            Log::write('system_sql', 'connect', $msg, Log::LEVEL_ERROR);
        }
        return self::$pdo_arr[$hash];
    }

    /**
     * 获得链接
     * @param $isReader
     * @return mixed
     */
    function getConn($isReader) {
        // TODO: Implement getConn() method.
        $this->pdo = $this->_connect($isReader);
        return $this;
    }

    /**
     * prepared sql and data
     * @param $sql
     * @param $data
     * @return mixed
     * @throws Exception
     */
    function setData($sql, $data) {
        // TODO: Implement setData() method.
        if (!is_array($data)) {
            throw new Exception ('The data must be array.');
        }
        if (!is_string($sql)) {
            throw new Exception ('The sql must be string.');
        }
        $this->prep_sql = $sql;
        $this->prep_data = $data;

        return $this;
    }

    /**
     * execute
     * @return mixed
     * @throws Exception
     */
    function execute() {
        // TODO: Implement execute() method.
        $this->exec();
        return true;
    }

    /**
     * begin transaction
     * @return mixed
     */
    function beginTransaction() {
        // TODO: Implement beginTransaction() method.
        //开启事务之前清除参与执行sql
        if (!empty ($this->prep_sql)) {
            $this->prep_sql = '';
        }
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
        return $this;
    }

    /**
     * commit transaction
     * @return mixed
     * @throws Exception
     */
    function commit() {
        // TODO: Implement commit() method.
        $res = null;
        if ($this->pdo->inTransaction()) {
            try {
                $res = $this->pdo->commit();
            } catch (PDOException $e) {
                // 出问题回滚 写入日志
                $this->pdo->rollBack();
                $this->_close();
                Log::write('system_sql', 'transaction', $e->getMessage());
                return false;
            }
            $this->_close();
            return $res;
        }

        $this->_close();
        throw new Exception ('There is no active transaction.');
    }

    /**
     * rollback transaction
     * @return mixed
     */
    function rollback() {
        // TODO: Implement rollback() method.
        $res = false;

        if ($this->pdo->inTransaction()) {
            try {
                $res = $this->pdo->rollBack();
            } catch (PDOException $e) {
                $this->_close();
                Log::write('system_sql', 'transaction', $e->getMessage());
            }
        }
        $this->_close();
        return $res;
    }

    /**
     * get affected count
     * @return mixed
     * @throws Exception
     */
    function affectedCount() {
        // TODO: Implement affectedCount() method.
        $this->checkStatus();
        $this->execute();
        $res = $this->stmt->rowCount();
        $this->_close();
        return $res;
    }

    /**
     * get last insert id.
     * @return mixed
     * @throws Exception
     */
    function lastInsertId() {
        // TODO: Implement lastInsertId() method.
        $this->checkStatus();
        $this->execute();
        $res = $this->pdo->lastInsertId();
        $this->_close();
        return $res;
    }

    /**
     * get one result
     * @return mixed
     * @throws Exception
     */
    function fetchOne() {
        // TODO: Implement fetchOne() method.
        $this->checkStatus();
        $this->execute();
        $res = $this->stmt->fetch(PDO::FETCH_ASSOC);
        $this->_close();
        return $res;
    }

    /**
     * get all result
     * @return mixed
     * @throws Exception
     */
    function fetchAll() {
        // TODO: Implement fetchAll() method.
        $this->checkStatus();
        $this->execute();
        $res = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->_close();
        return $res;
    }

    /**
     * get column
     * @param $columnNum
     * @return mixed
     * @throws Exception
     */
    function fetchColumn($columnNum) {
        // TODO: Implement fetchColumn() method.
        $this->checkStatus();
        $this->execute();
        $res = $this->stmt->fetchColumn($columnNum);
        $this->_close();
        return $res;
    }

    /**
     * 校验链接
     *
     * @throws Exception
     */
    private function checkStatus() {
        if ($this->pdo === null) {
            throw new Exception ('pdo is null.');
        }
    }

    /**
     * 执行语句
     * 调整为单条执行，执行完毕后清理 sql
     * @throws Exception
     */
    private function exec() {
        // 校验数据是否正确
        if (!is_string($this->prep_sql) || !is_array($this->prep_data)) {
            throw new Exception ('Type is error.');
        }

        $this->stmt = $this->pdo->prepare($this->prep_sql, array(
            PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY
        ));
        $data_keys = array_keys($this->prep_data);
        $data_values = array_values($this->prep_data);
        $data_count = count($this->prep_data);
        for ($j = 0; $j < $data_count; $j++) {
            $type = $this->pdo_type [gettype($data_values [$j])];
            if ($type == PDO::PARAM_INT) {
                $data_values [$j] = (int)$data_values [$j];
            }
            $this->stmt->bindParam($data_keys [$j], $data_values [$j], $type);
        }
        $this->stmt->execute();
        $this->prep_sql = '';
        $this->prep_data = array();
    }


    /**
     * 检查连接是否可用
     * @param  PDO $dbconn 数据库连接
     * @return Boolean
     */
    function pdo_ping($dbconn) {
        try {
            $dbconn->getAttribute(PDO::ATTR_SERVER_INFO);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false) {
                return false;
            }
        }
        return true;
    }
}
