<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiCommand;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiCommand
{
 
    /**
     * 获取用户信息
     *
     * @param int $id 用户ID
     * @return \Illuminate\Http\JsonResponse
     */
   

    public function index(Request $request) {
        $top_user_id = $request->input('top_user_id', 0);
        //从登录的用户开始取出user_id
        $list = User::where('is_deleted', 0)
            ->select('id', 'name', 'teams_id')
            ->when($top_user_id > 0, function($query) use ($top_user_id) {
                return $query->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$top_user_id]);
            })
            ->get();
        return response()->json(['status' => 'success','msg' => '获取用户列表成功','list'=>$list]);
    }

    public function userinfo(Request $request)
    {
        $user = User::where('id',$this->user_id)->select('id','username','phone','balance')->first();
       
        return response()->json(['status' => 'success','msg' => '获取用户信息成功','userinfo'=>$user]);
    }

    public function updateUser(Request $request)
    {
        $user = User::where('id',$this->user_id)->first();
        if (empty($user)) {
            return $this->error('用户不存在');
        }
        if ($request->input('balance') > 0) {   //充值  
            $user->balance = $request->input('balance',0);
        }
        if ($request->input('balance_password') != '') { //修改支付密码
            $user->balance_password = $request->input('balance_password',0);
        }
        if ($request->input('password') != '') { //修改密码
            $password = $request->input('password',0);
            $user->password = Hash::make($password);
        }
        $user->save();
        return response()->json(['status' => 'success','msg' => '更新用户信息成功','userinfo'=>$user]);
    }

    public function balance(Request $request)
    {
        $balance = User::where('id',$this->user_id)->value('balance');
        return response()->json(['status' => 'success','msg' => '获取用户余额成功','balance'=>$balance]);
    }
}
