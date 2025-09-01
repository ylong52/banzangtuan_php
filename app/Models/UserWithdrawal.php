<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWithdrawal extends Model
{
    use SoftDeletes;

    protected $table = 'user_withdrawal_records';

    protected $fillable = [
        'user_id',                // 用户ID
        'withdrawal_no',          // 提现单号
        'third_party_order_no',   // 第三方支付订单号
        'amount',                 // 提现金额
        'handling_fee',           // 手续费
        'actual_amount',          // 实际到账金额
        'withdrawal_type',      // 提现方式:'1-微信,2-支付宝',
        'withdrawal_alipay_account', // 支付宝账号
        'withdrawal_bank_name',    // 开户行名称
        'withdrawal_bank_account', // 开户行账号
        'withdrawal_status',      // 提现状态:0-待处理,1-已到账,2-失败
        'withdrawal_time',        // 提现申请时间
        'arrival_time',           // 到账时间
        'created_at',             // 创建时间
        'updated_at',             // 更新时间
        'deleted_at',             // 删除时间
        'pay_account_name',       // 收款人姓名
        'pay_account_number',     // 收款人账号
        'wx_package_info',        // 微信支付包信息
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'handling_fee' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'withdrawal_time' => 'datetime',
        'arrival_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // 提现状态映射
    public static $statusMap = [
        0 => '待处理',
        1 => '已到账',
        2 => '失败',
    ];

    // 提现方式映射
    public static $methodMap = [
        1 => '银行卡',
        2 => '支付宝',
        // 可扩展
    ];

    // 状态文本访问器
    public function getWithdrawalStatusTextAttribute()
    {
        return self::$statusMap[$this->withdrawal_status] ?? '未知状态';
    }

    // 提现方式文本访问器
    public function getWithdrawalMethodTextAttribute()
    {
        return self::$methodMap[$this->withdrawal_method] ?? '未知方式';
    }

    // 用户关联
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 时间格式化访问器
    public function getWithdrawalTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }

    public function getArrivalTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }

    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }

    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
    }
}