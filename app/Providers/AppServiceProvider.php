<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 设置缓存驱动为array以避免文件权限问题
        Config::set('cache.default', 'array');
        
        Carbon::macro('toJSON', function () {
            return $this->format('Y-m-d H:i:s');
        });
    }
}
