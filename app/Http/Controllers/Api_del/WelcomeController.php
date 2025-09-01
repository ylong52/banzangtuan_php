<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiCommand;
use Illuminate\Routing\Controller as BaseController;

class WelcomeController extends ApiCommand
{
    protected $noNeedLogin = [ ]; // 修改为通配符配置

    public function index(Request $request): string
    {
        try {
            $a =1;
            return $this->success('欢迎访问该测试路由 TestController');
        }catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }

    }


}
