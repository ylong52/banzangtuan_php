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
/*
* 系统参数管理  
*/

class ProductCategoryController extends AdminController
{
    protected $title = '产品分类管理';

    protected function grid()
    {

        $grid = new Grid(new ProductCategory());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('category', __('类型名称'));
        $grid->column('parent_id', __('父类ID'));
        $grid->column('brands', __('品牌'))->display(function ($value) {
            // 可选：如需美化，可转为数组后implode显示
            return $value ? implode(',', json_decode($value, true)) : '';
        });
        $grid->column('img', __('图片'))->image('', 50, 50); // 显示图片缩略图
        $grid->column('level', __('层级'));
        $grid->column('sort', __('排序权重'))->sortable();
        $grid->column('status', __('状态'))->display(function ($value) {
            return $value == 1 ? '启用' : '禁用';
        });
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('updated_at', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });
        return $grid;
    }

    public function detail($id)
    {
        $show = new Show(ProductCategory::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('category', __('类型名称'));
        $show->field('parent_id', __('父类ID'));
        $show->field('brands', __('品牌'))->as(function ($value) {
            return $value ? implode(',', json_decode($value, true)) : '';
        });
        $show->field('img', __('图片'))->image('', 50, 50); // 显示图片缩略图
        $show->field('level', __('层级'));
        $show->field('sort', __('排序权重'));
        $show->field('status', __('状态'))->as(function ($value) {
            return $value == 1 ? '启用' : '禁用';
        });
        $show->field('created_at', __('创建时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('updated_at', __('更新时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        // 禁用删除按钮
        // 禁用“编辑”、“删除”、“列表”按钮
        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
            $tools->disableList();
        });
        return $show;
    }
}
