<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Payway extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
        if(isset($_GET['paytype'])){
            $paytype = intval($_GET['paytype']);
        }else{
            $paytype = 0;
        }


        $map = array();
        $where = '';
        $config = config('grade');
        //总院长
        $cheif = $config['chief_dean'];
        //院长
        $dean = intval($config['dean']);
        if (!empty($_SESSION['CID'])) {
            if ($_SESSION['GID'] == $cheif || $_SESSION['GID'] == 1) {//总院长

            } else {
                if ($_SESSION['CID'] == $dean) {
                    $map['chain_id'] = $dean;
                } else {
                    $map['id'] = $_SESSION['UID'];
                }
            }

        } else {
            if ($_SESSION['GID'] == 1) {
                //超级管理员

            } else {
                return redirect('/index.php/index/login');
            }
        }
        $map['status'] = 1;
        if($paytype>0){
            $map['paytype'] = $paytype;
            $where = ' AND paytype='.$paytype ;
        }
        $total = Db::table('mbs_payway')
            ->where($map)
            ->count();
        $pagesize = 20;
        Loader::import('page.Page', EXTEND_PATH, '.class.php');
        $page = new \page($total, $pagesize);
        $paywaylist = Db::query('SELECT * FROM mbs_payway WHERE status=1 '.$where.' ORDER BY payway_id ASC '.$page->limit);
        $this->assign('paywaylist', $paywaylist);
        $down_page = $page->fpage();
        $this->assign('page', $down_page);
        $this->assign('paytype', $paytype);
        return $this->view->fetch();
    }

    public function editpayway()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $paywayinfo = Db::table('mbs_payway')
                ->where('payway_id', $id)
                ->find();
            $this->assign('paywayinfo', $paywayinfo);
        }
        return $this->view->fetch();
    }

    public function save_payway()
    {
        $payway_name = $_POST['payway_name'];
        $payway_desc = $_POST['payway_desc'];
        $paytype = $_POST['paytype'];
        $data['payway_name'] = $payway_name;
        $data['payway_desc'] = $payway_desc;
        $data['paytype'] = $paytype;


        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //修改
            $re = Db::table('mbs_payway')
                ->where('payway_id', intval($_GET['id']))
                ->update($data);
            if($re){
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);
            }else{
                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        } else {
            //创建
            $data['create_user'] = $_SESSION['UID'];
            $data['user_name'] = $_SESSION['UNAME'];
            $data['create_time'] = date('Y-m-d H:m:s');
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_payway')
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
        $id = intval($_POST['id']);

        if ($id > 0) {
            $re = Db::table('mbs_payway')
                ->where('payway_id', $id)
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

}