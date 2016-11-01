<?php

namespace App\Service;

use App\Dto\MemberStatisticsResponse;

/**
 * 會員相關的service
 *
 * @package App\Service
 */
class MemberService
{
    /**
     * @var \App\Model\HistoryLoginMongo
     */
    private $historyLoginDb;

    /**
     * @var \App\Model\MemberMongo
     */
    private $memberDb;

    /**
     * @var \App\Model\PosCardMongo
     */
    private $posCardDb;

    /**
     * @var \App\Model\MemberBikeArsMongo
     */
    private $memberBikeArsDb;

    /**
     * constructor
     *
     * @param array $repositoryArray
     */
    public function __construct(array $repositoryArray)
    {
        $this->memberDb = $repositoryArray['member'];
        $this->historyLoginDb = $repositoryArray['historyLogin'];
        $this->posCardDb = $repositoryArray['posCard'];
        $this->memberBikeArsDb = $repositoryArray['memberBikeArs'];
    }

    /**
     * 回傳推播對象
     *
     * @param string $keyWord
     * @return array
     */
    public function getPushTarget($keyWord = '')
    {
        $historyLoginData = $this->historyLoginDb->getDeviceToken();

        if (count($historyLoginData)) {
            $memberDeviceInfo = [];
            foreach ($historyLoginData as $val) {
                $memberDeviceInfo[$val['memberid']] = [
                    'regid' => $val['c2dm']['id'],
                    'type'  => $val['c2dm']['type']
                ];
            }
            $memberIdArray = array_keys($memberDeviceInfo);
            $memberData = $this->memberDb->useFuzzySearch(trim($keyWord), $memberIdArray);

            $result = map($memberData, function ($val) use ($memberDeviceInfo) {
                return [
                    'memberid'  => $val['memberid'],
                    'name'      => $val['name'],
                    'cellphone' => $val['cellphone'],
                    'email'     => $val['email'],
                    'regid'     => $memberDeviceInfo[$val['memberid']]['regid'],
                    'type'      => $memberDeviceInfo[$val['memberid']]['type'],
                ];
            });
            return $result;
        }

        return [];
    }

    /**
     * 取得該月各種統計數據
     *
     * @param string $year
     * @param string $month
     * @return \App\Dto\MemberStatisticsResponse
     * @throws \Exception
     */
    public function getMultiStatistics($year, $month)
    {
        $dtoResponse = new MemberStatisticsResponse();

        $countyInfo = [];
        $countryData = self::getCounty()->taiwan;
        foreach ($countryData as $countyObject) {
            $countyInfo[$countyObject->id] = $countyObject->county;
        }

        // 該月加入的會員
        $memberData = $this->memberDb->fuzzySearchByYearMonth($year, $month);
        $memberInfo = [];
        foreach ($memberData as $member) {
            $member['gender'] = ($member['gender'] == 3046) ? '男' : '女';
            $memberInfo[$member['memberid']] = $member;
        }
        $memberIds = array_keys($memberInfo);

        if (!count($memberIds)) {
            return $dtoResponse;
        }

        // 該月購車會員
        $posCardData = $this->posCardDb->fuzzySearchByYearMonth($year, $month, $memberIds);
        $posCardInfo = [];
        foreach ($posCardData as $key => $val) {
            if (!isset($posCardInfo[$val['MemberId']])) {
                $posCardInfo[$val['MemberId']][] = $val['No'];
            } else {
                array_push($posCardInfo[$val['MemberId']], $val['No']);
            }

        }

        // 會員安裝ARS資訊
        $memberBikeArsData = $this->memberBikeArsDb->all();
        $memberBikeArsInfo = [];
        foreach ($memberBikeArsData as $key => $val) {
            foreach ($val['owners'] as $okey => $oval) {
                if (!isset($memberBikeArsInfo[$oval['memberid']])) {
                    $memberBikeArsInfo[$oval['memberid']] = [];
                }
                array_push($memberBikeArsInfo[$oval['memberid']], $val['id']);
            }
        }

        // 該月有登錄RideLife
        $historyLoginInfo = $this->historyLoginDb->getHasDownloadApp($memberIds);

        // 該月車種前七名
        $posCardData = $this->posCardDb->getTop7ByFuzzySearch($year, $month, $memberIds);
        $top7Bike = [];
        foreach ($posCardData['result'] as $key => $val) {
            foreach ($val['memberid'] as $memberKey => $memberId) {
                if (!empty($memberId)) {
                    if (!isset($top7Bike[$val['_id']['id']])) {
                        $top7Bike[$val['_id']['id']] = [];
                    }
                    array_push($top7Bike[$val['_id']['id']], $memberId);
                }
            }

        }

        foreach ($top7Bike as $productType => $memberIdArray) {
            $dtoResponse->top7['ars'][$productType]['yes'] = 0;
            $dtoResponse->top7['reg'][$productType]['yes'] = 0;
            $dtoResponse->top7['ars'][$productType]['no'] = 0;
            $dtoResponse->top7['reg'][$productType]['no'] = 0;
            foreach ($memberIdArray as $memberId) {
                // 前七名車種有裝Ars否
                if (isset($memberBikeArsInfo[$memberId])) {
                    $dtoResponse->top7['ars'][$productType]['yes']++;
                } else {
                    $dtoResponse->top7['ars'][$productType]['no']++;
                }

                // 前七名車種有下載RideLife否
                if (in_array($memberId, $historyLoginInfo, true)) {
                    $dtoResponse->top7['reg'][$productType]['yes']++;
                } else {
                    $dtoResponse->top7['reg'][$productType]['no']++;
                }
            }
        }

        foreach ($memberInfo as $memberId => $member) {

            // 各縣市男女會員數量
            if (isset($countyInfo[$member['county']])) {
                $dtoResponse->county[$countyInfo[$member['county']]][$member['gender']]++;
                array_push($dtoResponse->county[$countyInfo[$member['county']]]['memberid'], $memberId);
            }

            // 男女會員數量
            $dtoResponse->sex[$member['gender']]++;

            // 各縣市購車數量,
            if (isset($posCardInfo[$memberId])) {
                if (isset($countyInfo[$member['county']])) {
                    $dtoResponse->county[$countyInfo[$member['county']]]['bike']['yes']++;
                }
                $dtoResponse->bike['yes']++;
            } else {
                if (isset($countyInfo[$member['county']])) {
                    $dtoResponse->county[$countyInfo[$member['county']]]['bike']['no']++;
                }
                $dtoResponse->bike['no']++;
            }

            // 有無下載RideLife並登錄
            if (in_array($memberId, $historyLoginInfo, true)) {
                $dtoResponse->reg['yes']++;
            } else {
                $dtoResponse->reg['no']++;
            }

            // 有無安裝ARS感測裝置
            if (isset($memberBikeArsInfo[$memberId])) {
                $dtoResponse->ars['yes']++;
            } else {
                $dtoResponse->ars['no']++;
            }
        }
        return $dtoResponse;
    }

    /**
     * 取得縣市資料
     *
     * @return object
     * @throws \Exception
     */
    public static function getCounty()
    {
        $json = file_get_contents(APPLICATION_PATH . '/app/data/country.json');
        if ($json) {
            return json_decode($json);
        } else {
            throw new \Exception('country.json讀取失敗');
        }
    }
}
