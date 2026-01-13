<?php
//
/**
 * 京东开放平台接口调用函数
 * @param string $appKey 应用Key
 * @param string $appSecret 应用密钥
 * @param string $method API方法名
 * @param array $bizParams 业务参数
 * @param string $accessToken 访问令牌(可选)
 * @return array 返回API调用结果，成功时包含完整的解析后响应数据，失败时包含错误信息。
 */
function callJdApi($appKey, $appSecret, $method, $bizParams, $accessToken = '') {
    require_once 'JdUnionClient.php';
    
    $config = [
        'appKey' => $appKey,
        'appSecret' => $appSecret,
        'accessToken' => $accessToken,
        'serverUrl' => 'https://api.jd.com/routerjson',
        'method' => $method,
        'format' => 'json',
        'v' => '1.0',
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    $client = new JdUnionClient($config);
    $response = $client->execute($bizParams);

    try {
        if (isset($response['error'])) {
            return ['success' => false, 'message' => "调用失败：" . $response['message'], 'data' => null];
        } elseif ($response['http_code'] === 200) {
            if (isset($response['parsed']['error_response'])) {
                return ['success' => false, 'message' => "接口返回错误：" . json_encode($response['parsed']['error_response'], JSON_UNESCAPED_UNICODE), 'data' => $response['parsed']];
            } else {
                // 成功时返回完整的解析后响应数据
                return ['success' => true, 'message' => '接口调用成功', 'data' => $response['parsed']];
            }
        } else {
            return ['success' => false, 'message' => "HTTP 请求失败，状态码：" . $response['http_code'] . "，响应：" . $response['response'], 'data' => null];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
    }
}

/**
 * 调用京东联盟推广接口 jd.union.open.promotion.bysubunionid.get
 * 根据截图传参，并提取 shortURL 字段
 * @param string $appKey 应用Key
 * @param string $appSecret 应用密钥
 * @param array $bizParams 业务参数，例如 ['materialId' => '推广物料ID', 'subUnionId' => '子联盟ID']
 * @param string $accessToken 访问令牌(可选)
 * @return array 返回API调用结果，包含 shortURL 或错误信息
 */
function callPromotionBySubUnionIdApi($appKey, $appSecret, $bizParams, $accessToken = '') {
    $method = 'jd.union.open.promotion.bysubunionid.get';
    $result = callJdApi($appKey, $appSecret, $method, $bizParams, $accessToken);
// echo "<pre> 59 >>>>> "; var_dump($bizParams, $result); die;
    if ($result['success']) {
        $data = $result['data'];
        // 根据API文档，jd.union.open.promotion.bysubunionid.get 接口的返回结构
        // 假设 shortURL 在 data['jd_union_open_promotion_bysubunionid_get_responce']['data']['shortURL'] 或类似路径下
        // 实际路径可能需要根据京东联盟API的最新文档进行调整
        $shortURL = 'N/A';
        $apiResponseKey = str_replace('.', '_', $method) . '_responce';

        if (isset($data[$apiResponseKey]['data']['shortURL'])) {
            $shortURL = $data[$apiResponseKey]['data']['shortURL'];
        } else if (isset($data['data']['shortURL'])) { // 尝试直接在data下查找
            $shortURL = $data['data']['shortURL'];
        } else if (isset($data['result']['shortURL'])) { // 尝试在result下查找
            $shortURL = $data['result']['shortURL'];
        }
        
        return ['success' => true, 'message' => 'API调用成功', 'shortURL' => $shortURL, 'full_response' => $data];
    } else {
        return $result;
    }
}

// 示例调用
 
$appKey = '77ed81195127ae87148f5bebce9d28fb';
$appSecret = '69294b9488cd40b8a64ade8be19e52bb';
$method = 'jd.union.open.goods.query';
$keyword = $_GET['keyword'];
$pattern = '/(?=.*京东)(?=.*https?:\/\/)/u';
if (!preg_match($pattern, $keyword)) {
    echo json_encode(['code'=>500,'msg'=>'参数格式错误，必须包含"京东"和有效的URL链接']);
    die;
}
// $couponlink = "https://coupon.m.jd.com/coupons/show.action?linkKey=AAROH_xIpeffAs_-naABEFoee5tYKR7b9JZyKpGiFSeVYQARBxy5eqjGVHE2KfXHP9-2XgCUeRHzKUY4qiBat4mM2V39_g";
// bysubunionid($couponlink);
// die;

    try {
        $bizParams = [
            'goodsReqDTO' => [
        //         'keyword' => '
        // 点击链接直接打开',
                'keyword'=> $keyword,
                'pageIndex' => 1,
                'pageSize' => 2,
            ],
        ];

        $result = callJdApi($appKey, $appSecret, $method, $bizParams);
        $goodsInfo = [];
        if ($result['success']) {
            // echo "<h3>API调用成功，提取关键信息：</h3>";
            $data = $result['data'];
            // print_r($data['jd_union_open_goods_query_responce']['queryResult']  ); die;
            if ($data['jd_union_open_goods_query_responce']['code']==0 && $data['jd_union_open_goods_query_responce']['code']==0 ) {
                
                $data = json_decode($data['jd_union_open_goods_query_responce']['queryResult'],true);
                // echo "<pre> 114 ";
                // var_dump($data['data'][0]['couponInfo']['couponList'][0]['link']); die;
            
                $firstProduct = $data['data'][0];        
                // 1. skuName
                $skuName = $firstProduct['skuName'] ?? 'N/A';
                // echo "<p>skuName: " . htmlspecialchars($skuName) . "</p>";
                $goodsInfo['skuName'] =  htmlspecialchars($skuName);
                // 2. comments
                $comments = $firstProduct['comments'] ?? 'N/A';
                // echo "<p>comments: " . htmlspecialchars($comments) . "</p>";
                $goodsInfo['comments'] =  htmlspecialchars($comments);

                // 3. imageList (取索引0的url)
                $imageUrl = 'N/A';
                if (isset($firstProduct['imageInfo']['imageList'][0]['url'])) {
                    $imageUrl = $firstProduct['imageInfo']['imageList'][0]['url'];
                }
                // echo "<p>imageList[0].url: " . htmlspecialchars($imageUrl) . "</p>";
                $goodsInfo['imageUrl'] =  htmlspecialchars($imageUrl);

                // 4. materialUrl
                $materialUrl = $firstProduct['materialUrl'] ?? 'N/A';
                // echo "<p>materialUrl: " . htmlspecialchars($materialUrl) . "</p>";
                $goodsInfo['materialUrl'] =  htmlspecialchars($materialUrl);
                // 5. purchasePrice
                $purchasePrice = $firstProduct['priceInfo']['price'] ?? 'N/A'; // 假设 purchasePrice 在 priceInfo.price
                // echo "<p>purchasePrice: " . htmlspecialchars($purchasePrice) . "</p>";
                
                $goodsInfo['purchasePrice'] =  htmlspecialchars($purchasePrice);
                $goodsInfo['couponlink'] =  $data['data'][0]['couponInfo']['couponList'][0]['link']; 
                $goodsInfo['itemId'] = $firstProduct['itemId'];
                $goodsInfo['couponCommission'] = $firstProduct['commissionInfo']['couponCommission'];
                $goodsInfo['discount'] = '';
                // $goodsInfo['couponCommission'] = 0.3;
                if ($goodsInfo['couponCommission']) {
                    $goodsInfo['discount'] = round($goodsInfo['couponCommission']/15,2);
                }
                
                $goodsInfo['amount'] = calculateAdjustedY($goodsInfo['discount']);  //加工佣金
//                 echo "<pre> 149 >>>";
// var_dump($goodsInfo  );    
  
                $goodsInfo['giftCouponKey'] = by_jd_union_open_coupon_gift_get();
                              
                by_jd_union_open_promotion_bysubunionid_get();

// var_dump("163>>>", $goodsInfo);                
                echo json_encode(['code'=>200,'data'=>$goodsInfo]);
                die;
            } else {
                echo json_encode(['code'=>500,'line'=>'158','msg'=>'未找到符合条件的卷!']); die;
            }
        } else {
            // echo "<h3>API调用失败：</h3>";
            // echo "<p>" . htmlspecialchars($result['message']) . "</p>";
            echo json_encode(['code'=>500,'line'=>'163','msg'=>'未找到符合条件的卷!']); die;
        }
    } catch (Exception $e) {
        echo json_encode(['code'=>500,'line'=>'166','msg'=>$e->getMessage()]);
        die;
    }
    
/**
 * 计算满足条件的调整后数量 y
 * @param float $x 礼金金额
 * @return int|false 调整后的 y 值，若无法满足条件则返回 false
 */

function calculateAdjustedY($x) {
    global $goodsInfo;
    // 检查礼金是否为正数
    if ($x <= 0) {
        $goodsInfo['discount'] = 0;
        return 0;
    }
    if ($x < 1) {
        $goodsInfo['discount'] = 0;
        return 0;
        
    }
    if ($x >50 ) {
        $goodsInfo['discount'] = 50;
        return 1;
    }

    // 计算初始数量y（向上取整）
    $y = ceil(20 / $x);
    $S = $x * $y;

    // 处理S > 50的情况：减小y直到S ≤ 50或y=1
    if ($S > 50) {
        $goodsInfo['discount'] = 50;
        return 1;
    }
    // 处理S ≤ 20的情况：增大y直到S > 20
    elseif ($S <= 20) {
        while ($S <= 20) {
            $y++;
            $S = $x * $y;
        }
        // 若增大后超过50，返回false
        if ($S > 50) {
            $goodsInfo['discount'] = 50;
            return 1;
        }
    }

    // 返回调整后的y值
    return $y?$y:'';
}

function by_jd_union_open_coupon_gift_get() {
    global $appKey, $appSecret ,$goodsInfo;
    $promotionBizParams = [
        'couponReq' => [
            'skuMaterialId' => $goodsInfo['itemId'],
            'discount' => $goodsInfo['discount'], // 为礼金数
            'amount' => $goodsInfo['amount'],
            'receiveStartTime' => date('Y-m-d 00', strtotime('today')),
            'receiveEndTime' => date('Y-m-d 00', strtotime('+1 day')),
            'effectiveDays' => 1, //消费者领取后n天内可用，时间天数1至7，当expireType=1时，必须设置该字段 
            'expireType'=>1
        ]
    ];
    try {
        $method = 'jd.union.open.coupon.gift.get';
        $result = callJdApi($appKey, $appSecret, $method, $promotionBizParams);
    // echo "<pre>237,,";
    // var_dump($promotionBizParams,$result); die;
        if ($result['success']) {
            $giftRet2 = $result['data']['jd_union_open_coupon_gift_get_responce']['getResult'];
                   
            $giftRet3 = json_decode($giftRet2,true);
            return  $giftRet3['data']['giftCouponKey'];
        } else {
            return '';
        }
        

    } catch (Exception $e) {
        echo json_encode(['code'=>500,'line'=>'182','msg'=>$e->getMessage()]);
        die;
    }
}

function by_jd_union_open_promotion_bysubunionid_get() 
{
    global $appKey, $appSecret ,$goodsInfo;
    // 示例调用 jd.union.open.promotion.bysubunionid.get 接口
    // echo "<h3>调用 jd.union.open.promotion.bysubunionid.get 接口：</h3>";
    // ... existing code ...
        // 构建promotionBizParams参数结构
        $promotionBizParams = [
            'promotionCodeReq' => [
                'materialId' => $goodsInfo['itemId'], // 替换为实际的物料ID
                'giftCouponKey' => $goodsInfo['giftCouponKey'],
                'couponUrl' => $goodsInfo['couponlink'],
                'sceneld' => 1
            ]
        ];
    try {
            $method =  "jd.union.open.promotion.bysubunionid.get";
            $result = callJdApi($appKey, $appSecret, $method, $promotionBizParams);
// echo "<pre>267 ,,";
// var_dump($promotionBizParams,$result); die;
            $getResult = $result['data']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
            $getResult2 = json_decode($getResult,true);
            $goodsInfo['shortURL']= $getResult2['data']['shortURL']; 
            
            return true;
        
    } catch (Exception $e) {
        echo json_encode(['code'=>500,'msg'=>$e->getMessage()]);
        die;
    }
}

