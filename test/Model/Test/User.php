<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:58
 */

namespace Test\Model\Test;

use Fisherman\Model\BaseModel;

class User extends BaseModel {

    function __construct() {
        $this->_init("test");
    }

    public function getAllUser() {
        $res = $this->dao->conn(true)->setTag("user")->preparedSql("SELECT * FROM `user`", array())->fetchAll();
        return $res;
    }

    public function fetchOne() {
        $res = $this->dao->conn(true)->setTag("user")->preparedSql("SELECT * FROM `user` WHERE `age`=15", array())->fetchOne();
        return $res;
    }

    /**
     * @param $oldName
     * @param $newName
     * @return number
     */
    public function modifyName($oldName, $newName) {
        $res = $this->dao->conn(false)->setTag("user")
            ->preparedSql("UPDATE `user` SET `name`=:name WHERE `name`=:oldname", array(":name" => $newName, ":oldname" => $oldName))
            ->affectedCount();
        return $res;
    }
}