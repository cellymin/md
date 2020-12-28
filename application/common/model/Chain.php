<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 模型自定义字段
 */
class Chain extends Model
{
    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 默认模型字段
     * @author min by 2020-9-12
     */

    public function getOne($cid=''){
        $map = array();
        $map['status'] = 1;
        $chain_info = Db::table('mbs_chain')
            ->field('id')
            ->where('status',1)
            ->where($map)
            ->find();
        return $chain_info;
    }
    public function getAll(){
        $chains = Db::table('mbs_chain')
            ->where('status',1)
            ->select();
        return $chains;
    }


}