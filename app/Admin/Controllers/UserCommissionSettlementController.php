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
use App\Models\User;
use App\Models\UserCommissionSettlement;

class UserCommissionSettlementController extends AdminController
{
    protected $title = '用户佣金结算表管理';

    protected function grid()
    {
        $grid = new Grid(new UserCommissionSettlement());

        $grid->column('user.username', __('用户名'));
        $grid->column('bill_no', __('账单编号'));
        $grid->column('settlement_period', __('结算月份'))->display(function ($value, $model) {
            return $this->settlement_year . '年' . ltrim($this->settlement_month, '0') . '月';
        });
        $grid->column('after_tax_income', __('税后收入'));
        $grid->column('general_commission', __('普通佣金'));
        // $grid->column('reward_commission', __('奖励佣金'));
        $grid->column('pre_tax_amount', __('税前金额'));
        $grid->column('total_tax', __('总税额'));
        $grid->column('is_paid', __('是否已打款'))->display(function ($isPaid) {
            return $isPaid ? '是' : '否';
        });
        $grid->column('remark', __('备注'));
        $grid->column('created_at', __('创建时间'))->display(function($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $grid->column('updated_at', __('更新时间'))->display(function($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
   

 
        $grid->filter(function ($filter) {
            $filter->disableIdFilter(); // 禁用默认的ID筛选器
            $filter->like('bill_no', '账单编号');
            $filter->between('created_at', '创建时间')->datetime();
            $filter->equal('user_id', '用户名')->select(function ($id) {
                if ($id) {
                    $user = \App\Models\User::find($id);
                    if ($user) {
                        return [$user->id => $user->username];
                    }
                }
                return [];
            })->ajax('/api/users');
            //是否打款
            $filter->equal('is_paid', '是否打款')->select([
                1 => '是',
                0 => '否',
            ]);
        });

        return $grid;
    }

    private function sumorder($user_id)
    {
        //上月
        $sum_estimate_fee['last_month_estimate_fee'] = Orders::query()
            ->whereBetween('order_time', [date('Y-m-01 00:00:00', strtotime('last month')), date('Y-m-t 23:59:59', strtotime('last month'))])
            // ->where('user_id', $this->user_id)
            ->whereIn('valid_code', [16, 17])
            ->sum('estimate_fee');   //是预估佣金

        $sum_estimate_fee['last_month_actual_fee'] = Orders::query()
            ->whereBetween('order_time', [date('Y-m-01 00:00:00', strtotime('last month')), date('Y-m-t 23:59:59', strtotime('last month'))])
            // ->where('user_id', $user_id)
            ->whereIn('valid_code', [16, 17])
            ->sum('actual_fee'); //实际佣金（买家收货之后没有退款的）
   

        // 上月有多少单
        $sum_estimate_fee['last_month_order_count'] = Orders::query()
            ->whereBetween('order_time', [date('Y-m-01 00:00:00', strtotime('last month')), date('Y-m-t 23:59:59', strtotime('last month'))])
            // ->where('user_id', $user_id)
            ->whereIn('valid_code', [16, 17])
            ->count();   //订单数
        
     
        return $sum_estimate_fee;
    }


    protected function form()
    {
        $form = new Form(new UserCommissionSettlement());

        // 用户ID选择器 - 独占一行
        $form->select('user_id', __('用户ID'))
            ->options(User::pluck('username', 'id'))
            ->required()
            ->help('选择对应的用户');

        // 获取统计数据
        $sum_estimate_fee = $this->sumorder(1); // 默认用户ID为1，后续可以动态获取
        
        // 组装统计卡片HTML字符串
        $statsHtml = '
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> '.date('Y-m', strtotime('last month')).'佣金统计概览</h3>
                </div>
                <div class="box-body">
           
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-blue"><i class="fa fa-line-chart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">上月预估总佣金&nbsp;&nbsp;&nbsp;¥' . number_format($sum_estimate_fee['last_month_estimate_fee'], 2) . '</span>
                                    <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">上月实际总佣金&nbsp;&nbsp;&nbsp;¥' . number_format($sum_estimate_fee['last_month_actual_fee'], 2) . '</span>
                                    <span class="info-box-text" style="font-size: 14px; color: #0073aa; font-weight: bold; display: block; height: 30px; line-height: 30px;">上月订单总数&nbsp;&nbsp;&nbsp;' . $sum_estimate_fee['last_month_order_count'] . '</span>
                                </div>
                            </div>
                        </div>
                         
                    </div>
                </div>
            </div>
        ';

        // 统计卡片行
        $form->row(function($row) use ($statsHtml) {
            $row->width(12)->html($statsHtml);
        });

        // 账单信息行
        $form->row(function($row) {
            // 账单编号 - 独占一行
            $row->width(2)->text('bill_no', __('账单编号'))
            ->required()
            ->rules('required|max:50')
            ->default(function() {
                // 获取当前月份减一个月
                $date = now()->subMonth();
                return $date->format('Ym').'帐单'; // 格式：202508
            })
            ->help('如：202502、202503等，默认为上个月');

            $row->width(2)->select('settlement_year', __('结算年份'))
                ->options(function() {
                    $currentYear = now()->year;
                    return [
                        $currentYear-1 => $currentYear-1,
                        $currentYear => $currentYear,
                        $currentYear+1 => $currentYear+1,
                    ];
                })
                ->default(function() {
                    return now()->subMonth()->year;
                })
                ->required();
                
            $row->width(2)->select('settlement_month', __('结算月份'))
                ->options([
                    '01' => '1', '02' => '2', '03' => '3', '04' => '4',
                    '05' => '5', '06' => '6', '07' => '7', '08' => '8',
                    '09' => '9', '10' => '10', '11' => '11', '12' => '12',
                ])
                ->default(function() {
                    return now()->subMonth()->format('m');
                })
                ->required()
                ->help('结算月份');
        });

        // 税后收入和普通佣金 - 同一行
        $form->row(function($row) {
            $row->width(2)->currency('after_tax_income', __('税后收入'))
                ->symbol('¥')
                ->required()
                ->rules('required|numeric|min:0')
                ->help('用户实际到账的金额');

            $row->width(2)->currency('general_commission', __('普通佣金'))
                ->symbol('¥')
                ->required()
                ->rules('required|numeric|min:0')
                ->help('基础佣金金额');
       
            $row->width(2)->currency('pre_tax_amount', __('税前金额'))
                ->symbol('¥')
                ->required()
                ->rules('required|numeric|min:0')
                ->help('扣除税费前的金额');

            $row->width(2)->currency('total_tax', __('总税额'))
                ->symbol('¥')
                ->required()
                ->rules('required|numeric|min:0')
                ->help('需要缴纳的税费总额');
        });

        // 是否已打款和备注 - 同一行
        $form->row(function($row) {
            $row->width(4)->switch('is_paid', __('是否已打款'))
                ->states([
                    'on'  => ['value' => 1, 'text' => '已打款', 'color' => 'success'],
                    'off' => ['value' => 0, 'text' => '未打款', 'color' => 'warning'],
                ])
                ->default(1)
                ->help('该笔佣金是否已经打款给用户');
            });

        $form->row(function($row) {
            $row->width(8)->textarea('remark', __('备注'))
                ->rows(2)
                ->rules('max:500')
                ->help('备注说明，如优惠活动、调整原因等');
        });

       

        // 表单提交前处理
        $form->saving(function (Form $form) {
            // 验证账单编号是否唯一
            $billNo = $form->bill_no;
            $existing = UserCommissionSettlement::where('bill_no', $billNo)->first();
            if ($existing && $existing->id != $form->id) {
                return back()->withInput()->withErrors(['bill_no' => '账单编号已存在']);
            }

            // 验证税前金额 = 税后收入 + 总税额
            $preTaxAmount = $form->pre_tax_amount;
            $afterTaxIncome = $form->after_tax_income;
            $totalTax = $form->total_tax;
            
            // if (abs($preTaxAmount - ($afterTaxIncome + $totalTax)) > 0.01) {
            //     return back()->withInput()->withErrors(['pre_tax_amount' => '税前金额必须等于税后收入 + 总税额']);
            // }
            
            // 验证总佣金 = 普通佣金（暂时注释掉奖励佣金相关验证）
            $generalCommission = $form->general_commission;
            // $rewardCommission = $form->reward_commission ?? 0;
            // $totalCommission = $generalCommission + $rewardCommission;
            
            // if (abs($preTaxAmount - $totalCommission) > 0.01) {
            //     return back()->withInput()->withErrors(['pre_tax_amount' => '税前金额必须等于总佣金（普通佣金 + 奖励佣金）']);
            // }
        });

        return $form;
    }


    /**
     * 详情页面
     */
    public function detail($id)
    {
        $show = new Show(UserCommissionSettlement::findOrFail($id));
        
        $show->field('id', __('ID'));
        $show->field('user.username', __('用户名'));
        $show->field('bill_no', __('账单编号'));
        $show->field('settlement_period', __('结算月份'))->as(function ($value, $model) {
            return $model->settlement_year . '年' . ltrim($model->settlement_month, '0') . '月';
        });
        
        // 金额相关字段
        $show->field('after_tax_income', __('税后收入'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $show->field('general_commission', __('普通佣金'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $show->field('reward_commission', __('奖励佣金'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $show->field('pre_tax_amount', __('税前金额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        $show->field('total_tax', __('总税额'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        
        // 总佣金（计算字段）
        $show->field('total_commission', __('总佣金'))->as(function ($value, $model) {
            $total = $model->general_commission + $model->reward_commission;
            return '¥' . number_format($total, 2);
        });
        
        // 打款状态
        $show->field('is_paid', __('打款状态'))->as(function ($value) {
            if ($value) {
                return '<span class="label label-success">已打款</span>';
            }
            return '<span class="label label-warning">未打款</span>';
        });
        
        $show->field('remark', __('备注'));
        $show->field('created_at', __('创建时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('updated_at', __('更新时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        
        $show->panel()->tools(function ($tools) {   
            $tools->disableDelete();
        });
        
        return $show;
    }

 
}