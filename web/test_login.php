<?php
/**
 * FastAdmin 登录页面诊断工具
 */

echo "<h1>FastAdmin 登录页面诊断</h1>";

// 1. 检查安装锁文件
$lockFile = __DIR__ . '/application/admin/command/Install/install.lock';
echo "<h2>1. 安装状态</h2>";
if (file_exists($lockFile)) {
    echo "✅ 安装锁文件存在<br>";
} else {
    echo "❌ 安装锁文件不存在<br>";
}

// 2. 检查数据库连接
echo "<h2>2. 数据库连接</h2>";
try {
    $config = include __DIR__ . '/application/database.php';
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    echo "✅ 数据库连接成功<br>";
    
    // 检查管理员账户
    $stmt = $pdo->query("SELECT username, email, status FROM {$config['prefix']}admin WHERE username='admin'");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "✅ 管理员账户存在: {$admin['username']} ({$admin['email']})<br>";
    } else {
        echo "❌ 管理员账户不存在<br>";
    }
} catch (Exception $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "<br>";
}

// 3. 检查关键文件
echo "<h2>3. 关键文件检查</h2>";
$files = [
    'public/admin.php' => '后台入口文件',
    'application/admin/controller/Index.php' => '后台控制器',
    'application/admin/view/index/login.html' => '登录页面模板',
    'public/assets/css/backend.css' => '后台CSS文件',
    'public/assets/js/require.min.js' => 'RequireJS文件',
    'public/assets/img/login-head.png' => '登录头部图片',
    'public/assets/img/avatar.png' => '头像图片',
];

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✅ {$desc}: {$file} ({$size} bytes)<br>";
    } else {
        echo "❌ {$desc}: {$file} 不存在<br>";
    }
}

// 4. 测试访问登录页面
echo "<h2>4. 登录页面访问测试</h2>";
$loginUrl = "http://{$_SERVER['HTTP_HOST']}/admin.php/index/login";
echo "登录页面URL: <a href='{$loginUrl}' target='_blank'>{$loginUrl}</a><br>";

// 5. 检查PHP配置
echo "<h2>5. PHP配置</h2>";
echo "PHP版本: " . PHP_VERSION . "<br>";
echo "错误显示: " . (ini_get('display_errors') ? '开启' : '关闭') . "<br>";
echo "错误报告级别: " . error_reporting() . "<br>";

// 6. 检查目录权限
echo "<h2>6. 目录权限</h2>";
$dirs = [
    'runtime' => '运行时目录',
    'public/uploads' => '上传目录',
];
foreach ($dirs as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path) ? '可写' : '不可写';
        echo "{$desc}: {$writable}<br>";
    } else {
        echo "{$desc}: 目录不存在<br>";
    }
}

echo "<hr>";
echo "<p><a href='/admin.php/index/login' target='_blank'>点击这里访问登录页面</a></p>";
?>

