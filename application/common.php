<?php


// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
session_start();
if(isset($_SESSION['UID']) && intval($_SESSION['UID'])>0){
    $uid = intval($_SESSION['UID']);
}
if (isset($uid) && !$uid) {
    return redirect('/login');
}

function getmenulist($uid='')
{
    $menu_list =  model('MenuList')->getmenulist($uid);
    $module =  model('MenuList')->getmodule();
    return ['menu'=>$menu_list,'module'=>$module];
}
function getmenulistall()
{
    $menu_list =  model('MenuList')->getmenulistall();
    $module =  model('MenuList')->getmodule();
    return ['menu'=>$menu_list,'module'=>$module];
}
function getuser_no(){
    list($usec, $sec) = explode(" ", microtime());
    $msec=round($usec*1000);
    $round = make_password(2);
    $no =  'MBS'.time().$msec.$round;
    return $no;
}
function getuserinfo(){
    if(isset($_SESSION['UID']) && intval($_SESSION['UID'])>0){
        $uid = intval($_SESSION['UID']);
    }
    if($uid>0) {
        return model('User')->getUserinfo($uid);
    }else{
        return redirect('/login');
    }
}
function make_password( $length = 8 )
{
    // 密码字符集，可任意添加你需要的字符
    $chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    // 在 $chars 中随机取 $length 个数组元素键名
    $keys = array_rand($chars, $length);
    $password = '';
    for($i = 0; $i < $length; $i++)
    {
        // 将 $length 个数组元素连接成字符串
        $password .= $chars[$keys[$i]];
    }
    return $password;
}
function chaininfo(){
     return model('Chain')->getOne();
}
function chains(){
    return model('Chain')->getAll();
}