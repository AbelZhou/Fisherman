<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/12
 * Time: 14:31
 */
$runtimeEnv = empty($_SERVER['RUNTIME_ENV']) ? "local" : $_SERVER["RUNTIME_ENV"];
$rootPath = __DIR__;

define("RUNTIME_ENV", $runtimeEnv);
define("ROOTPATH", $rootPath);

require_once "vendor/autoload.php";