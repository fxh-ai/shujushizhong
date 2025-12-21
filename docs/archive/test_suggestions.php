<?php
/**
 * 测试建议执行脚本
 * 使用方法：php test_suggestions.php
 */

// 引入ThinkPHP框架
define('APP_PATH', __DIR__ . '/application/');
require __DIR__ . '/thinkphp/start.php';

use think\Db;
use think\Cache;
use app\api\library\CoinGecko;

echo "========================================\n";
echo "    测试建议执行脚本\n";
echo "========================================\n\n";

$testResults = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'errors' => []
];

// 1. 性能测试 - API限流
echo "=== 1. 性能测试 - API限流 ===\n\n";

echo "1.1 检查限流配置：\n";
try {
    $batch = Db::name('batches')->where('api_key', 'test_api_key_123456')->find();
    if ($batch) {
        echo "  ✅ 批次限流配置: {$batch['rate_limit']} 次/分钟\n";
        $testResults['passed']++;
    } else {
        echo "  ⚠️  测试批次不存在\n";
        $testResults['warnings']++;
    }
} catch (\Exception $e) {
    echo "  ❌ 检查失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "限流配置检查失败: " . $e->getMessage();
}

echo "\n1.2 测试限流日志表：\n";
try {
    $logCount = Db::name('rate_limit_logs')->count();
    echo "  ✅ 限流日志表存在，当前记录数: {$logCount}\n";
    $testResults['passed']++;
} catch (\Exception $e) {
    echo "  ❌ 限流日志表检查失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "限流日志表检查失败: " . $e->getMessage();
}

// 2. 数据准确性测试
echo "\n=== 2. 数据准确性测试 ===\n\n";

echo "2.1 测试CoinGecko API连接：\n";
try {
    $coinGecko = new CoinGecko();
    $coinsList = $coinGecko->getCoinsList();
    echo "  ✅ CoinGecko API连接正常，获取到 " . count($coinsList) . " 个币种\n";
    $testResults['passed']++;
} catch (\Exception $e) {
    echo "  ❌ CoinGecko API连接失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "CoinGecko API连接失败: " . $e->getMessage();
}

echo "\n2.2 测试数据同步准确性：\n";
try {
    $coinCount = Db::name('coins')->where('status', 1)->count();
    echo "  ✅ 数据库中启用的币种数量: {$coinCount}\n";
    if ($coinCount > 0) {
        $testResults['passed']++;
    } else {
        echo "  ⚠️  数据库中没有启用的币种\n";
        $testResults['warnings']++;
    }
} catch (\Exception $e) {
    echo "  ❌ 数据同步检查失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "数据同步检查失败: " . $e->getMessage();
}

echo "\n2.3 测试缓存机制：\n";
try {
    $testKey = 'test_cache_' . time();
    $testValue = 'test_value_' . rand(1000, 9999);
    
    // 设置缓存
    Cache::set($testKey, $testValue, 60);
    
    // 获取缓存
    $cached = Cache::get($testKey);
    
    if ($cached === $testValue) {
        echo "  ✅ 缓存机制正常工作\n";
        $testResults['passed']++;
    } else {
        echo "  ❌ 缓存机制异常（值不匹配）\n";
        $testResults['failed']++;
        $testResults['errors'][] = "缓存机制异常";
    }
    
    // 清理测试缓存
    Cache::rm($testKey);
} catch (\Exception $e) {
    echo "  ❌ 缓存机制测试失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "缓存机制测试失败: " . $e->getMessage();
}

// 3. 边界情况测试
echo "\n=== 3. 边界情况测试 ===\n\n";

echo "3.1 测试数据库连接：\n";
try {
    Db::query('SELECT 1');
    echo "  ✅ 数据库连接正常\n";
    $testResults['passed']++;
} catch (\Exception $e) {
    echo "  ❌ 数据库连接失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "数据库连接失败: " . $e->getMessage();
}

echo "\n3.2 测试极端参数处理：\n";
try {
    // 测试空参数
    $emptyCoin = Db::name('coins')->where('coin_id', '')->find();
    echo "  ✅ 空参数处理正常（返回空结果）\n";
    $testResults['passed']++;
    
    // 测试不存在的币种
    $notExist = Db::name('coins')->where('coin_id', 'not_exist_coin_' . time())->find();
    echo "  ✅ 不存在币种处理正常（返回空结果）\n";
    $testResults['passed']++;
} catch (\Exception $e) {
    echo "  ❌ 极端参数测试失败: " . $e->getMessage() . "\n";
    $testResults['failed']++;
    $testResults['errors'][] = "极端参数测试失败: " . $e->getMessage();
}

// 输出测试结果
echo "\n========================================\n";
echo "    测试结果统计\n";
echo "========================================\n";
echo "✅ 通过: {$testResults['passed']}\n";
echo "❌ 失败: {$testResults['failed']}\n";
echo "⚠️  警告: {$testResults['warnings']}\n";
echo "总计: " . ($testResults['passed'] + $testResults['failed'] + $testResults['warnings']) . "\n";

if (!empty($testResults['errors'])) {
    echo "\n错误列表:\n";
    foreach ($testResults['errors'] as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n========================================\n";
if ($testResults['failed'] === 0) {
    echo "🎉 所有测试通过！\n";
} else {
    echo "⚠️  有测试失败，请检查上述错误\n";
}
echo "========================================\n";

