<?php

namespace app\api\library;

use think\Cache;
use think\Exception;
use think\Log;

/**
 * CoinGecko API 封装类
 * 
 * API文档：https://www.coingecko.com/en/api/documentation
 * 免费版限制：每分钟最多50次请求
 */
class CoinGecko
{
    /**
     * API基础URL
     */
    const BASE_URL = 'https://api.coingecko.com/api/v3';
    
    /**
     * API密钥（可选，如果有的话可以提高限制）
     */
    protected $apiKey = '';
    
    /**
     * 请求超时时间（秒）
     */
    protected $timeout = 10;
    
    /**
     * 构造函数
     */
    public function __construct()
    {
        // 从系统配置读取API密钥（如果有）
        $this->apiKey = \think\Db::name('system_configs')
            ->where('config_key', 'coingecko_api_key')
            ->value('config_value') ?: '';
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $endpoint API端点
     * @param array $params 请求参数
     * @return array
     * @throws Exception
     */
    protected function request($endpoint, $params = [])
    {
        $url = self::BASE_URL . '/' . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: DataClock/1.0'
            ]
        ]);
        
        // 如果有API密钥，添加到请求头
        if (!empty($this->apiKey)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: DataClock/1.0',
                'X-CG-Demo-API-Key: ' . $this->apiKey
            ]);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            Log::error('CoinGecko API请求失败: ' . $error);
            throw new Exception('CoinGecko API请求失败: ' . $error);
        }
        
        if ($httpCode !== 200) {
            Log::error('CoinGecko API返回错误: HTTP ' . $httpCode . ', Response: ' . $response);
            throw new Exception('CoinGecko API返回错误: HTTP ' . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('CoinGecko API响应解析失败: ' . json_last_error_msg());
            throw new Exception('CoinGecko API响应解析失败');
        }
        
        return $data;
    }
    
    /**
     * 获取币种列表
     * 
     * @param bool $includePlatform 是否包含平台信息
     * @return array
     */
    public function getCoinsList($includePlatform = false)
    {
        $cacheKey = 'coingecko_coins_list_' . ($includePlatform ? 'with_platform' : 'simple');
        
        // 尝试从缓存获取（缓存30分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $params = [];
            if ($includePlatform) {
                $params['include_platform'] = 'true';
            }
            
            $data = $this->request('coins/list', $params);
            
            // 缓存30分钟
            Cache::set($cacheKey, $data, 1800);
            
            return $data;
        } catch (Exception $e) {
            Log::error('获取币种列表失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取币种详细信息（包含Logo URL）
     * 
     * @param string $coinId 币种ID（如：bitcoin）
     * @param string $currency 计价货币（默认：usd）
     * @return array
     */
    public function getCoinDetail($coinId, $currency = 'usd')
    {
        $cacheKey = 'coingecko_coin_detail_' . $coinId . '_' . $currency;
        
        // 尝试从缓存获取（缓存5分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $params = [
                'localization' => 'false',
                'tickers' => 'false',
                'market_data' => 'true',
                'community_data' => 'false',
                'developer_data' => 'false',
                'sparkline' => 'false'
            ];
            
            $data = $this->request('coins/' . $coinId, $params);
            
            // 缓存5分钟
            Cache::set($cacheKey, $data, 300);
            
            return $data;
        } catch (Exception $e) {
            Log::error('获取币种详情失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取币种价格（简单接口，轻量级）
     * 
     * @param string|array $coinIds 币种ID（可以是单个或数组）
     * @param string|array $currencies 计价货币（默认：usd）
     * @return array
     */
    public function getSimplePrice($coinIds, $currencies = 'usd')
    {
        if (is_array($coinIds)) {
            $coinIds = implode(',', $coinIds);
        }
        if (is_array($currencies)) {
            $currencies = implode(',', $currencies);
        }
        
        $cacheKey = 'coingecko_simple_price_' . md5($coinIds . '_' . $currencies);
        
        // 尝试从缓存获取（缓存1分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $params = [
                'ids' => $coinIds,
                'vs_currencies' => $currencies,
                'include_market_cap' => 'true',
                'include_24hr_vol' => 'true',
                'include_24hr_change' => 'true',
                'include_last_updated_at' => 'true'
            ];
            
            $data = $this->request('simple/price', $params);
            
            // 缓存1分钟
            Cache::set($cacheKey, $data, 60);
            
            return $data;
        } catch (Exception $e) {
            Log::error('获取币种价格失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 获取K线数据（OHLC）
     * 
     * @param string $coinId 币种ID
     * @param string $currency 计价货币（默认：usd）
     * @param string $days 时间维度（1, 7, 14, 30, 90, 180, 365, max）
     *                      对于5分钟：使用1天数据
     *                      对于1小时：使用7天数据
     *                      对于1天：使用365天数据
     * @return array
     */
    public function getOhlc($coinId, $currency = 'usd', $days = '1')
    {
        $cacheKey = 'coingecko_ohlc_' . $coinId . '_' . $currency . '_' . $days;
        
        // 根据days设置不同的缓存时间
        $cacheTime = 300; // 默认5分钟
        if ($days == '7') {
            $cacheTime = 3600; // 1小时缓存60分钟
        } elseif ($days == '365') {
            $cacheTime = 86400; // 1天缓存24小时
        }
        
        // 尝试从缓存获取
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        try {
            $params = [
                'vs_currency' => $currency,
                'days' => $days
            ];
            
            $data = $this->request('coins/' . $coinId . '/ohlc', $params);
            
            // 缓存数据
            Cache::set($cacheKey, $data, $cacheTime);
            
            return $data;
        } catch (Exception $e) {
            Log::error('获取K线数据失败: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 下载Logo图片
     * 
     * @param string $logoUrl Logo URL
     * @param string $coinId 币种ID
     * @return string|false 返回本地存储路径，失败返回false
     */
    public function downloadLogo($logoUrl, $coinId)
    {
        if (empty($logoUrl)) {
            return false;
        }
        
        // 确定文件扩展名
        $extension = 'png';
        if (strpos($logoUrl, '.jpg') !== false || strpos($logoUrl, '.jpeg') !== false) {
            $extension = 'jpg';
        }
        
        // 本地存储路径
        $uploadPath = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'coins' . DS;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $localPath = $uploadPath . $coinId . '.' . $extension;
        $relativePath = '/uploads/coins/' . $coinId . '.' . $extension;
        
        // 如果文件已存在，直接返回
        if (file_exists($localPath)) {
            return $relativePath;
        }
        
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $logoUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'DataClock/1.0'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error || $httpCode !== 200) {
                Log::error('下载Logo失败: ' . $logoUrl . ', Error: ' . $error);
                return false;
            }
            
            // 保存文件
            if (file_put_contents($localPath, $imageData) === false) {
                Log::error('保存Logo文件失败: ' . $localPath);
                return false;
            }
            
            return $relativePath;
        } catch (Exception $e) {
            Log::error('下载Logo异常: ' . $e->getMessage());
            return false;
        }
    }
}

