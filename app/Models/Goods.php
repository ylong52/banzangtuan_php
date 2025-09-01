<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goods extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'goods';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'goodsname',
        'imagelist',
        'priceinfo',
        'shopinfo',
        'skutaglist',
        'keyword',
        'json',
        'share_copywriting',
        'white_image',
        'commission_info'
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
        'imagelist' => 'array',
        'priceinfo' => 'array',
        'shopinfo' => 'array',
        'skutaglist' => 'array',
        'json' => 'array'
    ];
}
