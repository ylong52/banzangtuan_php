<?php

namespace App\Providers;

use App\Models\GlobalConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\QueryException;

class GlobalConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('global_config', function () {
            // 只返回一个闭包或空数组，不访问数据库
            return [];
        });
    }

    public function boot()
    {
        try {
            // 直接用 key 作为一级键
            $configs = \App\Models\GlobalConfig::get();
            $result = [];
            foreach ($configs as $config) {
                $value = $config->value;
                switch ($config->type) {
                    case 2:
                        $value = json_decode($value, true);
                        break;
                    case 3:
                        $value = (float)$value;
                        break;
                }
                $result[$config->key] = $value;
            }
            config(['global_config' => $result]);

            GlobalConfig::updated(function () {
                Cache::forget('global_config');
            });

            GlobalConfig::created(function () {
                Cache::forget('global_config');
            });

            GlobalConfig::deleted(function () {
                Cache::forget('global_config');
            });
        } catch (QueryException $e) {
            // 数据库连接失败时，设置空的全局配置
            config(['global_config' => []]);
            // 可以记录日志，但不中断应用启动
            if ($this->app->environment('local')) {
                Log::warning('无法连接到数据库，全局配置未加载: ' . $e->getMessage());
            }
        }
    }
}

/*
在 Laravel 函数中调用全局配置的方法：

1. 使用 config() 函数：
   $freeWithdrawalLimit = config('global_config.free_withdrawal_limit');
   $siteName = config('global_config.site_name');

2. 使用 app() 函数：
   $globalConfig = app('global_config');
   $value = $globalConfig['free_withdrawal_limit'];

3. 使用 Config 门面：
   use Illuminate\Support\Facades\Config;
   $value = Config::get('global_config.free_withdrawal_limit');

4. 在控制器中使用：
   public function someFunction()
   {
       $config = config('global_config');
       $freeLimit = $config['free_withdrawal_limit'] ?? 50;
       return $freeLimit;
   }

5. 在 Blade 模板中使用：
   {{ config('global_config.site_name') }}

6. 带默认值的调用：
   $value = config('global_config.some_key', 'default_value');

7. 检查配置是否存在：
   if (config()->has('global_config.free_withdrawal_limit')) {
       $limit = config('global_config.free_withdrawal_limit');
   }
*/
