<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Cache;

/**
 * 固件相关接口
 */
class Firmware extends Api
{
    // API接口不需要登录验证
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取固件版本信息
     * GET /api/firmware/version
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * - current_version（可选）：当前设备固件版本，用于比较
     * 
     * @return \think\response\Json
     */
    public function version()
    {
        $currentVersion = $this->request->param('current_version', '');
        
        $cacheKey = 'api_firmware_version';
        
        // 尝试从缓存获取（缓存30分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false && empty($currentVersion)) {
            // 如果没有提供当前版本，直接返回缓存
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 获取最新的固件版本
            $firmware = Db::name('firmware_versions')
                ->where('status', 1)
                ->order('version', 'desc')
                ->find();
            
            if (!$firmware) {
                return $this->error('暂无可用固件版本');
            }
            
            // 构建响应数据（根据PRD文档格式）
            $result = [
                'latest_version' => $firmware['version'],
                'download_url' => $this->getFullUrl($firmware['download_url'] ?: $firmware['file_path']),
                'file_size' => $firmware['file_size'] ?? 0,
                'release_notes' => $firmware['release_notes'] ?? '',
                'force_update' => $firmware['force_update'] ?? 0,
            ];
            
            // 如果提供了当前版本，进行比较
            if (!empty($currentVersion)) {
                $result['current_version'] = $currentVersion;
                $result['need_update'] = $this->compareVersion($currentVersion, $firmware['version']);
            }
            
            // 缓存30分钟
            if (empty($currentVersion)) {
                Cache::set($cacheKey, $result, 1800);
            }
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            \think\Log::error('获取固件版本失败: ' . $e->getMessage());
            return $this->error('获取固件版本失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取固件版本历史
     * GET /api/firmware/history
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * - limit（可选）：返回数量，默认10
     * 
     * @return \think\response\Json
     */
    public function history()
    {
        $limit = (int)$this->request->param('limit', 10);
        $limit = max(1, min(50, $limit)); // 限制在1-50之间
        
        $cacheKey = 'api_firmware_history_' . $limit;
        
        // 尝试从缓存获取（缓存30分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 获取固件版本历史
            $firmwares = Db::name('firmware_versions')
                ->where('status', 1)
                ->order('version', 'desc')
                ->limit($limit)
                ->select();
            
            $result = [];
            foreach ($firmwares as $firmware) {
                $result[] = [
                    'version' => $firmware['version'],
                    'download_url' => $this->getFullUrl($firmware['download_url'] ?: $firmware['file_path']),
                    'file_size' => $firmware['file_size'] ?? 0,
                    'release_notes' => $firmware['release_notes'] ?? '',
                    'force_update' => $firmware['force_update'] ?? 0,
                ];
            }
            
            // 缓存30分钟
            Cache::set($cacheKey, $result, 1800);
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            \think\Log::error('获取固件版本历史失败: ' . $e->getMessage());
            return $this->error('获取固件版本历史失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 比较版本号
     * 
     * @param string $currentVersion 当前版本
     * @param string $latestVersion 最新版本
     * @return bool 是否需要更新
     */
    protected function compareVersion($currentVersion, $latestVersion)
    {
        // 移除可能的v前缀
        $currentVersion = ltrim($currentVersion, 'vV');
        $latestVersion = ltrim($latestVersion, 'vV');
        
        // 使用version_compare函数比较版本号
        // 返回true表示latestVersion > currentVersion，需要更新
        return version_compare($latestVersion, $currentVersion, '>');
    }
    
    /**
     * 获取完整URL
     * 
     * @param string $path 相对路径
     * @return string
     */
    protected function getFullUrl($path)
    {
        if (empty($path)) {
            return '';
        }
        
        // 如果已经是完整URL，直接返回
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        
        // 获取当前域名
        $scheme = $this->request->scheme();
        $host = $this->request->host();
        $port = $this->request->port();
        
        // 处理host中可能包含的端口号
        if (strpos($host, ':') !== false) {
            list($host, $existingPort) = explode(':', $host, 2);
            if (empty($port)) {
                $port = $existingPort;
            }
        }
        
        $baseUrl = $scheme . '://' . $host;
        if ($port && $port != 80 && $port != 443) {
            $baseUrl .= ':' . $port;
        }
        
        return $baseUrl . $path;
    }
}
