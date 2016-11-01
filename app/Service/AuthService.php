<?php

namespace App\Service;

use App\Model\MongoAbstract;
use \Firebase\JWT\JWT;

class AuthService
{
    /**
     * @var \App\Model\SidMongo
     */
    private $sidDb;

    /**
     * @var \App\Model\BackMemberMongo
     */
    private $backMemberDb;

    /**
     * constructor
     * @param \App\Model\MongoAbstract $sidDb
     */
    public function __construct(MongoAbstract $sidDb, MongoAbstract $backMemberDb)
    {
        $this->sidDb = $sidDb;
        $this->backMemberDb = $backMemberDb;
    }

    /**
     * 捷安特api登入
     *
     * @param string $account
     * @param string $password
     * @return string
     * @throws \Exception
     */
    public function giantLogin($account, $password)
    {
        $password = hash('sha1', $password);
        $sidInfo = $this->sidDb->login($account, $password);

        if ($sidInfo === null) {
            throw new \Exception('帳號或密碼錯誤');
        }

        /**
         * jwt 格式: header+claim(payload)+signature
         */
        $payload = [
            'iss'  => 'http://gfdev.program.com.tw',//識別client端
            'aud'  => 'MP',                         //簽發 JWT 的單位 (Token Provider)
            'iat'  => now()->timestamp,             //申請時間
            'exp'  => now()->addHour(4)->timestamp,   //到期時間
            'nbf'  => 0,                            //多久後啟動此token
            'jti'  => sha1($account . now()->format('Y-m-d H:i:s') . uniqid()),                      //json token id
            'data' => []                            // 存用戶資料
        ];

        $secretKey = getenv('JWT_KEY');
        $jwt = JWT::encode($payload, $secretKey, 'HS256');
        return $jwt;
    }

    /**
     * 後台登入取token
     * @todo
     *
     * @param $shopNo
     * @param $empNo
     * @param $empPw
     * @return string
     * @throws \Exception
     */
    public function backLogin($shopNo, $empNo, $empPw)
    {
        $newToken = md5(uniqid(rand(), true) . $empPw);
        $isExist = $this->backMemberDb->isExist($shopNo, $empNo, md5($empPw));

        if (!$isExist) {
            throw new \Exception('登入失敗');
        }

        /**
         * jwt 格式: header+claim(payload)+signature
         */
        $payload = [
            'iss'  => 'http://gfdev.program.com.tw',//識別client端
            'aud'  => 'MP',                         //簽發 JWT 的單位 (Token Provider)
            'iat'  => now()->timestamp,             //申請時間
            'exp'  => now()->addHour(4)->timestamp,   //到期時間
            'nbf'  => 0,                            //多久後啟動此token
            'jti'  => $newToken,                      //json token id
            'data' => []                            // 存用戶資料
        ];

        $secretKey = getenv('JWT_KEY');
        $jwt = JWT::encode($payload, $secretKey, 'HS256');
        return $jwt;
    }
}