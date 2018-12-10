<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 20:24
 */

namespace Fisherman\Module;

abstract class ModuleAbstract {
    private static $_instance = null;

    private function __construct() {
    }

    /**
     *
     * @param $className __CLASS__
     * @return null
     */
    protected static function _getInstance($className) {
        if (self::$_instance === null) {
            self::$_instance = new $className();
        }
        return self::$_instance;
    }

    /**
     * close clone method.
     */
    private function __clone() {
        // TODO: Implement __clone() method.
        echo "Hi. Clone.";
    }
}