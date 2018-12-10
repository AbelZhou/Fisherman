<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 16:13
 */

namespace Fisherman\Core;

use Symfony\Component\Yaml\Yaml;

class Config {
    /**
     * @param $configFileName
     * @return mixed
     */
    public static function getFile($configFileName) {
        $configPath = ROOTPATH . "/Conf/" . RUNTIME_ENV . "/" . $configFileName . ".yml";
        return YAML::parseFile($configPath);
    }
}