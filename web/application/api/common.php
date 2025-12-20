<?php
// API模块公共文件

// 定义全局中间件
// 注意：中间件执行顺序很重要
// 1. Auth中间件：验证api_key并注入批次信息
// 2. RateLimit中间件：基于批次信息进行限流
\think\Middleware::add('app\api\middleware\Auth');
\think\Middleware::add('app\api\middleware\RateLimit');
