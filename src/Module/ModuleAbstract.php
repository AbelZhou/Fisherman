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

    private final function __construct() {
        $this->init();
    }

    /**
     * Singleton
     * @return static
     */
    public static function getInstance() {
        if (self::$_instance === null) {
            $className = get_called_class();
            if ($className == false) {
                throw new \RuntimeException("Module class not found.");
            }
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

    /**
     * be used in construct.
     * @return mixed
     */
    abstract protected function init();
}