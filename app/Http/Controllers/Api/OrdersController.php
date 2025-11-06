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

    // public function __construct(){
    //     parent::__construct();
 
    //     if (!empty($this->user_id)) {
    //         $this->user_id = 1002;
    //     }
    // }

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
        $query = Orders::query()
        ->where('user_id', $this->user_id);
        $this->adjustWhere($query,$request);
        if(empty($request->order_number)) {
            //如果不查订单号，就取消时间条件
            $query->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time);
        }
         
        
        //从登录的用户开始取出user_id
        // 加入分页功能
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
         
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
        
        // 如果需要获取总数，可以克隆另一个查询
        $query2 = Orders::query()
            ->where('user_id', $this->user_id)
            ->where('order_time', '>=', $start_time)
            ->where('order_time', '<=', $end_time)           
            ->whereIn('valid_code',[16,17,24]);
// dd("94>>>",$start_time,$end_time,$query2->toSql());   
        $statistics =[];
        $statistics['order_count']= (clone $query2)->count();  //
        $statistics['order_price']= (clone $query2)->sum('estimate_cos_price');
        $estimate_fee_count= (clone $query2)->sum('estimate_fee'); 
        // $statistics['estimate_fee_count']=  round($estimate_fee_count * 0.9,   2);   //不应x0。9 ，在同步商品时就直接x0.9了
        $statistics['estimate_fee_count']=  $estimate_fee_count ;
        //遍历lists，取出status_txt
        foreach($lists as $key => &$val) {
            $val['status_txt'] = $val->status_txt;
            $val['order_time'] = $val->order_time;
            // $val['estimate_fee2'] = round($val->estimate_fee * 0.9, 2);
            $val['estimate_fee2'] = $val->estimate_fee;
            $val['actual_fee2'] = $val->actual_fee;
        }
        
        return response()->json([
            'status' => 'success',
            'msg' => 'success',
            'lists' => $lists,
            'pagination' => $pagination,
            'statistics' => $statistics
        ]);

    }

    //“组合”或“整理”用英文翻译
    
    private function adjustWhere(&$query,$request){

        if (!empty($request->order_number)) {
            $query->where('order_id',$request->order_number);
            return  ;
        }
        // if (empty($request->valid_code_txt)) {
        //     $query->whereIn('valid_code',[16,17]);
        //     return  ;
        // }
        if(!empty($request->valid_code_txt) && $request->valid_code_txt!='全部') {
            if($request->valid_code_txt =='已付款') {
                $query->where('valid_code',16);
            }elseif($request->valid_code_txt =='已发货') {
                $query->where('express_status',20);
            }elseif($request->valid_code_txt =='已完成') {
                $query->where('valid_code',17);
            }
        } 

    }
    

}