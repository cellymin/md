<?php

namespace app\index\controller;

use think\cache\driver\Memcache;
use think\Controller;
use think\Db;
use think\Loader;

use think\Request;

header("Content-type:text/html;charset=utf-8");
define("TOKEN", "ceshi");

class Wxmbs extends Controller
{
    private $accessToken;

    public function __initialize()
    {
        parent::__initialize();

    }

//    public function __construct(Request $request)
//    {
//        parent::__construct();
//        Loader::import('function', EXTEND_PATH, '.php');
//        $this->accessToken = get_access_token();
//
//        //获得方法
//        $action_name =$request->action();
//        //获得控制器
//        $controller_name = $request->controller();
//
//        if(!cookie('user')){
//            if($action_name != 'getcode'){
//                $this->auth($action_name, $controller_name);
//            }
//        }
//    }
    // 引导跳转的方式
    public function auth($action_name, $controller_name)
    {
        $config = config('weixin');
        $appid = $config['appid'];
        $bak = urlencode("http://jjm.mbsqb.com/index.php/index/" . $controller_name . "/" . $action_name);
        $redirecr_uri = urlencode('http://jjm.mbsqb.com/index.php/index/wxmbs/getcode?bak=' . $bak);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid
            . "&redirect_uri=" . $redirecr_uri
            . "&response_type=code&scope=snsapi_userinfo&state="
            . time() . "#wechat_redirect";
        header('Location:' . $url);
    }

    public function getcode(Request $request)
    {
        $config = config('weixin');
        $this->appid = $config['appid'];
        $this->appsecret = $config['secret'];
        $code = $_GET['code'];
        if (!$code) {
            echo '微信服务器故障';
            exit;
        }
        // 第二步：通过code换取网页授权中的access_token
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid="
            . $this->appid . "&secret=" . $this->appsecret . "&code=" . $code . "&grant_type=authorization_code";
        $result = file_get_contents($url);
        $result = json_decode($result, true);

        if (!$result) {
            echo '微信服务器故障';
            exit;
        }

        // 第三步：获取用户的基本信息，此操作仅限scope为snspai_userinfo
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $result['access_token']
            . "&openid=" . $result['openid'] . "&lang=zh_CN";
        $result = file_get_contents($url);
        $result = json_decode($result, true);
        $userInfo = db('customers')->where("openid", "=", $result['openid'])->find();
        if (!$userInfo) {
            echo '操作数据可以';
            $data = [
                'nickname' => $result['nickname'],
                'openid' => $result['openid'],
                'headimgurl' => $result['headimgurl']
            ];
            db('customers')->insert($data);
            $userInfo = $result;
        }
        // 第四步骤：跳转回跳转地址
        $bak = $_GET['bak'];
        cookie('user', $userInfo);
        header('Location:' . $bak);
    }

    public function index()
    {
        Loader::import('function', EXTEND_PATH, '.php');
        $this->accessToken = get_access_token();

        $this->valid();
        $this->creatMenu();

    }


    public function creatMenu()
    {
        //组装请求的url地址
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this->accessToken;

        $data = array(
            // button下的每一个元素
            "button" =>
                array(

                    //第一个一级菜单
                    array(
                        'type' => 'click',
                        "name" => "预约",
                        "key" => "info"),

                    array(

                        "name" => "苗博士",
                        "sub_button" =>
                            array(

                                array(
                                    "name" => '技师列表',
                                    "type" => "view",
                                    'url' => "http://jjm.mbsqb.com/index.php/index/Wxmbs/serverlist"),

//                                array(
//                                    'name' => 'c/c++',
//                                    'type' => 'pic_sysphoto',
//                                    'key' => 'sysptoto'),

//                                array(
//                                    'name' => 'java',
//                                    'type' => 'pic_weixin',
//                                    'key' => 'pic_weixin')
                            )

                    ),

                    array(
                        'type' => 'click',
                        'name' => 'xxxx',
                        'key' => 'content')
                )
        );

        // 将数据转换为json格式
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);

        $result = http_curl($url, $data, 'post');

        dump($result);

    }


    public function valid()
    {
        //获取随机字符串

        $echoStr = input("echostr");
        if ($echoStr) {
            // 验证接口的有效性，由于接口有效性的验证必定会传递echostr 参数
            if ($this->checkSignature()) {
                echo $echoStr;
                exit;
            }
        } else {
            $this->responseMsg();
        }

    }

    private function checkSignature()
    {
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpArr = implode($tmpArr);
        $tmpstr = sha1($tmpArr);

        if ($tmpstr == $signature) {
            return true;
        } else {
            return false;
        }
    }


    public function responseMsg()
    {

        //get post data, May be due to the different environments

        $postStr = file_get_contents('php://input');

        //extract post data
        //extract post data
        if (!empty($postStr)) {

            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
             the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;

            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();

            $textTpl = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>

                              <FromUserName><![CDATA[%s]]></FromUserName>

                              <CreateTime>%s</CreateTime>

                              <MsgType><![CDATA[%s]]></MsgType>

                              <Content><![CDATA[%s]]></Content>

                              <FuncFlag>0</FuncFlag>

                             </xml>";

            if (!empty($keyword)) {

                $msgType = "text";

                $contentStr = "Welcome to wechat world!";

                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);

                echo $resultStr;

            } else {

                echo "Input something...";

            }

        } else {

            echo "";
            exit;

        }

    }

    public function test()
    {
        $appid = "wxaed0f1d94924c8a6";
        Loader::import('memcache.MyMemcache', EXTEND_PATH, '.class.php');
        $obj = new \MyMemcache("127.0.0.1");
        $obj->set($appid, 'dewdew', 7000);
        $value = $obj->get($appid);
        $value = $obj->del($appid);
        echo $value;
    }

    //预约
    public function yuyue()
    {
//        $re = Db::table('ll')
//            ->select();
        return $this->view->fetch('reserve');
    }

    public function serverlist()
    {
        $data = array();
        if (isset($_GET['cid']) && intval($_GET['cid']) > 0) {
            $cid = intval($_GET['cid']);
        } else {
            $map = array();
            $map['status'] = 1;
            $chain_info = Db::table('mbs_chain')
                ->field('id')
                ->where('status', 1)
                ->where($map)
                ->find();
            $cidarr = $chain_info;
            if (!empty($cidarr)) {
                $cid = $cidarr['id'];
            } else {
                $data['code'] = -1;
                $data['msg'] = '请及时添加门店';
                return $data;
            }
        }

        $chainuser = Db::table('mbs_chain')
            ->alias('a')
            ->join('mbs_user b', 'a.id = b.chain_id', 'LEFT')
            ->field('a.name cname,a.phone cphone,a.tel ctel,a.address cadress,a.province cpro,a.city ccity,a.dean_id,b.*')
            ->where('a.id', $cid)
            ->select();
        $this->assign('chainuser', $chainuser);
//        echo '<pre/>';
//        var_dump($chainuser);
//        die();

        foreach ($chainuser as $k => $v) {
            $list['name'] = $v['cname'];
            $list['cphone'] = $v['cphone'];
            $list['ctel'] = $v['ctel'];
            $list['cadd'] = $v['address'];
            $list['cpro'] = $v['cpro'];
            $list['ccity'] = $v['ccity'];
            $list['dean_id'] = $v['dean_id'];
            $list['cid'] = $cid;
            break;
        }
        $this->assign('chaininfo', $list);

        $chains = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $this->assign('chains', $chains);

        return $this->view->fetch();
    }

    public function resertime()
    {
        if (isset($_GET['sign'])) {
            //技师id
            $jishiid = intval($_GET['sign']);
        }
        $date = [];
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        $fir = date('m/d', time());
        $firw = date('w', strtotime($fir));

        for ($i = 0; $i < 7; $i++) {
            $dd = date('m/d', strtotime('+' . $i . ' day', time()));
            $dw = date('w', strtotime('+' . $i . ' day', time()));
            $date[$i]['dd'] = $dd;
            $date[$i]['dw'] = $dw;
        }
        $this->assign('jsid', $jishiid);
        $this->assign('weekarray', $weekarray);
        $this->assign('date', $date);
        return $this->view->fetch();
    }

    public function bindphone()
    {

        if (isset($_POST['phone'])) {
            $phone = $_POST['phone'];
            $cusinfo = Db::table('mbs_customers')
                ->where('phone', $phone)
                ->find();
            $user = cookie('user');
            if (!empty($cusid)) {
                //手机号已存在;
                cookie('cusinfo', $cusinfo);
                //启动事务
                Db::startTrans();
                try {
                    //删除微信

                    $wxid = Db::table('mbs_customers')
                        ->where('openid', $user['openid'])
                        ->delete();
                    $data = [
                        'nickname' => $user['nickname'],
                        'openid' => $user['openid'],
                        'headimgurl' => $user['headimgurl']
                    ];
                    Db::table('mbs_customers')
                        ->where('phone', $phone)
                        ->update($data);

                    Db::commit();//提交事务
                    unset($data);
                    $data['code'] = 200;
                    $data['msg'] = '绑定成功';
                    return json_encode($data);

                } catch (\Exception $e) {
                    Db::rollback();//回滚事务

                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }

            } else {
                //新用户首次添加
            }
        } else {


        }

        return $this->view->fetch();
    }

    public function cusinfo()
    {

        $user = cookie('user');
        $openid = $user['openid'];
        $openid = 'oFeYX5mchFp76PP6c-yfqsweAIkg';

        $userinfo = Db::table('mbs_customers')
            ->where('openid', $openid)
            ->find();

        //服务记录
        $recorddetail = Db::table('mbs_all_assessment')
            ->field('r.*')
            ->alias('a')
            ->join('mbs_assessment_record r', 'a.id=r.sserver_id', 'LEFT')
            ->where('a.customer_id', $userinfo['id'])
            ->select();
        $this->assign('recordlist', $recorddetail);
        $this->assign('userinfo', $userinfo);
//        var_dump($recorddetail);
//        die();

        return $this->view->fetch();
    }

    /*
     * 保存预约信息
     */
    public function save_reserve()
    {
        $reserinfo = [];
        $data = [];
        $data['code'] = 0;
        $jsid = intval($_POST['jsid']);
        $date = str_replace('/', '-', $_POST['day']);
        $time = $_POST['time'];
        $now = time();
        $dd = strtotime(date('Y', time()) . '-' . $date . ' ' . $time);
        $nday = date('Y', time()) . '-' . $date;
        if ($dd < $now) {
            //跨年+1
            $dd = strtotime(date('Y', time()) . '-' . $date . ' ' . $time . '+1 year');
            $nday = date('Y-m-d', $dd);
        }
        //
        $userch = Db::table('mbs_user')
            ->field('chain_id')
            ->where('id', $jsid)
            ->find();

        $cusinfo = cookie('cusinfo');
        $reserinfo['customer_id'] = $cusinfo['id'];
        $reserinfo['server_id'] = $jsid;
        $reserinfo['day'] = $nday;
        $reserinfo['time'] = $time;
        $reserinfo['chain_id'] = intval($userch['chain_id']);
        $reserinfo['create_time'] = date('Y-m-d h:i:s', time());

        $res = Db::table('mbs_reser')
            ->insert($reserinfo);
        if ($res) {
            $data['msg'] = '预约成功！';
            return json_encode($data);
        } else {
            $data['msg'] = '预约失败';
            return json_encode($data);
        }
    }
}
