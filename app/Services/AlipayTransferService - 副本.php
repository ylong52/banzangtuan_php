<?php

namespace App\Services;

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * 支付宝支付服务类
 * 支持向个人账户转账（提现）功能
 */
class AlipayTransferService
{
    /**
     * 初始化支付宝SDK配置
     */
    private function initConfig()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';  // 使用生产环境
        $options->signType = 'RSA2';
        $options->ignoreSSL = true;  // 忽略SSL证书验证
        $options->httpProxy = '';  // 不使用代理
        $options->connectTimeout = 60000;  // 连接超时60秒
        $options->readTimeout = 60000;  // 读取超时60秒

        $alipayProperties = DynamicPropertyService::getAllByTypeTag('alipay_official');
       

        // 应用配置
        // $options->appId = env('ALIPAY_APP_ID', '');
        $options->appId = $alipayProperties['ALIPAY_APP_ID'];
        $options->merchantPrivateKey = $alipayProperties['ALIPAY_PRIVATE_KEY'];

 
 
        $options->merchantPrivateKey = $this->cleanPrivateKey(env('ALIPAY_PRIVATE_KEY', ''));
        
        $alipayCertContent = $alipayProperties['ALIPAY_CERT'] ?? '';
        $rootCertContent = $alipayProperties['ALIPAY_ROOT'] ?? '';
        $merchantCertContent = $alipayProperties['ALIPAY_MERCHANT_CERT'] ?? '';
      
        // 验证证书格式
        $alipayCertContent = $this->validateAndCleanCertContent($alipayCertContent, '支付宝公钥证书');
        $rootCertContent = $this->validateAndCleanCertContent($rootCertContent, '支付宝根证书');
        $merchantCertContent = $this->validateAndCleanCertContent($merchantCertContent, '商户私钥证书');
        
        if ($alipayCertContent && $rootCertContent && $merchantCertContent) {
            // 创建临时证书文件
            $tempDir = storage_path('temp/certs');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $certPath = $tempDir . '/alipay_cert_' . uniqid() . '.crt';
            $rootCertPath = $tempDir . '/alipay_root_' . uniqid() . '.crt';
            $merchantCertPath = $tempDir . '/merchant_cert_' . uniqid() . '.crt';
            
            // 写入证书内容
            file_put_contents($certPath, $alipayCertContent);
            file_put_contents($rootCertPath, $rootCertContent);
            file_put_contents($merchantCertPath, $merchantCertContent);
            
            // 设置SDK选项
            $options->alipayCertPath = $certPath;
            $options->alipayRootCertPath = $rootCertPath;
            $options->merchantCertPath = $merchantCertPath;
            
            Log::info('使用数据库证书内容模式', [
                'cert_path' => $certPath,
                'root_cert_path' => $rootCertPath,
                'merchant_cert_path' => $merchantCertPath
            ]);
        } else {
            Log::warning('证书内容验证失败，回退到密钥模式');
            throw new Exception('证书内容验证失败');
        }


        // 可选配置
        $options->notifyUrl = env('ALIPAY_NOTIFY_URL', '');
        $options->encryptKey = env('ALIPAY_ENCRYPT_KEY', '');

        Factory::setOptions($options);
        save_log('使用密钥模式',"AlipayTransferService");
        save_log([
            'app_id' => $options->appId,
            'gateway' => $options->gatewayHost,
            'cert_mode' => !empty($options->alipayCertPath)
        ],"AlipayTransferService"); 
    }

    /**
     * 向个人支付宝账户转账（提现）
     * 
     * @param string $payeeAccount 收款方支付宝账号
     * @param string $amount 转账金额（元）
     * @param string $payeeName 收款方真实姓名（可选，用于校验）
     * @param string $remark 转账备注
     * @param string $outBizNo 商户转账唯一订单号
     * @return array 转账结果
     * @throws Exception
     */
    public function transferToAccount($payeeAccount, $amount, $payeeName = '', $remark = '提现', $outBizNo = null)
    {
        try {
            $this->initConfig();

            // 生成唯一转账订单号
            if (empty($outBizNo)) {
                $outBizNo = 'TRANSFER_' . date('YmdHis') . '_' . mt_rand(100000, 999999);
            }

            Log::info('开始支付宝转账', [
                'payee_account' => $payeeAccount,
                'amount' => $amount,
                'payee_name' => $payeeName,
                'out_biz_no' => $outBizNo,
                'remark' => $remark
            ]);
            save_log("开始支付宝转账","AlipayTransferService");
            save_log([
                'payee_account' => $payeeAccount,
                'amount' => $amount,
                'payee_name' => $payeeName,
                'out_biz_no' => $outBizNo,
                'remark' => $remark
            ],"AlipayTransferService");

        

            // 调用支付宝转账接口 (alipay.fund.trans.uni.transfer)
            $result = Factory::util()
                ->generic()
                ->execute(
                    'alipay.fund.trans.uni.transfer',
                    [],
                    [
                        'out_biz_no' => $outBizNo,           // 商户转账唯一订单号
                        'trans_amount' => $amount,           // 转账金额
                        'product_code' => 'TRANS_ACCOUNT_NO_PWD', // 产品码
                        'biz_scene' => 'DIRECT_TRANSFER',    // 业务场景
                        'order_title' => $remark,            // 订单标题
                        'payee_info' => [
                            'identity' => $payeeAccount,     // 收款方账户
                            'identity_type' => 'ALIPAY_LOGON_ID', // 收款方账户类型
                            'name' => $payeeName ?: null     // 收款方真实姓名（可选）
                        ]
                    ]
                );

            Log::info('支付宝转账API调用结果', [
                'response' => $result,
                'out_biz_no' => $outBizNo
            ]);

            // 检查响应结果
            if (isset($result->code) && $result->code == '10000') {
                Log::info('支付宝转账成功', [
                    'out_biz_no' => $outBizNo,
                    'order_id' => $result->orderId ?? '',
                    'pay_date' => $result->payDate ?? ''
                ]);

                return [
                    'success' => true,
                    'message' => '转账成功',
                    'data' => [
                        'out_biz_no' => $outBizNo,
                        'order_id' => $result->orderId ?? '',
                        'pay_date' => $result->payDate ?? '',
                        'amount' => $amount,
                        'payee_account' => $payeeAccount,
                        'status' => 'SUCCESS'
                    ]
                ];
            } else {
                $errorCode = $result->code ?? 'UNKNOWN';
                $errorMsg = $result->msg ?? '转账失败';
                $subCode = $result->subCode ?? '';
                $subMsg = $result->subMsg ?? '';

                Log::warning('支付宝转账失败', [
                    'code' => $errorCode,
                    'msg' => $errorMsg,
                    'sub_code' => $subCode,
                    'sub_msg' => $subMsg,
                    'out_biz_no' => $outBizNo,
                    'full_response' => json_encode($result)
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'data' => [
                        'code' => $errorCode,
                        'sub_code' => $subCode,
                        'sub_msg' => $subMsg,
                        'out_biz_no' => $outBizNo
                    ]
                ];
            }

        } catch (Exception $e) {
            Log::error('支付宝转账异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'out_biz_no' => $outBizNo ?? '',
                'payee_account' => $payeeAccount,
                'amount' => $amount
            ]);

            return [
                'success' => false,
                'message' => '转账异常：' . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'out_biz_no' => $outBizNo ?? ''
                ]
            ];
        }
    }

    /**
     * 查询转账订单状态
     * 
     * @param string $outBizNo 商户转账唯一订单号
     * @return array 查询结果
     * @throws Exception
     */
    public function queryTransfer($outBizNo)
    {
        try {
            $this->initConfig();

            Log::info('查询支付宝转账状态', ['out_biz_no' => $outBizNo]);

            $result = Factory::util()
                ->generic()
                ->execute(
                    'alipay.fund.trans.order.query',
                    [],
                    [
                        'out_biz_no' => $outBizNo
                    ]
                );

            Log::info('支付宝转账状态查询结果', [
                'response' => $result,
                'out_biz_no' => $outBizNo
            ]);

            if (isset($result->code) && $result->code == '10000') {
                return [
                    'success' => true,
                    'message' => '查询成功',
                    'data' => [
                        'out_biz_no' => $outBizNo,
                        'order_id' => $result->orderId ?? '',
                        'status' => $result->status ?? '',
                        'pay_date' => $result->payDate ?? '',
                        'amount' => $result->amount ?? '',
                        'payee_account' => $result->payeeAccount ?? '',
                        'error_code' => $result->errorCode ?? '',
                        'fail_reason' => $result->failReason ?? ''
                    ]
                ];
            } else {
                $errorCode = $result->code ?? 'UNKNOWN';
                $errorMsg = $result->msg ?? '查询失败';
                
                return [
                    'success' => false,
                    'message' => $errorMsg,
                    'data' => [
                        'code' => $errorCode,
                        'sub_code' => $result->subCode ?? '',
                        'sub_msg' => $result->subMsg ?? '',
                        'full_response' => json_encode($result)
                    ]
                ];
            }

        } catch (Exception $e) {
            Log::error('支付宝转账查询异常', [
                'error' => $e->getMessage(),
                'out_biz_no' => $outBizNo
            ]);

            return [
                'success' => false,
                'message' => '查询异常：' . $e->getMessage(),
                'data' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * 验证支付宝账号格式
     * 
     * @param string $account 支付宝账号
     * @return bool
     */
    public function validateAlipayAccount($account)
    {
        // 支付宝账号可以是手机号或邮箱
        $phonePattern = '/^1[3-9]\d{9}$/';
        $emailPattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        
        return preg_match($phonePattern, $account) || preg_match($emailPattern, $account);
    }

    /**
     * 格式化金额（保留两位小数）
     * 
     * @param float $amount 金额
     * @return string
     */
    public function formatAmount($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * 批量转账到支付宝账户
     * 
     * @param array $transfers 转账列表 [['account' => '', 'amount' => '', 'name' => '', 'remark' => ''], ...]
     * @param string $batchNo 批次号
     * @return array 批量转账结果
     */
    public function batchTransfer($transfers, $batchNo = null)
    {
        if (empty($batchNo)) {
            $batchNo = 'BATCH_' . date('YmdHis') . '_' . mt_rand(100000, 999999);
        }

        $results = [];
        $successCount = 0;
        $failCount = 0;

        Log::info('开始批量转账', [
            'batch_no' => $batchNo,
            'count' => count($transfers)
        ]);

        foreach ($transfers as $index => $transfer) {
            $outBizNo = $batchNo . '_' . ($index + 1);
            
            $result = $this->transferToAccount(
                $transfer['account'],
                $transfer['amount'],
                $transfer['name'] ?? '',
                $transfer['remark'] ?? '批量提现',
                $outBizNo
            );

            $results[] = [
                'index' => $index + 1,
                'account' => $transfer['account'],
                'amount' => $transfer['amount'],
                'out_biz_no' => $outBizNo,
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            // 避免请求过于频繁，添加延迟
            if ($index < count($transfers) - 1) {
                sleep(1);
            }
        }

        Log::info('批量转账完成', [
            'batch_no' => $batchNo,
            'total' => count($transfers),
            'success' => $successCount,
            'fail' => $failCount
        ]);

        return [
            'batch_no' => $batchNo,
            'total' => count($transfers),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }

    /**
     * 格式化私钥，确保正确的PEM格式
     */
    private function formatPrivateKey($privateKey)
    {
        if (empty($privateKey)) {
            return '';
        }

        // 移除现有的头尾标记和空白字符
        $privateKey = str_replace([
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            "\r\n", "\r", "\n", " "
        ], '', $privateKey);

        // 如果私钥为空，返回空字符串
        if (empty($privateKey)) {
            return '';
        }

        // 重新格式化为标准PEM格式，使用Unix换行符（兼容Linux服务器）
        $formattedKey = "-----BEGIN RSA PRIVATE KEY-----\n";
        $formattedKey .= chunk_split($privateKey, 64, "\n");
        $formattedKey .= "-----END RSA PRIVATE KEY-----";

        return $formattedKey;
    }

    /**
     * 清理私钥，移除头尾标记，返回纯净的私钥内容供支付宝SDK使用
     */
    private function cleanPrivateKey($privateKey)
    {
        if (empty($privateKey)) {
            return '';
        }

        // 移除所有头尾标记和换行符、空格
        $cleanKey = str_replace([
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            "\r\n", "\r", "\n", " "
        ], '', $privateKey);

        // 返回纯净的私钥内容，支付宝SDK会自己添加头尾
        return $cleanKey;
    }

     /**
 * 验证并整理证书内容
 * 
 * @param string $certContent 证书内容
 * @param string $certType 证书类型（用于日志）
 * @return string|false 整理后的证书内容或false
 */
private function validateAndCleanCertContent($certContent, $certType)
{
    if (empty($certContent)) {
        Log::warning("{$certType}内容为空");
        return false;
    }
    
    // 1. 移除多余的空白字符和换行符
    $cleaned = trim($certContent);
    
    // 2. 检查是否包含证书头尾标识
    $hasHeader = false;
    $hasFooter = false;
    
    if (strpos($cleaned, '-----BEGIN') !== false) {
        $hasHeader = true;
    }
    
    if (strpos($cleaned, '-----END') !== false) {
        $hasFooter = true;
    }
    
    // 3. 如果没有证书头尾，尝试添加
    if (!$hasHeader || !$hasFooter) {
        Log::warning("{$certType}缺少证书头尾标识，尝试修复");
        
        // 根据证书类型添加适当的头尾
        if (strpos($certType, '私钥') !== false) {
            if (!$hasHeader) $cleaned = "-----BEGIN RSA PRIVATE KEY-----\n" . $cleaned;
            if (!$hasFooter) $cleaned = $cleaned . "\n-----END RSA PRIVATE KEY-----";
        } elseif (strpos($certType, '公钥') !== false) {
            if (!$hasHeader) $cleaned = "-----BEGIN CERTIFICATE-----\n" . $cleaned;
            if (!$hasFooter) $cleaned = $cleaned . "\n-----END CERTIFICATE-----";
        } else {
            if (!$hasHeader) $cleaned = "-----BEGIN CERTIFICATE-----\n" . $cleaned;
            if (!$hasFooter) $cleaned = $cleaned . "\n-----END CERTIFICATE-----";
        }
    }
    
    // 4. 标准化换行符
    $cleaned = str_replace(["\r\n", "\r"], "\n", $cleaned);
    
    // 5. 验证证书格式
    if (!$this->isValidCertFormat($cleaned)) {
        Log::error("{$certType}格式验证失败");
        return false;
    }
    
    // 6. 检查证书长度（RSA私钥通常至少1000字符，证书至少500字符）
    $minLength = (strpos($certType, '私钥') !== false) ? 1000 : 500;
    if (strlen($cleaned) < $minLength) {
        Log::warning("{$certType}长度过短，可能不完整");
        return false;
    }
    
    Log::info("{$certType}验证通过，长度: " . strlen($cleaned));
    return $cleaned;
}

/**
 * 验证证书格式是否正确
 * 
 * @param string $certContent 证书内容
 * @return bool
 */
private function isValidCertFormat($certContent)
{
    // 检查基本格式
    $lines = explode("\n", $certContent);
    $hasBegin = false;
    $hasEnd = false;
    $hasContent = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (strpos($line, '-----BEGIN') === 0) {
            $hasBegin = true;
        } elseif (strpos($line, '-----END') === 0) {
            $hasEnd = true;
        } elseif (!str_starts_with($line, '-----')) {
            $hasContent = true;
        }
    }
    
    return $hasBegin && $hasEnd && $hasContent;
}


}
