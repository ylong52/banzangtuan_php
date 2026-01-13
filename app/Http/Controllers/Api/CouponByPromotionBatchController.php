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

class CouponByPromotionBatchController extends Controller
{
    private $appKey,$appSecret;

     /**
     * 完整的文本处理和推广流程
     * 提取URL、创建礼金券+推广链接、追加随机内容
     * @param Request $request HTTP请求对象
     * @return array 返回处理结果数组
     */
    public function processTextWithPromotion(Request $request): array
    {
        $result = [];

        if ($request->has('SubUnionId')) {
            $subUnionId = $request->input('SubUnionId');

        } else {
            $subUnionId = User::where('id', Auth::id())->value('sub_union_id');
        }

        // $subUnionId = User::where('id', Auth::id())->value('sub_union_id');

        // if (!$subUnionId) {
        //     $subUnionId = 'JQ2025gakr9t';
        // }

        try {
            // 从请求中提取参数
            $param = [
                'appKey' => $request->input('app_key'),
                'appSecret' => $request->input('app_secret'),
                'amount' => $request->input('amount', 0),
                'discount' => $request->input('discount', 0),
                'content' => $request->input('content') ,
                'subUnionId' => $subUnionId
            ];
            $this->appKey = $param['appKey'];
            $this->appSecret = $param['appSecret'];
            //加入数据验证
            $validator = Validator::make($param, [
                'appKey' => 'required',
                'appSecret' => 'required',
                'amount' => 'required|numeric|min:1',
                'discount' => 'required|numeric|min:1',
                'content' => 'required|string',
                'subUnionId' => 'required|string'
            ],
            [
                'appKey.required' => '请输入appKey',
                'appSecret.required' => '请输入appSecret',
                'amount.required' => '请输入券数量',
                'amount.numeric' => '券数量必须为数字',
                'amount.min' => '券数量不能小于0',
                'discount.required' => '请输入礼金金额',
                'discount.numeric' => '礼金金额必须为数字',
                'discount.min' => '礼金金额不能小于0',
                'content.required' => '请输入要处理的内容',
                'content.string' => '内容格式不正确',
                'subUnionId.required' => '请输入subUnionId',
                'subUnionId.string' => 'subUnionId格式不正确'
            ]);
            if ($validator->fails()) {
                return ['success' => false, 'message' => $validator->errors()->first()];
            }


            //==== 从传入配置读取内容和subUnionId
            $content = $param['content'];
            $subUnionId = $param['subUnionId'] ;
            $amount = $param['amount'] ;
            $discount = $param['discount'] ;
            $resultArr =[];
            $exceptionDetails = [];
            $successCount = 0;
            $httpsLines = $this->extractHttpsLines($content);
            foreach ($httpsLines as $line) {
                try {
                    $promotionRetLink = $this->bysubunionidSingleUrl($line, $subUnionId,$amount,$discount);
                    // var_dump("50>>",$result,$processingResultsArr);
                    $successCount ++;
                    $resultArr[] = [
                        'success' => true,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'orgUrl' => $line,
                        'promotionRetLink' => $promotionRetLink,
                        'subUnionId' => $subUnionId,
                    ];
                } catch (\Exception $e) {
                    // 控制异常错误处理 - 移到第77行附近
                    $exceptionDetails[] =  $e->getMessage();
                    // 将失败的URL也添加到结果数组中，方便后续处理
                    $resultArr[] = [
                        'success' => false,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'orgUrl' => $line,
                        'promotionRetLink' => '',
                        'subUnionId' => $subUnionId,
                        'error' => $e->getMessage()
                    ];
                    continue;
                }
            }



            // 步骤4：统计与替换 - 根据 links 中的 shortURL 状态进行替换

            $finalText = $param['content'];

            foreach ($resultArr as $value) {
                // 使用正则表达式找到URL部分
                $urlPattern = '/(https?:\/\/[^\s]+)/u';
                if (preg_match($urlPattern, $value['orgUrl'], $matches)) {
                    $urlInLine = $matches[1]; // 匹配到的URL部分

                    // 处理成功的情况：替换URL部分
                    if ($value['success'] && !empty($value['promotionRetLink'])) {
                        $shortUrl = $value['promotionRetLink'];
                        $finalText = str_replace($urlInLine, $shortUrl, $finalText);
                    } else {
                        // 处理失败的情况：将URL标记为红色错误样式
                        $finalText = str_replace($urlInLine, '<span style="color: red; font-style: italic;">' . $urlInLine . '</span>', $finalText);
                    }
                }
                // 如果没有找到URL，保持原样（理论上不应该发生，因为extractHttpsLines已经过滤过了）
            }

            $result['successCount'] = $successCount;
            $result['finalText'] = $finalText;
            $result['debuginfo'] = $resultArr; // 添加调试信息

            if ($successCount > 0) {
                $result['code'] = 0;
                $result['message'] = "成功数量：".$successCount ;
            } else {
                $result['code'] = -1;
                $result['message'] = "操作失败：".implode(',',$exceptionDetails) ;
            }

//            $result['debuginfo'] = $resultArr;

        } catch (\Exception $e) {
            $result['code'] = -1;
            $result['message'] = '处理异常：' . $e->getMessage();
            $result['successCount'] = 0;
            $result['finalText'] = isset($param) ? ($param['content'] ?? '') : '';
        }

        return $result;
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
            $config['method'] = $method;
            $config['timestamp'] = date('Y-m-d H:i:s');
            $config['format'] = 'json';
            $config['v'] = '1.0';
            $config['appKey'] = $this->appKey;
            $config['appSecret'] = $this->appSecret;
            $config['accessToken'] = '';
            $config['serverUrl'] = 'https://api.jd.com/routerjson';

            $tempClient = new JdUnionClient($config);
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
    public function createGiftCoupon(array $couponReq)
    {
        $method = 'jd.union.open.coupon.gift.get';
        try {
                // 默认参数
                $defaultParams = [
                    'receiveStartTime' => date('Y-m-d 00', strtotime('today')),
                    'receiveEndTime' => date('Y-m-d 00', strtotime('+1 day')),
                    'effectiveDays' => 1,
                    'expireType' => 1
                ];

                $bizParams = [
                    'couponReq' => array_merge($defaultParams, $couponReq),                   
                ];

                // var_dump("154 line",$bizParams);


                $result = $this->callJdApi($method, $bizParams);
                // var_dump("158 line",$result);
                // die;

                // 检查 API 调用是否成功
                if (!$result['success']) {
                    throw new \Exception('API调用失败: ' . ($result['message'] ?? '未知错误'));
                }

                $giftRet = $result['data']['jd_union_open_coupon_gift_get_responce']['getResult'] ?? '';

                if (empty($giftRet)) {
                    throw new \Exception('API返回数据为空');
                }

                $giftData = json_decode($giftRet, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON解析失败: ' . json_last_error_msg());
                }


                if ($giftData['code']!=200) {
                    throw new \Exception('创建礼金券失败：'.$giftData['message']);
                }
                if (!isset($giftData['data']['giftCouponKey']) || empty($giftData['data']['giftCouponKey'])) {
                    throw new \Exception('创建礼金券失败：无效的giftCouponKey');
                }

                return $giftData['data']['giftCouponKey'];
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage() ?: '创建礼金失败!');
            }


    }

    /**
     * 生成推广链接
     * @param array $promotionReq 推广链接请求参数
     * @return string 返回shortURL，失败返回空字符串
     */
    public function generatePromotionLink(array $promotionReq): string
    {
        // var_dump("290>>>",$promotionReq);
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


    ################### 这是最新版的
    // $promotionRetLink = $this->bysubunionidSingleUrl($line, $subUnionId,$amount,$discount);

    public function bysubunionidSingleUrl($goods_url,$subUnionId ,$amount,$discount)
    {

            $goods_url = trim($goods_url);
            $pattern = '/(https?:\\/\\/[^\\s]+)/u';

            if (!preg_match($pattern, $goods_url)) {
                throw new \Exception('转链取得的URL错误,无法分析!');
            }
            $goodsQuyerRet = $this->__goodsQuery($goods_url);

            // 检查 __goodsQuery 的返回值
            if ($goodsQuyerRet === false) {
                throw new \Exception('商品查询API调用失败');
            }

            if (is_array($goodsQuyerRet) && isset($goodsQuyerRet['errorMsg'])) {
                throw new \Exception('商品查询异常: ' . $goodsQuyerRet['errorMsg']);
            }

            // 解析 queryResult
            if (!isset($goodsQuyerRet['queryResult'])) {
                throw new \Exception('商品查询响应格式错误：缺少queryResult字段');
            }

            $queryResult = json_decode($goodsQuyerRet['queryResult'], true);
            if ($queryResult === false || json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('商品查询响应JSON解析失败: ' . json_last_error_msg());
            }
            $queryRet['itemId'] = $queryResult['data'][0]['itemId']  ;
            if (empty($queryRet['itemId']) || $queryRet['itemId']=='') {
                throw new \Exception('转链取得的URL错误,无法分析!');
            }

// var_dump("398》》》","appkey=".$this->appKey,"appsecret=".$this->appSecret,"queryRet['itemId']>>>>",$queryRet['itemId'],"queryResult['data'][0]=====",$queryResult['data'][0]['couponInfo']);
// die;
            // 检查是否有优惠券
            $queryRet['couponLink'] =  '';  //礼金券，没券也可以直接加礼金
            $couponList = $queryResult['data'][0]['couponInfo']['couponList'] ?? [];
            if ($couponList && isset($couponList[0]['link']) && $couponList[0]['link']!=false) {
                
                $queryRet['couponLink'] = $couponList[0]['link'];
            }
           
// var_dump("439》》》",$amount,$discount);
            ### 2,创建礼金券
            $giftCouponKey = $this->createGiftCoupon([
                    'skuMaterialId' => $queryRet['itemId'],  // 商品ID
                    'discount' => $discount,             // 礼金10元
                    'amount' => $amount,                 // 生成1张券
                ]);
           // $giftCouponKey 在礼金券，业务上是可以为空的
 

            //####3,社交媒体获取推广链接接口
            $promotionRetLink  = $this->generatePromotionLink(['materialId' =>$queryRet['itemId'], 'giftCouponKey' => $giftCouponKey,'subUnionId'=>$subUnionId, 'couponUrl' => $queryRet['couponLink']]);
            if (false==$promotionRetLink) {
                throw new \Exception('生成推广链接失败!');
            }
            // var_dump("441 》》》",$promotionRetLink);
            return  $promotionRetLink ;
       
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
                $responseData = @$result['data']['jd_union_open_goods_query_responce'];
                if ($responseData && isset($responseData['queryResult'])) {
                    return $responseData;
                } else {
                    return ['errorMsg' => '响应数据格式错误'];
                }
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

