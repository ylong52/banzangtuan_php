<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AyqyOrders extends Model
{
    // 表名
    protected $table = 'ayqy_orders';

    // 主键类型
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // 自动维护时间戳
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    // 可批量赋值字段
    protected $fillable = [
        'order_number',
        'user_id',
        'product_id',
        'item_price', //对应产品价格price
        'buynumber',
        'account',
        'should_amount',
        'real_amount',
        'status',
        'msg',
        'created_at',
        'updated_at',
    ];

    // 字段注释（仅供参考，实际注释在数据库）
    // id: 主键ID
    // order_number: 订单号
    // user_id: 用户ID
    // product_id: 产品ID
    // buy_number: 购买数量
    // should_amount: 应付金额
    // real_amount: 实付金额
    // status: 订单状态,1表示成功
    // created_at: 创建时间
    // updated_at: 更新时间
    
    /**
     * 关联产品表
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'id');
    }

    /**
     * 关联用户表
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
