<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Rolelist extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
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
        $grouplist = Db::table('mbs_user_group')
            ->select();
        if(!isset($_GET['group_id']) || !intval($_GET['group_id'])>0){
            $map['group_id'] = $grouplist[0]['group_id'];
        }else{
            $map['group_id'] = intval($_GET['group_id']);
        }

        $menulist = Db::table('mbs_menu_url')
            ->where('online', 1)
            ->where('is_show', 1)
            ->select();
        $basemenu = $this->get_menu_list();
        $this->assign('basemenu', $basemenu);

        $group_role = Db::table('mbs_user_group')
            ->where($map)
            ->find();
        $this->assign('group_role', $group_role);

//        echo '<pre/>';
//        var_dump($basemenu);
//        die();

        $this->assign('grouplist', $grouplist);

        return $this->view->fetch();
    }

    public function editpannel()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $re = Db::table('mbs_menu_url')
                ->field('p.*,m.module_name,c.menu_name fname')
                ->alias('p')
                ->join('mbs_module m', 'p.module_id = m.module_id', 'LEFT')
                ->join('mbs_menu_url c', 'p.father_menu = c.menu_id', 'LEFT')
                ->where('p.menu_id', $id)
                ->find();
            $this->assign('pannelinfo', $re);
        }
        $module_list = $this->module_list();
        $this->assign('module_list', $module_list);
        $menulist = $this->menu_list();
//        echo '<pre/>';var_dump($re);die();
        $this->assign('menulist', $menulist);
        return $this->view->fetch();
    }

    public function module_list()
    {
        $module_list = Db::table('mbs_module')
            ->field('a.*')
            ->alias('a')
            ->where('a.online', '1')
            ->select();
        return $module_list;
    }

    public function menu_list()
    {
        $menu = array();
        $re = Db::table('mbs_menu_url')
            ->field('p.*,m.module_name,c.menu_id fid,c.menu_name fname')
            ->alias('p')
            ->join('mbs_module m', 'p.module_id = m.module_id', 'LEFT')
            ->join('mbs_menu_url c', 'p.father_menu = c.menu_id', 'LEFT')
            ->where('p.is_show', 1)
            ->where('m.online', 1)
            ->select();
        foreach ($re as $k => $v) {
            if ($v['fid']) {
                //有父级
                $menu[$v['module_id']]['module_id'] = $v['module_id'];
                $menu[$v['module_id']]['module_name'] = $v['module_name'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['fid'] = $v['fid'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['fname'] = $v['fname'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['secchild'][] = $v;
            } else {
                //没有父级
                $menu[$v['module_id']]['module_id'] = $v['module_id'];
                $menu[$v['module_id']]['module_name'] = $v['module_name'];
                $menu[$v['module_id']]['childfir'][] = $v;
            }
        }
        return $menu;
    }

    public function saverole()
    {
        $role_str = $_POST['role'];
        $data = array();
        $re = Db::table('mbs_user_group')
            ->where('group_id', intval($_POST['gid']))
            ->update(['group_role'=>$role_str]);
        $re = Db::table('mbs_user_group')
            ->where('group_id', intval($_POST['gid']))
            ->update(['group_role'=>$role_str]);

        if (isset($_POST['gid']) && intval($_POST['gid']) > 0) {
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_user_group')
                    ->where('group_id', intval($_POST['gid']))
                    ->update(['group_role'=>$role_str]);
                Db::commit();//提交事务
                $data['code'] = 200;
                $data['msg'] = '保存成功';
                return json_encode($data);

            } catch (\Exception $e) {
                Db::rollback();//回滚事务

                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        } else{
            $data['code'] = 0;
            $data['msg'] = '保存失败';
            return json_encode($data);
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