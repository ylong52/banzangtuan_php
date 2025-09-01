<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * 微信H5用户确认收款服务类
 * 
 * 该类用于处理微信客户端通过H5页面请求用户确认收款的功能
 * 主要包含以下功能：
 * 1. 获取用户确认收款页面所需的所有参数
 * 2. 查询转账单据状态
 * 3. 生成微信JS-SDK配置参数
 * 4. 验证用户确认结果
 */
class WechatRequestMerchantTransfer
{
    /**
     * 微信配置信息
     * @var array
     */
    private $config;
    
    /**
     * HTTP客户端
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->config = config('wechat');
        $this->client = new Client([
            'base_uri' => 'https://api.mch.weixin.qq.com',
            'timeout' => 30,
        ]);
    }

    /**
     * 获取用户确认收款页面数据
     * 
     * 该方法返回H5页面所需的所有参数，包括：
     * - JS-SDK配置参数（appId, timestamp, nonceStr, signature等）
     * - 转账参数（mchId, appId, package等）
     * - 转账批次号
     * 
     * @param string $transferId 转账批次号，用于查询转账状态
     * @return array 返回包含以下字段的数组：
     *               - success: bool 是否成功
     *               - message: string 错误信息（失败时）
     *               - js_config: array JS-SDK配置参数（成功时）
     *               - transfer_params: array 转账参数（成功时）
     *               - transfer_id: string 转账批次号（成功时）
     */
    public function getUserConfirmationPageData($transferId)
    {
        try {
            // 1. 查询转账单据状态
            $transferStatus = $this->queryTransferStatus($transferId);
            
            if ($transferStatus['status'] !== 'WAIT_USER_CONFIRM') {
                return [
                    'success' => false,
                    'message' => '转账状态不是待用户确认状态',
                    'status' => $transferStatus['status']
                ];
            }

            // 2. 生成JS-SDK配置参数
            $jsConfig = $this->generateJsConfig();
            
            // 3. 准备requestMerchantTransfer参数
            $transferParams = [
                'mchId' => $this->config['mch_id'],        // 商户号
                'appId' => $this->config['appid'],         // 微信公众号AppID
                'package' => $transferStatus['package_info'] ?? '', // 预支付交易会话标识
            ];

            return [
                'success' => true,
                'js_config' => $jsConfig,                  // JS-SDK配置参数
                'transfer_params' => $transferParams,      // 转账参数
                'transfer_id' => $transferId               // 转账批次号
            ];

        } catch (\Exception $e) {
            Log::error('获取用户确认收款页面数据失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '获取页面数据失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 查询转账单据状态
     * 
     * 调用微信支付API查询转账批次的状态信息
     * 
     * @param string $transferId 转账批次号
     * @return array 返回包含以下字段的数组：
     *               - status: string 转账状态（WAIT_USER_CONFIRM/SUCCESS/FAILED等）
     *               - package_info: string 预支付交易会话标识
     */
    private function queryTransferStatus($transferId)
    {
        $url = '/v3/transfer/batches/out-batch-no/' . $transferId;
        
        $response = $this->client->get($url, [
            'headers' => [
                'Authorization' => 'WECHATPAY2-SHA256-RSA2048 ' . $this->generateAuthHeader('GET', $url, ''),
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (compatible; WeChatPay)',
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        
        return [
            'status' => $result['transfer_batch']['batch_status'] ?? '',        // 转账状态
            'package_info' => $result['transfer_batch']['package_info'] ?? '',  // 预支付交易会话标识
        ];
    }

    /**
     * 生成JS-SDK配置参数
     * 
     * 生成微信JS-SDK初始化所需的配置参数，包括签名等
     * 
     * @return array 返回包含以下字段的数组：
     *               - appId: string 微信公众号AppID
     *               - timestamp: int 时间戳
     *               - nonceStr: string 随机字符串
     *               - signature: string 签名
     *               - jsApiList: array 需要使用的JS接口列表
     */
    private function generateJsConfig()
    {
        $timestamp = time();                                    // 当前时间戳
        $nonceStr = $this->generateNonceStr();                  // 生成随机字符串
        $url = request()->url();                                // 当前页面URL
        
        // 生成签名字符串
        $string = "jsapi_ticket=" . $this->getJsApiTicket() . 
                  "&noncestr=" . $nonceStr . 
                  "&timestamp=" . $timestamp . 
                  "&url=" . $url;
        
        $signature = sha1($string);                             // 使用SHA1算法生成签名

        return [
            'appId' => $this->config['appid'],                  // 微信公众号AppID
            'timestamp' => $timestamp,                          // 时间戳
            'nonceStr' => $nonceStr,                            // 随机字符串
            'signature' => $signature,                          // 签名
            'jsApiList' => ['requestMerchantTransfer']          // 需要使用的JS接口列表
        ];
    }

    /**
     * 获取JS API Ticket
     * 
     * 获取微信JS-SDK的jsapi_ticket，用于生成签名
     * 注意：这里需要实现完整的获取逻辑，包括缓存机制
     * 
     * @return string jsapi_ticket
     */
    private function getJsApiTicket()
    {
        // 这里应该实现获取jsapi_ticket的逻辑
        // 可以从缓存或重新请求获取
        // 简化示例，实际使用时需要完整实现
        return 'jsapi_ticket_placeholder';
    }

    /**
     * 生成随机字符串
     * 
     * 生成指定长度的随机字符串，用于签名
     * 
     * @param int $length 字符串长度，默认32位
     * @return string 随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 生成认证头
     * 
     * 生成微信支付V3 API请求所需的认证头
     * 
     * @param string $method HTTP方法（GET/POST等）
     * @param string $url 请求URL
     * @param string $body 请求体
     * @return string 认证头字符串
     */
    private function generateAuthHeader($method, $url, $body)
    {
        // 这里应该实现完整的微信支付V3签名算法
        // 包括：
        // 1. 构建签名字符串
        // 2. 使用商户私钥签名
        // 3. 生成认证头
        // 简化示例，实际使用时需要完整实现
        return 'auth_header_placeholder';
    }

    /**
     * 验证用户确认结果
     * 
     * 查询转账状态，验证用户是否已确认收款
     * 
     * @param string $transferId 转账批次号
     * @return array 返回包含以下字段的数组：
     *               - success: bool 是否成功
     *               - message: string 错误信息（失败时）
     *               - status: string 转账状态（成功时）
     *               - is_confirmed: bool 是否已确认（成功时）
     */
    public function verifyConfirmationResult($transferId)
    {
        try {
            $transferStatus = $this->queryTransferStatus($transferId);
            
            return [
                'success' => true,
                'status' => $transferStatus['status'],           // 转账状态
                'is_confirmed' => $transferStatus['status'] === 'SUCCESS' // 是否已确认
            ];
        } catch (\Exception $e) {
            Log::error('验证用户确认结果失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '验证失败: ' . $e->getMessage()
            ];
        }
    }
}
