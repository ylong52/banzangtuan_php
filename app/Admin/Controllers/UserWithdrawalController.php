<?php
namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Grid\Filter;
use App\Models\UserWithdrawal;
use App\Models\User;

class UserWithdrawalController extends AdminController
{
    protected $title = '用户提现管理';

    protected function grid()
    {
        $grid = new Grid(new UserWithdrawal());

        // 关联查询
        $grid->model()->with(['user']);
 
        $grid->model()->withTrashed();

        $grid->column('id', __('ID'))->sortable();
        $grid->column('withdrawal_no', __('提现单号'))->sortable();
        $grid->column('third_party_order_no', __('第三方订单号'));
        
        // 显示用户名
        $grid->column('user.username', __('用户名'))->sortable();
        
        $grid->column('amount', __('提现金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $grid->column('handling_fee', __('手续费'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $grid->column('actual_amount', __('实际到账'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $grid->column('withdrawal_method', __('提现方式'))->display(function ($value) {
            switch ($value) {
                case 1:
                    return '<span class="label label-info">银行卡</span>';
                case 2:
                    return '<span class="label label-primary">支付宝</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        
        $grid->column('withdrawal_status', __('提现状态'))->display(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">待处理</span>';
                case 1:
                    return '<span class="label label-success">已到账</span>';
                case 2:
                    return '<span class="label label-danger">失败</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        
        $grid->column('withdrawal_time', __('提现时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();
        $grid->column('deleted_at', __('删除时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();
        $grid->column('arrival_time', __('到账时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();
        
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();

        // 搜索设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 提现单号查询
            $filter->like('withdrawal_no', '提现单号');
            
            // 用户名查询
            $filter->where(function ($query) {
                $input = request('用户名');
                if ($input) {
                    $query->whereHas('user', function ($q) use ($input) {
                        $q->where('username', 'like', "%{$input}%");
                    });
                }
            }, '用户名');
            
            // 提现方式查询
            $filter->equal('withdrawal_method', '提现方式')->select([
                1 => '银行卡',
                2 => '支付宝'
            ]);
            
            // 提现状态查询
            $filter->equal('withdrawal_status', '提现状态')->select([
                0 => '待处理',
                1 => '已到账',
                2 => '失败'
            ]);
            
            // 提现时间范围查询
            $filter->between('withdrawal_time', '提现时间')->datetime();
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
            $query = UserWithdrawal::query();
            
            // 应用筛选条件
            if (request('withdrawal_no')) {
                $query->where('withdrawal_no', 'like', '%' . request('withdrawal_no') . '%');
            }
            
            if (request('用户名')) {
                $query->whereHas('user', function ($q) {
                    $q->where('username', 'like', '%' . request('用户名') . '%');
                });
            }
            
            if (request('withdrawal_method') !== null && request('withdrawal_method') !== '') {
                $query->where('withdrawal_method', request('withdrawal_method'));
            }
            
            if (request('withdrawal_status') !== null && request('withdrawal_status') !== '') {
                $query->where('withdrawal_status', request('withdrawal_status'));
            }
            
            if (request('withdrawal_time.start') && request('withdrawal_time.end')) {
                $query->whereBetween('withdrawal_time', [request('withdrawal_time.start'), request('withdrawal_time.end')]);
            }
            
            // 获取统计数据 - 使用更高效的方式
            $totalCount = (clone $query)->count();
            $totalAmount = (clone $query)->sum('amount');
            $totalActualAmount = (clone $query)->sum('actual_amount');
            $totalHandlingFee = (clone $query)->sum('handling_fee');
            
            // 分别获取各状态的统计
            $pendingQuery = (clone $query)->where('withdrawal_status', 0);
            $pendingCount = $pendingQuery->count();
            $pendingAmount = $pendingQuery->sum('amount');
            
            $successQuery = (clone $query)->where('withdrawal_status', 1);
            $successCount = $successQuery->count();
            $successAmount = $successQuery->sum('amount');
            
            $failedQuery = (clone $query)->where('withdrawal_status', 2);
            $failedCount = $failedQuery->count();
            $failedAmount = $failedQuery->sum('amount');
            
            return "
            <div style='padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-top: 10px;'>
                <strong>统计信息：</strong>
                <span style='margin-left: 20px; color: #ffc107;'>待处理: {$pendingCount}笔 ¥" . number_format($pendingAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #28a745;'>已到账: {$successCount}笔 ¥" . number_format($successAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #dc3545;'>失败: {$failedCount}笔 ¥" . number_format($failedAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #007bff; font-weight: bold;'>总计: {$totalCount}笔 ¥" . number_format($totalAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #6c757d;'>手续费: ¥" . number_format($totalHandlingFee, 2) . "</span>
                <span style='margin-left: 20px; color: #28a745;'>实际到账: ¥" . number_format($totalActualAmount, 2) . "</span>
            </div>";
        });

        return $grid;
    }

    public function detail($id)
    {
        $show = new Show(UserWithdrawal::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('withdrawal_no', __('提现单号'));
        $show->field('third_party_order_no', __('第三方订单号'));
        $show->field('user.username', __('用户名'));
        $show->field('amount', __('提现金额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('handling_fee', __('手续费'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('actual_amount', __('实际到账'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('withdrawal_method', __('提现方式'))->as(function ($value) {
            switch ($value) {
                case 1:
                    return '<span class="label label-info">银行卡</span>';
                case 2:
                    return '<span class="label label-primary">支付宝</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('withdrawal_status', __('提现状态'))->as(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">待处理</span>';
                case 1:
                    return '<span class="label label-success">已到账</span>';
                case 2:
                    return '<span class="label label-danger">失败</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('withdrawal_time', __('提现时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        });
        $show->field('arrival_time', __('到账时间'))->as(function ($value) {
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