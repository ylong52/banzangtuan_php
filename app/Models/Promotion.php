<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory;

    protected $table = 'promotions';

    // 可批量赋值的字段
    protected $fillable = [
        'user_id',
        'referred_by',
        'referral_code',
        'registration_time',
        'reward_amount',
        'reward_status',
        'reward_time'
    ];
    
    // 状态常量定义
    public const STATUS_PENDING = 0; // 待发放
    public const STATUS_ISSUED = 1;  // 已发放
    public const STATUS_INVALID = 2; // 已失效
    
    // 自动维护时间戳
    public $timestamps = true;
    
    // 关联用户表（被推荐用户）
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // 关联用户表（被推荐用户） - 别名保持兼容性
    public function userInfo()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // 关联推荐人
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
    
    // 关联推荐人 - 别名保持兼容性
    public function referrerInfo()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }
    
    // 状态访问器
    public function getRewardStatusTextAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => '待发放',
            self::STATUS_ISSUED => '已发放',
            self::STATUS_INVALID => '已失效'
        ];
        
        return $statuses[$this->reward_status] ?? '未知状态';
    }
 
    public function getRewardTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
 
    public function getRegistrationTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}