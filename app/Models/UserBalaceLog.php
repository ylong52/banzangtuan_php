<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBalaceLog extends Model
{
    // 表名
    protected $table = 'user_balance_log';

    // 主键
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // 自动维护 created_at 字段（无 updated_at）
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    // 可批量赋值字段
    protected $fillable = [
        'user_id',
        'order_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'transaction_no',
        'remark',
        'created_at',
    ];

    // 字段注释（仅供参考，实际注释在数据库）
    // id: 主键ID
    // user_id: 用户ID
    // order_id: 关联订单ID
    // transaction_type: 交易类型(1:支付 2:充值 3:退款 4:其他)
    // amount: 变动金额
    // balance_before: 变动前余额
    // balance_after: 变动后余额
    // transaction_no: 交易流水号
    // remark: 备注
    // created_at: 创建时间
}