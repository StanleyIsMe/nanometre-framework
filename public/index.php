<?php
/**
 * 入口
 */
require __DIR__ . '/vendor/autoload.php';
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../app'));

// 讀.env 判斷環境是正式機或測試機
$env = new Dotenv\Dotenv(APPLICATION_PATH);
$env->load();
$env->required('APPLICATION_ENV')->allowedValues(['PRODUCTION', 'DEVELOPMENT']);
define('APPLICATION_ENV', getenv('APPLICATION_ENV') . '_');

ini_set('display_errors', getenv(APPLICATION_ENV . 'DEBUG'));

// 主程式
$app = require_once APPLICATION_PATH . "/app.php";
$app->run();










