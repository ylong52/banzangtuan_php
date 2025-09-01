<?php
namespace App\Services;

/**
 * 京东开放平台接口调用类
 */
class JdUnionClient
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 验证系统参数是否完整、格式是否符合要求
     * @param array $params 待验证参数（含系统参数、应用级参数）
     * @return bool|string 验证通过返回 true，失败返回错误描述
     */
    private function validateSystemParams($params)
    {
        // 系统必传参数检查
        $requiredSystemParams = ['method', 'app_key', 'timestamp', 'format', 'v', 'sign'];
        foreach ($requiredSystemParams as $param) {
            if (empty($params[$param])) {
                return "系统参数 {$param} 不能为空";
            }
        }

        // 时间戳格式验证（示例简单校验，严格可结合正则或时间解析）
        $timestamp = $params['timestamp'];
        if (strtotime($timestamp) === false) {
            return "时间戳格式错误，需符合 yyyy - MM - dd HH:mm:ss 格式，当前值：{$timestamp}";
        }

        // access_token 按需校验（若接口要求必传则检查，这里示例判断：若 method 需授权则校验）
        // 实际根据具体接口文档调整，比如 jd.union.open.goods.query 若要求授权则开启下面校验
        // if (empty($params['access_token'])) {
        //     return "access_token 不能为空（接口需授权时）";
        // }

        return true;
    }

    /**
     * 生成签名（遵循截图中签名算法：参数按字母排序拼接，首尾加 appSecret，MD5 后转大写）
     * @param array $params 参与签名的参数（系统参数 + 应用级参数）
     * @return string 生成的签名
     */
    private function generateSign($params)
    {
        // 移除 sign 避免参与签名（若存在）
        if (isset($params['sign'])) {
            unset($params['sign']);
        }

        // 按参数名首字母升序排序
        ksort($params);

        // 拼接参数名和值
        $signStr = $this->config['appSecret'];
        foreach ($params as $key => $value) {
            $signStr .= $key . $value;
        }
        $signStr .= $this->config['appSecret'];

        // MD5 加密并转大写
        return strtoupper(md5($signStr));
    }

    /**
     * 发送接口请求
     * @param array $bizParams 应用级参数（如 goodsReqDTO 等业务参数）
     * @return array 接口响应结果，含错误信息、原始响应、解析后的内容等
     */
    public function execute($bizParams)
    {
        // 构建系统参数 + 应用级参数
        $systemParams = [
            'method' => $this->config['method'],
            'app_key' => $this->config['appKey'],
            'access_token' => $this->config['accessToken'],
            'timestamp' => $this->config['timestamp'],
            'format' => $this->config['format'],
            'v' => $this->config['v'],
            '360buy_param_json' => json_encode($bizParams), // 应用级参数，按接口要求封装
        ];
// var_dump("88>>>",$systemParams);  

        // 生成签名
        $systemParams['sign'] = $this->generateSign($systemParams);

        // 参数校验
        $validateResult = $this->validateSystemParams($systemParams);
        if ($validateResult!== true) {
            return [
                'error' => true,
                'message' => $validateResult,
            ];
        }

        // 拼接请求 URL
        $queryString = http_build_query($systemParams);
        $requestUrl = $this->config['serverUrl']. '?'. $queryString;

        // 发起 HTTP 请求（使用 cURL）
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 生产环境建议开启证书校验
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 处理响应
        $result = [
            'http_code' => $httpCode,
           'response' => $response,
        ];

        try {
            $parsedResponse = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['parsed'] = $parsedResponse;
            }
        } catch (Exception $e) {
            $result['error'] = '响应解析失败：'. $e->getMessage();
        }

        return $result;
    }
}
