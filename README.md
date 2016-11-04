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
1. git clone xxxxxxxxxxxx
2. composer install
3. composer dump-autoload

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
  $app->setRoute('ALL', '/member/edit', 'MemberController@editAddress');
}

$app->setMiddleware(['Auth', 'Csrf'], function () use ($app) {
  $app->setRoute('DELETE', '/member/delete', 'MemberController@deleteMember');
  $app->setRoute('PUT', '/member/update', 'MemberController@updateMemberInfo');
}
```
ps:   
* HTTP Method 僅支援=>GET、POST、PUT、PATCH、DELETE、HEAD、OPTIONS
* ALL 代表接受任何Method

### Controller

app/Controller/IndexController.php 基本構造，每支controller必須得繼承BaseController  
init方法相當於construct
```PHP
<?php

namespace App\Controller;

class IndexController extends BaseController
{
    /**
     * 初始行為
     */
    public function init()
    {

    }

    public function index()
    {
        echo "Hellow nanometre";
    }
}
```

### Request

ex1:   
取得url 或 form data參數，若取不到值則回傳'John'  
app('request') 等於 request()

```PHP
<?php

namespace App\Controller;

class IndexController extends BaseController
{
    /**
     * 初始行為
     */
    public function init()
    {

    }

    public function index()
    {
        $name = request()->getParam('name', 'John');
        $age = app('request')->getParam('name', 18);
    }
}
```

### Response

ex1:    
app('response') 等於 response()
範例這串程式碼已經寫在BaseController，可以直接 $this->sendResponseWithJson($array, 200); 來調用

```PHP
<?php

namespace App\Controller;

class IndexController extends BaseController
{
    /**
     * 初始行為
     */
    public function init()
    {

    }

    public function index()
    {
        response()->setHttpResponseCode(200)
                  ->setHeader('Content-type', 'application/json')
                  ->setHeader('X-Header-One', 'Header Value')
                  ->setHeader('X-Header-Two', 'Header Value')
                  ->setBody(json_encode(['status' => 1, 'memberName' => 'Stanley'], JSON_UNESCAPED_UNICODE))
                  ->sendResponse();
    }
}
```

### MongoDb

ex1:   
通過工廠模式，實例 app/Model/MemberMongo.php
```PHP
<?php

namespace App\Controller;
use App\Model\MongoFactory;

class IndexController extends BaseController
{
    /**
     * 初始行為
     */
    public function init()
    {

    }

    public function index()
    {
        $memberDb = MongoFactory::getInstance('Member')
        $allMemberData = $memberDb->all();
    }
}
```
$_collection指定對應Mongo collection表  
語法皆是純PHP原生[MongoCollection](http://php.net/manual/en/class.mongocollection.php)  

```PHP
<?php
namespace App\Model;

/**
 * 會員資料
 *
 * @package App\Model
 */
class MemberMongo extends MongoAbstract
{
    /**
     * @var string table name
     */
    protected $_collection = 'member';

    /**
     * 依id，取得資料
     *
     * @param array $ids
     * @param array $select
     * @return array
     */
    public function getById(array $ids, array $select = [])
    {
        $where = [
            'memberid' => ['$in' => $ids]
        ];

        if (empty($select)) {
            $result = $this->table()->find($where);
        } else {
            $field['_id'] = 0;
            foreach ($select as $key) {
                $field[$key] = 1;
            }
            $result = $this->table()->find($where, $field);
        }

        return iterator_to_array($result);
    }
}
```
### Validator

檢核項目:  
* required => 值不得為空字串、空陣列、Null  
* int => 是否為Integer  
* string => 是否為字串 
* bool => 是否為布林值  
* array => 是否為陣列
* numeric => 是否為數字
* date => 是否為日期格式  
* Size => 是否符合指定數值或檔案大小  
* Min => 等於或大於指定數值
* Max => 等於或小於指定數值

ex1:  
validator() 等於 app('validator')  
validator()->isPass() 此方法一定要調用，不然不會去執行檢核  
透過.階層 parent.father.age 等於去檢查 ['parent' => ['father' => ['age' => 60], 'mother' => ['age' => 55]]] ，age是否為Integer
```PHP
<?php

namespace App\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        $rules = [
            'name' => ['required', 'string'],
            'nickname' => ['required', 'string'],
            'age' => ['required', 'int', 'Min:1', 'Max:120'],
            'id' =>['required', 'array'],
            'address' => ['string'],
            'phone' => ['required', 'string'],
            'mail' => ['required', 'string'],
            'isSingle' => ['bool'],
            'birthday' => ['date'],
            'parent.father.age' => ['int', 'Min:1', 'Max:120'],
            'parent.mother.age' => ['int', 'Min:1', 'Max:120']
        ];

        $validate = validator(request()->getParams(), $rules);
        if (!$validate->isPass()) {
            $resp['error'] = [
                'status' => self::FAIL_CODE,
                'message' => $validate->getErrorMessage()
            ];
            $this->sendResponseWithJson($resp, 400);
        }
    }
}
```

### Logger

ex1:  
log預設紀錄在 project底下logs，第一個參數是指定目錄logs/Error/，第二個參數僅限字串內容  
若想更改log路徑，改變 .env底下LOG_DIRECT設定
```PHP
<?php

namespace App\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        logger('Error', '發生錯誤')
    }
}
```

## Info
[laravel官方](https://laravel.tw/docs/5.3)

