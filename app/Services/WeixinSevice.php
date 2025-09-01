<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use App\Services\FeixpayRsaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class WeixinSevice
{
 
    /**
     * 构造微信授权链接获取 code
     * @param string $redirectUri 授权后重定向的回调地址
     * @param string $state 自定义参数，用于保持请求和回调的状态
     * @return string 授权链接
     */
    public function getAuthUrl($redirectUri, $state = '')
    {
        $appid = env('WEIXIN_APPID');
        dd($appid);        
        // 如果没有传入回调地址，使用默认地址
        if (empty($redirectUri)) {
            $redirectUri = 'https://juan.ai-book.top/wechat/callback';
        }
        
        // 手动构建查询字符串，避免双重编码
        $queryString = 'appid='.$appid
                     .'&redirect_uri='.rawurlencode($redirectUri)
                     .'&response_type=code'
                     .'&scope=snsapi_base'
                     .'&state='.urlencode($state);
        
        return 'https://open.weixin.qq.com/connect/oauth2/authorize?'.$queryString.'#wechat_redirect';
    }

    /**
     * 通过 code 换取 openid
     * @param string $code 微信授权码
     * @return array 返回包含 openid 的数组
     */
    public function getOpenidByCode($code)
    {
        $appid = env('WEIXIN_APPID');
        $secret = env('WEIXIN_SECRET');
        
        // 记录请求开始
        Log::info('开始通过code获取openid', [
            'code' => $code,
            'appid' => $appid,
            'has_secret' => !empty($secret)
        ]);
        
        if (empty($appid)) {
            $error = ['errcode' => -1, 'errmsg' => '微信AppID未配置'];
            Log::error('微信AppID未配置', $error);
            return $error;
        }
        
        if (empty($secret)) {
            $error = ['errcode' => -2, 'errmsg' => '微信AppSecret未配置'];
            Log::error('微信AppSecret未配置', $error);
            return $error;
        }
        
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . 
               'appid=' . $appid . 
               '&secret=' . $secret . 
               '&code=' . $code . 
               '&grant_type=authorization_code';
        
        try {
            Log::info('请求微信API', ['url' => $url]);
            
            $response = Http::timeout(30)->get($url);
            $statusCode = $response->status();
            $result = $response->json();
            
            Log::info('微信API响应', [
                'status_code' => $statusCode,
                'response_body' => $response->body(),
                'parsed_result' => $result
            ]);
            
            // 检查HTTP状态码
            if ($statusCode !== 200) {
                $error = [
                    'errcode' => -3,
                    'errmsg' => '微信API请求失败，HTTP状态码: ' . $statusCode,
                    'http_status' => $statusCode,
                    'response_body' => $response->body()
                ];
                Log::error('微信API HTTP错误', $error);
                return $error;
            }
            
            // 检查响应是否为空
            if (empty($result)) {
                $error = [
                    'errcode' => -4,
                    'errmsg' => '微信API返回空响应',
                    'response_body' => $response->body()
                ];
                Log::error('微信API空响应', $error);
                return $error;
            }
            
            // 检查微信API是否返回错误
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                $errorMessages = [
                    40001 => 'AppSecret错误或者AppSecret不属于这个公众号',
                    40013 => '不合法的AppID',
                    40029 => '不合法的oauth_code',
                    41004 => '缺少secret参数',
                    41008 => '缺少oauth code',
                    42003 => 'oauth_code超时',
                    40163 => 'code已被使用'
                ];
                
                $detailedMessage = $errorMessages[$result['errcode']] ?? ($result['errmsg'] ?? '未知错误');
                
                $error = [
                    'errcode' => $result['errcode'],
                    'errmsg' => $result['errmsg'] ?? '未知错误',
                    'detailed_message' => $detailedMessage,
                    'original_response' => $result
                ];
                
                Log::error('微信API返回错误', $error);
                return $error;
            }
            
            // 检查是否包含openid
            if (!isset($result['openid'])) {
                $error = [
                    'errcode' => -5,
                    'errmsg' => '微信API响应中缺少openid字段',
                    'response' => $result
                ];
                Log::error('微信API响应缺少openid', $error);
                return $error;
            }
            
            Log::info('微信获取openid成功', [
                'openid' => $result['openid'],
                'access_token' => isset($result['access_token']) ? 'present' : 'missing',
                'expires_in' => $result['expires_in'] ?? null,
                'scope' => $result['scope'] ?? null
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $error = [
                'errcode' => -6,
                'errmsg' => '请求微信API异常: ' . $e->getMessage(),
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            
            Log::error('微信API请求异常', $error);
            return $error;
        }
    }

    /**
     * 检测是否在微信浏览器中
     * @return bool
     */
    public function isWechatBrowser()
    {
        $userAgent = request()->header('User-Agent');
        return strpos($userAgent, 'MicroMessenger') !== false;
    }

    /**
     * 获取微信页面端 openid（完整流程）
     * @param string $redirectUri 回调地址
     * @param string $state 自定义状态参数
     * @return string|array 返回 openid 或授权链接
     */
    public function getWechatOpenid($redirectUri = null, $state = '')
    {
        // 检查是否在微信浏览器中
        if (!$this->isWechatBrowser()) {
            return ['error' => 'not_wechat_browser', 'message' => '请在微信浏览器中访问'];
        }

        // 如果未提供回调地址，使用当前页面
        if (!$redirectUri) {
            $redirectUri = request()->url();
        }

        // 检查是否有 code 参数（授权回调）
        $code = request('code');
        if ($code) {
            // 通过 code 获取 openid
            $result = $this->getOpenidByCode($code);
            
            if (isset($result['openid'])) {
                // 缓存 openid（有效期 2 小时）
                $cacheKey = 'wechat_openid_' . md5($result['openid']);
                Cache::put($cacheKey, $result['openid'], 7200);
                
                // 记录到 session
                Session::put('wechat_openid', $result['openid']);
                
                return $result['openid'];
            } else {
                Log::error('微信获取 openid 失败', $result);
                return ['error' => 'get_openid_failed', 'message' => '获取 openid 失败', 'result' => $result];
            }
        }

        // 检查是否已有缓存的 openid
        $cachedOpenid = Session::get('wechat_openid');
        if ($cachedOpenid) {
            return $cachedOpenid;
        }

        // 构造授权链接
        $authUrl = $this->getAuthUrl($redirectUri, $state);
        return ['auth_url' => $authUrl, 'message' => '需要微信授权'];
    }

    /**
     * 获取缓存的 openid
     * @return string|null
     */
    public function getCachedOpenid()
    {
        return Session::get('wechat_openid');
    }

    /**
     * 清除缓存的 openid
     */
    public function clearCachedOpenid()
    {
        Session::forget('wechat_openid');
    }

    /**
     * 检查并获取 openid（简化版）
     * @return string|array
     */
    public function checkAndGetOpenid()
    {
        // 先检查是否有缓存的 openid
        $openid = $this->getCachedOpenid();
        if ($openid) {
            return $openid;
        }

        // 检查是否有 code 参数
        $code = request('code');
        if ($code) {
            $result = $this->getOpenidByCode($code);
            if (isset($result['openid'])) {
                Session::put('wechat_openid', $result['openid']);
                return $result['openid'];
            }
        }

        // 需要授权
        return ['need_auth' => true, 'auth_url' => $this->getAuthUrl('https://juan.ai-book.top/api/wechat/callback')];
    }

}