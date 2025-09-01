<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ApiCommand;
use App\Services\AyqySignService;
use Illuminate\Support\Facades\Auth;

class OrderController extends ApiCommand
{
    protected $error_msg = '';


    public function list(Request $request)
    {
        // 订单列表，分页查询
        $data = $request->all();
        
        // 获取分页参数
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);
        
        // 构建查询
        $query = \App\Models\AyqyOrders::with(['product:id,name,imgUrl'])
            ->where('user_id', $this->user_id);
        
        // 订单号查询条件
        if ($request->has('order_number') && !empty($request->input('order_number'))) {
            $query->where('order_number', 'like', '%' . $request->input('order_number') . '%');
        }
        
        // 订单状态查询条件
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }
        
        // 订单时间范围查询条件
        if ($request->has('start_date') && !empty($request->input('start_date'))) {
            $query->where('created_at', '>=', $request->input('start_date') . ' 00:00:00');
        }
        
        if ($request->has('end_date') && !empty($request->input('end_date'))) {
            $query->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }
        
        // 按创建时间倒序排列
        $query->orderBy('created_at', 'desc');
        
        // 执行分页查询
        $orders = $query->paginate($perPage, [
            'id',
            'order_number',
            'product_id',
            'item_price',
            'buynumber',
            'real_amount',
            'account',
            'status',
            'msg',
            'created_at',
            'updated_at'
        ], 'page', $page);
        
        // 格式化返回数据
        $formattedOrders = $orders->items();
        $formattedOrders = collect($formattedOrders)->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'product_name' =>  $order->product->name ,
                'product_img' => $order->product ? $order->product->imgUrl : '',
                'buynumber' => $order->buynumber,
                'real_amount' => $order->real_amount,
                'item_price' => $order->item_price,
                'account' => $order->account,
                'status' => $order->status,
                'status_text' => $this->getStatusText($order->status),
                'msg' => $order->msg,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];
        });
        
        return response()->json([
            'status' => 'success',
            'msg' => '获取订单列表成功',
            'data' => [
                'orders' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ]
            ]
        ]);
    }
    
    /**
     * 获取订单状态文本
     */
    private function getStatusText($status)
    {
        $statusMap = [
            0 => '待支付',
            1 => '成功',
            2 => '失败',
            3 => '取消'
        ];
        
        return $statusMap[$status] ?? '未知状态';
    }


    public function payOrder(Request $request)
    {
        $data = $request->all();
        // try {
            $validator = \Illuminate\Support\Facades\Validator::make($data, [
                'productId' => 'required|integer',
                'buynumber' => 'required|integer',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            if ($data['accountRequired'] == 1 && empty($data['account'])) {
                return response()->json(['msg' => '请输入账号']);
            }
            $productInfo = \App\Models\ProductDetail::where('id',$data['productId'])->first();  //价格要以productDetail表为准
            if (empty($productInfo)) {
                return response()->json(['msg' => '商品不存在']);
            }
            // 一般情况下，数据表内的price比money要小
            $salePrice = $productInfo['money']; //这个要给注册人看,用这个结算salePrice表示销价
            $price = $productInfo['price']; //这个不能给注册人看
            //下单前，要比较余额是否足够
            $user = \App\Models\User::where('id',$this->user_id)->first();
            if ($user->balance < $data['buynumber'] * $salePrice) {
                return response()->json(['msg' => '余额不足']);
            }
            
            $orders = new \App\Models\AyqyOrders();        
            $orders->order_number = date('YmdHis') . rand(10000, 99999);  //创建一个订单号;
            $orders->user_id = $this->user_id;
            $orders->product_id = $productInfo['id'];
            $orders->item_price = $salePrice;
            $orders->buynumber = $data['buynumber'];
            $orders->account = $data['account'];
            $orders->should_amount = round($salePrice * $data['buynumber'],2); //应付金额，这个要给会员看到,并且取小数后2位
            $orders->real_amount = round($data['buynumber'] * $price,2); //实付金额，这个不能给会员看到,并且取小数后2位
            $orders->status = 0;  //0表示待支付
            $orders->save();
            $ret = $this->__payOrder($orders);
            if ($ret) {
                return response()->json(['status' => 'success','msg' => '创建订单成功','order_number' => $orders->order_number]);
            } else {
                return response()->json(['status' => 'error', 'msg' => '创建订单失败，请稍后重试'], 500);
            }
           
        // } catch (\Exception $e) {
        //     \Illuminate\Support\Facades\Log::error('订单创建失败: ' . $e->getMessage());
        //     return response()->json(['status' => 'error', 'msg' => '订单创建失败，请稍后重试'], 500);
        // }

    }


    private function __payOrder($order)
    {
      
        $product_id = $order['product_id'];            
        if ($order->status != 0) {
            $this->error_msg = '订单状态错误';
            return false;
        }
        //下单前，要比较余额是否足够
        $user = \App\Models\User::where('id',$this->user_id)->first();
        if ($user->balance <  $order['should_amount']) {
            $this->error_msg = '余额不足'; 
        }
        Log::info('create order data >>>' . json_encode($order->toArray()));
 
        $productInfo = \App\Models\Products::where('id',$product_id)->first();   
        $orderRet = $this->PostOrderAyqy($product_id, $order->buynumber, $order->account,$order->order_number); 
        Log::info('PostOrderAyqy productInfo >>>' . json_encode($productInfo));  
        Log::info('PostOrderAyqy orderRet >>>' . json_encode($orderRet)); 
        if ($orderRet && $productInfo) {
            $this->error_msg = '';
            //保存订单                 
            $order->status = 1;
            $order->save();
            $this->saveUserBalanceLog($this->user_id, $order->id, $productInfo['price'], $user->balance, $user->balance - $order['should_amount'], $order->order_number, '购买商品');
            // 更新用户余额
            $user->balance = $user->balance - $order['should_amount'];
            $user->save();
            return true;
        } else {  
            $order->status = 0; //1表示成功
            $order->msg = $this->error_msg;
            $order->save();       
            return false;
        }
        
    }



    protected function saveUserBalanceLog($user_id,$order_id,$amount,$balance_before,$balance_after,$transaction_no,$remark){
        $log = new \App\Models\UserBalaceLog();
        $log->user_id = $user_id;
        $log->order_id = $order_id;
        $log->amount = $amount;
        $log->balance_before = $balance_before;
        $log->balance_after = $balance_after;
        $log->transaction_type = 1; //1表示购买商品
        $log->transaction_no = $transaction_no;
        $log->remark = $remark;
        $log->created_at = date('Y-m-d H:i:s');
        $log->save();
    }

        
    public function getOrderInfo(Request $request,$order_number)
    {
  
        if (empty($order_number)) {
            return response()->json(['msg' => '订单号不能为空']);
        }
         
        $order = \App\Models\AyqyOrders::where('order_number',$order_number)->first();
        if (empty($order)) {
            return response()->json(['msg' => '订单不存在']);
        }
        $productInfo = \App\Models\Products::where('id',$order->product_id)->select('id','name','imgUrl')->first();
        if (empty($productInfo)) {
            return response()->json(['msg' => '商品不存在']);
        }
        $uesrbalance = \App\Models\User::where('id',$order->user_id)->value('balance');
        $o = array_merge($order->toArray(),$productInfo->toArray(),['userbalance' => $uesrbalance]);
        return response()->json(['status' => 'success','msg' => '获取订单信息成功','order' => $o]);
    }

    // 向上游戏创建订单Ayqy
    public function PostOrderAyqy($productId, $buynumber, $account,$order_number)
    {
        try {
            $domain = env('AYQY_KAMI99_DOMAIN');
            $url = $domain . "/api/v2/addOrder";
     
            // 签名，拼接参数为：id+count+payType，签名规则参考根目录下接口说明文档
            $signService = new AyqySignService();
            $sign = $signService->generateSign([
                "id" => $productId,
                "count" => $buynumber,
                "payType" => 0,
            ]);

            $response = \Illuminate\Support\Facades\Http::asForm()
                ->withOptions([
                    'verify' => false,
                ])
                ->post($url, [
                    'userNo' => env('AYQY_KAMI99_USERNO'),
                    'sign' => $sign,
                    'id' => $productId,
                    'count' => $buynumber,
                    'payType' => 0, //0表示余额支付
                    'account' => $account,
                    'outerNumber' => $order_number
                ]);

            $data = $response->json();        
            Log::info('PostOrderAyqy create order data >>>' . json_encode($data)); 
            if ($data['code'] == 1000) {
                return $data; //下单成功，返回订单号
            } else {
                Log::info('PostOrderAyqy create order data >>> error::' . json_encode($data)); 
                $this->error_msg = $data['msg'];
                return null;
            }
        } catch (\Exception $e) {
            Log::error('上游下单失败: ' . $e->getMessage());
            $this->error_msg = '上游下单失败，请稍后重试';
            return null;
        }
    }

    public function cancelOrder(Request $request,$order_number)
    {
        $order = \App\Models\AyqyOrders::where('order_number',$order_number)->first();
        if (empty($order)) {
            return response()->json(['msg' => '订单不存在']);
        }
        $order->status = 3; //取消订单
        $order->save();
        return response()->json(['msg' => '取消订单成功']);
    }


}
