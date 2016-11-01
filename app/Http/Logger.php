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
     * @var string API log 檔的完整相對路徑 : 目錄
     */
    private $dirName = '';

    /**
     * @var string API log 檔的完整相對路徑 : 檔案
     */
    private $completeFileName = '';

    /**
     * @var string 寫入 Log 的訊息
     */
    private $message = '';

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
        $this->message = (string) $message;
        $this->dirName = getenv('LOG_DIRECT') . '/' . $dirName;
        $this->completeFileName = $this->dirName . '/' . now()->format('Ymd');

        // 檢查目錄
        if (!is_dir($this->dirName)) {
            mkdir($this->dirName, 0750, true);
        }

        $content = "[" . now()->toDateTimeString() . "]\t" . $this->message . "\n";
        file_put_contents($this->completeFileName, $content, FILE_APPEND | LOCK_EX);
    }
}

