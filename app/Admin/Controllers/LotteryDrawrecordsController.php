<?php

namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Grid\Filter;
use App\Models\LotteryDrawrecords;

class LotteryDrawrecordsController extends AdminController
{
    protected $title = '中奖记录管理';

    protected function grid()
    {
        $grid = new Grid(new LotteryDrawrecords());

        // 预加载用户关联，避免 N+1 查询
        $grid->model()->with('user');

        // 列表展示所有字段
        $grid->column('id', __('ID'))->sortable();
        $grid->column('user_id', __('用户ID'))->sortable();
        $grid->column('user.username', __('用户名'))->display(function ($value) {
            return $value ?: '-';
        });
        $grid->column('user.phone', __('手机号'))->display(function ($value) {
            return $value ?: '-';
        });
        $grid->column('order_no', __('订单号'))->sortable();
        $grid->column('lottery_code', __('开奖码'))->sortable();
        $grid->column('prize_name', __('奖项名称'));
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
        $grid->column('draw_time', __('开奖时间'))->sortable()->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
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
            
            // 1-1 奖品等级 select下拉查询
            $filter->equal('prize_level', __('奖品等级'))->select([
                1 => '一等奖',
                2 => '二等奖',
                3 => '三等奖',
                4 => '四等奖',
                5 => '五等奖',
                6 => '六等奖',
                7 => '七等奖',
                8 => '八等奖'
            ]);
            
            // 1-2 订单号 like查询
            $filter->like('order_no', __('订单号'));
            
            // 1-3 开奖码 绝对查询
            $filter->equal('lottery_code', __('开奖码'));
            
            // 1-4 奖项名称 like查询
            $filter->like('prize_name', __('奖项名称'));
            
            // 1-5 开奖时间 时间区间查询
            $filter->between('draw_time', __('开奖时间'))->datetime();
        });

        // 禁用新增按钮
        $grid->disableCreateButton();
        // 禁用导出
        $grid->disableExport();
        // 禁用批量操作
        $grid->disableBatchActions();
        // 禁用行选择器
        $grid->disableRowSelector();
        // 禁用操作列（编辑、删除）
        $grid->disableActions();
        
        // 启用分页（默认每页20条）
        $grid->paginate(20);
        
        // 默认按开奖时间降序排列
        $grid->model()->orderBy('draw_time', 'desc');

        return $grid;
    }

    
}