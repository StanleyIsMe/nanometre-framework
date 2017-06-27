<?php

namespace App\Controller;

use App\Model\Mongodb\MongoFactory;
use App\Model\Database\Member;
class IndexController extends BaseController
{
    /**
     * 初始行為
     */
    public function init()
    {

    }

    public function index()
    {
        echo 'Hello Nanometre';
    }
}