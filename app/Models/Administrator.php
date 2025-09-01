<?php

namespace App\Models;
use Illuminate\Notifications\Notifiable;

class Administrator extends \Encore\Admin\Auth\Database\Administrator
{
    /**
     * 使用通知功能
     * 该 trait 提供了发送通知的方法，例如：
     * - notify() 发送通知
     * - notifications() 获取通知
     * - readNotifications() 获取已读通知
     * - unreadNotifications() 获取未读通知
     */
    use Notifiable;
    
    protected $table = 'admin_users';
    
    protected $fillable = [
        'username',
        'password',
        'name',
        'avatar'
    ];
    
    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    protected $casts = [];
}