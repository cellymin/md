<?php

namespace app\index\controller;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use http\Params;
use Illuminate\Http\Request;
use think\Controller;
use think\Loader;
use think\View;
use think\Db;


class Xiaofeilog extends Base
{
    public function __initialize()
    {
        parent::__initialize();
    }

    public function index(){
        $chain = Db::query('SELECT * FROM mbs_chain WHERE status=1  ORDER BY id ASC');
        $chainlist = [];
        foreach ($chain as $k => $v) {
            $chainlist[$v['id']] = $v;
        }
        $this->assign('chain', $chainlist);
        $map = [];
        if(isset($_GET['chain_id']) && !empty($_GET['chain_id'])){
            $chain_id = intval($_GET['chain_id']);
            $map['u.chain_id'] = $chain_id;
            $this->assign('chain_id', $chain_id);
        }
        if(isset($_GET['create_time']) && !empty($_GET['create_time'])){
            $star_t = $_GET['create_time'].' 00:00:00';
            $end_t = $_GET['create_time'] . '23:59:59';
            $loglist = Db::table('mbs_xiaofei_log')
                ->alias('a')
                ->field('a.*,c.name custname,u.name uname')
                ->join('mbs_customers c','a.customer_id = c.id')
                ->join('mbs_user u','a.user_id = u.id')
                ->where($map)
                ->where('a.createTime', '>=', $star_t)
                ->where('a.createTime', '<=', $end_t)
                ->order('a.createTime','desc')
                ->select();
        }else{
            $loglist = Db::table('mbs_xiaofei_log')
                ->alias('a')
                ->field('a.*,c.name custname,u.name uname')
                ->join('mbs_customers c','a.customer_id = c.id')
                ->join('mbs_user u','a.user_id = u.id')
                ->where($map)
                ->order('a.createTime','desc')
                ->select();
        }


        $this->assign('loglist', $loglist);
        return $this->view->fetch();
}

}