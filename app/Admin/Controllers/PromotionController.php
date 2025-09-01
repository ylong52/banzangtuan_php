<?php
namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;
use Encore\Admin\Grid\Filter;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Tab;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;

class PromotionController extends AdminController
{
    protected $title = '推荐奖励管理';

    public function index(Content $content)
    {
        return $content
            ->title($this->title)
            ->description('推荐奖励数据管理')
            ->body($this->tab());
    }

    protected function tab()
    {
        $tab = new Tab();

        $tab->add('数据列表', $this->grid()->render());
        $tab->add('推荐人统计', $this->statisticsGrid()->render());

        return $tab;
    }

    protected function grid()
    {
        $grid = new Grid(new Promotion());

        // 关联查询
        $grid->model()->with(['user', 'referrer']);

        $grid->column('id', __('序号'))->sortable();
        $grid->column('user.username', __('注册用户'))->sortable();
        $grid->column('referrer.username', __('推荐人'))->sortable();
        $grid->column('referral_code', __('推荐码'))->sortable();
        $grid->column('registration_time', __('注册时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();
        $grid->column('reward_amount', __('奖励金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        })->sortable();
        $grid->column('reward_status', __('奖励状态'))->display(function ($value) {
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
        })->sortable();
        $grid->column('reward_time', __('奖励发放时间'))->display(function ($value) {
            return $value ? date('Y-m-d H:i:s', strtotime($value)) : '-';
        })->sortable();

        // 操作列
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
            
            // 添加查看详情按钮
            $actions->append('<a href="javascript:void(0);" class="btn btn-xs btn-info" onclick="showPromotionDetail(' . $actions->getKey() . ')">查看详情</a>');
        });

        // 搜索设置
        $grid->filter(function (Filter $filter) {
            $filter->disableIdFilter();
            
            // 推荐人/推荐码搜索
            $filter->where(function ($query) {
                $input = request('推荐人/推荐码');
                if ($input) {
                    $query->where(function ($q) use ($input) {
                        $q->whereHas('referrer', function ($subQuery) use ($input) {
                            $subQuery->where('username', 'like', "%{$input}%");
                        })
                        ->orWhere('referral_code', 'like', "%{$input}%");
                    });
                }
            }, '推荐人/推荐码');
            
            // 奖励状态查询
            $filter->equal('reward_status', '奖励状态')->select([
                0 => '待发放',
                1 => '已发放',
                2 => '已失效'
            ]);
            
            // 注册时间范围查询
            $filter->between('registration_time', '注册时间')->datetime();
        });

        // 关闭批量操作
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
            
            // 添加导出按钮
            $tools->append('<a href="' . admin_url('promotion/export') . '" class="btn btn-sm btn-success"><i class="fa fa-download"></i> 导出数据</a>');
        });

        // 添加统计行
        $grid->footer(function ($collection) {
            $query = Promotion::query();
            
            // 应用筛选条件
            if (request('推荐人/推荐码')) {
                $input = request('推荐人/推荐码');
                $query->where(function ($q) use ($input) {
                    $q->whereHas('referrer', function ($subQuery) use ($input) {
                        $subQuery->where('username', 'like', "%{$input}%");
                    })
                    ->orWhere('referral_code', 'like', "%{$input}%");
                });
            }
            
            if (request('reward_status') !== null && request('reward_status') !== '') {
                $query->where('reward_status', request('reward_status'));
            }
            
            if (request('registration_time.start') && request('registration_time.end')) {
                $query->whereBetween('registration_time', [request('registration_time.start'), request('registration_time.end')]);
            }
            
            // 获取统计数据
            $totalCount = (clone $query)->count();
            $totalAmount = (clone $query)->sum('reward_amount');
            
            $pendingQuery = (clone $query)->where('reward_status', 0);
            $pendingCount = $pendingQuery->count();
            $pendingAmount = $pendingQuery->sum('reward_amount');
            
            $issuedQuery = (clone $query)->where('reward_status', 1);
            $issuedCount = $issuedQuery->count();
            $issuedAmount = $issuedQuery->sum('reward_amount');
            
            $invalidQuery = (clone $query)->where('reward_status', 2);
            $invalidCount = $invalidQuery->count();
            $invalidAmount = $invalidQuery->sum('reward_amount');
            
            return "
            <div style='padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-top: 10px;'>
                <strong>统计信息：</strong>
                <span style='margin-left: 20px; color: #ffc107;'>待发放: {$pendingCount}笔 ¥" . number_format($pendingAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #28a745;'>已发放: {$issuedCount}笔 ¥" . number_format($issuedAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #dc3545;'>已失效: {$invalidCount}笔 ¥" . number_format($invalidAmount, 2) . "</span>
                <span style='margin-left: 20px; color: #007bff; font-weight: bold;'>总计: {$totalCount}笔 ¥" . number_format($totalAmount, 2) . "</span>
            </div>";
        });

        return $grid;
    }

    protected function statisticsGrid()
    {
        $grid = new Grid(new Promotion());

        // 使用原始查询来获取统计数据
        $grid->model()->select([
            'promotions.referred_by',
            'users.username as referrer_username',
            DB::raw('COUNT(*) as total_referrals'),
            DB::raw('SUM(promotions.reward_amount) as total_reward_amount'),
            DB::raw('SUM(CASE WHEN promotions.reward_status = 1 THEN promotions.reward_amount ELSE 0 END) as issued_amount'),
            DB::raw('SUM(CASE WHEN promotions.reward_status = 0 THEN promotions.reward_amount ELSE 0 END) as pending_amount'),
            DB::raw('SUM(CASE WHEN promotions.reward_status = 2 THEN promotions.reward_amount ELSE 0 END) as invalid_amount')
        ])
        ->join('users', 'promotions.referred_by', '=', 'users.id')
        ->groupBy('promotions.referred_by', 'users.username')
        ->orderBy('total_referrals', 'desc');

        $grid->column('referrer_username', __('推荐人'))->sortable();
        $grid->column('total_referrals', __('成功推荐人数'))->sortable();
        $grid->column('total_reward_amount', __('总奖励金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        })->sortable();
        $grid->column('issued_amount', __('已发放金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        })->sortable();
        $grid->column('pending_amount', __('待发放金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        })->sortable();
        $grid->column('invalid_amount', __('失效金额'))->display(function ($value) {
            return '¥' . number_format($value, 2);
        })->sortable();

        // 关闭操作列
        $grid->actions(function ($actions) {
            $actions->disableDelete();
            $actions->disableEdit();
            $actions->disableView();
        });

        // 关闭批量操作
        $grid->tools(function ($tools) {
            $tools->batch(function ($batch) {
                $batch->disableDelete();
            });
        });

        // 添加统计行
        $grid->footer(function ($collection) {
            $stats = Promotion::select([
                DB::raw('COUNT(DISTINCT referred_by) as total_referrers'),
                DB::raw('COUNT(*) as total_referrals'),
                DB::raw('SUM(reward_amount) as total_reward_amount'),
                DB::raw('SUM(CASE WHEN reward_status = 1 THEN reward_amount ELSE 0 END) as issued_amount'),
                DB::raw('SUM(CASE WHEN reward_status = 0 THEN reward_amount ELSE 0 END) as pending_amount'),
                DB::raw('SUM(CASE WHEN reward_status = 2 THEN reward_amount ELSE 0 END) as invalid_amount')
            ])->first();

            return "
            <div style='padding: 10px; background-color: #f5f5f5; border-radius: 4px; margin-top: 10px;'>
                <strong>总体统计：</strong>
                <span style='margin-left: 20px; color: #007bff;'>推荐人总数: {$stats->total_referrers}人</span>
                <span style='margin-left: 20px; color: #28a745;'>推荐总数: {$stats->total_referrals}笔</span>
                <span style='margin-left: 20px; color: #ffc107;'>总奖励金额: ¥" . number_format($stats->total_reward_amount, 2) . "</span>
                <span style='margin-left: 20px; color: #28a745;'>已发放: ¥" . number_format($stats->issued_amount, 2) . "</span>
                <span style='margin-left: 20px; color: #ffc107;'>待发放: ¥" . number_format($stats->pending_amount, 2) . "</span>
                <span style='margin-left: 20px; color: #dc3545;'>失效: ¥" . number_format($stats->invalid_amount, 2) . "</span>
            </div>";
        });

        return $grid;
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