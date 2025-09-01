<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Products extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'products';
    protected $primaryKey = 'id';
    public $timestamps = true;
 
    protected $fillable = [
        'price', 'name', 'money', 'day', 'type', 'multiple', 'status', 'isRepeat', 'skuType', 'dirIds', 'imgUrl' ,'isRefOrder','isRefMoney','dirId1','dirId2','dirId3','platform_price'
    ];
    // 'created_at', 'updated_at' 这2个字段要自动更新
    protected $guarded = ['created_at', 'updated_at'];
    
   
    public function getTypeAttribute($value)
    {
      
        if($value == 1) {
            return '卡券';
        } else if($value == 3) {
            return '直充';
        }
        return '未知';
    }

    public function getIsRefOrderAttribute($value)
    {
        return $value == 1 ? '是' : '否';
    }

    public function getIsRefMoneyAttribute($value)
    {
        return $value == 1 ? '是' : '否';
    }

    public function getStatusAttribute($value){
        //销售状态（1-销售/2-暂停/3-禁售） 
        if($value == 1) {
            return '销售';
        } else if($value == 2) {
            return '暂停';
        } else if($value == 3) {
            return '禁售';
        }
    }

    // 与ProductDetail的关系
    public function productDetail()
    {
        return $this->hasOne(ProductDetail::class, 'id', 'id');
    }
} 