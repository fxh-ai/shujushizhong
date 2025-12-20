<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\api\library\CoinGecko;
use think\Db;
use think\Cache;

/**
 * 币种相关接口
 */
class Coins extends Api
{
    // API接口不需要登录验证
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    /**
     * 获取币种列表
     * GET /api/coins/list
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * 
     * @return \think\response\Json
     */
    public function list()
    {
        $cacheKey = 'api_coins_list';
        
        // 尝试从缓存获取（缓存30分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 从数据库获取启用的币种
            $coins = Db::name('coins')
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->select();
            
            $result = [];
            $coinGecko = new CoinGecko();
            
            foreach ($coins as $coin) {
                $item = [
                    'id' => $coin['coin_id'],
                    'symbol' => $coin['symbol'],
                    'name' => $coin['custom_name'] ?: $coin['name'],
                ];
                
                // Logo处理：优先使用自定义Logo，其次本地Logo，最后CoinGecko原始URL
                if (!empty($coin['custom_logo_path'])) {
                    $item['logo'] = $this->getFullUrl($coin['custom_logo_path']);
                } elseif (!empty($coin['logo_path'])) {
                    $item['logo'] = $this->getFullUrl($coin['logo_path']);
                } elseif (!empty($coin['icon_url'])) {
                    $item['logo'] = $coin['icon_url'];
                } else {
                    $item['logo'] = '';
                }
                
                // 描述信息
                if (!empty($coin['description'])) {
                    $item['description'] = $coin['description'];
                }
                
                $result[] = $item;
            }
            
            // 如果数据库中没有币种，从CoinGecko获取并同步
            if (empty($result)) {
                $this->syncCoinsFromCoinGecko();
                // 重新查询
                $coins = Db::name('coins')
                    ->where('status', 1)
                    ->order('sort_order', 'asc')
                    ->select();
                
                foreach ($coins as $coin) {
                    $item = [
                        'id' => $coin['coin_id'],
                        'symbol' => $coin['symbol'],
                        'name' => $coin['custom_name'] ?: $coin['name'],
                    ];
                    
                    if (!empty($coin['custom_logo_path'])) {
                        $item['logo'] = $this->getFullUrl($coin['custom_logo_path']);
                    } elseif (!empty($coin['logo_path'])) {
                        $item['logo'] = $this->getFullUrl($coin['logo_path']);
                    } elseif (!empty($coin['icon_url'])) {
                        $item['logo'] = $coin['icon_url'];
                    } else {
                        $item['logo'] = '';
                    }
                    
                    if (!empty($coin['description'])) {
                        $item['description'] = $coin['description'];
                    }
                    
                    $result[] = $item;
                }
            }
            
            // 缓存30分钟
            Cache::set($cacheKey, $result, 1800);
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            \think\Log::error('获取币种列表失败: ' . $e->getMessage());
            return $this->error('获取币种列表失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 从CoinGecko同步币种数据
     * 
     * @return void
     */
    protected function syncCoinsFromCoinGecko()
    {
        try {
            $coinGecko = new CoinGecko();
            $coinsList = $coinGecko->getCoinsList();
            
            // 只同步常见的币种（可以根据需要调整）
            $commonCoins = ['bitcoin', 'ethereum', 'binancecoin', 'cardano', 'solana', 'polkadot', 'dogecoin', 'litecoin'];
            
            foreach ($coinsList as $coin) {
                // 只同步常见币种
                if (!in_array($coin['id'], $commonCoins)) {
                    continue;
                }
                
                // 检查是否已存在
                $exists = Db::name('coins')->where('coin_id', $coin['id'])->find();
                if ($exists) {
                    continue;
                }
                
                // 获取币种详情（包含Logo）
                try {
                    $detail = $coinGecko->getCoinDetail($coin['id']);
                    
                    // 下载Logo
                    $logoPath = '';
                    if (!empty($detail['image']['large'])) {
                        $logoPath = $coinGecko->downloadLogo($detail['image']['large'], $coin['id']);
                    }
                    
                    // 插入数据库
                    Db::name('coins')->insert([
                        'coin_id' => $coin['id'],
                        'symbol' => $coin['symbol'],
                        'name' => $coin['name'],
                        'icon_url' => $detail['image']['large'] ?? '',
                        'logo_path' => $logoPath ?: '',
                        'status' => 1,
                        'sort_order' => 0,
                    ]);
                } catch (\Exception $e) {
                    // 如果获取详情失败，只保存基本信息
                    Db::name('coins')->insert([
                        'coin_id' => $coin['id'],
                        'symbol' => $coin['symbol'],
                        'name' => $coin['name'],
                        'status' => 1,
                        'sort_order' => 0,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \think\Log::error('同步CoinGecko币种数据失败: ' . $e->getMessage());
        }
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

