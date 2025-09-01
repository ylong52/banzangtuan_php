<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;  

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable ;

    //启动了软删除
    use SoftDeletes;
    public $incrementing = false; // 禁用自动递增
    protected $keyType = 'int'; // 指定主键类型为整型
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'username',
        'phone',
        'email',
        'password',
        'balance',
        'wechat_openid',
        'avatar',
        'bank_real_name',
        'id_card',
        'bank_card',
        'bank_phone'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    
}
