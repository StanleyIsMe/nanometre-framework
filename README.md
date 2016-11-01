# Nanometre PHP Framework

Nanometre 是一個極小的PHP框架，只有MC功能。適合web架構採用前後端分離，及單純CRUD應用。使用方式極度類似 [Lumen](https://lumen.laravel.com/) ，因此非常好上手。

ps: 資料庫暫時只支援 [MongoDb](https://docs.mongodb.com/manual/)

## Environment
requires
* PHP:5.6.*
* vlucas/phpdotenv:2.*
* nesbot/carbon: 1.2.*

requires (dev)

* codeception/codeception: 2.2.*

## Installation
1. composer install
2. composer dump-autoload

## Usage

###Router
路由對應controller則是在app/Routes.php設定

指定路由名稱到你的controller操作  
ex:根路徑藉由GET Method指定IndexController的index方法
```PHP
$app->setRoute('GET', '/', 'IndexController@index');
```

指定哪些路由需要middleware，也可以透過array 執行多個middleware  
ex 1:先執行app/Middleware/AuthMiddleware，再走controller
```PHP
$app->setMiddleware(['Auth'], function () use ($app) {
  $app->setRoute('GET', '/member/search', 'MemberController@search');
}
```

ex 2:先執行app/Middleware/AuthMiddleware，再執行app/Middleware/CsrfMiddleware
```PHP
$app->setMiddleware(['Auth'], function () use ($app) {
  $app->setRoute('GET', '/member/search', 'MemberController@search');
  $app->setRoute('POST', '/member/add', 'MemberController@createMember');
}

$app->setMiddleware(['Auth', 'Csrf'], function () use ($app) {
  $app->setRoute('DELETE', '/member/delete', 'MemberController@deleteMember');
  $app->setRoute('PUT', '/member/update', 'MemberController@updateMemberInfo');
}
```
ps: HTTP Method 僅支援=>GET、POST、PUT、PATCH、DELETE、HEAD、OPTIONS







