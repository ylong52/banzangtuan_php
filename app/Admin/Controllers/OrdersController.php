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
use Illuminate\Http\Request;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Alert;
 
use App\Models\Orders;
 
use App\Models\Products;

class OrdersController extends AdminController
{
    protected $title = '订单管理';

    protected function grid()
    {
        // 方法1: 直接在模型初始化时添加查询条件
        $grid = new Grid(new Orders());
        
        // 预加载用户关系，避免N+1查询问题
        $grid->model()->with('user');
        
        // $grid->model()->whereNotNull('sub_union_id')->orderBy('order_time', 'desc');  
 
        // 添加统计信息
        $grid->header(function () {
            $whereUser = null;
            $request = Request();
            if ($request->has('user_id')) {
                $whereUser = ['user_id'=>$request->user_id];
            }

            // 今天的开始和结束时间
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            
            // 昨天的开始和结束时间
            $yesterdayStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));
            
            // 最近7天的开始时间
            $sevenDaysAgo = date('Y-m-d 00:00:00', strtotime('-7 days'));
            // 最近30天的时间
            $thirtyDaysAgo = date('Y-m-d 00:00:00', strtotime('-30 days'));
            // 总统计
            $totalEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->sum('estimate_fee');
            $totalOrders = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->count();
 
            //1今天的统计
            $todayEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$todayStart, $todayEnd])
                ->sum('estimate_fee');
            $todayOrders = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$todayStart, $todayEnd])
                ->count();
            
            //2昨天的统计
            $yesterdayEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$yesterdayStart, $yesterdayEnd])
                ->sum('estimate_fee');
            $yesterdayOrders = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$yesterdayStart, $yesterdayEnd])
                ->count();
            
            //3最近7天的统计
            $sevenDaysEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$sevenDaysAgo, $todayEnd])
                ->sum('estimate_fee');
            $sevenDaysOrders = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$sevenDaysAgo, $todayEnd])
                ->count();
            
            //4最近30天的统计
            $thirtyDaysEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$thirtyDaysAgo, $todayEnd])
                ->sum('estimate_fee');
            $thirtyDaysOrders = Orders::query()
                ->where($whereUser)
                ->whereIn('valid_code', [16,17])
                ->whereBetween('order_time', [$thirtyDaysAgo, $todayEnd])
                ->count();

            //5.actual_fee 全部订单已经收货、待结算的佣金
            $totalAllEstimateCosPrice = Orders::query()
                ->where($whereUser)
                ->where('valid_code', 17)
                ->sum('actual_fee');
            $totalAllEstimateCosOrders = Orders::query()
                ->where($whereUser)
                ->where('valid_code', 17)
                ->count();

            //6.上月已经收货、应得佣金
            $lastMonthTotalOrderActualFee = Orders::totalOrderActualFee(
                (empty($request->user_id) ? null : $request->user_id),
                date('Y-m-01 00:00:00', strtotime('last month')),
                date('Y-m-t 23:59:59', strtotime('last month'))
            );
            // dd($lastMonthTotalOrderActualFee);
            $totalLastMonthEstimateCosPrice = $lastMonthTotalOrderActualFee['sum_actual_fee'];
            $totalLastMonthEstimateCosOrders = $lastMonthTotalOrderActualFee['count_actual_fee'];
            //7.本月已经收货、应得佣金
            // 修改为本月的统计
            $ThisMonthTotalOrderActualFee = Orders::totalOrderActualFee(
                (empty($request->user_id) ? null : $request->user_id),
                date('Y-m-01 00:00:00'),
                date('Y-m-t 23:59:59')
            );
            $totalThisMonthEstimateCosPrice = $ThisMonthTotalOrderActualFee['sum_actual_fee'];
            $totalThisMonthEstimateCosOrders = $ThisMonthTotalOrderActualFee['count_actual_fee'];
            $totalGridQuery = Orders::query();            
            if ($request->has('user_id') && !empty($request->user_id)) {
                // $totalGridWhere['user_id'] = $request->user_id;
                $totalGridQuery->where('user_id', $request->user_id);
            }
            if ($request->has('valid_code') && !empty($request->valid_code)) {
                // 支持多选 valid_code
                if (is_array($request->valid_code)) {
                    // $totalGridWhere[] = ['valid_code', 'in', $request->valid_code];
                    $totalGridQuery->whereIn('valid_code', $request->valid_code);
                } else {
                    // $totalGridWhere['valid_code'] = $request->valid_code;
                    $totalGridQuery->where('valid_code', $request->valid_code);
                }
            }
            if ($request->has('order_time') && !empty($request->order_time)) {
                $orderTime = $request->order_time;
                if (isset($orderTime['start']) && isset($orderTime['end']) && $orderTime['start'] && $orderTime['end']) {
                    $totalGridQuery->where('order_time', '>=', $orderTime['start'])
                    ->where('order_time', '<=', $orderTime['end']);
                }

            }
            if ($request->has('finish_time') && !empty($request->finish_time)) {
                $finishTime = $request->finish_time;
                if (isset($finishTime['start']) && isset($finishTime['end']) && $finishTime['start'] && $finishTime['end']) {
                    $totalGridQuery->where('finish_time', '>=', $finishTime['start'])
                    ->where('finish_time', '<=', $finishTime['end']);

                }
            }
           
            $totalGrid = $this->totalGrid($totalGridQuery);
            
            // 先定义本月和上月变量
            $lastMonth = date('Y年m月', strtotime('last month'));
            $thisMonth = date('Y年m月');

            // 获取用户名（如果有用户ID查询条件）
            $userName = '';
            if ($request->has('user_id') && !empty($request->user_id)) {
                $user = \App\Models\User::find($request->user_id);
                if ($user) {
                    $userName = $user->username;
                }
            }

            $statisticsTitle = '统计信息';
            if (!empty($userName)) {
                $statisticsTitle = '统计信息&nbsp; 用户[' . $userName . ']';
            }

            return '<div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> ' . $statisticsTitle . '</h4>
                <div class="row">
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar"></i> 1.全部统计[状态：全部;统计:下单时间]</h5>
                        <p><strong>总预估计有效的佣金额：</strong> ¥' . number_format($totalEstimateCosPrice, 2) . '</p>
                        <p><strong>订单总数：</strong> ' . number_format($totalOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar-day"></i> 2.今日统计['.date('Y-m-d 00:00:00').'至'.date('Y-m-d 23:59:59').'][状态：16,17;统计:下单时间]</h5>
                        <p><strong>今日预估计有效的佣金额：</strong> ¥' . number_format($todayEstimateCosPrice, 2) . '</p>
                        <p><strong>今日订单数：</strong> ' . number_format($todayOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar-minus"></i> 3.昨日统计['.date('Y-m-d 00:00:00', strtotime('-1 day')).'至'.date('Y-m-d 23:59:59', strtotime('-1 day')).'][状态：16;统计:下单时间]</h5>
                        <p><strong>昨日预估计有效的佣金额：</strong> ¥' . number_format($yesterdayEstimateCosPrice, 2) . '</p>
                        <p><strong>昨日订单数：</strong> ' . number_format($yesterdayOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar-week"></i> 4.最近7天统计['.date('Y-m-01 00:00:00').'至'.date('Y-m-d 23:59:59').'][状态：16,17;统计:下单时间]</h5>
                        <p><strong>7天预估计有效的佣金额：</strong> ¥' . number_format($sevenDaysEstimateCosPrice, 2) . '</p>
                        <p><strong>7天订单数：</strong> ' . number_format($sevenDaysOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar-plus"></i> 5.最近30天统计['.date('Y-m-01 00:00:00').'至'.date('Y-m-d 23:59:59').'][状态：16,17;统计:下单时间)</h5>
                        <p><strong>30天预估计有效的佣金额：</strong> ¥' . number_format($thirtyDaysEstimateCosPrice, 2) . '</p>
                        <p><strong>30天订单数：</strong> ' . number_format($thirtyDaysOrders) . ' 笔</p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #e0e0e0; padding-top: 10px;">
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar"></i> 6.全部订单[状态：17,统计:下单时间]</h5>
                        <p><strong>全部订单已经收货、待结算的佣金:</strong> ¥' . number_format($totalAllEstimateCosPrice, 2) . '</p>
                        <p><strong>订单总数：</strong> ' . number_format($totalAllEstimateCosOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar"></i> 7.上月(' . $lastMonth . ')订单[状态：17,统计:订单完成时间]</h5>
                        <p><strong>本月预估结算佣金:</strong> ¥' . number_format($totalLastMonthEstimateCosPrice, 2) . '</p>
                        <p><strong>订单总数：</strong> ' . number_format($totalLastMonthEstimateCosOrders) . ' 笔</p>
                    </div>
                    <div class="col-md-2">
                        <h5><i class="fa fa-calendar"></i> 8.本月(' . $thisMonth . ')订单[状态：17,统计:订单完成时间]</h5>
                        <p><strong>下月预估结算佣金:</strong> ¥' . number_format($totalThisMonthEstimateCosPrice, 2) . '</p>
                        <p><strong>订单总数：</strong> ' . number_format($totalThisMonthEstimateCosOrders) . ' 笔</p>
                    </div>
                </div>
            </div>
            <div class="row grid_total_box">
                <div class="col-md-12">
                    <h5><i class="fa fa-calendar"></i> 统计列表信息</h5>
                    <div style="display: flex; flex-direction: row; gap: 20px;">
                        <p><strong>订单总数：</strong> ' . number_format($totalGrid['orders_count']) . ' 笔</p>
                        <p><strong>estimate_fee：</strong> ¥' . number_format($totalGrid['estimate_fee'], 2) . '</p>
                        <p><strong>actual_fee：</strong> ¥' . number_format($totalGrid['actual_fee'], 2) . '</p>
                        <p><strong>estimate_cos_price：</strong> ¥' . number_format($totalGrid['estimate_cos_price'], 2) . '</p>
                    </div>
                </div>
            </div>
            ';
        });

        $grid->column('id', __('ID'))->sortable();        
        $grid->column('user.username', __('用户昵称'))->limit(20);
        $grid->column('order_id', __('订单ID'));     
        
        // 显示商品标题
        $grid->column('sku_name', __('商品标题'));
        // 增加 sub_union_id 字段的显示，并支持查询
        $grid->column('sub_union_id', __('sub_union_id推广用户'))->sortable();
               
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
        $grid->column('estimate_cos_price', __('estimate_cos_price实付金额'));
        $grid->column('estimate_fee', __('estimate_fee预估佣金'));
        $grid->column('actual_cos_price', __('actual_cos_price实际计算佣金金额'));
        $grid->column('actual_fee', __('actual_fee推客分得实际佣金'));
        $grid->column('sub_side_rate', __('sub_side_rate分成比例(%)'));
        $grid->column('subsidy_rate', __('subsidy_rate补贴比例(%)'));
        $grid->column('final_rate', __('final_rate最终分佣比例(%)'));
        $grid->column('order_time', __('下单时间'))->sortable();
        $grid->column('modify_time', __('订单更新时间'))->sortable();
        $grid->column('finish_time', __('订单完成时间'))->sortable();
 
        // 根据订单状态 value 显示对应的中文标签
        $grid->column('valid_code', __('订单状态'))->display(function ($value) {
            switch ($value) {
                case -1:
                    return '<span class="label label-info">-1.未知</span>';
                case 2:
                    return '<span class="label label-info">2.无效-拆单</span>';
                case 3:
                    return '<span class="label label-default">3.无效-取消</span>';
                case 15:
                    return '<span class="label label-warning">15.待付款</span>';
                case 16:
                    return '<span class="label label-success">16.已付款</span>';
                case 17:
                    return '<span class="label label-primary">17.已完成</span>';
                case 24:
                    return '<span class="label label-info">24.已付定金</span>';
                default:
                    return '<span class="label label-default">0.未知</span>';
            }
        });
 
        $grid->column('updated_at', __('系统更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();

        // 查询设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();            
            // 商品标题查询
            $filter->like('sku_name', '商品标题');
            
            // 用户昵称查询 - 下拉选择（支持搜索）
            $filter->equal('user_id', '用户昵称')->select(function() {
                return \App\Models\User::whereNotNull('username')
                    ->where('username', '!=', '')
                    ->orderBy('username')
                    ->limit(500) // 限制返回500个用户，避免页面卡顿
                    ->pluck('username', 'id')
                    ->toArray();
            });
                                    
            // 订单号查询
            $filter->equal('order_id', '订单号');            
            // 订单状态查询 - 多选
            $filter->in('valid_code', '订单状态')->multipleSelect([
                -1 => '-1.未知',
                2 => '2.无效-拆单',
                3 => '3.无效-取消',
                15 => '15.待付款',
                16 => '16.已付款',
                17 => '17.已完成',
                24 => '24.已付定金'
            ]);            
            // 时间区间查询
            $filter->between('order_time', '下单时间')->datetime();
            $filter->between('finish_time', '订单完成时间')->datetime();
 
             
        });

        // 启用操作按钮
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            // 启用删除按钮
            $actions->disableDelete(false);
        });
        
        // 启用批量操作
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                // 启用批量删除
                $batch->disableDelete(false);
            });
        });
        
        return $grid;
    }

    // 添加批量删除方法
    public function batchDelete(Request $request)
    {
        $ids = $request->get('ids');
        
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => '请选择要删除的订单'
            ]);
        }
        
        try {
            $count = Orders::whereIn('id', $ids)->count();
            
            if ($count == 0) {
                return response()->json([
                    'status' => false,
                    'message' => '没有找到要删除的订单'
                ]);
            }
            
            // 执行删除
            Orders::whereIn('id', $ids)->delete();
            
            return response()->json([
                'status' => true,
                'message' => "成功删除 {$count} 条订单记录"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '删除失败：' . $e->getMessage()
            ]);
        }
    }

    // 添加全选删除方法
    public function deleteAll(Request $request)
    {
        try {
            // 获取当前筛选条件
            $query = Orders::query();
            
            // 应用筛选条件
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }
            
            if ($request->has('valid_code') && !empty($request->valid_code)) {
                if (is_array($request->valid_code)) {
                    $query->whereIn('valid_code', $request->valid_code);
                } else {
                    $query->where('valid_code', $request->valid_code);
                }
            }
            
            if ($request->has('order_time') && !empty($request->order_time)) {
                $orderTime = $request->order_time;
                if (isset($orderTime['start']) && isset($orderTime['end']) && $orderTime['start'] && $orderTime['end']) {
                    $query->where('order_time', '>=', $orderTime['start'])
                          ->where('order_time', '<=', $orderTime['end']);
                }
            }
            
            if ($request->has('finish_time') && !empty($request->finish_time)) {
                $finishTime = $request->finish_time;
                if (isset($finishTime['start']) && isset($finishTime['end']) && $finishTime['start'] && $finishTime['end']) {
                    $query->where('finish_time', '>=', $finishTime['start'])
                          ->where('finish_time', '<=', $finishTime['end']);
                }
            }
            
            // 获取符合条件的订单数量
            $count = $query->count();
            
            if ($count == 0) {
                return response()->json([
                    'status' => false,
                    'message' => '没有找到符合条件的订单'
                ]);
            }
            
            // 执行删除
            $query->delete();
            
            return response()->json([
                'status' => true,
                'message' => "成功删除 {$count} 条订单记录"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '删除失败：' . $e->getMessage()
            ]);
        }
    }

    public function totalGrid($query) { 
        $total = [];       
        $total['actual_fee'] =(clone $query)            
            ->sum('actual_fee');
        $total['estimate_fee'] = (clone $query)            
            ->sum('estimate_fee');
        $total['estimate_cos_price'] = (clone $query)            
            ->sum('estimate_cos_price');
        $total['orders_count'] =(clone $query)  
            ->count();

        return $total;
    }

    public function detail($id)
    {
        $show = new Show(Orders::with('user')->findOrFail($id));
        $show->field('id', __('ID'));
        $show->field('user.username', __('用户昵称'));
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
