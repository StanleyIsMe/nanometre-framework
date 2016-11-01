<?php

namespace App\Http;

use Closure;
use ReflectionClass;

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
     * 儲存物件 instance
     *
     * @var array
     */
    private $instanceArray = [];

    /**
     * 儲存路由
     *
     * @var array
     */
    private $routsArray = [];

    /**
     * 儲存物件別名
     *
     * @var array
     */
    private $aliases = [];

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
        $this->registerAliases();

        // setting catch all exception & error function
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'errorHandler']);
        register_shutdown_function([$this, 'errorHandler']);
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
     * 物件實例化array,實現single pattern
     *
     * @param string $alias
     * @param array $param
     * @return object
     * @throws \Exception
     */
    public function make($alias, $param = [])
    {
        if (isset($this->instanceArray[$alias])) {
            return $this->instanceArray[$alias];
        }

        if ((isset($this->aliases[$alias]) && class_exists($this->aliases[$alias])) || class_exists($alias)) {
            $objectName = isset($this->aliases[$alias]) ? $this->aliases[$alias] : $alias;

            $this->instanceArray[$alias] = call_user_func_array([
                new ReflectionClass($objectName),
                'newInstance'
            ], $param);
            return $this->instanceArray[$alias];
        } else {
            throw new \Exception("{$alias} is not a object");
        }
    }

    /**
     * 運行controller
     */
    public function run()
    {
        try {
            $requestUrl = $_SERVER['REQUEST_URI'];
            if (isset($_SERVER['QUERY_STRING'])) {
                $requestUrl = trim(str_replace('?' . $_SERVER['QUERY_STRING'], '', $requestUrl));
            }

            if ($_SERVER['REQUEST_METHOD'] === $this->routsArray[$requestUrl]['method'] || $this->routsArray[$requestUrl]['method'] === 'ALL') {
                // 指定實作middleware
                foreach ($this->routsArray[$requestUrl]['middleware'] as $middlewareObject) {
                    $this->make($middlewareObject);
                }

                // 指定實作的controller
                $controllerObj = $this->routsArray[$requestUrl]['instance'];
                $action = $this->routsArray[$requestUrl]['action'];
                $controllerInstance = $this->make($controllerObj);
                $controllerInstance->$action();
            } else {
                header('HTTP/1.1 405 Method Not Allowed.', true, 405);
                echo 'The ajax method is not correctly.';
                exit(1);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 設定中介層
     *
     * @param array $middlewareArray
     * @param Closure $callback
     *
     * @throws \Exception
     */
    public function setMiddleware(array $middlewareArray = ['Auth'], Closure $callback)
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
     */
    public function setRoute($method = 'ALL', $url = 'index', $target = 'indexController@index')
    {
        $method = strtoupper($method);
        if ($method === 'ALL' || Request::isValidMethod($method)) {
            list($controller, $action) = explode('@', $target);
            $controllerObj = 'App\\Controller' . '\\' . $controller;
            if (!isset($this->routsArray[$url]) && class_exists($controllerObj) && method_exists($controllerObj, $action)) {
                $this->routsArray[$url] = [
                    'middleware' => $this->middlewareArray,
                    'instance'   => $controllerObj,
                    'method'     => $method,
                    'action'     => $action
                ];

            }
        }

    }

    /**
     * 登錄物件別名
     */
    public function registerAliases()
    {
        $this->aliases = [
            'app'       => '\App\Http\Application',
            'request'   => '\App\Http\Request',
            'response'  => '\App\Http\Response',
            'validator' => '\App\Http\Validator',
            'logger'    => '\App\Http\Logger'
        ];
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
            $this->make('logger')->writeLog('Exception', $message);
        } else {
            $e = array_combine(['number', 'message', 'file', 'line', 'context'], array_pad($e, 5, null));
            $message = "{$e['file']} => 行數:{$e['line']} => 原因:{$e['message']}";
            $this->make('logger')->writeLog('Error', $message);
        }

        $msg = [
            'error' => [
                'message' => 'Server Error',
                'status'  => 0
            ]
        ];

        response()->setHttpResponseCode(500)
                  ->setHeader('Content-type', 'application/json')
                  ->setBody(json_encode($msg, JSON_UNESCAPED_UNICODE))
                  ->sendResponse();
    }
}

