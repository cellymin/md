<?php
namespace app\index\controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use think\Controller;
use think\View;

class Addinfo extends Base {

    public function __initialize() {
        parent::__initialize();
    }

    public function index(){
        $no = getuser_no();

        $this->assign('create_time',time());
        $this->assign('user_no',$no);
        return $this->view->fetch('template/addinfo.html');
    }
}