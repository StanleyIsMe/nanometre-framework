<?php

namespace App\Service;

use App\Model\MongoAbstract;
use App\Model\PosWebConnect as PosWebConnect;

/**
 * 捷安特促銷活動的service
 *
 * @package App\Service
 */
class PromotionService
{
    /**
     * @var int 行為成功
     */
    CONST PROMOTION_SUCCESS = 1;

    /**
     * @var int 活動過期
     */
    CONST PROMOTION_EXPIRED = 2;

    /**
     * @var int 優惠碼已被使用
     */
    CONST PROMOTION_USED = 3;

    /**
     * @var int 優惠碼錯誤
     */
    CONST PROMOTION_INVALID = 4;

    /**
     * @var int 不知名錯誤
     */
    CONST PROMOTION_SOMETHING_ERROR = 5;

    /**
     * @var \App\Model\PromotionInfoMongo
     */
    private $promotionInfoDb;

    /**
     * constructor
     * @param \App\Model\MongoAbstract $promotionInfoDb
     */
    public function __construct(MongoAbstract $promotionInfoDb)
    {
        $this->promotionInfoDb = $promotionInfoDb;
    }

    /**
     * 狀態碼對應訊息
     *
     * @param int|null $statusCode
     * @return array|string
     */
    public static function statusMapMsg($statusCode = null)
    {
        $msgArray = [
            self::PROMOTION_SUCCESS         => '此Ride Life優惠序號驗證成功',
            self::PROMOTION_EXPIRED         => '此Ride Life優惠序號已過期',
            self::PROMOTION_USED            => '此Ride Life優惠序號已被使用過',
            self::PROMOTION_INVALID         => '此Ride Life優惠序號輸入有誤，請重新檢查',
            self::PROMOTION_SOMETHING_ERROR => '不知名錯誤',
        ];

        return $statusCode === null ? $msgArray : $msgArray[$statusCode];
    }

    /**
     * 自捷安特Service取得活動
     *
     * @param array $otherParam
     * @param string $DateBeg
     * @param string $DateEnd
     * @return mixed
     * @throws \Exception
     */
    protected function getPromotionFromGiant(array $otherParam = [])
    {
        $posConnect = new PosWebConnect();

        // 日期起迄屬於 required
        $param = [
            'DateBeg' => '2015/01/01',
        ];
        $param = array_merge($otherParam, $param);
        return $posConnect->getApiResponse('GetPromotionTopLst', $param);
    }


    /**
     * 取得促銷活動
     *
     * @return array
     * @throws \Exception
     */
    public function getAllPromotion()
    {
        $promotionInfo = $this->getPromotionFromGiant(['Live' => 'Y']);

        $resp = [];
        if ($promotionInfo['retCode']) {
            $resp = map($promotionInfo['retVal'], function ($val) {
                return [
                    'pType' => $val['PType'],
                    'pNo'   => $val['PNo'],
                    'pName' => $val['PName'],
                ];

            });
        }
        return $resp;
    }

    /**
     * 檢核Coupon卷
     *
     * @param string $coupon
     * @param string $pNo
     * @return array
     */
    public function validateCoupon($coupon, $pNo)
    {
        // 活動是否存在而且進行中
        $promotionInfo = $this->getPromotionFromGiant(['PNo'  => $pNo, 'Live' => 'Y']);
        if ($promotionInfo['retCode'] === 0) {
            return [
                'retMsg'  => self::statusMapMsg(self::PROMOTION_EXPIRED),
                'retCode' => self::PROMOTION_EXPIRED
            ];
        }

        // coupon卷資訊
        $couponData = $this->promotionInfoDb->getByCouponPno($coupon, $pNo);

        // 優惠碼是否存在
        if ($couponData === null) {
            return [
                'retMsg'  => self::statusMapMsg(self::PROMOTION_INVALID),
                'retCode' => self::PROMOTION_INVALID
            ];
        }

        // 優惠碼是否已使用
        if ($couponData['couponInfo'][0]['isUsed']) {
            return [
                'retMsg'  => self::statusMapMsg(self::PROMOTION_USED),
                'retCode' => self::PROMOTION_USED
            ];
        }

        return [
            'retMsg'  => self::statusMapMsg(self::PROMOTION_SUCCESS),
            'retCode' => self::PROMOTION_SUCCESS,
        ];
    }

    /**
     * Coupon卷使用
     *
     * @param string $coupon
     * @param string $shopNo
     * @param string $date
     * @param string $pNo
     * @return bool
     * @throws \Exception
     */
    public function transactionCoupon($coupon, $shopNo, $date, $pNo)
    {
        // 活動是否存在而且進行中
        $promotionInfo = $this->getPromotionFromGiant(['PNo'  => $pNo, 'Live' => 'Y']);
        if ($promotionInfo['retCode'] === 0) {
            return false;
        }

        $result = $this->promotionInfoDb->updateCouponInfo($coupon, $shopNo, $date, $pNo, 'transaction');
        return $result['updatedExisting'];
    }

    /**
     * 取消Coupon卷使用
     *
     * @param string $coupon
     * @param string $shopNo
     * @param string $date
     * @param string $pNo
     * @return bool
     * @throws \Exception
     */
    public function cancelTransaction($coupon, $shopNo, $date, $pNo)
    {
        // 活動是否存在而且進行中
        $promotionInfo = $this->getPromotionFromGiant(['PNo'  => $pNo, 'Live' => 'Y']);
        if ($promotionInfo['retCode'] === 0) {
            return false;
        }

        $result = $this->promotionInfoDb->updateCouponInfo($coupon, $shopNo, $date, $pNo, 'cancel');
        return $result['updatedExisting'];
    }
}