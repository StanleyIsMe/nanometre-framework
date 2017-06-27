<?php
namespace App\Http;

/**
 * request 處理器
 *
 * @package App\Http
 */
class Request
{
    CONST HTTP_METHOD_GET = 'GET';
    CONST HTTP_METHOD_POST = 'POST';
    CONST HTTP_METHOD_PUT = 'PUT';
    CONST HTTP_METHOD_PATCH = 'PATCH';
    CONST HTTP_METHOD_DELETE = 'DELETE';
    CONST HTTP_METHOD_HEAD = 'HEAD';
    CONST HTTP_METHOD_OPTIONS = 'OPTIONS';

    /**
     * @var array
     */
    public static $validMethodTypes = [
        self::HTTP_METHOD_GET,
        self::HTTP_METHOD_POST,
        self::HTTP_METHOD_PUT,
        self::HTTP_METHOD_PATCH,
        self::HTTP_METHOD_DELETE,
        self::HTTP_METHOD_HEAD,
        self::HTTP_METHOD_OPTIONS,
    ];

    /**
     * @var array request參數
     */
    private $param;

    /**
     * constructor
     */
    public function __construct()
    {

    }

    /**
     * 是否為ajax行為
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * 取得request 方法
     *
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 取得request 路由
     *
     * @return string
     */
    public function getRoute()
    {
        $route = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['QUERY_STRING'])) {
            $route = trim(str_replace('?' . $_SERVER['QUERY_STRING'], '', $route));
        }
        return $route;
    }

    /**
     * 取得request 參數
     *
     * @param string $key
     * @param mixed|null $def
     * @return mixed
     */
    public function getParam($key, $def = null)
    {
        if (empty($this->param)) {
            $this->param = $this->getParams();
        }

        if (!isset($this->param[$key])) {
            $this->param[$key] = $def;
        }
        return $this->param[$key];
    }

    /**
     * 取得所有request 參數
     *
     * @return array
     */
    public function getParams()
    {
        $this->param = [];
        switch ($_SERVER['REQUEST_METHOD']) {
            case self::HTTP_METHOD_GET:
                $this->param = $_GET;
                break;
            case self::HTTP_METHOD_POST:
                $this->param = $_POST;
                break;
        }

        /**
         * method => put,patch,...etc
         */
        if (empty($this->param)) {
            parse_str(file_get_contents('php://input'), $this->param);
        }

        /**
         * file upload
         */
        if (!empty($_FILES)) {
            $this->getFiles();
        }

        return $this->param;
    }

    /**
     * 取得Client端 IP
     * @return string
     */
    public function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * 取得 request 表頭
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getHeader($name, $default = '')
    {
        if (!function_exists('getallheaders')) {
            $headers = false;
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        } else {
            $headers = getallheaders();
        }

        $name = ucfirst($name);
        return ($headers && isset($headers[$name])) ? $headers[$name] : $default;
    }

    /**
     * 取得上傳的檔案
     */
    private function getFiles()
    {
        $fileArray = [];
        foreach ($_FILES as $key => $fileInfo) {
            if (UPLOAD_ERR_NO_FILE == $fileInfo['error']) {
                $file = null;
            } else {
                $file = new File($fileInfo['tmp_name'], $fileInfo['name'], $fileInfo['type'], $fileInfo['size'], $fileInfo['error']);
            }
            $fileArray[$key] = $file;
        }
        $this->param = array_merge($this->param, $fileArray);
    }

    /**
     * 是否使用合法method
     *
     * @param $method
     * @return bool
     */
    public static function isValidMethod($method)
    {
        return in_array($method, self::$validMethodTypes, true);
    }
}