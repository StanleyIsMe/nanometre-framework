<?php

use Carbon\Carbon;
use App\Http\Application;

/**
 * helper 工具
 */

if (!function_exists('app')) {
    /**
     * 取得Application instance或者其他object instance.
     *
     * @return mixed|\App\Http\Application
     */
    function app($objectName = null, $param = [])
    {
        if ($objectName === null) {
            return Application::getInstance();
        }
        return Application::getInstance()->make($objectName, $param);
    }
}

if (!function_exists('now')) {
    /**
     * 取得當下時間object
     *
     * @link http://carbon.nesbot.com/
     * @param string $timeZone
     * @return Carbon
     */
    function now($timeZone = 'Asia/Taipei')
    {
        return Carbon::now($timeZone);
    }
}

if (!function_exists('map')) {
    /**
     * foreach array內容至另外array
     *
     * @param array $input
     * @param callable $func
     * @return array
     */
    function map($input, callable $func)
    {
        $result = [];

        foreach ($input as $each) {
            $data = $func($each);
            if (!empty($data)) {
                $result[] = $data;
            }
        }
        return $result;
    }
}

if (!function_exists('response')) {
    /**
     * 取得response instance.
     *
     * @return \App\Http\Response
     */
    function response()
    {
        return app()->make('response');
    }
}

if (!function_exists('request')) {
    /**
     * 取得request instance.
     *
     * @return \App\Http\Request
     */
    function request()
    {
        return app()->make('request');
    }
}

if (!function_exists('validator')) {
    /**
     * 取得validator instance.
     *
     * @param array $data
     * @param array $rules
     * @return \App\Http\Validator
     */
    function validator(array $data, array $rules)
    {
        return app()->make('validator', [
            $data,
            $rules
        ]);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function dd()
    {
        array_map(function ($x) {
            var_dump($x);
        }, func_get_args());

        die(1);
    }
}

if (!function_exists('logger')) {
    /**
     * logger instance.
     *
     * @param string $dirName
     * @param string $message
     * @return \App\Http\Logger
     */
    function logger($dirName = '', $message = '')
    {
        if (trim($dirName) != '' && trim($message) != '') {
            app()->make('logger')->writeLog($dirName, $message);
        }
        return app()->make('logger');
    }
}