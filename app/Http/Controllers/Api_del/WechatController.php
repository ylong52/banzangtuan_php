<?php

namespace App\Http\Controllers\Api;

use App\Services\WeixinSevice;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiCommand;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Services\DynamicPropertyService;

class WechatController extends ApiCommand
{
    protected $weixinService;

    public function __construct()
    {
        // $this->weixinService = $weixinService;
        // 对auth方法排除API中间件
        $this->middleware('web')->only(['auth']);
        parent::__construct();
    }

    /**
     * 生成微信授权链接，引导用户授权获取openid
     */
    public function auth(Request $request)
    {
        $username = $request->input('user');

        // 使用 Session 替代 Cache，更可靠
        Session::put('wechat_username', $username);
        
 
        $redirectUrl = url('/wechat/callback'); // 保持与路由一致
         
        try {
            $wechatOfficials = DynamicPropertyService::getAllByTypeTag('wechat_official');
            // 直接构建微信授权URL，避免处理Response对象
            $appId = config('wechat.appid');
          
            $scope = 'snsapi_base';
            $state = Str::random(32);
            
            // 构建微信授权URL
            $wechatAuthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query([
                'appid' => $appId,
                'redirect_uri' => $redirectUrl,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state,
                'connect_redirect' => 1
            ]) . '#wechat_redirect';
            
            // 将state存储到session中，用于回调验证
            Session::put('wechat_oauth_state', $state);
            
            Log::info('微信OAuth重定向', [
                'username' => $username,
                'redirect_url' => $redirectUrl,
                'wechat_auth_url' => $wechatAuthUrl,
                'state' => $state
            ]);
            
            return redirect($wechatAuthUrl);
            
        } catch (\Exception $e) {
            Log::error('微信OAuth重定向失败', [
                'error' => $e->getMessage(),
                'redirect_url' => $redirectUrl,
                'username' => $username,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '微信授权失败，请稍后重试',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 微信授权回调处理
     */
       public function callback(Request $request)
{
    try {
        $code = $request->input('code');
        $state = $request->input('state');
        $username = Session::get('wechat_username');
        $storedState = Session::get('wechat_oauth_state');
        
        // 调试：检查 Session 中的值
        Log::info('微信OAuth回调', [
            'code' => $code,
            'state' => $state,
            'stored_state' => $storedState,
            'username' => $username,
            'session_id' => session()->getId(),
            'all_session_data' => Session::all()
        ]);
        
        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => '授权失败：缺少授权码'
            ]);
        }
        
        // 验证state参数（可选，增强安全性）
        if ($state && $storedState && $state !== $storedState) {
            Log::warning('微信OAuth state验证失败', [
                'received_state' => $state,
                'stored_state' => $storedState
            ]);
        }

       
        // 通过授权码获取访问令牌和openid
        $response = app('easywechat.official_account')->oauth->getAccessToken($code);
            
        if (!isset($response['access_token']) || !isset($response['openid'])) {
            throw new \Exception('获取access_token或openid失败');
        }
        
        $openid = $response['openid'];
        if (false==$openid){
            throw new \Exception('获取openid失败');
        }
            
        if ($openid) {
            Session::put('wechat_openid', $openid);
            Cache::put('wechat_openid_' . session()->getId(), $openid, 3600);
            $user = User::where('username', $username)->first();

            if($user){
                $user->wechat_openid = $openid;
                $user->save();
            }
    
            if ($openid) {
                //转到首页
                $domain = env('APP_URL');
                return redirect($domain."/personal/payment-binding");
            }
        }
      
        
        return response()->json([
            'success' => false,
            'message' => '无法获取用户openid'
        ]);
        
    } catch (\Exception $e) {
        Log::error('微信授权回调处理失败: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => '授权失败',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * 获取当前用户的 openid
     */
    public function getOpenid()
    {
        try {
            $openid = Session::get('wechat_openid');
            if (!$openid) {
                $openid = Cache::get('wechat_openid_' . session()->getId());
            }
            if ($openid) {
                return response()->json([
                    'success' => true,
                    'openid' => $openid
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => '未找到 openid，请先进行微信授权'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('获取 openid 失败: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '获取 openid 失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清除缓存的 openid
     */
    public function clearOpenid()
    {
        try {
            Session::forget('wechat_openid');
            Cache::forget('wechat_openid_' . session()->getId());
            return response()->json([
                'success' => true,
                'message' => 'openid 已清除'
            ]);
        } catch (\Exception $e) {
            Log::error('清除 openid 失败: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => '清除 openid 失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}