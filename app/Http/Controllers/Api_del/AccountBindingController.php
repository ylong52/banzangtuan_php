<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ApiCommand;
use App\Models\AccountBinding;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AccountBindingController extends ApiCommand
{
    protected $error_msg = '';

    public function getOne(Request $request)
    {         
        $user = User::where('id', $this->user_id)->first();
 
        $data = AccountBinding::where('user_id', $this->user_id)->first();     
        if(!$data){
            $data = new AccountBinding();
            $data->user_id = $this->user_id;
            $data->use_accounttype = 0;
            $data->wx_openid = $user->wechat_openid;
            $data->created_at = date('Y-m-d H:i:s');
            $data->updated_at = date('Y-m-d H:i:s');
            $data->save();
        } else {
            if ($user->wechat_openid && !$data->wx_openid) {
                $data->wx_openid = $user->wechat_openid;
                $data->updated_at = date('Y-m-d H:i:s');
                $data->save();
            }
        }
         
        return response()->json(['status' =>'success','data' => $data]);        
    }

    public function store(Request $request)
    {        
        // 检查user_id是否为空
        if (intval($this->user_id)<=0) {
            return response()->json([
                'status' => 'error',
                'message' => '用户ID不能为空'
            ], 400);
        }

        // 获取请求数据并添加user_id
        $requestData = $request->all();
        $requestData['user_id'] = $this->user_id;
 
        // 使用updateOrCreate方法，如果存在则更新，不存在则创建
        $data = AccountBinding::updateOrCreate(
            ['user_id' => $this->user_id], // 查找条件
            $requestData // 更新或创建的数据
        );

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function delete(Request $request,$type)
    {
        if (!intval($type)>0) {
            return response()->json(['status' =>'error','message' => 'type参数错误']);
        }

        $data = AccountBinding::where('user_id', $this->user_id)->first();
        if($data){            
            //清除支付宝
            if ($request->type==1) {
                $data->alipay_account_number = null;
                $data->alipay_real_name = null;
                $data->updated_at = date('Y-m-d H:i:s');
                $data->save();
            }
            if ($request->type==2) {
                $data->wx_real_name = null;
                $data->wx_openid = null;
                $data->updated_at = date('Y-m-d H:i:s');
                $data->save();
                User::where('id', $this->user_id)->update(['wechat_openid' => null]);
            }
             
        } else {
            return response()->json(['status' =>'error','message' => '数据不存在']);
        }
        return response()->json(['status' =>'success','data' => $data]);
    }

    public function modifyUseAccounttype(Request $request,$use_accounttype)
    {
        //判断$use_accounttype，必须是大于0的数字
        if (!intval($use_accounttype)>0) {
            return response()->json(['status' =>'error','message' => 'use_accounttype参数错误']);
        }
        $data = AccountBinding::where('user_id', $this->user_id)->first(); 
        if ($data) {
            $data->use_accounttype = $use_accounttype;
            $data->save();
        }
        return response()->json(['status' =>'success','data' => $data]);
    }

    public function modifyWechatName(Request $request)
    {
        $data = AccountBinding::where('user_id', $this->user_id)->first(); 
        if ($data) {
            $data->wx_real_name = $request->wx_real_name;
            $data->save();
        }
        return response()->json(['status' =>'success','data' => $data]);
    }

}