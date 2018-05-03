<?php
/**
 * Created by PhpStorm.
 * User: fish
 * Date: 2018/3/7
 * Time: 11:04
 */

namespace app\index\controller;


use app\auth\controller\AdminBaseController;

class AdminIndexController extends AdminBaseController
{
    public function index()
    {
        return $this->fetch();
    }
}