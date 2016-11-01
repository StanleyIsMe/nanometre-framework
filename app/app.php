<?php

// 實例Application
$app = \App\Http\Application::getInstance();

// 設定路由
require_once APPLICATION_PATH . "/app/Routes.php";
return $app;