<?php

namespace App\Http\Controllers\Api\h5;
use Illuminate\Http\Request;
use App\Services\JdUnionClient;
use App\Models\User;
use App\Models\Orders;
use App\Models\Goods;
use App\Models\LotteryPrize;
use App\Models\LotteryCodes;
use App\Models\LotteryDrawrecords;
use App\Services\JdGoodsSevice;
use Illuminate\Support\Facades\DB;

/*
h5 抽奖接口
不用登录的接口
*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

class LotteryApiController extends H5BaseController
{
   
    public function __construct()
    {        
  
    }

    /*
    奖品列表
    不分页，只输出状态是status=1
    */
    public function getLotteryPrizeList() {
        try {
            // 查询所有启用的奖品（status=1），按奖品等级升序排列
            $cacheKey = 'lottery_prize_list_enabled_v1';
            $cacheTtl = 150; // 2分钟，单位：秒

            $prizeList = cache()->remember($cacheKey, $cacheTtl, function () {
                $prizes = LotteryPrize::enabled()
                    ->orderBy('prize_level', 'asc')
                    ->limit(8)
                    ->get();

                // 格式化返回数据
                return $prizes->map(function ($prize) {
                    return [
                        'id' => $prize->id,
                        'prize_name' => $prize->prize_name,
                    ];
                });
            });

            return response()->json([
                'code'=>200,
                'status' => 'success',
                'msg' => '获取奖品列表成功',
                'data' => $prizeList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code'=>500,
                'status' => 'error',
                'msg' => '获取奖品列表失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 开奖    
     */
    public function openLottery(Request $request) {
        /**
         * 开奖
         * 传入开奖码，订单号
         * 1，开奖码是8位数，字母数字混编，小写的。要在表内检查是否存在
         * 2，订单号，要在订单表内查找是否存在，并找出订单号对应的User_id. 无结果就返回错误信息
         * 3，如果开奖码和订单号都存在，则开始开奖，开奖的业务逻辑不是算随机数。是从表lottery_codes表符合lottery_code找出prize_level值。然后在lottery_prize表，找到prize_level对应的奖品信息。就表示中奖了。中奖的奖品信息要写入开奖记录表。
         * 4，开奖结果要写入开奖记录表。将中奖码表lottery_codes记录的状态标记为0（0表示禁用记录)
         * 5，返回给前端开奖结果，并更新奖品表内的中奖状态
         * 6，如果开奖码和订单号都不存在，则返回错误信息
         */

        try {
            $lottery_code = $request->input('lottery_code');
            $order_id = $request->input('order_number');

            // 1. 处理开奖码格式
            // 1.1 先将开奖码转为小写（统一格式）
            $processed_lottery_code = strtolower(trim($lottery_code));
            
            // 1.2 检查格式：8位且字母数字混编
            if (strlen($processed_lottery_code) != 6) {
                return response()->json([
                    'code'=>500,
                    'status' => 'error',
                    'msg' => '开奖码必须是6位'
                ]);
            }
                                  
            // 2. 检查开奖码是否存在
            $lotteryCodeRecord = LotteryCodes::byCode($processed_lottery_code)->first();
            if (!$lotteryCodeRecord) {
                return response()->json([
                    'code'=>500,
                    'status' => 'error',
                    'msg' => '开奖码不存在或已经使用'
                ]);
            }
            
            // 3. 检查订单号是否存在并获取用户ID
            $orderRecord = Orders::where('order_id', $order_id)->first();
            // if (!$orderRecord) {
            //     return response()->json([
            //         'code'=>500,
            //         'status' => 'error',
            //         'msg' => '订单号不存在'
            //     ]);
            // }

            # 在lottery_drawrecords表内检查是否存在该订单号
            $lotteryDrawRecord = LotteryDrawrecords::where('order_no', $order_id)->first();
            if ($lotteryDrawRecord && $lotteryDrawRecord->id > 0) {
                return response()->json([
                    'code'=>500,
                    'status' => 'error',
                    'msg' => '该订单号或抽奖码已经使用！请勿重复抽奖'
                ]);
            }

            $lotteryDrawRecord = LotteryDrawrecords::where('lottery_code', $processed_lottery_code)
            ->first();           
            if ($lotteryDrawRecord && $lotteryDrawRecord->id > 0) {
                //历史订单或开奖码已开奖过
                // return response()->json([
                //     'code'=>200,
                //     'status' => 'success',
                //     'msg' => '该订单号或开奖码已开奖过了，请勿重复开奖',
                //     'data' => [
                //         'order_no' => $order_id,
                //         'lottery_code' => $processed_lottery_code,                      
                //         'prize_name' => $lotteryDrawRecord->prize_name,
                //         'draw_time' => $lotteryDrawRecord->draw_time,
                //         'is_history' =>1, #1表示已开奖过
                //     ]
                // ]);
                return response()->json([
                    'code'=>500,
                    'status' => 'error',
                    'msg' => '该订单号或抽奖码已经使用！请勿重复抽奖'
                ]);
            }
            
            $user_id =0;
            if ($orderRecord) {
                $user_id = $orderRecord->user_id;
            }
            
            // 4. 开始开奖逻辑
            // 4.1 获取该开奖码对应的奖项等级
            $prize_level = $lotteryCodeRecord->prize_level;
        
            // 4.2 查询奖项等级对应的奖品信息（status=1表示有效）
            $lotteryPrize = LotteryPrize::where('prize_level', $prize_level)
                ->where('status', LotteryPrize::STATUS_ENABLED)
                ->first();
    
           
                
            // 4.3 判断是否中奖（有奖品信息即为中奖）
            $lottery_prize_id = $lotteryPrize ? $lotteryPrize->id : null;
            
            // 5. 写入开奖记录表
            $draw_time = now();
            $drawRecordData = [
                'user_id' => $user_id,
                'order_no' => $order_id,
                'lottery_code' => $processed_lottery_code,
                'prize_name' =>  $lotteryPrize ? $lotteryPrize->prize_name : null,
                'prize_level' => $prize_level,
                'lottery_prize_info' => $lotteryPrize ? json_encode($lotteryPrize, JSON_UNESCAPED_UNICODE) : null,
                'order_id_exist' => $orderRecord ? 1 : 0,
                'draw_time' => $draw_time,
                'is_won' => $lotteryPrize ? 1 : 0
            ];
            LotteryDrawrecords::create($drawRecordData);
            
            // 6. 标记该开奖码为禁用（status=0）
            $lotteryCodeRecord->status = LotteryCodes::STATUS_DISABLED;
            $lotteryCodeRecord->save();
            
            ##返回未中奖
            if (!$lotteryPrize || $prize_level == LotteryCodes::PRIZE_LEVEL_EIGHTH ) {

                return response()->json([
                    'code'=>200,
                    'status' => 'error',
                    'msg' => '未中奖，欢迎下次再来',
                    'data' => [
                        'order_no' => $order_id,
                        'lottery_code' => $processed_lottery_code,
                        'prize_name' => $lotteryPrize ? $lotteryPrize->prize_name : null,
                        'draw_time' => $draw_time->format('Y-m-d H:i:s'),
                        'is_won' => 0
                    ]
                ]);
            } else {
                // 7. 返回开奖结果
                return response()->json([
                    'code'=>200,
                    'status' => 'success',
                    'msg' => '开奖成功',
                    'data' => [
                        'order_no' => $order_id,
                        'lottery_code' => $processed_lottery_code,
                        'lottery_prize_id' => $lottery_prize_id,
                        'prize_name' => $lotteryPrize ? $lotteryPrize->prize_name : null,
                        'draw_time' => $draw_time->format('Y-m-d H:i:s'),
                        'is_won' => 1
                    ]
                ]);
            }
                    
            
        } catch (\Exception $e) {
            return response()->json([
                'code'=>500,
                'status' => 'error',
                'msg' => '开奖失败：' . $e->getMessage()
            ]);
        }

    }

    /**
     * 辅助方法：判断字符串是否同时包含小写字母和数字
     *
     * @param string $str
     * @return bool
     */
    private function containsLetterAndNumber($str)
    {
        $hasLetter = preg_match('/[a-z]/', $str);
        $hasNumber = preg_match('/[0-9]/', $str);
        return $hasLetter && $hasNumber;
    }



    public function queryPrize(Request $request) {
        try {
            $query = $request->input('query');
            
            // 如果关键字为空，返回错误
            if (empty($query)) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'msg' => '查询关键字不能为空'
                ], 400);
            }
            
            // 对 order_no 和 lottery_code 使用绝对查询（精确匹配），使用 whereOr
            $list = LotteryDrawrecords::where('order_no', $query)
                ->orWhere('lottery_code', $query)
                ->select('order_no', 'lottery_code', 'prize_name', 'draw_time')
                ->get();
            
            // 如果 list 为空，返回 json 错误
            if ($list->isEmpty()) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'msg' => '未找到相关开奖记录'
                ], 404);
            }
            
            // 否则返回 json 和 data 数组（未分页管理）
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'msg' => '查询成功',
                'data' => $list
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'msg' => '查询失败：' . $e->getMessage()
            ], 500);
        }
    }



}