<?php
require_once 'vendor/autoload.php';

// 加载Laravel应用
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Database connection: " . config('database.default') . "\n";
    echo "Database name: " . config('database.connections.mysql.database') . "\n\n";
    
    // 检查goods表是否存在
    $tables = DB::select('SHOW TABLES LIKE "goods"');
    if (empty($tables)) {
        echo "Error: goods表不存在！\n";
        exit;
    }
    
    echo "goods表存在，正在查看表结构...\n\n";
    
    // 查看表结构
    $columns = DB::select('DESCRIBE goods');
    echo "表结构:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} " . ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
