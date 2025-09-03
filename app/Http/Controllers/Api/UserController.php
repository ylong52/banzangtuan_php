<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController
{
 
    public function userinfo(Request $request)
    {   

        if (empty($this->user_id)) {
            $user_id = 10000;
        } else {
            $user_id = $this->user_id;
            if ($this->user_status === 0) {
                //0表示禁用，1表示有效
                return response()->json(['status' => 'error','msg' => '用户已经禁用!']);
            }
        }
        $user = User::where('id',$user_id)->first();
        $user->makeHidden(['password']);
       
        return response()->json(['status' => 'success','msg' => '获取用户信息成功','userinfo'=>$user]);
    }

    public function updateUser(Request $request)
    {
        if ($this->user_status === 0) {
            //0表示禁用，1表示有效
            return response()->json(['status' => 'error','msg' => '用户已经禁用!']);
        }
        $proc = false;
        $user = User::where('id',$this->user_id)->first();
        if (empty($user)) {
          
            return $this->error('用户不存在');
        }
        if ($request->input('balance') > 0) {   //充值  
            $proc = true;
            $user->balance = $request->input('balance',0);
        }
        
        if ($request->input('password') != '') { //修改密码
            $proc = true;
            $password = $request->input('password',0);
            $user->password = Hash::make($password);
        }
        if ($request->input('username') != '') { 
            $proc = true;
            $user->username = $request->input('username','');
        }
        if ($request->input('avatar') != '') { //avatar
            $proc = true;
            $user->avatar = $request->avatar;
        }
        if ($proc==false) {
            return $this->error('没有需要更新的信息');
        }
        $user->makeHidden(['password']);
        $user->save();
        return response()->json(['status' => 'success','msg' => '更新用户信息成功','userinfo'=>$user]);
    }

    public function storeRealName(Request $request) {

        // 检查并修复代码
        $validator = Validator::make($request->all(), [
            'bank_real_name' => 'required|string|max:50',
            'id_card' => [
                'required',
                'string',
                'size:18',
                'regex:/^\d{17}[\dXx]$/'
            ],
            'bank_card' => [
                'required',
                'string',
                'digits_between:16,19'
            ],
            'bank_phone' => [
                'required',
                'string',
                'regex:/^1[3-9]\d{9}$/'
            ],
        ], [
            'bank_real_name.required' => '姓名不能为空',
            'bank_real_name.max' => '姓名长度不能超过50个字符',
            'id_card.required' => '身份证号码不能为空',
            'id_card.size' => '身份证号码必须为18位',
            'id_card.regex' => '身份证号码格式不正确',
            'bank_card.required' => '银行卡号不能为空',
            'bank_card.digits_between' => '银行卡号长度应为16到19位',
            'bank_phone.required' => '银行预留手机号不能为空',
            'bank_phone.regex' => '银行预留手机号格式不正确',
        ]);

        if ($validator->fails()) {
            // 只取第一个错误信息
            $firstError = collect($validator->errors()->all())->first();
            return response()->json([
                'code' => 422,
                'msg' => $firstError
            ], 422);
        }
        $data = $validator->validated();

        try {
            //  $user_id = 10000;
            $user_id = $this->user_id;
            $user = User::where('id',$user_id)->first();            
            // 检查用户是否存在
            if (empty($user)) {
                return $this->error('用户不存在');
            }
            
            $user->bank_real_name = $data['bank_real_name'];
            $user->id_card = $data['id_card'];
            $user->bank_card = $data['bank_card'];
            $user->bank_phone = $data['bank_phone'];
           
            $user->save();
            return response()->json(['status' => 'success','msg' => '提交成功']);
         
   
        }catch(\Exception $e) {
            return $this->error('提交失败');
        }

        
    }


    
    public function balance(Request $request)
    {
        $balance = User::where('id',$this->user_id)->value('balance');
        return response()->json(['status' => 'success','msg' => '获取用户余额成功','balance'=>$balance]);
    }
}
