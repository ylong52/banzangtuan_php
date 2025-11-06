<?php
/**
 * 数据库连接测试脚本
 * 用于检查 .env 文件中的数据库配置是否有效
 */

// 加载 Laravel 框架
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "数据库连接测试\n";
echo "========================================\n\n";

try {
    // 获取数据库配置
    $config = config('database.connections.mysql');
    
    $host = $config['host'];
    $port = $config['port'];
    $database = $config['database'];
    $username = $config['username'];
    $password = $config['password'];
    
    echo "数据库配置信息：\n";
    echo "  主机: {$host}\n";
    echo "  端口: {$port}\n";
    echo "  数据库名: {$database}\n";
    echo "  用户名: {$username}\n";
    echo "  密码: " . (empty($password) ? '(空)' : str_repeat('*', strlen($password))) . "\n";
    echo "  字符集: {$config['charset']}\n";
    echo "\n";
    
    // 检查 .env 文件是否存在
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        echo "✓ .env 文件存在\n";
        
        // 读取 .env 文件内容（不显示敏感信息）
        $envContent = file_get_contents($envFile);
        $hasDbHost = preg_match('/DB_HOST\s*=/', $envContent);
        $hasDbPort = preg_match('/DB_PORT\s*=/', $envContent);
        $hasDbDatabase = preg_match('/DB_DATABASE\s*=/', $envContent);
        $hasDbUsername = preg_match('/DB_USERNAME\s*=/', $envContent);
        $hasDbPassword = preg_match('/DB_PASSWORD\s*=/', $envContent);
        
        echo "  .env 配置项检查：\n";
        echo "    DB_HOST: " . ($hasDbHost ? "✓ 已配置" : "✗ 未配置（使用默认值）") . "\n";
        echo "    DB_PORT: " . ($hasDbPort ? "✓ 已配置" : "✗ 未配置（使用默认值）") . "\n";
        echo "    DB_DATABASE: " . ($hasDbDatabase ? "✓ 已配置" : "✗ 未配置（使用默认值）") . "\n";
        echo "    DB_USERNAME: " . ($hasDbUsername ? "✓ 已配置" : "✗ 未配置（使用默认值）") . "\n";
        echo "    DB_PASSWORD: " . ($hasDbPassword ? "✓ 已配置" : "✗ 未配置（使用默认值）") . "\n";
    } else {
        echo "⚠ .env 文件不存在，使用 config/database.php 中的默认配置\n";
    }
    
    echo "\n";
    echo "正在测试数据库连接...\n";
    
    // 方法1: 使用 PDO 直接连接测试
    try {
        echo "  尝试连接到: {$host}:{$port}\n";
        
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$config['charset']}";
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 10, // 10秒超时
        ];
        
        $pdo = new PDO($dsn, $username, $password, $pdoOptions);
        
        echo "✓ PDO 连接成功！\n";
        
        // 获取数据库版本信息
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "  数据库版本: {$version}\n";
        
        // 获取当前数据库名
        $currentDb = $pdo->query('SELECT DATABASE()')->fetchColumn();
        echo "  当前数据库: {$currentDb}\n";
        
        // 获取表数量
        $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$database}'")->fetchColumn();
        echo "  数据表数量: {$tableCount}\n";
        
        $pdo = null;
    } catch (PDOException $e) {
        echo "✗ PDO 连接失败！\n";
        echo "  错误信息: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n";
    echo "正在测试 Laravel DB 连接...\n";
    
    // 方法2: 使用 Laravel DB Facade 测试
    try {
        DB::connection()->getPdo();
        echo "✓ Laravel DB 连接成功！\n";
        
        // 执行一个简单的查询
        $result = DB::select('SELECT 1 as test');
        if ($result && $result[0]->test == 1) {
            echo "  ✓ 查询测试通过\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Laravel DB 连接失败！\n";
        echo "  错误信息: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✓ 数据库配置有效，连接测试通过！\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "✗ 数据库连接测试失败！\n";
    echo "========================================\n";
    echo "错误详情:\n";
    echo "  " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "\n";
    echo "请检查以下内容：\n";
    echo "  1. 数据库服务器是否运行\n";
    echo "  2. 主机地址和端口是否正确\n";
    echo "  3. 数据库用户名和密码是否正确\n";
    echo "  4. 数据库是否存在\n";
    echo "  5. 网络连接是否正常\n";
    echo "  6. 防火墙是否允许连接\n";
    exit(1);
}

