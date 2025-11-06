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
use App\Models\Goods;
use App\Models\LotteryPrize;
use App\Services\JdGoodsSevice;
use Illuminate\Http\Request;
/*
* 抽奖管理
*/

class LotteryPrizeController extends AdminController
{
    protected $title = '抽奖管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LotteryPrize());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('prize_name', __('奖品名称'));
        $grid->column('description', __('奖品描述'));
        $grid->column('prize_level', __('奖品等级'))->display(function ($value) {
            $levels = [
                1 => '一等奖',
                2 => '二等奖',
                3 => '三等奖',
                4 => '四等奖',
                5 => '五等奖',
                6 => '六等奖',
                7 => '七等奖',
                8 => '八等奖'
            ];
            return $levels[$value] ?? '未知';
        });
        $grid->column('status', __('状态'))->display(function ($value) {
            return $value == 1 ? 
                '<span class="label label-success">启用</span>' : 
                '<span class="label label-danger">禁用</span>';
        });
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('updated_at', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        // 筛选功能
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            // 奖品名称 like 查询
            $filter->like('prize_name', __('奖品名称'));
            // 状态 select 下拉查询
            $filter->equal('status', __('状态'))->select([
                1 => '启用',
                0 => '禁用'
            ]);
        });

        // 禁用分页
        $grid->disablePagination();
        // 禁用新增按钮
        $grid->disableCreateButton();
        // 禁用导出
        $grid->disableExport();
        // 禁用批量操作
        $grid->disableBatchActions();
        // 禁用行选择器
        $grid->disableRowSelector();
        
        // 启用编辑和删除操作
        $grid->actions(function ($actions) {
            // 默认启用编辑和删除，不需要额外配置
        });

        // 添加备注区
        $grid->footer(function ($collection) {
            return '<div style="padding: 10px; background-color: #f5f5f5; border-top: 1px solid #ddd; margin-top: 10px;">
                <strong>备注：</strong>奖品是7条状态为启用的记录，大于取前7条数据。
            </div>';
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LotteryPrize());

        $form->text('prize_name', __('奖品名称'))->required();
        $form->textarea('description', __('奖品描述'));
        $form->select('prize_level', __('奖品等级'))->options([
            1 => '一等奖',
            2 => '二等奖',
            3 => '三等奖',
            4 => '四等奖',
            5 => '五等奖',
            6 => '六等奖',
            7 => '七等奖',
            8 => '八等奖'
        ])->default(1)->required();
        $form->select('status', __('状态'))->options([
            1 => '启用',
            0 => '禁用'
        ])->default(1)->required();

        return $form;
    }

    protected function detail($id)
    {
        $show = new Show(LotteryPrize::findOrFail($id));
        return $show;
    } 



}