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
 
use App\Models\AyqyOrders;
use App\Models\User;
use App\Models\Products;

class AyqyOrdersController extends AdminController
{
    protected $title = '订单管理';

    protected function grid()
    {
        $grid = new Grid(new AyqyOrders());

        // 关联查询
        $grid->model()->with(['product', 'user']);

        $grid->column('id', __('ID'))->sortable();
        $grid->column('order_number', __('订单号'))->sortable();
        
        // 显示商品标题
        $grid->column('product.name', __('商品标题'));
        
        // 显示用户名
        $grid->column('user.username', __('用户名'));
        
        // 显示手机号
        $grid->column('user.phone', __('手机号'));
        
        $grid->column('buynumber', __('购买数量'));
        $grid->column('should_amount', __('应付金额'));
        $grid->column('real_amount', __('实付金额'));
        $grid->column('status', __('订单状态'))->display(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">未支付</span>';
                case 1:
                    return '<span class="label label-success">成功</span>';
                case 2:
                    return '<span class="label label-danger">失败</span>';
                case 3:
                    return '<span class="label label-default">取消</span>';
                default:
                    return '<span class="label label-info">未知</span>';
            }
        });
        $grid->column('created_at', __('创建时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();
        $grid->column('updated_at', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });

        // 查询设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 商品标题查询
            $filter->where(function ($query) {
                $input = request('商品标题');
                if ($input) {
                    $query->whereHas('product', function ($q) use ($input) {
                        $q->where('name', 'like', "%{$input}%");
                    });
                }
            }, '商品标题');
            
            // 用户名查询
            $filter->where(function ($query) {
                $input = request('用户名');
                if ($input) {
                    $query->whereHas('user', function ($q) use ($input) {
                        $q->where('username', 'like', "%{$input}%");
                    });
                }
            }, '用户名');
            
            // 手机号查询
            $filter->where(function ($query) {
                $input = request('手机号');
                if ($input) {
                    $query->whereHas('user', function ($q) use ($input) {
                        $q->where('phone', 'like', "%{$input}%");
                    });
                }
            }, '手机号');
            
            // 订单号查询
            $filter->like('order_number', '订单号');
            
            // 订单状态查询
            $filter->equal('status', '订单状态')->select([
                0 => '未支付',
                1 => '成功',
                2 => '失败',
                3 => '取消'
            ]);
            
            // 时间区间查询
            $filter->between('created_at', '创建时间')->datetime();
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
        $show = new Show(AyqyOrders::findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('order_number', __('订单号'));
        $show->field('user.username', __('用户名'));
        $show->field('product.name', __('商品标题'));
        $show->field('buynumber', __('购买数量'));
        $show->field('should_amount', __('应付金额'));
        $show->field('real_amount', __('实付金额'));
        $show->field('status', __('订单状态'))->as(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">未支付</span>';
                case 1:
                    return '<span class="label label-success">成功</span>';
                case 2:
                    return '<span class="label label-danger">失败</span>';
                case 3:
                    return '<span class="label label-default">取消</span>';
                default:
                    return '<span class="label label-info">未知</span>';
            }
        });
        $show->field('created_at', __('创建时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('updated_at', __('更新时间'))->as(function ($value) {
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
