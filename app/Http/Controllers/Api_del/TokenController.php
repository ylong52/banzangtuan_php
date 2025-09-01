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



class TokenController extends ApiCommand
{
 
    public function checkToken(Request $request)  // Removed incorrect return type
    {
        try {
            $user = parent::__checkToken();   
            if (intval($user->id)>0) {
                return response()->json(["status"=>"success"],200) ;
            } 
            return response()->json(["status"=>"error","msg"=>"Unauthenticated."],401) ;
        } catch (\Exception $e) {
            $msg = "checkToken:".$e->getMessage()." 行号:".__LINE__." 函数:".__FUNCTION__;
            save_log($msg,"checkToken");
            return response()->json([
                "status"=>"error",
                'message' => 'Unauthenticated.' 
            ], 401) ;
        }        
    }

    

}
