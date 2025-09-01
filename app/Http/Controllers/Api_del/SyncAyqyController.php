<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiCommand;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\GlobalConfig;

class SyncAyqyController extends ApiCommand
{
    // 定义加价比例
    private static $markup_ratio = 1.1; // 10% 加价，可以根据需要调整
 
    public function __construct() {
        $markup_ratio =  GlobalConfig::where("key","markup_ratio")->value('value');
         
        self::$markup_ratio = intval($markup_ratio)>0 ? $markup_ratio : 1;
         
    }

    public function SyncProduct() 
    {
        //加入不限php响应时间
        set_time_limit(0);
        $signService = new \App\Services\AyqySignService();
       
        $data = $signService->httpGetApi("/api/getGoods.htm",[]);
        $allPage = $data['allPage'];
        echo '同步卡券【商品】第1页成功!!!\n';
       
        self::__StoreProduct($data['data']);
        echo '同步卡券【商品】第1页成功!!!\n';
 
        for($i=2;$i<=$allPage;$i++) {
            $data = $signService->httpGetApi("/api/getGoods.htm",['page'=>$i,'dirType'=>1]);
            // Log::info('同步卡券【商品】第'.$i.'页成功!!!');
            // Log::info($data);
            
            if ($data['data']) {
                self::__StoreProduct($data['data']);
            }
            echo '同步卡券【商品】第'.$i.'页成功!!!\n';
            
        }
        echo '同步卡券【商品】全部成功!!!\n';
        // $this->SyncProductDetail();
        // echo '同步卡券【商品详情】全部成功,系统退出 !!!\n';
        exit;
    }

    private static function __StoreProduct($saveList) {
     
        // 批量 upsert（有则更新，无则新增）
        foreach($saveList as $key => &$row) {
            if (is_array($row['dirIds'])) {
                $arr = $row['dirIds'];             
                $row['dirId1'] = $arr[0] ?? 0;
                $row['dirId2'] = $arr[1] ?? 0;
                $row['dirId3'] = $arr[2] ?? 0;
                $row['dirIds'] = implode(',', $arr);
            } elseif (empty($row['dirIds'])) {
                $row['dirIds'] = '';
            }
            $row['platform_price'] = $row['price'] * self::$markup_ratio;
        }
       
        \App\Models\Products::upsert(
            $saveList,
            ['id'], // 唯一键
            ['price', 'name', 'money', 'day', 'type', 'multiple', 'status', 'isRepeat', 'skuType', 'dirIds', 'imgUrl','isRefOrder','isRefMoney','keyId','number','platform_price']
        );
    }
   
    public function SyncProductDetail() {
        set_time_limit(0);
        $signService = new \App\Services\AyqySignService();
        //******************* Test */
        // $productId = 11794;
        // $data = $signService->httpGetApi("/api/getGood.htm",['id'=>$productId]);
        // dd("92 >>>",$productId,$data);
        //*************************************  */
        // $data = $signService->httpGetApi("/api/getGoods.htm",[]);
        $ids = \App\Models\Products::where('status',1)->pluck('id');  //只取字段id值

        foreach($ids as $productId) {
            $data = $signService->httpGetApi("/api/getGood.htm",['id'=>$productId]);
            // Log::info("SyncProductDetail 96 >>>>>>>>>>>");
            // Log::info($productId,$data);
            // dd("92 >>>",$productId,$data);
            //先删除id为$productId的记录
            \App\Models\ProductDetail::where('id',$productId)->delete();
            if (empty($data['data'])) {
                
                continue;
            }
            $productDetail = $data['data'];
            foreach($productDetail as $key => $row) {
                if (isset($row['imgs']) && is_array($row['imgs'])) {
                    $row['imgs'] = json_encode($row['imgs']);
                }
                if (isset($row['skus']) && is_array($row['skus'])) {
                    $row['skus'] = json_encode($row['skus']);
                }
                if (isset($row['templates']) && is_array($row['templates'])) {
                    $row['templates'] = json_encode($row['templates']);
                }
                if (isset($row['skuDetails']) && is_array($row['skuDetails'])) {
                    $row['skuDetails'] = json_encode($row['skuDetails']);
                }
    
                if (isset($row['specs']) && is_array($row['specs'])) {
                    $row['specs'] = json_encode($row['specs']);
                }
                if (isset($row['brands']) && is_array($row['brands'])) {
                    $row['brands'] = json_encode($row['brands']);
                }
                if (isset($row['discounts']) && is_array($row['discounts'])) {
                    $row['discounts'] = json_encode($row['discounts']);
                }
                
                //再插入
                \App\Models\ProductDetail::insert($row);
                
            echo '同步卡券【商品详情】'.$productId.'成功!!!\n';
            Log::info('同步卡券【商品详情】'.$productId.'成功!!!');
            sleep(1);
            }
        }
        echo '同步卡券【商品详情】全部成功!!!\n'; 
        Log::info('同步卡券【商品详情】全部成功!!!');
    }



}

