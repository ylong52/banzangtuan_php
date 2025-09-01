<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class DynamicProperty extends Model
{
    use SoftDeletes;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'dynamic_properties';

    /**
     * 主键字段名
     * 
     * 注意：该表没有设置自增ID作为主键，使用默认的主键行为
     * @var string
     */
    protected $primaryKey = null;

    /**
     * 是否自增主键
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 时间戳格式
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 可以批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'prop_key',
        'prop_value',
        'value_type',
        'description',
        'type_tag',
        'sort',
    ];

    /**
     * 不需要自动维护时间戳
     * 因为表中只有 updated_at 字段，没有 created_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 软删除字段
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'sort' => 'integer',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * 根据类型标签查询
     *
     * @param  Builder $query
     * @param  string $typeTag
     * @return Builder
     */
    public function scopeTypeTag(Builder $query, string $typeTag): Builder
    {
        return $query->where('type_tag', $typeTag);
    }

    /**
     * 根据属性键名查询
     *
     * @param  Builder $query
     * @param  string $propKey
     * @return Builder
     */
    public function scopePropKey(Builder $query, string $propKey): Builder
    {
        return $query->where('prop_key', $propKey);
    }

    /**
     * 根据主体名称查询
     *
     * @param  Builder $query
     * @param  string $name
     * @return Builder
     */
    public function scopeName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    /**
     * 按排序字段升序排列
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort', 'asc');
    }

    /**
     * 获取转换后的值（根据 value_type）
     *
     * @return mixed
     */
    public function getConvertedValueAttribute()
    {
        $value = $this->prop_value;
        
        switch ($this->value_type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'array':
                return explode(',', $value);
            case 'date':
                return \Carbon\Carbon::parse($value);
            case 'textare':

            default:
                return $value;
        }
    }

    //翻译type_tag为中文，如：type_tag=wechat_official，type_tag_txt = "官方微信入帐"
    public function getTypeTagTxtAttribute()
    {
        $map = [
            'wechat_official' => '官方微信公众号',
            'feixpay-wechat' => 'feixpay微信第三方支付站(入帐)',
            'feixpay-alipy' => 'feixpay支付宝第三方支付站(入帐)',
            // 可以根据实际情况继续添加映射
        ];

        return $map[$this->type_tag] ?? $this->type_tag;
    }
}
    