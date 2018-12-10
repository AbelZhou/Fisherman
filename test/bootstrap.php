<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 14:57
 */

require "../vendor/autoload.php";
$runtimeEnv = empty($_SERVER['RUNTIME_ENV']) ? "local" : $_SERVER["RUNTIME_ENV"];
$rootPath = __DIR__;

define("RUNTIME_ENV", $runtimeEnv);
define("ROOTPATH", $rootPath);

$user = new \Test\Model\Test\User();
$all = $user->getAllUser();
$one = $user->fetchOne();
var_dump($all,$one);
$changeName = $user->modifyName("张三", "张三三");
print "change res:{$changeName}" . PHP_EOL;
$afterChangeName = $user->fetchOne();
var_dump("after change",$afterChangeName);
$user->modifyName("张三三","张三");
$afterChangeName = $user->fetchOne();
var_dump("after change",$afterChangeName);