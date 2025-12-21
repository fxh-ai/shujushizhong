<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * 配置信息接口
 */
class Config extends Api
{
    // API接口不需要登录验证
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取系统配置信息
     * GET /api/config
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * 
     * @return \think\response\Json
     */
    public function index()
    {
        $cacheKey = 'api_system_config';
        
        // 尝试从缓存获取（缓存1分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 从系统配置表读取配置
            $configs = Db::name('system_configs')
                ->where('status', 'normal')
                ->column('config_value', 'config_key');
            
            // 构建响应数据
            $result = [
                'refresh_interval' => isset($configs['refresh_interval']) ? (int)$configs['refresh_interval'] : 300,
                'default_currency' => $configs['default_currency'] ?? 'USD',
                'display_coins' => isset($configs['display_coins']) ? json_decode($configs['display_coins'], true) : ['bitcoin', 'ethereum'],
                'timezone' => $configs['timezone'] ?? 'Asia/Shanghai',
                'display_format' => $configs['display_format'] ?? 'standard',
            ];
            
            // 处理display_coins，确保是数组
            if (is_string($result['display_coins'])) {
                $result['display_coins'] = json_decode($result['display_coins'], true) ?: ['bitcoin', 'ethereum'];
            }
            
            // 缓存1分钟
            Cache::set($cacheKey, $result, 60);
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            \think\Log::error('获取系统配置失败: ' . $e->getMessage());
            return $this->error('获取系统配置失败: ' . $e->getMessage());
        }
    }
}

