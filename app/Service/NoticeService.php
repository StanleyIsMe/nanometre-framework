<?php

namespace App\Service;

use App\Model\MongoAbstract;

/**
 * 通知訊息service
 *
 * @package App\Service
 * @todo
 */
class NoticeService
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
     * constructor
     *
     * @param \App\Model\MongoAbstract $historyLoginDb
     * @param \App\Model\MongoAbstract $memberDb
     */
    public function __construct()
    {

    }

    /**
     * 回傳推播對象
     *
     * @return array
     */
    public function getNoticeMsg()
    {

    }

    public function updateClickTime()
    {

    }
}