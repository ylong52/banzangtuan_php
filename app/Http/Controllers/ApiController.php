<?php
namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
 
use Illuminate\Http\Request;
 


//加入跨域
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
     
    protected $request;
    protected $user_id = null;
    protected $user_status = 1;
   
    
    public function __construct(Request $request = null)
    {

        $this->request = is_null($request) ? app(Request::class) : $request;
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $this->user_id = $user->id;
            $this->user_status = $user->status;  //0表示禁用，1表示有效
        }
        // 控制器初始化
        $this->_initialize();
        

    }


    public function __checkToken()
    {

        try {       
            // 获取请求头中的token
            $token = $this->request->header('Authorization');            
            if (!$token) {
                // 尝试从GET或POST参数中获取token
                $token = $this->request->input('token');            
            }        
            if (!$token) {
                throw new \Exception("token invalid!!!errorline:83");
            }        
            //判断是否有Bearer  ,没有要加上
            if (strpos($token, 'Bearer') === false) {
                $token = 'Bearer ' . trim($token);
            }
            // 将处理后的token设置回请求头
            $this->request->headers->set('Authorization', $token);
            // 使用Sanctum验证token
            $user = Auth::guard('sanctum')->user();
            return $user;
        } catch (\Exception $e) {
            // 处理异常
            return null;
        }

    }

    /**
     * 控制器初始化方法，需在子类中实现
     */
    protected function _initialize()
    {
        // 可在子类中重写该方法
    }


     
}

