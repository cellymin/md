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
            $data = getmenulist($_SESSION['UID']);
            $menu = $data['menu'];
            $module = $data['module'];
            $fir_menu = [];
            foreach ($menu as $k => $v) {
                if ($v['father_menu'] == 0) { //顶级分类
                    $fir_menu[$v['module_id']]['childli'][$v['menu_id']]['menu_id'] = $v['menu_id'];
                    $fir_menu[$v['module_id']]['childli'][$v['menu_id']]['menu_name'] = $v['menu_name'];
                    $fir_menu[$v['module_id']]['childli'][$v['menu_id']]['menu_url'] = $v['menu_url'];
                    $fir_menu[$v['module_id']]['childli'][$v['menu_id']]['module_id'] = $v['module_id'];
                } else {
                    $fir_menu[$v['module_id']]['childli'][$v['father_menu']]['childlist'][$v['menu_id']] = $v;
                }
            }
            foreach ($module as $k => $v) {
                    $fir_menu[$v['module_id']]['module_id'] = $v['module_id'];
                    $fir_menu[$v['module_id']]['module_name'] = $v['module_name'];
                    $fir_menu[$v['module_id']]['module_url'] = $v['module_url'];
            }
            $this->assign('list',$fir_menu);
//            echo '<pre/>';
//            var_dump($fir_menu);
//            die();
            $this->view->menu = $fir_menu;
            return $this->view->fetch();
        } else {
            return redirect('/index.php/index/login');
        }

    }

    public function dologin(Request $request)
    {
        $username = $request->username;
        $pass = md5($request->password);
        if (!empty($pass) && !empty($username)) {
            $re = DB::table('vich_user')
                ->where('user_name', $username)
                ->distinct()
                ->take(1)
                ->get();
            if (count($re) > 0) {
                foreach ($re as $k => $v) {
                    $uid = $v->id;
                    $upass = $v->password;
                    $right = $v->right;
                }
                if ($pass == $upass) {
                    // echo "密码正确";
                    session_start();
                    $_SESSION['UID'] = $uid;
                    $_SESSION['UNAME'] = $username;
                    $_SESSION['right'] = $right;
                    return redirect('/');
                } else {
                    return redirect('/login');
                }
            } else {
                echo "密码错误";
                return redirect('/login');
            }
        } else {
            return redirect('/login');
        }

    }
}
