<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use App\Services\FeixpayRsaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use App\Models\GlobalConfig;
use App\Models\DynamicProperty;
use App\Models\RechargeRecord;
use App\Models\Orders;
use App\Models\User;
/*
微信
支付宝
充值管理 
*/
class JdGoodsSevice
{
    public $appkey, $appSecret;
    public function __construct()
    {        
        $this->appkey = "49307c59823cec6b2f0fb490f0d3c957";
        $this->appSecret = "fe774b73fb63417bad12286ad8f716f7";                 
    }

    function goodsQuery($keyword)  {
        if (empty($keyword)) {
            throw new \Exception("keyword参数不能为空");
        }
        // $keyword = "【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」点击链接直接打开";
        preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+(?:「[^」]*」)?)/', $keyword, $matches);
        $url = isset($matches[0]) ? $matches[0] : '';  
        try {
            $goodsInfo = $this->__goodsQuery($url);
            return $goodsInfo;
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    //转链
    //必须下单后，才能查到订单
    public function bysubunionid($keyword,$subUnionId)    
    {
        
        if (empty($keyword)) {  
            throw new \Exception('item_id参数错误!');
        }
        if (empty($subUnionId)) {
            throw new \Exception('subUnionId参数错误!');
        }
        $pattern = '/(https?:\/\/[^\s]+)/u';
        if (!preg_match($pattern, $keyword)) {
            throw new \Exception("转链取得的URL错误,无法分析!");
        }

        preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+(?:「[^」]*」)?)/', $keyword, $matches);
        $url = isset($matches[0]) ? $matches[0] : '';          
        if (empty($url)) {  
            throw new \Exception('item_id参数错误!');
        }
 
        $promotionBizParams = [
            'promotionCodeReq' => [
                'materialId' => $url, // 替换为实际的物料ID
                'subUnionId' => $subUnionId,
                'sceneld' => 1
            ]
        ];
        
        $method =  "jd.union.open.promotion.bysubunionid.get";
        $shortURL = '';
        $response = $this->callJdApi($method, $promotionBizParams);
        if (empty($response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'])) {
            throw new \Exception("操作失败!");
        }
        $getResultStr = $response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
        $getResult = json_decode($getResultStr,true);

        if ($getResult['code']!=200) {
            throw new \Exception($getResult['message']);
        }  
        if (empty($getResult['data']['shortURL'])){
            throw new \Exception("转链失败!");
        } 
        // dd("70 >>>",$getResult);;
        // $goodsInfo = $this->__goodsQuery($getResult['data']['shortURL']);
        $shortURL = $getResult['data']['shortURL'];               
        return ['shortURL'=>$shortURL,'data'=>$getResult['data']];
         
    }


    function __goodsQuery($keyword)  {
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
        try {
            $result = $this->callJdApi($method,['goodsReqDTO'=>$prams]);
            $queryResult = [];
            if (isset($result['response']) && is_string($result['response'])) {
                $result['response'] = json_decode($result['response'],true);
                if (isset($result['response']['jd_union_open_goods_query_responce']['queryResult']) && is_string($result['response']['jd_union_open_goods_query_responce']['queryResult']) ) {
                    $queryResult = json_decode($result['response']['jd_union_open_goods_query_responce']['queryResult'],true);
                }
            }
            if (!$queryResult) {
                throw new \Exception("操作失败!");
            }
            
            if ($queryResult['code']!=200) {
                throw new \Exception($queryResult['message'] || "操作失败!");
            }
            if ($queryResult['totalCount']==0) {
                throw new \Exception("没有找到商品!");
            }
            $goods = $queryResult['data'][0]; 
            $goods['keyword'] = $keyword;
            return $goods;
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }


    //订单查询，已下单未支付的。只能查1小时之内有
    //此处无法使用缓存，订单状态经常在变化。订单24小时未支付会自动取消
    //https://union.jd.com/openplatform/api/v2?apiName=jd.union.open.order.row.query
    function orderQuery($starTime,$pageIndex) {
        $method ="jd.union.open.order.row.query";
        $orderReq = [            
            "pageSize"=>200,
            "fields"=>"goodsInfo",
            "type"=>1,  //(1：下单时间，2：完成时间（购买用户确认收货时间），3：更新时间                
        ];       
        $orderReq['startTime'] = $starTime;
        $orderReq['endTime'] = date('Y-m-d H:i:s', strtotime($starTime) + 3600);                 
        $orderReq['pageIndex'] = $pageIndex;                
        $orderReq['pageSize'] = 200;
        echo "start_time:".$starTime.",end_time:".$orderReq['endTime'].",pageIndex:".$pageIndex."\n";
        try {
            $result = $this->callJdApi($method,['orderReq'=>$orderReq]);
            $queryResult = null;
            if ($result['http_code']==200 && isset($result['response']) && is_string($result['response'])) {
                $result['response'] = json_decode($result['response'],true);
                if (isset($result['response']['jd_union_open_order_row_query_responce']['queryResult']) && is_string($result['response']['jd_union_open_order_row_query_responce']['queryResult'])) {
                    $queryResult = json_decode($result['response']['jd_union_open_order_row_query_responce']['queryResult'],true);
                }
            }
            if ($queryResult == null) {
                // throw new \Exception("操作失败!");
                return ['hasMore'=>false];
            }
            save_log($queryResult,"jd_order_query_result");
// dd("171>>>",$queryResult['hasMore']);            
            $hasMore = $queryResult['hasMore']??false;  //用于翻页还有数据吗？
            if (!isset($queryResult['hasMore'])) {
                return ['hasMore'=>false];
            }
            if (count($queryResult['data'])>0) {
                foreach ($queryResult['data'] as $order) {
                    // 从goodsInfo中提取商品相关信息
                    $goodsInfo = $order['goodsInfo'] ?? [];
                    if (empty($order['subUnionId'])) {
                        continue;
                    }
                    // 准备订单数据
                    $orderData = [
                        'id' => $order['id'] ?? '',
                        'user_id' => $this->getUserIdBySubUnionId($order['subUnionId']),  
                        'sub_union_id' => $order['subUnionId'] ?? '',
                        'sku_name' => trim($order['skuName']) ?? '',
                        'order_id' => $order['orderId'] ?? '',
                        'sku_num' => $order['skuNum'] ?? 0,
                        'price' => $order['price'] ?? 0,
                        'total_price' => round($order['skuNum'] * $order['price'], 2),
                        'sku_id' => $order['skuId'] ?? '',
                        'valid_code' => $order['validCode'] ?? 0,
                        'trace_type' => $order['traceType'] ?? 0,
                        'image_url' => $this->formatImageUrl($goodsInfo['imageUrl']) ?? '', // 从goodsInfo中获取
                        'shop_name' => trim($goodsInfo['shopName']) ?? '', // 从goodsInfo中获取
                        'actual_cos_price' => $order['actualCosPrice']?($order['actualCosPrice']*0.9): 0,
                        'commission_rate' => $order['commissionRate'] ?? 0,
                        'estimate_cos_price' => $order['estimateCosPrice'] ?? 0,
                        'estimate_fee' => $order['estimateFee']?($order['estimateFee']*0.9):0,    //折扣0.9
                        'order_time' => !empty($order['orderTime']) ? $order['orderTime'] : null,
                        'modify_time' => !empty($order['modifyTime']) ? $order['modifyTime'] : null,
                        'finish_time' => !empty($order['finishTime']) ? $order['finishTime'] : null,
                    ];

                    // 查找现有订单
                    $existingOrder = Orders::where('id', $order['id'])->first();
                    
                    if ($existingOrder) {
                        // 只检查modify_time字段是否有变化
                        $hasChanges = false;
                        if ($existingOrder->modify_time != $orderData['modify_time']) {
                            $hasChanges = true;
                        }                        
                        // 只有当modify_time有变化时才更新
                        if ($hasChanges) {
                            // 确保updated_at字段会被更新
                            $orderData['updated_at'] = now();
                            $existingOrder->update($orderData);
                        }
                    } else {
                        Orders::create($orderData);
                    }
                }
            }
            // return response()->json(['status' => 'success','msg' => 'success','queryResult'=>$queryResult['data']??[]]);
            // echo "\n"."starTime:".$orderReq['startTime'].",endTime:".$orderReq['endTime']."\n";
            return ['hasMore'=>$hasMore];

        } catch(\Exception $e) {
            echo "\n error:".$e->getMessage()."\n";
            return ['hasMore'=>$hasMore];
        }
    }

    private function formatImageUrl($imageUrl){
        // 判断图片链接是否为https，如果不是则替换为https
        if (strpos($imageUrl, 'https://') === 0) {
            return $imageUrl;
        } elseif (strpos($imageUrl, 'http://') === 0) {
            return 'https://' . substr($imageUrl, 7);
        } else {
            return $imageUrl;
        }
    }

    private function getUserIdBySubUnionId($subUnionId) {
        $userId = User::where('sub_union_id', $subUnionId)->value('id');
        return $userId !== null ? $userId : null;
    }

    function callJdApi($method, $bizParams, $accessToken = '') {
   
        $config = [
            'appKey' => $this->appkey,
            'appSecret' => $this->appSecret,
            'accessToken' => $accessToken,
            'serverUrl' => 'https://api.jd.com/routerjson',
            'method' => $method,
            'format' => 'json',
            'v' => '1.0',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    
        $client = new JdUnionClient($config);
        $response = $client->execute($bizParams);
        return $response;

    }



}