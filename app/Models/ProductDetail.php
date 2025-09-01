<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    protected $table = 'product_detail';
    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
         'number',
         'name',
         'price',
         'money',
         'type',
         'day',
         'min',
         'max',
         'note',
         'desc',
         'describe',
         'accountName',
         'accountType',
         'accountType1',
         'accountDesc',
         'accountContent',
         'accountRequired',
         'count',
         'TagName',
         'TagColor',
         'imgs',
         'discounts',
         'templates',
         'mainKey',
         'status',
         'multiple',
         'isRepeat',
         'skuType',
         'skus',
         'skuDetails',
         'specs',
         'brands',
         'created_at',
         'updated_at',
    ];
  
    //accountType字段如何写getAttribute  
    public function getAccountTypeDisplayNameAttribute(){
        //1-文本输入框；4-下拉菜单；5-多行文本；6-数字类型；7-累乘类型；8-图片类型
        dd(__LINE__);
        switch($value){
            case 1:
                return [
                    'name' => '文本输入框',
                    'accountType' => 1,
                ];
            case 4:
                return [
                    'name' => '下拉菜单',
                    'accountType' => 4,
                ];
            case 5:
                return [
                    'name' => '多行文本',
                    'accountType' => 5,
                ];
            case 6:
                return [
                    'name' => '数字类型',
                    'accountType' => 6,
                ];
            case 7:
                return [
                    'name' => '累乘类型',
                    'accountType' => 7,
                ];
            case 8:
                return [
                    'name' => '图片类型',
                    'accountType' => 8,
                ];
        }
        return $value;

    }

    public function getAccountRequiredAttribute($value){
        if($value == 1){
            return '是';
        }else{
            return '否';
        }
    }

    public function getSkuTypeAttribute($value){
        //0-单规格；1-多规格；2-多单规格
        switch($value){
            case 0:
                return '单规格';
            case 1:
                return '多规格';
            case 2:
                return '多单规格';
        }
        return $value;
    }

    public function getStatusAttribute($value){
      
        switch($value){
            case 1:
                return '销售';
            case 2:
                return '暂停';
            case 3:
                return '禁售';
        }
    }

    public function getTypeAttribute($value){
      #1-卡券；3-直充
        switch($value){
            case 1:
                return '卡券';
            case 3:
                return '直充';
        }
    }

    public function product(){
        return $this->belongsTo(Products::class, 'id', 'id');
    }
} 