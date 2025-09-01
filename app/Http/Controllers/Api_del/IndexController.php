<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiCommand;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use GeoIp2\WebService\Client;

class IndexController extends ApiCommand
{
    protected $noNeedLogin = ['*']; // *表示所有的无需验证

    public function checkToken(Request $request): bool
    {
        try {
            parent::__checkToken();
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('Token无效');
        }
        
    }

    

}
