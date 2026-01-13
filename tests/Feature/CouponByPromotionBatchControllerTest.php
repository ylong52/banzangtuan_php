<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\CouponByPromotionBatchController;

class CouponByPromotionBatchControllerTest extends TestCase
{
    /**
     * 测试 bysubunionidSingleUrl 方法（单URL转链）
     */
    public function test_bysubunionid_single_url()
    {
        // 初始化调试信息
        echo "\n=== 开始测试 bysubunionidSingleUrl 方法 ===\n";

        // // 使用空配置，避免构造函数抛出异常
        $param['app_key'] = "e5f035c22a6ca67a748154f781bb6c20";
        $param['app_secret'] = "e0d9c178fbf2444b9bb8dbf9f09e8365";


        // $param['app_key'] = "5e5c17128f23045dcb984507f211aeda";
        // $param['app_secret'] = "9635a20754074d4598e4d6814bb451e4";

 

        $param['content'] = "💥【德施曼智能锁】8日价格来袭！！新增9折券！
✅收货后好评联系客服返现30元（下单前咨询客服确认是否有好评返现）
1⃣Q5FPro💰1637
https://u.jd.com/EgCavU9
2⃣V20F💰1107
https://u.jd.com/EGCs7jFBc
3⃣Q2F💰1182
https://u.jd.com/EGCb6JWC3
👆收货后好评联系客服返现30元";
        $param['SubUnionId'] = 'JQ2025gakr9t';

//          $param['content'] = "
// 《鹤7 Pro 25款》 广东、辽宁、江苏、四川、浙江（限量）、福建、山东、北京（限量）、上海（摇号）、黑龙江
// 1️⃣雷鸟电视 98鹤7 Pro 25款💰10943
// https://u.jd.com/EG1Hh4D
// 2️⃣雷鸟电视 85鹤7 Pro 25款💰6771
// https://u.jd.com/Ea1APWC
// 3️⃣雷鸟电视 75鹤7 Pro 25款💰5330
// https://u.jd.com/E11NiIw
// 4️⃣雷鸟电视 65鹤7 Pro 25款💰3976
// https://u.jd.com/EG15qTB
//          ";
        $param['amount'] = 2;
        $param['discount'] = 10;

        // 在本测试内以 API 风格调用控制器的 processTextWithPromotion 方法
                $controller = new CouponByPromotionBatchController();
                $request = new Request();
                $request->merge($param);
                $apiResponse = $controller->processTextWithPromotion($request);

        // 只输出 JSON 响应，模拟 API 返回
        echo json_encode($apiResponse, JSON_UNESCAPED_UNICODE) . "\n";

    }

    /**
     * 测试URL替换时保留前缀文本
     */
    public function test_preserve_prefix_text_when_replacing_urls()
    {
        // 创建模拟的处理结果，包含带前缀的URL
        $mockResultArr = [
            [
                'success' => false, // 模拟失败，URL会被标记为红色
                'orgUrl' => '2⃣️953券：https://u.jd.com/EGOntxA',
                'promotionRetLink' => '',
                'subUnionId' => 'JQ2025gakr9t',
                'error' => 'Test error'
            ]
        ];

        // 模拟原始文本
        $originalText = "产品介绍：\n2⃣️953券：https://u.jd.com/EGOntxA\n其他内容";

        // 手动执行替换逻辑（模拟控制器中的逻辑）
        $finalText = $originalText;
        foreach ($mockResultArr as $value) {
            // 使用正则表达式找到URL部分
            $urlPattern = '/(https?:\/\/[^\s]+)/u';
            if (preg_match($urlPattern, $value['orgUrl'], $matches)) {
                $urlInLine = $matches[1]; // 匹配到的URL部分

                // 处理失败的情况：将URL标记为红色错误样式
                if (!($value['success'] && !empty($value['promotionRetLink']))) {
                    $finalText = str_replace($urlInLine, '<span style="color: red; font-style: italic;">' . $urlInLine . '</span>', $finalText);
                }
            }
        }

        // 验证结果
        $this->assertStringContains('2⃣️953券：', $finalText, '前缀文本应该被保留');
        $this->assertStringContains('<span style="color: red; font-style: italic;">https://u.jd.com/EGOntxA</span>', $finalText, 'URL应该被红色span包装');
        $this->assertStringNotContains('2⃣️953券：<span', $finalText, '前缀文本不应该被span包装');

        echo "\n=== 前缀保留测试 ===\n";
        echo "原始文本: $originalText\n";
        echo "处理后: $finalText\n";
    }

}


