<?php

namespace App\Http;

/**
 * log記錄器
 *
 * @package App\Http
 */
class Logger
{
    /**
     * constructor
     */
    public function __construct()
    {

    }

    /**
     * 寫入log檔
     *
     * @param string $dirName
     * @param string $message
     */
    public function writeLog($dirName, $message = '')
    {
        $message = (string) $message;

        if (empty(getenv('LOG_DIRECT'))) {
            $dirName = APPLICATION_PATH . "/../logs/{$dirName}";
        } else {
            $dirName = getenv('LOG_DIRECT') . '/' . $dirName;
        }

        $completeFileName = $dirName . '/' . now()->format('Ymd');

        // 檢查目錄
        if (!is_dir($dirName)) {
            mkdir($dirName, 0750, true);
        }

        $content = "\n[" . now()->toDateTimeString() . "]\n";
        $content .= "ip: " . request()->getClientIp() . PHP_EOL;
        $content .= "route: " . request()->getRoute() . PHP_EOL;
        $content .= "message: {$message}\n";
        file_put_contents($completeFileName, $content, FILE_APPEND | LOCK_EX);
    }
}

