<?php

namespace app\index\controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use think\Controller;
use think\View;

class Index extends Base
{
    public function __initialize() {
        parent::__initialize();
    }
    public function index()
    {
        //判断是否登录
        if (!empty($_SESSION['UID']) && intval($_SESSION['UID']) > 0) {
            return $this->view->fetch();
        } else {
            return redirect('/index.php/index/login');
        }

    }

}
