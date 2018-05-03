<?php
namespace app\index\controller;

use app\auth\controller\BaseController;

class IndexController extends BaseController
{
    public function index()
    {
        return $this->fetch();
    }
}
