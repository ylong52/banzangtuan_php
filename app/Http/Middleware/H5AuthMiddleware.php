<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class H5AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 获取 token
        $token = $request->header('Authorization');
        if (!$token) {
            $token = $request->input('token');
        }
        
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token 缺失',
                'code' => 401
            ], 401);
        }
        $token = str_replace("Bearer ",'',$token);
     
        $user_id =  DB::table('tokens')->where('token',$token)->value("user_id");
        
 
        if (!$user_id || intval($user_id) <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token 无效或已过期',
                'code' => 401
            ], 401);
        }
        
        
        // 获取用户信息
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => '用户不存在',
                'code' => 401
            ], 401);
        }
        
        // 检查用户状态
        if ($user->status == 0) {
            return response()->json([
                'status' => 'error',
                'message' => '用户已被禁用',
                'code' => 403
            ], 403);
        }
        
        // 将用户信息添加到请求中
        $request->merge(['user' => $user]);
        $request->merge(['user_id' => $user->id]);
        
        return $next($request);
    }
}
