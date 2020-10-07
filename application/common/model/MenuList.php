<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * 模型自定义字段
 */
class MenuList extends Model
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
    public function getmenulist($uid = '')
    {

        $uid = intval($uid);
        $menu_num = Db::table('mbs_user')
            ->alias('a')
            ->join('mbs_user_group b', 'a.user_group = b.group_id')
            ->where('a.id', $uid)
            ->column('group_role');
        $menunum = array_unique(array_filter(explode(',', implode($menu_num))));

        $menu_list = Db::table('mbs_menu_url')
            ->where('menu_id', 'in', $menunum)
            ->where('is_show',1)
            ->where('online',1)
            ->select();
        return $menu_list;
    }
    public function getmenulistall($uid = '')
    {
        $menu_list = Db::table('mbs_menu_url')
            ->where('is_show',1)
            ->where('online',1)
            ->select();
        return $menu_list;
    }
    public function getmodule(){
        $menu_list = Db::table('mbs_module')
            ->where('online',1)
            ->order('module_sort asc')
            ->select();
        return $menu_list;
    }

}