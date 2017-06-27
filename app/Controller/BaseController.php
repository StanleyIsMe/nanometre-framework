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
}