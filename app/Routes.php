<?php
$app->setRoute('POST', '/auth/login', 'AuthController@login');

$app->setMiddleware(['Auth','Csrf'], function () use ($app) {
    $app->setRoute('GET', '/push/getPromotionType', 'IndexController@index');
});




