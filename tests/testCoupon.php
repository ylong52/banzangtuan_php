<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Api\CouponByPromotionBatchConttroller;

class CouponByPromotionBatchControllerTest extends TestCase
{
    /**
     * 测试 bysubunionidSingleUrl 方法（单URL转链）
     */
    public function test_bysubunionid_single_url()
    {
        // 初始化调试信息
        echo "\n=== 开始测试 bysubunionidSingleUrl 方法 ===\n";
        
        $controller = new CouponByPromotionBatchConttroller(["appKey" => "e5f035c22a6ca67a748154f781bb6c20", "appSecret" => "e0d9c178fbf2444b9bb8dbf9f09e8365"]);
        echo "✓ 控制器实例化成功\n";

        $sampleUrl = 'https://u.jd.com/EgCavU9';
        $sampleSubUnionId = 'JQ2025gakr9t';

        // echo "测试参数:\n";
        // echo "  - URL: {$sampleUrl}\n";
        // echo "  - SubUnionId: {$sampleSubUnionId}\n";
        // echo "  - API配置: 无 (空配置)\n\n";

         
            $result = $controller->bysubunionidSingleUrl($sampleUrl, $sampleSubUnionId);
            echo "✓ 方法执行完成，返回结果类型: " . gettype($result) . "\n\n";

            // 打印完整结果
            echo "完整返回结果:\n";
            print_r($result);
            echo "\n";

            // 检查返回结构
            echo "结构检查:\n";
            $this->assertIsArray($result);
            echo "✓ 返回值是数组\n";

            $requiredKeys = ['errorTag', 'rawURL', 'shortURL', 'commissionShare', 'viewDetailUrl', 'isOrderTag', 'promotionBizParams'];
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $result);
                echo "✓ 包含键 '{$key}'\n";
            }

            // 检查预期行为
            echo "\n行为检查:\n";
            $this->assertTrue($result['errorTag']);
            echo "✓ errorTag 为 true (预期的API错误)\n";

            $this->assertEquals($sampleUrl, $result['rawURL']);
            echo "✓ rawURL 与输入URL匹配\n";

            $this->assertArrayHasKey('errorMsg', $result);
            echo "✓ 包含 errorMsg 字段\n";

            $this->assertStringContainsString('app_key 不能为空', $result['errorMsg']);
            echo "✓ 错误信息包含预期的 'app_key 不能为空'\n";

            // 打印关键字段值
            echo "\n关键字段值:\n";
            echo "  - errorTag: " . ($result['errorTag'] ? 'true' : 'false') . "\n";
            echo "  - rawURL: {$result['rawURL']}\n";
            echo "  - shortURL: {$result['shortURL']}\n";
            echo "  - commissionShare: {$result['commissionShare']}\n";
            echo "  - viewDetailUrl: {$result['viewDetailUrl']}\n";
            echo "  - isOrderTag: " . ($result['isOrderTag'] ? 'true' : 'false') . "\n";
            echo "  - errorMsg: {$result['errorMsg']}\n";

            if (isset($result['promotionBizParams'])) {
                echo "  - promotionBizParams: " . json_encode($result['promotionBizParams'], JSON_UNESCAPED_UNICODE) . "\n";
            }

            echo "\n=== 测试 bysubunionidSingleUrl 方法完成 ===\n";
 
    }
}