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
        // 获取api_key参数
        $apiKey = $request->param('api_key', '');
        
        // 如果api_key为空，返回400错误
        if (empty($apiKey)) {
            return json([
                'code' => 400,
                'msg' => 'Missing required parameter: api_key',
                'data' => null
            ], 400);
        }
        
        // 查询批次信息
        $batch = Db::name('batches')
            ->where('api_key', $apiKey)
            ->find();
        
        // 如果批次不存在，返回401错误
        if (!$batch) {
            return json([
                'code' => 401,
                'msg' => 'Invalid api_key',
                'data' => null
            ], 401);
        }
        
        // 如果批次被禁用，返回403错误
        if ($batch['status'] != 1) {
            return json([
                'code' => 403,
                'msg' => 'Batch is disabled',
                'data' => null
            ], 403);
        }
        
        // 将批次信息注入到请求中，供后续使用
        $request->batch = $batch;
        $request->batchId = $batch['id'];
        
        return $next($request);
    }
}

