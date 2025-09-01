<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RechargeRecord extends Model
{
    use SoftDeletes;
    
    protected $table = 'recharge_records';
    
    protected $fillable = [
        'user_id', 'order_no', 'third_party_order_no', 'amount',  'total_amount','handling_fee',
        'payment_method', 'status', 'payment_time','errormsg','payment_app_ids'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',        
        'handling_fee'=> 'decimal:2',
        'total_amount'=> 'decimal:2',
        'payment_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'errormsg' => 'string',
    ];
    
    // 状态映射
    public static $statusMap = [
        0 => '待支付',
        1 => '支付成功',
        2 => '支付失败',
    ];
    
    // 支付方式映射
    public static $paymentMethodMap = [
        1 => '微信',
        2 => '支付宝', 
    ];
    
    // 获取状态文本
    public function getStatusTextAttribute()
    {
        return self::$statusMap[$this->status] ?? '未知状态';
    }
    
    // 获取支付方式文本
    public function getPaymentMethodTextAttribute()
    {
        return self::$paymentMethodMap[$this->payment_method] ?? '未知方式';
    }
    
    // 用户关联
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPaymentTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }
    
}