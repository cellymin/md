<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Pannel extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }
    public function index(){
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

        $map['p.online'] = 1;
        $map['p.is_show'] = 1;
        $re = Db::table('mbs_menu_url')
            ->field('p.*,m.module_name,c.menu_name fname')
            ->alias('p')
            ->join('mbs_module m','p.module_id = m.module_id','LEFT')
            ->join('mbs_menu_url c','p.father_menu = c.menu_id','LEFT')
            ->where($map)
            ->select();
//        echo '<pre/>>';
//        var_dump($re);
//        die();
        $this->assign('menulist',$re);

        return $this->view->fetch();
    }
    public function editpannel(){
        $id = intval($_GET['id']);
        if($id>0){
            $re = Db::table('mbs_menu_url')
                ->field('p.*,m.module_name,c.menu_name fname')
                ->alias('p')
                ->join('mbs_module m','p.module_id = m.module_id','LEFT')
                ->join('mbs_menu_url c','p.father_menu = c.menu_id','LEFT')
                ->where('p.menu_id',$id)
                ->find();
            $this->assign('pannelinfo',$re);
        }
        $module_list = $this->module_list();
        $this->assign('module_list',$module_list);
        $menulist = $this->menu_list();
//        echo '<pre/>';var_dump($re);die();
        $this->assign('menulist',$menulist);
        return $this->view->fetch();
    }
    public function module_list(){
        $module_list = Db::table('mbs_module')
            ->field('a.*')
            ->alias('a')
            ->where('a.online','1')
            ->select();
        return $module_list;
    }
    public function menu_list(){
        $menu = array();
        $re = Db::table('mbs_menu_url')
            ->field('p.*,m.module_name,c.menu_id fid,c.menu_name fname')
            ->alias('p')
            ->join('mbs_module m','p.module_id = m.module_id','LEFT')
            ->join('mbs_menu_url c','p.father_menu = c.menu_id','LEFT')
            ->where('p.is_show',1)
            ->where('m.online',1)
            ->select();
        foreach ($re as $k=>$v){
            if($v['fid']){
                //有父级
                $menu[$v['module_id']]['module_id'] = $v['module_id'];
                $menu[$v['module_id']]['module_name'] = $v['module_name'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['fid'] = $v['fid'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['fname'] = $v['fname'];
                $menu[$v['module_id']]['childfir'][$v['fid']]['secchild'][] = $v;
            }else{
                //没有父级
                $menu[$v['module_id']]['module_id'] = $v['module_id'];
                $menu[$v['module_id']]['module_name'] = $v['module_name'];
                $menu[$v['module_id']]['childfir'][] = $v;
            }
        }
        return $menu;
    }

    public function save_pannel(){
        $pannel_info_str = array_filter(explode(',',$_POST['pannel_info']));
        $data = array();
        foreach ($pannel_info_str as $k=>$v){
            $data[substr($v,0,strrpos($v,'*'))] = substr($v,strripos($v,"*")+1);
        }
        if(isset($_GET['id']) && intval($_GET['id'])>0){
            //启动事务
            Db::startTrans();
            try{
                $re = Db::table('mbs_menu_url')
                    ->where('menu_id',intval($_GET['id']))
                    ->update($data);
                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200 ;
                $data['msg'] = '保存成功';
                return json_encode($data);

            }catch(\Exception $e){
                Db::rollback();//回滚事务

                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        }else{

            //启动事务
            Db::startTrans();
            try{
                $re = Db::table('mbs_menu_url')
                    ->insert($data);
                Db::commit();//提交事务
                unset($data);
                $data['code'] = 200 ;
                $data['msg'] = '保存成功';
                return json_encode($data);

            }catch(\Exception $e){
                Db::rollback();//回滚事务

                unset($data);
                $data['code'] = 0;
                $data['msg'] = '保存失败';
                return json_encode($data);
            }
        }

    }
    public function delete(){
        $id = intval($_POST['uid']);
        if($id>0){
            $re = Db::table('mbs_user')
                ->where('id',$id)
                ->update(['status' => 0]);
            if($re){
                $data['code'] = 200;
                $data['msg'] = "删除成功！";
                return  json_encode($data);
            }else{
                $data['code'] = 1;
                $data['msg'] = "删除失败！";
                return json_encode($data);
            }
        }else{
            $data['code'] = -1;
            $data['msg'] = '没有可操作的对象';
            return json_encode($data);
        }

    }
}