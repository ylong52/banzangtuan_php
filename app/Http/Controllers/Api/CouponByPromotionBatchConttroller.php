<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use GeoIp2\WebService\Client;
use App\Services\JdUnionClient;

class CouponByPromotionBatchConttroller extends Controller
{
    private $config;
    private $client;
    private $processingResults = [];

    /**
     * 构造函数
     * @param array $config 配置参数，包含appKey、appSecret等
     */
    public function init(array $config = [])
    {
        $defaultConfig = [
            'appKey' => '',
            'appSecret' => '',
            'accessToken' => '',
            'serverUrl' => 'https://api.jd.com/routerjson'
        ];

        $this->config = array_merge($defaultConfig, $config);

        // 只有在配置了appKey和appSecret时才初始化客户端
        if (empty($this->config['appKey']) || empty($this->config['appSecret'])) {
            throw new \Exception('appKey和appSecret不能为空');
        }

        // 从传入配置读取内容和subUnionId
        $content = $config['content'] ?? '';
        $subUnionId = $config['subUnionId'] ?? ($config['subUnionID'] ?? ($config['SubUnionId'] ?? null));
        $resultArr =[];
        if (!empty($content)) {
            $httpsLines = $this->extractHttpsLines($content);
            foreach ($httpsLines as $line) {
                try {
                    $result = $this->bysubunionidSingleUrl($line, $subUnionId);
                    // var_dump("50>>",$result);
                    if (!$result) {
                        $errorDetails = [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'error_type' => '转链失败',
                            'error_message' => 'bysubunionidSingleUrl 返回空结果',
                            'url' => $line,
                            'subUnionId' => $subUnionId,
                            'result_value' => $result, 
                           
                        ];

                        // // 打印详细错误信息
                        // echo "=== 转链失败详情 ===\n";
                        // echo "时间: " . $errorDetails['timestamp'] . "\n";
                        // echo "URL: " . $errorDetails['url'] . "\n";
                        // echo "SubUnionId: " . ($errorDetails['subUnionId'] ?? 'null') . "\n";
                        // echo "返回结果: " . $errorDetails['result_value'] . " (类型: " . $errorDetails['result_type'] . ")\n";
                        // echo "错误信息: " . $errorDetails['error_message'] . "\n";
                        // echo "上下文: 第 " . $errorDetails['context']['current_url_index'] . "/" . $errorDetails['context']['total_urls_found'] . " 个URL\n";
                        // echo "===================\n";

                        $resultArr[] = $errorDetails;
                    } else {
                        $resultArr[] = [
                            'success' => true,
                            'timestamp' => date('Y-m-d H:i:s'),
                            'url' => $line,
                            'result' => $result,
                            'subUnionId' => $subUnionId,
                            // 'context' => [
                            //     'content_length' => strlen($content),
                            //     'total_urls_found' => count($httpsLines),
                            //     'current_url_index' => array_search($line, $httpsLines) + 1
                            // ]
                        ];
                    }
                } catch (\Exception $e) {
                    // 控制异常错误处理 - 移到第77行附近
                    $exceptionDetails = [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'error_type' => '异常错误',
                        'error_message' => $e->getMessage(),
                        'exception_class' => get_class($e),
                        'url' => $line,
                        'subUnionId' => $subUnionId,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'context' => [
                            'content_length' => strlen($content),
                            'total_urls_found' => count($httpsLines),
                            'current_url_index' => array_search($line, $httpsLines) + 1
                        ],
                        'stack_trace' => $e->getTraceAsString()
                    ];

                    // 打印详细异常信息
                    // echo "=== 异常错误详情 ===\n";
                    // echo "时间: " . $exceptionDetails['timestamp'] . "\n";
                    // echo "异常类型: " . $exceptionDetails['exception_class'] . "\n";
                    // echo "URL: " . $exceptionDetails['url'] . "\n";
                    // echo "SubUnionId: " . ($exceptionDetails['subUnionId'] ?? 'null') . "\n";
                    // echo "错误信息: " . $exceptionDetails['error_message'] . "\n";
                    // echo "文件: " . $exceptionDetails['file'] . " (行: " . $exceptionDetails['line'] . ")\n";
                    // echo "上下文: 第 " . $exceptionDetails['context']['current_url_index'] . "/" . $exceptionDetails['context']['total_urls_found'] . " 个URL\n";
                    // echo "===================\n";

                    $resultArr[] = $exceptionDetails;
                }
            }

            // 输出处理总结
            $successCount = count(array_filter($resultArr, function($item) {
                return isset($item['success']) && $item['success'] === true;
            }));
            $errorCount = count($resultArr) - $successCount;

            // echo "\n=== 处理总结 ===\n";
            // echo "总URL数量: " . count($resultArr) . "\n";
            // echo "成功处理: {$successCount} 个\n";
            // echo "处理失败: {$errorCount} 个\n";
            // echo "成功率: " . (count($resultArr) > 0 ? round(($successCount / count($resultArr)) * 100, 2) : 0) . "%\n";
            // echo "===============\n";

            // 将结果保存到对象属性中，供后续访问
            $this->processingResults = $resultArr;
        }
    }

    /**
     * 获取构造函数中的URL处理结果
     * @return array 处理结果数组
     */
    public function getProcessingResults(): array
    {
        return $this->processingResults;
    }

    /**
     * 提取包含"https"链接的行数据
     * @param string $text 包含链接的文本
     * @return array 返回包含https链接的行数组
     */
    public function extractHttpsLines(string $text): array
    {
        $lines = explode("\n", $text);
        $result = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (strpos($trimmedLine, 'https') !== false) {
                $result[] = $trimmedLine;
            }
        }

        return $result;
    }

    /**
     * 在URL后面添加随机内容并更新整个字符串
     * @param string $text 原始文本
     * @param array $httpsLines 包含https链接的行数组
     * @return string 返回更新后的完整字符串
     */
    public function addRandomContentToUrls(string $text, array $httpsLines): string
    {
        $updatedText = $text;

        // 随机内容选项
        $randomContents = [
            ' 🔥限时优惠',
            ' ⭐超级划算',
            ' 💰超值推荐',
            ' 🎉热门商品',
            ' 🏷️厂家直销',
            ' 📦现货速发',
            ' 🎁赠品丰富',
            ' ⚡闪电发货',
            ' 💯正品保证',
            ' 🌟品质优良'
        ];

        foreach ($httpsLines as $line) {
            $randomContent = $randomContents[array_rand($randomContents)];
            $updatedLine = $line . $randomContent;

            // 替换原始字符串中的对应行
            $updatedText = str_replace($line, $updatedLine, $updatedText);
        }

        return $updatedText;
    }

    /**
     * 调用京东API的通用方法
     * @param string $method API方法名
     * @param array $bizParams 业务参数
     * @return array 返回API调用结果
     */
    public function callJdApi(string $method, array $bizParams): array
    {
 
        // try {
            // 更新配置中的method和其他必需参数
            $this->config['method'] = $method;
            $this->config['timestamp'] = date('Y-m-d H:i:s');
            $this->config['format'] = $this->config['format'] ?? 'json';
            $this->config['v'] = $this->config['v'] ?? '1.0';

            $tempClient = new JdUnionClient($this->config);
         
            $response = $tempClient->execute($bizParams);
// var_dump("116>>>>",$bizParams,$response);
// die;
            if (isset($response['error'])) {
                return ['success' => false, 'message' => "调用失败：" . $response['message']];
            } elseif ($response['http_code'] === 200) {
                if (isset($response['parsed']['error_response'])) {
                    return ['success' => false, 'message' => "接口返回错误：" . json_encode($response['parsed']['error_response'], JSON_UNESCAPED_UNICODE)];
                } else {
                    return ['success' => true, 'message' => '接口调用成功', 'data' => $response['parsed']];
                }
            } else {
                return ['success' => false, 'message' => "HTTP 请求失败，状态码：" . $response['http_code']];
            }
        // } catch (\Exception $e) {
        //     return ['success' => false, 'message' => '异常：' . $e->getMessage()];
        // }
    }

    /**
     * 创建礼金券
     * @param array $couponReq 礼金券请求参数
     * @return string 返回giftCouponKey，失败返回空字符串
     */
    public function createGiftCoupon(array $couponReq): string
    {
        $method = 'jd.union.open.coupon.gift.get';

        // 默认参数
        $defaultParams = [
            'receiveStartTime' => date('Y-m-d 00', strtotime('today')),
            'receiveEndTime' => date('Y-m-d 00', strtotime('+1 day')),
            'effectiveDays' => 1,
            'expireType' => 1
        ];

        $bizParams = [
            'couponReq' => array_merge($defaultParams, $couponReq),
            // 'appKey'=>$this->config['appKey'],
            // 'appSecret'=>$this->config['appSecret'],
        ];

        // var_dump("154 line",$bizParams);
    

        $result = $this->callJdApi($method, $bizParams);
        // var_dump("158 line",$result);
        // die;

        if ($result['success']) {
            $giftRet = $result['data']['jd_union_open_coupon_gift_get_responce']['getResult'] ?? '';
         
            if ($giftRet) {
                $giftRet2 = json_decode($giftRet, true);
                return $giftRet2['data']['giftCouponKey'] ?? '';
            }
        }

        return '';
    }

    /**
     * 生成推广链接
     * @param array $promotionReq 推广链接请求参数
     * @return string 返回shortURL，失败返回空字符串
     */
    public function generatePromotionLink(array $promotionReq): string
    {
        $method = 'jd.union.open.promotion.bysubunionid.get';
        // 'promotionCodeReq' => [
        //         'materialId' => $url, // 替换为实际的物料ID
        //         'subUnionId' => $subUnionId,
        //         'sceneld' => 1
        //     ]
        // 默认参数
        $defaultParams = [
            'sceneld' => 1
        ];

        $bizParams = [
            'promotionCodeReq' => array_merge($defaultParams, $promotionReq)
        ];

        $result = $this->callJdApi($method, $bizParams);

        if ($result['success']) {
            $getResult = $result['data']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'] ?? '';
            if ($getResult) {
                $getResult2 = json_decode($getResult, true);
                return $getResult2['data']['shortURL'] ?? '';
            }
        }

        return '';
    }

    /**
     * 完整的文本处理和推广流程
     * 提取URL、创建礼金券+推广链接、追加随机内容
     * @param string $text 原始文本
     * @return array 返回处理结果数组
     */
    public function processTextWithPromotion(array $param): array
    {
        $result = [];

        try {
            $this->init($param);
            $resultArr = $this->getProcessingResults();

            // 步骤4：统计与替换 - 根据 links 中的 shortURL 状态进行替换
            $successCount = 0;
            $errorCount = 0;
            $finalText = $param['content'];

            foreach ($resultArr as $value) {
                $shortUrl = $value['result']['shortURL'] ?? '';

                if ($value['success'] && !empty($shortUrl)) {
                    // 成功：用短链替换原链接
                    $successCount++;
                    $finalText = str_replace($value['url'], $shortUrl, $finalText);
                } else {
                    // 失败：用 span 标签包裹原链接（红色斜体）
                    $errorCount++;
                    $finalText = str_replace($value['url'], '<span style="color: red; font-style: italic;">' . $value['url'] . '</span>', $finalText);
                }
            }

            $result['successCount'] = $successCount;
            $result['errorCount'] = $errorCount;
            $result['finalText'] = $finalText;
            $result['success'] = true;
            $result['message'] = "成功数量：".$successCount."，失败数量：".$errorCount;

        } catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = '处理异常：' . $e->getMessage();
            $result['successCount'] = 0;
            $result['errorCount'] = 0;
            $result['finalText'] = $param['content'] ?? '';
        }

        return $result;
    }

    
    ################### 这是最新版的 
    public function bysubunionidSingleUrl(string $goods_url, ?string $subUnionId = null) 
    {
         
        $goods_url = trim($goods_url);
        $pattern = '/(https?:\\/\\/[^\\s]+)/u';

        if (!preg_match($pattern, $goods_url)) {
            throw new \Exception('转链取得的URL错误,无法分析!');
        }

        // preg_match($pattern, $goods_url, $matches);
        // $task_goods_url = $matches[1];
        //加入固定值，测试使用
        // $subUnionId = 'JQ2025gakr9t';
        // if (empty($subUnionId)) {
        //     // 在真实环境中从用户表获取 subUnionId，这里为测试必须提供
        //     throw new \Exception('subUnionId参数错误!');
        // }
        try {
        $goodsQuyerRet = $this->__goodsQuery($goods_url);
        $queryResult = json_decode($goodsQuyerRet['queryResult'],true);

        $queryRet['itemId'] = $queryResult['data'][0]['itemId']  ;

        // 检查是否有优惠券
        $couponList = $queryResult['data'][0]['couponInfo']['couponList'] ?? [];
        if (!empty($couponList)) {
            $queryRet['couponLink'] = $couponList[0]['link'];
        } else {
            // 没有优惠券时，使用空字符串或默认值
            $queryRet['couponLink'] = '';
        }
 
       
        ### 2,创建礼金券
        $giftCouponKey = $this->createGiftCoupon([
                'skuMaterialId' => $queryRet['itemId'],  // 商品ID
                'discount' => 10.00,             // 礼金10元
                'amount' => 100                  // 生成100张券
            ]);
          
        // die;
        //   var_dump("294>>","将链接转成新链接",  $giftCouponKey );
        // // var_dump("438>>",$itemId );
        //   die;            

        //####3,社交媒体获取推广链接接口
        $promotionRetLink  = $this->generatePromotionLink(['materialId' =>$queryRet['itemId'], 'giftCouponKey' => $giftCouponKey,'subUnionId'=>$subUnionId, 'couponUrl' => $queryRet['couponLink']]);

        // var_dump("441 》》》",$promotionRetLink);
        return ['success' => true, 'shortURL' => $promotionRetLink];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()||'转换错误'];
        }
    }


    
    private function __goodsQuery($keyword)  {
        // $keyword = "【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」点击链接直接打开";
        // $keyword = "【京东】https://3.cn/2odH-z1a「京东百亿补贴」点击链接直接打开";
        // if (empty(($keyword = $request->keyword))) {
        //     return response()->json(['status' => 'error','msg' => "keyword参数不能为空"]);
        // }
        $method ="jd.union.open.goods.query";
        $prams = [
            "keyword"=>$keyword,
            "fields"=>"purchasePriceInfo,documentInfo",
            'sceneId'=>1,
        ];
        // dump("313>>>",$method, $prams);
        try {
            $result = $this->callJdApi($method,['goodsReqDTO'=>$prams]);
            // dump("429 >>>>>>>>>>>>>>>>>>",$method, $prams,$result['data']);
            if ($result['success']) {
                return @$result['data']['jd_union_open_goods_query_responce'] ;
            }
           
            return false;
        } catch(\Exception $e) {
// dd("418>>>",$e->getMessage());            
            // throw new \Exception($e->getMessage());
            $errorMsg = $e->getMessage()." in ".$e->getFile()." on line ".$e->getLine();
            // dump("420>>>>",$errorMsg);
            return ['errorMsg'=>$errorMsg];
            // return null;
        }
    }
}

