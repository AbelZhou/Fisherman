<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 20:35
 */

namespace Fisherman\Module;

interface ModuleInterface{
    /**
     * 单例
     * @return mixed
     */
    static function getInstance();
}