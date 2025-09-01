<?php

namespace App\Http\Controllers\Api;

use App\Models\GlobalConfig;
use App\Models\User;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CliParser\AmbiguousOptionException;
use Illuminate\Support\Facades\Http;  
use Illuminate\Support\Str;
use App\Http\Controllers\ApiController;
use App\Services\JdUnionClient;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class JdApiController extends ApiController
{
    public $appkey, $appSecret;

    public function __construct()
    {        
        $this->appkey = "49307c59823cec6b2f0fb490f0d3c957";
        $this->appSecret = "fe774b73fb63417bad12286ad8f716f7";
        if ($this->user_status === 0) {
            //0表示禁用，1表示有效
            return response()->json(['status' => 'error','msg' => '用户已经禁用!']);
        }
        
    }

    //转链
    //必须下单后，才能查到订单
    public function bysubunionid(Request $request)    
    {
        $item_id = "【京东】https://3.cn/2od-pfdr「爱他美澳洲白金2段6罐 社群领券」点击链接直接打开";
        // $item_id = $request->item_id;
        if (empty($item_id)) {  
            return response()->json(['status' => 'error','msg' => 'item_id参数错误!']);
        }
        // 提取链接
        preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+)/', $item_id, $matches);
        $url = isset($matches[0]) ? $matches[0] : '';        
        $url_template = str_replace($url, '###', $item_id);
        if (empty($url)) {  
            return response()->json(['status' => 'error','msg' => 'item_id参数错误!']);
        }
        // dd("38 >>>",$item_id,$url_template,$url);
        // $subUnionId = config('app.subUnionIdx').$this->user_id;
        $subUnionId = config('app.subUnionIdx') ;
        $promotionBizParams = [
            'promotionCodeReq' => [
                'materialId' => $url, // 替换为实际的物料ID
                'subUnionId' => $subUnionId,
                'sceneld' => 1
            ]
        ];
        try {
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
            $goodsInfo = $this->__goodsQuery($getResult['data']['shortURL']);
            $shortURL = $getResult['data']['shortURL'];               
            $goods = [];        
// dd("74<<",$goodsInfo);             
            $goods['commissionShare'] = "佣金".$goodsInfo['commissionInfo']['commissionShare']."%";
             
            $new_item_id = str_replace('###', $shortURL, $url_template). " 或者复制文案打开京东" ;
            preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+(?:「[^」]*」)?)/', $new_item_id, $matches);
            $copy_txt = isset($matches[1]) ? $matches[1] : '';

            return response()->json(['status' => 'success','msg' => 'success','shortURL'=>$shortURL,'textarea_txt'=>$new_item_id,"copy_txt"=>$copy_txt,'goods'=>$goods]);
 
            
        } catch (\Exception $e) {            
            return response()->json(['status' => 'error','msg' => $e->getMessage()]);
        }

    }

    function goodsQuery(Request $request)  {
        if (empty(($keyword = $request->keyword))) {
            return response()->json(['status' => 'error','msg' => "keyword参数不能为空"]);
        }
        // $keyword = "【京东】https://u.jd.com/YO58R3K「爱他美澳洲白金2段6罐 社群领券」点击链接直接打开";
        preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+(?:「[^」]*」)?)/', $keyword, $matches);
        $url = isset($matches[0]) ? $matches[0] : '';  
        try {
            $goodsInfo = $this->__goodsQuery($url);
            return response()->json(['status' => 'success','msg' => 'success','goodsInfo'=>$goodsInfo]);
        } catch(\Exception $e) {
            return response()->json(['status' => 'error','msg' => $e->getMessage()]);
        }
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
// dd($queryResult);
// die;            
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
    function orderQuery(Request $request) {
        $method ="jd.union.open.order.row.query";
        $orderReq = [
            "pageIndex"=>1,
            "pageSize"=>200,
            "fields"=>"goodsInfo",
            "type"=>1,  //(1：下单时间，2：完成时间（购买用户确认收货时间），3：更新时间                
        ];
        $orderReq['endTime'] = date('Y-m-d H:i:s');
        $orderReq['startTime'] = date('Y-m-d H:i:s', strtotime($orderReq['endTime']) - 3600);
        if ($request->type) {
            $orderReq['type'] = $request->type;
        }
        if ($request->pageIndex) {
            $orderReq['pageIndex'] = $request->pageIndex;
        }
        if ($request->pageSize) {
            $orderReq['pageSize'] = $request->pageSize;
        }
        if ($request->startTime) {
            $orderReq['startTime'] = $request->startTime;
        }
        if ($request->endTime) {
            $orderReq['endTime'] = $request->endTime;
        }
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
                throw new \Exception("操作失败!");
            }
// dd($queryResult);     
            $hasMore = false;  //用于翻页还有数据吗？
            if (count($queryResult['data'])>0) {
                foreach ($queryResult['data'] as $order) {
                    // 从goodsInfo中提取商品相关信息
                    $goodsInfo = $order['goodsInfo'] ?? [];
                    
                    // 准备订单数据
                    $orderData = [
                        'id' => $order['id'] ?? '',
                        'sku_name' => $order['skuName'] ?? '',
                        'order_id' => $order['orderId'] ?? '',
                        'finish_time' => !empty($order['finishTime']) ? $order['finishTime'] : null,
                        'order_time' => !empty($order['orderTime']) ? $order['orderTime'] : null,
                        'modify_time' => !empty($order['modifyTime']) ? $order['modifyTime'] : null,
                        'sku_id' => $order['skuId'] ?? '',
                        'valid_code' => $order['validCode'] ?? 0,
                        'image_url' => $goodsInfo['imageUrl'] ?? '', // 从goodsInfo中获取
                        'owner' => $goodsInfo['owner'] ?? '', // 从goodsInfo中获取
                        'shop_name' => $goodsInfo['shopName'] ?? '', // 从goodsInfo中获取
                        'commission_rate' => $order['commissionRate'] ?? 0,
                        'sub_side_rate' => $order['subSideRate'] ?? 0,
                        'subsidy_rate' => $order['subsidyRate'] ?? 0,
                        'final_rate' => $order['finalRate'] ?? 0,
                        'estimate_cos_price' => $order['estimateCosPrice'] ?? 0,
                        'estimate_fee' => $order['estimateFee'] ?? 0,
                        'actual_cos_price' => $order['actualCosPrice'] ?? 0,
                        'actual_fee' => $order['actualFee'] ?? 0,
                        'sub_union_id' => $order['subUnionId'] ?? '',
                        'user_id' => Auth::id(), // 当前登录用户ID
                        'sku_num'=> $order['skuNum'] ?? 0,
                        'price' => $order['price'] ?? 0,
                        'total_price' => round($order['skuNum'] * $order['price'], 2),
                        'order_json' => json_encode($order, JSON_UNESCAPED_UNICODE), // 保存完整的原始订单数据 
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
                            $existingOrder->update($orderData);
                        }
                    } else {
                        // 如果是新订单，直接创建
                        // $orderData['created_at'] = date('Y-m-d H:i:s');
                        // $orderData['updated_at'] = date('Y-m-d H:i:s');
                        Orders::create($orderData);
                    }
                }
            }
            return response()->json(['status' => 'success','msg' => 'success','queryResult'=>$queryResult['data']??[]]);

        } catch(\Exception $e) {
            return response()->json(['status' => 'error','msg' => $e->getMessage()]);
        }
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
        //  dd("78>>>",$response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']);
        // if (isset($response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'])) {
        //     $getResultStr = $response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
        //     $getResult = json_decode($getResultStr,true);
        //     if ($getResult['code']!=0) {
        //         throw new \Exception($getResult['message']);
        //     } else {
        //         if (empty($getResult['data']['shortURL'])){
        //             throw new \Exception("转链失败!");
        //         } else {
        //             return $getResult['data']['shortURL'];
        //         }
        //     }
        // } else {
        //     throw new \Exception("操作失败!");
        // }
        // try {
        //     if (isset($response['error'])) {
        //         return ['success' => false, 'message' => "调用失败：" . $response['message'], 'data' => null];
        //     } elseif ($response['http_code'] === 200) {
        //         if (isset($response['parsed']['error_response'])) {
        //             return ['success' => false, 'message' => "接口返回错误：" . json_encode($response['parsed']['error_response'], JSON_UNESCAPED_UNICODE), 'data' => $response['parsed']];
        //         } else {
        //             // 成功时返回完整的解析后响应数据
        //             return ['success' => true, 'message' => '接口调用成功', 'data' => $response['parsed']];
        //         }
        //     } else {
        //         return ['success' => false, 'message' => "HTTP 请求失败，状态码：" . $response['http_code'] . "，响应：" . $response['response'], 'data' => null];
        //     }
        // } catch (\Exception $e) {
        //     return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        // }

    }


}