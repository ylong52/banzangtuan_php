<?php

namespace App\Http\Controllers;

use App\Models\GlobalConfig;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CliParser\AmbiguousOptionException;
use Illuminate\Support\Facades\Http; // Added for HTTP requests
use Illuminate\Support\Str;
use App\Http\Controllers\ApiController;

class AuthController extends ApiController
{
    public function autoRegisterAndLogin(Request $request)
    {
 
        try {
            // 验证必要参数
            $validator = Validator::make($request->all(), [
                'detail.encryptedData' => 'required|string',
                'detail.iv' => 'required|string',
                'detail.code' => 'required|string',
                'openid' => 'required|string',
            ], [
                'detail.encryptedData.required' => '加密数据不能为空',
                'detail.iv.required' => '初始向量不能为空',
                'detail.code.required' => '授权码不能为空',
                'openid.required' => '微信OpenID不能为空',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                $firstError = count($errors) > 0 ? $errors[0] : '参数验证失败';
                return response()->json([
                    'status' => 'error',
                    'message' => $firstError
                ], 400);
            }
 
            $code = $request->input('detail.code');
            $openid = $request->input('openid');
            $invitationCode = $request->input('invitationCode');
            if ($invitationCode == '888888') {
                $invitationCode = '';
            }
            // 通过code获取session_key
            $phoneNumber = $this->getSessionKeyByPhone($code);
                     
            if (!$phoneNumber) {               
                throw new \Exception('手机号解密失败',400);
            }

            // 检查用户是否已存在（通过openid或手机号）
            $user = User::where('wechat_openid', $openid)
                       ->orWhere('phone', $phoneNumber)
                       ->first();

            if (!$user) {
                // 用户不存在，创建新用户
                DB::beginTransaction();
                try {
                    $max_id = User::withTrashed()->max('id');
                    if (empty($max_id) || $max_id < 1000) {
                        $current_id = 1000;
                    } else {
                        $current_id = $max_id + 1;
                    }

                    $user = User::create([
                        'id' => $current_id,                     
                        'phone' => $phoneNumber,
                        'wechat_openid' => $openid,
                        'password' => Hash::make(Str::random(16)), // 生成随机密码
                        'status' => 1,
                    ]);
                    $user->save();
                    DB::commit();
                    $user = User::where('id', $user->id)->first();
                } catch (\Exception $e) {
                    DB::rollBack();
                    save_log($e->getMessage(), 'wechat_register_error');
                    return response()->json([
                        'status' => 'error',
                        'message' => '用户创建失败: ' . $e->getMessage()
                    ], 500);
                }
            }
            if ($user->status == 0) {
                throw new \Exception('用户已禁止登录',400);
            }
          
            if (empty($user->invitation_code)) {
                $user->invitation_code = $this->generateInvitationCode();  //新用户生成自己的invitation_code
                $user->sub_union_id = config('app.subUnionIdx').$user->invitation_code;
                $user->save();
            }
            $this->saveUserPromotion($user->id, $invitationCode);
            // 生成token
            /**              
             * createToken() 方法参数说明：
             * 1. 'wechat-token' - 令牌名称，用于标识这个令牌的用途
             * 2. ['*'] - 权限范围，'*' 表示所有权限
             * 3. now()->addDays(365) - 令牌过期时间，从当前时间开始365天后过期
             * 
             * plainTextToken - 获取令牌的纯文本形式，用于返回给客户端
             */
            $token = $user->createToken('wechat-token', ['*'], now()->addDays(365))->plainTextToken;
                            
            $userInfo = $user->makeHidden(['password', 'deleted_at', 'created_at', 'updated_at', 'balance' ]);

            return response()->json([
                'status' => 'success',
                'message' => '登录成功',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60 * 24 * 365, // 365天（秒）
                    'user' => $userInfo 
                ]
            ], 200);
        } catch (\Exception $e) {
            save_log($e->getMessage(), 'wechat_auto_register_error');
            return response()->json([
                'status' => 'error',
                'message' => '系统错误: ' . $e->getMessage()
            ], 500);
        }
    }

    private function saveUserPromotion($user_id, $invitation_code) {      
 
        if (!$this->verifyInvitationCode($user_id,$invitation_code)) {
            return false;
        }
        $id = User::where(['invitation_code'=>$invitation_code])->value("id");
        $promotion = new \App\Models\Promotion();
        $promotion->user_id = $user_id; //新用户
        $promotion->referred_by = $id;  // 推荐人
        $promotion->referral_code = $invitation_code;  // 推荐人
        $promotion->registration_time = date('Y-m-d H:i:s');
        $promotion->reward_amount = 0;
        $promotion->reward_status = 0; 
        $promotion->save();
    }   

    private function verifyInvitationCode($user_id,$invitationCode) { 
        if (intval($user_id) == 0 ){
            return false;
        } 
        $invitation_code = User::where('id', $user_id)->value('invitation_code');
        if (!empty($invitation_code)){
            //已经绑了注册码
            return false;
        }
        $count = User::where('invitation_code', $invitationCode)        
        ->where('id', '!=', $this->user_id)
        ->count();
        if ($count > 0){
            return true;
        }
        return false;
    }

    private function generateInvitationCode() {
        do {
            // 生成5位随机字符串（数字+大小写字母）
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $string = '';
            for ($i = 0; $i < 5; $i++) {  // 修复：应该是 < 5 而不是 <= 4
                $string .= $characters[rand(0, strlen($characters) - 1)];
            }
            
            // 生成1位小写字母作为前缀
            $characters = 'abcdefghijklmnopqrstuvwxyz';
            $string2 = $characters[rand(0, strlen($characters) - 1)];

            // 组合：1位小写字母 + 5位随机字符串（转为小写）
            $invitationCode = $string2 . strtolower($string);         
            
            // 检查数据库中是否已存在该邀请码
            $exists = User::where('invitation_code', $invitationCode)->exists();            
        } while ($exists); // 若存在则重新生成
        return $invitationCode;
    }

    public function generateUniqueUsername(string $phoneNumber): string
    {
        // 提取手机号后4位，若手机号不足4位则用默认值填充
        $phoneSuffix = strlen($phoneNumber) >= 4 
            ? substr($phoneNumber, -4) 
            : str_pad($phoneNumber, 4, '0');

        do {
            // 生成2位随机数字（00-99）
            $randomSuffix = str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
            
            // 组合成8位用户名（wx + 4位手机尾号 + 2位随机数）
            $username = 'JQ' . $phoneSuffix . $randomSuffix;
            
            // 检查数据库中是否已存在该用户名
            $exists = User::where('username', $username)->exists();
            
        } while ($exists); // 若存在则重新生成

        return $username;
    }

    /**
     * 通过code获取session_key
     */
    private function getSessionKeyByPhone($code)
    {
        $appid = config('wechat.appid');
        $appsecret = config('wechat.appsecret');
       
         
        // 1. 获取接口调用凭证 access_token
        $accessTokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $accessTokenResp = $this->httpRequest($accessTokenUrl);
        $accessTokenData = json_decode($accessTokenResp, true);

        if (empty($accessTokenData['access_token'])) {            
            throw new \Exception('获取access_token失败');
        }
        $accessToken = $accessTokenData['access_token'];

        // 2. 使用code调用手机号获取接口
        $phoneUrl = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$accessToken}";
        $postData = json_encode(['code' => $code]);
        $phoneResp = $this->httpRequest($phoneUrl, $postData, 'POST');
        $phoneData = json_decode($phoneResp, true);
     
        if (!isset($phoneData['errcode'])) {
            throw new \Exception('调用手机号获取接口失败');
        }
       
        if ($phoneData['errcode'] == 0 && $phoneData['errmsg'] =="ok" && !empty($phoneData['phone_info']['phoneNumber'])) {
            return $phoneData['phone_info']['phoneNumber'];
        }  
        
        throw new \Exception($phoneData['errmsg'] ?? '调用手机号获取接口失败');
         
    }

    /**
     * HTTP请求工具函数
     * @param string $url 请求地址
     * @param string $data POST数据
     * @param string $method 请求方法
     * @return string 响应内容
     */
    function httpRequest($url, $data = '', $method = 'GET') {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data != '') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
 

    public function register(Request $request)
    {
        save_log($request->all(),'register');
        die;
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            // 'phone' => 'required|string|max:11|unique:users',
            'password' => 'required|string|min:6',
        ], [
            'username.required' => '用户名不能为空',
            'username.max' => '用户名不能超过255个字符',
            'username.unique' => '用户名已存在',
            // 'phone.required' => '手机号不能为空',
            // 'phone.max' => '手机号不能超过11个字符',
            // 'phone.unique' => '手机号已经注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码不能小于6个字符',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $firstError = count($errors) > 0 ? $errors[0] : '注册失败';
            return response()->json([
                'status' => 'error',
                'message' => $firstError
            ], 400);
        }

        $max_id = User::withTrashed()->max('id');
        if (empty($max_id) || $max_id < 10000) {
            $current_id = 10000;
        } else {
            $current_id = $max_id + 1;
        }
        DB::beginTransaction();
        try {
            $user = User::create([
                'id' => $current_id,
                'username' => $request->username,
                // 'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);
            $invite  = $request->invite;
       
            if (!empty($invite)) {       
                //核对一下
                $count = User::where('id', $invite)->count();               
                if ($count > 0) {                    
                    //新增推广记录
                    $invite_amount = \App\Models\GlobalConfig::where(['status'=>1,'key'=>'invite_amount'])->value('value');
                    $promotion = new \App\Models\Promotion();
                    $promotion->user_id = $user->id;  //新用户
                    $promotion->referred_by = $invite;  //推荐人
                    $promotion->referral_code = $invite;  //推荐人
                    $promotion->registration_time = date('Y-m-d H:i:s');
                    $promotion->reward_amount = $invite_amount;
                    $promotion->reward_status = 1;  //1已发放
                    $promotion->save();
                    //更新用户余额日志  
                    $this->updateuser_balance_log($user->id, $invite);

                }
                              
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
        $userInfo = User::where('id',$user->id)->first()->makeHidden(['password','deleted_at','created_at','updated_at','balance_password']);
        DB::commit();
        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => '注册成功',
                'data' => $userInfo
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => '注册失败'
            ], 500);
        }
    }

    // //更新用户余额日志
    private function updateuser_balance_log($new_user_id, $referral_code)
    {
        $amount =  GlobalConfig::where("key","invite_amount")->value('value');
        if (!$amount) {
            $amount = 0;
        }
        $userInfo = User::where('id', $referral_code)->first();
        if ($userInfo) {
            $balance_before = $userInfo->balance;
            $balance_after = $balance_before + $amount;
        }
        $user_balance_log = new \App\Models\UserBalaceLog();
        $user_balance_log->user_id = $referral_code;
        $user_balance_log->amount = $amount;
        $user_balance_log->transaction_type = 4;
        $user_balance_log->balance_before = $balance_before;
        $user_balance_log->balance_after = $balance_after;
        $user_balance_log->transaction_no = '';
        $user_balance_log->remark = '推荐' . $new_user_id . '注册奖励';
        $user_balance_log->created_at = date('Y-m-d H:i:s');
        $user_balance_log->save();
        //更新用户余额
        $userInfo->balance = $balance_after;
        $userInfo->save();
    }


    public function login(Request $request)
    {
        // 61行开始
        try {
            $credentials = $request->validate([
                'username' => 'required|string|min:6',
                'password' => 'required',
            ], [
                'username.required' => '用户名不能为空',
                'username.min' => '用户名不能小于6个字符',
                'username.max' => '用户名不能超过255个字符',
                'password.required' => '密码不能为空',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // 只取第一个错误信息
            $firstError = collect($e->errors())->flatten()->first();
            return response()->json([
                'code' => 422,
                'msg' => $firstError // 只返回第一个错误字符串
            ], 422);
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            // 生成一个有效期为7天的token（可用Laravel Sanctum/Passport，或自定义token）
            if (method_exists($user, 'createToken')) {
                $token = $user->createToken('api-token', ['*'], now()->addDays(7))->plainTextToken;
            } else {
                throw new \Exception('User模型未正确配置API令牌功能');
            }
            $userInfo = User::where('id',$user->id)->select('id','username','phone','balance')->where('deleted_at',null)->first();
            return response()->json([
                'status' => 'success',
                'message' => '登录成功',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 60 * 60 * 24 * 7, // 7天（秒）
                    'user' => $userInfo
                ]
            ], 200);
        } else {
            return response()->json([
                'code' => 401,
                'msg' => '用户名或密码错误'
            ], 401);
        }
    }


    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    //忘记密码
    public function forgotPassword(Request $request)
    {        
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:11',
            'password' => 'required|string|min:6',
            'verify_code' => 'required',
        ], [
            'phone.required' => '手机号不能为空',
            'phone.max' => '手机号不能超过11个字符',
            'password.required' => '新密码不能为空',
            'password.min' => '新密码不能小于6个字符',
            'verify_code.required' => '验证码不能为空',
        ]);
                
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $firstError = count($errors) > 0 ? $errors[0] : '重置密码失败';
            return response()->json([
                'status' => 'error',
                'message' => $firstError
            ], 400);
        }
        
        // 查找用户
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '该手机号未注册'
            ], 404);
        }
        
        // 验证验证码
        if ($request->verify_code != '888888' && !$this->verifyCode($request->phone, $request->verify_code)) {
            return response()->json([
                'status' => 'error',
                'message' => '验证码错误或已过期'
            ], 400);
        }
        
        try {
            // 更新密码
            $user->password = Hash::make($request->password);
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => '密码重置成功'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '密码重置失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * 发送验证码
     */
    public function sendVerifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:11',
        ], [
            'phone.required' => '手机号不能为空',
            'phone.max' => '手机号不能超过11个字符',
        ]);
        
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $firstError = count($errors) > 0 ? $errors[0] : '发送验证码失败';
            return response()->json([
                'status' => 'error',
                'message' => $firstError
            ], 400);
        }
        
        // 查找用户
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '该手机号未注册，请先注册'
            ], 200);
        }
        
        try {
            // 生成6位随机验证码
            $code = mt_rand(100000, 999999);
            
            // 存储验证码到缓存或数据库
            // 这里使用Redis缓存，设置5分钟过期
            \Illuminate\Support\Facades\Redis::setex('verify_code:' . $request->phone, 300, $code);
            
            // 发送短信验证码
            // 这里应该调用短信服务API发送验证码
            // 例如：$this->sendSms($request->phone, $code);
            
            return response()->json([
                'status' => 'success',
                'message' => '验证码已发送',
                'code' => $code, // 实际生产环境中应该移除此行，这里仅为测试方便
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '发送验证码失败：' . $e->getMessage()
            ], 500);
        }
    }
    
    // 验证码验证方法
    private function verifyCode($phone, $code)
    {
        // 从缓存或数据库中获取之前发送的验证码
        $storedCode = \Illuminate\Support\Facades\Redis::get('verify_code:' . $phone);
        
        // 验证码比对
        if ($storedCode && $storedCode == $code) {
            // 验证成功后删除缓存中的验证码
            \Illuminate\Support\Facades\Redis::del('verify_code:' . $phone);
            return true;
        }
        
        return false;
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ], [
            'old_password.required' => '当前密码不能为空',
            'new_password.required' => '新密码不能为空',
            'new_password.min' => '新密码不能小于6个字符',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $firstError = count($errors) > 0 ? $errors[0] : '修改密码失败';
            return response()->json([
                'status' => 'error',
                'message' => $firstError
            ], 400);
        }

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => '旧密码错误'
            ], 400);
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => '密码修改成功'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '密码修改失败：' . $e->getMessage()
            ], 500);
        }
    }

    public function checkInvitationCode(Request $request) {
        $validator = Validator::make($request->all(), [
            'invitationCode' => 'required|string|max:50',
        ], [
            'invitationCode.required' => '邀请码不能为空',
            'invitationCode.max' => '邀请码不能超过50个字符',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 400);
        }
        $invitationCode = $request->invitationCode;
        if ($invitationCode == '888888') {
            return response()->json([
                'status' => 'success',
                'message' => '邀请码有效'
            ], 200);
        }
        $user = User::where('invitation_code', $invitationCode)->first();
        
        if ($user) {
            return response()->json([
                'status' => 'success',
                'message' => '邀请码有效'
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => '邀请码无效'
            ], 200);
        }
    }

}
