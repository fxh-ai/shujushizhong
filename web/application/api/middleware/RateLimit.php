<?php

namespace app\api\middleware;

use think\Request;
use think\Response;
use think\Db;
use think\Cache;

/**
 * API限流中间件
 * 
 * 基于批次的限流机制：
 * - 每个批次独立限流
 * - 限流次数从批次表的rate_limit字段读取
 * - 使用缓存记录请求次数
 * - 超过限制返回429状态码
 */
class RateLimit
{
    /**
     * 处理请求
     * 
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next)
    {
        // 获取批次信息（应该在Auth中间件之后执行）
        $batch = $request->batch ?? null;
        
        if (!$batch) {
            // 如果没有批次信息，跳过限流检查（可能是健康检查等不需要限流的接口）
            return $next($request);
        }
        
        $batchId = $batch['id'];
        $rateLimit = $batch['rate_limit'] ?? 100; // 默认100次/分钟
        
        // 获取当前时间窗口（分钟）
        $timeWindow = date('Y-m-d H:i');
        
        // 构建缓存键
        $cacheKey = 'rate_limit_' . $batchId . '_' . md5($timeWindow);
        
        // 获取当前请求次数
        $currentCount = Cache::get($cacheKey) ?: 0;
        
        // 检查是否超过限制
        if ($currentCount >= $rateLimit) {
            // 记录限流日志
            $this->logRateLimit($batchId, $request, $rateLimit);
            
            // 返回429错误
            return Response::create([
                'code' => 429,
                'msg' => 'Rate limit exceeded. Maximum ' . $rateLimit . ' requests per minute.',
                'time' => time(),
                'data' => [
                    'rate_limit' => $rateLimit,
                    'retry_after' => 60 - (int)date('s'), // 剩余秒数
                ]
            ], 'json', 429);
        }
        
        // 增加请求计数
        $currentCount++;
        Cache::set($cacheKey, $currentCount, 120); // 缓存2分钟，确保跨分钟边界也能正确计数
        
        // 记录请求日志（可选，避免日志过多）
        // 只在接近限制时记录
        if ($currentCount >= $rateLimit * 0.8) {
            $this->logRequest($batchId, $request, $currentCount, $rateLimit);
        }
        
        // 继续处理请求
        $response = $next($request);
        
        // 在响应头中添加限流信息
        $response->header([
            'X-RateLimit-Limit' => $rateLimit,
            'X-RateLimit-Remaining' => max(0, $rateLimit - $currentCount),
            'X-RateLimit-Reset' => strtotime($timeWindow . ':59') + 1, // 下一分钟的开始时间
        ]);
        
        return $response;
    }
    
    /**
     * 记录限流日志
     * 
     * @param int $batchId
     * @param Request $request
     * @param int $rateLimit
     */
    protected function logRateLimit($batchId, Request $request, $rateLimit)
    {
        try {
            Db::name('rate_limit_logs')->insert([
                'batch_id' => $batchId,
                'api_path' => $request->pathinfo(),
                'ip' => $request->ip(),
                'rate_limit' => $rateLimit,
                'request_count' => $rateLimit + 1, // 超过限制的请求
                'status' => 'blocked',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // 忽略日志记录错误，不影响主流程
            \think\Log::error('记录限流日志失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 记录请求日志（接近限制时）
     * 
     * @param int $batchId
     * @param Request $request
     * @param int $currentCount
     * @param int $rateLimit
     */
    protected function logRequest($batchId, Request $request, $currentCount, $rateLimit)
    {
        try {
            // 只记录接近限制的请求，避免日志过多
            if ($currentCount % 10 == 0 || $currentCount >= $rateLimit * 0.9) {
                Db::name('rate_limit_logs')->insert([
                    'batch_id' => $batchId,
                    'api_path' => $request->pathinfo(),
                    'ip' => $request->ip(),
                    'rate_limit' => $rateLimit,
                    'request_count' => $currentCount,
                    'status' => 'warning',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            // 忽略日志记录错误
        }
    }
}

