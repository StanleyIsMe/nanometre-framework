<?php

namespace App\Service;

use App\Dto\PromotionPushDto;
use App\Http\File;
use App\Model\Push\PushTypeFactory;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Model\PosWebConnect as PosWebConnect;

/**
 * 推播專門的service
 *
 * @package App\Service
 */
class PushService
{
    /**
     * @var \App\Model\PromotionInfoMongo
     */
    private $promotionInfoDb;

    /**
     * @var \App\Model\PushTemplateMongo
     */
    private $pushTemplateDb;

    /**
     * @var \App\Model\NotificationMongo
     */
    private $notificationDb;

    /**
     * @var \App\Model\RideTimeLineMongo
     */
    private $rideTimeLineDb;

    /**
     * @var \App\Model\MemberMongo
     */
    private $memberDb;

    /**
     * constructor
     * @param array $repositoryArray
     */
    public function __construct($repositoryArray)
    {
        $this->promotionInfoDb = $repositoryArray['promotionInfo'];
        $this->pushTemplateDb = $repositoryArray['pushTemplate'];
        $this->notificationDb = $repositoryArray['notification'];
        $this->rideTimeLineDb = $repositoryArray['rideTimeLine'];
        $this->memberDb = $repositoryArray['member'];
    }

    /**
     * 取得所有推播內容範本資料
     * @return array
     */
    public function getAllTemplate()
    {
        $templateInfo = $this->pushTemplateDb->all();
        $resp = [];
        if (!empty($templateInfo)) {
            $resp = $templateInfo;
        }
        return $resp;
    }

    /**
     * 圖片上傳
     *
     * @param \App\Http\File $file
     * @return string
     * @throws \Exception
     */
    public function imgUpload($file)
    {
        if (!$file->isValid()) {
            throw new \Exception('檔案傳送失敗');
        }

        if (($imageType = $file->isImage()) == false) {
            throw new \Exception('僅限圖檔');
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $imageSource = imagecreatefromjpeg($file->getPathname());
                break;
            case IMAGETYPE_PNG:
                $imageSource = imagecreatefrompng($file->getPathname());
                break;
            default:
                throw new \Exception('檔案傳送失敗');
        }

        if (imagesx($imageSource) > 480 || imagesy($imageSource) > 480) {
            throw new \Exception('圖檔長或寬度不合法');
        }

            // 儲存路徑
        $dir = getenv('IMG_DIRECT') . '/notifications';

        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        list($uploadType, $subName) = explode('/', $file->getMimeType());

        $fileName = now()->format('YmdHis');
        $file->move("{$dir}/{$fileName}.{$subName}");

        return "{$fileName}.{$subName}";
    }

    /**
     * 移除圖片
     * @param string $fileName
     * @throws \Exception
     */
    public function imgDelete($fileName)
    {
        // 圖片路徑
        $dir = getenv('IMG_DIRECT') . "/notifications/{$fileName}";

        $file = new File($dir, $fileName);

        if (!$file->isImage() || !$file->isFile()) {
            throw new \Exception('僅限圖檔');
        }

        if (!unlink($dir)) {
            throw new \Exception('檔案刪除失敗');
        }
    }

    /**
     * 隨機字串組合
     *
     * @param int $length
     * @param string $keySpace
     * @return string
     */
    public function randString($length, $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keySpace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[rand(0, $max)];
        }
        return $str;
    }

    /**
     * 送出推播
     *
     * @param \App\Dto\PromotionPushRequestDto $requestDto
     * @throws \App\Model\MongoException
     */
    public function sendPush($requestDto)
    {
        $this->multiWayPush($requestDto);

        $dto = new PromotionPushDto();

        if ($requestDto->getPNO() === null || $requestDto->getPNO() === '') {
            $dto->setInsertData(
                $requestDto->getMemberIds(),
                $requestDto->getTitle(),
                $requestDto->getContent(),
                $requestDto->getImage()
            );
        } else {
            /**
             * 二維條碼產生器
             */
            $generator = new BarcodeGeneratorPNG();
            $couponArray = [];
            $prefix = now()->timestamp;
            foreach ($requestDto->getMemberIds() as $memberId) {
                $coupon = '';
                while (1) {
                    $coupon = "{$prefix}-" . strtoupper($this->randString(5));
                    if (!in_array($coupon, $couponArray, true)) {
                        $couponArray[] = $coupon;
                        break 1;
                    }
                }

                $code39 = base64_encode($generator->getBarcode($coupon, $generator::TYPE_CODE_39, 1));
                $barcodeImage = '<img src="data:image/png;base64,' . $code39 . '">';

                $dto->setInsertData(
                    $memberId,
                    $requestDto->getTitle(),
                    $requestDto->getContent() . $barcodeImage . $coupon,
                    $requestDto->getImage(),
                    $barcodeImage,
                    $coupon
                );
            }
        }

        // 範本儲存
        if ($requestDto->getSaveEdit()) {
            $this->pushTemplateDb->updateContent(
                $requestDto->getOrder(),
                $requestDto->getTitle(),
                $requestDto->getContent()
            );
        }

        // 儲存通知
        $this->notificationDb->multiCreate($dto->getNotificationData());

        // 儲存騎乘時間軸
        $this->rideTimeLineDb->multiCreate($dto->getUUid(), $requestDto->getMemberIds());

        // 儲存推播紀錄&優惠碼資訊
        $this->promotionInfoDb->create(
            $requestDto->getMemberIds(),
            $requestDto->getTitle(),
            $requestDto->getContent(),
            $requestDto->getPNO(),
            $requestDto->getPName(),
            $dto->getCouponInfoData()
        );
    }

    /**
     * 多種途徑推播
     *
     * @param \App\Dto\PromotionPushRequestDto $requestDto
     */
    private function multiWayPush($requestDto)
    {
        $pushContent = strip_tags(html_entity_decode($requestDto->getContent()));
        //產生推播指定ID
        $pushLogId = uniqid();

        // gcm訊息內容
        $gcmMessage = [
            [
                'title'      => $requestDto->getTitle(),
                'content'    => $pushContent,
                'type'       => 0,
                'message_id' => $pushLogId,
            ]
        ];

        // apns訊息內容
        $apnsMessage = [
            'aps'        => [
                'alert' => $pushContent,
                'sound' => "default",
                'badge' => 1
            ],
            'type'       => 0,
            'message_id' => $pushLogId
        ];

        // mail訊息內容
        $mailMessage = [
            'title'   => $requestDto->getTitle(),
            'content' => $requestDto->getContent(),
            'type'    => 'coupon',
            'image'   => $requestDto->getImage()
        ];

        $register = [
            'gcm'  => [
                'regId'   => isset($requestDto->getRegId()['gcm']) ? $requestDto->getRegId()['gcm'] : [],
                'message' => ['data' => json_encode($gcmMessage, JSON_UNESCAPED_UNICODE)]
            ],
            'apns' => [
                'regId'   => isset($requestDto->getRegId()['apns']) ? $requestDto->getRegId()['apns'] : [],
                'message' => $apnsMessage
            ],
            'mail' => [
                'regId'   => (count($requestDto->getMemberIds()) && !empty($requestDto->getPNO())) ? $requestDto->getMemberIds() : [],
                'message' => $mailMessage
            ]
        ];

        foreach ($register as $pushType => $pushInfo) {
            if (!empty($pushInfo['regId'])) {
                PushTypeFactory::getInstance($pushType)->sendPush($pushInfo['regId'], $pushInfo['message']);
            }
        }
    }

    /**
     * 取得捷安特門市清單
     *
     * @param string|null $shopNo
     * @return array
     * @throws \Exception
     */
    protected function getGiantShopInfo($shopNo = null)
    {
        $posConnect = new PosWebConnect();

        // 日期起迄屬於 required
        $shopListInfoData = $posConnect->getApiResponse('GetShopInfoLst', ['SaleDate"' => '']);

        if ($shopListInfoData['retCode'] === PosWebConnect::API_SUCCESS) {
            $shopInfoList = [];
            foreach ($shopListInfoData['retVal'] as $shopInfo) {
                $shopInfoList[$shopInfo['ShopNo']] = $shopInfo;
            }
            return ($shopNo != null && isset($shopInfoList[$shopNo])) ? $shopInfoList[$shopNo] : $shopInfoList;
        } else {
            throw new \Exception("查詢門市失敗原因: {$shopListInfoData['retMsg']}");
        }
    }

    /**
     * 取得推播紀錄
     *
     * @param string $dateBegin
     * @param string $dataEnd
     * @return array
     */
    public function getPushRecord($dateBegin, $dataEnd)
    {
        $promotionInfoData = $this->promotionInfoDb->getDateRangeData($dateBegin, $dataEnd);

        if (empty($promotionInfoData)) {
            return [];
        }

        // 門市列表
        $shopInfo = $this->getGiantShopInfo();

        // 取得有參與促銷的memberId
        $memberIds = [];
        foreach ($promotionInfoData as $val) {
            $memberIds = array_merge($memberIds, $val['memberIds']);
        }
        $memberIds = array_values(array_unique($memberIds));

        $memberInfo = $this->memberDb->getById($memberIds, ['memberid', 'name']);
        // 改成以memberId為key
        $memberIdWithName = [];
        foreach ($memberInfo as $memberData) {
            $memberIdWithName[$memberData['memberid']] = $memberData['name'];
        }

        $resp = map($promotionInfoData, function ($item) use ($memberIdWithName, $shopInfo) {
            $detail = [];
            $isUsedTotal = 0;
            $memberDetailInfo = (empty($item['pNo'])) ? $item['memberIds'] : $item['couponInfo'];

            foreach ($memberDetailInfo as $memberDetail) {
                if (empty($item['pNo'])) {
                    $detail[] = [
                        'isUsed'       => 0,
                        'usedDateTime' => '',
                        'memberName'   => $memberIdWithName[$memberDetail],
                        'shopName'     => ''
                    ];
                } else {
                    if ($memberDetail['isUsed']) {
                        $isUsedTotal++;
                    }
                    $detail[] = [
                        'isUsed'       => $memberDetail['isUsed'],
                        'usedDateTime' => $memberDetail['usedDateTime'],
                        'memberName'   => $memberIdWithName[$memberDetail['memberId']],
                        'shopName'     => (isset($shopInfo[$memberDetail['shopNo']])) ? $shopInfo[$memberDetail['shopNo']]['ShopName'] : ''
                    ];
                }

            }

            return [
                'date'      => $item['created_at'],
                'pName'     => $item['pName'],
                'memberQty' => $item['memberQty'],
                'usedTotal' => $isUsedTotal,
                'detail'    => $detail
            ];
        });

        return $resp;
    }
}
