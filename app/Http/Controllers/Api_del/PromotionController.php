<?php
namespace App\Http\Controllers\Api;

 
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\ApiCommand;
 
use App\Models\Promotion;
 
 
class PromotionController extends ApiCommand
{
    // 列表输出
    public function index(Request $request)
    {
         
        $page = $request->input('page', 1);
        $perPage = $request->input('pageSize', 15);
  
        $query = Promotion::with(['userInfo:id,username,phone', 'referrerInfo:id,username,phone'])->where('referred_by',$this->user_id);

        //搜索条件，订单号，订单状态，订单时间
        // if ($request->has('referral_code') && !empty($request->input('referral_code'))) {
        //     $query->where('referral_code', 'like', '%' . $request->input('referral_code') . '%');
        // }
        // if ($request->has('reward_status') && !empty($request->input('reward_status'))) {
        //     $query->where('reward_status', $request->input('reward_status'));
        // }
        // if ($request->has('start_date') && !empty($request->input('start_date'))) {
        //     $query->where('reward_time', '>=', $request->input('reward_time') . ' 00:00:00');
        // }
        // if ($request->has('end_date') && !empty($request->input('end_date'))) {
        //     $query->where('reward_time', '<=', $request->input('reward_time') . ' 23:59:59');
        // }
        if ($request->has('searchKeyword') && !empty($request->input('searchKeyword'))) {
            $keyword = $request->input('searchKeyword');
            $query->where(function($mainQuery) use ($keyword) {
                // 搜索被推荐用户信息
                $mainQuery->whereHas('userInfo', function ($query) use ($keyword) {
                    $query->where('username', 'like', '%' . $keyword . '%')
                          ->orWhere('phone', 'like', '%' . $keyword . '%');
                })
                // 搜索推荐人信息
                ->orWhereHas('referrerInfo', function ($query) use ($keyword) {
                    $query->where('username', 'like', '%' . $keyword . '%')
                          ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            });
        }
         
        $list = $query->orderBy('created_at','desc')->paginate($perPage,['*'],'page',$page);

        return response()->json(['status' => 'success','msg' => '获取推广记录成功','list'=>$list]);

    }
 


}