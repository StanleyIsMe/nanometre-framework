<?php

namespace App\Controller;

/**
 * 抽象base controller
 *
 * @package App\Controller
 */
abstract class BaseController
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * controller 初始行為
     */
    public function init()
    {

    }

    /**
     * 送出json回應
     *
     * @param array|object $msgArray
     * @param int $httpCode
     */
    protected function sendResponseWithJson($msgArray, $httpCode = 200)
    {
        response()->setHttpResponseCode($httpCode)
                  ->setHeader('Content-type', 'application/json')
                  ->setBody(json_encode($msgArray, JSON_UNESCAPED_UNICODE))
                  ->sendResponse();
    }

    /**
     * 送出html/text回應
     *
     * @param array $msgArray
     * @param int $httpCode
     */
    protected function sendResponseWithText(array $msgArray, $httpCode = 200)
    {
        response()->setHttpResponseCode($httpCode)
                  ->setHeader('Content-type', 'text/html; charset=utf-8')
                  ->setBody(json_encode($msgArray, JSON_UNESCAPED_UNICODE))
                  ->sendResponse();
    }
}