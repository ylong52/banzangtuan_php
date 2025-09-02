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
 
use App\Models\Orders;
 
use App\Models\Products;

class OrdersController extends AdminController
{
    protected $title = '订单管理';

    protected function grid()
    {
        // 方法1: 直接在模型初始化时添加查询条件
        $grid = new Grid(new Orders());
        
        // 可以添加默认的查询条件
        // 例如：只显示今天的订单
        // $grid->model()->whereDate('created_at', date('Y-m-d'));
        
        // 例如：只显示有效的订单
        // $grid->model()->whereIn('valid_code', [16, 17]);
        $request = request();
        //created_at[start]=2025-09-02&created_at[end]=2025-09-02
        if ($request->has('order_time') && $request->get('order_time')) {
            $start = $request->get('order_time')['start'] . ' 00:00:00';
            $end = $request->get('order_time')['end'] . ' 23:59:59';
            $grid->model()->whereBetween('order_time', [$start, $end]);
        }
 
        // 例如：按创建时间倒序排列
        $grid->model()->orderBy('created_at', 'desc');

        // 添加统计信息
        $grid->header(function () {
            // 今天的开始和结束时间
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            
            // 昨天的开始和结束时间
            $yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));
            
            // 最近7天的开始时间
            $sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
            
            // 总统计
            $totalEstimateCosPrice = Orders::query()
                ->whereIn('valid_code', [16,17])
                ->sum('estimate_cos_price');
            $totalOrders = Orders::count();
            
            // 今天的统计
            $todayEstimateCosPrice = Orders::query()
                ->whereIn('valid_code', [16,17])
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->sum('estimate_cos_price');
            $todayOrders = Orders::query()
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count();
            
            // 昨天的统计
            $yesterdayEstimateCosPrice = Orders::query()
                ->whereIn('valid_code', [16,17])
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->sum('estimate_cos_price');
            $yesterdayOrders = Orders::query()
                ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
                ->count();
            
            // 最近7天的统计
            $sevenDaysEstimateCosPrice = Orders::query()
                ->whereIn('valid_code', [16,17])
                ->whereBetween('created_at', [$sevenDaysAgo, $todayEnd])
                ->sum('estimate_cos_price');
            $sevenDaysOrders = Orders::query()
                ->whereBetween('created_at', [$sevenDaysAgo, $todayEnd])
                ->count();
            
            return '<div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> 统计信息</h4>
                <div class="row">
                    <div class="col-md-3">
                        <h5><i class="fa fa-calendar"></i> 总体统计</h5>
                        <p><strong>总预估计有效的佣金额：</strong> ¥' . number_format($totalEstimateCosPrice, 2) . '</p>
                        <p><strong>订单总数：</strong> ' . number_format($totalOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-3">
                        <h5><i class="fa fa-calendar-day"></i> 今日统计</h5>
                        <p><strong>今日预估计有效的佣金额：</strong> ¥' . number_format($todayEstimateCosPrice, 2) . '</p>
                        <p><strong>今日订单数：</strong> ' . number_format($todayOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-3">
                        <h5><i class="fa fa-calendar-minus"></i> 昨日统计</h5>
                        <p><strong>昨日预估计有效的佣金额：</strong> ¥' . number_format($yesterdayEstimateCosPrice, 2) . '</p>
                        <p><strong>昨日订单数：</strong> ' . number_format($yesterdayOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-3">
                        <h5><i class="fa fa-calendar-week"></i> 最近7天统计</h5>
                        <p><strong>7天预估计有效的佣金额：</strong> ¥' . number_format($sevenDaysEstimateCosPrice, 2) . '</p>
                        <p><strong>7天订单数：</strong> ' . number_format($sevenDaysOrders) . ' 笔</p>
                    </div>
                </div>
            </div>';
        });

        $grid->column('id', __('ID'))->sortable();
        $grid->column('order_id', __('订单ID'))->sortable();        
        
        // 显示商品标题
        $grid->column('sku_name', __('商品标题'));
        // 增加 sub_union_id 字段的显示，并支持查询
        $grid->column('sub_union_id', __('sub_union_id推广用户'));
        // 在筛选器中加入 sub_union_id 查询
        $grid->filter(function ($filter) {
            // 其他筛选条件...
            $filter->like('sub_union_id', 'sub_union_id');
        });
        // 设置主图显示尺寸为 80x80，便于后台列表展示
        $grid->column('image_url', __('主图'))->image('', 80, 80);
               
        $grid->column('sku_num', __('数量'));
        $grid->column('price', __('单价'));
        $grid->column('total_price', __('总价')); 
        $grid->column('shop_name', __('店铺名称'));
        $grid->column('commission_rate', __('commission_rate佣金比例'));
        // $grid->column('estimate_cos_price', __('预估计佣金额'));
        $grid->column('estimate_fee', __('estimate_fee预估佣金'));
        $grid->column('actual_fee', __('actual_fee实际佣金'));
        $grid->column('order_time', __('下单时间'));
        // $grid->column('modify_time', __('订单更新时间'));
        $grid->column('finish_time', __('订单完成时间'));
 
        // 根据订单状态 value 显示对应的中文标签
        $grid->column('valid_code', __('订单状态'))->display(function ($value) {
            switch ($value) {
                case -1:
                    return '<span class="label label-info">未知</span>';
                case 3:
                    return '<span class="label label-default">无效-取消</span>';
                case 15:
                    return '<span class="label label-warning">待付款</span>';
                case 16:
                    return '<span class="label label-success">已付款</span>';
                case 17:
                    return '<span class="label label-primary">已完成</span>';
                case 24:
                    return '<span class="label label-info">已付定金</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
 
        $grid->column('updated_at', __('系统更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        // 查询设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();            
            // 商品标题查询
            $filter->like('sku_name', '商品标题');
                                    
            // 订单号查询
            $filter->like('order_id', '订单号');            
            // 订单状态查询
            $filter->equal('valid_code', '订单状态')->select([
                -1 => '未知',
                3 => '无效-取消',
                15 => '待付款',
                16 => '已付款',
                17 => '已完成',
                24 => '已付定金'
            ]);            
            // 时间区间查询
            $filter->between('order_time', '下单时间')->date();
        });

        // 关闭操作按钮
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
        });

        // 关闭批量操作
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    public function detail($id)
    {
        $show = new Show(Orders::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('order_id', __('订单ID'));
        $show->field('sku_name', __('商品标题'));
        $show->field('sub_union_id', __('sub_union_id'));
        $show->field('image_url', __('主图'))->image();
        $show->field('sku_num', __('数量'));
        $show->field('price', __('单价'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('total_price', __('总价'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('shop_name', __('店铺名称'));
        $show->field('commission_rate', __('佣金比例'))->as(function ($value) {
            return $value . '%';
        });
        $show->field('estimate_cos_price', __('预估计佣金额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('estimate_fee', __('预估佣金'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('valid_code', __('订单状态'))->as(function ($value) {
            switch ($value) {
                case -1:
                    return '<span class="label label-info">未知</span>';
                case 3:
                    return '<span class="label label-default">无效-取消</span>';
                case 15:
                    return '<span class="label label-warning">待付款</span>';
                case 16:
                    return '<span class="label label-success">已付款</span>';
                case 17:
                    return '<span class="label label-primary">已完成</span>';
                case 24:
                    return '<span class="label label-info">已付定金</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('order_time', __('下单时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('finish_time', __('订单完成时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('updated_at', __('系统更新时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->panel()->tools(function ($tools) {   
            $tools->disableEdit();
            $tools->disableDelete();
            $tools->disableList();
        });
        return $show;
    }
}
