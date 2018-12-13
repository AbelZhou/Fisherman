<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/12
 * Time: 12:05
 */
namespace Model\DBName;
use Fisherman\Model\BaseModel;

class Tablename extends BaseModel{

    public function __construct() {
        parent::_init("db_tag");
    }

    public function someFunc(){
        return $this->dao->conn(false)->setTag("cache_tag_")->preparedSql("some sql",array())->fetchOne();
    }
}