<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Fromway extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
        $map = array();
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

        $re = Db::table('mbs_fromway')
            ->where($map)
            ->order('fromway_id asc')
            ->select();
        $this->assign('fromwaylist', $re);

        return $this->view->fetch();
    }

    public function editfromway()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $fromwayinfo = Db::table('mbs_fromway')
                ->where('fromway_id', $id)
                ->find();
            $this->assign('fromwayinfo', $fromwayinfo);
        }
        return $this->view->fetch();
    }

    public function save_fromway()
    {
        $fromway_name = $_POST['fromway_name'];
//        $fromway_desc = $_POST['fromway_desc'];
        $data['fromway_name'] = $fromway_name;
//        $data['fromway_desc'] = $fromway_desc;


        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //修改
            $re = Db::table('mbs_fromway')
                ->where('fromway_id', intval($_GET['id']))
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
            $data['create_time'] = date('Y-m-d H:m:s',time());
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_fromway')
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
            $re = Db::table('mbs_fromway')
                ->where('fromway_id', $id)
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