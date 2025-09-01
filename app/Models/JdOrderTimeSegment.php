<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JdOrderTimeSegment extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'jdorder_time_segments';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        // 主键ID
        'id',
        // 时段唯一标识（如20250830_0000_0059）
        'segment_id',
        // 时段起始时间
        'start_time',
        // 时段结束时间
        'end_time',
        // 时段状态：0=待生成,1=待查询,2=查询中,3=已完成,4=失败
        'status',
        // 当前查询页码
        'current_page',
        // 重试次数
        'retry_count',
        // 最后查询时间
        'last_query_time',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除标记（NULL=未删除，有值=已删除）
        'deleted_at'
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

 
}
