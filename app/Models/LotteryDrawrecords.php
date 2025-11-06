<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotteryDrawrecords extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'lottery_drawrecords';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'order_no',
        'lottery_code',
        'prize_name',
        'lottery_prize_info',
        'prize_level',
        'draw_time',
        'is_won'
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'prize_level' => 'integer',
        'is_won' => 'integer',
        'draw_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * 是否中奖常量
     */
    const IS_WON_NO = 0;      // 未中奖
    const IS_WON_YES = 1;     // 中奖

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
     * 关联用户
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_no', 'order_id');
    }

    /**
     * 关联开奖码
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lotteryCode()
    {
        return $this->belongsTo(LotteryCodes::class, 'lottery_code', 'lottery_code');
    }

    /**
     * 根据用户ID查找
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
     * 根据订单号查找
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $orderNo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOrderNo($query, $orderNo)
    {
        return $query->where('order_no', $orderNo);
    }

    /**
     * 根据开奖码查找
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $lotteryCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLotteryCode($query, $lotteryCode)
    {
        return $query->where('lottery_code', $lotteryCode);
    }

    /**
     * 查找中奖记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWon($query)
    {
        return $query->where('is_won', self::IS_WON_YES);
    }

    /**
     * 查找未中奖记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotWon($query)
    {
        return $query->where('is_won', self::IS_WON_NO);
    }

    /**
     * 按开奖时间排序（降序）
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('draw_time', 'desc');
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
     * 获取是否中奖文本
     *
     * @return string
     */
    public function getIsWonTextAttribute()
    {
        return $this->is_won === self::IS_WON_YES ? '中奖' : '未中奖';
    }

    /**
     * 获取奖品等级文本
     *
     * @return string
     */
    public function getPrizeLevelTextAttribute()
    {
        $levels = [
            self::PRIZE_LEVEL_THANKS => '谢谢参与',
            self::PRIZE_LEVEL_FIRST => '一等奖',
            self::PRIZE_LEVEL_SECOND => '二等奖',
            self::PRIZE_LEVEL_THIRD => '三等奖',
            self::PRIZE_LEVEL_FOURTH => '四等奖',
            self::PRIZE_LEVEL_FIFTH => '五等奖',
            self::PRIZE_LEVEL_SIXTH => '六等奖',
            self::PRIZE_LEVEL_SEVENTH => '七等奖',
            self::PRIZE_LEVEL_EIGHTH => '八等奖'
        ];
        return $levels[$this->prize_level] ?? '未知';
    }

    /**
     * 获取开奖时间格式化
     *
     * @param mixed $value
     * @return string|null
     */
    public function getDrawTimeAttribute($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
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
