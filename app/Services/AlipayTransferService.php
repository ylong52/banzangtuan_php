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
    private function initConfig_old2()
    {
        $options = new Config();
        $options->protocol = 'https';
        $options->gatewayHost = 'openapi.alipay.com';  // 使用生产环境
        $options->signType = 'RSA2';
        $options->ignoreSSL = true;  // 忽略SSL证书验证
        $options->httpProxy = '';  // 不使用代理

        $alipayProperties = DynamicPropertyService::getAllByTypeTag('alipay_official');

        // 应用配置
        $options->appId = $alipayProperties['ALIPAY_APP_ID'];
        // $options->merchantPrivateKey = $this->cleanPrivateKey(env('ALIPAY_PRIVATE_KEY', ''));
        $options->merchantPrivateKey = $alipayProperties['ALIPAY_PRIVATE_KEY'];
        
        // 证书文件路径配置（支持相对路径和绝对路径）
        $certPath = env('ALIPAY_CERT_PATH', '');
        $rootCertPath = env('ALIPAY_ROOT_CERT_PATH', '');
        $merchantCertPath = env('ALIPAY_MERCHANT_CERT_PATH', '');
        
        // 如果配置了证书路径，使用证书模式
        if (!empty($certPath)) {
            // 处理相对路径，转换为绝对路径
            if (!file_exists($certPath)) {
                $certPath = base_path($certPath);
            }
            if (!file_exists($rootCertPath)) {
                $rootCertPath = base_path($rootCertPath);
            }
            if (!file_exists($merchantCertPath)) {
                $merchantCertPath = base_path($merchantCertPath);
            }
            //打印输出文件内容
           // echo "certPath==",file_get_contents($certPath),"\n\n\n\n";
            // echo "rootCertPath==",file_get_contents($rootCertPath),"\n\n\n\n";
            // echo "merchantCertPath==",file_get_contents($merchantCertPath),"\n\n\n\n";
            // die;
            $options->alipayCertPath = $certPath;
            $options->alipayRootCertPath = $rootCertPath;
            $options->merchantCertPath = $merchantCertPath;
            
            Log::info('使用证书模式', [
                'cert_path' => $certPath,
                'root_cert_path' => $rootCertPath,
                'merchant_cert_path' => $merchantCertPath,
                'cert_exists' => file_exists($certPath),
                'root_exists' => file_exists($rootCertPath),
                'merchant_exists' => file_exists($merchantCertPath)
            ]);
            
        } else {
            // 使用密钥模式
            $options->alipayPublicKey = env('ALIPAY_PUBLIC_KEY', '');
            Log::info('使用密钥模式');
        }

        // 可选配置
        // $options->notifyUrl = env('ALIPAY_NOTIFY_URL', '');
        // $options->encryptKey = env('ALIPAY_ENCRYPT_KEY', '');
 
        Factory::setOptions($options);

        Log::info('支付宝SDK配置初始化完成', [
            'app_id' => $options->appId,
            'gateway' => $options->gatewayHost,
            'cert_mode' => !empty($options->alipayCertPath)
        ]);
    }

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

        $alipayProperties = DynamicPropertyService::getAllByTypeTag('alipay_official');

        $options->appId = $alipayProperties['ALIPAY_APP_ID'];
        $options->merchantPrivateKey =  $this->cleanPrivateKey($alipayProperties['ALIPAY_PRIVATE_KEY']);

        $alipay_cert_update_at = \App\Models\DynamicProperty::where('prop_key', 'ALIPAY_CERT')->value('updated_at');
        $cert_update_at =   (new \DateTime($alipay_cert_update_at, new \DateTimeZone('Asia/Shanghai')))->getTimestamp();
        
        $tempDir = storage_path('app/public/certs');
        is_dir($tempDir) or mkdir($tempDir, 0777, true);
        //判断文件不存在就从数据库取值，并且保存
        if (!file_exists($tempDir . '/alipay_cert_' . $cert_update_at . '.crt')) {
           
            $alipayCertContent = $alipayProperties['ALIPAY_CERT'] ?? '';
            $rootCertContent = $alipayProperties['ALIPAY_ROOT'] ?? '';
            $merchantCertContent = $alipayProperties['ALIPAY_MERCHANT_CERT'] ?? '';

            // 验证并格式化证书内容
            $alipayCertContent = $this->validateAndCleanCertContent($alipayCertContent, '支付宝公钥证书');
            $rootCertContent = $this->validateAndCleanCertContent($rootCertContent, '支付宝根证书');
            $merchantCertContent = $this->validateAndCleanCertContent($merchantCertContent, '商户私钥证书');

            // 保存证书文件
            if (!file_exists($tempDir . '/alipay_cert_' . $cert_update_at . '.crt')) {
                file_put_contents($tempDir . '/alipay_cert_' . $cert_update_at . '.crt', $alipayCertContent);
            }
            if (!file_exists($tempDir . '/alipay_root_' . $cert_update_at . '.crt')) {
                file_put_contents($tempDir . '/alipay_root_' . $cert_update_at . '.crt', $rootCertContent);
            }
            if (!file_exists($tempDir . '/merchant_cert_' . $cert_update_at . '.crt')) {
                file_put_contents($tempDir . '/merchant_cert_' . $cert_update_at . '.crt', $merchantCertContent);
            }
            $options->alipayCertPath = $tempDir . '/alipay_cert_' . $cert_update_at . '.crt';
            $options->alipayRootCertPath = $tempDir . '/alipay_root_' . $cert_update_at . '.crt';
            $options->merchantCertPath = $tempDir . '/merchant_cert_' . $cert_update_at . '.crt';

            Log::info('使用数据库证书内容模式', [
                'cert_path' => $options->alipayCertPath,
                'root_cert_path' => $options->alipayRootCertPath,
                'merchant_cert_path' => $options->merchantCertPath
            ]);
        } else {
      
            $options->alipayCertPath = $tempDir . '/alipay_cert_' . $cert_update_at . '.crt';
            $options->alipayRootCertPath = $tempDir . '/alipay_root_' . $cert_update_at . '.crt';
            $options->merchantCertPath = $tempDir . '/merchant_cert_' . $cert_update_at . '.crt';
            Log::info('使用数据库证书内容模式', [
                'cert_path' => $options->alipayCertPath,
                'root_cert_path' => $options->alipayRootCertPath,
                'merchant_cert_path' => $options->merchantCertPath
            ]);
        }
 
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

        // 在控制台输出详细信息（用于调试）
        if (php_sapi_name() === 'cli') {
            echo "💰 AlipayService 转账参数:\n";
            echo "   - 收款账户: [{$payeeAccount}]\n";
            echo "   - 收款姓名: [{$payeeName}]\n";
            echo "   - 转账金额: [{$amount}]\n";
            echo "   - 订单号: [{$outBizNo}]\n";
            echo "   - 备注: [{$remark}]\n";
            echo "   - 账户字节数: " . strlen($payeeAccount) . "\n";
            echo "   - 姓名字节数: " . strlen($payeeName) . "\n";
            echo "   - UTF-8编码检查:\n";
            echo "     * 账户: " . (mb_check_encoding($payeeAccount, 'UTF-8') ? '✅' : '❌') . "\n";
            echo "     * 姓名: " . (mb_check_encoding($payeeName, 'UTF-8') ? '✅' : '❌') . "\n";
            echo "   - 服务器环境:\n";
            echo "     * 操作系统: " . PHP_OS . "\n";
            echo "     * SAPI: " . php_sapi_name() . "\n";
            echo "     * 目录分隔符: " . DIRECTORY_SEPARATOR . "\n";
            
            // 测试私钥格式
            $privateKey = env('ALIPAY_PRIVATE_KEY', '');
            $formattedKey = $this->formatPrivateKey($privateKey);
            echo "   - 私钥信息:\n";
            echo "     * 原始长度: " . strlen($privateKey) . "\n";
            echo "     * 格式化后长度: " . strlen($formattedKey) . "\n";
            
            // 测试OpenSSL私钥解析
            $resource = openssl_pkey_get_private($formattedKey);
            if ($resource !== false) {
                echo "     * OpenSSL解析: ✅ 成功\n";
                
                // 测试签名
                $testData = "test";
                $signature = '';
                if (openssl_sign($testData, $signature, $resource, OPENSSL_ALGO_SHA256)) {
                    echo "     * 签名测试: ✅ 成功\n";
                } else {
                    echo "     * 签名测试: ❌ 失败 - " . openssl_error_string() . "\n";
                }
                
                if (function_exists('openssl_free_key')) {
                    openssl_free_key($resource);
                }
            } else {
                echo "     * OpenSSL解析: ❌ 失败 - " . openssl_error_string() . "\n";
            }
        }

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
     * 从MySQL数据库取出证书并整理为正确格式
     * 
     * @param string $certContent 从数据库取出的原始证书内容
     * @param string $certType 证书类型（用于日志）
     * @return string|false 整理后的证书内容或false
     */
    private function formatCertFromDatabase($certContent, $certType)
    {
        if (empty($certContent)) {
            Log::warning("{$certType}内容为空");
            return false;
        }
        
        // 1. 移除多余的空白字符和换行符
        $cleaned = trim($certContent);
        
        // 2. 按证书边界分割（支持多个证书）
        $certificates = [];
        
        // 使用正则表达式分割证书
        $parts = preg_split('/(-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----)/', $cleaned, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        
        $currentCert = '';
        $inCert = false;
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;
            
            if ($part === '-----BEGIN CERTIFICATE-----') {
                $currentCert = $part . PHP_EOL;
                $inCert = true;
            } elseif ($part === '-----END CERTIFICATE-----') {
                $currentCert .= $part . PHP_EOL;
                $certificates[] = $currentCert;
                $currentCert = '';
                $inCert = false;
            } else {
                if ($inCert) {
                    $currentCert .= $part . PHP_EOL;
                }
            }
        }
        
        // 如果没有找到完整的证书，尝试其他方法
        if (empty($certificates)) {
            Log::warning("{$certType}未找到完整证书边界，尝试其他方法");
            
            // 检查是否包含证书内容但没有边界
            if (strpos($cleaned, 'MIID') !== false || strpos($cleaned, 'MIIE') !== false) {
                // 尝试添加证书边界
                $cleaned = "-----BEGIN CERTIFICATE-----\n" . $cleaned . "\n-----END CERTIFICATE-----";
                $certificates[] = $cleaned;
            }
        }
        
        // 3. 整理每个证书的格式（每行 64 字符，符合 PEM 规范）
        $formattedCerts = [];
        foreach ($certificates as $cert) {
            $formattedCert = $this->formatSingleCert($cert);
            if ($formattedCert) {
                $formattedCerts[] = $formattedCert;
            }
        }
        
        if (empty($formattedCerts)) {
            Log::error("{$certType}格式化失败，没有有效的证书");
            return false;
        }
        
        // 4. 合并多个证书（如果有的话）
        $result = implode(PHP_EOL . PHP_EOL, $formattedCerts);
        
        Log::info("{$certType}格式化完成，最终长度: " . strlen($result));
        return $result;
    }

    /**
     * 格式化单个证书
     * 
     * @param string $cert 单个证书内容
     * @return string|false 格式化后的证书或false
     */
    private function formatSingleCert($cert)
    {
        // 去除多余空白，保留 BEGIN/END
        $certLines = explode(PHP_EOL, $cert);
        $header = '';
        $footer = '';
        $body = '';
        
        foreach ($certLines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, '-----BEGIN') === 0) {
                $header = $line;
            } elseif (strpos($line, '-----END') === 0) {
                $footer = $line;
            } else {
                $body .= $line;
            }
        }
        
        if (empty($header) || empty($footer) || empty($body)) {
            Log::warning("证书格式不完整");
            return false;
        }
        
        // 移除body中的空白字符
        $body = preg_replace('/\s+/', '', $body);
        
        // 按 64 字符分割主体内容
        $formattedBody = wordwrap($body, 64, PHP_EOL, true);
        
        // 重新拼接证书
        $formattedCert = $header . PHP_EOL 
                       . $formattedBody . PHP_EOL 
                       . $footer . PHP_EOL;
        
        return $formattedCert;
    }

      
    /**
     * 验证并整理证书内容（增强版，支持数据库格式）
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
        
        // 5. 检查是否是数据库格式的证书（没有正确换行的证书）
        $lines = explode("\n", $cleaned);
        $hasLongLine = false;
        $certCount = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // 检查是否有超长行（超过100字符）
            if (strlen($line) > 100) {
                $hasLongLine = true;
            }
            
            // 计算证书数量
            if (strpos($line, '-----BEGIN CERTIFICATE-----') === 0) {
                $certCount++;
            }
        }
        
        // 6. 如果是数据库格式的证书，使用专门的格式化方法
        // 检测条件：有超长行 或 包含证书标识 或 包含MIID/MIIE标识
        if ($hasLongLine || $certCount > 0 || strpos($cleaned, 'MIID') !== false || strpos($cleaned, 'MIIE') !== false) {
            Log::info("检测到数据库格式证书，使用格式化方法");
            return $this->formatCertFromDatabase($certContent, $certType);
        }
        
        // 如果上面的检测没有触发，但确实包含证书内容，也使用格式化方法
        if (strpos($cleaned, '-----BEGIN CERTIFICATE-----') !== false && strpos($cleaned, '-----END CERTIFICATE-----') !== false) {
            Log::info("检测到标准证书格式，但可能包含多个证书，使用格式化方法");
            return $this->formatCertFromDatabase($certContent, $certType);
        }
        
        // 7. 验证证书格式
        if (!$this->isValidCertFormat($cleaned)) {
            Log::error("{$certType}格式验证失败");
            return false;
        }
        
        // 8. 检查证书长度（RSA私钥通常至少1000字符，证书至少500字符）
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
            } elseif (strpos($line, '-----') !== 0) {
                $hasContent = true;
            }
        }
        
        return $hasBegin && $hasEnd && $hasContent;
    }

    /**
     * 从数据库获取证书并格式化的实际使用示例
     * 
     * @param string $certContent 从数据库取出的证书内容
     * @return string|false 格式化后的证书内容或false
     */
    public function processCertFromDatabase($certContent)
    {
        try {
            // 使用新的格式化方法处理数据库证书
            $formattedCert = $this->formatCertFromDatabase($certContent, '支付宝证书');
            
            if ($formattedCert) {
                Log::info("数据库证书格式化成功");
                
                // 可以选择保存到临时文件
                $tempFile = tempnam(sys_get_temp_dir(), 'alipay_cert_');
                file_put_contents($tempFile, $formattedCert);
                
                Log::info("格式化后的证书已保存到: " . $tempFile);
                return $tempFile; // 返回文件路径，或者直接返回格式化后的内容
            } else {
                Log::error("数据库证书格式化失败");
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("处理数据库证书时发生错误: " . $e->getMessage());
            return false;
        }
    }
}
