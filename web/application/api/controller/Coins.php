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
                try {
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
                } catch (\Exception $e) {
                    \think\Log::error('同步CoinGecko数据失败: ' . $e->getMessage());
                    // 即使同步失败，也返回空数组，而不是抛出异常
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
     * 获取单个币种的行情数据
     * GET /api/coins/{coin_id}/quote
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * - coin_id（路径参数）：币种ID
     * - currency（可选）：计价货币，默认USD
     * 
     * @return \think\response\Json
     */
    public function quote()
    {
        $coinId = $this->request->param('coin_id', '');
        $currency = strtolower($this->request->param('currency', 'usd'));
        
        if (empty($coinId)) {
            return $this->error('币种ID不能为空');
        }
        
        $cacheKey = 'api_coin_quote_' . $coinId . '_' . $currency;
        
        // 尝试从缓存获取（缓存5分钟）
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 从数据库获取币种信息
            $coin = Db::name('coins')
                ->where('coin_id', $coinId)
                ->where('status', 1)
                ->find();
            
            if (!$coin) {
                return $this->error('币种不存在或已禁用');
            }
            
            $coinGecko = new CoinGecko();
            
            // 获取价格数据
            $priceData = $coinGecko->getSimplePrice($coinId, $currency);
            
            if (empty($priceData[$coinId])) {
                return $this->error('获取行情数据失败');
            }
            
            $price = $priceData[$coinId];
            
            // 获取币种详情（包含更多信息）
            try {
                $detail = $coinGecko->getCoinDetail($coinId, $currency);
            } catch (\Exception $e) {
                \think\Log::warning('获取币种详情失败，使用简单价格数据: ' . $e->getMessage());
                $detail = null;
            }
            
            // 构建币种信息
            $coinInfo = [
                'id' => $coin['coin_id'],
                'symbol' => $coin['symbol'],
                'name' => $coin['custom_name'] ?: $coin['name'],
            ];
            
            // Logo处理
            if (!empty($coin['custom_logo_path'])) {
                $coinInfo['logo'] = $this->getFullUrl($coin['custom_logo_path']);
            } elseif (!empty($coin['logo_path'])) {
                $coinInfo['logo'] = $this->getFullUrl($coin['logo_path']);
            } elseif (!empty($coin['icon_url'])) {
                $coinInfo['logo'] = $coin['icon_url'];
            } else {
                $coinInfo['logo'] = '';
            }
            
            // 描述信息
            if (!empty($coin['description'])) {
                $coinInfo['description'] = $coin['description'];
            }
            
            // 构建行情数据
            // CoinGecko simple/price返回格式：
            // {
            //   "bitcoin": {
            //     "usd": 88000,
            //     "usd_market_cap": 1750000000000,
            //     "usd_24h_vol": 30000000000,
            //     "usd_24h_change": -0.5,
            //     "last_updated_at": 1234567890
            //   }
            // }
            $quote = [
                $currency => $price[$currency] ?? 0,
                $currency . '_24h_change' => $price[$currency . '_24h_change'] ?? 0,
                $currency . '_24h_change_percentage' => $price[$currency . '_24h_change'] ?? 0, // 注意：CoinGecko返回的是百分比数值，不是百分比字符串
                $currency . '_market_cap' => $price[$currency . '_market_cap'] ?? 0,
                $currency . '_24h_vol' => $price[$currency . '_24h_vol'] ?? 0,
                'last_updated_at' => $price['last_updated_at'] ?? time(),
            ];
            
            // 如果有详情数据，补充更多信息
            if ($detail && isset($detail['market_data'])) {
                $marketData = $detail['market_data'];
                if (isset($marketData['current_price'][$currency])) {
                    $quote[$currency] = $marketData['current_price'][$currency];
                }
                if (isset($marketData['price_change_percentage_24h'])) {
                    $quote[$currency . '_24h_change_percentage'] = $marketData['price_change_percentage_24h'];
                }
                if (isset($marketData['market_cap'][$currency])) {
                    $quote[$currency . '_market_cap'] = $marketData['market_cap'][$currency];
                }
                if (isset($marketData['total_volume'][$currency])) {
                    $quote[$currency . '_24h_vol'] = $marketData['total_volume'][$currency];
                }
            }
            
            $result = [
                'coin' => $coinInfo,
                'quote' => $quote,
            ];
            
            // 缓存5分钟
            Cache::set($cacheKey, $result, 300);
            
            // 同时更新数据库缓存表
            try {
                Db::name('coin_quotes')->insert([
                    'coin_id' => $coinId,
                    'currency' => $currency,
                    'price' => $quote[$currency],
                    'change_24h' => $quote[$currency . '_24h_change_percentage'],
                    'market_cap' => $quote[$currency . '_market_cap'],
                    'volume_24h' => $quote[$currency . '_24h_vol'],
                    'updated_at' => time(),
                ], true);
            } catch (\Exception $e) {
                // 忽略数据库插入错误
            }
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage() ?: '未知错误';
            \think\Log::error('获取行情数据失败: ' . $errorMsg . ', Trace: ' . $e->getTraceAsString());
            return $this->error('获取行情数据失败: ' . $errorMsg);
        }
    }
    
    /**
     * 获取K线数据
     * GET /api/coins/{coin_id}/ohlc
     * 
     * 请求参数：
     * - api_key（必需）：批次密钥
     * - coin_id（路径参数）：币种ID
     * - currency（可选）：计价货币，默认USD
     * - interval（必需）：时间维度，5m/1h/1d
     * 
     * @return \think\response\Json
     */
    public function ohlc()
    {
        $coinId = $this->request->param('coin_id', '');
        $currency = strtolower($this->request->param('currency', 'usd'));
        $interval = $this->request->param('interval', '');
        
        if (empty($coinId)) {
            return $this->error('币种ID不能为空');
        }
        
        if (empty($interval) || !in_array($interval, ['5m', '1h', '1d'])) {
            return $this->error('时间维度参数错误，支持：5m, 1h, 1d');
        }
        
        // 映射时间维度到CoinGecko的days参数
        $daysMap = [
            '5m' => '1',    // 5分钟K线使用1天数据
            '1h' => '7',    // 1小时K线使用7天数据
            '1d' => '365',  // 1天K线使用365天数据
        ];
        
        $days = $daysMap[$interval];
        $cacheKey = 'api_coin_ohlc_' . $coinId . '_' . $currency . '_' . $interval;
        
        // 根据时间维度设置不同的缓存时间
        $cacheTime = 300; // 默认5分钟
        if ($interval == '1h') {
            $cacheTime = 3600; // 1小时缓存60分钟
        } elseif ($interval == '1d') {
            $cacheTime = 86400; // 1天缓存24小时
        }
        
        // 尝试从缓存获取
        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $this->success('获取成功', $cached);
        }
        
        try {
            // 检查币种是否存在
            $coin = Db::name('coins')
                ->where('coin_id', $coinId)
                ->where('status', 1)
                ->find();
            
            if (!$coin) {
                return $this->error('币种不存在或已禁用');
            }
            
            $coinGecko = new CoinGecko();
            
            // 获取OHLC数据
            $ohlcData = $coinGecko->getOhlc($coinId, $currency, $days);
            
            if (empty($ohlcData)) {
                return $this->error('获取K线数据失败');
            }
            
            // 转换数据格式
            // CoinGecko返回格式：[[timestamp, open, high, low, close], ...]
            $result = [];
            foreach ($ohlcData as $item) {
                if (count($item) >= 5) {
                    $result[] = [
                        'timestamp' => $item[0],
                        'open' => $item[1],
                        'high' => $item[2],
                        'low' => $item[3],
                        'close' => $item[4],
                    ];
                }
            }
            
            // 缓存数据
            Cache::set($cacheKey, $result, $cacheTime);
            
            // 同时更新数据库缓存表
            try {
                $cacheData = json_encode($result);
                Db::name('coin_ohlc_cache')->insert([
                    'coin_id' => $coinId,
                    'currency' => $currency,
                    'interval' => $interval,
                    'data' => $cacheData,
                    'updated_at' => time(),
                ], true);
            } catch (\Exception $e) {
                // 忽略数据库插入错误
            }
            
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage() ?: '未知错误';
            \think\Log::error('获取K线数据失败: ' . $errorMsg . ', Trace: ' . $e->getTraceAsString());
            return $this->error('获取K线数据失败: ' . $errorMsg);
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
