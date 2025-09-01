<?php
require_once 'vendor/autoload.php';

// 加载Laravel应用
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Goods;
use Illuminate\Support\Facades\DB;

try {
    echo "=== 调试商品保存过程 ===\n\n";
    
    // 模拟实际业务场景的数据
    $goodsResult = [
        'skuName' => '测试商品_' . time(),
        'imageInfo' => [
            'imageList' => ['img1.jpg', 'img2.jpg'],
            'whiteImage' => 'white_image_test.jpg' // 这个字段有值
        ],
        'priceInfo' => ['price' => 199.99],
        'shopInfo' => ['shop' => '测试店铺'],
        'skuTagList' => ['tag1', 'tag2'],
        'keyword' => 'debug_keyword_' . time()
    ];
    
    $shareCopywriting = '这是测试分享文案_' . time(); // 这个字段有值
    
    echo "原始数据:\n";
    echo "- whiteImage: " . ($goodsResult['imageInfo']['whiteImage'] ?? 'NULL') . "\n";
    echo "- shareCopywriting: " . ($shareCopywriting ?? 'NULL') . "\n\n";
    
    // 准备商品数据（模拟JdGoodsController的逻辑）
    $goods = [];
    if (isset($goodsResult['imageInfo']['imageList']) && is_array($goodsResult['imageInfo']['imageList'])) {
        $goods['imagelist'] = json_encode($goodsResult['imageInfo']['imageList']);
    }
    $goods['goodsname'] = $goodsResult['skuName'];
    $goods['priceinfo'] = json_encode($goodsResult['priceInfo']);
    $goods['shopinfo'] = json_encode($goodsResult['shopInfo']);
    $goods['skutaglist'] = json_encode($goodsResult['skuTagList']);
    $goods['keyword'] = $goodsResult['keyword'];
    $goods['json'] = json_encode($goodsResult['priceInfo']);
    $goods['share_copywriting'] = $shareCopywriting ?? '';
    $goods['white_image'] = $goodsResult['imageInfo']['whiteImage'] ?? '';
    
    echo "准备保存的goods数组:\n";
    foreach ($goods as $key => $value) {
        echo "- {$key}: " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
    echo "\n";
    
    // 检查是否已存在相同商品
    $existingGoods = Goods::where('keyword', $goods['keyword'])->first();
    
    if ($existingGoods) {
        echo "找到现有商品，ID: {$existingGoods->id}\n";
        echo "现有数据:\n";
        echo "- share_copywriting: " . ($existingGoods->share_copywriting ?? 'NULL') . "\n";
        echo "- white_image: " . ($existingGoods->white_image ?? 'NULL') . "\n\n";
        
        // 更新现有商品
        echo "执行更新...\n";
        $updateResult = $existingGoods->update($goods);
        echo "更新结果: " . ($updateResult ? '成功' : '失败') . "\n";
        
        // 验证更新后的数据
        $updatedGoods = Goods::find($existingGoods->id);
        echo "更新后的数据:\n";
        echo "- share_copywriting: " . ($updatedGoods->share_copywriting ?? 'NULL') . "\n";
        echo "- white_image: " . ($updatedGoods->white_image ?? 'NULL') . "\n";
        
    } else {
        echo "未找到现有商品，创建新商品...\n";
        $goodsModel = new Goods();
        $goodsModel->fill($goods);
        $result = $goodsModel->save();
        
        if ($result) {
            echo "✅ 新商品创建成功！ID: {$goodsModel->id}\n";
            
            // 验证保存的数据
            $savedGoods = Goods::find($goodsModel->id);
            echo "保存后的数据验证:\n";
            echo "- share_copywriting: " . ($savedGoods->share_copywriting ?? 'NULL') . "\n";
            echo "- white_image: " . ($savedGoods->white_image ?? 'NULL') . "\n";
            
            // 清理测试数据
            $goodsModel->delete();
            echo "\n🧹 测试数据已清理\n";
        } else {
            echo "❌ 新商品创建失败！\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}


