<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use Encore\Admin\Facades\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use App\Models\ProductCategory;
use App\Models\Products;
use App\Admin\Actions\Product\SoftDelete;
/*
* 系统参数管理  
*/

class ProductsController extends AdminController
{
    protected $title = '产品管理';

    protected function grid()
    {
        $grid = new Grid(new Products());

        $grid->column('id', __('商品编号'))->sortable();
        $grid->column('name', __('商品名称'));
        $grid->column('price', __('商品面值'))->display(function ($value) {
            return number_format($value, 2, '.', '');
        });
        $grid->column('money', __('购买价格'))->display(function ($value) {
            return number_format($value, 2, '.', '');
        });
        $grid->column('day', __('质保天数'));
        $grid->column('type', __('商品类型'))->using([1 => '卡券', 3 => '直充'], '未知');
        $grid->column('multiple', __('发货倍数'));
        $grid->column('status', __('销售状态'))->using([1 => '销售', 2 => '暂停', 3 => '禁售'], '未知');
        $grid->column('isRepeat', __('重复下单'))->using([0 => '不允许', 1 => '允许'], '未知');
        $grid->column('skuType', __('规格类型'))->using([0 => '单规格', 1 => '多规格', 2 => '多单规格'], '未知');
        $grid->column('dirIds', __('关联分类ID'))->display(function ($value) {
            // 处理JSON数组或单个值
            if (is_string($value)) {
                $arr = json_decode($value, true);
                if (is_array($arr)) {
                    return implode(',', $arr);
                }
            }
            // 如果不是JSON数组，直接返回原值
            return $value;
        });
        $grid->column('imgUrl', __('图片'))->image('', 50, 50);
        $grid->column('isRefOrder', __('是否参考订单'))->using([0 => '否', 1 => '是'], '否');
        $grid->column('keyId', __('KeyID'));
        $grid->column('isRefMoney', __('是否参考金额'))->using([0 => '否', 1 => '是'], '否');
        $grid->column('number', __('库存数量'));
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('updated_at', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('deleted_at', __('软删除'))->display(function ($value) {
            return $value ? '已删除' : '正常';
        });



        $grid->filter(function ($filter) {
            $filter->like('name', '商品名称');
            $filter->equal('type', '商品类型')->select([
                1 => '卡券',
                3 => '直充',
            ]);
            $filter->equal('status', '销售状态')->select([
                1 => '销售',
                2 => '暂停',
                3 => '禁售',
            ]);
            $filter->equal('isRepeat', '重复下单')->select([
                0 => '不允许',
                1 => '允许',
            ]);
            $filter->equal('skuType', '规格类型')->select([
                0 => '单规格',
                1 => '多规格',
                2 => '多单规格',
            ]);
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete(); // 关闭原生删除
            $actions->add(new SoftDelete()); // 添加软删除按钮
            $actions->disableEdit(); // 如需禁用编辑
        });
        return $grid;
    }

    

}