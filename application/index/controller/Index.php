<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\VarTest;
use think\Controller;
use think\Loader;
use think\migration\command\migrate\Status;
use think\View;
use think\Db;

class Index extends Base
{
    public function __initialize() {
        parent::__initialize();
    }
    public function index()
    {
        //判断是否登录
        if (!empty($_SESSION['UID']) && intval($_SESSION['UID']) > 0) {
            if(!empty($_GET['shichang'])){
                $shichang = trim($_GET['shichang']);
            }
            if(!empty($_GET['chain_id'])) {
                $chain_id = intval($_GET['chain_id']);
            }

            $this->assign('today', date('Y-m-d'));
            $today = date('Y-m-d');
            $yestoday = date('Y-m-d', strtotime('-1 day'));
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $houtian = date('Y-m-d', strtotime('+2 day'));
            $nianyueri = date('Y年m月d日', time());
            $xingqinum = date('N', time());
            $tianshu = date('d');
            $premonth = date('Y-m-d', strtotime('-1 month'));
            $xfwhere = ' AND status=1 ';
            $tfwhere = ' AND status=1 ';
            $hkwhere = ' ';
            $dswhere = ' AND status=1 ';
            $tbxfwhere = '';
            $tbtfwhere = '';
            //今天
            $todayxfwhere = '';
            $todaytfwhere = '';
            $todayhkwhere = '';
            $todaydswhere = '';
            //昨天
            $yestodayxfwhere = '';
            $yestodaytfwhere = '';
            $yestodayhkwhere = '';
            $yestodaydswhere = '';
            //本月
            $monthxfwhere = '';
            $monthtfwhere = '';
            $monthhkwhere = '';
            $monthdswhere = '';
            //上月
            $premonthxfwhere = '';
            $premonthtfwhere = '';
            $premonthhkwhere = '';
            $premonthdswhere = '';

            //当日
            $today_s = $today.' 00:00:00';
            $today_e = $today.' 23:59:59';
            $todayxfwhere .= '  AND create_time >\'' . $today_s . '\' and create_time < \'' . $today_e . '\'';
            $todaytfwhere .= '  AND create_time >\'' . $today_s . '\' and create_time < \'' . $today_e . '\'';
            $todayhkwhere .= '  AND h.create_time >\'' . $today_s . '\' and h.create_time < \'' . $today_e . '\'';
            $todaydswhere .= '  AND create_time >\'' . $today_s . '\' and create_time < \'' . $today_e . '\'';
            //昨日
            $yestoday_s = $yestoday.' 00:00:00';
            $yestoday_e = $yestoday.' 23:59:59';
            $yestodayxfwhere .= '  AND create_time >\'' . $yestoday_s . '\' and create_time < \'' . $yestoday_e . '\'';
            $yestodaytfwhere .= '  AND create_time >\'' . $yestoday_s . '\' and create_time < \'' . $yestoday_e . '\'';
            $yestodayhkwhere .= '  AND h.create_time >\'' . $yestoday_s . '\' and h.create_time < \'' . $yestoday_e . '\'';
            $yestodaydswhere .= '  AND create_time >\'' . $yestoday_s . '\' and create_time < \'' . $yestoday_e . '\'';
            //本月
            $month_s = date('Y-m-01').' 00:00:00';
            $month_e = date('Y-m-d').' 23:59:59';
            $monthxfwhere .= '  AND create_time >\'' . $month_s . '\' and create_time < \'' . $month_e . '\'';
            $monthtfwhere .= '  AND create_time >\'' . $month_s . '\' and create_time < \'' . $month_e . '\'';
            $monthhkwhere .= '  AND h.create_time >\'' . $month_s . '\' and h.create_time < \'' . $month_e . '\'';
            $monthdswhere .= '  AND create_time >\'' . $month_s . '\' and create_time < \'' . $month_e . '\'';
            //上月
            $premonth_s = date('Y-m-01',strtotime($premonth)).' 00:00:00';
            $premonth_e = date("Y-m-d", strtotime(-$tianshu.' day')).' 23:59:59';
            $premonthxfwhere .= '  AND create_time >\'' . $premonth_s . '\' and create_time < \'' . $premonth_e . '\'';
            $premonthtfwhere .= '  AND create_time >\'' . $premonth_s . '\' and create_time < \'' . $premonth_e . '\'';
            $premonthhkwhere .= '  AND h.create_time >\'' . $premonth_s . '\' and h.create_time < \'' . $premonth_e . '\'';
            $premonthdswhere .= '  AND create_time >\'' . $premonth_s . '\' and create_time < \'' . $premonth_e . '\'';
            //判断身份
            $config = config('group');
            if ($_SESSION['GID'] == $config['dean']) {
                //院长
                $this->assign('sign', 2);
            } else if ($_SESSION['GID'] == $config['admin']) {
                //超级管理员
                $this->assign('sign', 1);
            }
            if ($_SESSION['GID'] == $config['chief_dean'] || $_SESSION['GID']==1) {
                //总院长/管理员
                if(!empty($shichang)){
                    $scids=[];
                    $shichangre = Db::table('mbs_chain')
                        ->field('id')
                        ->where('status', 1)
                        ->where('city', $shichang)
                        ->select();
                    foreach ($shichangre as $k){
                        $scids[] = $k['id'];
                    }
                    if(!empty($scids)){
                        $xfwhere .= ' AND serchain_id in( ' .implode(',',$scids).')';
                        $tfwhere .= ' AND serchain_id in( ' .implode(',',$scids).')';
                        $hkwhere .= ' AND x.serchain_id in( ' .implode(',',$scids).')';
                        $dswhere .= ' AND xfchain_id in( ' .implode(',',$scids).')';
                        $tbxfwhere .= ' AND serchain_id in( ' .implode(',',$scids).')';
                        $tbtfwhere .= ' AND serchain_id in( ' .implode(',',$scids).')';
                    }
                }
                if(!empty($chain_id)){
                    $xfwhere .= ' AND serchain_id ='.$chain_id;
                    $tfwhere .= ' AND serchain_id ='.$chain_id;
                    $hkwhere .= ' AND x.serchain_id ='.$chain_id;
                    $dswhere .= ' AND xfchain_id ='.$chain_id;
                    $tbxfwhere .= ' AND serchain_id ='.$chain_id;
                    $tbtfwhere .= ' AND serchain_id ='.$chain_id;

                }

                $this->assign('sign', 1);
            }
            if(isset($_SESSION['CID']) && $_SESSION['CID'] == $config['dean']){
                //院长 可以看所属门店的所有消费
                $xfwhere .= ' AND serchain_id = ' .$_SESSION['CID'];
                $tfwhere .= ' AND serchain_id = ' .$_SESSION['CID'];
                $hkwhere .= ' AND x.serchain_id = ' .$_SESSION['CID'];
                $dswhere .= ' AND xfchain_id = ' .$_SESSION['CID'];
                $tbxfwhere .= ' AND serchain_id  = ' .$_SESSION['CID'];
                $tbtfwhere .= ' AND serchain_id  = ' .$_SESSION['CID'];

            }else{
                if((isset($_SESSION['CID']) && $_SESSION['GID'] != $config['chief_dean']) && $_SESSION['GID'] != 1 ){

                    //普通职员只能看自己服务的客户总消费
                    $custidsarr = [];
                    $custidsstr = '';
                    $custids = Db::table('mbs_all_assessment')
                        ->field('customer_id')
                        ->distinct(true)
                        ->where('server_id',$_SESSION['UID'])
                        ->select();
                  foreach ($custids as $k){
                      if($k['customer_id']>0){
                          $custidsarr[] = $k['customer_id']  ;
                      }
                  }
                  $custidsstr =  implode(',',array_unique($custidsarr));
                    $xfwhere .= ' AND customer_id in( ' .$custidsstr.')';
                    $tfwhere .= ' AND customer_id in( ' .$custidsstr.')';
                    $hkwhere .= ' AND h.customer_id in( ' .$custidsstr.')';
                    $dswhere .= ' AND customer_id in( ' .$custidsstr.')';
                    $tbxfwhere .= ' AND customer_id in( ' .$custidsstr.')';
                    $tbtfwhere .= ' AND customer_id in( ' .$custidsstr.')';

                }
            }
            //所有市场
            $shichangre = Db::table('mbs_chain')
                ->field('id,city')
                ->where('status', 1)
                ->group('city')
                ->select();
            foreach ($shichangre as $k => $v) {
                $shichanglist[$v['id']] = $v;
            }
            $this->assign('shichanglist', $shichanglist);
//            echo '<pre/>';
//            var_dump($shichangre);die();
            $chain = Db::table('mbs_chain')
                ->where('status', 1)
                ->select();
            $chainlist = array();
            foreach ($chain as $k => $v) {
                $chainlist[$v['id']] = $v;
            }
            $this->assign('chain', $chain);

            //当日消费，欠费，借店支付，$dayxfarr//   还款，退费，代店支付
            $dayarr = [];
            $yestodayarr = [];
            $montharr = [];
            $premontharr = [];
            $chainids = [];
            $dayxflist = Db::query('SELECT total_money xiaofei,serchain_id,xfchain_id,if_local,owe FROM mbs_xiaofei WHERE 1=1   ' . $xfwhere.$todayxfwhere . ' GROUP BY serchain_id ');
            $daydslist = Db::query('SELECT SUM(total_money) daishou,serchain_id,xfchain_id,if_local  FROM mbs_xiaofei WHERE 1=1 AND if_local=2 AND serchain_id!=xfchain_id  ' . $dswhere.$todaydswhere . ' GROUP BY serchain_id ');
            $daytflist = Db::query('SELECT SUM(total_money) tuifei,serchain_id FROM mbs_tuifei WHERE 1=1   ' . $tfwhere.$todaytfwhere . ' GROUP BY serchain_id ');
            $dayhklist = Db::query('SELECT SUM(h.huan_money) huankuan,x.serchain_id serchain_id FROM mbs_xfhuankuan h INNER JOIN mbs_xiaofei x WHERE h.status=1 and x.status=1  ' .$hkwhere.$todayhkwhere . ' GROUP BY x.serchain_id ');

            foreach ($dayxflist as $k=>$v){
                //消费
                $chainids[]= $v['serchain_id'];
                if(empty($dayarr[$v['serchain_id']]['xiaofei'])){
                    $dayarr[$v['serchain_id']]['xiaofei'] = $v['xiaofei'];
                }else{
                    $dayarr[$v['serchain_id']]['xiaofei'] = $dayarr[$v['serchain_id']]['xiaofei'] + $v['xiaofei'];
                }
                //欠款
                if(empty($dayarr[$v['serchain_id']]['qiankuan'])){
                    $dayarr[$v['serchain_id']]['qiankuan'] = $v['owe'];
                }else{
                    $dayarr[$v['serchain_id']]['qiankuan'] = $dayarr[$v['serchain_id']]['qiankuan'] + $v['owe'];
                }
                //借店
                if($v['serchain_id']!=$v['xfchain_id'] && $v['if_local']==2){
                    if(empty($dayarr[$v['serchain_id']]['jiedian'])){
                        $dayarr[$v['serchain_id']]['jiedian'] = $v['xiaofei'];
                    }else{
                        $dayarr[$v['serchain_id']]['jiedian'] = $dayarr[$v['serchain_id']]['jiedian'] + $v['xiaofei'];
                    }
                }
            }
            foreach ($daydslist as $k=>$v){
                $chainids[]= $v['xfchain_id'];
                if(empty( $dayarr[$v['xfchain_id']]['daishou'])){
                    $dayarr[$v['xfchain_id']]['daishou'] = $v['daishou'];
                }else{
                    $dayarr[$v['xfchain_id']]['daishou'] = $v['daishou'] + $dayarr[$v['xfchain_id']]['daishou'];
                }
            }
            foreach ($daytflist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $dayarr[$v['serchain_id']]['tuifei'])){
                    $dayarr[$v['serchain_id']]['tuifei'] = $v['tuifei'];
                }else{
                    $dayarr[$v['serchain_id']]['tuifei'] = $v['tuifei'] + $dayarr[$v['serchain_id']]['tuifei'];
                }
            }
            foreach ($dayhklist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $dayarr[$v['serchain_id']]['huankuan'])){
                    $dayarr[$v['serchain_id']]['huankuan'] = $v['huankuan'];
                }else{
                    $dayarr[$v['serchain_id']]['huankuan'] = $v['huankuan'] + $dayarr[$v['serchain_id']]['huankuan'];
                }
            }
            $this->assign('dayarr', $dayarr);

            //昨日消费，欠费，退费，还款，借店支付，代店支付
            $yestodayxflist = Db::query('SELECT total_money xiaofei,serchain_id,xfchain_id,if_local,owe FROM mbs_xiaofei WHERE 1=1   ' . $xfwhere.$yestodayxfwhere . ' GROUP BY serchain_id ');
            $yestodaydslist = Db::query('SELECT SUM(total_money) daishou,serchain_id,xfchain_id,if_local  FROM mbs_xiaofei WHERE 1=1 AND if_local=2 AND serchain_id!=xfchain_id  ' . $dswhere.$yestodaydswhere . ' GROUP BY serchain_id ');
            $yestodaytflist = Db::query('SELECT SUM(total_money) tuifei,serchain_id FROM mbs_tuifei WHERE 1=1   ' . $tfwhere.$yestodaytfwhere . ' GROUP BY serchain_id ');
            $yestodayhklist = Db::query('SELECT SUM(h.huan_money) huankuan,x.serchain_id serchain_id FROM mbs_xfhuankuan h INNER JOIN mbs_xiaofei x WHERE h.status=1 and x.status=1  ' .$hkwhere.$yestodayhkwhere . ' GROUP BY x.serchain_id ');
            foreach ($yestodayxflist as $k=>$v){
                //消费
                $chainids[]= $v['serchain_id'];
                if(empty($yestodayarr[$v['serchain_id']]['xiaofei'])){
                    $yestodayarr[$v['serchain_id']]['xiaofei'] = $v['xiaofei'];
                }else{
                    $yestodayarr[$v['serchain_id']]['xiaofei'] = $yestodayarr[$v['serchain_id']]['xiaofei'] + $v['xiaofei'];
                }
                //欠款
                if(empty($yestodayarr[$v['serchain_id']]['qiankuan'])){
                    $yestodayarr[$v['serchain_id']]['qiankuan'] = $v['owe'];
                }else{
                    $yestodayarr[$v['serchain_id']]['qiankuan'] = $yestodayarr[$v['serchain_id']]['qiankuan'] + $v['owe'];
                }
                //借店
                if($v['serchain_id']!=$v['xfchain_id'] && $v['if_local']==2){
                    if(empty($yestodayarr[$v['serchain_id']]['jiedian'])){
                        $yestodayarr[$v['serchain_id']]['jiedian'] = $v['xiaofei'];
                    }else{
                        $yestodayarr[$v['serchain_id']]['jiedian'] = $yestodayarr[$v['serchain_id']]['jiedian'] + $v['xiaofei'];
                    }
                }
            }
            foreach ($yestodaydslist as $k=>$v){
                $chainids[]= $v['xfchain_id'];
                if(empty( $yestodayarr[$v['xfchain_id']]['daishou'])){
                    $yestodayarr[$v['xfchain_id']]['daishou'] = $v['daishou'];
                }else{
                    $yestodayarr[$v['xfchain_id']]['daishou'] = $v['daishou'] + $yestodayarr[$v['xfchain_id']]['daishou'];
                }
            }
            foreach ($yestodaytflist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $daydsarr[$v['serchain_id']]['tuifei'])){
                    $yestodayarr[$v['serchain_id']]['tuifei'] = $v['tuifei'];
                }else{
                    $yestodayarr[$v['serchain_id']]['tuifei'] = $v['tuifei'] + $yestodayarr[$v['serchain_id']]['tuifei'];
                }
            }
            foreach ($yestodayhklist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $yestodayarr[$v['serchain_id']]['huankuan'])){
                    $yestodayarr[$v['serchain_id']]['huankuan'] = $v['huankuan'];
                }else{
                    $yestodayarr[$v['serchain_id']]['huankuan'] = $v['huankuan'] + $yestodayarr[$v['serchain_id']]['huankuan'];
                }
            }
            $this->assign('yestodayarr', $yestodayarr);
            //本月消费，欠费，退费，还款，借店支付，代店支付
            $monthxflist = Db::query('SELECT total_money xiaofei,serchain_id,xfchain_id,if_local,owe FROM mbs_xiaofei WHERE 1=1   ' . $xfwhere.$monthxfwhere . ' GROUP BY serchain_id ');
            $monthdslist = Db::query('SELECT SUM(total_money) daishou,serchain_id,xfchain_id,if_local  FROM mbs_xiaofei WHERE 1=1 AND if_local=2 AND serchain_id!=xfchain_id  ' . $dswhere.$monthdswhere . ' GROUP BY serchain_id ');
            $monthtflist = Db::query('SELECT SUM(total_money) tuifei,serchain_id FROM mbs_tuifei WHERE 1=1   ' . $tfwhere.$monthtfwhere . ' GROUP BY serchain_id ');
            $monthhklist = Db::query('SELECT SUM(h.huan_money) huankuan,x.serchain_id serchain_id FROM mbs_xfhuankuan h INNER JOIN mbs_xiaofei x WHERE h.status=1 and x.status=1  ' .$hkwhere.$monthhkwhere . ' GROUP BY x.serchain_id ');
            foreach ($monthxflist as $k=>$v){
                //消费
                $chainids[]= $v['serchain_id'];
                if(empty($montharr[$v['serchain_id']]['xiaofei'])){
                    $montharr[$v['serchain_id']]['xiaofei'] = $v['xiaofei'];
                }else{
                    $montharr[$v['serchain_id']]['xiaofei'] = $montharr[$v['serchain_id']]['xiaofei'] + $v['xiaofei'];
                }
                //欠款
                if(empty($montharr[$v['serchain_id']]['qiankuan'])){
                    $montharr[$v['serchain_id']]['qiankuan'] = $v['owe'];
                }else{
                    $montharr[$v['serchain_id']]['qiankuan'] = $montharr[$v['serchain_id']]['qiankuan'] + $v['owe'];
                }
                //借店
                if($v['serchain_id']!=$v['xfchain_id'] && $v['if_local']==2){
                    if(empty($montharr[$v['serchain_id']]['jiedian'])){
                        $montharr[$v['serchain_id']]['jiedian'] = $v['xiaofei'];
                    }else{
                        $montharr[$v['serchain_id']]['jiedian'] = $montharr[$v['serchain_id']]['jiedian'] + $v['xiaofei'];
                    }
                }
            }
            foreach ($monthdslist as $k=>$v){
                $chainids[]= $v['xfchain_id'];
                if(empty( $montharr[$v['xfchain_id']]['daishou'])){
                    $montharr[$v['xfchain_id']]['daishou'] = $v['daishou'];
                }else{
                    $montharr[$v['xfchain_id']]['daishou'] = $v['daishou'] + $montharr[$v['xfchain_id']]['daishou'];
                }
            }
            foreach ($monthtflist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $montharr[$v['serchain_id']]['tuifei'])){
                    $montharr[$v['serchain_id']]['tuifei'] = $v['tuifei'];
                }else{
                    $montharr[$v['serchain_id']]['tuifei'] = $v['tuifei'] + $montharr[$v['serchain_id']]['tuifei'];
                }
            }
            foreach ($monthhklist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $montharr[$v['serchain_id']]['huankuan'])){
                    $montharr[$v['serchain_id']]['huankuan'] = $v['huankuan'];
                }else{
                    $montharr[$v['serchain_id']]['huankuan'] = $v['huankuan'] + $montharr[$v['serchain_id']]['huankuan'];
                }
            }
            $this->assign('montharr', $montharr);
            //上月消费，欠费，退费，还款，借店支付，代店支付
            $premonthxflist = Db::query('SELECT total_money xiaofei,serchain_id,xfchain_id,if_local,owe FROM mbs_xiaofei WHERE 1=1   ' . $xfwhere.$premonthxfwhere . ' GROUP BY serchain_id ');
            $premonthdslist = Db::query('SELECT SUM(total_money) daishou,serchain_id,xfchain_id,if_local  FROM mbs_xiaofei WHERE 1=1 AND if_local=2 AND serchain_id!=xfchain_id  ' . $dswhere.$premonthdswhere . ' GROUP BY serchain_id ');
            $premonthtflist = Db::query('SELECT SUM(total_money) tuifei,serchain_id FROM mbs_tuifei WHERE 1=1   ' . $tfwhere.$premonthtfwhere . ' GROUP BY serchain_id ');
            $premonthhklist = Db::query('SELECT SUM(h.huan_money) huankuan,x.serchain_id serchain_id FROM mbs_xfhuankuan h INNER JOIN mbs_xiaofei x WHERE h.status=1 and x.status=1  ' .$hkwhere.$premonthhkwhere . ' GROUP BY x.serchain_id ');
            foreach ($premonthxflist as $k=>$v){
                //消费
                $chainids[]= $v['serchain_id'];
                if(empty($premontharr[$v['serchain_id']]['xiaofei'])){
                    $premontharr[$v['serchain_id']]['xiaofei'] = $v['xiaofei'];
                }else{
                    $premontharr[$v['serchain_id']]['xiaofei'] = $premontharr[$v['serchain_id']]['xiaofei'] + $v['xiaofei'];
                }
                //欠款
                if(empty($premontharr[$v['serchain_id']]['qiankuan'])){
                    $premontharr[$v['serchain_id']]['qiankuan'] = $v['owe'];
                }else{
                    $premontharr[$v['serchain_id']]['qiankuan'] = $premontharr[$v['serchain_id']]['qiankuan'] + $v['owe'];
                }
                //借店
                if($v['serchain_id']!=$v['xfchain_id'] && $v['if_local']==2){
                    if(empty($premontharr[$v['serchain_id']]['jiedian'])){
                        $premontharr[$v['serchain_id']]['jiedian'] = $v['xiaofei'];
                    }else{
                        $premontharr[$v['serchain_id']]['jiedian'] = $premontharr[$v['serchain_id']]['jiedian'] + $v['xiaofei'];
                    }
                }
            }
            foreach ($premonthdslist as $k=>$v){
                $chainids[]= $v['xfchain_id'];
                if(empty( $premontharr[$v['xfchain_id']]['daishou'])){
                    $premontharr[$v['xfchain_id']]['daishou'] = $v['daishou'];
                }else{
                    $premontharr[$v['xfchain_id']]['daishou'] = $v['daishou'] + $premontharr[$v['xfchain_id']]['daishou'];
                }
            }
            foreach ($premonthtflist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $premontharr[$v['serchain_id']]['tuifei'])){
                    $premontharr[$v['serchain_id']]['tuifei'] = $v['tuifei'];
                }else{
                    $premontharr[$v['serchain_id']]['tuifei'] = $v['tuifei'] + $premontharr[$v['serchain_id']]['tuifei'];
                }
            }
            foreach ($premonthhklist as $k=>$v){
                $chainids[]= $v['serchain_id'];
                if(empty( $premontharr[$v['serchain_id']]['huankuan'])){
                    $premontharr[$v['serchain_id']]['huankuan'] = $v['huankuan'];
                }else{
                    $premontharr[$v['serchain_id']]['huankuan'] = $v['huankuan'] + $premontharr[$v['serchain_id']]['huankuan'];
                }
            }
            $this->assign('premontharr', $premontharr);
            $chainids = array_unique($chainids);
            sort($chainids);
            if(!empty($chainids)){
                $chainidsstr = implode(',',$chainids);
                $chainlist = Db::table('mbs_chain')
                    ->where('status',1)
                    ->where('id in ('.$chainidsstr.')')
                    ->select();
                $this->assign('chainlist', $chainlist);
            }
            //图表
            $tenxiaofeiarr = [];
            $tenowearr = [];
            $tentuifeiarr = [];
            //截止今天前十天每天的消费数据
            $date_riqi = [];
            for($i=0;$i<10;$i++){
                $date_riqi[9-$i] = '\''.date('Y-m-d',strtotime('-'.$i.' day')).'\'';
            }
            sort($date_riqi);
//echo substr($date_riqi[0],1,strlen($date_riqi[0])-2);die();
            $this->assign('date_riqi', implode(',',$date_riqi));
            $this->assign('date_riqi_s',substr($date_riqi[0],1,strlen($date_riqi[0]-2)));
            $this->assign('date_riqi_e',substr($date_riqi[9],1,strlen($date_riqi[0]-2)));
            $tenxiaofeire = Db::query('SELECT DATE_FORMAT(create_time, \'%Y-%m-%d\') AS time, sum(total_money) xiaofei,sum(owe) owe FROM mbs_xiaofei WHERE status=1 '.$tbxfwhere.' AND create_time > \''. substr($date_riqi[0],1,strlen($date_riqi[0])-2).' 00:00:00\' AND create_time < \''.substr($date_riqi[9],1,strlen($date_riqi[9])-2).' 23:59:59\'  GROUP BY time ORDER BY create_time ASC');
//            echo '<pre/>';var_dump($date_riqi);die();

            foreach ($tenxiaofeire as $k){
                switch($k['time']){
                    case substr($date_riqi[0],1,strlen($date_riqi[0])-2):
                        $tenxiaofeiarr[0] = $k['xiaofei'];
                        $tenowearr[0] = $k['owe'];
                        break;
                    case substr($date_riqi[1],1,strlen($date_riqi[1])-2):
                        $tenxiaofeiarr[1] = $k['xiaofei'];
                        $tenowearr[1] = $k['owe'];
                        break;
                    case substr($date_riqi[2],1,strlen($date_riqi[2])-2):
                        $tenxiaofeiarr[2] = $k['xiaofei'];
                        $tenowearr[2] = $k['owe'];
                        break;
                    case substr($date_riqi[3],1,strlen($date_riqi[3])-2):
                        $tenxiaofeiarr[3] = $k['xiaofei'];
                        $tenowearr[3] = $k['owe'];
                        break;
                    case substr($date_riqi[4],1,strlen($date_riqi[4])-2):
                        $tenxiaofeiarr[4] = $k['xiaofei'];
                        $tenowearr[4] = $k['owe'];
                        break;
                    case substr($date_riqi[5],1,strlen($date_riqi[5])-2):
                        $tenxiaofeiarr[5] = $k['xiaofei'];
                        $tenowearr[5] = $k['owe'];
                        break;
                    case substr($date_riqi[6],1,strlen($date_riqi[6])-2):
                        $tenxiaofeiarr[6] = $k['xiaofei'];
                        $tenowearr[6] = $k['owe'];
                        break;
                    case substr($date_riqi[7],1,strlen($date_riqi[7])-2):
                        $tenxiaofeiarr[7] = $k['xiaofei'];
                        $tenowearr[7] = $k['owe'];
                        break;
                    case substr($date_riqi[8],1,strlen($date_riqi[8])-2):
                        $tenxiaofeiarr[8] = $k['xiaofei'];
                        $tenowearr[8] = $k['owe'];
                        break;
                    case substr($date_riqi[9],1,strlen($date_riqi[9])-2):
                        $tenxiaofeiarr[9] = $k['xiaofei'];
                        $tenowearr[9] = $k['owe'];
                        break;
                }
            }
            //截止今天前十天每天的退费数据
            $tentuifeire = Db::query('SELECT DATE_FORMAT(create_time, \'%Y-%m-%d\') AS time, sum(total_money) tuifei FROM 	mbs_tuifei WHERE  status=1 '.$tbtfwhere.' AND create_time > \''. substr($date_riqi[0],1,strlen($date_riqi[0])-2).' 00:00:00\' AND create_time < \''.substr($date_riqi[9],1,strlen($date_riqi[9])-2).' 23:59:59\'  GROUP BY time');
            foreach ($tentuifeire as $k){
                switch($k['time']){
                    case substr($date_riqi[0],1,strlen($date_riqi[0])-2):
                        $tentuifeiarr[0] = $k['tuifei'];
                        break;
                    case substr($date_riqi[1],1,strlen($date_riqi[1])-2):
                        $tentuifeiarr[1] = $k['tuifei'];
                        break;
                    case substr($date_riqi[2],1,strlen($date_riqi[2])-2):
                        $tentuifeiarr[2] = $k['tuifei'];
                        break;
                    case substr($date_riqi[3],1,strlen($date_riqi[3])-2):
                        $tentuifeiarr[3] = $k['tuifei'];
                        break;
                    case substr($date_riqi[4],1,strlen($date_riqi[4])-2):
                        $tentuifeiarr[4] = $k['tuifei'];
                        break;
                    case substr($date_riqi[5],1,strlen($date_riqi[5])-2):
                        $tentuifeiarr[5] = $k['tuifei'];
                        break;
                    case substr($date_riqi[6],1,strlen($date_riqi[6])-2):
                        $tentuifeiarr[6] = $k['tuifei'];
                        break;
                    case substr($date_riqi[7],1,strlen($date_riqi[7])-2):
                        $tentuifeiarr[7] = $k['tuifei'];
                        break;
                    case substr($date_riqi[8],1,strlen($date_riqi[8])-2):
                        $tentuifeiarr[8] = $k['tuifei'];
                        break;
                    case substr($date_riqi[9],1,strlen($date_riqi[9])-2):
                        $tentuifeiarr[9] = $k['tuifei'];
                        break;
                }
            }


            for($i=0;$i<10;$i++){
                if(empty($tenxiaofeiarr[$i])) {
                    $tenxiaofeiarr[$i] = 0;
                }
                if(empty($tenowearr[$i])){
                    $tenowearr[$i] = 0;
                }
                if(empty($tentuifeiarr[$i])){
                    $tentuifeiarr[$i] = 0;
                }
            }
            ksort($tenxiaofeiarr);
            ksort($tenowearr);
            ksort($tentuifeiarr);
            $this->assign('tenxiaofeiarr', implode(',',$tenxiaofeiarr));
            $this->assign('tenowearr', implode(',',$tenowearr));
            $this->assign('tentuifeiarr', implode(',',$tentuifeiarr));
            $xiaofeiMax = max($tenxiaofeiarr);
            $tuifeiMax = max($tentuifeiarr);
            $oweMax = max($tenowearr);
            $max = max([$xiaofeiMax,$tuifeiMax,$oweMax]);
            if($max>1000 && $max<=10000){
                $left = 45;
                $this->assign('left',$left);
            }else if($max>10000 && $max<=100000){
                $left = 60;
                $this->assign('left',$left);
            }
            else if($max>100000 && $max<=1000000){
                $left = 80;
                $this->assign('left',$left);
            }else if($max<1000){
                $left = 30;
                $this->assign('left',$left);
            }
            return $this->view->fetch();
        } else {
            return redirect('/index.php/index/login');
        }
    }
}
