<?php

namespace App\Http\Controllers\Api\h5;
use Illuminate\Http\Request;
use App\Services\JdUnionClient;
use App\Models\User;
use App\Models\Orders;
use App\Models\Goods;
use App\Services\JdGoodsSevice;


class JdGoodsController extends H5BaseController
{
    public $appkey, $appSecret;

    public function __construct()
    {        
 
        parent::__construct();
        $this->appkey = "e5f035c22a6ca67a748154f781bb6c20";
        $this->appSecret = "e0d9c178fbf2444b9bb8dbf9f09e8365";
        if ($this->user_status === 0) {
            //0表示禁用，1表示有效
            return response()->json(['status' => 'error','msg' => '用户已经禁用!']);
        }
        if (empty($this->user_id)) {
            return response()->json(['status' => 'error','msg' => '用户未登录!'],400);
        }
        
    }

 
    //转链
    //必须下单后，才能查到订单
    //转链分多种情况，以下情况要考虑是多个商品和链接的情况
    //1，含有券和下单，两种情况都有，或者只有一种
    //2, 只有“下单”，或只有“券”
    //3, 即没有“券”和也没有“下单”
    //4, 链接内是非官方链接
    public function bysubunionid(Request $request)    
    {
       
        // $request->item_id = "
        // 【京东】https://3.cn/2porp-78?jkl=@OFzLBAZcuinE@ CA8680 「海尔520升594超薄零嵌全空间冰箱」
        // 点击链接直接打开 或者复制文案打开京东
        // ";

        if (empty($request->item_id)) {
            return response()->json(['status' => 'error','msg' => 'item_id参数错误!']);
        }        
        $raw_item_url_txt = $request->item_id;
        try {
     
            $pattern1 = '/https?:\/\/[a-zA-Z0-9\.\/\:\-\_\?\=\&\%]+/u';
            
            if (preg_match($pattern1, $raw_item_url_txt) ) {
                //================ 第一种情况完成 ==================          
                // 同时包含两个关键词时，执行这里的逻辑                
                preg_match_all($pattern1, $raw_item_url_txt, $matches1);
   
                $good_urls = [];
                if (!empty($matches1[0])) {
                    $good_urls = array_merge($good_urls, $matches1[0]);
                }
                
                $bysubunionidRet = [];
                foreach($good_urls as $goods_url) {
                    //转链接
                    $bysubunionidRet[] = $this->__bysubunionid2($goods_url);            
                }
                
                //将旧链接换成新链接
                $new_item_txt = $raw_item_url_txt;
                // 通过rawURL去重
                $uniqueBysubunionidRet = [];
                $uniqueRawURLs = [];

                foreach($bysubunionidRet as $item) {
                    if (!in_array($item['rawURL'], $uniqueRawURLs)) {
                        $uniqueRawURLs[] = $item['rawURL'];
                        $uniqueBysubunionidRet[] = $item;
                    }
                }
                $bysubunionidRet2 = $uniqueBysubunionidRet;
                foreach($bysubunionidRet2 as $k1=>$r1) {
                    $new_item_txt = str_replace(                    
                        $r1['rawURL'],
                        $r1['shortURL'],
                        $new_item_txt
                    );
                }
  
                $displayStr = $new_item_txt;
                // 去除“从”到第一个空格之间的内容，包括“从”本身
                $displayStr = preg_replace('/\?[^\s]*\s?/u', '', $displayStr);
                // https://u.jd.com/SGUvkBL?hjk=@iMF5681@MF5681 【小天】
                $copy_txt =  preg_replace('/<span\s+style="color:red;">(https?:\/\/[^<]+)<\/span>/i', '$1', $displayStr);

                if (count($bysubunionidRet) > 1 ) {
                    //多个订单
                    $e = 0;$s=0;
                    // $displayMsg = [];
                    foreach($bysubunionidRet as $r1) {
                        if ($r1['errorTag']==false) {
                            $s ++;
               
                        } 
                        if ($r1['errorTag']==true) {
                            $e ++;
                         
                        }
                    }
                    
                    return response()->json(['status' => 'success','msg' => 'success','copy_txt'=>$copy_txt,'displayMsg'=>$displayStr,
                    'commissionShare'=>"",'shortURL'=>'','successmsg'=>"转换链接成功{$s}条，失败{$e}条","bysubunionidRet"=>$bysubunionidRet]);              
                } elseif(count($bysubunionidRet)==1) {
                    $subUnionRet = $bysubunionidRet[0];    
                    $msg = $subUnionRet['errorTag']?'转链失败':'转链成功';
 
                    return response()->json(['status' => 'success','msg' => $msg,'copy_txt'=>$copy_txt, 'displayMsg'=>$displayStr,'commissionShare'=>$subUnionRet['commissionShare'],'shortURL'=>$subUnionRet['shortURL'],"successmsg"=>"","bysubunionidRet"=>$bysubunionidRet]);
                } else {                  
                    $commissionShare = "";
                    $shortURL = "";
                    foreach($bysubunionidRet  as $r1) {
                        if (!empty($r1['commissionShare'])) {
                            $commissionShare = $r1['commissionShare'];
                        }
                        if ($r1['errorTag']==false && $r1['isOrderTag']==true) {
                            $shortURL = $r1['viewDetailUrl'];
                        }
                    }
                    return response()->json(['status' => 'success','msg' => 'success','copy_txt'=>$copy_txt,'displayMsg'=>$displayStr,'commissionShare'=>$commissionShare,'shortURL'=>$shortURL,"successmsg"=>"","bysubunionidRet"=>$bysubunionidRet]);
                }


                // dump("191>>>",$bysubunionidRet,$new_item_txt);
                //================ 第一种情况完成 ==================            
            } else  {
                 //================ 第二种情况完成 ==================                    
                throw new \Exception("未找到链接!");
            }
    
       
        }catch(\Exception $e) {
            return response()->json(['status' => 'error','msg' => $e->getMessage()]);
        }
    

    }


     //转链
    //必须下单后，才能查到订单
    private function __bysubunionid2($goods_url)    
    { 
        try {
            $goods_url = trim($goods_url);
            //判断是否为合法的url链接，用正则式来判断
            $pattern = '/(https?:\/\/[^\s]+)/u';
            
            if (!preg_match($pattern, $goods_url)) {
                throw new \Exception("转链取得的URL错误,无法分析!");
            }
                   
            preg_match($pattern, $goods_url, $matches);
            $task_goods_url = $matches[1];
           
            $subUnionId = User::where('id',$this->user_id)->value('sub_union_id');
            if (!$subUnionId) {
                throw new \Exception("subUnionId参数错误!");
            }
 
        $promotionBizParams = [
            'promotionCodeReq' => [
                'materialId' => $task_goods_url, // 替换为实际的物料ID
                'subUnionId' => $subUnionId,
                'sceneld' => 1
            ]
        ];
       
            $method =  "jd.union.open.promotion.bysubunionid.get";

            save_log(['promotionBizParams'=>$promotionBizParams,'method'=>$method],'bysubunionid');
            $response = $this->callJdApi($method, $promotionBizParams);
            
    // dump("273>>>",$promotionBizParams,$method,$response);   
    // die;
            if (empty($response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'])) {
                throw new \Exception("操作失败!");
            }
            $getResultStr = $response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
            $getResult = json_decode($getResultStr,true);

            if ($getResult['code']=="2001925") {
                 // ----------------- 调用礼金链接来处理
                $giftRet = $this->giftlink($task_goods_url,$subUnionId);
                if (empty($giftRet)) {
                    throw new \Exception("操作失败!"); 
                }
                $getResult['data']['shortURL'] = $giftRet;
            }elseif ($getResult['code']!=200) {
                throw new \Exception($getResult['message']);
            }  
            if (empty($getResult['data']['shortURL'])){
                throw new \Exception("转链失败!");
            } 
            $isOrderTag = false;
            $commissionShare = '';
             
                // dump("247>>>",$item_url_txt, $goods_url);
            $goodsInfo = $this->__goodsQuery($getResult['data']['shortURL']);  
// dump("298>>>",$getResult['data']['shortURL'],$goodsInfo);
            if (!empty($goodsInfo['errorMsg'])) {
                $commissionShare = "";
                $isOrderTag = false;
                
            }elseif (!empty($goodsInfo['commissionInfo'])) {
                $commissionShare = "佣金".$goodsInfo['commissionInfo']['commissionShare']."%";
                $isOrderTag = true;
            }
            //goods_url
            $retShortURL = preg_replace('/(https?:\/\/[^\s]+)/u', $getResult['data']['shortURL'], $goods_url);
 
            return ['errorTag'=>false,'rawURL'=>$goods_url,'shortURL'=>$retShortURL,'commissionShare'=>$commissionShare,'viewDetailUrl'=>$getResult['data']['shortURL'],'isOrderTag'=>$isOrderTag];
        } catch(\Exception $e) {
            //打印具体错误，行号及错误信息
            // $errorMsg = $e->getMessage()." in ".$e->getFile()." on line ".$e->getLine();
            // dump($errorMsg);
            $retShortURL = preg_replace('/(https?:\/\/[^\s]+)/u', '<span style="color:red;">$1</span>', $goods_url);
            
            return ['errorTag'=>true,'rawURL'=>$goods_url,'shortURL'=>$retShortURL,'commissionShare'=>'','viewDetailUrl'=>'','isOrderTag'=>false,'errorMsg'=>$e->getMessage()];
        }
                        
    }

    //礼金链接
    private function giftlink($task_goods_url,$subUnionId) {
        $goodsQueryresult = $this->__goodsQuery($task_goods_url);
        // if (!empty($goodsQueryresult)) {
        //     return null;
        // }
        // dump("324??",$goodsQueryresult);
        $conversionlink_rawurl = $goodsQueryresult['itemId'];
        
        $promotionBizParams = [
            'promotionCodeReq' => [
                'materialId' => $conversionlink_rawurl, // 替换为实际的物料ID
                'subUnionId' => $subUnionId,
                'sceneld' => 1
            ]
        ];

        $method =  "jd.union.open.promotion.bysubunionid.get";
        $response = $this->callJdApi($method, $promotionBizParams);        
        if (empty($response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'])) {
            throw new \Exception("操作失败!");
        }
        $getResultStr = $response['parsed']['jd_union_open_promotion_bysubunionid_get_responce']['getResult'];
        $getResult = json_decode($getResultStr,true);  
        //判断getResult
        if ($getResult['code']!=200) {
           // throw new \Exception($getResult['message']);
            return null;
        }
        if (empty($getResult['data']['shortURL'])) {
           // throw new \Exception("转链失败!");
            return null;
        }           
        return $getResult['data']['shortURL'];
    }

 

    
    function goodsQuery(Request $request)  {
        if (empty(($keyword = $request->keyword))) {
            return response()->json(['status' => 'error','msg' => "keyword参数不能为空"]);
        }
        preg_match('/(https?:\/\/[\w.-]+\.[\w]+\/[\w-]+(?:「[^」]*」)?)/', $keyword, $matches);
        $url = isset($matches[0]) ? $matches[0] : '';  
        try {
            $goodsInfo = $this->__goodsQuery($url);
            $estimateFee_Rate = 0.9;
            if ($goodsInfo['commissionInfo']['commissionShare'] > 5) {
                $estimateFee_Rate = 0.8;
            }

            
            // 直接处理单个商品对象，不需要循环
            $goodsInfo['commissionInfo']['commissionShare2'] =  $goodsInfo['commissionInfo']['commissionShare'] - 0.1 ; 
            $goodsInfo['commissionInfo']['commission2'] = round($goodsInfo['commissionInfo']['couponCommission'] * $estimateFee_Rate , 2);
 
            return response()->json(['status' => 'success','msg' => 'success','goodsInfo'=>$goodsInfo]);
        } catch(\Exception $e) {
            return response()->json(['status' => 'error','msg' => $e->getMessage()]);
        }
    }

    function __goodsQuery($keyword)  {
 
        $method ="jd.union.open.goods.query";
        $prams = [
            "keyword"=>$keyword,
            "fields"=>"purchasePriceInfo,documentInfo",
            'sceneId'=>1,
        ];
        // dump("313>>>",$method, $prams);
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
//  dump("404 >>>>",$result,$queryResult);
            
            if ($queryResult['code']!=200) {
                throw new \Exception($queryResult['message'] || "操作失败!");
            }
            // if ($queryResult['totalCount']==0) {
            //     throw new \Exception("没有找到商品!");
            // }
            if (empty($queryResult['data']) || count($queryResult['data'])==0) {
                throw new \Exception("没有找到商品!");
            }
            $goods = $queryResult['data'][0];            
            $goods['keyword'] = $keyword;
            return $goods;
        } catch(\Exception $e) {
// dd("418>>>",$e->getMessage());            
            // throw new \Exception($e->getMessage());
            $errorMsg = $e->getMessage()." in ".$e->getFile()." on line ".$e->getLine();
            // dump("420>>>>",$errorMsg);
            return ['errorMsg'=>$errorMsg];
            // return null;
        }

    }

    //订单查询，已下单未支付的。只能查1小时之内有
    //此处无法使用缓存，订单状态经常在变化。订单24小时未支付会自动取消
//     //https://union.jd.com/openplatform/api/v2?apiName=jd.union.open.order.row.query
//     function orderQuery(Request $request) {
//         $method ="jd.union.open.order.row.query";
//         $orderReq = [
//             "pageIndex"=>1,
//             "pageSize"=>200,
//             "fields"=>"goodsInfo",
//             "type"=>1,  //(1：下单时间，2：完成时间（购买用户确认收货时间），3：更新时间                
//         ];
//         $orderReq['endTime'] = date('Y-m-d H:i:s');
//         $orderReq['startTime'] = date('Y-m-d H:i:s', strtotime($orderReq['endTime']) - 3600);
//         if ($request->type) {
//             $orderReq['type'] = $request->type;
//         }
//         if ($request->pageIndex) {
//             $orderReq['pageIndex'] = $request->pageIndex;
//         }
//         if ($request->pageSize) {
//             $orderReq['pageSize'] = $request->pageSize;
//         }
//         if ($request->startTime) {
//             $orderReq['startTime'] = $request->startTime;
//         }
//         if ($request->endTime) {
//             $orderReq['endTime'] = $request->endTime;
//         }
//         try {
//             $result = $this->callJdApi($method,['orderReq'=>$orderReq]);
//             $queryResult = null;
//             if ($result['http_code']==200 && isset($result['response']) && is_string($result['response'])) {
//                 $result['response'] = json_decode($result['response'],true);
//                 if (isset($result['response']['jd_union_open_order_row_query_responce']['queryResult']) && is_string($result['response']['jd_union_open_order_row_query_responce']['queryResult'])) {
//                     $queryResult = json_decode($result['response']['jd_union_open_order_row_query_responce']['queryResult'],true);
//                 }
//             }
//             if ($queryResult == null) {
//                 throw new \Exception("操作失败!");
//             }
// // dd($queryResult);     
//             $hasMore = false;  //用于翻页还有数据吗？
//             if (count($queryResult['data'])>0) {
//                 foreach ($queryResult['data'] as $order) {
//                     // 从goodsInfo中提取商品相关信息
//                     $goodsInfo = $order['goodsInfo'] ?? [];
                    
//                     // 准备订单数据
//                     $orderData = [
//                         'id' => $order['id'] ?? '',
//                         'sku_name' => $order['skuName'] ?? '',
//                         'order_id' => $order['orderId'] ?? '',
//                         'finish_time' => !empty($order['finishTime']) ? $order['finishTime'] : null,
//                         'order_time' => !empty($order['orderTime']) ? $order['orderTime'] : null,
//                         'modify_time' => !empty($order['modifyTime']) ? $order['modifyTime'] : null,
//                         'sku_id' => $order['skuId'] ?? '',
//                         'valid_code' => $order['validCode'] ?? 0,
//                         'image_url' => $goodsInfo['imageUrl'] ?? '', // 从goodsInfo中获取
//                         'owner' => $goodsInfo['owner'] ?? '', // 从goodsInfo中获取
//                         'shop_name' => $goodsInfo['shopName'] ?? '', // 从goodsInfo中获取
//                         'commission_rate' => $order['commissionRate'] ?? 0,
//                         'sub_side_rate' => $order['subSideRate'] ?? 0,
//                         'subsidy_rate' => $order['subsidyRate'] ?? 0,
//                         'final_rate' => $order['finalRate'] ?? 0,
//                         'estimate_cos_price' => $order['estimateCosPrice'] ?? 0,
//                         'estimate_fee' => $order['estimateFee'] ?? 0,
//                         'actual_cos_price' => $order['actualCosPrice'] ?? 0,
//                         'actual_fee' => $order['actualFee'] ?? 0,
//                         'sub_union_id' => $order['subUnionId'] ?? '',
//                         'user_id' => Auth::id(), // 当前登录用户ID
//                         'sku_num'=> $order['skuNum'] ?? 0,
//                         'price' => $order['price'] ?? 0,
//                         'total_price' => round($order['skuNum'] * $order['price'], 2),
//                         'order_json' => json_encode($order, JSON_UNESCAPED_UNICODE), // 保存完整的原始订单数据 
//                     ];

//                     // 查找现有订单
//                     $existingOrder = Orders::where('id', $order['id'])->first();
                    
//                     if ($existingOrder) {
//                         // 只检查modify_time字段是否有变化
//                         $hasChanges = false;
//                         if ($existingOrder->modify_time != $orderData['modify_time']) {
//                             $hasChanges = true;
//                         }
                        
//                         // 只有当modify_time有变化时才更新
//                         if ($hasChanges) {
//                             $existingOrder->update($orderData);
//                         }
//                     } else {
//                         // 如果是新订单，直接创建
//                         // $orderData['created_at'] = date('Y-m-d H:i:s');
//                         // $orderData['updated_at'] = date('Y-m-d H:i:s');
//                         Orders::create($orderData);
//                     }
//                 }
//             }
//             return response()->json(['status' => 'success','msg' => 'success','queryResult'=>$queryResult['data']??[]]);

//         } catch(\Exception $e) {
//             return response()->json(['status' => 'error','msg' => $e->getMessage()]);
//         }
//     }
    

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