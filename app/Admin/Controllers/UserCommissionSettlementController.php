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
        $grid->column('after_tax_income', __('税后收入'));
        $grid->column('general_commission', __('普通佣金'));
        // $grid->column('reward_commission', __('奖励佣金'));
        $grid->column('pre_tax_amount', __('税前金额'));
        $grid->column('total_tax', __('总税额'));
        $grid->column('is_paid', __('是否已打款'))->display(function ($isPaid) {
            return $isPaid ? '是' : '否';
        });
        $grid->column('remark', __('备注'));
        $grid->column('created_at', __('创建时间'));
        $grid->column('updated_at', __('更新时间'));
   

 
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
        });

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new UserCommissionSettlement());

        // 用户ID选择器
        $form->select('user_id', __('用户ID'))
            ->options(User::pluck('username', 'id'))
            ->required()
            ->help('选择对应的用户');

        // 账单编号
        $form->text('bill_no', __('账单编号'))
            ->required()
            ->rules('required|max:50')
            ->default(function() {
                // 获取当前月份减一个月
                $date = now()->subMonth();
                return $date->format('Ym').'帐单'; // 格式：202508
            })
            ->help('如：202502、202503等，默认为上个月');

        // 税后收入
        $form->currency('after_tax_income', __('税后收入'))
            ->symbol('¥')
            ->required()
            ->rules('required|numeric|min:0')
            ->help('用户实际到账的金额');

        // 普通佣金
        $form->currency('general_commission', __('普通佣金'))
            ->symbol('¥')
            ->required()
            ->rules('required|numeric|min:0')
            ->help('基础佣金金额');

        // // 奖励佣金
        // $form->currency('reward_commission', __('奖励佣金'))
        //     ->symbol('¥')
        //     ->default(0)
        //     ->rules('numeric|min:0')
        //     ->help('额外奖励佣金金额');

        // 税前金额
        $form->currency('pre_tax_amount', __('税前金额'))
            ->symbol('¥')
            ->required()
            ->rules('required|numeric|min:0')
            ->help('扣除税费前的金额');

        // 总税额
        $form->currency('total_tax', __('总税额'))
            ->symbol('¥')
            ->required()
            ->rules('required|numeric|min:0')
            ->help('需要缴纳的税费总额');

        // 是否已打款
        $form->switch('is_paid', __('是否已打款'))
            ->states([
                'on'  => ['value' => 1, 'text' => '已打款', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => '未打款', 'color' => 'warning'],
            ])
            ->default(1)
            ->help('该笔佣金是否已经打款给用户');

        // 备注
        $form->textarea('remark', __('备注'))
            ->rows(3)
            ->rules('max:500')
            ->help('备注说明，如优惠活动、调整原因等');

        // 创建时间和更新时间（只读）
        $form->display('created_at', __('创建时间'));
        $form->display('updated_at', __('更新时间'));

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
            
            if (abs($preTaxAmount - ($afterTaxIncome + $totalTax)) > 0.01) {
                return back()->withInput()->withErrors(['pre_tax_amount' => '税前金额必须等于税后收入 + 总税额']);
            }
            
            // 验证总佣金 = 普通佣金 + 奖励佣金
            $generalCommission = $form->general_commission;
            $rewardCommission = $form->reward_commission;
            $totalCommission = $generalCommission + $rewardCommission;
            
            if (abs($preTaxAmount - $totalCommission) > 0.01) {
                return back()->withInput()->withErrors(['pre_tax_amount' => '税前金额必须等于总佣金（普通佣金 + 奖励佣金）']);
            }
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