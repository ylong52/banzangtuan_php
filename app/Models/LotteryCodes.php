<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryCodes extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'lottery_codes';

    /**
     * 不需要自动维护时间戳
     * 因为表中没有 created_at 和 updated_at 字段
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'lottery_code',
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
        'status' => 'integer'
    ];

    /**
     * 奖品等级常量
     */
    const PRIZE_LEVEL_THANKS = 0;      // 谢谢参与
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
    const STATUS_ENABLED = 1;          // 有效

    /**
     * 根据抽奖码查找
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $code
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCode($query, $code)
    {
        // return $query->where('lottery_code', $code)->where("status",self::STATUS_ENABLED);

        return $query->where('lottery_code', $code);
    }

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
     * 查找有效的抽奖码
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

     
    /**
     * 获取状态文本
     *
     * @return string
     */
    public function getStatusTextAttribute()
    {
        return $this->status === self::STATUS_ENABLED ? '有效' : '禁用';
    }
}
