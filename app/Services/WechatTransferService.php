<?php

namespace App\Services;

use GuzzleHttp\Client;

//微信公众号出账服务
class WechatTransferService
{
    protected $appId;
    protected $mchId;
    protected $apiV3Key;
    protected $privateKey;
    protected $serialNo;
    protected $client;

    protected $appId2;
    protected $mchId2;
    protected $apiV3Key2;
    protected $privateKey2;
    protected $serialNo2;
 

    public function __construct()
    {
        // 直接加载配置文件，避免使用Laravel的config()函数
        // $configPath = __DIR__ . '/../../config/wechat.php';
        // if (!file_exists($configPath)) {
        //     throw new \Exception('微信配置文件不存在: ' . $configPath);
        // }
        
        // $config = require $configPath;
        // $this->appId = $config['appid'];
        // $this->mchId = $config['mch_id'];
        // $this->apiV3Key = $config['api_v3_key'] ?? '';
        // $this->serialNo = $config['serial_no'] ?? '';
       
        // // 直接构建私钥文件路径
        // $privateKeyPath = __DIR__ . '/../../config/certs/wechat/merchant_privateKey.pem';
        // if (!file_exists($privateKeyPath)) {
        //     throw new \Exception('商户私钥文件不存在: ' . $privateKeyPath);
        // }
        // $this->privateKey = file_get_contents($privateKeyPath);
        
 
        $wechatProperties = DynamicPropertyService::getAllByTypeTag('wechat_official');
        $this->appId = $wechatProperties['appid'];
        $this->mchId = $wechatProperties['mch_id'];
        $this->apiV3Key = $wechatProperties['api_v3_key'] ?? '';
        $this->serialNo = $wechatProperties['serial_no'] ?? '';
        $this->privateKey  = $wechatProperties['merchant_privatekey_pem'];

        $this->client = new Client([
            'base_uri' => 'https://api.mch.weixin.qq.com',
            'timeout' => 30,
        ]);
    }

    /**
     * 发起转账（根据API文档实现）
     * @param array $params 转账参数
     * @return array
     */
    public function transfer(array $params): array
    {
      
        // 构建请求参数（根据文档要求）
        $requestData = [
            'appid' => $this->appId,
            'out_bill_no' => $params['out_bill_no'],
            'transfer_scene_id' =>  '1000', // 默认1000
            // 'openid' => 'oScOfvrkFm_ktVC-CTLjvZtPh_KE',
            'openid' => $params['openid'],
            'transfer_amount' => $params['transfer_amount'], // 金额，单位：分
            'transfer_remark' =>  '新会员开通有礼',
              "user_recv_perception"=>"现金奖励",
            // 添加转账场景报备信息（必需）
            'transfer_scene_report_infos' => [
                [
                    'info_type' => '活动名称',
                    'info_content' => '推新会员有礼'
                ],
                [
                    'info_type' => '奖励说明', 
                    'info_content' => '推新会员有礼'
                ]
            ]
        ];
 
        // 可选参数
        if (isset($params['user_name'])) {
            $requestData['user_name'] = $params['user_name'];
        }
        if (isset($params['notify_url'])) {
            $requestData['notify_url'] = $params['notify_url'];
        }
        if (isset($params['user_recv_perception'])) {
            $requestData['user_recv_perception'] = $params['user_recv_perception'];
        }
        if (isset($params['transfer_scene_report_infos'])) {
            $requestData['transfer_scene_report_infos'] = $params['transfer_scene_report_infos'];
        }
 
        save_log("微信转账参数: ","WechatCreateTransfer");
        save_log($requestData,"WechatCreateTransfer");
        try {
            $response = $this->client->post('/v3/fund-app/mch-transfer/transfer-bills', [
                'json' => $requestData,
                'headers' => [
                    'Authorization' => $this->buildAuthHeader('POST', '/v3/fund-app/mch-transfer/transfer-bills', json_encode($requestData)),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Wechatpay-Serial' => $this->serialNo,
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);
            save_log("微信转账返回: ","WechatCreateTransfer");
            save_log($result,"WechatCreateTransfer");
 
            if ($result['transfer_bill_no'] == '') {
                throw new \Exception("微信转账失败");
            } else {
               return [
                    'success' => true,
                    'data' => $result,
                    'out_bill_no' => $result['out_bill_no'] ?? '',
                    'transfer_bill_no' => $result['transfer_bill_no'] ?? '',
                    'create_time' => $result['create_time'] ?? '',
                    'state' => $result['state'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            // echo "微信转账失败: " . $e->getMessage() . "\n";
            // echo "请求参数: " . json_encode($requestData, JSON_UNESCAPED_UNICODE) . "\n";
            save_log("微信转账失败: ","WechatCreateTransfer");
            save_log($e->getMessage(),"WechatCreateTransfer");           
            throw new \Exception($e->getMessage());            
        }
    }

    /**
     * 查询转账状态
     * @param string $outBillNo 商户订单号
     * @return array
     */
    public function queryTransferStatus(string $outBillNo): array
    {
        try {
            $url = "/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$outBillNo}";
            
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => $this->buildAuthHeader('GET', $url, ''),
                    'Accept' => 'application/json',
                    'Wechatpay-Serial' => $this->serialNo,
                ],
            ]);

            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);
 
            // return [
            //     'success' => true,
            //     'data' => $result,
            // ];
            return $result;
        } catch (\Exception $e) {
            // echo "查询转账状态失败: " . $e->getMessage() . "\n";
            // return [
            //     'success' => false,
            //     'error' => $e->getMessage(),
            // ];
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 构建Authorization头
     * @param string $method HTTP方法
     * @param string $url 请求URL
     * @param string $body 请求体
     * @return string
     */
    private function buildAuthHeader(string $method, string $url, string $body): string
    {
        $timestamp = time();
        $nonce = $this->generateNonce();
        
        // 构建签名字符串
        $signString = implode("\n", [
            $method,
            $url,
            $timestamp,
            $nonce,
            $body,
            ''
        ]);

        // 生成签名
        $signature = '';
        openssl_sign($signString, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        // 构建Authorization头
        return sprintf(
            'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",signature="%s",timestamp="%d",serial_no="%s"',
            $this->mchId,
            $nonce,
            $signature,
            $timestamp,
            $this->serialNo
        );
    }

    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    private function generateNonce(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}