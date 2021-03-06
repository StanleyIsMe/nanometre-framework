<?php
use Carbon\Carbon;
use App\Http\Container;

/**
 * helper 工具
 */

if (!function_exists('app')) {
    /**
     * Container instance或者其他object instance.
     *
     * @param null $objectName
     * @param array $param
     * @return mixed|\App\Http\Container
     * @throws \Exception
     */
    function app($objectName = null, $param = [])
    {
        if ($objectName === null) {
            return Container::getInstance();
        }
        return Container::getInstance()->build($objectName, $param);
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
     * @param bool $isDetail
     * @return \App\Http\Logger
     */
    function logger($dirName = '', $message = '', $isDetail = false)
    {
        if (trim($dirName) != '' && trim($message) != '') {
            app()->make('logger')->writeLog($dirName, $message, $isDetail);
        }
        return app()->make('logger');
    }
}

if (!function_exists('keyOfIndex')) {
    /**
     * 某key值當index
     *
     * @param array $input
     * @param string $key
     * @return array
     */
    function keyOfIndex(array &$input, $key)
    {
        if (empty($input)) {
            return [];
        }

        $idToKey = [];
        foreach ($input as $doc) {
            if (!isset($doc[$key])) {
                throw new InvalidArgumentException("{$key} is not exist");
            }
            $idToKey[$doc[$key]] = $doc;
        }
        $input = $idToKey;
        return $input;
    }
}

if (!function_exists('ifNotExist')) {
    /**
     * 升級php7後，即可用 ?? 替換
     *
     * @param mixed $input
     * @param mixed $def
     * @return mixed
     */
    function ifNotExist($input, $def)
    {
        return isset($input) ? $input : $def;
    }
}

if (!function_exists('randString')) {
    /**
     * 隨機字串組合
     *
     * @param int $length
     * @param string $keySpace
     * @return string
     */
    function randString($length, $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keySpace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[rand(0, $max)];
        }
        return $str;
    }
}

