<?php
// API模块公共文件

// 注意：ThinkPHP 5.0 不支持全局中间件注册
// 中间件逻辑已在 Api 基类的 _initialize() 方法中实现
// 通过 checkApiKey() 方法进行鉴权
// RateLimit 中间件暂时不使用，限流逻辑可以在需要时添加到控制器中
