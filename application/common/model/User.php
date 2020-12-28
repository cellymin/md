<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 模型自定义字段
 */
class User extends Model
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

    public function getUserinfo($uid=''){
        $user_info = Db::table('mbs_user')
            ->where('status',1)
            ->where('id',$uid)
            ->find();
        return $user_info;
    }

}