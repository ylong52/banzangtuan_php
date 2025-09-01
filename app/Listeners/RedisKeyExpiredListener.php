<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RedisKeyExpiredListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * 处理 Redis 键过期事件。
     *
     * @param  string  $key
     * @return void
     */
    public function handle($event)
    {
        Log::info("Redis key expired: {$key}");
        // 在这里添加你需要执行的业务逻辑
    }
}
