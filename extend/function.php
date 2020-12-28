<?php
// 获取请求的地址的方法

use think\Loader;

if(!function_exists("http_curl")){

    function http_curl($url,$data = array(),$method = "get",$returnType = "json")

    {
        //1.开启会话
        $ch = curl_init();

        //2.设置参数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if($method != "get"){

            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

        }

        curl_setopt($ch, CURLOPT_URL,$url);

        //执行会话
        $json = curl_exec($ch);

        curl_close($ch);

        if($returnType == "json"){

            return json_decode($json, true);

        }

        return $json;

    }

}
if(!function_exists('get_access_token')){

    function get_access_token()
    {
        $appid = "wxaed0f1d94924c8a6";
        //微信的appid
        $secret = "b9820a8b39a84f7c194abc63df471c65";
        //微信的开发者密钥
        // 读取缓存中的内容
        Loader::import('memcache.MyMemcache', EXTEND_PATH, '.class.php');
        //引入缓存方法文件
//        $obj = new \MyMemcache("123.59.169.142");
        $obj = new \MyMemcache();

        $value = $obj->get($appid);

        if(!$value){

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .$appid . "&secret=" .$secret;

            $result = http_curl($url);

            $value = $result['access_token'];

            $obj->set($appid,$value, 7000);

        }

        return $value;

    }

}
