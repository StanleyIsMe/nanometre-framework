<?php

namespace App\Middleware;

use App\Model\MongoFactory as MongoFactory;
use \Firebase\JWT\JWT;
/**
 * 身份檢核
 *
 * @package App\Middleware
 */
class AuthMiddleware
{
    /**
     * @var string
     */
    const SID_EMPTY = 'gf0002001';

    /**
     * @var string
     */
    const SID_TYPE_ERROR = 'gf0002002';

    /**
     * @var string
     */
    const SID_NOT_EXIST = 'gf0002003';

    /**
     * @var string
     */
    const SID_OVER_TIME = 'gf0002004';

    /**
     * constructor
     */
    public function __construct()
    {
       $this->checkJwt();
//        $this->checkSid();
//        $this->checkToken();
    }

    /**
     * 檢核sid
     *
     * @throws \App\Model\MongoException
     */
    public function checkSid()
    {
        $sid = request()->getParam('sid', '');
        $message = [
            'retCode' => -1,
            'retMsg'  => '',
            'retVal'  => $sid,
            'errCode' => ''
        ];

        // 解析 sid：user 與 sid 合法時間
        if (empty($sid)) {
            $message['retMsg'] = 'sid 為空';
            $message['errCode'] = self::SID_EMPTY;
            $this->sendResponse(401, $message);
        }

        if (!strpos($sid, ':')) {
            $message['retMsg'] = 'sid 格式錯誤';
            $message['errCode'] = self::SID_TYPE_ERROR;
            $this->sendResponse(401, $message);
        } else {

            list($userHash, $expired) = explode(':', $sid);
            if (empty($userHash) || empty($expired)) {
                $message['retMsg'] = 'sid 格式錯誤';
                $message['errCode'] = self::SID_TYPE_ERROR;
                $this->sendResponse(401, $message);
            }

            if ($expired < time()) {
                $message['retMsg'] = 'sid 已過期';
                $message['errCode'] = self::SID_OVER_TIME;
                $this->sendResponse(401, $message);
            }
        }

        /**
         * @var \App\Model\SidMongo $sidDb
         */
        $sidDb = MongoFactory::getInstance('Sid');
        $result = $sidDb->isExist($sid);

        if (empty($result)) {
            $message['retMsg'] = 'sid 不正確或使用者尚未登入';
            $message['errCode'] = self::SID_NOT_EXIST;
            $this->sendResponse(401, $message);
        }
    }

    /**
     * 檢查token正確否
     *
     * @throws \App\Model\MongoException
     */
    public function checkToken()
    {
        $token = $sid = request()->getParam('tk', '');
        /**
         * @var \App\Model\HistoryLoginMongo $historyLoginDb
         */
        $historyLoginDb = MongoFactory::getInstance('HistoryLogin');
        $message = [
            'retCode' => -2,
            'retMsg'  => '',
            'retVal'  => $token,
            'errCode' => ''
        ];

        $isPass = false;
        if (empty($token)) {
            $message['retMsg'] = '請提供登入憑證';
        } else {
            $row = $historyLoginDb->getByToken($token);

            if ($row === null) {
                $message['retMsg'] = '查無登入憑證';
            } elseif ($row['expire_time'] < now()->timestamp) {
                $message['retMsg'] = '登入憑證已過期';
            } else {
                $historyLoginDb->updateExpireTime($row['_id']);
                $isPass = true;
            }
        }

        if (!$isPass) {
            $this->sendResponse(401, $message);
        }

    }

    /**
     * 檢查後台登入提供的jwt
     */
    private function checkJwt()
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
        $this->sendResponse(401, $resp);
    }

    /**
     * 送出回應
     *
     * @param int $httpStatusCode
     * @param array $msgArray
     */
    private function sendResponse($httpStatusCode, $msgArray)
    {
        $resp = response();
        $resp->setHttpResponseCode($httpStatusCode)
             ->setHeader('Content-type', 'application/json')
             ->setBody(json_encode($msgArray, JSON_UNESCAPED_UNICODE))
             ->sendResponse();
    }
}