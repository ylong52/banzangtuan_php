<?php
namespace App\Admin\Controllers;

use App\Http\Controllers\ApiCommand;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\SurveyRecord;
use App\Models\SamplicioSurveyResponse;
use App\Models\TopRankingList;
use App\Models\Refund;
use App\Models\HistoricalSurveyStatistics;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
// 统计
class BorderController extends AdminController
{   

    public function index(\Encore\Admin\Layout\Content $content)
    {
        $data = [];     
        return $content->title('数据统计仪表板')
            ->description('数据统计仪表板')
            ->view('admin.border.index', $data);
    }
    

    /*
    * 统计每个用户的答题数
    */
    public function getUserTopTotal(Request $request) 
    {
        $pageSize = $request->input('pagesize', 15); // 默认每页10条数据
        $topRankingList = TopRankingList::with(['user','user.team'])
        ->orderBy('completed_amount', 'desc')
        ->orderBy('completed_copies', 'desc')
        ->paginate($pageSize);
        // 第42行 - getUserTopTotal方法
        if ($topRankingList->isEmpty()) {
            return response()->json(['status' => false, 'message' => '没有数据']);
        } else {
            return response()->json(['status' => true, 'data' => $topRankingList]);
        }
    }

    /*
    * 对survey_number进行统计
    */
    public function getSurveyNumberTotal(Request $request) 
    {
        $pageSize = $request->input('pagesize', 15); // 默认每页10条数据
        $list = HistoricalSurveyStatistics::paginate($pageSize);
        if ($list->isEmpty()) {
            return response()->json(['status' => false, 'message' => '没有数据']);
        } else {
            return response()->json(['status' => true, 'data' => $list]);
        }
    }

    public function getSurveyNumberIdxUsers(Request $request) {
        $page = $request->input('page', 1); // 默认第一页
        $pageSize = $request->input('pagesize', 15); // 默认每页10条数据
        $survey_number = $request->input('survey_number');
        if (!$survey_number) {  
            return response()->json(['status' => false, 'message' => '参数错误']);
        }

        $query = SurveyRecord::join('samplicio_survey_response', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
            ->where('samplicio_survey_response.survey_number', 'like', $survey_number.'%')
            ->where('surveyrecord.response_flag', 1)
            ->where('samplicio_survey_response.survey_status', 1)
            ->groupBy('surveyrecord.user_id');

        // 获取总数用于分页
        $total = (clone $query)->count();        
        // 分页查询
        $userlist =(clone $query)->select('user_id')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->pluck('user_id')
            ->toArray();
 
        $data = self::byUsersTotal($survey_number,$userlist);
        //要返回分页数据
        return response()->json(['status' => true, 'data' => [
            'total' => $total,
            'list' => $data
        ]]);
        
    }

    //分人统计
    private static function byUsersTotal($survey_number, $userlist) {
        $query = SurveyRecord::join('samplicio_survey_response', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
            ->where('samplicio_survey_response.survey_number', 'like', $survey_number.'%')
            ->where('surveyrecord.response_flag', 1)     
            ->where('samplicio_survey_response.survey_status', 1)
            ->whereIn('surveyrecord.user_id', $userlist)
            ->groupBy('surveyrecord.user_id')
            ->select('surveyrecord.user_id', DB::raw('count(*) as total'));
    
        $todayResults = (clone $query)   //当天数据
            ->whereDate('surveyrecord.created_at', now()->toDateString())
            ->get()
            ->keyBy('user_id');
    
        $allResults = (clone $query)->get()->keyBy('user_id');
    
        $arr = [];
        foreach ($userlist as $userId) {
            $userInfo = User::with('team')->find($userId);
            $arr[] = [
                'user_id' => $userId,
                'user_name' => $userInfo->name,
                'team_name' => $userInfo->team->name,
                'today' => $todayResults->has($userId) ? $todayResults[$userId]->total : 0,
                'all' => $allResults->has($userId) ? $allResults[$userId]->total : 0
            ];
        }
    
        return $arr;
    }

    public function yieldtotal() {
        //先统计出退款数
        // $usersTypeBay = self::refundTotalAmount('day');    //
        //统计产出
        $data = [];
        $query = SurveyRecord::join('samplicio_survey_response', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
        ->whereDate('surveyrecord.created_at', now()->toDateString())
        ->where('surveyrecord.response_flag', 1)
        ->where('samplicio_survey_response.survey_status', 1);
   
        //日产出: 当天的所有人的总业绩
        $daySumAmount = (clone $query)->sum('samplicio_survey_response.rpi_commission_amount') - self::refundTotalAmount('day'); // 减去退款金额;
        $data['dayRPiCommissionSumAmount'] = ['value'=>round($daySumAmount,2),'label'=>'日会员产出'];

        //日产出: 当天的所有人的总业绩
        $daySumAmount = (clone $query)->sum('samplicio_survey_response.rpi') - self::refundTotalAmount('day'); // 减去退款金额;
        $data['daySumAmount'] = ['value'=>round($daySumAmount,2),'label'=>'日RPI产出'];

        //日份数: 当天所有人完成调查的总份数
        $dayCopies =  SurveyRecord::whereDate('surveyrecord.created_at', now()->toDateString())
            ->where('surveyrecord.response_flag', 1)
            ->count();
        $data['dayCopies'] = ['value'=>$dayCopies,'label'=>'日份数'];

        //日点击: 当天所有人的总点击数
        $dayClicks =  SurveyRecord::whereDate('surveyrecord.created_at', now()->toDateString())
         ->count();
        $data['dayClicks'] = ['value'=>$dayClicks,'label'=>'日点击'];

        //日平均: 当天所有人完成的平均单价，日产出/日份数
        if ($dayCopies == 0) {
            $dayAverage = 0;
        } else {
            $dayAverage = round($daySumAmount / $dayCopies, 2);
        }
        $data['dayAverage'] = ['value'=>$dayAverage,'label'=>'日平均'];

        //6,日EPC
        if ($dayClicks == 0) {
            $data['dayEPC'] = ['value'=>0,'label'=>'日EPC'];
        } else {
            $data['dayEPC'] = ['value'=>round($daySumAmount / $dayClicks,2),'label'=>'日EPC'];
        }

        if ($dayCopies == 0) {
            $daySuccessRate = 0;
        } else {
            $daySuccessRate = round($dayCopies / $dayClicks, 2);
        }
        $data['daySuccessRate'] = ['value'=>$daySuccessRate,'label'=>'日成功率'];

        //------- 月产出 ----------------------------
        $query = SurveyRecord::join('samplicio_survey_response', 'surveyrecord.mid', '=', 'samplicio_survey_response.mid')
            ->whereBetween('surveyrecord.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('surveyrecord.response_flag', 1)
            ->where('samplicio_survey_response.survey_status', 1);

        $monthRpiCommissionSumAmount = (clone $query)->sum('samplicio_survey_response.rpi_commission_amount') - self::refundTotalAmount('month'); // 减去退款金额;
        $data2['monthSumAmount'] = ['value'=>round($monthRpiCommissionSumAmount, 2),'label'=>'月会员价格'];
        // 月产出: 当月的所有人的总业绩
        $monthSumAmount = (clone $query)->sum('samplicio_survey_response.rpi') - self::refundTotalAmount('month'); // 减去退款金额;
        $data2['monthSumAmount'] = ['value'=>round($monthSumAmount, 2),'label'=>'月RIP价格'];
        // 月份数: 当月所有人完成调查的总份数
        $monthCopies =  SurveyRecord::whereBetween('surveyrecord.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('surveyrecord.response_flag', 1)
            ->count();
        $data2['monthCopies'] = ['value'=>$monthCopies,'label'=>'月份数'];    
        // 月点击: 当月所有人的总点击数 （就是点击那5个调查链接的数量）
        $monthClicks =  SurveyRecord::whereBetween('surveyrecord.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $data2['monthClicks'] = ['value'=>$monthClicks,'label'=>'月点击'];
        // 月平均: 当月所有人完成的平均单价，月产出/月份数
        if ($monthCopies == 0) {
            $monthAverage = 0;
        } else {
            $monthAverage = round($monthSumAmount / $monthCopies, 2);
        }
        //5.月平均
        $data2['monthAverage'] = ['value'=>$monthAverage,'label'=>'月平均'];
       
        //6.月EPC
        if ($monthClicks == 0) {
            $data2['monthEPC'] = ['value'=>0,'label'=>'月EPC'];
        } else {
            $data2['monthEPC'] = ['value'=>round($monthSumAmount / $monthClicks,2),'label'=>'月EPC'];
        }
        // 7.月成功率: 当月所有人的总成功率，月份数/月点击 
        if ($monthClicks == 0) {
            $monthSuccessRate = 0;
        } else {
            $monthSuccessRate = round($monthCopies / $monthClicks, 2);
        }
        $data2['monthSuccessRate'] = ['value'=>$monthSuccessRate,'label'=>'月成功率'];
        //------------总产出
        
        // 所有时间加起来，所有人的总业绩
        $totalSumAmount = SamplicioSurveyResponse::sum('samplicio_survey_response.rpi') - self::refundTotalAmount('all');
        $data3['totalSumAmount'] = ['value'=>round($totalSumAmount,2),'label'=>'总产出'];
        // 总份数: 所有人完成调查的总份数
        $totalCopies =  SurveyRecord::where('surveyrecord.response_flag', 1)
            ->count();
        $data3['totalCopies'] = ['value'=>$totalCopies,'label'=>'总份数'];
        //总点击: 所有时间加起来，所有人的总点击数量
        $totalClicks =  SurveyRecord::count();
        $data3['totalClicks'] = ['value'=>$totalClicks,'label'=>'总点击'];
       
        //总成功率: 所有时间加起来，所有人的总成功率。总份数/总点击
        if ($totalClicks == 0) {
            $totalSuccessRate = 0;
        } else {
            $totalSuccessRate = round($totalCopies / $totalClicks, 2);
        }
        $data3['totalSuccessRate'] = ['value'=>$totalSuccessRate,'label'=>'总成功率'];

        //去掉$data的key
        $data = array_values($data);
        $data2 = array_values($data2);
        $data3 = array_values($data3);
        return response()->json(['status' => true, 'data' => [$data,$data2,$data3]]);
    }

    public static function refundTotalByUserIdAndTime($type) {
        //分人分时间统计退款金额
        // $type = $request->input('type', 'month'); // 默认为按天统计
        $query = SurveyRecord::leftJoin('refund', function($join) {
            $join->on('surveyrecord.mid', '=', 'refund.mid') ;
        })->select("surveyrecord.user_id");
        
        if ($type === 'day') {
            $query->whereDate('refund.complete_time', now()->toDateString());
        } else if ($type === 'month') {
            $month = now()->format('Y-m'); // 当前年月
            list($year, $month) = explode('-', $month);            
            $query->whereYear('refund.complete_time', $year)
               ->whereMonth('refund.complete_time', $month);                
        }
        
        $userIds = $query->groupBy('surveyrecord.user_id')
            ->pluck('user_id')
            ->toArray();
 
        $data = [];
        foreach ($userIds as $userId) {
            //  一个 surveyrecord.mid 可能对应多个 refund.mid （一对多关系）
            // - 一个 surveyrecord.mid 对应一个 samplicio_survey_response.mid
            $query = SurveyRecord::leftJoin('refund', function($join) {
                $join->on('surveyrecord.mid', '=', 'refund.mid');
            })
            ->join('samplicio_survey_response', 'samplicio_survey_response.mid', '=', 'surveyrecord.mid') // 改为直接关联surveyrecord
            ->where('surveyrecord.user_id', $userId)
            ->select(["surveyrecord.mid","surveyrecord.user_id","samplicio_survey_response.rpi_commission_amount","refund.rpi","refund.complete_time"])
            ->distinct(); // 添加distinct避免重复
       
            //refund.rpi是rpi的佣金，不是会员价格
            if ($type == 'day') {
                $query->whereDate('refund.complete_time', now()->toDateString());
            } else if ($type == 'month') {
                $month = now()->format('Y-m'); // 默认当前年月
                list($year, $month) = explode('-', $month);
                $query->whereYear('refund.complete_time', $year)
                    ->whereMonth('refund.complete_time', $month);
            }

            $list = $query->get()->toArray();
           
            if (!$list) {
                continue;
            }

            foreach ($list as $item) {
                if ($item['rpi'] >= 0) {
                    // $data[$item['user_id']] = ['amount'=> 0 + $item['rpi_commission_amount'],'complete_time'=> $item['complete_time'],'type'=>$type ];
                    $data[$item['user_id']] =  0 + $item['rpi_commission_amount'];
                } else if ($item['rpi'] <= 0) {
                    $data[$item['user_id']] = 0 - $item['rpi_commission_amount'];
                }
            }

        }

        return $data;                
    }



    public static function refundTotalAmount($type) {
        //不分人，只分时间统计退款金额
        // type = month,day
        // $type = $request->input('type', 'month'); // 默认为按天统计
        $query = SurveyRecord::leftJoin('refund', function($join) {
            $join->on('surveyrecord.mid', '=', 'refund.mid');
        })
        ->join('samplicio_survey_response', 'samplicio_survey_response.mid', '=', 'surveyrecord.mid') // 改为直接关联surveyrecord
        ->select(["refund.rpi","samplicio_survey_response.rpi_commission_amount"])
        ->distinct(); // 添加distinct避免重复
   
        //refund.rpi是rpi的佣金，不是会员价格
        if ($type == 'day') {
            $query->whereDate('refund.complete_time', now()->toDateString());
        } else if ($type == 'month') {
            $month = now()->format('Y-m'); // 默认当前年月
            list($year, $month) = explode('-', $month);
            $query->whereYear('refund.complete_time', $year)
                ->whereMonth('refund.complete_time', $month);
        }

        $list = $query->get()->toArray();
        $data = 0;
        foreach ($list as $item) {
            if ($item['rpi'] >= 0) {
                $data = $data + $item['rpi_commission_amount'];
            } else if ($item['rpi'] <= 0) {
                $data = $data - $item['rpi_commission_amount'];
            }
        }
        return $data;
                
    }


}