<?php
namespace App\Http\Controllers\Api;

 
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\ApiCommand;
 
use App\Models\RechargeRecord;
use App\Services\AyqySignService;
use App\Services\PaymentOrderService; 
use App\Services\DynamicPropertyService;
use App\Models\User;
use App\Services\FeixpayRsaService;
/*
输出一个充值的逻辑和注意事项
1.充值的逻辑
*/
class RechargeRecordController extends ApiCommand
{
    // 列表输出
    public function index(Request $request)
    {
         
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);        
        $list = RechargeRecord::where('user_id',$this->user_id)->orderBy('created_at','desc');
        // $list = RechargeRecord::query()->orderBy('created_at','desc');
        //搜索条件，订单号，订单状态，订单时间
        if ($request->has('order_number') && !empty($request->input('order_number'))) {
            $list->where('order_number', 'like', '%' . $request->input('order_number') . '%');
        }
        if ($request->has('status') && !empty($request->input('status')) && $request->input('status') != '') {
            
            $list->where('status', $request->input('status'));
        }
        if ($request->has('start_date') && !empty($request->input('start_date'))) {
            $list->where('created_at', '>=', $request->input('start_date') . ' 00:00:00');
        }
        if ($request->has('end_date') && !empty($request->input('end_date'))) {
            $list->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }
        
        $paginator = $list->paginate($perPage, ['*'], 'page', $page);
 
        $list = $paginator->items();
        $list = array_map(function($item){
            $item['status_text'] = RechargeRecord::$statusMap[$item['status']];
            $item['payment_method_text'] = RechargeRecord::$paymentMethodMap[$item['payment_method']];
            return $item;
        }, $list);
        // 分离数据和分页信息
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem()
        ];
        
        $recharge_fees =  \App\Models\GlobalConfig::where("key","recharge_fees")->value('value');
        $payment_method = \App\Models\GlobalConfig::where("key","payment_method")->value('value');
        if (is_string($payment_method)) {
            $payment_method = str_replace(array('"','[',']'), '', $payment_method);
            $payment_method = explode(',',$payment_method);
        }
        return response()->json([
            'status' => 'success',
            'msg' => '获取充值记录成功',
            'list' => $paginator->items(),
            'recharge_fees' => $recharge_fees ?? 0,
            'payment_method' => $payment_method,
            'pagination' => $pagination
        ]);

    }

    // private function QueryOrderStatus() {
    //     //控制15秒才能执行一次
    //     $last_time = Cache::get('QueryOrderStatus_last_time');
    //     if($last_time && time() - $last_time < 15){
    //         return;
    //     }
    //     Cache::put('QueryOrderStatus_last_time',time());
    //     $feixpayAlipys = DynamicPropertyService::getAllByTypeTag('feixpay-alipy');
       
    //     //状态:0-待支付,1-支付成功,2-支付失败
    //     $paymentService = new PaymentOrderService();
    //     try {
    //         // $list = RechargeRecord::where('status',0)
    //         // ->where('updated_at', '>=', date('Y-m-d 00:00:00'))
    //         // ->where('updated_at', '<', date('Y-m-d H:i:s'))
    //         // ->select("order_no")->orderBy('created_at','desc')->get()->toArray();
    //         $list = RechargeRecord::where('status',0)   
    //         ->where()         
    //         ->select("order_no")->orderBy('created_at','desc')
    //         ->get()->toArray();
    
    //         //状态:0-待支付,1-支付成功,2-支付失败
    //         foreach($list as $item){            
    //             $orderRetState = $paymentService->FeixpayQueryOrder($item['order_no']);   
 
    //             $rechargeRecord = RechargeRecord::where('order_no',$item['order_no'])->first();               
    //             if($orderRetState == 3){
    //                 //3表示支付成功                    
    //                 $rechargeRecord->status = 1;
    //                 $rechargeRecord->payment_time = date('Y-m-d H:i:s');
    //                 $rechargeRecord->updated_at = date('Y-m-d H:i:s');
    //                 $rechargeRecord->save();
    //                 $this->addBalance_log($rechargeRecord);
    //             }else if (in_array($orderRetState,[0,4,5])){                    
    //                 //其他状态，不做处理
    //                 $rechargeRecord->status = 2;
    //                 $rechargeRecord->payment_time = date('Y-m-d H:i:s');
    //                 $rechargeRecord->updated_at = date('Y-m-d H:i:s');
    //                 $rechargeRecord->save();
    //             } 
    //         }
    //     } catch (\Exception $e) {
    //         save_log('查询订单状态失败 QueryOrderStatus::','RechargeRecordController');
    //         save_log($e->getMessage(),'RechargeRecordController');
    //         // dump("125 >>>",$e->getMessage());
    //     }
    // }

    /**
     * 充值记录详情
     */
    public function show($id)
    {
        $record = RechargeRecord::findOrFail($id);
        
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $record
        ]);
    }

    //新增
    public function createpay(Request $request)
    {        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            // 'order_number' => 'required|string|max:255', 
            'amount' => 'required|numeric|min:0.50',
            'way_type' => 'required|in:wechat,alipay',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error','msg' => $validator->errors()->first()]);
        }

        $max_id = RechargeRecord::withTrashed()->max('id');
        if (empty($max_id) || $max_id < 10000) {
            $current_id = 10000;
        } else {
            $current_id = $max_id + 1;
        }
        $order_number = 'RECH' . date('YmdHis') . $current_id; //Re表示充值
        $data = $request->all();
        $payment_method = 0;
        $feixapyWechats = DynamicPropertyService::getAllByTypeTag('feixpay-wechat');
        $feixpayAlipys = DynamicPropertyService::getAllByTypeTag('feixpay-alipy');
        if ($data['way_type'] == 'wechat') {
            $payment_method = 1;
            $payment_app_ids = ['app_id'=>$feixapyWechats['app_id'],'ins_id'=>$feixapyWechats['ins_id'],'md5_key'=>$feixapyWechats['md5_key']];
        }
        if ($data['way_type'] == 'alipay') {
            $payment_method = 2;
            $payment_app_ids = ['app_id'=>$feixpayAlipys['app_id'],'ins_id'=>$feixpayAlipys['ins_id'],'md5_key'=>$feixpayAlipys['md5_key']];
        }
    

        $recharge_fees =  \App\Models\GlobalConfig::where("key","recharge_fees")->value('value') -0;
           
        try {
            $data = $request->all();
            $rechargeRecord = new RechargeRecord();
            $rechargeRecord->id = $current_id;
            $rechargeRecord->user_id = $this->user_id;
            $rechargeRecord->order_no = $order_number;  
            $rechargeRecord->amount = round($data['amount'], 2, PHP_ROUND_HALF_DOWN);
            $rechargeRecord->handling_fee = $recharge_fees>0 ? $recharge_fees / 100 : 0;   //手续费
            $total_amount = round($data['amount'] * (1 + $recharge_fees / 100), 2, PHP_ROUND_HALF_DOWN);
            $rechargeRecord->total_amount  = $total_amount;
            $rechargeRecord->payment_method = $payment_method;
            $rechargeRecord->payment_app_ids = json_encode($payment_app_ids);
            $rechargeRecord->status = 0; // 待支付
            $rechargeRecord->save();
            $paymentService = new PaymentOrderService();
            
            // way_type=['wechat','alipay' ]
            $payRet = $paymentService->createPayment( $data['way_type'],$order_number,$total_amount,'充值','充值费用含手续费');
            if(!empty($payRet['pay_order_id'])){
                $rechargeRecord->third_party_order_no = $payRet['pay_order_id'];
                $rechargeRecord->save();
            }
            return response()->json(['status' => 'success','msg' => '创建充值记录成功','payinfo' => $payRet]);
        }catch(\Exception $e){
            //支付失败，要做errormsg保存
            $rechargeRecord = RechargeRecord::where('order_no',$order_number)->first();
            if($rechargeRecord){
                $rechargeRecord->status = 2;
                $rechargeRecord->errormsg = $e->getMessage();
                $rechargeRecord->save();
            }
            $msg = $e->getMessage();
            $msg .= ' 文件：'.$e->getFile().' 行号：'.$e->getLine();
            return response()->json(['status' => 'error','msg' => '创建充值记录失败','detailmsg' => $msg]);
        }

        /* 返回
{
    "status": "success",
    "msg": "创建充值记录成功",
    "payinfo": {
        "pay_info": "https://api.feixpay.cn/Call/Dinpay/wxRawPub?pay_order_id=202507261916328586",
        "pay_order_id": "202507261916328586"
    }
}
        */
    }

    //删除
    public function destroy($id)
    {
        try {
            $rechargeRecord = RechargeRecord::findOrFail($id);
            $rechargeRecord->delete();
            return response()->json(['status' => 'success','msg' => '删除充值记录成功']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error','msg' => '删除充值记录失败']);
        }
    }

    // //更新用户余额日志
    private function addBalance_log($rechargeRecordInfo)
    {       
        $userInfo = User::where('id', $rechargeRecordInfo->user_id)->first();
        if ($userInfo) {
            $balance_before = $userInfo->balance;
            $balance_after = $balance_before + $rechargeRecordInfo->amount;
        }
        $user_balance_log = new \App\Models\UserBalaceLog();
        $user_balance_log->user_id = $rechargeRecordInfo->user_id;
        $user_balance_log->amount = $rechargeRecordInfo->amount;
        $user_balance_log->transaction_type = 1; //1表示充值
        $user_balance_log->balance_before = $balance_before;
        $user_balance_log->balance_after = $balance_after;
        $user_balance_log->transaction_no = $rechargeRecordInfo->third_party_order_no;
        $user_balance_log->remark = '充值' . $rechargeRecordInfo->amount;
        $user_balance_log->created_at = date('Y-m-d H:i:s');
        $user_balance_log->save();
        //更新用户余额
        $userInfo->balance = $balance_after;
        $userInfo->save();
    }

}