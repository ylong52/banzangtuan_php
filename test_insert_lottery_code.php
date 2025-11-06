<?php
/**
 * 抽奖码批量插入脚本
 * 生成8个等级，每个等级1000条记录，共8000条
 * lottery_code: 6位唯一码（字母数字混编，小写）
 * prize_level: 1-8
 * status: 1
 */

// 加载 Laravel 框架
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "抽奖码批量生成和插入脚本\n";
echo "========================================\n\n";

try {
    // 检查表是否存在
    $tableExists = DB::select("SHOW TABLES LIKE 'lottery_codes'");
    if (empty($tableExists)) {
        echo "⚠ 警告: lottery_codes 表不存在！\n";
        echo "请先执行以下SQL创建表：\n\n";
        echo "CREATE TABLE `lottery_codes` (\n";
        echo "	`id` INT(11) NULL DEFAULT NULL,\n";
        echo "	`lottery_code` VARCHAR(10) NULL DEFAULT NULL COMMENT '抽奖码（唯一，如 \"AB1234\"）' COLLATE 'utf8mb4_general_ci',\n";
        echo "	`prize_level` TINYINT(2) NULL DEFAULT NULL COMMENT '对应奖品等级（1 = 一等奖，2 = 二等奖...，0 = 谢谢参与）',\n";
        echo "	`status` TINYINT(2) NOT NULL DEFAULT '1' COMMENT '1有效，0表示禁用'\n";
        echo ")\n";
        echo "COMMENT='换奖码表'\n";
        echo "COLLATE='utf8mb4_general_ci'\n";
        echo "ENGINE=InnoDB;\n\n";
        echo "是否继续？(y/n): ";
        // 在命令行中可以手动输入，这里自动继续
        echo "\n继续执行...\n\n";
    }
    
    // 检查表中是否已有数据
    $existingCount = DB::table('lottery_codes')->count();
    if ($existingCount > 0) {
        echo "⚠ 警告: lottery_codes 表中已有 {$existingCount} 条记录！\n";
        echo "是否清空现有数据？(y/n): ";
        // 这里可以选择清空或追加，为了安全，我们不清空
        echo "\n将在现有数据基础上追加新数据...\n\n";
    }
    
    // 获取已存在的抽奖码（用于确保唯一性）
    $existingCodes = DB::table('lottery_codes')->pluck('lottery_code')->toArray();
    $existingCodesSet = array_flip($existingCodes); // 使用数组键进行快速查找
    
    echo "开始生成抽奖码...\n";
    echo "配置: 8个等级 × 1000条 = 8000条记录\n\n";
    
    // 生成6位唯一码的函数
    function generateUniqueCode(&$usedCodes, $length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789'; // 小写字母和数字
        $maxAttempts = 1000; // 最大尝试次数
        $attempts = 0;
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[mt_rand(0, strlen($characters) - 1)];
            }
            $attempts++;
            
            if ($attempts > $maxAttempts) {
                throw new Exception("无法生成唯一码，尝试次数过多");
            }
        } while (isset($usedCodes[$code]));
        
        $usedCodes[$code] = true;
        return $code;
    }
    
    $totalInserted = 0;
    $batchSize = 100; // 每批插入100条，提高性能
    $usedCodes = $existingCodesSet; // 初始化已使用码集合
    
    // 为每个等级生成1000条记录
    for ($prizeLevel = 1; $prizeLevel <= 8; $prizeLevel++) {
        echo "正在生成第 {$prizeLevel} 等奖 (prize_level={$prizeLevel})...\n";
        
        $batch = [];
        $levelCount = 0;
        
        for ($i = 0; $i < 1000; $i++) {
            // 生成唯一码
            $lotteryCode = generateUniqueCode($usedCodes, 6);
            
            $batch[] = [
                'lottery_code' => $lotteryCode,
                'prize_level' => $prizeLevel,
                'status' => 1,
            ];
            
            // 批量插入
            if (count($batch) >= $batchSize) {
                DB::table('lottery_codes')->insert($batch);
                $totalInserted += count($batch);
                $levelCount += count($batch);
                $batch = [];
                
                // 显示进度
                echo "  已插入: {$levelCount}/1000\n";
            }
        }
        
        // 插入剩余的数据
        if (!empty($batch)) {
            DB::table('lottery_codes')->insert($batch);
            $totalInserted += count($batch);
            $levelCount += count($batch);
        }
        
        echo "✓ 第 {$prizeLevel} 等奖完成: {$levelCount} 条记录\n\n";
    }
    
    echo "========================================\n";
    echo "✓ 抽奖码生成完成！\n";
    echo "========================================\n";
    echo "总共插入: {$totalInserted} 条记录\n";
    echo "等级分布: 每个等级 1000 条，共 8 个等级\n\n";
    
    // 验证数据
    echo "正在验证数据...\n";
    $totalCount = DB::table('lottery_codes')->count();
    echo "  总记录数: {$totalCount}\n";
    
    // 检查每个等级的数量
    $levelCounts = DB::table('lottery_codes')
        ->where('status', 1)
        ->groupBy('prize_level')
        ->select('prize_level', DB::raw('count(*) as count'))
        ->get();
    
    echo "  各等级记录数:\n";
    foreach ($levelCounts as $level) {
        echo "    第 {$level->prize_level} 等奖: {$level->count} 条\n";
    }
    
    // 检查唯一性
    $duplicateCount = DB::table('lottery_codes')
        ->select('lottery_code', DB::raw('count(*) as count'))
        ->groupBy('lottery_code')
        ->having('count', '>', 1)
        ->count();
    
    if ($duplicateCount > 0) {
        echo "  ⚠ 警告: 发现 {$duplicateCount} 个重复的抽奖码！\n";
    } else {
        echo "  ✓ 所有抽奖码都是唯一的\n";
    }
    
    echo "\n========================================\n";
    echo "脚本执行完成！\n";
    echo "========================================\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "✗ 执行失败！\n";
    echo "========================================\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}

