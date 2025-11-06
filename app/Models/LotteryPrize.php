<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryPrize extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'lottery_prize';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'prize_name',
        'description',
        'prize_level',
        'status'
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'prize_level' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 奖品等级常量
     */
    const PRIZE_LEVEL_FIRST = 1;       // 一等奖
    const PRIZE_LEVEL_SECOND = 2;      // 二等奖
    const PRIZE_LEVEL_THIRD = 3;       // 三等奖
    const PRIZE_LEVEL_FOURTH = 4;      // 四等奖
    const PRIZE_LEVEL_FIFTH = 5;       // 五等奖
    const PRIZE_LEVEL_SIXTH = 6;       // 六等奖
    const PRIZE_LEVEL_SEVENTH = 7;     // 七等奖
    const PRIZE_LEVEL_EIGHTH = 8;      // 八等奖

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;         // 禁用
    const STATUS_ENABLED = 1;          // 启用

    /**
     * 根据奖品等级查找
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPrizeLevel($query, $level)
    {
        return $query->where('prize_level', $level);
    }

    /**
     * 查找启用的奖品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 查找禁用的奖品
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', self::STATUS_DISABLED);
    }

    /**
     * 根据奖品名称查找
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByName($query, $name)
    {
        return $query->where('prize_name', 'like', '%' . $name . '%');
    }

     
    /**
     * 获取状态文本
     *
     * @return string
     */
    public function getStatusTextAttribute()
    {
        return $this->status === self::STATUS_ENABLED ? '启用' : '禁用';
    }

    /**
     * 获取创建时间格式化
     *
     * @param mixed $value
     * @return string|null
     */
    public function getCreatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    /**
     * 获取更新时间格式化
     *
     * @param mixed $value
     * @return string|null
     */
    public function getUpdatedAtAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
}
