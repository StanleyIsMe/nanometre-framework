<?php

namespace App\Http;

use Closure;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 主程式
 *
 * @package App\Http
 */
class Application
{
    /**
     * The Application instance.
     *
     * @var \App\Http\Application
     */
    private static $instance = [];

    /**
     * 儲存路由
     *
     * @var array
     */
    private $routsArray = [];

    /**
     * 儲存中間層object
     *
     * @var array
     */
    private $middlewareArray = [];

    /**
     * constructor
     */
    public function __construct()
    {
        // setting catch all exception & error function
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'errorHandler']);

        foreach (Request::$validMethodTypes as $method) {
            $this->routsArray[$method] = [];
        }
    }

    /**
     * 取得Application instance
     *
     * @return \App\Http\Application
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 運行controller
     */
    public function run()
    {
        try {
            $requestMethod = $_SERVER['REQUEST_METHOD'];

            // 判斷請求Method是否正確
            if (!Request::isValidMethod($requestMethod)) {
                header('HTTP/1.1 405 Method Not Allowed.', true, 405);
                echo 'The ajax method is not correctly.';
                exit(1);
            }

            // 判斷請求路由是否有相對應
            if (($dispatchRouteInfo = $this->getDispatch()) === null) {
                header('HTTP/1.1 404 Not Found.', true, 404);
                echo 'Not Found.';
                exit(1);
            };

            $routBehavior = $this->routsArray[$requestMethod][$dispatchRouteInfo['route']];

            // 指定實作middleware
            foreach ($routBehavior['middleware'] as $middlewareObject) {
                app($middlewareObject);
            }

            // 指定實作的controller
            $controllerObj = $routBehavior['instance'];
            $action = $routBehavior['action'];

            $controllerInstance = app($controllerObj);

            call_user_func_array([
                $controllerInstance,
                $action
            ], $dispatchRouteInfo['param']);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 取得路由分配
     * @return array|null
     */
    private function getDispatch()
    {
        $requestUrl = $_SERVER['REQUEST_URI'];
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        if (isset($_SERVER['QUERY_STRING'])) {
            $requestUrl = trim(str_replace('?' . $_SERVER['QUERY_STRING'], '', $requestUrl));
        }

        // 無變數route 先判斷
        if (isset($this->routsArray[$requestMethod][$requestUrl])) {
            return ['route' => $requestUrl, 'param' => []];
        }

        $requestUrlDetail = explode('/', $requestUrl);

        foreach ($this->routsArray[$requestMethod] as $rout => $detail) {
            $ruleUrlDetail = explode('/', $rout);
            if (count($requestUrlDetail) !== count($ruleUrlDetail)) {
                continue;
            }

            $partOfDiff = array_diff($requestUrlDetail, $ruleUrlDetail);
            $isValidRequest = true;
            $param = [];
            foreach ($partOfDiff as $index => $content) {
                if (isset($ruleUrlDetail[$index])) {
                    preg_match('#\{\w+\}#', $ruleUrlDetail[$index], $match);
                    if (!empty($match)) {
                        $param[] = $requestUrlDetail[$index];
                    } else {
                        $isValidRequest = false;
                        break;
                    }
                } else {
                    $isValidRequest = false;
                    break;
                }
            }

            if ($isValidRequest) {
                return ['route' => $rout, 'param' => $param];
            }
        }
        return null;
    }

    /**
     * 設定中介層
     *
     * @param array $middlewareArray
     * @param Closure $callback
     *
     * @throws \Exception
     */
    public function setMiddleware(array $middlewareArray, Closure $callback)
    {
        foreach ($middlewareArray as $middlewareName) {
            $middlewareObject = "App\\Middleware\\{$middlewareName}Middleware";

            if (class_exists($middlewareObject)) {
                $this->middlewareArray[$middlewareName] = $middlewareObject;
            }
        }
        call_user_func($callback, $this);
        $this->middlewareArray = [];
    }

    /**
     * 設定路由
     *
     * @param string $method
     * @param string $url
     * @param string $target
     * @return $this
     */
    public function setRoute($method = 'GET', $url = 'index', $target = 'indexController@index')
    {
        $method = strtoupper($method);
        if (Request::isValidMethod($method)) {
            list($controller, $action) = explode('@', $target);
            $controllerObj = 'App\\Controller' . '\\' . $controller;
            if (!isset($this->routsArray[$method][$url]) && class_exists($controllerObj) && method_exists($controllerObj, $action)) {
                $this->routsArray[$method][$url] = [
                    'middleware' => $this->middlewareArray,
                    'instance'   => $controllerObj,
                    'method'     => $method,
                    'action'     => $action,
                ];
            }
        }
        return $this;
    }

    /**
     * 抓取error & exception 最後一道牆
     * @throws \Exception
     */
    public function errorHandler()
    {
        // Check (fatal shutdown)
        $e = error_get_last();

        // check (error handler)
        if ($e === null) {
            $e = func_get_args();
        }

        // Return if no error
        if (empty($e)) {
            return;
        }

        // check (exception handler)
        if (isset($e[0]) && $e[0] instanceof \Exception) {
            /**
             * @var \Exception $e
             */
            $e = $e[0];
            $message = "{$e->getFile()} => 行數:{$e->getLine()} => 原因:{$e->getMessage()}";
            app('logger')->writeLog('Exception', $message);
        } else {
            $e = array_combine(['number', 'message', 'file', 'line', 'context'], array_pad($e, 5, null));
            $message = "{$e['file']} => 行數:{$e['line']} => 原因:{$e['message']}";
            app('logger')->writeLog('Error', $message);
        }

        $msg = [
            'error' => [
                'message' => (getenv('APPLICATION_ENV') !== 'PRODUCTION') ? $message : 'Server Error',
                'status'  => 0
            ]
        ];

        response()->json($msg, 500);
    }

    /**
     * 建立 laravel ORM
     */
    private function setupDatabaseORM()
    {
        $capsule = new Capsule();

        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => getenv(APPLICATION_ENV . 'MYSQL_HOST'),
            'port'      => getenv(APPLICATION_ENV . 'MYSQL_PORT'),
            'database'  => getenv(APPLICATION_ENV . 'MYSQL_DB_NAME'),
            'username'  => getenv(APPLICATION_ENV . 'MYSQL_USER'),
            'password'  => getenv(APPLICATION_ENV . 'MYSQL_PASS'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}

