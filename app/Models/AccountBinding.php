<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBinding extends Model
{
    // 表名
    protected $table = 'account_binding';

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
        'user_id',                    // 用户唯一标识（如用户系统的ID）
        'wx_openid',                  // 微信openid
        'wx_real_name',               // 微信真实姓名
        'alipay_account_number',      // 支付宝账号
        'alipay_real_name',                  // 真实姓名
        'use_accounttype',            // 1-支付宝，2-微信
        'created_at',                 // 创建时间
        'updated_at',                 // 更新时间
    ];

    /**
     * 关联用户表
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取账户类型文本
     */
    public function getAccountTypeTextAttribute()
    {
        $types = [
            1 => '支付宝',
            2 => '微信'
        ];
        return $types[$this->use_accounttype] ?? '未知';
    }

    /**
     * 获取脱敏的支付宝账号
     */
    public function getMaskedAlipayAccountAttribute()
    {
        if (!$this->alipay_account_number) {
            return '';
        }
        
        $account = $this->alipay_account_number;
        $length = strlen($account);
        
        if ($length <= 4) {
            return $account;
        }
        
        return substr($account, 0, 2) . str_repeat('*', $length - 4) . substr($account, -2);
    }

    /**
     * 获取脱敏的微信openid
     */
    public function getMaskedWxOpenidAttribute()
    {
        if (!$this->wx_openid) {
            return '';
        }
        
        $openid = $this->wx_openid;
        $length = strlen($openid);
        
        if ($length <= 6) {
            return $openid;
        }
        
        return substr($openid, 0, 3) . str_repeat('*', $length - 6) . substr($openid, -3);
    }
}
