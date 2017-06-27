<?php

namespace App\Http;

/**
 * response 處理器
 *
 * @package App\Http
 */
class Response
{
    /**
     * @var array 標頭
     */
    private $headers;

    /**
     * @var mixed 回傳資料
     */
    private $body;

    /**
     * @var int http status code
     */
    private $httpResponseCode = 200;

    /**
     * 設定標頭
     *
     * @param string $name
     * @param string $value
     * @param bool|false $replace
     * @return $this
     */
    public function setHeader($name, $value, $replace = false)
    {
        $value = (string) $value;

        if ($replace) {
            foreach ($this->headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->headers[$key]);
                }
            }
        }

        $this->headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );

        return $this;
    }

    /**
     * 設定http狀態碼
     *
     * @param int $code
     * @return $this
     * @throws \Exception
     */
    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new \Exception("Invalid HTTP status code {$code} is not valid.");
        }
        $this->httpResponseCode = $code;
        return $this;
    }

    /**
     * 設定回傳資料
     *
     * @param mixed $content
     * @return $this
     */
    public function setBody($content)
    {
        if ($content === null || !is_string($content)) {
            throw new \UnexpectedValueException('The Response content must be a string');
        }
        $this->body = $content;
        return $this;
    }

    /**
     * 送出標頭
     *
     * @return $this
     */
    public function sendHeader()
    {
        if (headers_sent()) {
            return $this;
        }

        if (count($this->headers) || (200 !== $this->httpResponseCode)) {
            foreach ($this->headers as $header) {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
            header('HTTP/1.1 ' . $this->httpResponseCode);
        } elseif (200 === $this->httpResponseCode) {
            return $this;
        }

        return $this;
    }

    /**
     * 送出資料
     */
    public function sendBody()
    {
        echo $this->body;
    }

    /**
     * response 送出流程
     */
    public function sendResponse()
    {
        ob_start();
        $this->sendHeader();
        $this->sendBody();
        ob_get_clean();
        exit(1);
    }

    /**
     * 取得回應內容
     *
     * @return array|mixed
     */
    public function getResponseContent()
    {
        if (empty($this->body)) {
            return [];
        }
        return $this->body;
    }

    /**
     * 送出json回應
     *
     * @param mixed $data
     * @param int $status
     * @throws \Exception
     */
    public function json($data = [], $status = 200)
    {
        $this->setHttpResponseCode($status)
             ->setHeader('Content-type', 'application/json')
             ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE))
             ->sendResponse();
    }

    /**
     * 送出html/text回應
     *
     * @param mixed $data
     * @param int $status
     * @throws \Exception
     */
    public function html($data = [], $status = 200)
    {
        $this->setHttpResponseCode($status)
             ->setHeader('Content-type', 'text/html; charset=utf-8')
             ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE))
             ->sendResponse();
    }
}