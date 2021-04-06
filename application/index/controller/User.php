<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class User extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index()
    {
        $map = array();
        $where = '';
        $config = config('group');
        //总院长
        $cheif = $config['chief_dean'];
        //院长
        $dean = intval($config['dean']);
        if (!empty($_SESSION['CID'])) {
            if ($_SESSION['GID'] == $cheif || $_SESSION['GID'] == 1) {//总院长或者超级管理员

            } else {
                if ($_SESSION['GID'] == $dean) {
                    //院长
                    $map['chain_id'] = $_SESSION['CID'];
                    $where .= ' AND chain_id='.$_SESSION['CID'].' ';
                } else {
                    $map['id'] = $_SESSION['UID'];
                    $where .= ' AND id='.$_SESSION['UID'].' ';
                }
            }

        } else {
            if ($_SESSION['GID'] == 1 || $_SESSION['GID'] == $cheif) {
                //总院长和超级管理员没有所属门店
            } else {
                return redirect('/index.php/index/login');
            }
        }
        //门店
        $chain = Db::table('mbs_chain')
            ->where('status', 1)
            ->select();
        $chainlist = array();
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chainlist);
        //用户组
        $usergroup = Db::table('mbs_user_group')
            ->where('status', 1)
            ->select();
        $grouplist = array();
        foreach ($usergroup as $k => $v) {
            $grouplist[$v['group_id']] = $v;
        }
        $this->assign('grouplist', $grouplist);

        if (!empty($_GET['chain_id']) && intval($_GET['chain_id']) > 0) {
            $map['chain_id'] = intval($_GET['chain_id']);
            $where = ' AND chain_id=\'' . intval($_GET['chain_id']) . '\' ';
            $this->assign('cid', intval($_GET['chain_id']));
        }
        if (!empty($_GET['group']) && intval($_GET['group']) > 0) {
            $map['user_group'] = intval($_GET['group']);
            $where = ' AND user_group=\'' . intval($_GET['group']) . '\' ';
            $this->assign('group', intval($_GET['group']));
        }
        if (!empty($_GET['keywords'])) {
            $where .= ' AND (name LIKE \'%' . $_GET['keywords'] . '%\' OR phone LIKE \'%' . $_GET['keywords'] . '%\' ) ';
        }
        $map['status'] = 1;

        if (!empty($_GET['keywords'])) {
            $total = Db::table('mbs_user')
                ->where($map)
                ->where('name LIKE \'%' . $_GET['keywords'] . '%\' OR phone LIKE \'%' . $_GET['keywords'] . '%\'   ')
                ->count();
        }else{
            $total = Db::table('mbs_user')
                ->where($map)
                ->count();
        }

        $pagesize = 20;
        Loader::import('page.Page', EXTEND_PATH, '.class.php');
        $page = new \page($total, $pagesize);
        $re = Db::query('SELECT * FROM mbs_user WHERE status=1 '.$where.' ORDER BY id ASC '.$page->limit);
        $this->assign('userlist', $re);
        $down_page = $page->fpage();
        $this->assign('page', $down_page);

        return $this->view->fetch();
    }

    public function edituser()
    {
        $id = intval($_GET['id']);
        if ($id > 0) {
            $userinfo = Db::table('mbs_user')
                ->where('id', $id)
                ->find();
            $this->assign('userinfo', $userinfo);
        }

        $chain_list = $this->chain_list();
        $group_list = $this->group_list();
        $user_grade = $this->get_grade();
        $this->assign('grouplist', $group_list);
        $this->assign('chainlist', $chain_list);
        $this->assign('gradelist', $user_grade);
        return $this->view->fetch();
    }

    public function chain_list()
    {
        $chain_list = Db::table('mbs_chain')
            ->field('a.*')
            ->alias('a')
            ->where('a.status', '1')
            ->select();
        return $chain_list;
    }

    public function group_list()
    {
        $group_list = Db::table('mbs_user_group')
            ->select();
        return $group_list;
    }

    public function get_grade()
    {
        $user_grade = Db::table('mbs_grade')
            ->select();
        return $user_grade;
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
        $data['nickname'] = $new['usernick'];
        $data['birth'] = $new['userbirth'];
        $data['sex'] = $new['sex'];
        $data['phone'] = $new['userphone'];
        $data['job_name'] = $new['userjob'];
        $data['grade_name'] = $new['usergrade'];
        $data['email'] = $new['useremail'];
        $data['address'] = $new['useraddress'];
        if(strlen( $new['userpassword'])>=4){
            $data['password'] = md5($new['userpassword']);
        }else if(strlen($new['userpassword'])>0 && strlen($new['userpassword'])<4){
            $data['code'] = 0;
            $data['msg'] = '密码长度必须大于4';
            return json_encode($data);
        }
        $data['grade_id'] = $new['usergradeid'];
        $data['chain_id'] = $new['userchainid'];
        $data['chain_name'] = $new['userchainid'];
        $data['user_group'] = $new['usergroupid'];
        $data['chain_name'] = $new['userchain'];
        $data['usrsrc'] = $_POST['usrsrc'];

        Loader::import('pinyin.PinYin', EXTEND_PATH, '.class.php');
        $pinyin = new \pinyin();
        if (!empty($new['usernick'])) {
            $data['nick_quan_pin'] = strtoupper($pinyin->getpy($new['usernick']));
        }
        $data['quanpin'] = strtoupper($pinyin->getpy($new['username']));
        $data['jianxie'] = strtoupper($pinyin->getpy($new['username'], false));

        if (isset($_GET['id']) && intval($_GET['id']) > 0) {
            //编辑
            $data['update_time'] = date('Y-m-d h:i:s', time());

            $re = Db::table('mbs_user')
                ->where('id', intval($_GET['id']))
                ->update($data);
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_user')
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
            if(strlen($new['userpassword'])==0){
                $data['password'] = md5(123456);
            }
            $data['create_time'] = date('Y-m-d h:i:s', time());
            //启动事务
            Db::startTrans();
            try {
                $re = Db::table('mbs_user')
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
            $if_exist = Db::table('mbs_user')
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
}