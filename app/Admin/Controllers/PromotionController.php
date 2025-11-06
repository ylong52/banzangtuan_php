<?php
namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Grid\Filter;
use App\Models\Promotion;
use App\Models\Orders;
use App\Models\User;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;

class PromotionController extends AdminController
{
    protected $title = '推荐人管理';

    protected function grid()
    {
        $grid = new Grid(new Promotion());

        // 关联查询
        $grid->model()->with(['user', 'referrer']);

        $grid->column('id', __('序号'))->display(function ($value) {
            static $index = 0;
            return ++$index;
        });  
        $grid->column('user.id', __('注册用户'));
        $grid->column('user.username', __('注册用户'))->display(function ($value) {
            return $value ?: '用户未定义';
        })->sortable();
        $grid->column('referrer.username', __('推荐人'))->sortable();
        $grid->column('referral_code', __('推荐码'))->sortable();
        $grid->column('thisMonth', __('本月推荐人数'))->display(function ($value) {
            $controller = new PromotionController();
            return  $controller->totalMonth($this->user_id,'thisMonth');
        });
        $grid->column('lastMonth', __('上月推荐人数'))->display(function ($value) {
            $controller = new PromotionController();
            return  $controller->totalMonth($this->user_id,'lastMonth');
        });

        $grid->column('user.bank_real_name', __('注册用户方真实姓名'))->display(function ($value) {
            return $value  ?? '-';
        }) ;
        $grid->column('user.id_card', __('身份证'))->display(function ($value) {
            return $value ? $value : '-';
        });
        $grid->column('user.bank_card', __('银行卡号'))->display(function ($value) {
            return $value ? $value : '-';
        });
        $grid->column('user.bank_phone', __('银行卡预留电话'))->display(function ($value) {
            return $value ? $value : '-';
        });

        // 操作列
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
 
        });

        // 搜索设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
                     
            // 推荐码查询
            $filter->like('referral_code', '推荐码');
            
            // 注册时间范围查询
            $filter->between('registration_time', '注册时间')->datetime();
        });

        // 关闭批量操作
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
            
             
        });

         
        return $grid;
    }

    public function totalMonth($user_id,$type) {
        if($type == 'lastMonth'){
            //  1，上月已经收货、应得佣金
            $lastMonthTotalOrderActualFee = Orders::totalOrderActualFee(
                $user_id,
                date('Y-m-01 00:00:00', strtotime('last month')),
                date('Y-m-t 23:59:59', strtotime('last month'))
            );
            return $lastMonthTotalOrderActualFee['sum_actual_fee'];
        }
        if($type == 'thisMonth'){
            // 2，本月已经收货、应得佣金
            $ThisMonthTotalOrderActualFee = Orders::totalOrderActualFee(
                $user_id,
                date('Y-m-01 00:00:00'),
                date('Y-m-t 23:59:59')
            );
            return $ThisMonthTotalOrderActualFee['sum_actual_fee'];
        }
 

    }

   

    public function detail($id)
    {
        $promotion = Promotion::with(['user', 'referrer'])->findOrFail($id);
        
        // 如果是 AJAX 请求，返回弹窗内容
        if (request()->ajax()) {
            return view('admin.promotion.detail', compact('promotion'));
        }
        
        $show = new Show($promotion);
        
        $show->field('id', __('序号'));
        $show->field('user.username', __('注册用户'));
        $show->field('referrer.username', __('推荐人'));
        $show->field('referral_code', __('推荐码'));
        $show->field('registration_time', __('注册时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        });
        $show->field('reward_amount', __('奖励金额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('reward_status', __('奖励状态'))->as(function ($value) {
            switch ($value) {
                case 0:
                    return '<span class="label label-warning">待发放</span>';
                case 1:
                    return '<span class="label label-success">已发放</span>';
                case 2:
                    return '<span class="label label-danger">已失效</span>';
                default:
                    return '<span class="label label-default">未知</span>';
            }
        });
        $show->field('reward_time', __('奖励发放时间'))->as(function ($value) {
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

    public function export()
    {
        // 导出功能实现
        $promotions = Promotion::with(['user', 'referrer'])->get();
        
        $filename = '推荐奖励数据_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($promotions) {
            $file = fopen('php://output', 'w');
            
            // 写入表头
            fputcsv($file, [
                '序号', '注册用户', '推荐人', '推荐码', '注册时间', 
                '奖励金额', '奖励状态', '奖励发放时间', '创建时间'
            ]);
            
            // 写入数据
            foreach ($promotions as $promotion) {
                $status = match($promotion->reward_status) {
                    0 => '待发放',
                    1 => '已发放',
                    2 => '已失效',
                    default => '未知'
                };
                
                fputcsv($file, [
                    $promotion->id,
                    $promotion->user->username ?? '未知用户',
                    $promotion->referrer->username ?? '未知推荐人',
                    $promotion->referral_code,
                    $promotion->registration_time,
                    $promotion->reward_amount,
                    $status,
                    $promotion->reward_time ?: '-',
                    $promotion->created_at
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}