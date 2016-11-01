<?php

namespace App\Middleware;

use \Firebase\JWT\JWT;

/**
 * 新身份檢核
 * @todo JWT方式
 */
class GiantAuthMiddleware
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->checkJwt();
    }

    /**
     * JWT 身份檢核
     */
    public function checkJwt()
    {
        $authHeader = request()->getHeader('authorization', null);
        if ($authHeader !== null) {
            try {
                list($jwt) = sscanf($authHeader, 'Bearer %s');

                $secretKey = getenv('JWT_KEY');

                $token = JWT::decode($jwt, $secretKey, ['HS256']);
                return;
            } catch (\Exception $e) {

            }
        }
        $resp = ['retCode' => 0, 'retMsg' => '請重新登入'];
        $this->sendResponse($resp, 401);
    }

    /**
     * 送出回應
     *
     * @param int $httpStatusCode
     * @param array $msgArray
     */
    private function sendResponse($msgArray, $httpStatusCode = 200)
    {
        response()->setHttpResponseCode($httpStatusCode)
                  ->setHeader('Content-type', 'text/html; charset=utf-8')
                  ->setBody(json_encode($msgArray, JSON_UNESCAPED_UNICODE))
                  ->sendResponse();
    }
}