<?php

$app->setMiddleware(['Auth'], function () use ($app) {
    $app->setRoute('GET', '/push/getPromotionType', 'PushController@getPromotionType');
});




