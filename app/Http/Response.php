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
            throw new \Exception('Invalid HTTP response code');
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
        $this->body = $content;
        return $this;
    }

    /**
     * 送出標頭
     *
     * @return $this
     */
    private function sendHeader()
    {
        if (count($this->headers) || (200 != $this->httpResponseCode)) {
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
    private function sendBody()
    {
        echo $this->body;die;
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
        exit;
    }
}