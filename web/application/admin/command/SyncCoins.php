<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use app\api\library\CoinGecko;

/**
 * 同步CoinGecko币种数据命令
 */
class SyncCoins extends Command
{
    protected function configure()
    {
        $this->setName('sync:coins')
            ->setDescription('Sync coins data from CoinGecko API');
    }

    protected function execute(Input $input, Output $output)
    {
        // 确保数据库连接使用UTF-8编码
        Db::execute("SET NAMES utf8mb4");
        
        $output->writeln('开始同步CoinGecko币种数据...');
        
        try {
            $coinGecko = new CoinGecko();
            
            // 获取币种列表
            $output->writeln('正在获取币种列表...');
            $coinsList = $coinGecko->getCoinsList();
            $output->writeln('获取到 ' . count($coinsList) . ' 个币种');
            
            // 定义要同步的常见币种
            $commonCoins = [
                'bitcoin', 'ethereum', 'binancecoin', 'cardano', 'solana', 
                'polkadot', 'dogecoin', 'litecoin', 'chainlink', 'polygon',
                'avalanche-2', 'uniswap', 'tron', 'stellar', 'cosmos'
            ];
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($coinsList as $coin) {
                // 只同步常见币种
                if (!in_array($coin['id'], $commonCoins)) {
                    continue;
                }
                
                // 检查是否已存在
                $exists = Db::name('coins')->where('coin_id', $coin['id'])->find();
                if ($exists) {
                    $output->writeln("币种 {$coin['id']} 已存在，跳过");
                    continue;
                }
                
                $output->writeln("正在同步币种: {$coin['id']} ({$coin['symbol']})");
                
                try {
                    // 获取币种详情（包含Logo）
                    $detail = $coinGecko->getCoinDetail($coin['id']);
                    
                    // 下载Logo
                    $logoPath = '';
                    if (!empty($detail['image']['large'])) {
                        $logoPath = $coinGecko->downloadLogo($detail['image']['large'], $coin['id']);
                        if ($logoPath) {
                            $output->writeln("  Logo已下载: {$logoPath}");
                        }
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
                    
                    $successCount++;
                    $output->writeln("  ✓ 币种 {$coin['id']} 同步成功");
                    
                    // 避免请求过快，稍微延迟
                    usleep(200000); // 0.2秒
                    
                } catch (\Exception $e) {
                    $failCount++;
                    $output->writeln("  ✗ 币种 {$coin['id']} 同步失败: " . $e->getMessage());
                    
                    // 如果获取详情失败，只保存基本信息
                    try {
                        Db::name('coins')->insert([
                            'coin_id' => $coin['id'],
                            'symbol' => $coin['symbol'],
                            'name' => $coin['name'],
                            'status' => 1,
                            'sort_order' => 0,
                        ]);
                        $output->writeln("  ✓ 币种 {$coin['id']} 基本信息已保存");
                    } catch (\Exception $e2) {
                        $output->writeln("  ✗ 币种 {$coin['id']} 基本信息保存失败: " . $e2->getMessage());
                    }
                }
            }
            
            $output->writeln('');
            $output->writeln("同步完成！");
            $output->writeln("成功: {$successCount} 个");
            $output->writeln("失败: {$failCount} 个");
            
        } catch (\Exception $e) {
            $output->writeln("同步失败: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

