<?php

namespace App\Http\Controllers\Api\h5;

use App\Models\GlobalConfig;
use App\Models\User;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use SebastianBergmann\CliParser\AmbiguousOptionException;
use Illuminate\Support\Facades\Http;  
use Illuminate\Support\Str;
use App\Services\JdUnionClient;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

class LoginController extends H5BaseController
{

    public function login(Request $request)
    {
        $phone = $request->input('phone');
        $invitation_code = $request->input('invitation_code');   //invitation_code做为登录密码
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => '手机号不存在']);
        }
        if ($user->invitation_code != $invitation_code) {
            return response()->json(['status' => 'error', 'message' => '推荐码错误']);
        }
        // $token = md5($phone.$invitation_code.time());
        $token = md5($phone.$invitation_code.time());
        DB::table('tokens')->insert([
            'token' => $token,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
 
        return response()->json(['status' => 'success', 'message' => '登录成功', 'token' => $token,'user' =>[
                "user_id"=>$user->id,                
                "phone"=>$user->phone,                
                "invitation_code"=>$user->invitation_code            
            ]
    
        ]);


    }

    public function getUserInfo(Request $request) {
 
        // 使用基类方法获取用户信息
        $user = $this->getCurrentUser();
        $user_id = $this->getCurrentUserId();        
        return response()->json([
            'status' => 'success',
            'message' => '获取用户信息成功',
            'data' => [
                'user_id' => $user_id,
                'username' => $user->username,
                'phone' => $user->phone,
                'balance' => $user->balance,
                'status' => $user->status
            ]
        ]);
    }

}