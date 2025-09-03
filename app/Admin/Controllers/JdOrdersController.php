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

class JdOrdersController extends AdminController
{
    protected $title = '京东订单管理';

    protected function grid()
    {
        $grid = new Grid(new Orders());
        $grid->model()->with('user');

        $grid->column('id', __('订单ID'))->sortable();
        $grid->column('user.username', __('用户昵称'))->limit(20);
        $grid->column('order_id', __('京东订单号'))->sortable();
        $grid->column('sku_name', __('商品标题'))->limit(50);
        $grid->column('sku_id', __('SKU ID'));
        $grid->column('sku_num', __('购买数量'));
        $grid->column('price', __('单价'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $grid->column('total_price', __('总价'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $grid->column('shop_name', __('店铺名称'))->limit(30);
        $grid->column('commission_rate', __('佣金比例'))->display(function ($value) {
            return $value . '%';
        });
        $grid->column('estimate_fee', __('预估佣金'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $grid->column('valid_code', __('订单状态'))->display(function ($value) {
            return $this->getStatusText($value);
        });
        $grid->column('order_time', __('下单时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();
        $grid->column('modify_time', __('更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();
        $grid->column('updated_at', __('系统更新时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        })->sortable();

        // 查询设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 京东订单号查询
            $filter->like('order_id', '京东订单号');
            
            // 用户昵称查询
            $filter->like('user.username', '用户昵称');
            
            // 商品标题查询
            $filter->like('sku_name', '商品标题');
            
            // SKU ID查询
            $filter->like('sku_id', 'SKU ID');
            
            // 店铺名称查询
            $filter->like('shop_name', '店铺名称');
            
            // 订单状态查询
            $filter->equal('valid_code', '订单状态')->select([
                15 => '待付款',
                16 => '已付款',
                17 => '已完成',
                24 => '已付定金',
                33 => '超市卡充值订单',
                -1 => '未知',
                2 => '无效-拆单',
                3 => '无效-取消',
                4 => '无效-京东帮帮主订单',
                5 => '无效-账号异常',
                6 => '无效-赠品类目不返佣',
                7 => '无效-校园订单',
                8 => '无效-企业订单',
                9 => '无效-团购订单',
                11 => '无效-乡村推广员下单',
                13 => '违规订单-其他',
                14 => '无效-来源与备案网址不符',
                19 => '无效-佣金比例为0',
                20 => '无效-此复购订单对应的首购订单无效',
                21 => '无效-云店订单',
                22 => '无效-PLUS会员佣金比例为0',
                23 => '无效-支付有礼',
                25 => '违规订单-流量劫持',
                26 => '违规订单-流量异常',
                27 => '违规订单-违反京东平台规则',
                28 => '违规订单-多笔交易异常',
                29 => '无效-跨屏跨店',
                30 => '无效-累计件数超出类目上限',
                31 => '无效-黑名单sku',
                34 => '无效-推卡订单无效',
                35 => '无效-非CID订单',
                36 => '违规订单-账户绑定有误',
            ]);
            
            // 时间区间查询
            $filter->between('order_time', '下单时间')->datetime();
            $filter->between('modify_time', '更新时间')->datetime();
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
        
        $show->field('id', __('订单ID'));
        $show->field('user.username', __('用户昵称'));
        $show->field('order_id', __('京东订单号'));
        $show->field('sku_name', __('商品标题'));
        $show->field('sku_id', __('SKU ID'));
        $show->field('sku_num', __('购买数量'));
        $show->field('price', __('单价'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('total_price', __('总价'))->as(function ($value) {
            return '¥' . number_format($value, 2);
        });
        $show->field('image_url', __('商品图片'))->image();
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
            return $this->getStatusText($value);
        });
        $show->field('order_time', __('下单时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('modify_time', __('更新时间'))->as(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '';
        });
        $show->field('finish_time', __('完成时间'))->as(function ($value) {
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

    /**
     * 获取订单状态文本
     */
    private function getStatusText($validCode)
    {
        switch ($validCode) {
            case -1:
                return '<span class="label label-default">未知</span>';
            case 2:
                return '<span class="label label-danger">无效-拆单</span>';
            case 3:
                return '<span class="label label-danger">无效-取消</span>';
            case 4:
                return '<span class="label label-danger">无效-京东帮帮主订单</span>';
            case 5:
                return '<span class="label label-danger">无效-账号异常</span>';
            case 6:
                return '<span class="label label-danger">无效-赠品类目不返佣</span>';
            case 7:
                return '<span class="label label-danger">无效-校园订单</span>';
            case 8:
                return '<span class="label label-danger">无效-企业订单</span>';
            case 9:
                return '<span class="label label-danger">无效-团购订单</span>';
            case 11:
                return '<span class="label label-danger">无效-乡村推广员下单</span>';
            case 13:
                return '<span class="label label-warning">违规订单-其他</span>';
            case 14:
                return '<span class="label label-danger">无效-来源与备案网址不符</span>';
            case 15:
                return '<span class="label label-warning">待付款</span>';
            case 16:
                return '<span class="label label-info">已付款</span>';
            case 17:
                return '<span class="label label-success">已完成</span>';
            case 19:
                return '<span class="label label-danger">无效-佣金比例为0</span>';
            case 20:
                return '<span class="label label-danger">无效-此复购订单对应的首购订单无效</span>';
            case 21:
                return '<span class="label label-danger">无效-云店订单</span>';
            case 22:
                return '<span class="label label-danger">无效-PLUS会员佣金比例为0</span>';
            case 23:
                return '<span class="label label-danger">无效-支付有礼</span>';
            case 24:
                return '<span class="label label-info">已付定金</span>';
            case 25:
                return '<span class="label label-warning">违规订单-流量劫持</span>';
            case 26:
                return '<span class="label label-warning">违规订单-流量异常</span>';
            case 27:
                return '<span class="label label-warning">违规订单-违反京东平台规则</span>';
            case 28:
                return '<span class="label label-warning">违规订单-多笔交易异常</span>';
            case 29:
                return '<span class="label label-danger">无效-跨屏跨店</span>';
            case 30:
                return '<span class="label label-danger">无效-累计件数超出类目上限</span>';
            case 31:
                return '<span class="label label-danger">无效-黑名单sku</span>';
            case 33:
                return '<span class="label label-success">超市卡充值订单</span>';
            case 34:
                return '<span class="label label-danger">无效-推卡订单无效</span>';
            case 35:
                return '<span class="label label-danger">无效-非CID订单</span>';
            case 36:
                return '<span class="label label-warning">违规订单-账户绑定有误</span>';
            default:
                return '<span class="label label-default">未知状态</span>';
        }
    }
}
