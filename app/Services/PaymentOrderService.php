<?php
namespace App\Services;
use Illuminate\Support\Facades\Log;
use App\Services\FeixpayRsaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use App\Models\GlobalConfig;
use App\Models\DynamicProperty;
use App\Models\RechargeRecord;
/*
微信
支付宝
充值管理 
*/
class PaymentOrderService
{

    protected $orderCreateUrl = 'https://api.feixpay.cn/payment.order/create';
  
        protected $AlipayAccount = [];
        protected $WechatAccount = [];
        protected $error = null;
        protected $request = null;

        public function __construct(){
            $feixapyWechats = DynamicPropertyService::getAllByTypeTag('feixpay-wechat');
            $feixpayAlipys = DynamicPropertyService::getAllByTypeTag('feixpay-alipy');

            // $this->AlipayAccount['ins_id'] = 'M932072923';
            // $this->AlipayAccount['app_id'] = 'APP_199522066165';
            // $this->AlipayAccount['sp_sn'] = 'S328998652';
            // $this->AlipayAccount['mch_channel_id'] = 6897;
            // $this->AlipayAccount['in_code'] = 'dinpay';
            // $this->AlipayAccount['md5_key'] = 'ce9aa0012f8ba33e7a2e13a72fd5e6c6';
    
            // $this->WechatAccount['ins_id'] = 'M932072923';
            // $this->WechatAccount['app_id'] = 'APP_855257620967';
            // $this->WechatAccount['sp_sn'] = 'S598786759';
            // $this->WechatAccount['mch_channel_id'] = 6897;
            // $this->WechatAccount['in_code'] = 'dinpay';
            // $this->WechatAccount['md5_key'] = 'af381e7c55a9720778752c6d7abf3951';
            //way_code必传 https://www.yuque.com/hlwl/cziks/egc1ali4h0t478ay#wjQzf
            $this->AlipayAccount['ins_id'] = $feixpayAlipys['ins_id'] ;
            $this->AlipayAccount['app_id'] = $feixpayAlipys['app_id'] ;
            // $this->AlipayAccount['sp_sn'] = $feixpayAlipys['sp_sn'] ;
            // $this->AlipayAccount['mch_channel_id'] = $feixpayAlipys['mch_channel_id'] ;
            // $this->AlipayAccount['in_code'] = $feixpayAlipys['in_code'] ;
            $this->AlipayAccount['md5_key'] = $feixpayAlipys['md5_key'] ;
            $this->AlipayAccount['way_code'] = $feixpayAlipys['way_code'] ;

            $this->WechatAccount['ins_id'] = $feixapyWechats['ins_id'] ;
            $this->WechatAccount['app_id'] = $feixapyWechats['app_id'] ;
            // $this->WechatAccount['sp_sn'] = $feixapyWechats['sp_sn'] ;
            // $this->WechatAccount['mch_channel_id'] = $feixapyWechats['mch_channel_id'] ;
            // $this->WechatAccount['in_code'] = $feixapyWechats['in_code'] ;
            $this->WechatAccount['md5_key'] = $feixapyWechats['md5_key'] ;
            $this->WechatAccount['way_code'] = $feixapyWechats['way_code'] ;
 
        }

        public function createPayment($way_type,$mch_order_no,$amount,$subject,$body)
        {
            
             
            if($way_type == 'wechat'){
                $ret = $this->WechatCreateOrder($mch_order_no,$amount,$subject,$body);
            }
            elseif($way_type == 'alipay'){
                    $ret = $this->AlipayCreateOrder($mch_order_no,$amount,$subject,$body);
                }
                 
             
            return $ret;

        }

    
        private function WechatCreateOrder($mch_order_no,$amount,$subject='',$body='') {
            
                $currentTime = time();

                 
                $bizContent = [    
                    "way_code"=> $this->WechatAccount['way_code'],   
                    "mch_order_no" => $mch_order_no,
                    "amount" => $amount .'',
                    "client_ip" => $_SERVER['REMOTE_ADDR'],
                    "subject" => $subject,
                    "body" => $body, 
                    "return_url" => "https://" . $_SERVER['HTTP_HOST'] . "/personal/rechargelist",
                    'channel_extra' => [
                        'applyId' => "https://www.baidu.com",
                        'applyName' => "百度"
                    ]
                ];

                $data = [
                    "ins_id" => $this->WechatAccount['ins_id'],
                    "app_id" => $this->WechatAccount['app_id'],
                    "sign_type" => "MD5", // 确保使用 MD5 签名类型
                    "timestamp" => $currentTime,
                    "biz_content" => json_encode($bizContent)
                ];
       
                // 使用 MD5 密钥进行签名
                $rsaServer = new FeixpayRsaService('', $this->WechatAccount['md5_key'] );
                $sign = $rsaServer->generateSign($data, "MD5"); // 明确指定使用 MD5 签名
                $data['sign'] = $sign;
                $postJson = json_encode($data);

                $url = 'https://api.feixpay.cn/payment.order/create';
                $headers = [
                    'Content-Type: application/json',
                ];
                $res = $rsaServer->curlPost($url, $data, $headers);
                save_log('创建微信订单','PaymentOrderService');
                save_log($postJson,"PaymentOrderService"); 
                save_log($res,"PaymentOrderService");             
                $ret = json_decode($res,true);                
 
                if (!empty($ret['code']) && $ret['code']==1) {       
                    $this->error = null;         
                    return [
                        'pay_info'=>$ret['data']['pay_info'],
                        'pay_order_id'=>$ret['data']['pay_order_id']
                    ];
                }
            
                // $this->error = '创建订单失败:'.$ret['msg'];
                throw new \Exception('微信创建订单失败:通道名称['.$this->WechatAccount['way_code']."],".$ret['msg']);
                 
            
        }

        private function AlipayCreateOrder($mch_order_no, $amount, $subject = '', $body = '')
        {             
            $currentTime = time();
            $bizContent = [
                "way_code" =>  $this->AlipayAccount['way_code'],
                "mch_order_no" => $mch_order_no,
                "amount" => $amount .'',
                "client_ip" =>  $_SERVER['REMOTE_ADDR'],
                "subject" => $subject,
                "body" => $body,
                // "notify_url" => "https://" . $_SERVER['HTTP_HOST'] . "/payment/callback",
                "return_url" => "https://" . $_SERVER['HTTP_HOST'] . "/personal/rechargelist",
                // "return_url" => "",
            ];
            $data = [
                "ins_id" => $this->AlipayAccount['ins_id'],
                "app_id" => $this->AlipayAccount['app_id'],
                "sign_type" => "MD5", // 确保使用 MD5 签名类型
                "timestamp" => $currentTime,
                "biz_content" => json_encode($bizContent)
            ];
    
            // 使用 MD5 密钥进行签名
            $rsaServer = new FeixpayRsaService('', $this->AlipayAccount['md5_key']);
            $sign = $rsaServer->generateSign($data, "MD5"); // 明确指定使用 MD5 签名
            $data['sign'] = $sign;
            $postJson = json_encode($data);
            $url = 'https://api.feixpay.cn/payment.order/create';
            $headers = [
                'Content-Type: application/json',
            ];
            $res = $rsaServer->curlPost($url, $data, $headers);
            save_log('创建支付宝订单','PaymentOrderService');
            save_log($postJson,"PaymentOrderService"); 
            save_log($res,"PaymentOrderService");             
            $ret = json_decode($res,true);
            if (!empty($ret['code']) && $ret['code']==1) {
                $this->error = null;
                return [
                    'pay_info'=>$ret['data']['pay_info'],
                    'pay_order_id'=>$ret['data']['pay_order_id']
                ];
            }
            throw new \Exception('微信创建订单失败:通道名称['.$this->AlipayAccount['way_code']."],".$ret['msg']);
          
         
    }


    
    public function FeixpayQueryOrder($third_party_order_no) {
        $rechargeRecordInfo = RechargeRecord::where('third_party_order_no',$third_party_order_no)->first();
        if (empty($rechargeRecordInfo)) {
            return ['msg'=>'订单不存在','error'=>true];  
        }
        //payment_method 支付方式:1-微信,2-支付宝
        if ($rechargeRecordInfo->payment_method == 1) {
            $ins_id = $this->WechatAccount['ins_id'];
            $app_id = $this->WechatAccount['app_id'];
            $md5_key = $this->WechatAccount['md5_key'];
        } else if ($rechargeRecordInfo->payment_method == 2) {

            $ins_id = $this->AlipayAccount['ins_id'];
            $app_id = $this->AlipayAccount['app_id'];
            $md5_key = $this->AlipayAccount['md5_key'];
           
        }
        
        $currentTime = time();
        $bizContent = [            
            "pay_order_id" => $third_party_order_no 
        ];
        $data = [
            "ins_id" => $ins_id,
            "app_id" => $app_id,
            "sign_type" => "MD5", // 确保使用 MD5 签名类型
            "timestamp" => $currentTime,
            "biz_content" => json_encode($bizContent)
        ];
// var_dump($data);
        // 使用 MD5 密钥进行签名
        $rsaServer = new FeixpayRsaService('', $md5_key);
        $sign = $rsaServer->generateSign($data, "MD5"); // 明确指定使用 MD5 签名
        $data['sign'] = $sign;

        $url =  "https://api.feixpay.cn/payment.order/query"; 
        $headers = [
            'Content-Type: application/json',
        ];
        $res = $rsaServer->curlPost($url, $data, $headers);
        $ret = json_decode($res,true); 
//  var_dump("api.feixpay.cn ret===",$ret);         
        save_log('查询订单状态','FeixpayQueryOrder');
        save_log($data,'FeixpayQueryOrder');
        save_log($res,'FeixpayQueryOrder');
        if (!$ret) {
            return ['msg'=>'查询订单状态失败','error'=>true];  
        }
     
        if (!empty($ret['code']) && $ret['code'] != 1) {
            return ['msg'=>$ret['msg'],'error'=>true];  
        }
        if(empty($ret['data']['state'])){
            return ['msg'=>$ret['msg'],'error'=>true];  
        }
        return ['error'=>false,'state'=>$ret['data']['state']];
    }


}

