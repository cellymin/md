<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Customers extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
        $config = config('grade');
        if ($_SESSION['GID'] == $config['chief_dean']) {
            //总院长
            $this->assign('sign', 2);
        } else if ($_SESSION['GID'] == $config['admin']) {
            //超级管理员
            $this->assign('sign', 1);
        }
        if ($_SESSION['GID'] == $config['chief_dean']) {
            //总院长
            $this->assign('sign', 2);
        }
        $map = array();
        $ormap = array();

//        if (!empty($_GET['name'])) {
//            $map['o.chain_name'] = ['like', '%' . $_GET['chain_name'] . '%'];
//        }
        if (!empty($_GET['keywords'])) {
            $ormap['a.name'] = ['like', '%' . $_GET['keywords'] . '%'];
        }
        if (!empty($_GET['keywords'])) {
            $ormap['a.phone'] = ['like', '%' . $_GET['keywords'] . '%'];
        }
        if (!empty($_GET['keywords'])) {
            $ormap['o.server_no'] = ['like', '%' . $_GET['keywords'] . '%'];
        }
        if (!empty($_GET['create_time'])) {
            $create_time = strtotime($_GET['create_time']);
            $star_t = date('Y-m-d 00:00:00', $create_time);
            $end_t = date('Y-m-d 23:59:59', $create_time);
            $map['o.create_time'] = ['between time', [$star_t, $end_t]];
        }
        if (!empty($_GET['keywords'])) {
            $ormap['o.server_name'] = ['like', '%' . $_GET['keywords'] . '%'];
        }
        $map['a.status'] = 1;
        $re = Db::table('mbs_customers')
            ->alias('a')
            ->join('mbs_all_assessment o', 'a.id=o.customer_id', 'LEFT')
            ->field('a.*,o.id sid,o.status ostatus,o.server_id ser_id')

            ->whereOr($ormap)
            ->where($map)
            ->select();
//        $res = Db::table('mbs_customers')->getLastSql();
//        echo '<pre/>';
//        var_dump($_SESSION['UID']);
//        var_dump($re['ser_id']);
//        var_dump($res);die();
        $this->assign('uid', $_SESSION['UID']);
        $this->assign('userlist', $re);

        return $this->view->fetch();
    }

    public function edituser()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $userinfo = Db::table('mbs_customers')
                ->where('id', $id)
                ->find();
            $this->assign('userinfo', $userinfo);
        }
        return $this->view->fetch();
    }

    public function if_exist($phone = '')
    {
        $phone = $_POST['phone'];
        $data = array();
        if (!empty($phone)) {
            $rule = '/^0?(13|14|15|17|18)[0-9]{9}$/';
            $result = preg_match($rule, $phone);
            if (!$result) {
                $data['msg'] = "手机号码有误";
                $data['status'] = -1;
                return json_encode($data);
            }
            $if_exist = Db::table('mbs_customers')
                ->where('phone', $phone)
                ->select();
            if (!empty($if_exist)) {
                $data['msg'] = "手机号重复，请重新输入！";
                $data['code'] = 1;
                return json_encode($data);
            } else {
                $data['msg'] = "输入成功！";
                $data['code'] = 200;
                return json_encode($data);
            }
        }
    }

    public function addserver()
    {
        $id = intval($_GET['id']);
        $cid = intval($_SESSION['CID']);
        $gid = intval($_SESSION['GID']);
        $config = config('grade');
        $map = array();
        if ($gid == $config['chief_dean'] || $gid == 1) {//超级管理员或者总院长
            $map['a.status'] = 1;
            $map['p.group_name'] = '技师';
        } else {
            $map['a.status'] = 1;
            $map['a.chain_id'] = intval($_SESSION['CID']);
            $map['p.group_name'] = '技师';
        }
        $jsinfo = Db::table('mbs_user')
            ->alias('a')
            ->join('mbs_user_group p', 'a.user_group = p.group_id', 'LEFT')
            ->field('a.id,a.name,a.quanpin,a.jianxie')
            ->where($map)
            ->select();
        $this->assign('jsinfo', $jsinfo);
        $cusinfo = Db::table('mbs_customers')
            ->where('id', $id)
            ->find();
//        echo '<pre/>';
//        var_dump($cusinfo);
//        die();
        $this->assign('cusinfo', $cusinfo);
        $no = getuser_no();
        $this->assign('create_time', time());
        $this->assign('user_no', $no);
        return $this->view->fetch();
    }

    public function save_user()
    {
        $user_info_str = array_filter(explode(',', $_POST['user_info']));
        $new = array();
        $data = array();
        foreach ($user_info_str as $k => $v) {
            $new[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        $data['name'] = $new['username'];
        $data['birth'] = $new['userbirth'];
        $data['sex'] = $new['sex'];
        $data['phone'] = $new['userphone'];
        $data['email'] = $new['useremail'];
        $data['address'] = $new['useraddress'];
        $data['source_from'] = $new['sourcefrom'];
        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            $data['update_time'] = date('Y-m-d h:i:s', time());
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_customers')
                    ->where('id', intval($_GET['id']))
                    ->update($data);
                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        } else {
            $data['create_time'] = date('Y-m-d h:i:s', time());
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_customers')
                    ->insert($data);
                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);

            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        }
    }

    public function delete()
    {
        $id = intval($_POST['uid']);
        if ($id > 0) {
            $re = Db::table('mbs_customers')
                ->where('id', $id)
                ->update(['status' => 0]);
            if ($re) {
                $data['code'] = 200;
                $data['msg'] = "删除成功！";
                return json_encode($data);
            } else {
                $data['code'] = 1;
                $data['msg'] = "删除失败！";
                return json_encode($data);
            }
        } else {
            $data['code'] = -1;
            $data['msg'] = '没有可操作的对象';
            return json_encode($data);
        }

    }

    public function save_server()
    {
        $server_id = 0;
        if(isset($_POST['serid'])){
            $server_id = intval($_POST['serid']);
        }

        $customerspost = $_POST['customers'];
        if (isset($_POST['sserinfo'])) {
            $sserinfo = $_POST['sserinfo'];
        }
        //附加用户信息
        $addinfoexd = $_POST['addinfo'];
        $exdcusarr = array_filter(explode(',', $addinfoexd));
        foreach ($exdcusarr as $k => $v) {
            $exdcusinfo[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        if(isset($_POST['cussrc']) && $_POST['cussrc']!=''){
            //图片
            $exdcusinfo['srcsource'] = $_POST['cussrc'];
        }

        $cusarr = array_filter(explode(',', $customerspost));
        foreach ($cusarr as $k => $v) {
            $addinfo[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }

        $addinfo['skin_cate'] = $_POST['skin_cate'];
        $addinfo['health'] = $_POST['health'];
        $addinfo['freckle_experience'] = $_POST['freckle_experience'];
        $addinfo['stains_cate'] = $_POST['stains_cate'];


        if ($server_id > 0) {
            $addinfo['update_time'] = date('Y-m-d', time());
            //        启动事务
            Db::startTrans();
            try {

                Db::table('mbs_customers')
                    ->where("id", $addinfo['customer_id'])
                    ->update($exdcusinfo);

                Db::table('mbs_all_assessment')
                    ->where("id", $server_id)
                    ->update($addinfo);

                if (isset($sserinfo)) {
                    foreach ($sserinfo as $k => $v) {
                        $list[$k] = $v;
                        //服务单
                        $list[$k]['sserver_id'] = $server_id;
                        //添加人
                        $list[$k]['adduser_id'] = $_SESSION['UID'];
                        //技师id
                        $list[$k]['server_id'] = $addinfo['server_id'];
                        //技师名称
                        $list[$k]['server_name'] = $addinfo['server_name'];
                    }
                    Db::table('mbs_assessment_record')
                        ->insertAll($list);

                }

                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);

            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        } else {

            $if_exist = Db::table('mbs_all_assessment')
                ->where("id", $addinfo['customer_id'])
                ->find();
            if ($if_exist) {
                $data['code'] = 0;
                $data['msg'] = '已有服务单';
                return json_encode($data);
            }
            $addinfo['ask_time'] = date('Y-m-d', time());
            $addinfo['create_time'] = date('Y-m-d', time());
            //接待人id
            $addinfo['reception'] = $_SESSION['UID'];
            //添加人id
            $addinfo['adduser_id'] = $_SESSION['UID'];
            //        启动事务
            Db::startTrans();
            try {

                $if_exist = Db::table('mbs_all_assessment')
                    ->where("id", $addinfo['customer_id'])
                    ->find();
                if ($if_exist) {
                    $data['code'] = 0;
                    $data['msg'] = '已有服务单';
                    return json_encode($data);
                }

                Db::table('mbs_customers')
                    ->where("id", $addinfo['customer_id'])
                    ->update($exdcusinfo);

                Db::table('mbs_all_assessment')
                    ->insert($addinfo);
                $id = Db::name('mbs_all_assessment')->getLastInsID();
                if (isset($sserinfo)) {
                    foreach ($sserinfo as $k => $v) {
                        $list[$k] = $v;
                        //服务单
                        $list[$k]['sserver_id'] = $server_id;
                        //添加人
                        $list[$k]['adduser_id'] = $_SESSION['UID'];
                        //技师id
                        $list[$k]['server_id'] = $addinfo['server_id'];
                        //技师名称
                        $list[$k]['server_name'] = $addinfo['server_name'];
                    }
                    Db::table('mbs_assessment_record')
                        ->insertAll($list);

                }
                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);

            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        }

    }

    public function editserver()
    {
        $config = config('grade');
        if ($_SESSION['GID'] == $config['chief_dean']) {
            //总院长
            $this->assign('sign', 2);
        } else if ($_SESSION['GID'] == $config['admin']) {
            //超级管理员
            $this->assign('sign', 1);
        }
        $serid = intval($_GET['id']);
        $cid = intval($_SESSION['CID']);
        $gid = intval($_SESSION['GID']);

        $ser_id = intval($_GET['id']);
        $serinfo = Db::table('mbs_all_assessment')
            ->field('a.*,c.*,c.id cusid')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->where('a.id', $ser_id)
            ->find();
//echo'<pre/>';var_dump($serinfo);die();
        $recetion = Db::table('mbs_user')
            ->field('name')
            ->where('id', $serinfo['reception'])
            ->find();
        $this->assign('reception', $recetion['name']);
        $this->assign('serid', $serid);
        if ($_SESSION['UID'] == $serinfo['server_id']) {
            //总院长
            $this->assign('sign', 3);
        }
//        echo $serid;
//        echo '<pre/>';
//        var_dump($serinfo);
//        die();
        $this->assign('serinfo', $serinfo);
        $skin_cate = array_filter(explode(',', $serinfo['skin_cate']));

        $health = array_filter(explode(',', $serinfo['health']));
        $freckle_experience = array_filter(explode(',', $serinfo['freckle_experience']));
        $stains_cate = array_filter(explode(',', $serinfo['stains_cate']));
        $this->assign(['skin_cate' => $skin_cate, 'health' => $health, 'freckle_experience' => $freckle_experience, 'stains_cate' => $stains_cate]);

        $serdetail = Db::table('mbs_assessment_record')
            ->where('sserver_id', $ser_id)
            ->where('status', 1)
            ->select();
        $this->assign('serdetail', $serdetail);
        $this->assign('create_time', time());
        $map = array();
        if ($gid == $config['chief_dean'] || $gid == 1) {//超级管理员或者总院长
            $map['a.status'] = 1;
            $map['p.group_name'] = '技师';
        } else {
            $map['a.status'] = 1;
            $map['a.chain_id'] = intval($_SESSION['CID']);
            $map['p.group_name'] = '技师';
        }
        $jsinfo = Db::table('mbs_user')
            ->alias('a')
            ->join('mbs_user_group p', 'a.user_group = p.group_id', 'LEFT')
            ->field('a.id,a.name,a.quanpin,a.jianxie')
            ->where($map)
            ->select();
        $this->assign('jsinfo', $jsinfo);
        return $this->view->fetch();

    }

    public function viewserver()
    {
        $ser_id = intval($_GET['id']);
        $serinfo = Db::table('mbs_all_assessment')
            ->field('a.*,c.*')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->where('a.id', $ser_id)
            ->find();
        $this->assign('serinfo', $serinfo);
        $recetion = Db::table('mbs_user')
            ->field('name')
            ->where('id', $serinfo['reception'])
            ->find();
        $this->assign('reception', $recetion['name']);
        $skin_cate = array_filter(explode(',', $serinfo['skin_cate']));

        $health = array_filter(explode(',', $serinfo['health']));
        $freckle_experience = array_filter(explode(',', $serinfo['freckle_experience']));
        $stains_cate = array_filter(explode(',', $serinfo['stains_cate']));
        $this->assign(['skin_cate' => $skin_cate, 'health' => $health, 'freckle_experience' => $freckle_experience, 'stains_cate' => $stains_cate]);

        $serdetail = Db::table('mbs_assessment_record')
            ->where('sserver_id', $ser_id)
            ->where('status', 1)
            ->select();
        $this->assign('serdetail', $serdetail);
        return $this->view->fetch();
    }

    public function delserver()
    {

        $sid = intval($_POST['sid']);
        try {
            Db::table('mbs_all_assessment')
                ->where('id', $sid)
                ->update(['status' => -1]);
            Db::table('mbs_assessment_record')
                ->where('sserver_id', $sid)
                ->update(['status' => -1]);

            Db::commit();//提交事务
            unset($data);
            $data['code'] = 200;
            $data['msg'] = '删除成功';
            return json_encode($data);
        } catch (\Exception $e) {
            Db::rollback();//回滚事务
            unset($data);
            $data['code'] = 0;
            $data['msg'] = '删除失败';
            return json_encode($data);
        }
    }

    public function delserverrecord()
    {
        $rid = intval($_POST['rid']);
        $re = Db::table('mbs_assessment_record')
            ->where('id', $rid)
            ->update(['status' => -1]);
        if ($re) {
            $data['code'] = 200;
            $data['msg'] = '删除成功';
            return json_encode($data);
        } else {
            $data['code'] = 0;
            $data['msg'] = '删除失败';
            return json_encode($data);
        }
    }
}