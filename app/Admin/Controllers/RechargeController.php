<?php
namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use App\Models\RechargeRecord;


class RechargeController extends AdminController
{
    protected $title = '充值管理';

    protected function grid()
    {
        $grid = new Grid(new RechargeRecord());

        // 关联查询
        $grid->model()->with(['user']);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('order_no', __('订单号'))->sortable();
        $grid->column('third_party_order_no', __('第三方订单号'));
        
        // 显示用户名
        $grid->column('user.username', __('用户名'));
        
        $grid->column('amount', __('充值金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $grid->column('payment_method', __('支付方式'))->display(function ($value) {
            switch ($value) {
                case 1:
                    return '<span class="label label-success">微信</span>';
                case 2:
                    return '<span class="label label-primary">支付宝</span>';
                case 3:
                    return '<span class="label label-info">银行卡</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        
        $grid->column('status', __('状态'))->display(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">待支付</span>';
                case 1:
                    return '<span class="label label-success">支付成功</span>';
                case 2:
                    return '<span class="label label-danger">支付失败</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        
        $grid->column('payment_time', __('支付时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();
        
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();

        // 搜索设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 订单号查询
            $filter->like('order_no', '订单号');
            
            // 用户名查询
            $filter->where(function ($query) {
                $input = request('用户名');
                if ($input) {
                    $query->whereHas('user', function ($q) use ($input) {
                        $q->where('username', 'like', "%{$input}%");
                    });
                }
            }, '用户名');
            
            // 支付方式查询
            $filter->equal('payment_method', '支付方式')->select([
                1 => '微信',
                2 => '支付宝',
                3 => '银行卡'
            ]);
            
            // 状态查询
            $filter->equal('status', '状态')->select([
                0 => '待支付',
                1 => '支付成功',
                2 => '支付失败'
            ]);
            
            // 支付时间范围查询
            $filter->between('payment_time', '支付时间')->datetime();
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

        // 添加统计行
        $grid->footer(function ($collection) {
            // 获取当前筛选条件
            $query = RechargeRecord::query();
            
            // 应用筛选条件
            if (request('order_no')) {
                $query->where('order_no', 'like', '%' . request('order_no') . '%');
            }
            
            if (request('用户名')) {
                $query->whereHas('user', function ($q) {
                    $q->where('username', 'like', '%' . request('用户名') . '%');
                });
            }
            
            if (request('payment_method') !== null && request('payment_method') !== '') {
                $query->where('payment_method', request('payment_method'));
            }
            
            if (request('status') !== null && request('status') !== '') {
                $query->where('status', request('status'));
            }
            
            if (request('payment_time.start') && request('payment_time.end')) {
                $query->whereBetween('payment_time', [request('payment_time.start'), request('payment_time.end')]);
            }
       
            // 获取统计数据 - 使用更高效的方式
            $totalCount = (clone $query)->count();
            $totalAmount = (clone $query)->sum('amount');
            
            // 分别获取各状态的统计
            $successQuery = (clone $query)->where('status', 1);
            $successCount = $successQuery->count();
            $successAmount = $successQuery->sum('amount');
            
            $pendingQuery = (clone $query)->where('status', 0);
            $pendingCount = $pendingQuery->count();
            $pendingAmount = $pendingQuery->sum('amount');
            
            $failedQuery = (clone $query)->where('status', 2);
            $failedCount = $failedQuery->count();
            $failedAmount = $failedQuery->sum('amount');
            
            return "
            <div style='padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-top: 10px;'>
                <strong>统计信息：</strong>
                <span style='margin-left: 20px; color: #28a745;'>支付成功: {$successCount}笔 ¥" . number_format($successAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #ffc107;'>待支付: {$pendingCount}笔 ¥" . number_format($pendingAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #dc3545;'>支付失败: {$failedCount}笔 ¥" . number_format($failedAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #007bff; font-weight: bold;'>总计: {$totalCount}笔 ¥" . number_format($totalAmount, 2) . "</span>
            </div>";
        });

        return $grid;
    }

    public function detail($id)
    {
        $show = new Show(RechargeRecord::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('order_no', __('订单号'));
        $show->field('third_party_order_no', __('第三方订单号'));
        $show->field('user.username', __('用户名'));
        $show->field('amount', __('充值金额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('payment_method', __('支付方式'))->as(function ($value) {
            switch ($value) {
                case 1:
                    return '<span class="label label-success">微信</span>';
                case 2:
                    return '<span class="label label-primary">支付宝</span>';
                case 3:
                    return '<span class="label label-info">银行卡</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('status', __('状态'))->as(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">待支付</span>';
                case 1:
                    return '<span class="label label-success">支付成功</span>';
                case 2:
                    return '<span class="label label-danger">支付失败</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('payment_time', __('支付时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        });
        $show->field('created_at', __('创建时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('updated_at', __('更新时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        // 禁用编辑、删除、列表按钮
        $show->panel()->tools(function ($tools) {
            $tools->disableEdit();
            $tools->disableDelete();
            $tools->disableList();
        });
        
        return $show;
    }
}