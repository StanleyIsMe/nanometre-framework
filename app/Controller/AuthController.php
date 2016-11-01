<?php

namespace App\Controller;

use App\Model\MongoFactory;
use App\Service\AuthService;

class AuthController extends BaseController
{
    /**
     * @var int ok
     */
    const SUCCESS_CODE = 1;

    /**
     * @var int error or not found
     */
    const FAIL_CODE = 0;

    /**
     * @var \App\Service\AuthService
     */
    private $authService;

    /**
     * 初始行為
     */
    public function init()
    {
        $this->authService = new AuthService(
            MongoFactory::getInstance('Sid'),
            MongoFactory::getInstance('BackMember')
        );
    }

    /**
     * 後台login
     * @todo 重構完登入再調整
     *
     * @method post
     * @response json
     */
    public function login()
    {
        if (!request()->isXmlHttpRequest()) {
            $resp = [
                'status'  => self::FAIL_CODE,
                'message' => 'request method should be XmlHttpRequest'
            ];
            $this->sendResponseWithJson($resp, 405);
        }

        $rules = [
            'ShopNo' => ['required', 'string'],
            'EmpNo'  => ['required', 'string'],
            'EmpPw'  => ['required', 'string'],
        ];

        $validate = validator(request()->getParams(), $rules);
        if (!$validate->isPass()) {
            $resp = [
                'retCode'  => self::FAIL_CODE,
                'retMsg' => $validate->getErrorMessage()
            ];
            $this->sendResponseWithJson($resp, 400);
        }

        $token = $this->authService->backLogin(
            request()->getParam('ShopNo'),
            request()->getParam('EmpNo'),
            request()->getParam('EmpPw')
        );

        $resp = [
            'status' => self::SUCCESS_CODE,
            'token' => $token
        ];
        $this->sendResponseWithJson($resp);
    }

    /**
     * 新apiLogin，採JWT
     *
     * @method post
     * @response json
     */
    public function giantApiLogin()
    {
        try {
            $rules = [
                'account' => ['required', 'string'],
                'passwd'  => ['required', 'string'],
            ];

            $validate = validator(request()->getParams(), $rules);
            if (!$validate->isPass()) {
                $resp = [
                    'retCode'  => self::FAIL_CODE,
                    'retMsg' => $validate->getErrorMessage()
                ];
                $this->sendResponseWithText($resp, 400);
            }

            $token = $this->authService->giantLogin(request()->getParam('account'), request()->getParam('passwd'));

            $resp = [
                'retCode' =>self::SUCCESS_CODE,
                'retMsg' => '登入成功',
                'retVal' => $token
            ];
            $message = 'IP: [' . request()->getClientIp() . '] 登入成功';
            logger('Auth/apiLogin', $message);
            $this->sendResponseWithText($resp);
        } catch (\Exception $e) {
            $message = 'IP: [' . request()->getClientIp() . '] 登入失敗，原因: ' . $e->getMessage();
            logger('Auth/apiLogin', $message);
            $resp = ['retCode' => self::FAIL_CODE, 'retMsg' => '登入失敗'];
            $this->sendResponseWithText($resp);
        }
    }
}