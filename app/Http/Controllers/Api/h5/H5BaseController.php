<?php

namespace App\Http\Controllers\Api\h5;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Support\Facades\DB;

//加入跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");


class H5BaseController extends BaseController
{
         
    protected $request;
    protected $user = null;
    protected $user_id = null;

    protected $user_status = 0;

    public function __construct()
    {
        $request = Request();
        $this->request = is_null($request) ? app(Request::class) : $request;
 
        $this->checkH5Auth();
    }
    
    /**
     * 检查 H5 认证
     */
    protected function checkH5Auth()
    {
        $token = $this->request->header('Authorization');
          
        if (!$token) {
            $token = $this->request->input('token');
        }
        
        if (!$token) {
            $this->user = null;
            $this->user_id = null;
            return;
        }
        $token = str_replace("Bearer ",'',$token);
     
        // 从Cache中获取用户ID
        $user_id = DB::table('tokens')->where('token',$token)->value("user_id");
 
        if ($user_id && intval($user_id) > 0) {
            
            $user = User::find($user_id);
          
            if ($user) {
                $this->user = $user;
                $this->user_id = $user->id;
                $this->user_status = $user->status;
            }
        }
    }
    
    /**
     * 检查用户是否已认证
     */
    protected function requireAuth()
    {
        if (!$this->user) {
            return response()->json([
                'status' => 'error',
                'message' => '请先登录',
                'code' => 401
            ], 401);
        }
        return null;
    }
    
    /**
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        return $this->user;
    }
    
    /**
     * 获取当前用户ID
     */
    protected function getCurrentUserId()
    {
        return $this->user_id;
    }
}
