<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 后台入口文件 ]
// 使用此文件可以达到隐藏admin模块的效果
// 为了你的安全，强烈不建议将此文件名修改成admin.php
// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');

// 判断是否安装
if (!is_file(APP_PATH . 'admin/command/Install/install.lock')) {
    header("location:./install.php");
    exit;
}

// 加载框架引导文件
require __DIR__ . '/../thinkphp/base.php';

// 绑定到admin模块
\think\Route::bind('admin');

// 关闭路由
\think\App::route(false);

// 设置根url
\think\Url::root('');

// 如果没有指定路径，默认跳转到登录页面
if (empty($_SERVER['PATH_INFO']) || $_SERVER['PATH_INFO'] == '/') {
    $adminFile = basename($_SERVER['SCRIPT_NAME']);
    header("location:./{$adminFile}/index/login");
    exit;
}

// 执行应用
\think\App::run()->send();
