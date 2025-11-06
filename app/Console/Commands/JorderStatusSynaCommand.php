<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\JdOrderTimeSegment;
use App\Models\Orders;
use App\Services\JdGoodsSevice;

class JorderStatusSynaCommand extends Command
{
    protected $signature = 'jorder:status-syna';

    protected $description = '京东订单状态同步';

    public function handle()
    {
        $this->info('开始京东订单状态同步...,当前时间:'.date('Y-m-d H:i:s'));
        $this->synaJorderStatus();
        sleep(1);
        $this->info('京东订单状态同步完成...,结束时间:'.date('Y-m-d H:i:s'));
    }


    private function synaJorderStatus()
    {
        $startTime = date('Y-m-d 00:00:00', strtotime('-46 day'));  //46天前
        $endTime = date('Y-m-d 23:59:59');    // 小时前

        $jdOrderList = Orders::query()
        ->whereBetween('order_time', [$startTime, $endTime])        
        ->get();
        $jdGoodSevice = new JdGoodsSevice();
        foreach ($jdOrderList as $jdOrder) {
            try {
                // $jdOrder->order_id = '336672155556';
                $ret = $jdGoodSevice->orderIdQuery($jdOrder->order_id);
                if ($ret !== false && is_array($ret) && isset($ret['data'][0]['validCode']) && !empty($ret['data'][0]['validCode'])) {
                    $orderData = $ret['data'][0];                
                    $update = [];                
                    $update['modify_time'] = $orderData['modifyTime'];
                    $update['finish_time'] = !empty($orderData['finishTime']) ? $orderData['finishTime'] : null;
                    $update['valid_code'] = $orderData['validCode'];
                    $update['trace_type'] = $orderData['traceType'];
                    $update['commission_rate'] = $orderData['commissionRate'];
                    $update['sub_side_rate'] = $orderData['subSideRate'];
                    $update['subsidy_rate'] = $orderData['subsidyRate'];
                    $update['final_rate'] = $orderData['finalRate'];
                    $update['estimate_cos_price'] = $orderData['estimateCosPrice'];
                    $estimateFee_Rate = 0.9;
                    if ($orderData['commissionRate'] >5 ) {
                        $estimateFee_Rate = 0.8;
                    }
                    $update['estimate_fee'] = $orderData['estimateFee'] * $estimateFee_Rate ;
                    $update['actual_cos_price'] = $orderData['actualCosPrice'];
                    $update['actual_fee'] = $orderData['actualFee'] * $estimateFee_Rate ;
                    $update['updated_at'] = now();
                    Orders::query()->where("order_id",$jdOrder->order_id)->update($update);
                    echo "订单".$jdOrder->order_id."状态同步完成\n";
                    // die;
                }
            }catch(\Exception $e){
                echo "订单".$jdOrder->order_id."状态同步失败\n";
            }
        }

        
    }

    
}