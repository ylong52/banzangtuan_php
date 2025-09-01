<?php
namespace App\Providers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use App\Listeners\RedisKeyExpiredListener;

class RedisEventServiceProvider extends ServiceProvider
{
/**
* 注册服务提供者。
*
* @return void
*/
    public function register()
    {
    //
    }

    /**
    * 启动服务提供者。
    *
    * @return void
    */
    public function boot()
    {
        $channel = '__keyevent@0__:expired'; // 0 是 Redis 数据库编号
        Redis::subscribe([$channel], function ($message) {
        $listener = new RedisKeyExpiredListener();
        $listener->handle($message);
        });
    }

}

