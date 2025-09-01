<?php
require_once 'vendor/autoload.php';

// 加载Laravel应用
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Goods;
use Illuminate\Support\Facades\DB;

try {
    echo "=== 测试商品字段保存功能 ===\n\n";
    
    // 测试数据
    $testData = [
        'goodsname' => '测试商品_' . time(),
        'imagelist' => json_encode(['test1.jpg', 'test2.jpg']),
        'priceinfo' => json_encode(['price' => 99.99]),
        'shopinfo' => json_encode(['shop' => '测试店铺']),
        'skutaglist' => json_encode(['tag1', 'tag2']),
        'keyword' => 'test_keyword_' . time(),
        'json' => json_encode(['test' => 'data']),
        'share_copywriting' => '这是一个测试分享文案，包含中文和特殊字符！@#$%^&*()',
        'white_image' => 'test_white_image.jpg'
    ];
    
    echo "准备保存的数据:\n";
    foreach ($testData as $key => $value) {
        echo "- {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
    echo "\n";
    
    // 创建新商品
    $goods = new Goods();
    $goods->fill($testData);
    $result = $goods->save();
    
    if ($result) {
        echo "✅ 商品保存成功！ID: {$goods->id}\n\n";
        
        // 验证保存的数据
        $savedGoods = Goods::find($goods->id);
        echo "保存后的数据验证:\n";
        echo "- share_copywriting: " . ($savedGoods->share_copywriting ?? 'NULL') . "\n";
        echo "- white_image: " . ($savedGoods->white_image ?? 'NULL') . "\n";
        echo "- created_at: " . ($savedGoods->created_at ?? 'NULL') . "\n";
        echo "- updated_at: " . ($savedGoods->updated_at ?? 'NULL') . "\n\n";
        
        // 测试更新功能
        echo "=== 测试更新功能 ===\n";
        $updateData = [
            'share_copywriting' => '更新后的分享文案_' . time(),
            'white_image' => 'updated_white_image_' . time() . '.jpg'
        ];
        
        $updateResult = $savedGoods->update($updateData);
        if ($updateResult) {
            echo "✅ 更新成功！\n";
            
            // 重新获取数据验证更新
            $updatedGoods = Goods::find($goods->id);
            echo "更新后的数据:\n";
            echo "- share_copywriting: " . ($updatedGoods->share_copywriting ?? 'NULL') . "\n";
            echo "- white_image: " . ($updatedGoods->white_image ?? 'NULL') . "\n";
        } else {
            echo "❌ 更新失败！\n";
        }
        
        // 清理测试数据
        $goods->delete();
        echo "\n🧹 测试数据已清理\n";
        
    } else {
        echo "❌ 商品保存失败！\n";
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}


