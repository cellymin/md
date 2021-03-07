<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Package extends Base
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

        $chainlist = Db::table('mbs_chain')
            ->where('status', 1)
            ->order('id asc')
            ->select();
        $this->assign('chainlist', $chainlist);

        $map['status'] = 1;
        $re = Db::table('mbs_package')
            ->where($map)
            ->order('package_id asc')
            ->select();
        $this->assign('packagelist', $re);

        return $this->view->fetch();
    }

    public function editpackage()
    {
        $id = intval($_GET['id']);

        $chainlist = Db::table('mbs_chain')
            ->where('status', 1)
            ->order('id asc')
            ->select();
        $this->assign('chainlist', $chainlist);

        $projectlist = Db::table('mbs_project')
            ->where('status', 1)
            ->order('project_id asc')
            ->select();
        $this->assign('projectlist', $projectlist);

        if ($id > 0) {
            $packageinfo = Db::table('mbs_package')
                ->where('package_id', $id)
                ->find();
            if (!empty($packageinfo)) {

                $chainchk = $packageinfo['chain_id'];
                $this->assign('chainchk', $chainchk);


                $projectchk = $packageinfo['project_id'];
                $this->assign('projectchk', $projectchk);

            }

            $this->assign('packageinfo', $packageinfo);
        }
        return $this->view->fetch();
    }

    public function save_package()
    {
        $package_name = $_POST['package_name'];
        if (!empty($_POST['chainid']) && is_array($_POST['chainid'])) {
            $chain_id = $_POST['chainid'];
            $chain_id = implode(',', $chain_id);
            $data['chain_id'] = $chain_id;
        }
        if (!empty($_POST['demoid']) && is_array($_POST['demoid'])) {
            $project_id = $_POST['demoid'];
            $project_id = implode(',', $project_id);
            $data['project_id'] = $project_id;
        }
        $data['package_name'] = $package_name;

        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //修改
            $re = Db::table('mbs_package')
                ->where('package_id', intval($_GET['id']))
                ->update($data);
            //启动事务
            if ($re) {
                unset($data);
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);

            } else {
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
                $re = Db::table('mbs_package')
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
            $re = Db::table('mbs_project')
                ->where('project_id', $id)
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