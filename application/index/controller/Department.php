<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Department extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
        $config = config('grade');
        $zhuguan = $config['zhuguan'];//主管
        $map = array();
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
        $re = Db::table('mbs_department')
            ->alias('c')
            ->field('c.*,u.name uname')
            ->join('mbs_user u', 'u.id = c.zhuguanid', 'LEFT')
            ->where($map)
            ->select();
        $this->assign('deprtlist', $re);

        return $this->view->fetch();
    }

    public function editdepartment()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $re = Db::table('mbs_department')
                ->field('c.*,u.name uname')
                ->alias('c')
                ->join('mbs_user u', 'c.zhuguanid = u.id', 'LEFT')
                ->where('c.departmentId', $id)
                ->find();
            $this->assign('departinfo', $re);
        }
        $map['a.status'] = 1;
        $map['a.id'] = ['neq',1];
        $zglist = Db::table('mbs_user')
            ->alias('a')
            ->field('a.id,a.name,a.quanpin,a.jianxie')
            ->where($map)
            ->select();

//        $zglist = $this->zhuguanlist();
//        echo '<pre/>';var_dump($zglist);die();
        $this->assign('zglist', $zglist);
        return $this->view->fetch();
    }

    public function zhuguanlist()
    {
        $config = config('grade');
        $zhuguan = $config['zhuguan'];//主管

        $list = Db::table('mbs_user')
            ->field('a.*')
            ->alias('a')
            ->where('a.depart_id', $zhuguan)
            ->select();
        return $list;
    }

    public function save_department()
    {
        $depart_info_str = array_filter(explode(',', $_POST['depart_info']));
        $data = array();
        foreach ($depart_info_str as $k => $v) {
            $data[substr($v, 0, strrpos($v, '*'))] = substr($v, strripos($v, "*") + 1);
        }

        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_department')
                    ->where('departmentId', intval($_GET['id']))
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
            $data['createUser'] = $_SESSION['UID'];
            $data['createTime'] = date('Y-m-d h:i:s', time());
            $data['createCompany'] = 0;
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_department')
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
            $re = Db::table('mbs_user')
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
}