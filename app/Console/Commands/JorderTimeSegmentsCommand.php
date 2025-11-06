<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\JdOrderTimeSegment;
use App\Services\JdGoodsSevice;

class JorderTimeSegmentsCommand extends Command
{
    protected $signature = 'jorder:time-segments';

    protected $description = '京东订单时段查询';

    public function handle()
    {
 
        $this->info('开始京东订单时段查询...,当前时间:'.date('Y-m-d H:i:s'));
        $this->createJorderTimeSegments();
        sleep(1);
        $this->QueryJorderTimeSegments();
        
        $this->info('京东订单时段查询完成...,结束时间:'.date('Y-m-d H:i:s'));
    }

    private function createJorderTimeSegments() {
 
        // 整理数据，将start_time的时间字段超过48小时的，将deleted_at软标记为当前时间
        $jdOrderTimeSegments = JdOrderTimeSegment::where('start_time', '<', date('Y-m-d 00:00:00', strtotime('-24 hour')))->get();
     
        foreach ($jdOrderTimeSegments as $jdOrderTimeSegment) {
            $jdOrderTimeSegment->deleted_at = date('Y-m-d H:i:s');
            $jdOrderTimeSegment->save();
        }
        // 跟当前的小时加1小时，为结束时间

        //取最后的一条记录start_time字段
        $segmentInfo = JdOrderTimeSegment::orderBy('end_time', 'desc')->first();
        $last_end_time = $segmentInfo->end_time;
        $last_start_time = $segmentInfo->start_time;
        $new_end_time = date('Y-m-d H:0:00', strtotime('+1 hour'));
//  dd($last_end_time,$new_end_time,$last_start_time);        
        // if ($last_end_time >= $new_end_time) {
        //     //如果最后一条记录的end_time小于当前的小时，则将最后一条记录的end_time设置为当前的小时
        //     return false;
        // }
        // 将 $last_start_time 和 $end_time 按每小时分段，生成1小时一个的时段
        for ($i = strtotime($last_start_time); $i <= strtotime($new_end_time); $i += 3600) {
            // 取年月日+小时，格式为 2025-09-01 10:00:00
            $start_time2 = date('Y-m-d H:00:00', $i);
            // 计算end_time为start_time所在小时的59分59秒
            $end_time2 = date('Y-m-d H:59:59', $i);
            //存在
            $exists = JdOrderTimeSegment::where('segment_id', $start_time2.'_'.$end_time2)->first();
            if ($exists) {
                continue;
            }
            $jdOrderTimeSegments = JdOrderTimeSegment::create([
                'segment_id' => $start_time2.'_'.$end_time2,
                'start_time' => $start_time2,
                'end_time' => $end_time2,
                'status' => 0,
                'current_page' => 1,
                'retry_count' => 0,
                'last_query_time' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null,
            ]);

        }


    }

    private function QueryJorderTimeSegments() {
        echo "start QueryJorderTimeSegments >>> \n";
        $segments = JdOrderTimeSegment::where('status', 0)
        ->where('deleted_at', null)
        ->orderBy('start_time', 'desc')->get();

        if ($segments->count() == 0) {
            JdOrderTimeSegment::where('deleted_at', null)
            ->update(['status' => 0]);
            sleep(1);
            $segments = JdOrderTimeSegment::where('status', 0)
             ->where('deleted_at', null)
             ->orderBy('start_time', 'desc')->get(); 
        }

        foreach($segments as $segment) {
            $starTime = $segment->start_time;
            $pageIndex = $segment->current_page;
            $segment->updated_at = date('Y-m-d H:i:s');
            $segment->status = 1;
            $segment->save();
            $jdGoodSevice = new JdGoodsSevice();
            while(true) {                        
                $ret = $jdGoodSevice->orderQuery($starTime,$pageIndex);
                if ($ret['hasMore']==false) {
                    $segment->status = 2;
                    $segment->last_query_time = date('Y-m-d H:i:s');
                    $segment->updated_at = date('Y-m-d H:i:s');
                    $segment->save();
                    break;
                } 
                $pageIndex++;
                $segment->current_page = $pageIndex;
                $segment->last_query_time = date('Y-m-d H:i:s');
                $segment->updated_at = date('Y-m-d H:i:s');
                $segment->save();                             
            }
        }
        
    }



}