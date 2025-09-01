<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use App\Models\User;
use App\Models\Orders;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('仪表盘')
            ->description('系统概览')
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $column->append(view('admin.charts.dashboard_user_order', [
                        'userCount' => User::count(),
                        'orderCount' => Orders::count(),
                        'todayUsers' => User::whereDate('created_at', Carbon::today())->count(),
                        'yesterdayUsers' => User::whereDate('created_at', Carbon::yesterday())->count(),
                        'dayBeforeYesterdayUsers' => User::whereDate('created_at', Carbon::today()->subDays(2))->count(),
                        'lastWeekUsers' => User::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count(),
                        'todayOrders' => Orders::whereDate('created_at', Carbon::today())->count(),
                        'yesterdayOrders' => Orders::whereDate('created_at', Carbon::yesterday())->count(),
                        'dayBeforeYesterdayOrders' => Orders::whereDate('created_at', Carbon::today()->subDays(2))->count(),
                        'lastWeekOrders' => Orders::whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->count(),
                    ]));
                });
            });
    }
}
