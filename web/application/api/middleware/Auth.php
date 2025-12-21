<?php

namespace app\api\middleware;

use think\Request;
use think\Response;
use think\Db;

/**
 * API鉴权中间件
 * 验证批次密钥（api_key）
 */
class Auth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 获取当前请求的控制器和操作
        $controller = strtolower($request->controller());
        $action = strtolower($request->action());
        
        // 健康检查接口可以不需要api_key（在控制器中已经处理）
        if ($controller === 'health' && $action === 'index') {
            // 如果提供了api_key，验证并注入批次信息（可选）
            $apiKey = $request->param('api_key', '');
            if (!empty($apiKey)) {
                $this->validateApiKey($request, $apiKey);
            }
            return $next($request);
        }
        
        // 其他接口必须验证api_key
        $apiKey = $request->param('api_key', '');
        
        // 如果api_key为空，返回400错误
        if (empty($apiKey)) {
            return json([
                'code' => 400,
                'msg' => 'Missing required parameter: api_key',
                'time' => time(),
                'data' => null
            ], 400);
        }
        
        $this->validateApiKey($request, $apiKey);
        
        return $next($request);
    }
    
    /**
     * 验证api_key并注入批次信息
     * @param Request $request
     * @param string $apiKey
     */
    protected function validateApiKey($request, $apiKey)
    {
        // 查询批次信息
        $batch = Db::name('batches')
            ->where('api_key', $apiKey)
            ->find();
        
        // 如果批次不存在，返回401错误
        if (!$batch) {
            throw new \think\exception\HttpResponseException(
                Response::create(json([
                    'code' => 401,
                    'msg' => 'Invalid api_key',
                    'time' => time(),
                    'data' => null
                ]), 'json', 401)
            );
        }
        
        // 如果批次被禁用，返回403错误
        if ($batch['status'] != 1) {
            throw new \think\exception\HttpResponseException(
                Response::create(json([
                    'code' => 403,
                    'msg' => 'Batch is disabled',
                    'time' => time(),
                    'data' => null
                ]), 'json', 403)
            );
        }
        
        // 将批次信息注入到请求中，供后续使用
        $request->batch = $batch;
        $request->batchId = $batch['id'];
    }
}

