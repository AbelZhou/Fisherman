<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/11
 * Time: 17:40
 */

namespace Test\Module\Test;

use Fisherman\Module\ModuleAbstract;
use Fisherman\Module\ModuleInterface;

class User extends ModuleAbstract implements ModuleInterface {
    /**
     * @var \Test\Model\Test\User
     */
    private $userModel = null;


    /**
     * be used in construct.
     * @return mixed
     */
    protected function init() {
        // TODO: Implement initClass() method.
        $this->userModel = new \Test\Model\Test\User();
    }

    public function getAll() {
        return $this->userModel->getAllUser();
    }

    public function fetchOne() {
        return $this->userModel->fetchOne();
    }


    public function modifyOne($oldName, $newName) {
        return $this->userModel->modifyName($oldName, $newName);
    }


}