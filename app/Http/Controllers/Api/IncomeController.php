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
    // public function __construct(){
    //     parent::__construct();
 
    //     if (!empty($this->user_id)) {
    //         $this->user_id = 1002;
    //     }
    // }
    
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $pageSize = 4;
        $user_id = $this->user_id;        
        $query = UserCommissionSettlement::query()
            ->where('user_id', $user_id)
            ->where('is_paid', 1);

        $paginatedResult = (clone $query)->orderBy('created_at', 'desc')->paginate($pageSize, ['*'], 'page', $page);

        $lists = $paginatedResult->items();
 
        $lists = collect($lists)->each(function ($item) {
            $item->updated_at_formatted = $item->updated_at_formatted;
            $item->created_at_formatted = $item->created_at_formatted;
            $item->is_paid_formatted = $item->is_paid_formatted;
            $item->countOrders = $this->countOrders($item->settlement_year."-". $item->settlement_month);
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
        $start_time = date('Y-m-01 00:00:00', strtotime($order_year_month . '-01'));
        $end_time = date('Y-m-t 23:59:59', strtotime($order_year_month . '-01'));
//  dd("61,",$start_time,$end_time);
        return Orders::query()
            ->where('user_id', $this->user_id)            
            ->where('finish_time', '>=', $start_time)
            ->where('finish_time', '<=', $end_time)
            ->whereIn('valid_code', [17])
            ->count();
    }

    // public function index2(Request $request)
    // {
    //     // 将order_time字段转成年月,使用mysql的函数，分组列出
    //     $year_month_data = DB::select("
    //         SELECT DISTINCT DATE_FORMAT(order_time, '%Y-%m') AS order_time2 
    //         FROM orders  
    //         ORDER BY order_time2 DESC
    //     ");


    //     $order_month = [];    //订单的结算日期     
    //     foreach ($year_month_data as $item) {
    //         $order_month[] = $item->order_time2;
    //     }
    // }

    public function orderlists(Request $request)
    {
        $settlementInfo = UserCommissionSettlement::query()
            ->where('id', $request->id)->first();
        if (!$settlementInfo) {
            return response()->json(['status' => 'error', 'msg' => '结算信息不存在']);
        }

        $start_time = date('Y-m-01 00:00:00', strtotime($settlementInfo->settlement_year . '-' . $settlementInfo->settlement_month . '-01'));
        $end_time = date('Y-m-t 23:59:59', strtotime($settlementInfo->settlement_year . '-' . $settlementInfo->settlement_month . '-01'));
 
        //从登录的用户开始取出user_id
        // 加入分页功能
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $query = Orders::query()       
            ->where('user_id', $this->user_id)
            ->where('finish_time', '>=', $start_time)
            ->where('finish_time', '<=', $end_time)
            ->whereIn('valid_code', [17]);

        if (!empty($request->keyword)) {
            $query->where('order_id', 'like', '%' . $request->keyword . '%')
                ->orWhere('sku_name', 'like', '%' . $request->keyword . '%') ;
        }

        // 克隆查询对象用于分页        
        $paginatedResult = $query->orderBy('finish_time', 'desc')->paginate($pageSize, ['*'], 'page', $page);

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
            $val['estimate_fee2'] = $val->estimate_fee;
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
        $user_id = $this->user_id;
        // 上月已经收货、应得佣金
        $total = Orders::totalOrderActualFee(
            $user_id,
            date('Y-m-01 00:00:00', strtotime('last month')),
            date('Y-m-t 23:59:59', strtotime('last month'))
        );

        $sum_estimate_fee['this_month'] = $total['sum_actual_fee'];
        // 本月已经收货、应得佣金 
        $total = Orders::totalOrderActualFee(
            $user_id,
            date('Y-m-01 00:00:00'),
            date('Y-m-t 23:59:59')
        );
        $sum_estimate_fee['last_month'] = $total['sum_actual_fee'];

        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'data' => $sum_estimate_fee
        ]);
    }
}
