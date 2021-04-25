<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use think\Controller;
use think\Loader;
use think\migration\command\migrate\Status;
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
        $config = config('group');
//        var_dump($_SESSION);die();
        if ($_SESSION['GID'] == $config['dean']) {
            //院长
            $this->assign('sign', 2);
        } else if ($_SESSION['GID'] == $config['admin']) {
            //超级管理员
            $this->assign('sign', 1);
        }else if ($_SESSION['GID'] == $config['chief_dean']) {
            //总院长
            $this->assign('sign', 1);
        }else{
            $this->assign('sign', 5);
        }
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chainlist);
        $map = array();
        $ormap = array();
        $where = '';
        $orwhere = '';

        if (!empty($_GET['chain_id']) && intval($_GET['chain_id']) > 0) {
            $map['a.chain_id'] = intval($_GET['chain_id']);
            $where .= ' AND a.chain_id=\'' . intval($_GET['chain_id']) . '\' ';
            $this->assign('cid', intval($_GET['chain_id']));
        }
        //院长能看本院所有客户
        if ($_SESSION['GID'] == $config['dean']) {
            //院长
            $map['a.chain_id'] = intval($_SESSION['CID']);
            $where .= ' AND a.chain_id=\'' . intval($_SESSION['CID']) . '\' ';
        }

        //普通技师能看自己负责的客人
        if ($_SESSION['GID'] == $config['jishi']) {
            //技师
            $owearr = [];
            $owecust = Db::table('mbs_all_assessment')
                ->distinct(true)
                ->field('customer_id')
                ->where('status', 1)
                ->where('server_id', intval($_SESSION['UID']))
                ->select();
            foreach ($owecust as $k => $v) {
                $owearr[] = $v['customer_id'];
            }
            if (count($owearr) > 0) {
                $owestr = implode(',', $owearr);
                $map['a.id'] = ['in', $owestr];
                $where .= ' AND a.id in (' . $owestr . ')';
            }
        }

        if (!empty($_GET['keywords'])) {
            $ormap['a.name'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['a.phone'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['o.server_no'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['o.server_name'] = ['like', '%' . $_GET['keywords'] . '%'];
            $orwhere .= ' AND (a.name LIKE \'%' . $_GET['keywords'] . '%\' OR a.phone LIKE \'%' . $_GET['keywords'] . '%\'  OR o.server_no LIKE \'%' . $_GET['keywords'] . '%\'  OR o.server_name LIKE \'%' . $_GET['keywords'] . '%\') ';
        }
        if (!empty($_GET['create_time'])) {
            $create_time = strtotime($_GET['create_time']);
            $star_t = date('Y-m-d 00:00:00', $create_time);
            $end_t = date('Y-m-d 23:59:59', $create_time);
            $map['o.create_time'] = ['between time', [$star_t, $end_t]];
            $where .= ' AND o.create_time>\'' . $star_t . '\' AND o.create_time<\'' . $end_t . '\' ';
        }
        $map['a.status'] = 1;
        $pagesize = 20;
        Loader::import('page.Page', EXTEND_PATH, '.class.php');
        if (!empty($_GET['keywords'])) {
            $total = Db::table('mbs_customers')
                ->alias('a')
                ->join('mbs_all_assessment o', 'a.id=o.customer_id', 'LEFT')
                ->where('a.name LIKE \'%' . $_GET['keywords'] . '%\' OR a.phone LIKE \'%' . $_GET['keywords'] . '%\'  OR o.server_no LIKE \'%' . $_GET['keywords'] . '%\'  OR o.server_name LIKE \'%' . $_GET['keywords'] . '%\'')
                ->where($map)
                ->count();
        } else {
            $total = Db::table('mbs_customers')
                ->alias('a')
                ->join('mbs_all_assessment o', 'a.id=o.customer_id', 'LEFT')
                ->where($map)
                ->count();
        }

        $page = new \page($total, $pagesize);
        $re = Db::query('SELECT a.*,o.id sid,o.status ostatus,o.server_id ser_id FROM mbs_customers AS a LEFT JOIN mbs_all_assessment AS o ON a.id=o.customer_id 
WHERE a.status=1 ' . $orwhere . $where . ' ORDER BY a.id DESC ' . $page->limit);
        $down_page = $page->fpage();
        $this->assign('page', $down_page);
        $cusno = [];
        $ree = [];
        foreach ($re as $k => $v) {
            $cusno[] = $v['id'];
            $ree[$v['id']] = $v;
        }
        //顾客id集合
        $str = implode(',', $cusno);

        $sermap['status'] = 1;
        $sermap['customer_id'] = array('in', $str);
        //有效服务单
        $serinfo = Db::table('mbs_all_assessment')
            ->alias('o')
            ->field('o.id sid,o.status ostatus,o.server_id ser_id,customer_id')
            ->where($sermap)
            ->order('id')
            ->select();

        foreach ($serinfo as $k => $v) {
            $ree[$v['customer_id']]['sid'] = $v['sid'];
            $ree[$v['customer_id']]['ostatus'] = $v['ostatus'];
            $ree[$v['customer_id']]['ser_id'] = $v['ser_id'];
        }
        //释放
        unset($serinfo);
        $this->assign('uid', $_SESSION['UID']);
        $this->assign('userlist', $ree);

        return $this->view->fetch();
    }

    public function edituser()
    {
        $id = intval($_GET['id']);
        $source = Db::table('mbs_fromway')
            ->where('status', 1)
            ->order('fromway_id asc')
            ->select();
        $this->assign('source', $source);

        if ($id > 0) {
            $userinfo = Db::table('mbs_customers')
                ->where('id', $id)
                ->find();
            $this->assign('userinfo', $userinfo);
        }
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chain);
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

    public function custfp()
    {
        $custidsstr = trim($_POST['custids']);
        $oldchain = intval($_POST['oldchain']);
        $newchain = intval($_POST['newchain']);
        $sign = intval($_POST['sign']);
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        if (strlen($custidsstr) == 0 && $sign == 2) {
            //批量迁移同店全部顾客

            Db::startTrans();
            try {
                $re1 = Db::table('mbs_customers')
                    ->where('status', 1)
                    ->update(['chain_id' => $newchain]);

                $logdata['user_id'] = $_SESSION['UID'];
                $logdata['customer_id'] = $custidsstr;
                $logdata['logType'] = '分配门店';
                $logdata['logContent'] = '将全部客户从' . $chainlist[$oldchain]['name'] . '分配至' . $chainlist[$newchain]['name'];
                $logdata['createTime'] = date('Y-m-d H:i:s');
                $logdata['status'] = 1;
                $re2 = Db::table('mbs_caozuo_log')
                    ->insert($logdata);

                if ($re1 && $re2) {
                    Db::commit();//提交事务
                    unset($data);
                    $data['code'] = 200;
                    $data['msg'] = '保存成功';
                    return json_encode($data);
                } else {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        } else {
            //一个或多个同店顾客分配
            if (strlen($custidsstr) == 0) {
                $data['code'] = 0;
                $data['msg'] = '操作客户不能为空';
                return json_encode($data);
            }
            Db::startTrans();
            try {
                $re = Db::table('mbs_customers')
                    ->field('chain_id')
                    ->where(' id in(' . $custidsstr . ') ')
                    ->group('chain_id')
                    ->select();
                if (count($re) > 1) {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '只能操作一家门店';
                    return json_encode($data);
                }
                $re1 = Db::table('mbs_customers')
                    ->where(' id in(' . $custidsstr . ') ')
                    ->update(['chain_id' => $newchain]);

                $logdata['user_id'] = $_SESSION['UID'];
                $logdata['customer_id'] = $custidsstr;
                $logdata['logType'] = '分配门店';
                $logdata['logContent'] = '将客户' . $custidsstr . '从' . $chainlist[$oldchain]['name'] . '分配至' . $chainlist[$newchain]['name'];
                $logdata['createTime'] = date('Y-m-d H:i:s');
                $logdata['status'] = 1;
                $re2 = Db::table('mbs_caozuo_log')
                    ->insert($logdata);

                if ($re1 && $re2) {
                    Db::commit();//提交事务
                    unset($data);
                    $data['code'] = 200;
                    $data['msg'] = '保存成功';
                    return json_encode($data);
                } else {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
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
        $this->assign('cusinfo', $cusinfo);
        $no = getuser_no();
        $this->assign('create_time', time());
        $this->assign('user_no', $no);
        return $this->view->fetch();
    }

    /*
     * 添加消费
     */
    public function addxiaofei()
    {
        $cname = '';
        //门店id
        $gid = intval($_SESSION['GID']);
        $config = config('group');
        //客户信息
        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            $id = intval($_GET['id']);
            $cusinfo = Db::table('mbs_customers')
                ->alias('a')
                ->join('mbs_all_assessment s', 'a.id = s.customer_id', 'left')
                ->field('a.*,s.server_id')
                ->where('a.id', $id)
                ->find();
            $cid = $_SESSION['CID'] ?: $cusinfo['chain_id'];
            if ($cid > 0) {
                $cnamearr = getChainname($cid);
                $cname = $cnamearr['name'];
            }
            //默认门店技师
            $jsmap['status'] = 1;
            $jsmap['chain_id'] = $cid;
            $jsmap['user_group'] = $config['jishi'];

            $jishi = Db::table('mbs_user')
                ->where($jsmap)
                ->order('id asc')
                ->select();
            $this->assign('jishi', $jishi);
            //默认门店诊疗项目
            $pakmap['status'] = 1;
            $package = Db::table('mbs_package')
                ->where($pakmap)
                ->where('find_in_set(' . $cid . ',chain_id)')
                ->order('package_id asc')
                ->select();
            $this->assign('package', $package);

            //默认门店前台
            $reception = intval($config['reception']);
            $remap['status'] = 1;
            $remap['chain_id'] = $cid;
            $remap['user_group'] = $config['reception'];
            $recelist = Db::table('mbs_user')
                ->where($remap)
                ->order('id asc')
                ->select();
            $this->assign('reception', $recelist);
            $cin['cid'] = $cid;
            $cin['cname'] = $cname;
            $this->assign('cin', $cin);
            $this->assign('cname', $cname);
            $this->assign('cusinfo', $cusinfo);

        }


        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        } else if ($gid == $config['dean']) {
            //院长
            $this->assign('usign', 2);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        }


        //支付方式
        $paymap['status'] = 1;
        $paymap['paytype'] = 1;
        $payway = Db::table('mbs_payway')
            ->where($paymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        $opaymap['status'] = 1;
        $opaymap['paytype'] = 2;
        $opayway = Db::table('mbs_payway')
            ->where($opaymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('opayway', $opayway);
        return $this->view->fetch();
    }

    public function addcustinfo()
    {
        $cname = '';
        $cid = 0;
        $jishi = array();
        $package = array();
        $recelist = array();
        //门店id
        $gid = intval($_SESSION['GID']);
        $config = config('group');
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $custinfo = Db::table('mbs_customers')
                ->where('status', 1)
                ->where('id', intval($_GET['id']))
                ->find();
            $cardinfo = Db::table('mbs_customer_card')
                ->where('status', 1)
                ->where('customer_id', intval($_GET['id']))
                ->find();

            $this->assign('custinfo', $custinfo);
            $this->assign('cardinfo', $cardinfo);
            if (!empty($custinfo)) {
                $cid = intval($custinfo['chain_id']);
            } else {
                header("location:" . DQURL . 'index/customers/');
            }
        }
        //客户信息
        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        } else if ($gid == $config['dean']) {
            //院长
            $cid = intval($_SESSION['CID']);

            $this->assign('usign', 2);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        }
        if ($cid > 0) {
            $cnamearr = getChainname($cid);
            $cname = $cnamearr['name'];


            //默认门店技师
            $jsmap['status'] = 1;
            $jsmap['chain_id'] = $cid;
            $jsmap['user_group'] = $config['jishi'];

            $jishi = Db::table('mbs_user')
                ->where($jsmap)
                ->order('id asc')
                ->select();

            //默认门店诊疗项目
            $pakmap['status'] = 1;
            $package = Db::table('mbs_package')
                ->where($pakmap)
                ->where('find_in_set(' . $cid . ',chain_id)')
                ->order('package_id asc')
                ->select();


            //默认门店前台
            $reception = intval($config['reception']);
            $remap['status'] = 1;
            $remap['chain_id'] = $cid;
            $remap['user_group'] = $config['reception'];
            $recelist = Db::table('mbs_user')
                ->where($remap)
                ->order('id asc')
                ->select();

            $cin['cid'] = $cid;
            $cin['cname'] = $cname;
            $this->assign('cin', $cin);
            $this->assign('cname', $cname);
        }
        //来源
        $source = Db::table('mbs_fromway')
            ->where('status', 1)
            ->order('fromway_id asc')
            ->select();
        $this->assign('source', $source);
        //支付方式
        $paymap['status'] = 1;
        $paymap['paytype'] = 1;
        $payway = Db::table('mbs_payway')
            ->where($paymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        $opaymap['status'] = 1;
        $opaymap['paytype'] = 2;
        $opayway = Db::table('mbs_payway')
            ->where($opaymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('opayway', $opayway);
        $this->assign('jishi', $jishi);
        $this->assign('package', $package);
        $this->assign('reception', $recelist);
        return $this->view->fetch();
    }

    public function editcustinfo()
    {
        //编辑消费
        $cname = '';
        $cid = 0;
        $jishi = array();
        $package = array();
        $recelist = array();
        //门店id
        $gid = intval($_SESSION['GID']);
        $config = config('group');
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            //消费id
            $xiaofeiinfo = Db::table('mbs_xiaofei')
                ->where('status', 1)
                ->where('xiaofei_id', intval($_GET['id']))
                ->find();
            $this->assign('xiaofeiinfo', $xiaofeiinfo);

            if (empty($xiaofeiinfo)) {
                echo '非法操作';
                header("location:" . DQURL . 'index/customers/');
            }

            $custinfo = Db::table('mbs_customers')
                ->where('status', 1)
                ->where('id', intval($xiaofeiinfo['customer_id']))
                ->find();

            $cardinfo = Db::table('mbs_customer_card')
                ->where('status', 1)
                ->where('customer_id', intval($xiaofeiinfo['customer_id']))
                ->find();

            $this->assign('custinfo', $custinfo);
            $this->assign('cardinfo', $cardinfo);
            if (!empty($custinfo)) {
                $cid = intval($custinfo['chain_id']);
            } else {
                header("location:" . DQURL . 'index/customers/');
            }
        } else {
            echo '非法操作';
            header("location:" . DQURL . 'index/customers/');
        }
        //客户信息
        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        } else if ($gid == $config['dean']) {
            //院长
            $cid = intval($_SESSION['CID']);

            $this->assign('usign', 2);
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $this->assign('chain', $chain);
        }
        if ($cid > 0) {
            $cnamearr = getChainname($cid);
            $cname = $cnamearr['name'];
            //默认门店技师
            $jsmap['status'] = 1;
            $jsmap['chain_id'] = $cid;
            $jsmap['user_group'] = $config['jishi'];

            $jishi = Db::table('mbs_user')
                ->where($jsmap)
                ->order('id asc')
                ->select();

            //默认门店诊疗项目
            $pakmap['status'] = 1;
            $package = Db::table('mbs_package')
                ->where($pakmap)
                ->where('find_in_set(' . $cid . ',chain_id)')
                ->order('package_id asc')
                ->select();


            //默认门店前台
            $reception = intval($config['reception']);
            $remap['status'] = 1;
            $remap['chain_id'] = $cid;
            $remap['user_group'] = $config['reception'];
            $recelist = Db::table('mbs_user')
                ->where($remap)
                ->order('id asc')
                ->select();

            $cin['cid'] = $cid;
            $cin['cname'] = $cname;
            $this->assign('cin', $cin);
            $this->assign('cname', $cname);
        }
        //来源
        $source = Db::table('mbs_fromway')
            ->where('status', 1)
            ->order('fromway_id asc')
            ->select();
        $this->assign('source', $source);
        //支付方式
        $paymap['status'] = 1;
        $paymap['paytype'] = 1;
        $payway = Db::table('mbs_payway')
            ->where($paymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        $opaymap['status'] = 1;
        $opaymap['paytype'] = 2;
        $opayway = Db::table('mbs_payway')
            ->where($opaymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('opayway', $opayway);
        $this->assign('jishi', $jishi);
        $this->assign('package', $package);
        $this->assign('reception', $recelist);
        return $this->view->fetch();
    }

    public function editxiaofei()
    {
        $id = intval($_GET['id']);
        $xiaofeiinfo = Db::table('mbs_xiaofei')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'INNER')
            ->field('a.*')
            ->where('a.xiaofei_id', $id)
            ->find();
        if ($xiaofeiinfo['serchain_id'] > 0) {
            $cid = $xiaofeiinfo['serchain_id'];
            $cnamearr = getChainname($cid);
            $cname = $cnamearr['name'];
        }
        $gid = intval($_SESSION['GID']);
        $config = config('group');
        //客户信息
        $cusinfo = Db::table('mbs_customers')
            ->where('id', $xiaofeiinfo['customer_id'])
            ->find();

        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);
        } else if ($gid == $config['dean']) {
            //院长
            $this->assign('usign', 2);
        }
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $this->assign('chain', $chain);

        //默认门店技师
        $jsmap['status'] = 1;
        $jsmap['chain_id'] = $cid;
        $jsmap['user_group'] = $config['jishi'];

        $jishi = Db::table('mbs_user')
            ->where($jsmap)
            ->order('id asc')
            ->select();
        $this->assign('jishi', $jishi);
        //默认门店诊疗项目
        $pakmap['status'] = 1;
        $package = Db::table('mbs_package')
            ->where($pakmap)
            ->where('find_in_set(' . $cid . ',chain_id)')
            ->order('package_id asc')
            ->select();
        $this->assign('package', $package);

        //默认门店前台
        $reception = intval($config['reception']);
        $remap['status'] = 1;
        $remap['chain_id'] = $cid;
        $remap['user_group'] = $config['reception'];
        $recelist = Db::table('mbs_user')
            ->where($remap)
            ->order('id asc')
            ->select();
        $this->assign('reception', $recelist);

        //支付方式
        $paymap['status'] = 1;
        $paymap['paytype'] = 1;
        $payway = Db::table('mbs_payway')
            ->where($paymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        $opaymap['status'] = 1;
        $opaymap['paytype'] = 2;
        $opayway = Db::table('mbs_payway')
            ->where($opaymap)
            ->order('payway_id asc')
            ->select();
        $this->assign('opayway', $opayway);

        $cin['cid'] = $cid;
        $cin['cname'] = $cname;
        $this->assign('cin', $cin);
        $this->assign('cname', $cname);

        $this->assign('cusinfo', $cusinfo);
        $this->assign('xiaofeiinfo', $xiaofeiinfo);
        return $this->view->fetch();
    }

    public function savexiaofei()
    {
        $user_info_str = array_filter(explode(',', $_POST['xfinfo']));
        $dataxf = array();
        $xiaofei_id = 0;
        if (isset($_POST['xiaofei_id'])) {
            $xiaofei_id = intval($_POST['xiaofei_id']);
        }
        foreach ($user_info_str as $k => $v) {
            $dataxf[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        $dataxf['update_time'] = date('Y-m-d H:m:s', time());
        if ($xiaofei_id > 0) {
            //编辑保存

            if (intval($dataxf['customer_id']) > 0) {
                $customer = Db::table('mbs_customers')
                    ->where('id', intval($dataxf['customer_id']))
                    ->find();
            } else {
                $data['code'] = 0;
                $data['msg'] = '操作失败';
                return json_encode($data);
            }
            if ($customer) {
                $dataxf['fromway_id'] = $customer['source_id'];
            }


            //消费
            Db::startTrans();

            try {
                //作废相关还款
                $datahk['status'] = 0;
                $ifhave = Db::table('mbs_xfhuankuan')
                    ->where('xiaofei_id', $xiaofei_id)
                    ->where('status', 1)
                    ->select();

                $re0 = Db::table('mbs_xfhuankuan')
                    ->where('xiaofei_id', $xiaofei_id)
                    ->update($datahk);
                //添加消费
                $ree = Db::table('mbs_xiaofei')
                    ->where('customer_id', $dataxf['customer_id'])
                    ->where('status', 1)
                    ->find();
                if ($ree) {
                    $dataxf['firvisit'] = 2;
                } else {
                    $dataxf['firvisit'] = 1;
                }
                $re = Db::table('mbs_xiaofei')
                    ->where('xiaofei_id', $xiaofei_id)
                    ->update($dataxf);
                //操作日志
                $logdata['xf_id'] = $xiaofei_id;
                $logdata['user_id'] = $_SESSION['UID'];
                $logdata['customer_id'] = $dataxf['customer_id'];
                $logdata['logType'] = 3;
                $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'];
                $logdata['money'] = $dataxf['total_money'];
                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                $re1 = Db::table('mbs_xiaofei_log')
                    ->insert($logdata);
                if (($re && $re1 && $re0 && !empty($ifhave)) || (empty($ifhave) && $re && $re1)) {
                    Db::commit();//提交事务
                    unset($data);
                    $data['code'] = 200;
                    $data['msg'] = '保存成功';
                    return json_encode($data);
                } else {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }


        } else {
            if (intval($dataxf['customer_id']) > 0) {
                $customer = Db::table('mbs_customers')
                    ->where('id', intval($dataxf['customer_id']))
                    ->find();
            } else {
                $data['code'] = 0;
                $data['msg'] = '操作失败';
                return json_encode($data);
            }

            if ($customer) {
                $dataxf['fromway_id'] = $customer['source_id'];
            }
            if (intval($dataxf['package_id']) == -1) {
                //退款
                unset($dataxf['huakou']);
                Db::startTrans();
                try {
                    //添加退费
                    $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                    $re = Db::table('mbs_tuifei')
                        ->insert($dataxf);
                    //操作日志
                    $logdata['xf_id'] = $re;
                    $logdata['user_id'] = $_SESSION['UID'];
                    $logdata['customer_id'] = $dataxf['customer_id'];
                    $logdata['logType'] = 2;
                    $logdata['logContent'] = '添加退费记录，退费金额' . $dataxf['total_money'];
                    $logdata['money'] = $dataxf['total_money'];
                    $logdata['createTime'] = date('Y-m-d H:m:s', time());

                    $re1 = Db::table('mbs_xiaofei_log')
                        ->insert($logdata);
                    if ($re && $re1) {
                        Db::commit();//提交事务
                        unset($data);
                        $data['code'] = 200;
                        $data['msg'] = '保存成功';
                        return json_encode($data);
                    } else {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }
                } catch (\Exception $e) {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }

            } else {
                //消费
                Db::startTrans();
                try {
                    //添加消费
                    $ree = Db::table('mbs_xiaofei')
                        ->where('customer_id', $dataxf['customer_id'])
                        ->find();
                    if ($ree) {
                        $dataxf['firvisit'] = 2;
                    } else {
                        $dataxf['firvisit'] = 1;
                    }
                    $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                    $re = Db::table('mbs_xiaofei')
                        ->insert($dataxf);
                    //操作日志
                    $logdata['xf_id'] = $re;
                    $logdata['user_id'] = $_SESSION['UID'];
                    $logdata['customer_id'] = $dataxf['customer_id'];
                    $logdata['logType'] = 1;
                    $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                    $logdata['money'] = $dataxf['total_money'];
                    $logdata['createTime'] = date('Y-m-d H:m:s', time());

                    $re1 = Db::table('mbs_xiaofei_log')
                        ->insert($logdata);
                    if ($re && $re1) {
                        Db::commit();//提交事务
                        unset($data);
                        $data['code'] = 200;
                        $data['msg'] = '保存成功';
                        return json_encode($data);
                    } else {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }
                } catch (\Exception $e) {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }
            }
        }


    }

    public function savecustxf()
    {
        //客户信息
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
        $data['source_id'] = $new['sourceid'];
        $data['source_from'] = $new['sourcefrom'];
        $data['chain_id'] = $new['chainid'];
        $data['money'] = $new['money'];

        $rule = '/^0?(13|14|15|17|18)[0-9]{9}$/';
        $result = preg_match($rule, $data['phone']);
        if (!$result) {
            $data['msg'] = "手机号码有误";
            $data['status'] = 0;
            return json_encode($data);
        }
        if (empty($data['chain_id'])) {
            $data['msg'] = "门店不能为空";
            $data['status'] = 0;
            return json_encode($data);
        }
        //消费信息
        $user_info_str = array_filter(explode(',', $_POST['xfinfo']));
        $dataxf = array();

        foreach ($user_info_str as $k => $v) {
            $dataxf[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        $dataxf['reception_id'] = intval($dataxf['reception_id']);
        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //不是新客户 id为客户id
            $ree = Db::table('mbs_xiaofei')
                ->where('customer_id', intval($_GET['id']))
                ->find();
            if ($ree) {
                $dataxf['firvisit'] = 2;
            } else {
                $dataxf['firvisit'] = 1;
            }
            if (intval($dataxf['total_money']) > 0) {
                //有消费
                $data['update_time'] = date('Y-m-d h:i:s', time());
                if (intval($data['chain_id']) != intval($dataxf['serchain_id'])) {
                    $data['code'] = 0;
                    $data['msg'] = '服务门店与客户门店不一致';
                    return json_encode($data);
                }
                $if_exist = Db::table('mbs_customers')
                    ->where('phone', trim($data['phone']))
                    ->where('id!=' . intval($_GET['id']))
                    ->where('status', 1)
                    ->find();
                if (!empty($if_exist)) {
                    $data['code'] = 0;
                    $data['msg'] = '手机号重复';
                    return json_encode($data);
                }
                //是否有卡
                $cardinfo = Db::table('mbs_customer_card')
                    ->where('customer_id', intval($_GET['id']))
                    ->where('status', 1)
                    ->find();
                //客户信息
                $custinfo = Db::table('mbs_customers')
                    ->where('id', intval($_GET['id']))
                    ->where('status', 1)
                    ->find();

                if (intval($dataxf['package_id']) == -1) {
                    //退费
                    //修改客户
                    unset($dataxf['huakou']);
                    Db::startTrans();
                    try {
                        $re = Db::table('mbs_customers')
                            ->where('id', intval($_GET['id']))
                            ->update($data);
                        //退费
                        $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                        $dataxf['customer_id'] = intval($_GET['id']);
                        $dataxf['customer_name'] = trim($data['name']);
                        $retf = Db::table('mbs_tuifei')
                            ->insertGetId($dataxf);
                        //添加操作日志
                        $logdata['xf_id'] = intval($retf);
                        $logdata['user_id'] = $_SESSION['UID'];
                        $logdata['customer_id'] = intval($_GET['id']);
                        $logdata['logType'] = 2;
                        $logdata['logContent'] = '添加退费记录，退费金额' . $dataxf['total_money'];
                        $logdata['money'] = $dataxf['total_money'];
                        $logdata['createTime'] = date('Y-m-d H:m:s', time());

                        $re1 = Db::table('mbs_xiaofei_log')
                            ->insert($logdata);
                        if ($retf && $re1) {
                            Db::commit();//提交事务
                            unset($data);
                            $data['code'] = 200;
                            $data['custid'] = intval($_GET['id']);
                            $data['msg'] = '保存成功';
                            return json_encode($data);
                        } else {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    } catch (\Exception $e) {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }

                } else if (intval($dataxf['huakou']) > 0) {
                    //不是退费    //有划扣
                    if (intval($data['chain_id']) != intval($dataxf['serchain_id'])) {
                        $data['code'] = 0;
                        $data['msg'] = '服务门店与客户门店不一致';
                        return json_encode($data);
                    }
                    if (!empty($cardinfo)) {
                        //有卡
                        if ((intval($custinfo['money']) - intval($dataxf['huakou'])) >= 0) {
                            //修改客户
                            Db::startTrans();
                            try {
                                //划扣之后余额
                                $data['money'] = intval($custinfo['money']) - intval($dataxf['huakou']);
                                $re = Db::table('mbs_customers')
                                    ->where('id', intval($_GET['id']))
                                    ->update($data);

                                //修改卡余额
                                $xgdata['money'] = intval($custinfo['money']) - intval($dataxf['huakou']);
                                $re6 = Db::table('mbs_customer_card')
                                    ->where('card_id', intval($cardinfo['card_id']))
                                    ->where('status', 1)
                                    ->update($xgdata);
                                //卡消费操作
                                $cdata['card_id'] = intval($cardinfo['card_id']);
                                $cdata['customer_id'] = intval($_GET['id']);
                                $cdata['type'] = 2;
                                $cdata['chain_id'] = $data['chain_id'];
                                $cdata['money'] = intval($dataxf['huakou']);
                                $cdata['add_user'] = intval($_SESSION['UID']);
                                $cdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re3 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cdata);
                                //添加卡划扣值日志
                                $logdata['xf_id'] = intval($re3);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 7;
                                $logdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                                $logdata['money'] = $dataxf['huakou'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                $re4 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                //添加消费
                                $dataxf['card_id'] = intval($cardinfo['card_id']);
                                $dataxf['hk_caozuoid'] = intval($re3);
                                $dataxf['customer_id'] = intval($_GET['id']);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);
                                //添加消费日志
                                $logdata['xf_id'] = intval($rexf);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if ($re && $rexf && $re3 && $re2 && $re4 && $re6) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($_GET['id']);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }

                        } else {
                            $data['code'] = 0;
                            $data['msg'] = '划扣不能大于卡内余额';
                            return json_encode($data);
                        }
                    } else {
                        //没有卡 先建卡
                        Db::startTrans();
                        try {
                            $cadata['money'] = intval($data['money']); //充值金额
                            $data['money'] = intval($data['money']) - intval($dataxf['huakou']);
                            $re = Db::table('mbs_customers')
                                ->where('id', intval($_GET['id']))
                                ->update($data);

                            //建卡
                            $chdata['customer_id'] = intval($_GET['id']);
                            $chdata['card_no'] = time() . mt_rand(1, 100);;
                            $chdata['phone'] = $data['phone'];
                            $chdata['money'] = $data['money'];
                            $chdata['chain_id'] = $data['chain_id'];
                            $chdata['add_user'] = intval($_SESSION['UID']);
                            $chdata['type'] = 1;
                            $chdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re7 = Db::table('mbs_customer_card')
                                ->insertGetId($chdata);

                            //卡创建充值操作
                            $cadata['card_id'] = intval($re7);
                            $cadata['customer_id'] = intval($_GET['id']);
                            $cadata['type'] = 1;
                            $cadata['chain_id'] = $data['chain_id'];
                            $cadata['add_user'] = intval($_SESSION['UID']);
                            $cadata['create_time'] = date('Y-m-d h:i:s', time());
                            $re1 = Db::table('mbs_caozuocard')
                                ->insertGetId($cadata);

                            //添加充值日志
                            $logcadata['xf_id'] = intval($re1);
                            $logcadata['user_id'] = $_SESSION['UID'];
                            $logcadata['customer_id'] = intval($_GET['id']);
                            $logcadata['logType'] = 5;
                            $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                            $logcadata['money'] = intval($cadata['money']);
                            $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                            $re5 = Db::table('mbs_xiaofei_log')
                                ->insert($logcadata);
                            //卡划扣操作
                            $cdata['card_id'] = intval($re7);
                            $cdata['customer_id'] = intval($_GET['id']);
                            $cdata['type'] = 2;
                            $cdata['chain_id'] = $data['chain_id'];
                            $cdata['money'] = intval($dataxf['huakou']);
                            $cdata['add_user'] = intval($_SESSION['UID']);
                            $cdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re3 = Db::table('mbs_caozuocard')
                                ->insertGetId($cdata);

                            //添加卡划扣值日志
                            $loghkdata['xf_id'] = intval($re3);
                            $loghkdata['user_id'] = $_SESSION['UID'];
                            $loghkdata['customer_id'] = intval($_GET['id']);
                            $loghkdata['logType'] = 7;
                            $loghkdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                            $loghkdata['money'] = $dataxf['huakou'];
                            $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                            $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re4 = Db::table('mbs_xiaofei_log')
                                ->insert($loghkdata);

                            //添加消费
                            $dataxf['card_id'] = intval($re7);
                            $dataxf['cz_caozuoid'] = intval($re1);
                            $dataxf['hk_caozuoid'] = intval($re3);
                            $dataxf['customer_id'] = intval($_GET['id']);
                            $dataxf['customer_name'] = trim($data['name']);
                            $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                            $rexf = Db::table('mbs_xiaofei')
                                ->insertGetId($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($rexf);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = intval($_GET['id']);
                            $logdata['logType'] = 1;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);

                            if ($re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['custid'] = intval($_GET['id']);
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    }
                } else {
                    //没有划扣
                    //有充值
                    if (intval($data['money']) > 0) {
                        if (!empty($cardinfo)) {
                            $re = Db::table('mbs_customers')
                                ->where('id', intval($_GET['id']))
                                ->update($data);
                            //不能更改卡余额
                            $dataxf['card_id'] = intval($cardinfo['card_id']);
                            Db::startTrans();
                            try {
                                $dataxf['customer_id'] = intval($_GET['id']);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);
                                //操作日志
                                $logdata['xf_id'] = $rexf;
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                $re1 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);

                                if ($rexf && $re1) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($_GET['id']);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }
                        {
                            //没有卡是要建卡
                            Db::startTrans();
                            try {
                                //修改用户
                                $re = Db::table('mbs_customers')
                                    ->where('id', intval($_GET['id']))
                                    ->update($data);
                                //建卡
                                $chdata['customer_id'] = intval($_GET['id']);
                                $chdata['card_no'] = time() . mt_rand(1, 100);;
                                $chdata['phone'] = $data['phone'];
                                $chdata['money'] = $data['money'];
                                $chdata['chain_id'] = $data['chain_id'];
                                $chdata['add_user'] = intval($_SESSION['UID']);
                                $chdata['type'] = 1;
                                $chdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re7 = Db::table('mbs_customer_card')
                                    ->insertGetId($chdata);
                                //卡创建充值操作
                                $cadata['card_id'] = intval($re7);
                                $cadata['customer_id'] = intval($_GET['id']);
                                $cadata['type'] = 1;
                                $cadata['money'] = $data['money'];
                                $cadata['chain_id'] = $data['chain_id'];
                                $cadata['add_user'] = intval($_SESSION['UID']);
                                $cadata['create_time'] = date('Y-m-d h:i:s', time());
                                $re1 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cadata);

                                //添加充值日志
                                $logcadata['xf_id'] = intval($re1);
                                $logcadata['user_id'] = $_SESSION['UID'];
                                $logcadata['customer_id'] = intval($_GET['id']);
                                $logcadata['logType'] = 5;
                                $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                                $logcadata['money'] = intval($cadata['money']);
                                $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                                $re5 = Db::table('mbs_xiaofei_log')
                                    ->insert($logcadata);

                                //添加消费
                                $dataxf['customer_id'] = intval($_GET['id']);
                                $dataxf['card_id'] = intval($re7);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);
                                //操作日志
                                $logdata['xf_id'] = $rexf;
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);

                                if ($rexf && $re1 && $re && $re2 && $re7 && $re5) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($_GET['id']);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }

                        }
                    } else {
                        $re = Db::table('mbs_customers')
                            ->where('id', intval($_GET['id']))
                            ->update($data);
                        Db::startTrans();
                        try {
                            $dataxf['customer_id'] = intval($_GET['id']);
                            $dataxf['customer_name'] = trim($data['name']);
                            $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                            $rexf = Db::table('mbs_xiaofei')
                                ->insertGetId($dataxf);
                            //操作日志
                            $logdata['xf_id'] = $rexf;
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = intval($_GET['id']);
                            $logdata['logType'] = 1;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:i:s', time());

                            $re1 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);

                            if ($rexf && $re1) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['custid'] = intval($_GET['id']);
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    }


                }
            } else {
                //无消费
                if (intval($data['money']) > 0) {
                    //有充值
                    if (intval($dataxf['huakou']) > 0) {
                        //有划扣
                        if ((intval($data['money']) - intval($dataxf['huakou'])) >= 0) {
                            $cardinfo = Db::table('mbs_customer_card')
                                ->where('customer_id', intval($_GET['id']))
                                ->where('status', 1)
                                ->find();
                            $custinfo = Db::table('mbs_customers')
                                ->where('id', intval($_GET['id']))
                                ->where('status', 1)
                                ->find();
                            if (!empty($cardinfo)) {
                                //有卡 不能直接改卡余额
                                //修改客户
                                $data['money'] = intval($custinfo['money']) - intval($dataxf['huakou']);
                                $re = Db::table('mbs_customers')
                                    ->where('id', intval($_GET['id']))
                                    ->update($data);
                                //修改卡余额
                                $xgdata['money'] = intval($custinfo['money']) - intval($dataxf['huakou']);
                                $re6 = Db::table('mbs_customer_card')
                                    ->where('card_id', intval($cardinfo['card_id']))
                                    ->where('status', 1)
                                    ->update($xgdata);
                                //卡消费操作
                                $cdata['card_id'] = intval($cardinfo['card_id']);
                                $cdata['customer_id'] = intval($_GET['id']);
                                $cdata['type'] = 2;
                                $cdata['chain_id'] = $data['chain_id'];
                                $cdata['money'] = intval($dataxf['huakou']);
                                $cdata['add_user'] = intval($_SESSION['UID']);
                                $cdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re3 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cdata);
                                //添加卡划扣值日志
                                $logdata['xf_id'] = intval($re3);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 7;
                                $logdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                                $logdata['money'] = $dataxf['huakou'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                $re4 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                //添加消费
                                $dataxf['card_id'] = intval($cardinfo['card_id']);
                                $dataxf['hk_caozuoid'] = intval($re3);
                                $dataxf['customer_id'] = intval($_GET['id']);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);
                                //添加消费日志
                                $logdata['xf_id'] = intval($rexf);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($_GET['id']);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if ($re && $rexf && $re2 && $re3 && $re4 && $re6) {
                                    $data['code'] = 200;
                                    $data['msg'] = '修改成功';
                                    $data['custid'] = intval($_GET['id']);
                                    return json_encode($data);
                                } else {
                                    $data['code'] = 0;
                                    $data['msg'] = '无更改';
                                    return json_encode($data);
                                }

                            } else {
                                //无卡
                                Db::startTrans();
                                try {
                                    $cadata['money'] = $data['money'];
                                    //修改客户
                                    $data['money'] = $data['money'] - intval($dataxf['huakou']);
                                    $re = Db::table('mbs_customers')
                                        ->where('id', intval($_GET['id']))
                                        ->update($data);
                                    //建卡
                                    $chdata['customer_id'] = intval($_GET['id']);
                                    $chdata['card_no'] = time() . mt_rand(1, 100);;
                                    $chdata['phone'] = $data['phone'];
                                    $chdata['money'] = $data['money'];
                                    $chdata['chain_id'] = $data['chain_id'];
                                    $chdata['add_user'] = intval($_SESSION['UID']);
                                    $chdata['type'] = 1;
                                    $chdata['create_time'] = date('Y-m-d h:i:s', time());
                                    $re7 = Db::table('mbs_customer_card')
                                        ->insertGetId($chdata);
                                    //卡创建充值操作
                                    $cadata['card_id'] = intval($re7);
                                    $cadata['customer_id'] = intval($_GET['id']);
                                    $cadata['type'] = 1;
                                    $cadata['chain_id'] = $data['chain_id'];
                                    $cadata['add_user'] = intval($_SESSION['UID']);
                                    $cadata['create_time'] = date('Y-m-d h:i:s', time());
                                    $re1 = Db::table('mbs_caozuocard')
                                        ->insertGetId($cadata);

                                    //添加充值日志
                                    $logcadata['xf_id'] = intval($re1);
                                    $logcadata['user_id'] = $_SESSION['UID'];
                                    $logcadata['customer_id'] = intval($_GET['id']);
                                    $logcadata['logType'] = 5;
                                    $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                                    $logcadata['money'] = intval($cadata['money']);
                                    $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                                    $re5 = Db::table('mbs_xiaofei_log')
                                        ->insert($logcadata);
                                    //卡消费操作
                                    $cdata['card_id'] = intval($re7);
                                    $cdata['customer_id'] = intval($_GET['id']);
                                    $cdata['type'] = 2;
                                    $cdata['chain_id'] = $data['chain_id'];
                                    $cdata['money'] = intval($dataxf['huakou']);
                                    $cdata['add_user'] = intval($_SESSION['UID']);
                                    $cdata['create_time'] = date('Y-m-d h:i:s', time());
                                    $re3 = Db::table('mbs_caozuocard')
                                        ->insertGetId($cdata);
                                    //添加卡划扣值日志
                                    $logdata['xf_id'] = intval($re3);
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = intval($_GET['id']);
                                    $logdata['logType'] = 7;
                                    $logdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                                    $logdata['money'] = $dataxf['huakou'];
                                    $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                    $re4 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);
                                    //添加消费
                                    $dataxf['card_id'] = intval($re7);
                                    $dataxf['hk_caozuoid'] = intval($re3);
                                    $dataxf['customer_id'] = intval($_GET['id']);
                                    $dataxf['customer_name'] = trim($data['name']);
                                    $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                    $rexf = Db::table('mbs_xiaofei')
                                        ->insertGetId($dataxf);
                                    //添加消费日志
                                    $logdata['xf_id'] = intval($rexf);
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = intval($_GET['id']);
                                    $logdata['logType'] = 1;
                                    $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                                    $logdata['money'] = $dataxf['total_money'];
                                    $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                    $re2 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);
                                    if ($re && $re7 && $re1 && $re5 && $re3 && $re4 && $rexf && $re2) {
                                        Db::commit();//提交事务
                                        unset($data);
                                        $data['code'] = 200;
                                        $data['custid'] = intval($_GET['id']);
                                        $data['msg'] = '保存成功';
                                        return json_encode($data);
                                    } else {
                                        Db::rollback();//回滚事务
                                        unset($data);
                                        $data['code'] = 0;
                                        $data['msg'] = '保存失败';
                                        return json_encode($data);
                                    }
                                } catch (\Exception $e) {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            }
                        } else {
                            $data['code'] = 0;
                            $data['msg'] = '划扣金额不能大于卡内余额';
                            return json_encode($data);
                        }
                    } else {
                        //无划扣
                        $cardinfo = Db::table('mbs_customer_card')
                            ->where('customer_id', intval($_GET['id']))
                            ->where('status', 1)
                            ->find();
                        if (!empty($cardinfo)) {
                            //有卡 不能直接改卡余额
                            //修改客户
                            $re = Db::table('mbs_customers')
                                ->where('id', intval($_GET['id']))
                                ->update($data);
                            if ($re) {
                                $data['code'] = 200;
                                $data['msg'] = '修改成功';
                                $data['custid'] = intval($_GET['id']);
                                return json_encode($data);
                            } else {
                                $data['code'] = 0;
                                $data['msg'] = '无更改';
                                return json_encode($data);
                            }

                        } else {
                            //无卡
                            Db::startTrans();
                            try {
                                //修改客户
                                $re = Db::table('mbs_customers')
                                    ->where('id', intval($_GET['id']))
                                    ->update($data);
                                //建卡
                                $chdata['customer_id'] = intval($_GET['id']);
                                $chdata['card_no'] = time() . mt_rand(1, 100);;
                                $chdata['phone'] = $data['phone'];
                                $chdata['money'] = $data['money'];
                                $chdata['chain_id'] = $data['chain_id'];
                                $chdata['add_user'] = intval($_SESSION['UID']);
                                $chdata['type'] = 1;
                                $chdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re7 = Db::table('mbs_customer_card')
                                    ->insertGetId($chdata);
                                //卡创建充值操作
                                $cadata['card_id'] = intval($re7);
                                $cadata['customer_id'] = intval($_GET['id']);
                                $cadata['type'] = 1;
                                $cadata['money'] = $data['money'];
                                $cadata['chain_id'] = $data['chain_id'];
                                $cadata['add_user'] = intval($_SESSION['UID']);
                                $cadata['create_time'] = date('Y-m-d h:i:s', time());
                                $re1 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cadata);

                                //添加充值日志
                                $logcadata['xf_id'] = intval($re1);
                                $logcadata['user_id'] = $_SESSION['UID'];
                                $logcadata['customer_id'] = intval($_GET['id']);
                                $logcadata['logType'] = 5;
                                $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                                $logcadata['money'] = intval($cadata['money']);
                                $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                                $re5 = Db::table('mbs_xiaofei_log')
                                    ->insert($logcadata);
                                if ($re && $re7 && $re1 && $re5) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($_GET['id']);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }
                    }


                } else {
                    //无充值
                    if (intval($data['money']) > 0) {
                        $data['code'] = 0;
                        $data['msg'] = '划扣金额不能大于卡内余额';
                        return json_encode($data);
                    }
                    //修改客户
                    $re = Db::table('mbs_customers')
                        ->where('id', intval($_GET['id']))
                        ->update($data);
                    if ($re) {
                        $data['code'] = 200;
                        $data['custid'] = intval($_GET['id']);
                        $data['msg'] = '修改成功';
                        return json_encode($data);
                    } else {
                        $data['code'] = 0;
                        $data['msg'] = '无更改';
                        return json_encode($data);
                    }
                }
            }
        } else {
            //新客户
            $data['create_time'] = date('Y-m-d h:i:s', time());
            if (intval($dataxf['total_money']) > 0) {
                //有消费
                //启动事务
                //增加客户
                //手机号不能重复
                $if_exist = Db::table('mbs_customers')
                    ->where('phone', trim($data['phone']))
                    ->where('status', 1)
                    ->find();
                if (!empty($if_exist)) {
                    $data['code'] = 0;
                    $data['msg'] = '手机号重复';
                    return json_encode($data);
                }
                if (intval($data['chain_id']) != intval($dataxf['serchain_id'])) {
                    $data['code'] = 0;
                    $data['msg'] = '服务门店与客户门店不一致';
                    return json_encode($data);
                }
                if (intval($dataxf['package_id']) == -1) {
                    //退费
                    //添加退款
                    unset($dataxf['huakou']);
                    Db::startTrans();
                    try {
                        $re = Db::table('mbs_customers')
                            ->insertGetId($data);
                        $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                        $dataxf['customer_id'] = intval($re);
                        $dataxf['customer_name'] = trim($data['name']);
                        $retf = Db::table('mbs_tuifei')
                            ->insertGetId($dataxf);
                        //添加操作日志
                        $logdata['xf_id'] = intval($retf);
                        $logdata['user_id'] = $_SESSION['UID'];
                        $logdata['customer_id'] = intval($re);
                        $logdata['logType'] = 2;
                        $logdata['logContent'] = '添加退费记录，退费金额' . $dataxf['total_money'];
                        $logdata['money'] = $dataxf['total_money'];
                        $logdata['createTime'] = date('Y-m-d H:m:s', time());

                        $re1 = Db::table('mbs_xiaofei_log')
                            ->insert($logdata);
                        if ($re && $retf && $re1) {
                            Db::commit();//提交事务
                            unset($data);
                            $data['code'] = 200;
                            $data['custid'] = intval($re);
                            $data['msg'] = '保存成功';
                            return json_encode($data);
                        } else {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    } catch (\Exception $e) {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }

                } else {
                    //不是退费
                    if ($dataxf['huakou'] > 0) {
                        //有消费划扣

                        if ($dataxf['huakou'] > 0 && intval($data['money']) - intval($dataxf['huakou']) >= 0) {
                            //添加客户 $re
                            Db::startTrans();
                            try {
                                $cadata['money'] = intval($data['money']); //充值金额
                                $data['money'] = intval($data['money']) - intval($dataxf['huakou']);
                                $re = Db::table('mbs_customers')
                                    ->insertGetId($data);
                                //卡创建
                                $chdata['customer_id'] = intval($re);
                                $chdata['card_no'] = time() . mt_rand(1, 100);;
                                $chdata['phone'] = $data['phone'];
                                $chdata['money'] = $data['money'];
                                $chdata['chain_id'] = $data['chain_id'];
                                $chdata['add_user'] = intval($_SESSION['UID']);
                                $chdata['type'] = 1;
                                $chdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re7 = Db::table('mbs_customer_card')
                                    ->insertGetId($chdata);
                                //卡创建充值操作
                                $cadata['card_id'] = intval($re7);
                                $cadata['customer_id'] = intval($re);
                                $cadata['type'] = 1;
                                $cadata['money'] = $cadata['money'];
                                $cadata['chain_id'] = $data['chain_id'];
                                $cadata['add_user'] = intval($_SESSION['UID']);
                                $cadata['create_time'] = date('Y-m-d h:i:s', time());
                                $re1 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cadata);
                                //添加充值日志
                                $logcadata['xf_id'] = intval($re1);
                                $logcadata['user_id'] = $_SESSION['UID'];
                                $logcadata['customer_id'] = intval($re);
                                $logcadata['logType'] = 5;
                                $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                                $logcadata['money'] = intval($cadata['money']);
                                $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                                $re5 = Db::table('mbs_xiaofei_log')
                                    ->insert($logcadata);

                                //卡划扣操作
                                $cdata['card_id'] = intval($re7);
                                $cdata['customer_id'] = intval($re);
                                $cdata['type'] = 2;
                                $cdata['chain_id'] = $data['chain_id'];
                                $cdata['money'] = intval($dataxf['huakou']);
                                $cdata['add_user'] = intval($_SESSION['UID']);
                                $cdata['create_time'] = date('Y-m-d h:i:s', time());
                                $re3 = Db::table('mbs_caozuocard')
                                    ->insertGetId($cdata);

                                //添加卡划扣值日志
                                $loghkdata['xf_id'] = intval($re3);
                                $loghkdata['user_id'] = $_SESSION['UID'];
                                $loghkdata['customer_id'] = intval($re);
                                $loghkdata['logType'] = 7;
                                $loghkdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                                $loghkdata['money'] = $dataxf['huakou'];
                                $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                                $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                                $re4 = Db::table('mbs_xiaofei_log')
                                    ->insert($loghkdata);

                                //添加消费
                                $dataxf['firvisit'] = 1;
                                $dataxf['card_id'] = intval($re7);
                                $dataxf['cz_caozuoid'] = intval($re1);
                                $dataxf['hk_caozuoid'] = intval($re3);
                                $dataxf['customer_id'] = intval($re);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
//                    var_dump($dataxf);die();
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);

                                //添加消费日志
                                $logdata['xf_id'] = intval($rexf);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($re);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);

                                if ($re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($re);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } else if (intval($data['money']) - intval($dataxf['huakou']) < 0) {
                            //划扣金额判断
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '划扣不能大于卡内余额';
                            return json_encode($data);
                        }
                    } else {
                        //没有划扣
                        if (intval($data['money']) > 0) {
                            //有充值
                            $re = Db::table('mbs_customers')
                                ->insertGetId($data);
                            //卡充值
                            $cadata['money'] = intval($data['money']); //充值金额
                            $cadata['customer_id'] = intval($re);
                            $cadata['card_no'] = time() . mt_rand(1, 100);;
                            $cadata['phone'] = $data['phone'];
                            $cadata['type'] = 1;
                            $cadata['chain_id'] = $data['chain_id'];
                            $cadata['add_user'] = intval($_SESSION['UID']);
                            $cadata['create_time'] = date('Y-m-d h:i:s', time());
                            $re1 = Db::table('mbs_customer_card')
                                ->insertGetId($cadata);
                            //添加充值日志
                            $logcadata['xf_id'] = intval($re1);
                            $logcadata['user_id'] = $_SESSION['UID'];
                            $logcadata['customer_id'] = intval($re);
                            $logcadata['logType'] = 5;
                            $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                            $logcadata['money'] = intval($cadata['money']);
                            $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                            $re5 = Db::table('mbs_xiaofei_log')
                                ->insert($logcadata);
                            //添加消费
                            $dataxf['firvisit'] = 1;
                            $dataxf['customer_id'] = intval($re);
                            $dataxf['card_id'] = intval($re1);
                            $dataxf['customer_name'] = trim($data['name']);
                            $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                            $rexf = Db::table('mbs_xiaofei')
                                ->insertGetId($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($rexf);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = intval($re);
                            $logdata['logType'] = 1;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());

                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);
                            if ($re && $rexf && $re1 && $re2 && $re5) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['custid'] = intval($re);
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }

                        } else {
                            //无充值无划扣
                            Db::startTrans();
                            try {
                                $re = Db::table('mbs_customers')
                                    ->insertGetId($data);
                                //添加消费
                                $dataxf['firvisit'] = 1;
                                $dataxf['customer_id'] = intval($re);
                                $dataxf['customer_name'] = trim($data['name']);
                                $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'];
//                    var_dump($dataxf);die();
                                $rexf = Db::table('mbs_xiaofei')
                                    ->insertGetId($dataxf);
                                //操作日志
                                $logdata['xf_id'] = $rexf;
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = intval($re);
                                $logdata['logType'] = 1;
                                $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'];
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                $re1 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);

                                if ($re && $rexf && $re1) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['custid'] = intval($re);
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }

                    }
                }
            } else {
                //无消费
                if (intval($data['money']) > 0) {
                    //有充值 新客户无卡
                    if (intval($dataxf['huakou']) > 0) {
                        //消费为0 有充值 有划扣
                        Db::startTrans();
                        try {
                            $cadata['money'] = intval($data['money']);
                            $data['money'] = intval($data['money']) - $dataxf['huakou'];
                            $re = Db::table('mbs_customers')
                                ->insertGetId($data);
                            //建卡
                            $chdata['customer_id'] = intval($re);
                            $chdata['card_no'] = time() . mt_rand(1, 100);;
                            $chdata['phone'] = $data['phone'];
                            $chdata['money'] = $data['money'];
                            $chdata['chain_id'] = $data['chain_id'];
                            $chdata['add_user'] = intval($_SESSION['UID']);
                            $chdata['type'] = 1;
                            $chdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re7 = Db::table('mbs_customer_card')
                                ->insertGetId($chdata);
                            //卡创建充值操作
                            $cadata['card_id'] = intval($re7);
                            $cadata['customer_id'] = intval($re);
                            $cadata['type'] = 1;
                            $cadata['chain_id'] = $data['chain_id'];
                            $cadata['add_user'] = intval($_SESSION['UID']);
                            $cadata['create_time'] = date('Y-m-d h:i:s', time());
                            $re1 = Db::table('mbs_caozuocard')
                                ->insertGetId($cadata);

                            //添加充值日志
                            $logcadata['xf_id'] = intval($re1);
                            $logcadata['user_id'] = $_SESSION['UID'];
                            $logcadata['customer_id'] = intval($re);
                            $logcadata['logType'] = 5;
                            $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                            $logcadata['money'] = intval($cadata['money']);
                            $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                            $re5 = Db::table('mbs_xiaofei_log')
                                ->insert($logcadata);
                            //卡划扣操作
                            $cdata['card_id'] = intval($re7);
                            $cdata['customer_id'] = intval($re);
                            $cdata['type'] = 2;
                            $cdata['chain_id'] = $data['chain_id'];
                            $cdata['money'] = intval($dataxf['huakou']);
                            $cdata['add_user'] = intval($_SESSION['UID']);
                            $cdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re3 = Db::table('mbs_caozuocard')
                                ->insertGetId($cdata);

                            //添加卡划扣值日志
                            $loghkdata['xf_id'] = intval($re3);
                            $loghkdata['user_id'] = $_SESSION['UID'];
                            $loghkdata['customer_id'] = intval($re);
                            $loghkdata['logType'] = 7;
                            $loghkdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                            $loghkdata['money'] = $dataxf['huakou'];
                            $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                            $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re4 = Db::table('mbs_xiaofei_log')
                                ->insert($loghkdata);

                            //添加消费
                            $dataxf['firvisit'] = 1;
                            $dataxf['card_id'] = intval($re7);
                            $dataxf['cz_caozuoid'] = intval($re1);
                            $dataxf['customer_id'] = intval($re);
                            $dataxf['customer_name'] = trim($data['name']);
                            $dataxf['create_time'] = date('Y-m-d H:m:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
//                    var_dump($dataxf);die();
                            $rexf = Db::table('mbs_xiaofei')
                                ->insertGetId($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($rexf);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = intval($re);
                            $logdata['logType'] = 1;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());

                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);
                            if ($re && $re7 && $re1 && $re5 && $rexf && $re2 && $re3 && $re4) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['custid'] = intval($re);
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }

                    } else {
                        //无划扣
                        Db::startTrans();
                        try {
                            $re = Db::table('mbs_customers')
                                ->insertGetId($data);
                            //建卡
                            $chdata['customer_id'] = intval($re);
                            $chdata['card_no'] = time() . mt_rand(1, 100);;
                            $chdata['phone'] = $data['phone'];
                            $chdata['money'] = $data['money'];
                            $chdata['chain_id'] = $data['chain_id'];
                            $chdata['add_user'] = intval($_SESSION['UID']);
                            $chdata['type'] = 1;
                            $chdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re7 = Db::table('mbs_customer_card')
                                ->insertGetId($chdata);
                            //卡创建充值操作
                            $cadata['card_id'] = intval($re7);
                            $cadata['customer_id'] = intval($re);
                            $cadata['type'] = 1;
                            $cadata['money'] = $data['money'];
                            $cadata['chain_id'] = $data['chain_id'];
                            $cadata['add_user'] = intval($_SESSION['UID']);
                            $cadata['create_time'] = date('Y-m-d h:i:s', time());
                            $re1 = Db::table('mbs_caozuocard')
                                ->insertGetId($cadata);

                            //添加充值日志
                            $logcadata['xf_id'] = intval($re1);
                            $logcadata['user_id'] = $_SESSION['UID'];
                            $logcadata['customer_id'] = intval($re);
                            $logcadata['logType'] = 5;
                            $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                            $logcadata['money'] = intval($cadata['money']);
                            $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                            $re5 = Db::table('mbs_xiaofei_log')
                                ->insert($logcadata);
                            if ($re && $re7 && $re1 && $re5) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['custid'] = intval($re);
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }

                    }

                } else {
                    //无充值
                    //创建客户
                    $re = Db::table('mbs_customers')
                        ->insertGetId($data);
                    if ($re) {
                        $data['code'] = 200;
                        $data['custid'] = intval($re);
                        $data['msg'] = '修改成功';
                        return json_encode($data);
                    } else {
                        $data['code'] = 0;
                        $data['msg'] = '无更改';
                        return json_encode($data);
                    }
                }
            }


        }
    }

    public function saveeditcustxf()
    {
        //客户信息
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
        $data['source_id'] = $new['sourceid'];
        $data['source_from'] = $new['sourcefrom'];
        $data['chain_id'] = $new['chainid'];
        $data['money'] = $new['money'];

        $rule = '/^0?(13|14|15|17|18)[0-9]{9}$/';
        $result = preg_match($rule, $data['phone']);
        if (!$result) {
            $data['msg'] = "手机号码有误";
            $data['status'] = 0;
            return json_encode($data);
        }
        if (empty($data['chain_id'])) {
            $data['msg'] = "门店不能为空";
            $data['status'] = 0;
            return json_encode($data);
        }
        //消费信息
        $user_info_str = array_filter(explode(',', $_POST['xfinfo']));
        $dataxf = array();

        foreach ($user_info_str as $k => $v) {
            $dataxf[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        $dataxf['reception_id'] = intval($dataxf['reception_id']);
        $xiaofeiinfo = Db::table('mbs_xiaofei')
            ->where('xiaofei_id', intval($_GET['id']))
            ->where('status', 1)
            ->find();
        if (empty($xiaofeiinfo)) {
            $data['code'] = 0;
            $data['msg'] = '操作对象为空';
            return json_encode($data);
        }
        $xiaofei_id = intval($_GET['id']);
        $customer_id = intval($xiaofeiinfo['customer_id']);

        //老客户编辑修改消费
        if (intval($dataxf['total_money']) > 0) {
            //有消费
            $data['update_time'] = date('Y-m-d h:i:s', time());
            if (intval($data['chain_id']) != intval($dataxf['serchain_id'])) {
                $data['code'] = 0;
                $data['msg'] = '服务门店与客户门店不一致';
                return json_encode($data);
            }
            $if_exist = Db::table('mbs_customers')
                ->where('phone', trim($data['phone']))
                ->where('id!=' . $customer_id)
                ->where('status', 1)
                ->find();
            if (!empty($if_exist)) {
                $data['code'] = 0;
                $data['msg'] = '手机号重复';
                return json_encode($data);
            }
            //是否有卡
            $cardinfo = Db::table('mbs_customer_card')
                ->where('customer_id', $customer_id)
                ->where('status', 1)
                ->find();
            //客户信息
            $custinfo = Db::table('mbs_customers')
                ->where('id', $customer_id)
                ->where('status', 1)
                ->find();
            $oldhuakou = intval($xiaofeiinfo['huakou']);
            if (intval($dataxf['package_id']) == -1) {
                //退费
                //修改客户
                unset($data['money']);
                unset($dataxf['huakou']);
                $re = Db::table('mbs_customers')
                    ->where('id', $customer_id)
                    ->update($data);
                Db::startTrans();
                try {
                    //退费
                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                    $retf = Db::table('mbs_tuifei')
                        ->update($dataxf);
                    //添加操作日志
                    $logdata['xf_id'] = $xiaofei_id;
                    $logdata['user_id'] = $_SESSION['UID'];
                    $logdata['customer_id'] = $customer_id;
                    $logdata['logType'] = 2;
                    $logdata['logContent'] = '修改消费记录，退费金额' . $dataxf['total_money'];
                    $logdata['money'] = $dataxf['total_money'];
                    $logdata['createTime'] = date('Y-m-d H:m:s', time());

                    $re1 = Db::table('mbs_xiaofei_log')
                        ->insert($logdata);
                    if ($retf && $re1) {
                        Db::commit();//提交事务
                        unset($data);
                        $data['code'] = 200;
                        $data['msg'] = '保存成功';
                        return json_encode($data);
                    } else {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }
                } catch (\Exception $e) {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }

            } else if (intval($dataxf['huakou']) > 0) {
                //不是退费    //有划扣
                if (intval($data['chain_id']) != intval($dataxf['serchain_id'])) {
                    $data['code'] = 0;
                    $data['msg'] = '服务门店与客户门店不一致';
                    return json_encode($data);
                }
                if (!empty($cardinfo)) {
                    //有卡
                    //原始余额 =原始划扣+用户余额
                    if ((intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou'])) >= 0) {
                        //修改客户
                        //原始划扣
                        $oldowe = intval($xiaofeiinfo['owe']);
                        if ($oldhuakou != intval($dataxf['huakou'])) {
                            //划扣金额修改 跟之前不一样
                            Db::startTrans();
                            try {
                                if (intval($dataxf['owe']) != $oldowe) {
                                    // 欠款不一致//作废相关还款
                                    $datahk['status'] = 0;
                                    $ifhave = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->where('status', 1)
                                        ->select();

                                    $re0 = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->update($datahk);
                                }

                                $data['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                //更新客户余额
                                $re = Db::table('mbs_customers')
                                    ->where('id', $customer_id)
                                    ->update($data);

                                //修改卡余额
                                $xgdata['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                $re6 = Db::table('mbs_customer_card')
                                    ->where('card_id', intval($cardinfo['card_id']))
                                    ->where('status', 1)
                                    ->update($xgdata);

                                //修改卡划扣操作
                                $cdata['money'] = $dataxf['huakou'];

                                $ifhashuakou = Db::table('mbs_caozuocard')
                                    ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                    ->find();
                                if (!empty($ifhashuakou)) {
                                    $re1 = Db::table('mbs_caozuocard')
                                        ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                        ->update($cdata);
                                    $logdata['xf_id'] = intval($xiaofeiinfo['hk_caozuoid']);
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = $customer_id;
                                    $logdata['logType'] = 8;
                                    $logdata['logContent'] = '修改划扣记录记录，划扣金额' . $dataxf['huakou'] . '，原金额' . $oldhuakou;
                                    $logdata['money'] = $dataxf['huakou'];
                                    $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                    $re4 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);

                                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                } else {
                                    //添加划扣操作
                                    $cdata['card_id'] = intval($xiaofeiinfo['card_id']);
                                    $cdata['customer_id'] = $customer_id;
                                    $cdata['type'] = 2;
                                    $cdata['chain_id'] = $data['chain_id'];
                                    $cdata['money'] = intval($dataxf['huakou']);
                                    $cdata['add_user'] = intval($_SESSION['UID']);
                                    $cdata['create_time'] = date('Y-m-d h:i:s', time());
                                    $re3 = Db::table('mbs_caozuocard')
                                        ->insertGetId($cdata);
                                    //添加卡划扣值日志
                                    $logdata['xf_id'] = $re3;
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = $customer_id;
                                    $logdata['logType'] = 7;
                                    $logdata['logContent'] = '修改划扣记录记录，划扣金额' . $dataxf['huakou'] . '，原金额' . $oldhuakou;
                                    $logdata['money'] = $dataxf['huakou'];
                                    $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                    $re4 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);
                                    $dataxf['hk_caozuoid'] = intval($re3);
                                    $dataxf['huakou'] = $dataxf['huakou'];
                                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                }
                                //修改消费
                                $rexf = Db::table('mbs_xiaofei')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->where('status', 1)
                                    ->update($dataxf);

                                //添加消费日志
                                $logdata['xf_id'] = intval($rexf);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = $customer_id;
                                $logdata['logType'] = 3;
                                $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']) . '，原划扣金额' . $oldhuakou;
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if (!empty($ifhashuakou)) {
                                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                        if ($re && $rexf && $re1 && $re2 && $re4 && $re6 && $re0) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    } else {
                                        if ($re && $rexf && $re1 && $re2 && $re4 && $re6) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    }
                                } else {
                                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {

                                        if ($re && $rexf && $re3 && $re2 && $re4 && $re6) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }

                                    } else {
                                        if ($re && $rexf && $re3 && $re2 && $re4 && $re6) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } else {
                            //划扣金额无修改 跟之前一样
                            //修改客户
                            if ($customer_id > 0) {
                                $customer = Db::table('mbs_customers')
                                    ->where('id', $customer_id)
                                    ->find();
                            } else {
                                $data['code'] = 0;
                                $data['msg'] = '操作失败';
                                return json_encode($data);
                            }
                            $re = Db::table('mbs_customers')
                                ->where('id', $customer_id)
                                ->update($data);

                            if ($customer) {
                                $dataxf['fromway_id'] = $customer['source_id'];
                            }
                            //消费
                            Db::startTrans();
                            try {

                                if (intval($dataxf['owe']) != $oldowe) {
                                    // 欠款不一致//作废相关还款
                                    $datahk['status'] = 0;
                                    $ifhave = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->where('status', 1)
                                        ->select();

                                    $re0 = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->update($datahk);
                                }

                                //修改消费
                                $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                $rexf = Db::table('mbs_xiaofei')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->update($dataxf);

                                //操作日志
                                $logdata['xf_id'] = $xiaofei_id;
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = $customer_id;
                                $logdata['logType'] = 3;
                                $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'];
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                $re1 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                    if ($re && $re1 && $re0) {
                                        Db::commit();//提交事务
                                        unset($data);
                                        $data['code'] = 200;
                                        $data['msg'] = '保存成功';
                                        return json_encode($data);
                                    } else {
                                        Db::rollback();//回滚事务
                                        unset($data);
                                        $data['code'] = 0;
                                        $data['msg'] = '保存失败';
                                        return json_encode($data);
                                    }
                                } else {
                                    if ($rexf && $re1) {
                                        Db::commit();//提交事务
                                        unset($data);
                                        $data['code'] = 200;
                                        $data['msg'] = '保存成功';
                                        return json_encode($data);
                                    } else {
                                        Db::rollback();//回滚事务
                                        unset($data);
                                        $data['code'] = 0;
                                        $data['msg'] = '保存失败';
                                        return json_encode($data);
                                    }
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }

                    } else {
                        $data['code'] = 0;
                        $data['msg'] = '修改划扣不能大于原始卡内余额';
                        return json_encode($data);
                    }
                } else {
                    //有消费 有划扣 没有卡 先建卡
                    Db::startTrans();
                    try {
                        $oldowe = intval($xiaofeiinfo['owe']);
                        if (intval($dataxf['owe']) != $oldowe) {
                            // 欠款不一致//作废相关还款
                            $datahk['status'] = 0;
                            $ifhave = Db::table('mbs_xfhuankuan')
                                ->where('xiaofei_id', $xiaofei_id)
                                ->where('status', 1)
                                ->select();

                            $re0 = Db::table('mbs_xfhuankuan')
                                ->where('xiaofei_id', $xiaofei_id)
                                ->update($datahk);
                        }
                        $cadata['money'] = intval($data['money']); //充值金额
                        $data['money'] = intval($data['money']) - intval($dataxf['huakou']);
                        $re = Db::table('mbs_customers')
                            ->where('id', $customer_id)
                            ->update($data);
                        //建卡
                        $chdata['customer_id'] = $customer_id;
                        $chdata['card_no'] = time() . mt_rand(1, 100);;
                        $chdata['phone'] = $data['phone'];
                        $chdata['money'] = $data['money'];
                        $chdata['chain_id'] = $data['chain_id'];
                        $chdata['add_user'] = intval($_SESSION['UID']);
                        $chdata['type'] = 1;
                        $chdata['create_time'] = date('Y-m-d h:i:s', time());
                        $re7 = Db::table('mbs_customer_card')
                            ->insertGetId($chdata);
                        //卡创建充值操作
                        $cadata['card_id'] = intval($re7);
                        $cadata['customer_id'] = $customer_id;
                        $cadata['type'] = 1;
                        $cadata['chain_id'] = $data['chain_id'];
                        $cadata['add_user'] = intval($_SESSION['UID']);
                        $cadata['create_time'] = date('Y-m-d h:i:s', time());
                        $re1 = Db::table('mbs_caozuocard')
                            ->insertGetId($cadata);

                        //添加充值日志
                        $logcadata['xf_id'] = intval($re1);
                        $logcadata['user_id'] = $_SESSION['UID'];
                        $logcadata['customer_id'] = $customer_id;
                        $logcadata['logType'] = 5;
                        $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                        $logcadata['money'] = intval($cadata['money']);
                        $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                        $re5 = Db::table('mbs_xiaofei_log')
                            ->insert($logcadata);

                        //卡划扣操作
                        $cdata['card_id'] = intval($re7);
                        $cdata['customer_id'] = $customer_id;
                        $cdata['type'] = 2;
                        $cdata['chain_id'] = $data['chain_id'];
                        $cdata['money'] = intval($dataxf['huakou']);
                        $cdata['add_user'] = intval($_SESSION['UID']);
                        $cdata['create_time'] = date('Y-m-d h:i:s', time());
                        $re3 = Db::table('mbs_caozuocard')
                            ->insertGetId($cdata);

                        //添加卡划扣值日志
                        $loghkdata['xf_id'] = intval($re3);
                        $loghkdata['user_id'] = $_SESSION['UID'];
                        $loghkdata['customer_id'] = $customer_id;
                        $loghkdata['logType'] = 7;
                        $loghkdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                        $loghkdata['money'] = $dataxf['huakou'];
                        $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                        $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                        $re4 = Db::table('mbs_xiaofei_log')
                            ->insert($loghkdata);

                        //修改消费
                        $dataxf['card_id'] = intval($re7);
                        $dataxf['cz_caozuoid'] = intval($re1);
                        $dataxf['hk_caozuoid'] = intval($re3);
                        $dataxf['huakou'] = $dataxf['huakou'];
                        $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                        $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                        $rexf = Db::table('mbs_xiaofei')
                            ->where('xiaofei_id', $xiaofei_id)
                            ->update($dataxf);

                        //添加消费日志
                        $logdata['xf_id'] = intval($rexf);
                        $logdata['user_id'] = $_SESSION['UID'];
                        $logdata['customer_id'] = $customer_id;
                        $logdata['logType'] = 1;
                        $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                        $logdata['money'] = $dataxf['total_money'];
                        $logdata['createTime'] = date('Y-m-d H:m:s', time());
                        $re2 = Db::table('mbs_xiaofei_log')
                            ->insert($logdata);
                        if (intval($dataxf['owe']) != $oldowe) {
                            if (($re0 && $re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5)) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } else {
                            if (($re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5)) {
                                Db::commit();//提交事务
                                unset($data);
                                $data['code'] = 200;
                                $data['msg'] = '保存成功';
                                return json_encode($data);
                            } else {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }

                    } catch (\Exception $e) {
                        Db::rollback();//回滚事务
                        unset($data);
                        $data['code'] = 0;
                        $data['msg'] = '保存失败';
                        return json_encode($data);
                    }
                }
            } else {
                if (intval($data['money']) > 0) {
                    //无划扣 有充值
                    if (!empty($cardinfo)){
                        //有卡
                        if ((intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou'])) >= 0) {
                            //修改客户
                            //原始划扣
                            $oldowe = intval($xiaofeiinfo['owe']);
                            if ($oldhuakou != intval($dataxf['huakou'])) {
                                //划扣金额修改 跟之前不一样
                                Db::startTrans();
                                try {
                                    if (intval($dataxf['owe']) != $oldowe) {
                                        // 欠款不一致//作废相关还款
                                        $datahk['status'] = 0;
                                        $ifhave = Db::table('mbs_xfhuankuan')
                                            ->where('xiaofei_id', $xiaofei_id)
                                            ->where('status', 1)
                                            ->select();

                                        $re0 = Db::table('mbs_xfhuankuan')
                                            ->where('xiaofei_id', $xiaofei_id)
                                            ->update($datahk);
                                    }

                                    $data['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                    //更新客户余额
                                    $re = Db::table('mbs_customers')
                                        ->where('id', $customer_id)
                                        ->update($data);

                                    //修改卡余额
                                    $xgdata['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                    $re6 = Db::table('mbs_customer_card')
                                        ->where('card_id', intval($cardinfo['card_id']))
                                        ->where('status', 1)
                                        ->update($xgdata);

                                    //修改卡划扣操作
                                    $cdata['money'] = $dataxf['huakou'];

                                    $ifhashuakou = Db::table('mbs_caozuocard')
                                        ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                        ->find();
                                    if (!empty($ifhashuakou)) {
                                        $re1 = Db::table('mbs_caozuocard')
                                            ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                            ->update($cdata);
                                        $logdata['xf_id'] = intval($xiaofeiinfo['hk_caozuoid']);
                                        $logdata['user_id'] = $_SESSION['UID'];
                                        $logdata['customer_id'] = $customer_id;
                                        $logdata['logType'] = 8;
                                        $logdata['logContent'] = '修改划扣记录记录，划扣金额' . $dataxf['huakou'] . '，原金额' . $oldhuakou;
                                        $logdata['money'] = $dataxf['huakou'];
                                        $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                        $re4 = Db::table('mbs_xiaofei_log')
                                            ->insert($logdata);

                                        $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                        $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                    }
                                    //修改消费
                                    $rexf = Db::table('mbs_xiaofei')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->where('status', 1)
                                        ->update($dataxf);

                                    //添加消费日志
                                    $logdata['xf_id'] = intval($rexf);
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = $customer_id;
                                    $logdata['logType'] = 3;
                                    $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']) . '，原划扣金额' . $oldhuakou;
                                    $logdata['money'] = $dataxf['total_money'];
                                    $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                    $re2 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);
                                    if (!empty($ifhashuakou)) {
                                        if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                            if ($re && $rexf && $re1 && $re2 && $re4 && $re6 && $re0) {
                                                Db::commit();//提交事务
                                                unset($data);
                                                $data['code'] = 200;
                                                $data['msg'] = '保存成功';
                                                return json_encode($data);
                                            } else {
                                                Db::rollback();//回滚事务
                                                unset($data);
                                                $data['code'] = 0;
                                                $data['msg'] = '保存失败';
                                                return json_encode($data);
                                            }
                                        } else {
                                            if ($re && $rexf && $re1 && $re2 && $re4 && $re6) {
                                                Db::commit();//提交事务
                                                unset($data);
                                                $data['code'] = 200;
                                                $data['msg'] = '保存成功';
                                                return json_encode($data);
                                            } else {
                                                Db::rollback();//回滚事务
                                                unset($data);
                                                $data['code'] = 0;
                                                $data['msg'] = '保存失败';
                                                return json_encode($data);
                                            }
                                        }
                                    } else {
                                        if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {

                                            if ($re && $rexf  && $re2 && $re4 && $re6 && $re0) {
                                                Db::commit();//提交事务
                                                unset($data);
                                                $data['code'] = 200;
                                                $data['msg'] = '保存成功';
                                                return json_encode($data);
                                            } else {
                                                Db::rollback();//回滚事务
                                                unset($data);
                                                $data['code'] = 0;
                                                $data['msg'] = '保存失败';
                                                return json_encode($data);
                                            }

                                        } else {
                                            if ($re && $rexf && $re2 && $re4 && $re6) {
                                                Db::commit();//提交事务
                                                unset($data);
                                                $data['code'] = 200;
                                                $data['msg'] = '保存成功';
                                                return json_encode($data);
                                            } else {
                                                Db::rollback();//回滚事务
                                                unset($data);
                                                $data['code'] = 0;
                                                $data['msg'] = '保存失败';
                                                return json_encode($data);
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } else {
                                //划扣金额无修改 跟之前一样
                                //修改客户
                                if ($customer_id > 0) {
                                    $customer = Db::table('mbs_customers')
                                        ->where('id', $customer_id)
                                        ->find();
                                } else {
                                    $data['code'] = 0;
                                    $data['msg'] = '操作失败';
                                    return json_encode($data);
                                }
                                $re = Db::table('mbs_customers')
                                    ->where('id', $customer_id)
                                    ->update($data);

                                if ($customer) {
                                    $dataxf['fromway_id'] = $customer['source_id'];
                                }
                                //消费
                                Db::startTrans();
                                try {

                                    if (intval($dataxf['owe']) != $oldowe) {
                                        // 欠款不一致//作废相关还款
                                        $datahk['status'] = 0;
                                        $ifhave = Db::table('mbs_xfhuankuan')
                                            ->where('xiaofei_id', $xiaofei_id)
                                            ->where('status', 1)
                                            ->select();

                                        $re0 = Db::table('mbs_xfhuankuan')
                                            ->where('xiaofei_id', $xiaofei_id)
                                            ->update($datahk);
                                    }

                                    //修改消费
                                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                    $rexf = Db::table('mbs_xiaofei')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->update($dataxf);

                                    //操作日志
                                    $logdata['xf_id'] = $xiaofei_id;
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = $customer_id;
                                    $logdata['logType'] = 3;
                                    $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'];
                                    $logdata['money'] = $dataxf['total_money'];
                                    $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                    $re1 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);
                                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                        if ($re && $re1 && $re0) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    } else {
                                        if ($rexf && $re1) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            }

                        } else {
                            $data['code'] = 0;
                            $data['msg'] = '修改划扣不能大于原始卡内余额';
                            return json_encode($data);
                        }

                    }else{
                        //无卡
                        Db::startTrans();
                        try {
                            $oldowe = intval($xiaofeiinfo['owe']);
                            if (intval($dataxf['owe']) != $oldowe) {
                                // 欠款不一致//作废相关还款
                                $datahk['status'] = 0;
                                $ifhave = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->where('status', 1)
                                    ->select();

                                $re0 = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->update($datahk);
                            }
                            //修改客户
                            $re = Db::table('mbs_customers')
                                ->where('id', $customer_id)
                                ->update($data);
                            //建卡
                            $chdata['customer_id'] = $customer_id;
                            $chdata['card_no'] = time() . mt_rand(1, 100);;
                            $chdata['phone'] = $data['phone'];
                            $chdata['money'] = $data['money'];
                            $chdata['chain_id'] = $data['chain_id'];
                            $chdata['add_user'] = intval($_SESSION['UID']);
                            $chdata['type'] = 1;
                            $chdata['create_time'] = date('Y-m-d h:i:s', time());
                            $re7 = Db::table('mbs_customer_card')
                                ->insertGetId($chdata);
                            //卡创建充值操作
                            $cadata['card_id'] = intval($re7);
                            $cadata['customer_id'] = $customer_id;
                            $cadata['type'] = 1;
                            $cadata['money'] = $data['money'];
                            $cadata['chain_id'] = $data['chain_id'];
                            $cadata['add_user'] = intval($_SESSION['UID']);
                            $cadata['create_time'] = date('Y-m-d h:i:s', time());
                            $re1 = Db::table('mbs_caozuocard')
                                ->insertGetId($cadata);

                            //添加充值日志
                            $logcadata['xf_id'] = intval($re1);
                            $logcadata['user_id'] = $_SESSION['UID'];
                            $logcadata['customer_id'] = $customer_id;
                            $logcadata['logType'] = 5;
                            $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                            $logcadata['money'] = intval($cadata['money']);
                            $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                            $re5 = Db::table('mbs_xiaofei_log')
                                ->insert($logcadata);

                            $dataxf['card_id'] = intval($re7);
                            $dataxf['cz_caozuoid'] = intval($re1);
                            $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                            $rexf = Db::table('mbs_xiaofei')
                                ->where('xiaofei_id', $xiaofei_id)
                                ->update($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($xiaofei_id);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = $customer_id;
                            $logdata['logType'] = 5;
                            $logdata['logContent'] = '修改消费中充值，充值金额' . $cadata['money'];
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);

                            if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                if ($re && $re7 && $re1 && $re5 && $re0) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } else {
                                if ($re && $re7 && $re1 && $re5) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    }


                } else {
                    //没有划扣 无充值
                    if ((intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou'])) >= 0) {
                        //修改客户
                        //原始划扣
                        $oldowe = intval($xiaofeiinfo['owe']);
                        if ($oldhuakou != intval($dataxf['huakou'])) {
                            //划扣金额修改 跟之前不一样
                            Db::startTrans();
                            try {
                                if (intval($dataxf['owe']) != $oldowe) {
                                    // 欠款不一致//作废相关还款
                                    $datahk['status'] = 0;
                                    $ifhave = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->where('status', 1)
                                        ->select();

                                    $re0 = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->update($datahk);
                                }

                                $data['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                //更新客户余额
                                $re = Db::table('mbs_customers')
                                    ->where('id', $customer_id)
                                    ->update($data);

                                //修改卡余额
                                $xgdata['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                                $re6 = Db::table('mbs_customer_card')
                                    ->where('card_id', intval($cardinfo['card_id']))
                                    ->where('status', 1)
                                    ->update($xgdata);

                                //修改卡划扣操作
                                $cdata['money'] = $dataxf['huakou'];

                                $ifhashuakou = Db::table('mbs_caozuocard')
                                    ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                    ->find();
                                if (!empty($ifhashuakou)) {
                                    $re1 = Db::table('mbs_caozuocard')
                                        ->where('caozuo_id', intval($xiaofeiinfo['hk_caozuoid']))
                                        ->update($cdata);
                                    $logdata['xf_id'] = intval($xiaofeiinfo['hk_caozuoid']);
                                    $logdata['user_id'] = $_SESSION['UID'];
                                    $logdata['customer_id'] = $customer_id;
                                    $logdata['logType'] = 8;
                                    $logdata['logContent'] = '修改划扣记录记录，划扣金额' . $dataxf['huakou'] . '，原金额' . $oldhuakou;
                                    $logdata['money'] = $dataxf['huakou'];
                                    $logdata['createTime'] = date('Y-m-d H:i:s', time());
                                    $re4 = Db::table('mbs_xiaofei_log')
                                        ->insert($logdata);

                                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                }
                                //修改消费
                                $rexf = Db::table('mbs_xiaofei')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->where('status', 1)
                                    ->update($dataxf);

                                //添加消费日志
                                $logdata['xf_id'] = intval($rexf);
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = $customer_id;
                                $logdata['logType'] = 3;
                                $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']) . '，原划扣金额' . $oldhuakou;
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:m:s', time());

                                $re2 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if (!empty($ifhashuakou)) {
                                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                        if ($re && $rexf && $re1 && $re2 && $re4 && $re6 && $re0) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    } else {
                                        if ($re && $rexf && $re1 && $re2 && $re4 && $re6) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    }
                                } else {
                                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {

                                        if ($re && $rexf  && $re2 && $re4 && $re6 && $re0) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }

                                    } else {
                                        if ($re && $rexf && $re2 && $re4 && $re6) {
                                            Db::commit();//提交事务
                                            unset($data);
                                            $data['code'] = 200;
                                            $data['msg'] = '保存成功';
                                            return json_encode($data);
                                        } else {
                                            Db::rollback();//回滚事务
                                            unset($data);
                                            $data['code'] = 0;
                                            $data['msg'] = '保存失败';
                                            return json_encode($data);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        } else {
                            //划扣金额无修改 跟之前一样
                            //修改客户
                            if ($customer_id > 0) {
                                $customer = Db::table('mbs_customers')
                                    ->where('id', $customer_id)
                                    ->find();
                            } else {
                                $data['code'] = 0;
                                $data['msg'] = '操作失败';
                                return json_encode($data);
                            }
                            $re = Db::table('mbs_customers')
                                ->where('id', $customer_id)
                                ->update($data);

                            if ($customer) {
                                $dataxf['fromway_id'] = $customer['source_id'];
                            }
                            //消费
                            Db::startTrans();
                            try {

                                if (intval($dataxf['owe']) != $oldowe) {
                                    // 欠款不一致//作废相关还款
                                    $datahk['status'] = 0;
                                    $ifhave = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->where('status', 1)
                                        ->select();

                                    $re0 = Db::table('mbs_xfhuankuan')
                                        ->where('xiaofei_id', $xiaofei_id)
                                        ->update($datahk);
                                }

                                //修改消费
                                $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                                $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                                $rexf = Db::table('mbs_xiaofei')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->update($dataxf);

                                //操作日志
                                $logdata['xf_id'] = $xiaofei_id;
                                $logdata['user_id'] = $_SESSION['UID'];
                                $logdata['customer_id'] = $customer_id;
                                $logdata['logType'] = 3;
                                $logdata['logContent'] = '修改消费记录，消费金额' . $dataxf['total_money'];
                                $logdata['money'] = $dataxf['total_money'];
                                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                                $re1 = Db::table('mbs_xiaofei_log')
                                    ->insert($logdata);
                                if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                    if ($re && $re1 && $re0) {
                                        Db::commit();//提交事务
                                        unset($data);
                                        $data['code'] = 200;
                                        $data['msg'] = '保存成功';
                                        return json_encode($data);
                                    } else {
                                        Db::rollback();//回滚事务
                                        unset($data);
                                        $data['code'] = 0;
                                        $data['msg'] = '保存失败';
                                        return json_encode($data);
                                    }
                                } else {
                                    if ($rexf && $re1) {
                                        Db::commit();//提交事务
                                        unset($data);
                                        $data['code'] = 200;
                                        $data['msg'] = '保存成功';
                                        return json_encode($data);
                                    } else {
                                        Db::rollback();//回滚事务
                                        unset($data);
                                        $data['code'] = 0;
                                        $data['msg'] = '保存失败';
                                        return json_encode($data);
                                    }
                                }
                            } catch (\Exception $e) {
                                Db::rollback();//回滚事务
                                unset($data);
                                $data['code'] = 0;
                                $data['msg'] = '保存失败';
                                return json_encode($data);
                            }
                        }

                    } else {
                        $data['code'] = 0;
                        $data['msg'] = '修改划扣不能大于原始卡内余额';
                        return json_encode($data);
                    }
                }
            }
        } else if (intval($dataxf['huakou']) > 0) {
            //新消费为0
            $custinfo = Db::table('mbs_customers')
                ->where('id', $customer_id)
                ->where('status', 1)
                ->find();
            $oldhuakou = intval($xiaofeiinfo['huakou']);
            $cardinfo = Db::table('mbs_customer_card')
                ->where('customer_id', $customer_id)
                ->where('status', 1)
                ->find();
            if (!empty($cardinfo)) {
                //有卡
                if ((intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou'])) >= 0) {
                    //消费金额为0  划扣金额不为零 卡内余额为现有余额加上旧的划扣减去新划扣
                    if (intval($dataxf['huakou']) == $oldhuakou) {
                        //用户可能无更改 余额不需要变动
                        $re = Db::table('mbs_customers')
                            ->where('id', $customer_id)
                            ->update($data);
                        Db::startTrans();
                        try {
                            $oldowe = intval($xiaofeiinfo['owe']);
                            if (intval($dataxf['owe']) != $oldowe) {
                                // 欠款不一致//作废相关还款
                                $datahk['status'] = 0;
                                $ifhave = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->where('status', 1)
                                    ->select();

                                $re0 = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->update($datahk);
                            }
                            $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                            $rexf = Db::table('mbs_xiaofei')
                                ->where('xiaofei_id', $xiaofei_id)
                                ->update($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($rexf);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = $customer_id;
                            $logdata['logType'] = 3;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);
                            if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                if ($re0 && $re2 && $rexf) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } else {
                                if ($re2 && $rexf) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            }

                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }

                    } else {
                        //两次划扣金额不一致
                        Db::startTrans();
                        try {
                            $oldowe = intval($xiaofeiinfo['owe']);
                            if (intval($dataxf['owe']) != $oldowe) {
                                // 欠款不一致//作废相关还款
                                $datahk['status'] = 0;
                                $ifhave = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->where('status', 1)
                                    ->select();

                                $re0 = Db::table('mbs_xfhuankuan')
                                    ->where('xiaofei_id', $xiaofei_id)
                                    ->update($datahk);
                            }

                            $data['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                            $re = Db::table('mbs_customers')
                                ->where('id', $customer_id)
                                ->update($data);
                            //修改卡
                            $xgdata['money'] = intval($custinfo['money']) + $oldhuakou - intval($dataxf['huakou']);
                            $re6 = Db::table('mbs_customer_card')
                                ->where('card_id', intval($cardinfo['card_id']))
                                ->where('status', 1)
                                ->update($xgdata);
                            //修改卡划扣操作
                            $cdata['money'] = intval($dataxf['huakou']);
                            $re3 = Db::table('mbs_caozuocard')
                                ->where('caozuo_id', intval($cardinfo['hk_caozuoid']))
                                ->where('status', 1)
                                ->update($cdata);

                            //添加卡划扣值日志
                            $loghkdata['xf_id'] = intval($re3);
                            $loghkdata['user_id'] = $_SESSION['UID'];
                            $loghkdata['customer_id'] = $customer_id;
                            $loghkdata['logType'] = 8;
                            $loghkdata['logContent'] = '修改划扣记录记录，划扣金额' . $dataxf['huakou'];
                            $loghkdata['money'] = $dataxf['huakou'];
                            $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                            $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re4 = Db::table('mbs_xiaofei_log')
                                ->insert($loghkdata);

                            //修改消费
                            $dataxf['huakou'] = $dataxf['huakou'];
                            $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                            $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                            $rexf = Db::table('mbs_xiaofei')
                                ->where('xiaofei_id', $xiaofei_id)
                                ->update($dataxf);

                            //添加消费日志
                            $logdata['xf_id'] = intval($rexf);
                            $logdata['user_id'] = $_SESSION['UID'];
                            $logdata['customer_id'] = $customer_id;
                            $logdata['logType'] = 3;
                            $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                            $logdata['money'] = $dataxf['total_money'];
                            $logdata['createTime'] = date('Y-m-d H:m:s', time());
                            $re2 = Db::table('mbs_xiaofei_log')
                                ->insert($logdata);
                            if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                                if ($re0 && $re && $re2 && $re3 && $re4 && $re6 && $rexf) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            } else {
                                if ($re && $re2 && $re3 && $re4 && $re6 && $rexf) {
                                    Db::commit();//提交事务
                                    unset($data);
                                    $data['code'] = 200;
                                    $data['msg'] = '保存成功';
                                    return json_encode($data);
                                } else {
                                    Db::rollback();//回滚事务
                                    unset($data);
                                    $data['code'] = 0;
                                    $data['msg'] = '保存失败';
                                    return json_encode($data);
                                }
                            }
                        } catch (\Exception $e) {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    }

                } else {
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '划扣金额不能大于原始卡内余额';
                    return json_encode($data);
                }
            } else {
                //无卡 消费为0 划扣不为0
                Db::startTrans();
                try {
                    $oldowe = intval($xiaofeiinfo['owe']);
                    if (intval($dataxf['owe']) != $oldowe) {
                        // 欠款不一致//作废相关还款
                        $datahk['status'] = 0;
                        $ifhave = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $xiaofei_id)
                            ->where('status', 1)
                            ->select();

                        $re0 = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $xiaofei_id)
                            ->update($datahk);
                    }
                    $cadata['money'] = intval($data['money']); //充值金额
                    $data['money'] = intval($data['money']) - intval($dataxf['huakou']);
                    $re = Db::table('mbs_customers')
                        ->where('id', $customer_id)
                        ->update($data);
                    //建卡
                    $chdata['customer_id'] = $customer_id;
                    $chdata['card_no'] = time() . mt_rand(1, 100);;
                    $chdata['phone'] = $data['phone'];
                    $chdata['money'] = $data['money'];
                    $chdata['chain_id'] = $data['chain_id'];
                    $chdata['add_user'] = intval($_SESSION['UID']);
                    $chdata['type'] = 1;
                    $chdata['create_time'] = date('Y-m-d h:i:s', time());
                    $re7 = Db::table('mbs_customer_card')
                        ->insertGetId($chdata);
                    //卡创建充值操作
                    $cadata['card_id'] = intval($re7);
                    $cadata['customer_id'] = $customer_id;
                    $cadata['type'] = 1;
                    $cadata['chain_id'] = $data['chain_id'];
                    $cadata['add_user'] = intval($_SESSION['UID']);
                    $cadata['create_time'] = date('Y-m-d h:i:s', time());
                    $re1 = Db::table('mbs_caozuocard')
                        ->insertGetId($cadata);

                    //添加充值日志
                    $logcadata['xf_id'] = intval($re1);
                    $logcadata['user_id'] = $_SESSION['UID'];
                    $logcadata['customer_id'] = $customer_id;
                    $logcadata['logType'] = 5;
                    $logcadata['logContent'] = '添加消费充值记录，充值金额' . intval($cadata['money']);
                    $logcadata['money'] = intval($cadata['money']);
                    $logcadata['createTime'] = date('Y-m-d H:i:s', time());
                    $re5 = Db::table('mbs_xiaofei_log')
                        ->insert($logcadata);

                    //卡划扣操作
                    $cdata['card_id'] = intval($re7);
                    $cdata['customer_id'] = $customer_id;
                    $cdata['type'] = 2;
                    $cdata['chain_id'] = $data['chain_id'];
                    $cdata['money'] = intval($dataxf['huakou']);
                    $cdata['add_user'] = intval($_SESSION['UID']);
                    $cdata['create_time'] = date('Y-m-d h:i:s', time());
                    $re3 = Db::table('mbs_caozuocard')
                        ->insertGetId($cdata);

                    //添加卡划扣值日志
                    $loghkdata['xf_id'] = intval($re3);
                    $loghkdata['user_id'] = $_SESSION['UID'];
                    $loghkdata['customer_id'] = $customer_id;
                    $loghkdata['logType'] = 7;
                    $loghkdata['logContent'] = '添加划扣记录记录，划扣金额' . $dataxf['huakou'];
                    $loghkdata['money'] = $dataxf['huakou'];
                    $loghkdata['createTime'] = date('Y-m-d H:i:s', time());
                    $loghkdata['createTime'] = date('Y-m-d H:m:s', time());
                    $re4 = Db::table('mbs_xiaofei_log')
                        ->insert($loghkdata);

                    //修改消费
                    $dataxf['card_id'] = intval($re7);
                    $dataxf['cz_caozuoid'] = intval($re1);
                    $dataxf['hk_caozuoid'] = intval($re3);
                    $dataxf['huakou'] = $dataxf['huakou'];
                    $dataxf['update_time'] = date('Y-m-d H:i:s', time());
                    $dataxf['kaifa_money'] = $dataxf['total_money'] + $dataxf['owe'] + intval($dataxf['huakou']);
                    $rexf = Db::table('mbs_xiaofei')
                        ->where('xiaofei_id', $xiaofei_id)
                        ->update($dataxf);

                    //添加消费日志
                    $logdata['xf_id'] = intval($rexf);
                    $logdata['user_id'] = $_SESSION['UID'];
                    $logdata['customer_id'] = $customer_id;
                    $logdata['logType'] = 1;
                    $logdata['logContent'] = '添加消费记录，消费金额' . $dataxf['total_money'] . ',划扣金额为' . intval($dataxf['huakou']);
                    $logdata['money'] = $dataxf['total_money'];
                    $logdata['createTime'] = date('Y-m-d H:m:s', time());
                    $re2 = Db::table('mbs_xiaofei_log')
                        ->insert($logdata);
                    if (intval($dataxf['owe']) != $oldowe && !empty($ifhave)) {
                        if (($re0 && $re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5)) {
                            Db::commit();//提交事务
                            unset($data);
                            $data['code'] = 200;
                            $data['msg'] = '保存成功';
                            return json_encode($data);
                        } else {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    } else {
                        if (($re && $rexf && $re1 && $re2 && $re3 && $re4 && $re5)) {
                            Db::commit();//提交事务
                            unset($data);
                            $data['code'] = 200;
                            $data['msg'] = '保存成功';
                            return json_encode($data);
                        } else {
                            Db::rollback();//回滚事务
                            unset($data);
                            $data['code'] = 0;
                            $data['msg'] = '保存失败';
                            return json_encode($data);
                        }
                    }

                } catch (\Exception $e) {
                    Db::rollback();//回滚事务
                    unset($data);
                    $data['code'] = 0;
                    $data['msg'] = '保存失败';
                    return json_encode($data);
                }

            }

        } else {
            unset($data);
            $data['code'] = 0;
            $data['msg'] = '消费金额为零，划扣为零';
            return json_encode($data);
        }
    }


    public function viewxiaofei()
    {
        $config = config('group');
        $gid = intval($_SESSION['GID']);
        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);

        } else if ($gid == $config['dean']) {
            //院长
            $this->assign('usign', 2);
        } else {
            $this->assign('usign', 3);
        }
        $this->assign('sessiongid', $_SESSION['GID']);

        $customer_id = intval($_GET['id']);
        $xiaofeilist = Db::table('mbs_xiaofei')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->join('mbs_package p', 'a.package_id = p.package_id', 'LEFT')
            ->field('a.*,c.phone,p.package_name')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->order('a.create_time desc')
            ->select();
        $tfxiaofeilist = Db::table('mbs_tuifei')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->join('mbs_package p', 'a.package_id = p.package_id', 'LEFT')
            ->field('a.*,c.phone,p.package_name')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->order('a.create_time desc')
            ->select();
        $this->assign('xiaofeilist', $xiaofeilist);
        $this->assign('tfxiaofeilist', $tfxiaofeilist);

        $payway = Db::table('mbs_payway')
            ->where('status', 1)
            ->where('paytype', 3)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        return $this->view->fetch();
    }

    public function detailinfo()
    {
        $config = config('group');
        $gid = intval($_SESSION['GID']);
        if ($gid == 1 || $gid == $config['chief_dean']) {
            //超级管理员或者总院长，没有分配门店
            //其他门店代收的费用
            $this->assign('usign', 1);

        } else if ($gid == $config['dean']) {
            //院长
            $this->assign('usign', 2);
        } else {
            $this->assign('usign', 3);
        }
        $source = Db::table('mbs_fromway')
            ->where('status', 1)
            ->order('fromway_id asc')
            ->select();
        $this->assign('source', $source);
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chainlist);
        $this->assign('sessiongid', $_SESSION['GID']);
        $customer_id = intval($_GET['id']);
        if ($customer_id > 0) {
            $userinfo = Db::table('mbs_customers')
                ->alias('a')
                ->field('a.*,c.card_no,c.card_id')
                ->join('mbs_customer_card c', 'a.id=c.customer_id', 'left')
                ->where('(a.id= \'' . $customer_id . '\' and c.status is null) or (a.id= \'' . $customer_id . '\' and c.status=1)')
                ->find();
            $this->assign('userinfo', $userinfo);
        }
        $xiaofeilist = Db::table('mbs_xiaofei')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->join('mbs_package p', 'a.package_id = p.package_id', 'LEFT')
            ->field('a.*,c.phone,p.package_name')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->order('a.create_time desc')
            ->select();
        $tfxiaofeilist = Db::table('mbs_tuifei')
            ->alias('a')
            ->join('mbs_customers c', 'a.customer_id = c.id', 'LEFT')
            ->join('mbs_package p', 'a.package_id = p.package_id', 'LEFT')
            ->field('a.*,c.phone,p.package_name')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->order('a.create_time desc')
            ->select();
        $this->assign('xiaofeilist', $xiaofeilist);
        $this->assign('tfxiaofeilist', $tfxiaofeilist);

        $payway = Db::table('mbs_payway')
            ->where('status', 1)
            ->where('paytype', 3)
            ->order('payway_id asc')
            ->select();
        $this->assign('payway', $payway);

        $logcharge = Db::table('mbs_caozuocard')
            ->field('a.*,u.name')
            ->alias('a')
            ->join('mbs_user u', 'a.add_user=u.id')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->where('a.type', 1)
            ->order('a.create_time desc')
            ->select();
        $this->assign('logcharge', $logcharge);
        $loghuakou = Db::table('mbs_caozuocard')
            ->field('a.*,u.name')
            ->alias('a')
            ->join('mbs_user u', 'a.add_user=u.id')
            ->where('a.customer_id', $customer_id)
            ->where('a.status', 1)
            ->where('a.type', 2)
            ->order('a.create_time desc')
            ->select();
        $this->assign('loghuakou', $loghuakou);
        return $this->view->fetch();
    }

    public function delxiaofei()
    {
        $id = intval($_POST['id']);
        if ($id > 0) {

            Db::startTrans();
            try {
                $xiaofeiinfo = Db::table('mbs_xiaofei')
                    ->where('xiaofei_id', $id)
                    ->find();
                if (!empty($xiaofeiinfo)) {
                    if (intval($xiaofeiinfo['huakou'])) {

                        //增加用户余额
                        $reu = Db::table('mbs_customers')
                            ->where('id', intval($xiaofeiinfo['customer_id']))
                            ->setInc('money', intval($xiaofeiinfo['huakou']));
                        //增加卡余额
                        $rec = Db::table('mbs_customer_card')
                            ->where('card_id', intval($xiaofeiinfo['card_id']))
                            ->setInc('money', intval($xiaofeiinfo['huakou']));

                        $re = Db::table('mbs_xiaofei')
                            ->where('xiaofei_id', $id)
                            ->update(['status' => 0]);
                        $ifhave = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $id)
                            ->where('status', 1)
                            ->select();

                        $re0 = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $id)
                            ->update(['status' => 0]);
                        $logdata['xf_id'] = $id;
                        $logdata['user_id'] = $_SESSION['UID'];
                        $logdata['customer_id'] = intval($xiaofeiinfo['customer_id']);
                        $logdata['logType'] = 2;
                        $logdata['logContent'] = '删除消费记录，消费金额' . $xiaofeiinfo['total_money'];
                        $logdata['money'] = $xiaofeiinfo['total_money'];
                        $logdata['createTime'] = date('Y-m-d H:i:s', time());

                        $re1 = Db::table('mbs_xiaofei_log')
                            ->insert($logdata);
                        if ((empty($ifhave) && $re && $reu && $rec && $re1) || (!empty($ifhave) && $re && $reu && $rec && $re0 && $re1)) {
                            Db::commit();
                            $data['code'] = 200;
                            $data['msg'] = "删除成功！";
                            return json_encode($data);
                        } else {
                            Db::rollback();
                            $data['code'] = 1;
                            $data['msg'] = "删除失败！";
                            return json_encode($data);
                        }
                    } else {
                        $re = Db::table('mbs_xiaofei')
                            ->where('xiaofei_id', $id)
                            ->update(['status' => 0]);
                        $ifhave = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $id)
                            ->where('status', 1)
                            ->select();

                        $re0 = Db::table('mbs_xfhuankuan')
                            ->where('xiaofei_id', $id)
                            ->update(['status' => 0]);

                        $logdata['xf_id'] = $id;
                        $logdata['user_id'] = $_SESSION['UID'];
                        $logdata['customer_id'] = intval($xiaofeiinfo['customer_id']);
                        $logdata['logType'] = 2;
                        $logdata['logContent'] = '删除消费记录，消费金额' . $xiaofeiinfo['total_money'];
                        $logdata['money'] = $xiaofeiinfo['total_money'];
                        $logdata['createTime'] = date('Y-m-d H:i:s', time());

                        $re1 = Db::table('mbs_xiaofei_log')
                            ->insert($logdata);
                        if ((empty($ifhave) && $re && $re1) || (!empty($ifhave) && $re && $re0 && $re1)) {
                            Db::commit();
                            $data['code'] = 200;
                            $data['msg'] = "删除成功！";
                            return json_encode($data);
                        } else {
                            Db::rollback();
                            $data['code'] = 1;
                            $data['msg'] = "删除失败！";
                            return json_encode($data);
                        }
                    }

                } else {
                    Db::rollback();
                    $data['code'] = 1;
                    $data['msg'] = "无效的操作！";
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }

        } else {
            $data['code'] = -1;
            $data['msg'] = '没有可操作的对象';
            return json_encode($data);
        }

    }

    public function deltuifei()
    {
        $id = intval($_POST['id']);
        if ($id > 0) {
            $tuifeiinfo = Db::table('mbs_tuifei')
                ->where('tuifei_id', $id)
                ->where('status', 1)
                ->find();
            Db::startTrans();
            try {
                $re = Db::table('mbs_tuifei')
                    ->where('tuifei_id', $id)
                    ->update(['status' => 0]);

                $logdata['xf_id'] = $id;
                $logdata['user_id'] = $_SESSION['UID'];
                $logdata['customer_id'] = intval($tuifeiinfo['customer_id']);
                $logdata['logType'] = 2;
                $logdata['logContent'] = '删除退费记录，消费金额' . $tuifeiinfo['total_money'];
                $logdata['money'] = $tuifeiinfo['total_money'];
                $logdata['createTime'] = date('Y-m-d H:i:s', time());

                $re1 = Db::table('mbs_xiaofei_log')
                    ->insert($logdata);

                if ($re && $re1) {
                    Db::commit();
                    $data['code'] = 200;
                    $data['msg'] = "删除成功！";
                    return json_encode($data);
                } else {
                    Db::rollback();
                    $data['code'] = 1;
                    $data['msg'] = "删除失败！";
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }

        } else {
            $data['code'] = -1;
            $data['msg'] = '没有可操作的对象';
            return json_encode($data);
        }
    }


    public function addclean()
    {
        $id = intval($_POST['xf_id']);
        $money = trim($_POST['owe']);
        $payway_id = trim($_POST['payway_id']);
        if (is_numeric($money)) {
            $xfinfo = Db::table('mbs_xiaofei')
                ->where('xiaofei_id', $id)
                ->find();
            $data['xiaofei_id'] = $id;
            $data['payway_id'] = $payway_id;
            $data['customer_id'] = $xfinfo['customer_id'];
            $data['customer_name'] = $xfinfo['customer_name'];
            $data['create_time'] = date('Y-m-d H:m:s', time());
            if ($money >= $xfinfo['owe']) {
                $data['clean'] = 1;
                $data['owe'] = 0;
                $data['huan_money'] = $money;
                $udata['clean'] = 1;
                $udata['owe'] = 0;
            } else {
                $data['clean'] = 2;
                $data['owe'] = $xfinfo['owe'] - $money;
                $data['huan_money'] = $money;
                $udata['owe'] = $xfinfo['owe'] - $money;
            }
            $logdata['xf_id'] = $id;
            $logdata['user_id'] = $_SESSION['UID'];
            $logdata['customer_id'] = $xfinfo['customer_id'];
            $logdata['logType'] = 4;
            $logdata['logContent'] = '还款' . $money;
            $logdata['money'] = $money;
            $logdata['createTime'] = date('Y-m-d H:m:s', time());

            Db::startTrans();

            $re = Db::table('mbs_xiaofei')
                ->where('xiaofei_id', $id)
                ->update($udata);

            $re1 = Db::table('mbs_xfhuankuan')
                ->insert($data);
            $re2 = Db::table('mbs_xiaofei_log')
                ->insert($logdata);
            if ($re && $re1 && $re2) {
                Db::commit();//提交事务
                $data['code'] = 200;
                $data['msg'] = '操作成功';
                if (($xfinfo['owe'] - $money) > 0) {
                    $data['owe'] = $xfinfo['owe'] - $money;
                } else {
                    $data['owe'] = 0;
                }
                return json_encode($data);
            } else {
                Db::rollback();
                $data['code'] = 0;
                $data['msg'] = '操作失败';
                return json_encode($data);
            }

        } else {
            $data['code'] = 0;
            $data['msg'] = '操作失败';
            return json_encode($data);
        }
    }

    public function payway()
    {
        $type = intval($_POST['type']);
        if ($type == 1) {
            //支付方式
            $paymap['status'] = 1;
            $paymap['paytype'] = 1;
            $payway = Db::table('mbs_payway')
                ->where($paymap)
                ->order('payway_id asc')
                ->select();
            $data['code'] = 200;
            $data['msg'] = 1;
            $data['payway'] = $payway;
            return json_encode($data);
        } else if ($type == 2) {
            $opaymap['status'] = 1;
            $opaymap['paytype'] = 2;
            $opayway = Db::table('mbs_payway')
                ->where($opaymap)
                ->order('payway_id asc')
                ->select();
            $data['code'] = 200;
            $data['msg'] = 2;
            $data['payway'] = $opayway;
            return json_encode($data);
        } else {
            $data['code'] = 0;
            $data['msg'] = '操作失败';
            return json_encode($data);
        }
    }

    public function changechain()
    {
        $chainid = intval($_POST['chainid']);
        $config = config('group');
        if ($chainid > 0) {

            //门店技师
            $jsmap['status'] = 1;
            $jsmap['chain_id'] = $chainid;
            $jsmap['user_group'] = $config['jishi'];

            $jishi = Db::table('mbs_user')
                ->where($jsmap)
                ->order('id asc')
                ->select();
            $data['jishi'] = $jishi;

            //门店诊疗项目
            $pakmap['status'] = 1;
            $package = Db::table('mbs_package')
                ->where($pakmap)
                ->where('find_in_set(' . $chainid . ',chain_id)')
                ->order('package_id asc')
                ->select();
            $data['package'] = $package;

            //门店前台
            $reception = intval($config['reception']);
            $remap['status'] = 1;
            $remap['chain_id'] = $chainid;
            $remap['user_group'] = $config['reception'];
            $recelist = Db::table('mbs_user')
                ->where($remap)
                ->order('id asc')
                ->select();
            $data['reception'] = $recelist;

            $data['code'] = 200;
            $data['msg'] = '操作成功';
            return json_encode($data);
        } else {
            $data['code'] = 0;
            $data['msg'] = '无效的门店';
            return json_encode($data);
        }
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
        $data['source_id'] = $new['sourceid'];
        $data['source_from'] = $new['sourcefrom'];
        $data['chain_id'] = $new['chainid'];
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
        if (isset($_POST['serid'])) {
            $server_id = intval($_POST['serid']);
        }

        $customerspost = $_POST['customers'];

        //附加用户信息
        $addinfoexd = $_POST['addinfos'];
        $exdcusarr = array_filter(explode(',', $addinfoexd));
        foreach ($exdcusarr as $k => $v) {
            $exdcusinfo[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }
        if (isset($_POST['cussrc']) && $_POST['cussrc'] != '') {
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
        $addinfo['customer_sign'] = $_POST['customer_sign'];
        $addinfo['dean_sign'] = $_POST['dean_sign'];
        if ($server_id > 0) {
            $addinfo['update_time'] = date('Y-m-d', time());
            //上传图片凭证
            $picturestr = [];
            if (isset($_FILES['upfile'])) {
                $photo = $_FILES['upfile'];
                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/upload";//文件存放目录
                if ($_FILES) {
                    extract($_POST);
                    $i = 0;
                    foreach ($_FILES["upfile"]["error"] as $key => $error) {
                        if ($error == UPLOAD_ERR_OK) {
                            $re = $this->upload_multi($uploaddir, $_FILES["upfile"], $i, $server_id);
                            if (!empty($re)) {
                                $picturestr[] = $re;
                            }
                        }
                        $i++;
                    }
                }
                $picstr = Db::table('mbs_all_assessment')
                    ->field('pictures')
                    ->where('id', $server_id)
                    ->find();
                if (!empty($picstr)) {
                    $oldarr = explode(',', $picstr['pictures']);
                    $picturestr = array_filter(array_unique(array_merge($oldarr, $picturestr)));
                }
                foreach ($picturestr as $k => $v) {
                    $picinfo = explode('_', $v);
                    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/" . date('Ymd', $picinfo[1]) . '/' . $v)) {
                        unset($picturestr[$k]);
                    }
                }
                if (!empty($picturestr)) {
                    $picturestrs = implode(',', $picturestr);
                }
                $addinfo['pictures'] = $picturestrs;
            }
            if (isset($_POST['sserinfo'])) {
                $sserinfo = json_decode($_POST['sserinfo'], true);
            }
            $list = [];

            //        启动事务
            Db::startTrans();
            try {

                Db::table('mbs_customers')
                    ->where("id", $addinfo['customer_id'])
                    ->update($exdcusinfo);

                Db::table('mbs_all_assessment')
                    ->where("id", $server_id)
                    ->update($addinfo);

                if (!empty($sserinfo)) {
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
                ->where("customer_id", $addinfo['customer_id'])
                ->where("status", 1)
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
                    ->where("customer_id", $addinfo['customer_id'])
                    ->where("status", 1)
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
        $picarr = [];
        if (!empty($serinfo['pictures'])) {
            $piclist = explode(',', $serinfo['pictures']);
            foreach ($piclist as $k => $v) {
                $picinfo = explode('_', $v);
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/" . date('Ymd', $picinfo[1]) . '/' . $v)) {
                    $picarr[$k]['name'] = $v;
                    $picarr[$k]['url'] = date('Ymd', $picinfo[1]);
                }
            }
        }
        $this->assign('picarr', $picarr);
////        echo $serid;
//        echo '<pre/>';
//        var_dump($picarr);
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
        //图片凭证
        $picarr = [];
        if (!empty($serinfo['pictures'])) {
            $piclist = explode(',', $serinfo['pictures']);
            foreach ($piclist as $k => $v) {
                $picinfo = explode('_', $v);
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/" . date('Ymd', $picinfo[1]) . '/' . $v)) {
                    $picarr[$k]['name'] = $v;
                    $picarr[$k]['url'] = date('Ymd', $picinfo[1]);
                }
            }
        }
        $this->assign('picarr', $picarr);
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

    public function addcharge()
    {
        $customer_id = intval($_POST['cust_id']);
        $card_no = trim($_POST['card_no']);
        $card_info = Db::table('mbs_customer_card')
            ->where('customer_id', $customer_id)
            ->where('card_no', $card_no)
            ->where('status', 1)
            ->find();

        $card = Db::table('mbs_customer_card')
            ->where('customer_id', $customer_id)
            ->where('card_no!=\'' . $card_no . '\'')
            ->where('status', 1)
            ->select();
        if (!empty($card)) {
            $data['code'] = 0;
            $data['msg'] = '卡号不对，请核实';
            return json_encode($data);
        }

        Db::startTrans();
        try {
            //总值增加
            $custmoney = Db::table('mbs_customers')
                ->where('id', $customer_id)
                ->find();
            $custdata['money'] = intval($_POST['chargemoney']) + intval($custmoney['money']);
            $re1 = Db::table('mbs_customers')
                ->where('id', $customer_id)
                ->update($custdata);

            $re3 = Db::table('mbs_customer_card')
                ->where('customer_id', $customer_id)
                ->update($custdata);

            //充值操作
            $data['card_id'] = $card_info['card_id'];
            $data['customer_id'] = $customer_id;
            $data['chain_id'] = intval($_POST['chain_id']);
            $data['type'] = 1;
            $data['money'] = intval($_POST['chargemoney']);
            $data['add_user'] = $_SESSION['UID'];
            $data['create_time'] = date('Y-m-d H-i:s', time());
            $re = Db::table('mbs_caozuocard')
                ->insertGetId($data);

            //操作日志
            $logdata['xf_id'] = $re;
            $logdata['user_id'] = $_SESSION['UID'];
            $logdata['customer_id'] = $customer_id;
            $logdata['logType'] = 5;
            $logdata['logContent'] = '客户充值，充值金额' . intval($_POST['chargemoney']);
            $logdata['money'] = intval($_POST['chargemoney']);
            $logdata['createTime'] = date('Y-m-d H:i:s', time());
            $re2 = Db::table('mbs_xiaofei_log')
                ->insert($logdata);
            if ($re && $re1 && $re2 && $re3) {
                Db::commit();//提交事务
                $data['code'] = 200;
                $data['msg'] = '充值成功';
                $data['id'] = $re;
                $data['addname'] = $_SESSION['UNAME'];
                return json_encode($data);
            } else {
                Db::rollback();//回滚事务
                $data['code'] = 0;
                $data['msg'] = '充值失败';
                return json_encode($data);
            }
        } catch (\Exception $e) {
            Db::rollback();//回滚事务
            unset($data);
            $data['code'] = 0;
            $data['msg'] = '充值失败';
            return json_encode($data);
        }
    }

    public function delcharge()
    {
        $caozuoid = intval($_POST['id']);
        $caozuoinfo = Db::table('mbs_caozuocard')
            ->where('caozuo_id', $caozuoid)
            ->find();
        if (empty($caozuoinfo) || !(intval($caozuoinfo['card_id']) > 0)) {
            $data['code'] = 0;
            $data['msg'] = '作废失败，操作为空';
            return json_encode($data);
        }
        $cardinfo = Db::table('mbs_customer_card')
            ->where('card_id', intval($caozuoinfo['card_id']))
            ->find();
        //充值之后产生过划扣的卡不能废除
        $ifhuakou = Db::table('mbs_caozuocard')
            ->where('type', 2)
            ->where('status', 1)
            ->where('card_id', intval($caozuoinfo['card_id']))
            ->where('customer_id', $cardinfo['customer_id'])
            ->where('create_time', '>= time', date('Y-m-d H:i:s', strtotime($caozuoinfo['create_time']) - 30))
            ->find();
        if (!empty($ifhuakou)) {
            $data['code'] = 0;
            $data['msg'] = '此卡充值后被划扣，不能作废';
            return json_encode($data);
        }
        $custinfo = Db::table('mbs_customers')
            ->field('money')
            ->where('id', $cardinfo['customer_id'])
            ->find();

        if (intval($caozuoinfo['money']) > 0) {
            Db::startTrans();
            try {
                $cdata['status'] = 2;
                $re = Db::table('mbs_caozuocard')
                    ->where('caozuo_id', $caozuoid)
                    ->update($cdata);

                $cadata['money'] = intval($custinfo['money']) - intval($caozuoinfo['money']);
                $re1 = Db::table('mbs_customer_card')
                    ->where('card_id', intval($caozuoinfo['card_id']))
                    ->update($cadata);

                $custdata['money'] = intval($custinfo['money']) - intval($caozuoinfo['money']);
                $re2 = Db::table('mbs_customers')
                    ->where('id', $cardinfo['customer_id'])
                    ->update($custdata);

                $logdata['xf_id'] = intval($caozuoinfo['card_id']);
                $logdata['user_id'] = $_SESSION['UID'];
                $logdata['customer_id'] = $cardinfo['customer_id'];
                $logdata['logType'] = 6;
                $logdata['logContent'] = '客户充值，作废充值金额' . intval($caozuoinfo['money']);
                $logdata['money'] = intval($caozuoinfo['money']);
                $logdata['createTime'] = date('Y-m-d H:i:s', time());
                $re3 = Db::table('mbs_xiaofei_log')
                    ->insert($logdata);
                if ($re && $re1 && $re2 && $re3) {
                    Db::commit();//提交事务
                    $data['code'] = 200;
                    $data['msg'] = '作废成功';
                    $data['money'] = intval($custinfo['money']) - intval($caozuoinfo['money']);
                    $data['addname'] = $_SESSION['UNAME'];
                    return json_encode($data);
                } else {
                    Db::rollback();//回滚事务
                    $data['code'] = 0;
                    $data['msg'] = '作废失败';
                    return json_encode($data);
                }
            } catch (\Exception $e) {
                Db::rollback();//回滚事务
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '充值失败';
                return json_encode($data);
            }
        } else {
            unset($data);
            $data['code'] = 0;
            $data['msg'] = '操作金额非法';
            return json_encode($data);
        }
    }

    public function imgtest()
    {
        $picturestr = [];
        $serid = intval($_GET['serverno']);
        if (!isset($_FILES['upfile'])) {
            $data['code'] = 0;
            $data['msg'] = '上传数据为空';
            return json_encode($data);
        }
        $photo = $_FILES['upfile'];
        $uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/upload";//文件存放目录
        if ($_FILES) {
            extract($_POST);
            $i = 0;
            foreach ($_FILES["upfile"]["error"] as $key => $error) {
                if ($error == UPLOAD_ERR_OK) {
                    $re = $this->upload_multi($uploaddir, $_FILES["upfile"], $i, $serid);
                    if (!empty($re)) {
                        $picturestr[] = $re;
                    }
                }
                $i++;
            }
        }

        $picstr = Db::table('mbs_all_assessment')
            ->field('pictures')
            ->where('id', $serid)
            ->find();
        if (!empty($picstr)) {
            $oldarr = explode(',', $picstr['pictures']);
            $picturestr = array_filter(array_unique(array_merge($oldarr, $picturestr)));
        }
        foreach ($picturestr as $k => $v) {
            $picinfo = explode('_', $v);
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/" . date('Ymd', $picinfo[1]) . '/' . $v)) {
                unset($picturestr[$k]);
            }
        }
        if (!empty($picturestr)) {
            $picturestrs = implode(',', $picturestr);
        }
        $re = Db::table('mbs_all_assessment')
            ->where('id', $serid)
            ->update(['pictures' => $picturestrs]);
        if ($re) {
            if ($re) {
                $data['code'] = 200;
                $data['msg'] = '上传成功';
                $data['piclist'] = $picturestrs;
                return json_encode($data);
            } else {
                $data['code'] = 0;
                $data['msg'] = '上传失败';
                return json_encode($data);
            }
        }

    }

    public function upload_multi($path, $photo, $i, $serid)
    {

        $date = date('Ymd', time());
        if (!file_exists($path))//如果目录不存在就新建
            $path = mkdir($path);
        if (!file_exists($path . '/' . $date))
            $childpath = mkdir($path . '/' . $date);
        $piece = explode('.', $photo['name'][$i]);
        $uploadfile = $path . '/' . $date . '/' . $serid . '_' . time() . '_' . $i . '.' . $piece[1];
        $result = move_uploaded_file($photo['tmp_name'][$i], $uploadfile);
        if (!$result) {
            exit('上传失败');
        }
        $this->image_zip($uploadfile, 50, 600);
        return basename($uploadfile);
    }

    function image_zip($url, $size_number, $size_thumb)
    {
        $size = filesize($url);
        if (($size / 1000) > $size_number) {
            $image = \think\Image::open($url);
            $image->thumb($size_thumb, $size_thumb)->save($url);
        }
    }

}