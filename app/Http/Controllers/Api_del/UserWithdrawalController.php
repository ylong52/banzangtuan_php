<?php

namespace App\Http\Controllers\Api;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiCommand;
use App\Models\AccountBinding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserWithdrawal;
use App\Services\PaymentTransferService;
use App\Models\UserBalance;

class UserWithdrawalController extends ApiCommand
{
    public function __construct()
    {
        parent::__construct();
    }

    // 列表输出
    public function index(Request $request)
    {

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);
        // $this->batchUpdateStatus();
        $list = UserWithdrawal::where('user_id', $this->user_id)->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        //搜索条件，订单号，订单状态，订单时间
        if ($request->has('order_number') && !empty($request->input('order_number'))) {
            $list->where('order_number', 'like', '%' . $request->input('order_number') . '%');
        }
        if ($request->has('status') && !empty($request->input('status'))) {
            $list->where('status', $request->input('status'));
        }
        if ($request->has('start_date') && !empty($request->input('start_date'))) {
            $list->where('created_at', '>=', $request->input('start_date') . ' 00:00:00');
        }
        if ($request->has('end_date') && !empty($request->input('end_date'))) {
            $list->where('created_at', '<=', $request->input('end_date') . ' 23:59:59');
        }
        $list->where('deleted_at', null);
        $account_binding = AccountBinding::where('user_id', $this->user_id)->first();
        $account = [];
        if (!empty($account_binding)) {
            if (!empty($account_binding->wx_openid) && !$account_binding->wx_openid) {
                $account['wechat']['wx_openid'] = $account_binding->wx_openid;
            }
            if (!empty($account_binding->alipay_account_number)) {
                $account['alipay'] = [
                    'alipay_account_number'=>$account_binding->alipay_account_number,
                    'alipay_real_name'=>$account_binding->alipay_real_name,
                ]; 
            }
        }
        $userBalanceInfo['balance'] = intval(\App\Models\User::where('id', $this->user_id)->value('balance'));
        $userBalanceInfo['recharge_fees'] = intval(config('global_config.recharge_fees'));
        return response()->json(['status' => 'success', 'msg' => '获取充值记录成功', 'list' => $list ,'accountInfo'=>$account,'userBalanceInfo'=>$userBalanceInfo]);

    }

    // //批量更新提现状态
    // public function batchUpdateStatus() {
    //     //1.将status=3 的订单, withdrawal_time已经超过了24小时。将status=4，设为过期
    //     // 使用正确的微信转账服务查询状态
    //     //提现状态:0-待处理,1-已到账,2-失败,3-未确认收款(微信)
    //     $transferService = new \App\Services\WechatTransferService();  
    //     $list = UserWithdrawal::query()
    //     ->whereIn('withdrawal_status', [0,2,3])
    //     ->get();
        
    //     foreach ($list as $item) {
    //         //微信的                         
    //         $statusResult = $transferService->queryTransferStatus($item->withdrawal_no);
         
    //         if ($statusResult['state']=='WAIT_USER_CONFIRM') {
    //             continue;
    //         }
    //         if (!empty($statusResult) || $statusResult['state']=='SUCCESS') {
                
    //             $item->withdrawal_status = 1;
    //             $item->arrival_time = time();
    //             $item->save();
    //             $user = \App\Models\User::find($item->user_id);
    //             $userBalanceLog = new \App\Models\UserBalaceLog();
    //             $userBalanceLog->user_id = $item->user_id;
    //             $userBalanceLog->order_id = $item->id;
    //             $userBalanceLog->transaction_type = 3;
    //             $userBalanceLog->amount = $item->amount;
    //             $userBalanceLog->balance_before = $user->balance;
    //             $userBalanceLog->balance_after = $user->balance;
    //             $userBalanceLog->transaction_no = $item->withdrawal_no;
    //             $userBalanceLog->remark = '微信提现已经确认,已到账';
    //             $userBalanceLog->created_at = date('Y-m-d H:i:s');
    //             $userBalanceLog->save();
    //         } elseif ($statusResult['state']!='SUCCESS' && $statusResult['state']=='WAIT_USER_CONFIRM') {
    //             $item->withdrawal_status = 2;
    //             $item->save();
    //             //回退余额
    //             $user = \App\Models\User::find($item->user_id);
    //             $user->balance = $user->balance + $item->amount;
    //             $user->save();
    //             $userBalanceLog = new \App\Models\UserBalaceLog();
    //             $userBalanceLog->user_id = $item->user_id;
    //             $userBalanceLog->order_id = $item->id;
    //             $userBalanceLog->transaction_type = 3;
    //             $userBalanceLog->amount = $item->amount;
    //             $userBalanceLog->balance_before = $user->balance;
    //             $userBalanceLog->balance_after = $user->balance;
    //             $userBalanceLog->transaction_no = $item->withdrawal_no;
    //             $userBalanceLog->remark = '微信提现用户超24小时未确认,已回退';
    //             $userBalanceLog->created_at = date('Y-m-d H:i:s');
    //             $userBalanceLog->save();
    //         }
    //     }
    //     //2.支付宝48小时未到账，将status=2，设为过期
    //     // $list = UserWithdrawal::where('withdrawal_status', 0)
    //     // ->where('withdrawal_type', 1)
    //     // ->where('withdrawal_time', '<', time() - 48 * 60 * 60)->get();
    //     // foreach ($list as $item) {
    //     //     $item->status = 2;
    //     //  $item->arrival_time = time();
    //     //     $item->save();
    //     //     //回退余额
    //     //     $user = \App\Models\User::find($item->user_id);
    //     //     $user->balance = $user->balance + $item->amount;
    //     //     $user->save();
    //     //     $userBalanceLog = new \App\Models\UserBalaceLog();
    //     //     $userBalanceLog->user_id = $item->user_id;
    //     //     $userBalanceLog->order_id = $item->id;
    //     //     $userBalanceLog->transaction_type = 3;
    //     //     $userBalanceLog->amount = $item->amount;
    //     //     $userBalanceLog->balance_before = $user->balance;
    //     //     $userBalanceLog->balance_after = $user->balance;
    //     //     $userBalanceLog->transaction_no = $item->withdrawal_no;
    //     //     $userBalanceLog->remark = '支付宝48小时未到账,已回退';
    //     //     $userBalanceLog->created_at = date('Y-m-d H:i:s');
    //     // }

    //     return true;
    // }

    /**
     * 充值记录详情
     */
    public function show($id)
    {
        $record = UserWithdrawal::findOrFail($id);
        return response()->json([
            'code' => 200,
            'message' => '获取成功',
            'data' => $record
        ]);
    }

    //新增提现
    public function create(Request $request)
    {
        $freeWithdrawalLimit = config('global_config.free_withdrawal_limit');

        $data = $request->all();
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:wechat,alipay'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'msg' => $validator->errors()->first()]);
        }

        $balance = \App\Models\User::find($this->user_id)->balance;
        if ($balance < $data['amount']) {
            return response()->json(['status' => 'error', 'msg' => '余额不足']);
        }
        if ($data['amount'] > $freeWithdrawalLimit) {
            return response()->json(['status' => 'error', 'msg' => '提现金额不能大于免提金额' . $freeWithdrawalLimit . '元']);
        }
        Log::info('用户余额: ' . $balance);
        //account_binding 判断where user_id 是否存在
        
        $account_binding = \App\Models\AccountBinding::where('user_id', $this->user_id)->first();
        if (empty($account_binding)) {
            return response()->json(['status' => 'error', 'msg' => '请先绑定账户']);
        }
           
        $account_bindingInfo = AccountBinding::where('user_id', $this->user_id)->first();
        if (empty($account_bindingInfo)) {
            return response()->json(['status' => 'error', 'msg' => '请先绑定账户']);
        }

        if ($request->payment_method == 'wechat') {
            if (empty($account_bindingInfo['wx_openid'])) {
                return response()->json(['status' => 'error', 'msg' => '请先绑定微信账户']);
            }
            $pay_account_name = $account_bindingInfo['wx_real_name']??'';
            $pay_account_number = $account_bindingInfo['wx_openid'];
            $withdrawal_type = 2;
        }
        if ($request->payment_method == 'alipay') {
            if (empty($account_bindingInfo['alipay_account_number']) || empty($account_bindingInfo['alipay_real_name'])) {
                return response()->json(['status' => 'error', 'msg' => '请先绑定支付宝账户']);
            }
            $pay_account_name = $account_bindingInfo['alipay_real_name'];
            $pay_account_number = $account_bindingInfo['alipay_account_number'];
            $withdrawal_type = 1;
        }


        $max_id = UserWithdrawal::withTrashed()->max("id");
        if (empty($max_id) || $max_id < 10000) {
            $current_id = 10000;
        } else {
            $current_id = $max_id + 1;
        }
        Log::info('当前ID: ' . $current_id);
        // DB::beginTransaction();
        // try { 
        $userWithdrawal = new UserWithdrawal();
        $userWithdrawal->id = $current_id;
        //生成一个提现单号
        $userWithdrawal->withdrawal_no = $withdrawal_no = date('YmdHis') . rand(10000, 99999);
        $userWithdrawal->user_id = $this->user_id;
        $userWithdrawal->amount = $pay_amount = $data['amount'];

        $userWithdrawal->handling_fee = 0;
        $userWithdrawal->actual_amount = 0;
        $userWithdrawal->withdrawal_type = $withdrawal_type;
        //
        $userWithdrawal->withdrawal_status = 0; // 待处理
        $userWithdrawal->withdrawal_time = time();    //TimeSTAMP 
        $userWithdrawal->pay_account_name = $pay_account_name;
        $userWithdrawal->pay_account_number = $pay_account_number;
        $userWithdrawal->save();
        //要扣除余额，等失败在补回
        $user = \App\Models\User::find($this->user_id);
        $user->balance = $user->balance - $data['amount'];
        $user->save();
        $userBalanceLog = new \App\Models\UserBalaceLog();
        $userBalanceLog->user_id = $this->user_id;
        $userBalanceLog->order_id = $userWithdrawal->id;
        $userBalanceLog->transaction_type = 3;  //3-提现
        $userBalanceLog->amount = $data['amount'];
        $userBalanceLog->balance_before = $user->balance;
        $userBalanceLog->balance_after = $user->balance;
        $userBalanceLog->transaction_no = $userWithdrawal->withdrawal_no;
        $userBalanceLog->remark = '预提现申请,已扣除,等待用户确认';
        $userBalanceLog->created_at = date('Y-m-d H:i:s');
        $userBalanceLog->save();
      
        //创建转账订单
        ////1-微信 ，2-支付宝
        $paymentTransferService = new PaymentTransferService();
        if ($withdrawal_type == 1) {
            //支付宝
            $pay = [];
            $pay['user_id'] = $this->user_id;
            $pay['order_id'] = $userWithdrawal->id;
            $pay['mch_order_no'] = $userWithdrawal->withdrawal_no;
            $pay['amount'] = $pay_amount;
            $pay['real_name'] = $account_bindingInfo['alipay_real_name'];
            $pay['account'] = $account_bindingInfo['alipay_account_number'];
            $pay['withdrawal_no'] = $withdrawal_no;
            //检查一下$pay是否正确
            foreach($pay as $key => $value) {
                if (empty($value)) {
                    throw new \Exception('参数缺失：' . $key);
                }
            }
            $paymentTransferService->AlipayCreateTransfer($pay);
        } else {
            //微信开通企业转账个人
            $userWithdrawal->withdrawal_time = time();    //TimeSTAMP 
            $userWithdrawal->pay_account_name = $pay_account_name;
            $userWithdrawal->pay_account_number = $pay_account_number;
            $userWithdrawal->save();
            $pay = [];
            $pay['mch_order_no'] = $userWithdrawal->withdrawal_no;
            $pay['amount'] = $pay_amount;
            // $pay['wx_real_name'] = $account_bindingInfo['wx_real_name'];
            $pay['wx_openid'] = $account_bindingInfo['wx_openid'];
            $pay['withdrawal_no'] = $withdrawal_no;
           
            //检查一下$pay是否正确
            foreach(['mch_order_no','amount','wx_openid','withdrawal_no'] as  $value) {
                if (empty($pay[$value])) {
                    throw new \Exception('参数缺失：' .  $pay[$value]);
                }
            }
       
            save_log('微信支付参数: ' . json_encode($pay),"WechatCreateTransfer");
            $paymentTransferService->WechatCreateTransfer($pay);
            return response()->json(['status' => 'success', 'msg' => '微信提现已经转账成功，等待用户确认。注意要在微信浏览器中打开确认页面']);
        }
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('提现失败: ' . $e->getMessage());
        //     //详细的错误 
        //     $error = '创建转账订单失败:' . $e->getMessage() . ' 行号:' . $e->getLine() . ' 错误:' . $e->getTraceAsString();
        //     save_log($error,"paymentTransferservice");
        //     return response()->json(['status' => 'error','msg' => '创建提现记录失败']);
        // }
        // DB::commit();
        return response()->json(['status' => 'success', 'msg' => '成功处理提现！', 'record' => $userWithdrawal]);
    }

    public function delete($id)
    {
        $record = UserWithdrawal::findOrFail($id);
        //提现状态:0-待处理,1-已到账,2-失败
        if ($record->withdrawal_status == 1) {
            return response()->json(['status' => 'error', 'msg' => '已到账的提现记录不能删除']);
        }
        if ($record->withdrawal_status == 0) {
            //回退余额
            $user = \App\Models\User::find($this->user_id);
            $user->balance = $user->balance + $record->amount;
            $user->save();

            Log::info('删除提现记录 >>>> 用户名:' . $user->username . ',,提现单号:' . $record->withdrawal_no . ',,回退余额:' . $record->amount . ',,时间:' . date('Y-m-d H:i:s'));
        }
        $record->deleted_at = date('Y-m-d H:i:s');
        $record->save();
        return response()->json(['status' => 'success', 'msg' => '删除提现记录成功']);
    }

    //微信待收款确认
    public function wechatConfirm(Request $request)
    {
        save_log($request->all(),"wechatConfirm");
        $id = $request->input('id');
        $currentUrl = $request->input('currentUrl');

        if (empty($id)) {
            return response()->json(['status' => 'error', 'msg' => '参数缺失：id']);
        }

        $record = UserWithdrawal::find($id);
        if (empty($record)) {
            return response()->json(['status' => 'error', 'msg' => '提现记录不存在']);
        }
        // 仅当转账成功且待用户确认（3）时，才能拉起确认收款
        if ((int)$record->withdrawal_status !== 3) {
            return response()->json(['status' => 'error', 'msg' => '提现记录状态不正确']);
        }
        try {
            // 生成JS-SDK配置
            $wechatService = new \App\Services\WechatJSConfigService();
            // 后端兜底获取当前URL，避免前端未传导致签名异常
            if (empty($currentUrl)) {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? '';
                $uri = $_SERVER['REQUEST_URI'] ?? '';
                $currentUrl = $scheme . $host . $uri;
            }
            $js_config = $wechatService->getSignPackage($currentUrl);

            // 使用正确的微信转账服务查询状态
            $transferService = new \App\Services\WechatTransferService();               
            $statusResult = $transferService->queryTransferStatus($record->withdrawal_no);
            if (!$statusResult || $statusResult['state']!='WAIT_USER_CONFIRM') {
                throw new \Exception("当前订单{$record->withdrawal_no}当前提现状态不正确,请稍后再试");
            }
 
            return response()->json([
                'status' => 'success',
                'msg' => '获取微信JS-SDK配置成功',
                'js_config' => $js_config,
                'transferParams' => [
                    'mchId' => config('wechat.mch_id'),
                    'appId' => config('wechat.appid'),
                    'package' => $record->wx_package_info,
                ],
                'transferId' => $record->third_party_order_no,
            ]);
        } catch (\Throwable $e) {
// dd("337>>>",$e);
            //加强错误 的输出，错误在哪个文件 的哪一行
            save_log($e->getMessage(), "wechatConfirm");
            save_log($e->getTraceAsString(), "wechatConfirm");
            save_log('微信确认收款失败: ' . $e->getFile() . ' 第 ' . $e->getLine() . ' 行', "wechatConfirm");
            // save_log('微信确认收款失败: ' . $e->getTraceAsString(), "wechatConfirm");
            // save_log('微信确认收款失败: ' . $e->getTrace(), "wechatConfirm");
            // save_log('微信确认收款失败: ' . $e->getTraceAsString(), "wechatConfirm");
            // save_log('微信确认收款失败: ' . $e->getTraceAsString(), "wechatConfirm");
            return response()->json(['status' => 'error','msg' => '查询微信转账状态失败: '.$e->getMessage()]);
        }
    }

    //微信用户确定收款时的回调状态
    public function wxcheckTransferStatus(Request $request)
    {
        save_log($request->all(),"wxcheckTransferStatus");
        $transferId = $request->input('transferId','');
    
        $record = UserWithdrawal::where('third_party_order_no',$transferId)->first();
        // 使用正确的微信转账服务查询状态
        $transferService = new \App\Services\WechatTransferService();
        $statusResult = $transferService->queryTransferStatus($record->withdrawal_no);
        save_log($record->withdrawal_no,"wxcheckTransferStatus");
        save_log($statusResult,"wxcheckTransferStatus");
        if ($statusResult['state']=='SUCCESS') {
            $record->withdrawal_status = 1; //1-已到账
            $record->save();            
            $user = \App\Models\User::find($record->user_id);           
            $userBalanceLog = new \App\Models\UserBalaceLog();
            $userBalanceLog->user_id = $record->user_id;
            $userBalanceLog->order_id = $record->id;
            $userBalanceLog->transaction_type = 3;
            $userBalanceLog->amount = $record->amount;
            $userBalanceLog->balance_before = $user->balance;
            $userBalanceLog->balance_after = $user->balance;
            $userBalanceLog->transaction_no = $record->withdrawal_no;
            $userBalanceLog->remark = '微信提现成功';
            $userBalanceLog->created_at = date('Y-m-d H:i:s');
            $userBalanceLog->save();
        }
 

    }

}
