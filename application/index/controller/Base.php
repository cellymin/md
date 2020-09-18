<?php

namespace app\index\controller;

use think\Controller;

class Base extends Controller
{
    public function __construct()
    {
        /**
         * 不验证用户登陆的页面
         */
        parent::__construct();

        //不验证用户登陆的页面index
        $exception_arth_list = [
            'index/login/index'
        ];
        $redirect_url = 'index.php/index/login';
        $this->checkUserLogin($redirect_url, $exception_arth_list);
    }


    /**
     *  检查用户是否登陆,登陆时跳转到登陆页面
     *  $redirect_url 要跳的url (不区别大小写) [str] 例: 'member/Users/login'
     *  $exception_arth_list [array] 不验证用户登陆的页面地址(不区别大小写) 例:     ['member/user/login','member/Users/reg']
     *  $msg 跳转前的提示信息
     */
    protected function checkUserLogin($redirect_url, $exception_arth_list = [], $msg = '')
    {
        if (!is_string($redirect_url) || !is_array($exception_arth_list) || !is_string($msg)) {
            die('传入的参数错误.');
        }
        $module=request()->module();//获取当前访问的模块
        $controller=request()->controller();//获取当前访问的控制器
        $action= request()->action();//获取当前访问的方法
        $current_auth_str=$module.'/'.$controller.'/'.$action; //转成字符串
        if(!empty($exception_arth_list) && is_array($exception_arth_list)){
            foreach($exception_arth_list as &$v){
                if(!is_string($v)){
                    die('不验证页面数组里的值只能为字符串类型.');
                }
                $v=strtolower($v);
            }
        }
//        var_dump($current_auth_str);
//        var_dump($exception_arth_list);
//        die();
        //当前访问的页面$current_auth_str转为全小写后,如果不在$exception_arth_list客户中就验证用户是否登陆
        if(!empty($exception_arth_list) && is_array($exception_arth_list)){
            if(!in_array(strtolower($current_auth_str),$exception_arth_list)) {
                if (isset($_SESSION['UID'])) {
                }else{
                    $this->redirect('/index.php/index/login');
                }
            }

        }



    }
}