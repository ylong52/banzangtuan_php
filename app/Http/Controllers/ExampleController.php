<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ExampleController extends Controller
{
    /**
     * 示例1：使用 config() 函数获取配置
     */
    public function getConfigExample()
    {
        // 获取单个配置项
        $freeWithdrawalLimit = config('global_config.free_withdrawal_limit');
        
        // 获取所有全局配置
        $allConfigs = config('global_config');
        
        // 带默认值的获取
        $siteName = config('global_config.site_name', '默认网站名');
        
        return response()->json([
            'free_withdrawal_limit' => $freeWithdrawalLimit,
            'all_configs' => $allConfigs,
            'site_name' => $siteName
        ]);
    }
    
    /**
     * 示例2：使用 app() 函数获取配置
     */
    public function getConfigWithApp()
    {
        $globalConfig = app('global_config');
        
        $freeLimit = $globalConfig['free_withdrawal_limit'] ?? 50;
        $siteName = $globalConfig['site_name'] ?? '默认名称';
        
        return response()->json([
            'free_limit' => $freeLimit,
            'site_name' => $siteName
        ]);
    }
    
    /**
     * 示例3：使用 Config 门面
     */
    public function getConfigWithFacade()
    {
        $freeLimit = Config::get('global_config.free_withdrawal_limit');
        $siteName = Config::get('global_config.site_name', '默认名称');
        
        return response()->json([
            'free_limit' => $freeLimit,
            'site_name' => $siteName
        ]);
    }
    
    /**
     * 示例4：在业务逻辑中使用配置
     */
    public function processWithdrawal($amount)
    {
        $freeLimit = config('global_config.free_withdrawal_limit', 50);
        
        if ($amount <= $freeLimit) {
            return response()->json([
                'message' => '免费提现',
                'fee' => 0
            ]);
        } else {
            $fee = ($amount - $freeLimit) * 0.01; // 1% 手续费
            return response()->json([
                'message' => '需要手续费',
                'fee' => $fee
            ]);
        }
    }
    
    /**
     * 示例5：检查配置是否存在
     */
    public function checkConfigExists()
    {
        $configs = [
            'free_withdrawal_limit' => config()->has('global_config.free_withdrawal_limit'),
            'site_name' => config()->has('global_config.site_name'),
            'non_existent' => config()->has('global_config.non_existent')
        ];
        
        return response()->json($configs);
    }
    
    /**
     * 示例6：在 Blade 视图中使用（返回视图）
     */
    public function showConfigView()
    {
        $configs = config('global_config');
        
        return view('example.config', compact('configs'));
    }
}


