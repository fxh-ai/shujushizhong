<?php
/**
 * 完整API测试脚本
 * 使用方法：php test_all_apis.php
 */

// 定义测试配置
define('API_BASE_URL', 'http://localhost:8000/index.php/api');
define('TEST_API_KEY', 'test_api_key_123456');

// 测试结果统计
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'errors' => []
];

/**
 * 发送HTTP请求
 */
function httpRequest($url, $method = 'GET', $params = [])
{
    $ch = curl_init();
    
    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'http_code' => $httpCode,
        'body' => $response,
        'data' => json_decode($response, true)
    ];
}

/**
 * 测试用例
 */
function testCase($name, $url, $params = [], $expectedCode = 200, $checkCallback = null)
{
    global $testResults;
    
    echo "\n[测试] {$name}\n";
    echo "URL: {$url}\n";
    
    $result = httpRequest($url, 'GET', $params);
    
    if ($result['http_code'] === 0) {
        echo "❌ 失败: 网络错误 - {$result['error']}\n";
        $testResults['failed']++;
        $testResults['errors'][] = "{$name}: 网络错误";
        return false;
    }
    
    if ($result['http_code'] !== $expectedCode) {
        echo "❌ 失败: HTTP状态码 {$result['http_code']}，期望 {$expectedCode}\n";
        $testResults['failed']++;
        $testResults['errors'][] = "{$name}: HTTP状态码错误";
        return false;
    }
    
    if ($checkCallback && is_callable($checkCallback)) {
        $checkResult = $checkCallback($result['data']);
        if (!$checkResult) {
            echo "❌ 失败: 数据验证失败\n";
            $testResults['failed']++;
            $testResults['errors'][] = "{$name}: 数据验证失败";
            return false;
        }
    }
    
    echo "✅ 通过\n";
    if (isset($result['data']['code']) && $result['data']['code'] == 1) {
        echo "   响应: {$result['data']['msg']}\n";
    }
    $testResults['passed']++;
    return true;
}

// 开始测试
echo "========================================\n";
echo "    完整API功能测试\n";
echo "========================================\n";

// 1. 健康检查接口
testCase(
    '健康检查（不带api_key）',
    API_BASE_URL . '/health/index',
    [],
    200,
    function($data) {
        return isset($data['data']['status']) && $data['data']['status'] === 'ok';
    }
);

testCase(
    '健康检查（带api_key）',
    API_BASE_URL . '/health/index',
    ['api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']['status']) && $data['data']['status'] === 'ok';
    }
);

// 2. 币种列表接口
testCase(
    '币种列表（不带api_key - 应返回400）',
    API_BASE_URL . '/coins/list',
    [],
    400
);

testCase(
    '币种列表（带api_key）',
    API_BASE_URL . '/coins/list',
    ['api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']) && is_array($data['data']);
    }
);

// 3. 行情数据接口
testCase(
    '行情数据（Bitcoin）',
    API_BASE_URL . '/coins/quote',
    ['coin_id' => 'bitcoin', 'api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']['coin']) && isset($data['data']['quote']);
    }
);

// 4. K线图接口
testCase(
    'K线数据（5分钟）',
    API_BASE_URL . '/coins/ohlc',
    ['coin_id' => 'bitcoin', 'interval' => '5m', 'api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']) && is_array($data['data']);
    }
);

testCase(
    'K线数据（1小时）',
    API_BASE_URL . '/coins/ohlc',
    ['coin_id' => 'bitcoin', 'interval' => '1h', 'api_key' => TEST_API_KEY],
    200
);

testCase(
    'K线数据（1天）',
    API_BASE_URL . '/coins/ohlc',
    ['coin_id' => 'bitcoin', 'interval' => '1d', 'api_key' => TEST_API_KEY],
    200
);

// 5. 固件版本接口
testCase(
    '固件版本',
    API_BASE_URL . '/firmware/version',
    ['api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']['latest_version']);
    }
);

testCase(
    '固件版本比较',
    API_BASE_URL . '/firmware/version',
    ['api_key' => TEST_API_KEY, 'current_version' => '1.0.0'],
    200,
    function($data) {
        return isset($data['data']['need_update']);
    }
);

// 6. 配置信息接口
testCase(
    '配置信息',
    API_BASE_URL . '/config/index',
    ['api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['data']) && is_array($data['data']);
    }
);

// 7. 错误场景测试
testCase(
    '无效api_key',
    API_BASE_URL . '/coins/list',
    ['api_key' => 'invalid_key'],
    401
);

testCase(
    '不存在的币种',
    API_BASE_URL . '/coins/quote',
    ['coin_id' => 'notexist', 'api_key' => TEST_API_KEY],
    200,
    function($data) {
        return isset($data['code']) && $data['code'] != 1; // 应该返回错误
    }
);

// 输出测试结果
echo "\n========================================\n";
echo "    测试结果统计\n";
echo "========================================\n";
echo "✅ 通过: {$testResults['passed']}\n";
echo "❌ 失败: {$testResults['failed']}\n";
echo "总计: " . ($testResults['passed'] + $testResults['failed']) . "\n";

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

