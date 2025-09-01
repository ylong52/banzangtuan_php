<?php
namespace App\Http\Controllers\Api;

 
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ApiCommand;
 
use App\Models\RechargeRecord;
use App\Services\PaymentService; 
use App\Models\User;
 
class PaymentController extends ApiCommand
{
    public function callback(Request $request)
    {
        // $data = $request->all();
        // save_log($data,"payment_callback");

        // //如果订单号的前面2个字是're'
        // //$order_number = 'RECH' . date('YmdHis') . $current_id; //RECH表示充值
        // Log::info('充值回调 >>>>', $data);
        // // save_log('充值回调 >>>>',"payment_callback");
        // // save_log($data,"payment_callback");
        // // dd($data);
        // $rechargeRecordInfo = RechargeRecord::where('order_no', $data['mch_order_no'])
        // ->where('total_amount',$data['amount'])
        // ->where('status',0)
        // ->first();
   
        // if (!empty($rechargeRecordInfo) && $rechargeRecordInfo->id>0) {               
        //     $rechargeRecordInfo->third_party_order_no = $data['ins_order_sn'];
        //     $rechargeRecordInfo->payment_time = date('Y-m-d H:i:s');
        //     $rechargeRecordInfo->updated_at =  date('Y-m-d H:i:s');
        //     $rechargeRecordInfo->status  = 1; 
        //     $rechargeRecordInfo->save();
        //     // $this->addBalance_log($rechargeRecordInfo);
        //     save_log('充值成功 >>>>',"payment_callback");
        //     return response()->json(['status' => 'success','msg' => '充值成功']);
        // } else {
        //     save_log('充值失败 >>>>',"payment_callback");
        //     return response()->json(['status' => 'error','msg' => '充值失败']);
        // }
        
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


    public function transfercallback(Request $request)
    {
        $data = $request->all();
        Log::info('转账回调 >>>>', $data);
        save_log('转账回调 >>>>',"transfercallback");
        save_log($data,"transfercallback");
    }



}
