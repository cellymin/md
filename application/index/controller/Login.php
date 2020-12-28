<?php

namespace app\index\controller;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use think\Controller;
use think\View;
use think\Db;

class Login extends Controller
{

    public function index()
    {
        if (isset($_SESSION['UID']) && intval($_SESSION['UID']) > 0) {
            return redirect('/');
        }
        return $this->view->fetch('template/login.html');
    }

    public function dologin($username = '', $password = '')
    {

        $username = trim($_POST['accountName']);
        $password = trim($_POST['password']);
        if (!empty($username) && !empty($password)) {
            $re = Db::table('mbs_user')
                ->where('name', $username)
                ->find();

            if (!empty($re)) {
                if (!empty($password)) {
                    if (md5($password) == $re['password']) {
                        $_SESSION['UID'] = $re['id'];
                        $_SESSION['UNAME'] = $re['name'];
                        $_SESSION['GID'] = $re['grade_id'];
                        $_SESSION['CID'] = $re['chain_id'];
                        $_SESSION['USRC'] = $re['usrsrc'];
                        $data['code'] = 200;
                        $data['msg'] = '登录成功';
                        return json_encode($data);
                    }
                }
            } else {
                $data['code'] = -1;
                $data['msg'] = '账户名不存在';
                return json_encode($data);
            }
        } else {
            $data['code'] = -1;
            $data['msg'] = '账户名或密码不能为空';
            return json_encode($data);
        }
    }

    public function logout()
    {
        if (!empty($_SESSION['UID'])) {
            $_SESSION['UID'] = '';
            $_SESSION['UNAME'] = '';
        }
        return redirect('/');
    }

}