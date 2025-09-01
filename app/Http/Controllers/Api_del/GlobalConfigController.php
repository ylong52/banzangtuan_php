<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiCommand;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GlobalConfig;

class GlobalConfigController extends ApiCommand
{
 
    public function getConfig(Request $request) 
    {
        $config = GlobalConfig::where('status', 1)->get();
        if ($config->isEmpty()) {
            return response()->json(['status' =>'success','message' => '配置不存在','data' => null ]);
        }
        $new = [];
        foreach($config as $item){
            $new[$item->key]  = $item->value;
        }
        return response()->json(['status' =>'success','data' => $new]);

    }

    

}
