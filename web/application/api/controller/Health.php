<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * 健康检查接口
 */
class Health extends Api
{
    // 健康检查接口不需要登录和权限验证
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
    // 健康检查接口可以不使用api_key验证（可选）
    // 如果提供了api_key，会验证；如果没有提供，也可以访问
    protected $noNeedApiKey = ['*'];

    /**
     * 健康检查
     * GET /api/health
     * 
     * 可选参数：api_key（如果提供会验证并返回批次信息）
     * 
     * @return \think\response\Json
     */
    public function index()
    {
        $status = 'ok';
        $errors = [];
        
        // 检查数据库连接
        $databaseStatus = 'connected';
        try {
            Db::query('SELECT 1');
        } catch (\Exception $e) {
            $databaseStatus = 'error';
            $status = 'error';
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }
        
        // 检查缓存状态
        $cacheStatus = 'working';
        try {
            $testKey = 'health_check_' . time();
            Cache::set($testKey, 'test', 10);
            $value = Cache::get($testKey);
            if ($value !== 'test') {
                $cacheStatus = 'error';
                $status = 'error';
                $errors[] = 'Cache test failed';
            } else {
                Cache::rm($testKey);
            }
        } catch (\Exception $e) {
            $cacheStatus = 'error';
            $status = 'error';
            $errors[] = 'Cache error: ' . $e->getMessage();
        }
        
        // 获取系统版本（从配置或常量）
        $version = '1.0.0';
        
        // 构建响应数据
        $data = [
            'status' => $status,
            'timestamp' => time(),
            'version' => $version,
            'database' => $databaseStatus,
            'cache' => $cacheStatus,
        ];
        
        // 如果有错误，添加错误信息
        if (!empty($errors)) {
            $data['errors'] = $errors;
        }
        
        // 如果提供了api_key，尝试验证并返回批次信息
        $apiKey = $this->request->param('api_key', '');
        if (!empty($apiKey)) {
            try {
                $batch = Db::name('batches')
                    ->where('api_key', $apiKey)
                    ->where('status', 1)
                    ->find();
                
                if ($batch) {
                    $data['batch'] = [
                        'id' => $batch['id'],
                        'name' => $batch['name'],
                        'status' => $batch['status'],
                    ];
                }
            } catch (\Exception $e) {
                // 忽略批次查询错误，不影响健康检查
            }
        }
        
        return $this->success('Health check completed', $data);
    }
}

