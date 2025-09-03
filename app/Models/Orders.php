<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'id',                // 唯一标识，varchar(50)
        'user_id',           // 用户ID，int(11)
        'sub_union_id',      // 子渠道标识，varchar(80)
        'sku_name',          // 标题，varchar(255)
        'order_id',          // 订单号，varchar(30)
        'sku_num',           // 商品数量，int(10)
        'price',             // 单价，decimal(10,2)
        'total_price',       // 总价，decimal(10,2)
        'sku_id',            // SKU ID，varchar(30)
        'valid_code',        // 下单状态，int(5)
        'image_url',         // SKU主图链接，varchar(255)
        'shop_name',         // 店铺名称，varchar(255)
        'commission_rate',   // 佣金比例(%)，decimal(10,2)
        'estimate_cos_price',// 预估计佣金额，decimal(15,2)
        'estimate_fee',      // 推客的预估佣金，decimal(15,2)
        'order_time',        // 下单时间，datetime
        'modify_time',       // 更新时间，datetime
        'finish_time',       // 完成时间，datetime
        'updated_at',        // 修改时间，datetime
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'finish_time',
        'order_time',
        'modify_time',
        'created_at',
        'updated_at', 
    ];

    /**
     * 属性的类型转换
     *
     * @var array
     */
    protected $casts = [
        'commission_rate' => 'float',
        'estimate_cos_price' => 'float',
        'estimate_fee' => 'float',
        'user_id' => 'integer',
        'sku_num' => 'integer',
        'valid_code' => 'integer',
        'price' => 'float',
        'total_price' => 'float',
    ];

    //虚拟字段
    public function getStatusTxtAttribute() {
        return $this->status_txt($this->valid_code);
    }
    
    public function getOrderTimeAttribute($value) {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getFinishTimeAttribute($value) {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    public function getModifyTimeAttribute($value) {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
    
    public function getCreatedAtAttribute($value) {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
 
    public function getUpdatedAtAttribute($value) {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }

    //状态文本方法
    public function status_txt($val = null) {
        $this->validCode = $val !== null ? $val : $this->valid_code;
        $status = "";
        
        switch ($this->validCode) {
            case -1:
                $status = "未知";
                break;
            case 2:
                $status = "无效-拆单";
                break;
            case 3:
                $status = "无效-取消";
                break;
            case 4:
                $status = "无效-京东帮帮主订单";
                break;
            case 5:
                $status = "无效-账号异常";
                break;
            case 6:
                $status = "无效-赠品类目不返佣";
                break;
            case 7:
                $status = "无效-校园订单";
                break;
            case 8:
                $status = "无效-企业订单";
                break;
            case 9:
                $status = "无效-团购订单";
                break;
            case 11:
                $status = "无效-乡村推广员下单";
                break;
            case 13:
                $status = "违规订单-其他";
                break;
            case 14:
                $status = "无效-来源与备案网址不符";
                break;
            case 15:
                $status = "待付款";
                break;
            case 16:
                $status = "已付款";
                break;
            case 17:
                $status = "已完成（购买用户确认收货）";
                break;
            case 19:
                $status = "无效-佣金比例为0";
                break;
            case 20:
                $status = "无效-此复购订单对应的首购订单无效";
                break;
            case 21:
                $status = "无效-云店订单";
                break;
            case 22:
                $status = "无效-PLUS会员佣金比例为0";
                break;
            case 23:
                $status = "无效-支付有礼";
                break;
            case 24:
                $status = "已付定金";
                break;
            case 25:
                $status = "违规订单-流量劫持";
                break;
            case 26:
                $status = "违规订单-流量异常";
                break;
            case 27:
                $status = "违规订单-违反京东平台规则";
                break;
            case 28:
                $status = "违规订单-多笔交易异常";
                break;
            case 29:
                $status = "无效-跨屏跨店";
                break;
            case 30:
                $status = "无效-累计件数超出类目上限";
                break;
            case 31:
                $status = "无效-黑名单sku";
                break;
            case 33:
                $status = "超市卡充值订单";
                break;
            case 34:
                $status = "无效-推卡订单无效";
                break;
            case 35:
                $status = "无效-非CID订单";
                break;
            case 36:
                $status = "违规订单-账户绑定有误";
                break;
            default:
                $status = "未知状态";
                break;
        }
        
        return $status;
    }

    /**
     * 主键是否自增
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * 主键的类型
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * 禁用自动时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 关联用户表
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
