<?php
/**
 *
 * Author: abel
 * Email:abel.zhou@hotmail.com
 * Date: 2018/12/4
 * Time: 15:53
 */

namespace Fisherman\Func;

use Fisherman\Core\Config;

class Log {
    const LEVEL_DEBUG = 0;
    const LEVEL_INFO = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;

    public static function write($module_name, $notice_header, $notice_body = '', $MSG_LEVEL = 1) {
        $configure = Config::getFile("config");

        if (isset($configure["log"]["level"])) {
            if ($MSG_LEVEL <= (int)$configure["log"]["level"]) {
                return false;
            }
        }


        if (isset($configure["log"]["path"])) {
            $path = ROOTPATH . $configure["log"]["dir"];
        } else {
            $path = ROOTPATH . "/log/";
        }
        if (!file_exists($path)) {
            if (!mkdir($path, 0755) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
        $date_str = date('Y-m-d');
        $date_fmt = date('Y-m-d H:i:s', time());
        $path = $path . $module_name . '/';
        if (!file_exists($path)) {
            if (!mkdir($path, 0755) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
        $filePath = $path . $date_str . '.log';
        if (!$fp = @fopen($filePath, 'ab')) {
            return false;
        }

        $level_msg = "[UNDEFINE]";
        switch ($MSG_LEVEL) {
            case Log::LEVEL_DEBUG:
                $level_msg = "[DEBUG]";
                break;
            case Log::LEVEL_INFO:
                $level_msg = "[INFO]";
                break;
            case Log::LEVEL_WARNING:
                $level_msg = "[WARNING]";
                break;
            case Log::LEVEL_ERROR:
                $level_msg = "[ERROR]";
                break;
            default:
        }

        $message = $date_fmt . '::' . str_pad($level_msg, 10, " ", STR_PAD_RIGHT) . $notice_header . '::' . $notice_body . "\n";
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($filePath, 0755);
        return true;
    }
}