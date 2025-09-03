<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
use App\Models\Orders;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrdersController extends ApiController
{
    public function index(Request $request) {
       
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
 
        if ($request->time_txt=="今天") {
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
        }elseif ($request->time_txt=="昨日") {
            $start_time = date('Y-m-d 00:00:00',strtotime('-1 day'));
            $end_time = date('Y-m-d 23:59:59',strtotime('-1 day'));
        }elseif ($request->time_txt=="近7天") {
            $start_time = date('Y-m-d 00:00:00',strtotime('-6 days'));
            $end_time = date('Y-m-d 23:59:59');
            
        }elseif ($request->time_txt=="近30天") {
            $start_time = date('Y-m-d 00:00:00',strtotime('-29 days'));
            $end_time = date('Y-m-d 23:59:59');
        }elseif ($request->time_txt=="本月") {
            $start_time = date('Y-m-01 00:00:00');
            $end_time = date('Y-m-d 23:59:59');
        }elseif($request->time_txt=="上月") {
            $start_time = date('Y-m-01 00:00:00', strtotime('last month'));
            $end_time = date('Y-m-t 23:59:59', strtotime('last month'));
        }elseif($request->time_txt=="自定义") {
            if (!$request->customize_startDate) {
                return  response()->json(['status' => 'error','msg' => '请选择开始时间']);
            }
            if (!$request->customize_endDate) {
                return  response()->json(['status' => 'error','msg' => '请选择结束时间']);
            }
            $start_time = $request->customize_startDate.' 00:00:00';
            $end_time = $request->customize_endDate.' 23:59:59';
        }
        // 全部，待付款，已付款，已发货，已完成，已取消，已失效
        $wherevalid_code = [];
        if (!empty($request->valid_code_txt) && $request->valid_code_txt!='全部') {
            if($request->valid_code_txt =='已付款') {
                $wherevalid_code = ['valid_code'=>16];
            }elseif($request->valid_code_txt =='已发货') {
                $wherevalid_code = ['express_status'=>20];
            }elseif($request->valid_code_txt =='已完成') {
                $wherevalid_code = ['valid_code'=>17];
            }elseif($request->valid_code_txt =='已取消') {
                $wherevalid_code = ['valid_code'=>3];
            }elseif($request->valid_code_txt =='已失效') {
                $wherevalid_code = ['valid_code'=>3];
            }         
        }

        $whereorder_number = [];
        if (!empty($request->order_number)) {
            $whereorder_number = ['order_id'=>$request->order_number];
        }
        
        //从登录的用户开始取出user_id
        // 加入分页功能
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $query = Orders::query()
            ->where('user_id', $this->user_id)
            ->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time)
            ->where($whereorder_number)
            ->where($wherevalid_code);

        // 克隆查询对象用于分页        
        $paginatedResult = (clone $query)->orderBy('order_time', 'desc')->paginate($pageSize, ['*'], 'page', $page);
        
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
        
        // 如果需要获取总数，可以克隆另一个查询
        $query2 = Orders::query()
            ->where('user_id', $this->user_id)
            ->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time)            
            ->whereIn('valid_code',[16,17]);
        $statistics =[];
        $statistics['order_count']= (clone $query2)->count();  //
        $statistics['order_price']= (clone $query2)->sum('total_price');
        $statistics['estimate_fee_count']= (clone $query2)->sum('estimate_fee'); 
        
        //遍历lists，取出status_txt
        foreach($lists as $key => &$val) {
            $val['status_txt'] = $val->status_txt;
            $val['order_time'] = $val->order_time;
            $val['estimate_fee2'] = round($val->estimate_fee * 0.9, 2);
        }
        
        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'lists' => $lists,
            'pagination' => $pagination,
            'statistics' => $statistics
        ]);

    }




}