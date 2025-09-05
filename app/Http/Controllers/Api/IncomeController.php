<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Models\Orders;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\UserCommissionSettlement;

//个人收益
class IncomeController extends ApiController
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $pageSize = 4;
        $query = UserCommissionSettlement::query()
            ->where('user_id', $request->input('user_id'))
            ->where('is_paid', 1);

        $paginatedResult = (clone $query)->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        $lists = $paginatedResult->items();

        $lists = collect($lists)->each(function ($item) {
            $item->updated_at_formatted = $item->updated_at_formatted;
            $item->created_at_formatted = $item->created_at_formatted;
            $item->is_paid_formatted = $item->is_paid_formatted;
            $item->countOrders = $this->countOrders($item->settlement_year . $item->settlement_month);
            return $item;
        });

        $pagination = [
            'current_page' => $paginatedResult->currentPage(),
            'last_page' => $paginatedResult->lastPage(),
            'per_page' => $paginatedResult->perPage(),
            'total' => $paginatedResult->total(),
            'from' => $paginatedResult->firstItem(),
            'to' => $paginatedResult->lastItem(),
            'has_more_pages' => $paginatedResult->hasMorePages()
        ];


        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'lists' => $lists,
            'pagination' => $pagination
        ]);
    }

    private function countOrders($order_year_month) {
        $start_time = $order_year_month . "01 00:00:00";
        $end_time = $order_year_month . "31 23:59:59";

        return Orders::query()
            ->where('user_id', $this->user_id)
            ->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time)
            ->whereIn('valid_code', [16, 17])
            ->count();
    }

    public function index2(Request $request)
    {
        // 将order_time字段转成年月,使用mysql的函数，分组列出
        $year_month_data = DB::select("
            SELECT DISTINCT DATE_FORMAT(order_time, '%Y-%m') AS order_time2 
            FROM orders  
            ORDER BY order_time2 DESC
        ");


        $order_month = [];    //订单的结算日期     
        foreach ($year_month_data as $item) {
            $order_month[] = $item->order_time2;
        }
    }

    public function orderlists(Request $request)
    {
        $settlementInfo = UserCommissionSettlement::query()
            ->where('id', $request->id)->first();
        if (!$settlementInfo) {
            return response()->json(['status' => 'error', 'msg' => '结算信息不存在']);
        }

        $start_time = $settlementInfo->settlement_year . $settlementInfo->settlement_month . "01 00:00:00";
        $end_time = $settlementInfo->settlement_year . $settlementInfo->settlement_month . "31 23:59:59";

        //从登录的用户开始取出user_id
        // 加入分页功能
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $query = Orders::query()
            ->where('user_id', $this->user_id)        
            ->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time)
            ->whereIn('valid_code', [16, 17]);

        if (!empty($request->keyword)) {
            $query->where('order_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('sku_name', 'like', '%' . $request->keyword . '%') ;
        }

        // 克隆查询对象用于分页        
        $paginatedResult = $query->orderBy('order_time', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        // 分离列表数据和分页信息
        $lists = $paginatedResult->items();
        $pagination = [
            'current_page' => $paginatedResult->currentPage(),
            'last_page' => $paginatedResult->lastPage(),
            'per_page' => $paginatedResult->perPage(),
            'total' => $paginatedResult->total(),
            'from' => $paginatedResult->firstItem(),
            'to' => $paginatedResult->lastItem(),
            'has_more_pages' => $paginatedResult->hasMorePages()
        ];


        //遍历lists，取出status_txt
        foreach ($lists as $key => &$val) {
            $val['status_txt'] = $val->status_txt;
            $val['order_time'] = $val->order_time;
            $val['finish_time'] = $val->finish_time;
            $val['estimate_fee2'] = round($val->estimate_fee * 0.9, 2);
        }

        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'lists' => $lists,
            'pagination' => $pagination
        ]);
    }



    public function sumorder(Request $request)
    {
        //上月
        // $sum_estimate_fee['last_month'] = Orders::query()
        //     ->whereBetween('order_time', [date('Y-m-01 00:00:00', strtotime('last month')), date('Y-m-t 23:59:59', strtotime('last month'))])
        //     // ->where('user_id', $this->user_id)
        //     ->whereIn('valid_code', [16, 17])
        //     ->sum('estimate_fee');
        // //本月
        // $sum_estimate_fee['this_month'] = Orders::query()
        //     ->whereBetween('order_time', [date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59')])
        //     // ->where('user_id', $this->user_id)
        //     ->whereIn('valid_code', [16, 17])
        //     ->sum('estimate_fee');


            // 上月已经收货、应得佣金
            $sum_estimate_fee['this_month'] = Orders::query()
            ->where('user_id', $this->user_id)
                ->where('valid_code', 17)
                ->whereBetween('order_time', [date('Y-m-01 00:00:00', strtotime('last month')), date('Y-m-t 23:59:59', strtotime('last month'))])
                ->sum('actual_fee');
           

            // 本月已经收货、应得佣金 
            // 修改为本月的统计
           $sum_estimate_fee['last_month'] = Orders::query()
           ->where('user_id', $this->user_id)
                ->where('valid_code', 17)
                ->whereBetween('order_time', [
                    date('Y-m-01 00:00:00'), 
                    date('Y-m-t 23:59:59')
                ])
                ->sum('actual_fee');
        

        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'data' => $sum_estimate_fee
        ]);
    }
}
