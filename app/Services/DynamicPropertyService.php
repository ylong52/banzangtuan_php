<?php

namespace App\Services;

use App\Models\DynamicProperty;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use stdClass;

class DynamicPropertyService
{
    /**
     * 根据type_tag和prop_key获取记录对象（支持属性访问）
     *
     * @param string $typeTag
     * @param string $propKey
     * @return stdClass
     * @throws ModelNotFoundException
     */
    /*
    // 1,获取单条记录的完整信息
    $appId = DynamicPropertyService::getProperty('feixpay-wechat', 'app_id');

    // 访问各属性
    $typeTag = $appId->type_tag;      // 获取type_tag值
    $value = $appId->prop_value;      // 获取prop_value值
    $valueType = $appId->value_type;  // 获取value_type值
    $convertedValue = $appId->converted_value;  // 获取转换后的值
    */
    public static function getProperty(string $typeTag, string $propKey): stdClass
    {
        // 获取数据库记录
        $record = DynamicProperty::typeTag($typeTag)
            ->propKey($propKey)
            ->firstOrFail();
        
        // 创建可访问的对象
        $property = new stdClass();
        $property->type_tag = $record->type_tag;
        $property->prop_key = $record->prop_key;
        $property->prop_value = $record->prop_value;
        $property->value_type = $record->value_type;
        $property->description = $record->description;
        $property->name = $record->name;
        
        return $property;
    }

 
    /*
        // 2，获取某类型下的所有属性，返回数组格式
        $wechatProperties = DynamicPropertyService::getAllByTypeTag('feixpay-wechat');

        // 访问特定属性 - 现在可以直接取值
        $privateKey = $wechatProperties['private_key'];
        $mchChannelId = $wechatProperties['mch_channel_id'];
        $appId = $wechatProperties['app_id'];
    */
    public static function getAllByTypeTag(string $typeTag): array
    {
        $properties = DynamicProperty::typeTag($typeTag)
            ->ordered()
            ->get();
        
        $result = [];
        foreach ($properties as $item) {
            $result[$item->prop_key] = $item->prop_value;
        }        
        return $result;
    }

}
    

