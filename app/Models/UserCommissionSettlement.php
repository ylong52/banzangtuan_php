<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCommissionSettlement extends Model
{
  

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'user_commission_settlement';

    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id',             // 用户ID
        'bill_no',             // 账单编号
        'after_tax_income',    // 税后收入
        'general_commission',  // 普通佣金
        'reward_commission',   // 奖励佣金
        'pre_tax_amount',      // 税前金额
        'total_tax',           // 总税额
        'is_paid',             // 是否已打款
        'remark'               // 备注
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at' 
    ];

    /**
     * 属性转换
     *
     * @var array
     */
    protected $casts = [
        'after_tax_income' => 'decimal:2',
        'general_commission' => 'decimal:2',
        'reward_commission' => 'decimal:2',
        'pre_tax_amount' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'is_paid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * 获取用户关联
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 获取是否已打款的状态文本
     *
     * @return string
     */
    public function getIsPaidTextAttribute(): string
    {
        return $this->is_paid ? '已打款' : '未打款';
    }

    /**
     * 获取是否已打款的状态标签
     *
     * @return string
     */
    public function getIsPaidLabelAttribute(): string
    {
        if ($this->is_paid) {
            return '<span class="label label-success">已打款</span>';
        }
        return '<span class="label label-warning">未打款</span>';
    }

    /**
     * 获取税后收益的格式化显示
     *
     * @return string
     */
    public function getAfterTaxIncomeFormattedAttribute(): string
    {
        return '¥' . number_format($this->after_tax_income, 2);
    }

    /**
     * 获取通用佣金的格式化显示
     *
     * @return string
     */
    public function getGeneralCommissionFormattedAttribute(): string
    {
        return '¥' . number_format($this->general_commission, 2);
    }

    /**
     * 获取奖励佣金的格式化显示
     *
     * @return string
     */
    public function getRewardCommissionFormattedAttribute(): string
    {
        return '¥' . number_format($this->reward_commission, 2);
    }

    /**
     * 获取税前金额的格式化显示
     *
     * @return string
     */
    public function getPreTaxAmountFormattedAttribute(): string
    {
        return '¥' . number_format($this->pre_tax_amount, 2);
    }

    /**
     * 获取综合税金的格式化显示
     *
     * @return string
     */
    public function getTotalTaxFormattedAttribute(): string
    {
        return '¥' . number_format($this->total_tax, 2);
    }

    /**
     * 获取总佣金（通用佣金 + 奖励佣金）
     *
     * @return float
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->general_commission + $this->reward_commission;
    }

    /**
     * 获取总佣金的格式化显示
     *
     * @return string
     */
    public function getTotalCommissionFormattedAttribute(): string
    {
        return '¥' . number_format($this->total_commission, 2);
    }

    /**
     * 范围查询：已打款
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * 范围查询：未打款
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * 范围查询：按账单编号
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $billNo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBillNo($query, $billNo)
    {
        return $query->where('bill_no', $billNo);
    }

    /**
     * 范围查询：按用户ID
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 范围查询：按时间范围
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 获取创建时间的格式化显示
     *
     * @return string
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : '';
    }

    /**
     * 获取更新时间的格式化显示
     *
     * @return string
     */
    public function getUpdatedAtFormattedAttribute(): string
    {
        return $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : '';
    }

    /**
     * 获取创建时间的日期显示（仅日期）
     *
     * @return string
     */
    public function getCreatedDateAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y-m-d') : '';
    }

    /**
     * 获取创建时间的时间显示（仅时间）
     *
     * @return string
     */
    public function getCreatedTimeAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('H:i:s') : '';
    }

    /**
     * 获取创建时间的友好显示（如：2小时前、3天前等）
     *
     * @return string
     */
    public function getCreatedAtDiffForHumansAttribute(): string
    {
        return $this->created_at ? $this->created_at->diffForHumans() : '';
    }

    /**
     * 获取更新时间的友好显示（如：2小时前、3天前等）
     *
     * @return string
     */
    public function getUpdatedAtDiffForHumansAttribute(): string
    {
        return $this->updated_at ? $this->updated_at->diffForHumans() : '';
    }

    /**
     * 获取创建时间的年月显示（如：2025年2月）
     *
     * @return string
     */
    public function getCreatedYearMonthAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('Y年n月') : '';
    }

    /**
     * 获取创建时间的完整中文显示（如：2025年2月15日 14时30分）
     *
     * @return string
     */
    public function getCreatedAtChineseAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }
        
        $year = $this->created_at->format('Y');
        $month = $this->created_at->format('n');
        $day = $this->created_at->format('j');
        $hour = $this->created_at->format('G');
        $minute = $this->created_at->format('i');
        
        return "{$year}年{$month}月{$day}日 {$hour}时{$minute}分";
    }
}