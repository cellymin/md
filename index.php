<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

// 定义项目路径
define('APP_PATH', __DIR__ . '/application/');

define('SITE_URL','/');
define('CSS_URL',SITE_URL.'public/static/stylesheets/');
define('IMG_URL',SITE_URL.'public/static/images/');
define('JS_URL',SITE_URL.'public/static/js/');
define('STATIC_URL',SITE_URL.'public/static/');
define('FENYE_URL',SITE_URL.'index.php/');


//定义当前控制器URL
define('DQURL',"http://".$_SERVER['HTTP_HOST'].FENYE_URL."");
define('SITEURL',"http://".$_SERVER['HTTP_HOST']."");
// 加载框架引导文件
require './thinkphp/start.php';