<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
 
use App\Models\DynamicProperty;
use App\Models\User;
use App\Models\Products;

class DynamicPropertyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '支付管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DynamicProperty);
        
        // 禁用默认的操作按钮
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableBatchActions();
        $grid->disableRowSelector();
        
        // 自定义视图
        $grid->view('admin.dynamic_property.config');
        
        return $grid;
    }

        /**
     * 显示配置页面
     */
    public function index(Content $content)
    {
        //加入翻译
        $typeTags_cn = [
            'wechat_official' => '微信公众号(提现)',
            'alipay_official' => '支付宝(提现)',
            'feixpay-wechat' => '飞信支付-微信(入帐)',
            'feixpay-alipy' => '飞信支付-支付宝(入帐)',
        ];

 
        // 获取所有配置数据，按sort字段 排序
        $typeTags = DynamicProperty::select('type_tag')
            ->whereNull('deleted_at')
            ->groupBy('type_tag')
            ->orderByRaw('(SELECT MAX(sort) FROM dynamic_properties dp2 WHERE dp2.type_tag = dynamic_properties.type_tag) asc')
            ->get()
            ->pluck('type_tag')
            ->toArray();
 

        $data = [];
        foreach($typeTags as $typeTag){
            $data[$typeTag] = $this->getConfigDetails($typeTag);
        }
 
        return $content
            ->header('动态配置管理')
            ->description('系统配置管理')
            ->body(view('admin.dynamic_property.index', compact('data','typeTags_cn')));
    }

    /**
     * 获取配置组数据
     */
    private function getConfigGroup($group)
    {
        $configs = DynamicProperty::where('type_tag', $group)->get();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->prop_key] = $config->prop_value;
        }
        
        return $result;
    }

    /**
     * 获取配置组详细数据
     */
    private function getConfigDetails($group)
    {
        $configs = DynamicProperty::where('type_tag', $group)->orderBy('sort', 'asc')->get();
        $result = [];
        
        foreach ($configs as $config) {
            $result[] = [
                'prop_key' => $config->prop_key,
                'prop_value' => $config->prop_value,
                'value_type' => $config->value_type ?? 'input',
                'description' => $config->description,
                'name' => $config->name,
            ];
        }
        
        return $result;
    }

    /**
     * 保存配置
     */
    public function save()
    {
        try {
            $data = request()->all();
 
            $updateCount = 0;
   
            $type_tag = array_keys($data)[0];
     
            $updates = $data[$type_tag];
        
            // // 验证必输字段
            // foreach ($updates as $key => $value) {
            //     // 检查值是否为空
            //     if (empty($value) && $value !== '0') {
            //         return response()->json([
            //             'status' => false,
            //             'message' => "配置项 '{$key}' 不能为空，请填写后重试！"
            //         ], 400);
            //     }
            // }
            
            foreach ($updates as $key => $value) {                                         
                // 如果记录不存在，创建新记录
                DynamicProperty::where(
                    ['prop_key'=>$key,'type_tag'=>$type_tag]
                )->update(
                    [
                        'prop_value' => $value, 
                        'updated_at' => now()
                    ]
                );
                $updateCount++;                    
            }
                 
            
            
            $message = "配置保存成功！更新了 {$updateCount} 条记录";
            
            
            return response()->json([
                'status' => true,
                'message' => $message,
                'updated' => $updateCount 
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '保存失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取配置描述
     */
    private function getConfigDescription($group, $key)
    {
        $descriptions = [
            'wechat' => [
                'appid' => '微信公众号AppID',
                'appsecret' => '微信公众号AppSecret',
                'token' => '微信公众号Token',
                'encoding_aes_key' => '微信公众号EncodingAESKey',
                'webhook_url' => '微信公众号Webhook URL'
            ],
            'payment' => [
                'api_key' => '支付API密钥',
                'merchant_id' => '支付商户ID',
                'types' => '支付类型',
                'wechat_mchid' => '微信支付商户号',
                'alipay_app_id' => '支付宝应用ID',
                'notify_url' => '支付回调地址'
            ]
        ];
        
        return $descriptions[$group][$key] ?? '';
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(DynamicProperty::findOrFail($id));

        $show->field('name', '配置名称');
        $show->field('type_tag', '配置组');
        $show->field('prop_key', '配置键');
        $show->field('prop_value', '配置值');
        $show->field('value_type', '值类型');
        $show->field('description', '描述');
        $show->field('sort', '排序');
        $show->field('updated_at', __('更新时间'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new DynamicProperty);

        $form->text('name', '配置名称')->required();
        
        $form->select('type_tag', '配置组')->options([
            'wechat' => '微信配置',
            'payment' => '支付配置',
            'system' => '系统配置',
        ])->required();
        
        $form->text('prop_key', '配置键')->required();
        $form->textarea('prop_value', '配置值')->required();
        
        $form->select('value_type', '值类型')->options([
            'string' => '字符串',
            'int' => '整数',
            'float' => '浮点数',
            'bool' => '布尔值',
            'json' => 'JSON',
            'array' => '数组',
            'date' => '日期',
        ])->default('string');
        
        $form->text('description', '描述');
        $form->number('sort', '排序')->default(0);

        return $form;
    }
}