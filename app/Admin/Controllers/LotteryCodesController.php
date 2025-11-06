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
use App\Models\LotteryCodes;
use Illuminate\Http\Request;
/*
* 抽奖码管理
*/

class LotteryCodesController extends AdminController
{
    protected $title = '抽奖码管理';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LotteryCodes());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('lottery_code', __('抽奖码'))->sortable();
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
        $grid->column('status', __('状态'))->display(function ($value) {
            $statusText = $value == 1 ? '有效' : '禁用';
            $statusClass = $value == 1 ? 'success' : 'danger';
            
            return sprintf(
                '<span class="label label-%s">%s</span>',
                $statusClass,
                $statusText
            );
        });
        
        // 添加自定义操作列（放在状态列之后）
        $grid->column('action_buttons', __('操作'))->display(function () {
            $id = $this->getAttribute('id');
            $currentStatus = $this->getAttribute('status');
            
            // 根据当前状态显示不同的按钮
            if ($currentStatus == 1) {
                // 如果当前是有效，显示"禁用状态"按钮
                return '<a href="javascript:void(0);" class="btn btn-xs btn-danger disable-status" data-id="' . $id . '">禁用状态</a>';
            } else {
                // 如果当前是禁用，显示"启用状态"按钮
                return '<a href="javascript:void(0);" class="btn btn-xs btn-success enable-status" data-id="' . $id . '">启用状态</a>';
            }
        });

        // 筛选功能
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 抽奖码等于查询
            $filter->equal('lottery_code', __('抽奖码'));
            
            // 奖品等级下拉查询
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
            
            // 状态下拉查询
            $filter->equal('status', __('状态'))->select([
                1 => '有效',
                0 => '禁用'
            ]);
        });

        // 禁用新增按钮
        $grid->disableCreateButton();
        // 禁用导出
        $grid->disableExport();
        // 禁用批量操作
        $grid->disableBatchActions();
        // 禁用行选择器
        $grid->disableRowSelector();
        
        // 完全禁用默认操作列
        $grid->disableActions();
        
        // 启用分页（默认已启用，但明确设置）
        $grid->paginate(20);

        // 添加 JavaScript 处理状态切换
        $grid->footer(function ($collection) {
            $disableUrl = url(config('admin.route.prefix') . '/lottery-codes/disable-status');
            $enableUrl = url(config('admin.route.prefix') . '/lottery-codes/enable-status');
            return <<<HTML
<script>
$(document).ready(function() {
    // 处理禁用状态按钮
    $(document).on('click', '.disable-status', function(e) {
        e.preventDefault();
        var \$btn = $(this);
        var id = \$btn.data('id');
        
        if (!confirm('确定要禁用这条记录吗？')) {
            return;
        }
        
        \$.ajax({
            url: '{$disableUrl}',
            type: 'POST',
            data: {
                id: id,
                _token: LA.token
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // 刷新页面
                    $.pjax.reload('#pjax-container');
                } else {
                    toastr.error(response.message || '操作失败');
                }
            },
            error: function() {
                toastr.error('网络错误，请重试');
            }
        });
    });
    
    // 处理启用状态按钮
    $(document).on('click', '.enable-status', function(e) {
        e.preventDefault();
        var \$btn = $(this);
        var id = \$btn.data('id');
        
        if (!confirm('确定要启用这条记录吗？')) {
            return;
        }
        
        \$.ajax({
            url: '{$enableUrl}',
            type: 'POST',
            data: {
                id: id,
                _token: LA.token
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // 刷新页面
                    $.pjax.reload('#pjax-container');
                } else {
                    toastr.error(response.message || '操作失败');
                }
            },
            error: function() {
                toastr.error('网络错误，请重试');
            }
        });
    });
});
</script>
HTML;
        });

        return $grid;
    }

    /**
     * 禁用状态
     */
    public function disableStatus(Request $request)
    {
        $id = $request->input('id');
        
        $lotteryCode = LotteryCodes::find($id);
        if (!$lotteryCode) {
            return response()->json(['success' => false, 'message' => '记录不存在']);
        }
        
        $lotteryCode->status = 0;
        $lotteryCode->save();
        
        return response()->json([
            'success' => true, 
            'message' => '状态已禁用',
            'status' => 0,
            'status_text' => '禁用'
        ]);
    }
    
    /**
     * 启用状态
     */
    public function enableStatus(Request $request)
    {
        $id = $request->input('id');
        
        $lotteryCode = LotteryCodes::find($id);
        if (!$lotteryCode) {
            return response()->json(['success' => false, 'message' => '记录不存在']);
        }
        
        $lotteryCode->status = 1;
        $lotteryCode->save();
        
        return response()->json([
            'success' => true, 
            'message' => '状态已启用',
            'status' => 1,
            'status_text' => '有效'
        ]);
    }
}
