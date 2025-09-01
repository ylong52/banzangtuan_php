<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use App\Services\FeixpayRsaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use App\Services\AlipayTransferService;
use App\Models\UserWithdrawal;
use App\Services\WechatTransferService;

/*
微信
支付宝
提现管理 
*/
class PaymentTransferService
{
        protected $AlipayAccount = [];
        protected $error = null;
        protected $request = null;

        public function __construct(){    
         
        }

        /*
        * 支付宝转账-提现
        */
        public function AlipayCreateTransfer($payInfo) {      
               
            $alipayService = new AlipayTransferService();            
            $result = $alipayService->transferToAccount(
                $payInfo['account'],  // 收款账户
                $payInfo['amount'],        // 转账金额
                $payInfo['real_name'],     // 收款人姓名
                '提现',
                $payInfo['withdrawal_no'],               
            );
    
            if (isset($result['success']) && $result['success']) {           
                //withdrawal_status 提现状态:0-待处理,1-已到账,2-失败,3-未确认收款(微信)      
                UserWithdrawal::where('withdrawal_no',$payInfo['withdrawal_no'])->update(['withdrawal_status' => 1,   
                'withdrawal_time' => date("Y-m-d H:i:s"),
                'third_party_order_no' => $result['data']['out_biz_no'] 
                ]);     
                //不管成功是否，先扣除用户的余额
                $user = \App\Models\User::find($payInfo['user_id']);                
                $userBalanceLog = new \App\Models\UserBalaceLog();
                $userBalanceLog->user_id = $payInfo['user_id'];
                $userBalanceLog->order_id = $payInfo['order_id'];
                $userBalanceLog->transaction_type = 3;
                $userBalanceLog->amount = $payInfo['amount'];
                $userBalanceLog->balance_before = $user->balance;
                $userBalanceLog->balance_after = $user->balance;
                $userBalanceLog->transaction_no = $result['data']['out_biz_no'];
                $userBalanceLog->remark = '支付宝提现成功';
                $userBalanceLog->created_at = date('Y-m-d H:i:s');
                $userBalanceLog->save();
            }else {
                save_log("支付宝转账失败","AlipayCreateTransfer");
                // $msg = $result['message'] . "\n" . $result['file'] . "\n" . $result['line'] . "\n" . $result['trace'] . "\n" . $result['trace_as_string'];
                // save_log($msg,'AlipayCreateTransfer'); 
                save_log($result,"AlipayCreateTransfer");
                //dd($result);
                throw new \Exception($result['message']??'支付宝提现失败');
            }
        }

 
        /*
        * 微信开通企业转账个人
        */
        public function WechatCreateTransfer($payInfo) {
            $openid = $payInfo['wx_openid']; // 测试用户openid
            $amount = intval($payInfo['amount'] * 100); // 转账金额,分，取整数
            $outBillNo = $payInfo['withdrawal_no'];
            
      
            $transferService = new WechatTransferService();
            $transferParams = [
                'out_bill_no' => $outBillNo,                 
                'openid' => $openid,
                'transfer_amount' => $amount               
            ];
                
            save_log("微信转账参数: ","WechatCreateTransfer");
            save_log($transferParams,"WechatCreateTransfer");
            $result = $transferService->transfer($transferParams);
            
            if ($result && $result['success']) {
                $wx_package_info = $result['data']['package_info'];
                 
                save_log("转帐参数: ","WechatCreateTransfer");
                save_log($result,"WechatCreateTransfer");
                // 查询转账状态
                // echo "查询转账状态...\n";
                $statusResult = $transferService->queryTransferStatus($outBillNo);
              
                if ($statusResult['state']=='WAIT_USER_CONFIRM') {                     
                    UserWithdrawal::where('withdrawal_no',$payInfo['withdrawal_no'])->update([
                        'withdrawal_status' => 3, //转账成功，待用户确认，才能进账
                        'withdrawal_time' => date("Y-m-d H:i:s"),
                        'third_party_order_no' => $statusResult['transfer_bill_no'],
                        'wx_package_info' => $wx_package_info
                    ]);                      
                }
                
            } else {
                // echo "❌ 转账失败: " . $result['error'] . "\n";
                // echo "商户订单号: " . $result['out_bill_no'] . "\n";
                save_log("❌ 转账失败: ","WechatCreateTransfer");
                save_log($result['out_bill_no'],"WechatCreateTransfer");
                throw new \Exception("转账失败");
            }  

        }

        

        

    }