<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Tongji extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function day()
    {
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chain);
        $this->assign('chainlist', $chainlist);
        if (isset($_GET['chain_id']) && intval($_GET['chain_id']) > 0) {
            $cid = intval($_GET['chain_id']);
            $this->assign('cid', $cid);
        } else {
            $cid = $_SESSION['CID'];
            $this->assign('cid', $cid);
        }
        //日报表
        $xfwhere = '';
        $tfwhere = '';
        $pakwhere = '';
        $daiwhere = '';
        $tuifeiwhere = '';
        $daituiwhere = '';
        if (!isset($_GET['create_time']) || empty($_GET['create_time'])) {
//            默认当日
            $star_t = date('Y-m-d 00:00:00',time());
            $end_t = date('Y-m-d 23:59:59',time());
            $xfwhere .= ' AND (x.create_time >\'' . $star_t . '\' AND x.create_time <\'' . $end_t . '\' ';
            $xfwhere .= ' OR (h.create_time >\'' . $star_t . '\' AND h.create_time <\'' . $end_t . '\')) ';
            $tfwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $pakwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $daiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $tuifeiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $daituiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
        } else if (!empty($_GET['create_time'])) {
            $star_t = $_GET['create_time'] . ' 00:00:00';
            $end_t = $_GET['create_time'] . ';23:59:59';
            $this->assign('create_time', date('Y-m-d', time()));
            $xfwhere .= ' AND (x.create_time >\'' . $star_t . '\' AND x.create_time <\'' . $end_t . '\' ';
            $xfwhere .= ' OR (h.create_time >\'' . $star_t . '\' AND h.create_time <\'' . $end_t . '\')) ';
            $tfwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $pakwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $daiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $tuifeiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $daituiwhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
        }

        if ($cid > 0) {
            $xfwhere .= ' AND x.serchain_id =\' ' . $cid . '\'';
            $tfwhere .= ' AND serchain_id =\' ' . $cid . '\'';
            $pakwhere .= ' AND serchain_id =\' ' . $cid . '\'';
            $daiwhere .= ' AND xfchain_id =\' ' . $cid . '\' AND serchain_id!=xfchain_id ';
            $tuifeiwhere .= ' AND xfchain_id =\' ' . $cid . '\' ';
            $daituiwhere .= ' AND xfchain_id =\' ' . $cid . '\' AND serchain_id!=xfchain_id ';
        } else {
//            $cid=2;//示例后期删除
        }
        $cust = [];
        $custxf = [];
        $custxflist = [];
        $custtf = [];
        $custtflist = [];
        $customowe = [];
        //客户消费、还款清单
        $xfcount = [];
        $xfc = [];
        $jishixf = [];
        $custxf = [];
        $feihuan = [];
        $xiaofeitotal = 0;
        $xiaofeilist = Db::query('SELECT x.total_money xiaofei,x.xiaofei_id,h.huankuan_id,x.owe,h.huan_money huankuan,x.customer_id customer_id,x.payway_id xfpayway_id,h.payway_id hkpayway_id,x.firvisit,x.package_id,x.jishi_id,x.jishi_name,x.serchain_id,x.xfchain_id,h.status hstatus FROM mbs_xiaofei x LEFT JOIN mbs_xfhuankuan h ON x.xiaofei_id = h.xiaofei_id WHERE x.status=1 ' . $xfwhere . ' ORDER BY x.xiaofei_id ASC');
        foreach ($xiaofeilist as $k => $v) {
            $feihuan[$v['xiaofei']][] = $v['xiaofei'];
            if (count($feihuan[$v['xiaofei']]) > 1) {
                //已记录
            } else {
                //未记录
                if ($v['xiaofei'] > 0) {
                    $xiaofeitotal = $v['xiaofei'] + $xiaofeitotal;
                }
            }
            if (!empty($v['hstatus'])) {
                $custxf[] = "'" . $v['customer_id'] . "'";
                $jishixf[] = "'" . $v['jishi_id'] . "'";

                $custxflist[$v['customer_id']][$k]['xiaofei_id'] = $v['xiaofei_id'];
                $customowe[$v['customer_id']][$v['xiaofei_id']]['owe'] = $v['owe'];
                if ($v['serchain_id'] != $v['xfchain_id']) {
                    $custxflist[$v['customer_id']][$k]['jdpayway_id'] = $v['xfpayway_id'];
                    $custxflist[$v['customer_id']][$k]['jdxiaofei'] = $v['xiaofei'];
                    $custxflist[$v['customer_id']][$k]['xfpayway_id'] = '';
                    $custxflist[$v['customer_id']][$k]['xiaofei'] = '';
                } else {
                    $custxflist[$v['customer_id']][$k]['xfpayway_id'] = $v['xfpayway_id'];
                    $custxflist[$v['customer_id']][$k]['xiaofei'] = $v['xiaofei'];
                    $custxflist[$v['customer_id']][$k]['jdpayway_id'] = '';
                    $custxflist[$v['customer_id']][$k]['jdxiaofei'] = '';
                }
                $custxflist[$v['customer_id']][$k]['huankuan_id'] = $v['huankuan_id'];
                $custxflist[$v['customer_id']][$k]['hkpayway_id'] = $v['hkpayway_id'];
                $custxflist[$v['customer_id']][$k]['huankuan'] = $v['huankuan'];
                $custxflist[$v['customer_id']][$k]['firvisit'] = $v['firvisit'];
                $custxflist[$v['customer_id']][$k]['package_id'] = $v['package_id'];
                $custxflist[$v['customer_id']][$k]['jishi_name'] = $v['jishi_name'];
                $custxflist[$v['customer_id']][$k]['serchain_id'] = $v['serchain_id'];
                $custxflist[$v['customer_id']][$k]['xfchain_id'] = $v['xfchain_id'];
                if (!empty($v['hstatus'])) {
                    $custxflist[$v['customer_id']][$k]['kfje'] = $v['xiaofei'] + $v['owe'] + $v['huankuan'];
                } else {
                    $custxflist[$v['customer_id']][$k]['kfje'] = $v['xiaofei'] + $v['owe'];
                }
                if (!empty($v['huankuan_id']) && !empty($v['hstatus'])) {
                    $xfc [$v['xiaofei_id']][] = $v['huankuan_id'] . ',' . $v['owe'] . ',' . $v['xiaofei'] . ',' . $v['xfchain_id'] . ',' . $v['huankuan'] . ',' . $v['xiaofei_id'];
                }
            } else {
                if (count($feihuan[$v['xiaofei']]) > 1) {

                    //已记录
                } else {
                    //未记录
                    $custxf[] = "'" . $v['customer_id'] . "'";
                    $jishixf[] = "'" . $v['jishi_id'] . "'";

                    $custxflist[$v['customer_id']][$k]['xiaofei_id'] = $v['xiaofei_id'];
                    $customowe[$v['customer_id']][$v['xiaofei_id']]['owe'] = $v['owe'];
                    if ($v['serchain_id'] != $v['xfchain_id']) {
                        $custxflist[$v['customer_id']][$k]['jdpayway_id'] = $v['xfpayway_id'];
                        $custxflist[$v['customer_id']][$k]['jdxiaofei'] = $v['xiaofei'];
                        $custxflist[$v['customer_id']][$k]['xfpayway_id'] = '';
                        $custxflist[$v['customer_id']][$k]['xiaofei'] = '';
                    } else {
                        $custxflist[$v['customer_id']][$k]['xfpayway_id'] = $v['xfpayway_id'];
                        $custxflist[$v['customer_id']][$k]['xiaofei'] = $v['xiaofei'];
                        $custxflist[$v['customer_id']][$k]['jdpayway_id'] = '';
                        $custxflist[$v['customer_id']][$k]['jdxiaofei'] = '';
                    }
                    $custxflist[$v['customer_id']][$k]['huankuan_id'] = null;
                    $custxflist[$v['customer_id']][$k]['hkpayway_id'] = null;
                    $custxflist[$v['customer_id']][$k]['huankuan'] = null;
                    $custxflist[$v['customer_id']][$k]['firvisit'] = $v['firvisit'];
                    $custxflist[$v['customer_id']][$k]['package_id'] = $v['package_id'];
                    $custxflist[$v['customer_id']][$k]['jishi_name'] = $v['jishi_name'];
                    $custxflist[$v['customer_id']][$k]['serchain_id'] = $v['serchain_id'];
                    $custxflist[$v['customer_id']][$k]['xfchain_id'] = $v['xfchain_id'];
                    if (!empty($v['hstatus'])) {
                        $custxflist[$v['customer_id']][$k]['kfje'] = $v['xiaofei'] + $v['owe'] + $v['huankuan'];
                    } else {
                        $custxflist[$v['customer_id']][$k]['kfje'] = $v['xiaofei'] + $v['owe'];
                    }

                    if (!empty($v['huankuan_id']) && !empty($v['hstatus'])) {
                        $xfc [$v['xiaofei_id']][] = $v['huankuan_id'] . ',' . $v['owe'] . ',' . $v['xiaofei'] . ',' . $v['xfchain_id'] . ',' . $v['huankuan'] . ',' . $v['xiaofei_id'];
                    }
                }
            }

        }
//        echo '<pre/>';var_dump($customowe);die();
        //当天之内多次还款
        foreach ($xfc as $k => $v) {
            $aa = count($v);
            foreach ($v as $kk => $vv) {
                $tt = explode(',', $vv);
                if ($kk == 0) {
                    $xfcount[$k][$tt[0]]['row'] = $aa;
                    $xfcount[$k][$tt[0]]['owe'] = $tt[1];
                    $xfcount[$k][$tt[0]]['xiaofei'] = $tt[2];
                    $xfcount[$k][$tt[0]]['xfchain_id'] = $tt[3];
                    $xfcount[$k]['kfje'][$tt[5]]['kfje'] = $tt[1] + $tt[2] + $tt[4];


                } else {
                    $xfcount[$k][$tt[0]]['row'] = null;
                    $xfcount[$k][$tt[0]]['owe'] = null;
                    $xfcount[$k][$tt[0]]['xiaofei'] = null;
                    $xfcount[$k][$tt[0]]['xfchain_id'] = null;
                    $xfcount[$k]['kfje'][$tt[5]]['kfje'] = $xfcount[$k]['kfje'][$tt[5]]['kfje'] + $tt[4];

                }

            }
        }
//        echo '<pre/>';var_dump($xfcount);die();
        $this->assign('xfcount', $xfcount);
        $this->assign('custxflist', $custxflist);
        $this->assign('customowe', $customowe);
        //客户退费
        $custtf = [];
        $jishitf = [];
        $tuitotal = 0;

        $tuifeilist = Db::query('SELECT total_money tuifei,tuifei_id,customer_id,payway_id xfpayway_id,firvisit,jishi_id,jishi_name,serchain_id,xfchain_id FROM mbs_tuifei WHERE status=1 ' . $tfwhere . ' ORDER BY customer_id ASC');

        foreach ($tuifeilist as $k => $v) {
            if ($v['tuifei'] > 0) {
                $tuitotal = $tuitotal + $v['tuifei'];
            }
            $custtf[] = "'" . $v['customer_id'] . "'";
            $jishitf[] = "'" . $v['jishi_id'] . "'";
            $custtflist[$v['customer_id']][$k]['xiaofei'] = $v['tuifei'];
            $custtflist[$v['customer_id']][$k]['tuifei_id'] = $v['tuifei_id'];
            $custtflist[$v['customer_id']][$k]['xfpayway_id'] = $v['xfpayway_id'];
            $custtflist[$v['customer_id']][$k]['firvisit'] = $v['firvisit'];
            $custtflist[$v['customer_id']][$k]['jishi_name'] = $v['jishi_name'];
            $custtflist[$v['customer_id']][$k]['serchain_id'] = $v['serchain_id'];
            $custtflist[$v['customer_id']][$k]['xfchain_id'] = $v['xfchain_id'];
        }
        $this->assign('custtflist', $custtflist);

        $cust = array_unique(array_merge($custxf, $custtf));
        $jishiids = array_unique(array_merge($jishixf, $jishitf));
//        echo'<pre/>';var_dump($jishiids);die();
        $custwhere = '';
        if (!empty($cust)) {
            $custstr = implode(',', $cust);
            $custwhere .= ' AND id IN (' . $custstr . ') ';
        }

        $customers = Db::query('SELECT * FROM mbs_customers WHERE status=1 ' . $custwhere . ' ORDER BY id ASC');
        $this->assign('customers', $customers);
        //支付方式
        $payway = Db::query("SELECT payway_id,payway_name,paytype FROM mbs_payway WHERE status=1 ");
        $localpay = array();
        $otherchainpay = array();
        $huanpay = array();

        foreach ($payway as $k => $v) {
            if ($v['payway_id'] == 1) {
                //本店支付
                $localpay[$k] = $v;
            }
            if ($v['payway_id'] == 2) {
                //借店支付
                $otherchainpay[$k] = $v;
            }
            if ($v['payway_id'] == 1) {
                //还款支付
                $otherchainpay[$k] = $v;
            }
        }
        $this->assign('localpay', $localpay);
        $this->assign('otherchainpay', $otherchainpay);
        $this->assign('otherchainpay', $otherchainpay);
        $this->assign('payway', $payway);

        $pack = Db::table('mbs_package')
            ->where('status', 1)
            ->order('package_id asc')
            ->select();
        $package = array();
        foreach ($pack as $k => $v) {
            $package[$v['package_id']] = $v;
        }
        $this->assign('package', $package);
//        echo '<pre/>';var_dump($package);die();

        //消费支付方式
        $xfpay = config('xfpay');
        $this->assign('xfpay', $xfpay);

        //借店支付方式
        $jdpay = config('jdpay');
        $this->assign('jdpay', $jdpay);

        //还款支付方式
        $hkpay = config('hkpay');
        $this->assign('hkpay', $hkpay);

        $pakid = config('package');
        $this->assign('pakid', $pakid);
        //统计当天指定门店祛斑，体验卡，疗程，产品消费


        if ($cid > 0) {

            $dschain = array();//代收
            $yejilist = array();
            $paktotal = array();
            $dschainid = array();
            $jishiall = array();

            //当日消费
            $xiaofeipak = Db::query('SELECT SUM(total_money) xiaofei,SUM(owe) owe,package_id,jishi_id FROM mbs_xiaofei WHERE status=1  ' . $pakwhere . ' GROUP BY package_id,jishi_id ORDER BY customer_id ASC');

            foreach ($xiaofeipak as $k => $v) {
                if ($v['jishi_id'] > 0) {
                    $jishiall[] = $v['jishi_id'];
                }
                switch ($v['package_id']) {
                    case $pakid['quban']:
                        if ($v['package_id'] == $pakid['quban']) {
                            if (isset($yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'])) {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                            if (isset($paktotal[$v['package_id']]['total'])) {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'] + $paktotal[$v['package_id']]['total'];
                            } else {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['tiyanka']:
                        if ($v['package_id'] == $pakid['tiyanka']) {
                            if (isset($yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'])) {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                            if (isset($paktotal[$v['package_id']]['total'])) {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'] + $paktotal[$v['package_id']]['total'];
                            } else {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['chanpinyeji']:
                        if ($v['package_id'] == $pakid['chanpinyeji']) {
                            if (isset($yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'])) {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                            if (isset($paktotal[$v['package_id']]['total'])) {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'] + $paktotal[$v['package_id']]['total'];
                            } else {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['liaochengyeji']:
                        if ($v['package_id'] == $pakid['liaochengyeji']) {
                            if (isset($yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'])) {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $yejilist[$v['jishi_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                            if (isset($paktotal[$v['package_id']]['total'])) {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'] + $paktotal[$v['package_id']]['total'];
                            } else {
                                $paktotal[$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                }

            }
            //当日退费

            $tuifeijishi = Db::query('SELECT SUM(total_money) tuifei,SUM(owe) owe,jishi_id FROM mbs_tuifei WHERE status=1  ' . $tuifeiwhere . ' GROUP BY jishi_id ORDER BY customer_id ASC');
            foreach ($tuifeijishi as $k => $v) {

                if ($v['jishi_id'] > 0) {
                    $jishiall[] = $v['jishi_id'];
                }
                $yejilist[$v['jishi_id']]['tuifei'] = $v['tuifei'];
            }

            $this->assign('yejilist', $yejilist);
            $this->assign('paktotal', $paktotal);
            $this->assign('tuitotal', $tuitotal);
            $this->assign('xiaofeitotal', $xiaofeitotal);

            //当日代收其他门店
            $dstotal = 0;
            $daishou = Db::query('SELECT SUM(total_money) xiaofei,SUM(owe) owe,xfchain_id,package_id FROM mbs_xiaofei WHERE status=1  ' . $daiwhere . ' GROUP BY package_id,xfchain_id ORDER BY customer_id ASC');
            foreach ($daishou as $k => $v) {
                if ($v['xfchain_id'] > 0) {
                    $dschainid[] = $v['xfchain_id'];
                }
                if ($v['xiaofei'] > 0) {
                    $dstotal = $dstotal + $v['xiaofei'];
                }

                switch ($v['package_id']) {
                    case $pakid['quban']:
                        if ($v['package_id'] == $pakid['quban']) {
                            if (isset($yejilist[$v['xfchain_id']]['package'][$v['package_id']]['total'])) {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['tiyanka']:
                        if ($v['package_id'] == $pakid['tiyanka']) {
                            if (isset($yejilist[$v['xfchain_id']]['package'][$v['package_id']]['total'])) {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['chanpinyeji']:
                        if ($v['package_id'] == $pakid['chanpinyeji']) {
                            if (isset($yejilist[$v['xfchain_id']]['package'][$v['package_id']]['total'])) {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                    case $pakid['liaochengyeji']:
                        if ($v['package_id'] == $pakid['liaochengyeji']) {
                            if (isset($yejilist[$v['xfchain_id']]['package'][$v['package_id']]['total'])) {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'] + $yejilist[$v['jishi_id']][$v['package_id']]['total'];
                            } else {
                                $dschain[$v['xfchain_id']]['package'][$v['package_id']]['total'] = $v['xiaofei'];
                            }
                        }
                        break;
                }
            }
            //当日代退费门店
            $daituichain = Db::query('SELECT SUM(total_money) tuifei,SUM(owe) owe,xfchain_id FROM mbs_tuifei WHERE status=1  ' . $daituiwhere . ' GROUP BY xfchain_id ORDER BY customer_id ASC');
            foreach ($daituichain as $k => $v) {
                if ($v['xfchain_id'] > 0) {
                    $dschainid[] = $v['xfchain_id'];
                }
                $dschain[$v['xfchain_id']]['tuifei'] = $v['tuifei'];
            }
            $this->assign('dschain', $dschain);
            $this->assign('dstotal', $dstotal);

            $jswherestr = implode(',', array_unique($jishiall));
            $jswhere = '';
            if (!empty($jishiids)) {
                $jishistr = implode(',', $jishiids);
                $jswhere = ' AND id IN (' . $jishistr . ') ';
                $jishi = Db::query('SELECT * FROM mbs_user WHERE status=1' . $jswhere . ' ORDER BY id ASC');
            } else {
                $jswhere = ' AND id IN () ';
                $jishi = array();
            }

//        echo '<pre/>'; var_dump($yejilist);die();
            $this->assign('countjishi', count($jishi) + 1);
            $this->assign('jishi', $jishi);
            $chainarr = array();
            $chainid = array_unique($dschainid);

            foreach ($chain as $k => $v) {
                if (in_array($v['id'], $chainid)) {
                    $chainarr[$k] = $v;
                }
            }
            $this->assign('countchain', count($chainarr) + 1);
            $this->assign('chainarr', $chainarr);
//        echo '<pre/>';var_dump($dschain);die();

        }

        return $this->view->fetch();
    }

    public function count()
    {
        $package = config('package');
        $xfpay = config('xfpay');
        $where = '';
        $dswhere = '';
        if (!isset($_GET['create_time']) || empty($_GET['create_time'])) {
            //默认当日
            $star_t = date('Y-m-01 00:00:00',time());
            $end_t = date('Y-m-d 23:59:59',time());
            $where .=  ' AND create_time >\''.$star_t.'\' AND create_time <\''.$end_t.'\' ';
        } else if (!empty($_GET['create_time'])) {
            $star_t = date('Y-m-01 00:00:00',strtotime($_GET['create_time'] . ' 00:00:00'));
            $end_t = $_GET['create_time'] . '23:59:59';
            $where .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $dswhere .= ' AND create_time >\'' . $star_t . '\' AND create_time <\'' . $end_t . '\' ';
            $this->assign('create_time', date('Y-m-d', time()));
        }
        $cid = 0;
        if (isset($_GET['chain_id']) && !empty($_GET['chain_id'])) {
            $cid = intval($_GET['chain_id']);
        } else {
            $cid = $_SESSION['CID'];
        }
        if (!$cid > 0) {
            $cid = 1;
        }
        $where .= ' AND serchain_id =\' ' . $cid . '\'';
        $dswhere .= ' AND xfchain_id =\' ' . $cid . '\' AND xfchain_id!=serchain_id ';

        $this->assign('cid', $cid);
        $jsyjlist = [];//技师业绩
        $demototal = [];
        $tuifeilist = [];
        $tuifeitotal = 0;
        $chainids = [];
        $dashoutotal = 0;
        $jdtotal = 0;
        $jishiids = [];
        $res = Db::query('SELECT SUM(total_money) xiaofei,SUM(kaifa_money) kaifa,xiaofei_id,owe,customer_id,payway_id xfpayway_id,firvisit,
        package_id,jishi_id, jishi_name,serchain_id,xfchain_id FROM mbs_xiaofei WHERE status = 1 ' . $where . ' GROUP BY jishi_id,package_id ORDER BY jishi_id ASC');

        foreach ($res as $k => $v) {
            $jishiids[] = "'" . $v['jishi_id'] . "'";

            $jsyjlist[$v['jishi_id']][$v['package_id']]['xiaofei'] = $v['xiaofei'];
            $jsyjlist[$v['jishi_id']][$v['package_id']]['kaifa'] = $v['kaifa'];
            if(!empty($jsyjlist[$v['jishi_id']]['xiaofeitotal'])){
                $jsyjlist[$v['jishi_id']]['xiaofeitotal'] = $v['xiaofei'] +  $jsyjlist[$v['jishi_id']]['xiaofeitotal'] ;
            }else{
                $jsyjlist[$v['jishi_id']]['xiaofeitotal'] = $v['xiaofei'];
            }
            if(!empty($jsyjlist[$v['jishi_id']]['kaifatotal'])){
                $jsyjlist[$v['jishi_id']]['kaifatotal'] = $v['kaifa'] + $jsyjlist[$v['jishi_id']]['kaifatotal'] ;
            }else{
                $jsyjlist[$v['jishi_id']]['kaifatotal'] = $v['kaifa'];
            }

            if (!empty($demototal[$v['package_id']]['total'])) {
                $demototal[$v['package_id']]['total'] = $v['xiaofei'] + $demototal[$v['package_id']]['total'];
            } else {
                $demototal[$v['package_id']]['total'] = $v['xiaofei'];
            }

        }
        $jdres = Db::query('SELECT SUM(total_money) xiaofei FROM mbs_xiaofei WHERE status = 1 AND xfchain_id!=serchain_id ' . $where . ' ORDER BY jishi_id ASC');
        if (!empty($jdres)) {
            $jdtotal = $jdres[0]['xiaofei'];
        }
        $this->assign('jdtotal', $jdtotal);
        $this->assign('jdtotal', $jdtotal);
        $this->assign('jsyjlist', $jsyjlist);
        $this->assign('demototal', $demototal);
        //退费
        $res1 = Db::query('SELECT SUM(total_money) tuifei,tuifei_id,payway_id tfpayway_id,jishi_id, jishi_name,serchain_id,xfchain_id 
            FROM mbs_tuifei WHERE status = 1 ' . $where . ' GROUP BY jishi_id,package_id ORDER BY jishi_id ASC');
        foreach ($res1 as $k => $v) {
            $jishiids[] = "'" . $v['jishi_id'] . "'";
            if(!empty($jsyjlist[$v['jishi_id']]['xiaofeitotal'])){
                $jsyjlist[$v['jishi_id']]['xiaofeitotal'] =  intval($jsyjlist[$v['jishi_id']]['xiaofeitotal']) - $v['tuifei'];
            }else{
                $jsyjlist[$v['jishi_id']]['xiaofeitotal'] =  0 - $v['tuifei'];
            }


            $jsyjlist[$v['jishi_id']]['tuifei'] = $v['tuifei'];
            if ($tuifeitotal > 0) {
                $tuifeitotal = $v['tuifei'] + $tuifeitotal;
            } else {
                $tuifeitotal = $v['tuifei'];
            }
        }
        $this->assign('tuifeilist', $tuifeilist);
        $this->assign('tuifeitotal', $tuifeitotal);
        $res2 = Db::query('SELECT SUM(total_money) xiaofei,jishi_id, jishi_name,serchain_id,xfchain_id FROM mbs_xiaofei WHERE status = 1 ' . $dswhere . ' GROUP BY jishi_id,serchain_id ORDER BY jishi_id ASC');

        foreach ($res2 as $k => $v) {
            $jishiids[] = "'" . $v['jishi_id'] . "'";
            $chainids[] = "'" . $v['serchain_id'] . "'";
            $chainids[] = "'" . $v['xfchain_id'] . "'";

            $jsyjlist[$v['jishi_id']]['chain'][$v['serchain_id']]['chain_id'] = $v['serchain_id'];
            if (isset($jsyjlist[$v['jishi_id']]['chain'][$v['serchain_id']]['xiaofei'])) {
                $jsyjlist[$v['jishi_id']]['chain'][$v['serchain_id']]['xiaofei'] = $jsyjlist[$v['jishi_id']]['chain'][$v['serchain_id']]['xiaofei'] + $v['xiaofei'];
            } else {
                $jsyjlist[$v['jishi_id']]['chain'][$v['serchain_id']]['xiaofei'] = $v['xiaofei'];
            }
            if(!empty($jsyjlist[$v['jishi_id']]['dstotal'])){
                $jsyjlist[$v['jishi_id']]['dstotal'] = $v['xiaofei'] + $jsyjlist[$v['jishi_id']]['dstotal'];
            }else{
                $jsyjlist[$v['jishi_id']]['dstotal'] = $v['xiaofei'];
            }
            if(!empty($jsyjlist[$v['jishi_id']]['xiaofeitotal'])){
                $jsyjlist[$v['jishi_id']]['moneytotal'] = $jsyjlist[$v['jishi_id']]['xiaofeitotal'] + $jsyjlist[$v['jishi_id']]['dstotal'];
            }else{
                $jsyjlist[$v['jishi_id']]['moneytotal'] = $jsyjlist[$v['jishi_id']]['dstotal'];
            }

            if ($dashoutotal > 0) {
                $dashoutotal = $v['xiaofei'] + $dashoutotal;
            } else {
                $dashoutotal = $v['xiaofei'];
            }
        }
        $this->assign('jsyjlist', $jsyjlist);
        $this->assign('dashoutotal', $dashoutotal);

        $jsstr = implode(',', array_unique($jishiids));
        if (!empty($jishiids)) {
            $jswhere = ' AND id IN (' . $jsstr . ') ';
            $jishi = Db::query('SELECT * FROM mbs_user WHERE status=1' . $jswhere . ' ORDER BY id ASC');
        } else {
            $jswhere = ' AND id IN () ';
            $jishi = array();
        }
        $chainids[] = $cid;
        $chainstr = implode(',', array_unique($chainids));

        $chain = Db::query('SELECT * FROM mbs_chain WHERE status=1  ORDER BY id ASC');

        $chainlist = [];
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $totalpay = [];
        $res3 = Db::query('SELECT SUM(total_money) xiaofei,payway_id xfpayway_id FROM mbs_xiaofei WHERE status = 1 ' . $where . ' GROUP BY payway_id ORDER BY jishi_id ASC');
        foreach ($res3 as $k => $v) {
            $totalpay[$v['xfpayway_id']] = $v['xiaofei'];
        }
        $this->assign('totalpay', $totalpay);
        $this->assign('xfpay', $xfpay);
        $this->assign('jishi', $jishi);
        $this->assign('chain', $chainlist);
        $this->assign('package', $package);
        return $this->view->fetch();

    }

    public function customers()
    {
        $where = '';
        $tjwhere = '';
        if (!empty($_GET['keywords'])) {
            $ormap['name'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['phone'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['server_no'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['server_name'] = ['like', '%' . $_GET['keywords'] . '%'];
            $where .= ' AND(name LIKE \'%' . $_GET['keywords'] . '%\' ';
            $where .= ' OR phone LIKE \'%' . $_GET['keywords'] . '%\' ';
            $where .= ' OR server_no LIKE \'%' . $_GET['keywords'] . '%\' ';
            $where .= ' OR server_name LIKE \'%' . $_GET['keywords'] . '%\') ';
        }

        if (!empty($_GET['create_time'])) {
            $create_time = strtotime($_GET['create_time']);
            $star_t = date('Y-m-d 00:00:00', $create_time);
            $end_t = date('Y-m-d 23:59:59', $create_time);
            $map['o.create_time'] = ['between time', [$star_t, $end_t]];
            $tjwhere .= ' AND create_time >\'' . $star_t . '\' and create_time <\'' . $end_t . '\' ';
        }

        //客户消费报表
        $cust = array();
        $customers = Db::query('SELECT * FROM mbs_customers WHERE status=1' . $where . ' ORDER BY id ASC');

        $res = Db::query('SELECT customer_id,SUM(total_money) xiaofei,SUM(owe) owe FROM `mbs_xiaofei` WHERE status=1 ' . $tjwhere . ' GROUP BY customer_id  ORDER BY customer_id ASC');
        foreach ($res as $k) {
            $cust['tj'][$k['customer_id']]['xiaofei'] = $k['xiaofei'];
            $cust['tj'][$k['customer_id']]['owe'] = $k['owe'];
        }
        $res1 = Db::query('SELECT customer_id,SUM(total_money) tuifei FROM `mbs_tuifei` WHERE status=1 ' . $tjwhere . ' GROUP BY customer_id  ORDER BY customer_id ASC');
        foreach ($res1 as $k) {
            $cust['tj'][$k['customer_id']]['tuifei'] = $k['tuifei'];
        }
//        echo'<pre/>'; var_dump($customers);die();
        $this->assign('customers', $customers);
        $this->assign('cust', $cust);

        return $this->view->fetch();
    }

    public function performance()
    {
        //治疗师业绩表
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $this->assign('chain', $chain);
        if (isset($_GET['chain_id']) && intval($_GET['chain_id']) > 0) {
            $cid = intval($_GET['chain_id']);
            $this->assign('cid', $cid);
        } else {
            $cid = $_SESSION['CID'];
            $this->assign('cid', $cid);
        }

        $where = '';
        $tjwhere = '';
        if ($cid > 0) {
            $where .= ' AND chain_id=\'' . $cid . '\'';
        }
        if (!empty($_GET['keywords'])) {
            $ormap['name'] = ['like', '%' . $_GET['keywords'] . '%'];
            $ormap['phone'] = ['like', '%' . $_GET['keywords'] . '%'];
            $where .= ' AND (name LIKE \'%' . $_GET['keywords'] . '%\' ';
            $where .= ' OR phone LIKE \'%' . $_GET['keywords'] . '%\') ';
        }

        if (!empty($_GET['create_time'])) {
            $create_time = strtotime($_GET['create_time']);
            $star_t = date('Y-m-d 00:00:00', $create_time);
            $end_t = date('Y-m-d 23:59:59', $create_time);
            $map['o.create_time'] = ['between time', [$star_t, $end_t]];
            $tjwhere .= ' AND create_time >\'' . $star_t . '\' and create_time <\'' . $end_t . '\' ';
        }

        //客户消费报表
        $jstj = array();
        $jishi = Db::query('SELECT * FROM mbs_user WHERE status=1' . $where . ' ORDER BY id ASC');


        $res = Db::query('SELECT jishi_id,SUM(total_money) xiaofei,SUM(owe) owe FROM `mbs_xiaofei` WHERE status=1 ' . $tjwhere . ' GROUP BY jishi_id  ORDER BY jishi_id ASC');
        foreach ($res as $k) {
            $jstj['tj'][$k['jishi_id']]['xiaofei'] = $k['xiaofei'];
            $jstj['tj'][$k['jishi_id']]['owe'] = $k['owe'];
        }
        $res1 = Db::query('SELECT jishi_id,SUM(total_money) tuifei FROM `mbs_tuifei` WHERE status=1 ' . $tjwhere . ' GROUP BY jishi_id  ORDER BY jishi_id ASC');
        foreach ($res1 as $k) {
            $jstj['tj'][$k['jishi_id']]['tuifei'] = $k['tuifei'];

        }
//        echo'<pre/>'; var_dump($customers);die();
        $this->assign('jishi', $jishi);
        $this->assign('jstj', $jstj);
        return $this->view->fetch();
    }

}