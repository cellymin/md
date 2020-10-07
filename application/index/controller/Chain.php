<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Chain extends Base
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

        $map['u.status'] = 1;
        $re = Db::table('mbs_chain')
            ->field('c.*,u.name uname')
            ->alias('c')
            ->join('mbs_user u','u.id = c.dean_id','LEFT')
            ->where($map)
            ->select();
//        echo '<pre/>>';
//        var_dump($re);
//        die();
        $this->assign('chainlist',$re);

        return $this->view->fetch();
    }
    public function editchain(){
        $id = intval($_GET['id']);
        if($id>0){
            $re = Db::table('mbs_chain')
                ->field('c.*,u.name uname')
                ->alias('c')
                ->join('mbs_user u','c.dean_id = u.id','LEFT')
                ->where('c.id',$id)
                ->find();
            $this->assign('chaininfo',$re);
        }

        $deanlist = $this->deanlist();
//        echo '<pre/>';var_dump($deanlist);die();
        $this->assign('deanlist',$deanlist);
        return $this->view->fetch();
    }
    public function deanlist(){
        $config =  config('group');
        $dean = $config['dean'];//院长

        $list = Db::table('mbs_user')
            ->field('a.*')
            ->alias('a')
            ->where('a.user_group',$dean)
            ->select();
        return $list;
    }
    public function save_chain(){
        $chain_info_str = array_filter(explode(',',$_POST['chain_info']));
        $data = array();
        foreach ($chain_info_str as $k=>$v){
            $data[substr($v,0,strrpos($v,'*'))] = substr($v,strripos($v,"*")+1);
        }
        $data['country'] = '中国';
        $data['phone'] = $data['tel'];
        if(isset($_GET['id']) && intval($_GET['id'])>0){
            $data['update_time'] = date('Y-m-d h:i:s',time());
            //启动事务
            Db::startTrans();
            try{
                $re = Db::table('mbs_chain')
                    ->where('id',intval($_GET['id']))
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
            $data['create_time'] = date('Y-m-d h:i:s',time());
            //启动事务
            Db::startTrans();
            try{
                $re = Db::table('mbs_chain')
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