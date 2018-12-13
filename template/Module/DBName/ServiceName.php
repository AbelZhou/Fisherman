<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/12
 * Time: 12:34
 */

namespace Module\DBName;

use Fisherman\Module\ModuleAbstract;
use Fisherman\Module\ModuleInterface;
use Model\DBName\Tablename;

class ServiceName extends ModuleAbstract implements ModuleInterface {
    /**
     * @var Tablename
     */
    private $tablenameModel = null;

    /**
     * be used in construct.
     * @return mixed
     */
    protected function init() {
        // TODO: Implement init() method.
        $this->tablenameModel = new Tablename();
    }

    public function someFunc(){
        return $this->tablenameModel->someFunc();
    }

}