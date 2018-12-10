<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:23
 */
namespace Fisherman\DB;

interface DBInterface {
    /**
     * 获得链接
     * @param $isReader
     * @return $this
     */
    function getConn($isReader);

    /**
     * prepared sql and data
     * @param $sql
     * @param $data
     * @return $this
     */
    function setData($sql, $data);

    /**
     * execute
     * @return mixed
     */
    function execute();

    /**
     * begin transaction
     * @return mixed
     */
    function beginTransaction();

    /**
     * commit transaction
     * @return mixed
     */
    function commit();

    /**
     * rollback transaction
     * @return mixed
     */
    function rollback();

    /**
     * get affected count
     * @return mixed
     */
    function affectedCount();

    /**
     * get last insert id.
     * @return mixed
     */
    function lastInsertId();

    /**
     * get one result
     * @return mixed
     */
    function fetchOne();

    /**
     * get all result
     * @return mixed
     */
    function fetchAll();

    /**
     * get column
     * @param $columnNum
     * @return mixed
     */
    function fetchColumn($columnNum);
}