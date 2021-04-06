<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
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
        }
        if ($_SESSION['GID'] == $config['chief_dean']) {
            //总院长
            $this->assign('sign', 1);
        }
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chain);
        $map = array();
        $ormap = array();
        $where = '';
        $orwhere = '';
        //院长能看本院所有客户
        if ($_SESSION['GID'] == $config['dean']) {
            //院长
            $map['a.chain_id'] = intval($_SESSION['CID']);
            $where = ' AND a.chain_id=\'' . intval($_SESSION['CID']) . '\' ';
        }

        //普通技师能看自己负责的客人
        if ($_SESSION['GID'] == $config['jishi']) {
            //技师
            $owearr = [];
            $owecust = Db::table('mbs_all_assessment')
                ->distinct(true)
                ->field('customer_id')
                ->where('status',1)
                ->where('server_id',intval($_SESSION['UID']))
                ->select();
            foreach ($owecust as $k=>$v){
                $owearr[]= $v['customer_id'];
            }
            if(count($owearr)>0){
                $owestr = implode(',',$owearr);
                $map['a.id'] = ['in',$owestr];
                $where .= ' AND a.id in ('.$owestr.')';
            }
        }
        if (!empty($_GET['chain_id']) && intval($_GET['chain_id']) > 0) {
            $map['a.chain_id'] = intval($_GET['chain_id']);
            $where = ' AND a.chain_id=\'' . intval($_GET['chain_id']) . '\' ';
            $this->assign('cid', intval($_GET['chain_id']));
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
            $where = ' AND o.create_time>\'' . $star_t . '\' AND o.create_time<\'' . $end_t . '\' ';
        }
        $map['a.status'] = 1;
        $pagesize = 20;
        Loader::import('page.Page', EXTEND_PATH, '.class.php');

        $total = Db::table('mbs_customers')
            ->alias('a')
            ->join('mbs_all_assessment o', 'a.id=o.customer_id', 'LEFT')
            ->whereOr($ormap)
            ->where($map)
            ->count();
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
        $id = intval($_GET['id']);
        $cid = intval($_SESSION['CID']);
        $cname = '';
        //门店id
        if ($cid > 0) {
            $cnamearr = getChainname($cid);
            $cname = $cnamearr['name'];
        }
        $gid = intval($_SESSION['GID']);
        $config = config('group');
        //客户信息
        $cusinfo = Db::table('mbs_customers')
            ->where('id', $id)
            ->find();

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

        $cin['cid'] = $cid;
        $cin['cname'] = $cname;
        $this->assign('cin', $cin);
        $this->assign('cname', $cname);

        $this->assign('cusinfo', $cusinfo);
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
        }
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

    public function delxiaofei()
    {
        $id = intval($_POST['id']);
        if ($id > 0) {

            Db::startTrans();
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
            if ((empty($ifhave) && $re) || (!empty($ifhave) && $re && $re0)) {
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
        if (isset($_POST['sserinfo'])) {
            $sserinfo = json_decode($_POST['sserinfo'],true);
        }
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
            if(isset($_FILES['upfile'])){
                $photo = $_FILES['upfile'];
                $uploaddir = $_SERVER['DOCUMENT_ROOT'] . "/upload";//文件存放目录
                if ($_FILES) {
                    extract($_POST);
                    $i = 0;
                    foreach ($_FILES["upfile"]["error"] as $key => $error) {
                        if ($error == UPLOAD_ERR_OK) {
                            $re =    $this->upload_multi($uploaddir, $_FILES["upfile"], $i,$server_id);
                            if(!empty($re)){
                                $picturestr[] = $re;
                            }
                        }
                        $i++;
                    }
                }
                $picstr = Db::table('mbs_all_assessment')
                    ->field('pictures')
                    ->where('id',$server_id)
                    ->find();
                if(!empty($picstr)){
                    $oldarr = explode(',',$picstr['pictures']);
                    $picturestr = array_filter(array_unique(array_merge($oldarr,$picturestr)));
                }
                foreach ($picturestr as $k=>$v){
                    $picinfo = explode('_',$v);
                    if(!file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/".date('Ymd',$picinfo[1]).'/'.$v)){
                        unset($picturestr[$k]);
                    }
                }
                if(!empty($picturestr)){
                    $picturestrs = implode(',',$picturestr);
                }
                $addinfo['pictures'] = $picturestrs;
            }

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
        if(!empty($serinfo['pictures'])){
            $piclist = explode(',',$serinfo['pictures']);
            foreach ($piclist as $k=>$v){
                $picinfo = explode('_',$v);
                if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/".date('Ymd',$picinfo[1]).'/'.$v)){
                    $picarr[$k]['name'] = $v;
                    $picarr[$k]['url'] = date('Ymd',$picinfo[1]);
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
        if(!empty($serinfo['pictures'])){
            $piclist = explode(',',$serinfo['pictures']);
            foreach ($piclist as $k=>$v){
                $picinfo = explode('_',$v);
                if(file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/".date('Ymd',$picinfo[1]).'/'.$v)){
                    $picarr[$k]['name'] = $v;
                    $picarr[$k]['url'] = date('Ymd',$picinfo[1]);
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
    public function indeximg(){
        return $this->view->fetch();
    }

    public function imgtest()
    {
        $picturestr = [];
        $serid = intval($_GET['serverno']);
        if(!isset($_FILES['upfile'])){
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
                    $re =    $this->upload_multi($uploaddir, $_FILES["upfile"], $i,$serid);
                    if(!empty($re)){
                        $picturestr[] = $re;
                    }
                }
                $i++;
            }
        }

        $picstr = Db::table('mbs_all_assessment')
            ->field('pictures')
            ->where('id',$serid)
            ->find();
        if(!empty($picstr)){
            $oldarr = explode(',',$picstr['pictures']);
            $picturestr = array_filter(array_unique(array_merge($oldarr,$picturestr)));
        }
        foreach ($picturestr as $k=>$v){
            $picinfo = explode('_',$v);
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . "/upload/".date('Ymd',$picinfo[1]).'/'.$v)){
                unset($picturestr[$k]);
            }
        }
        if(!empty($picturestr)){
            $picturestrs = implode(',',$picturestr);
        }
        $re = Db::table('mbs_all_assessment')
            ->where('id',$serid)
            ->update(['pictures'=>$picturestrs]);
        if($re){
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
    public function upload_multi($path,$photo,$i,$serid){

        $date = date('Ymd',time());
        if (!file_exists($path))//如果目录不存在就新建
            $path = mkdir($path);
        if(!file_exists($path.'/'.$date))
            $childpath = mkdir($path.'/'.$date);
        $piece = explode('.', $photo['name'][$i]);
        $uploadfile = $path.'/'.$date.'/' .$serid. '_' .time().'_'.$i. '.' . $piece[1];
        $result = move_uploaded_file($photo['tmp_name'][$i], $uploadfile);
        if (!$result) {
            exit('上传失败');
        }
        return basename($uploadfile);
    }
}