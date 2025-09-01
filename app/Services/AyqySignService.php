<?php

namespace App\Services;

class AyqySignService
{
    
    protected $apiKey; 
    // 客户编号，也可根据实际业务从登录态、配置等地方获取
    protected $userNo; 

    /**
     * 构造函数，初始化 apiKey 和 userNo
     * @param string $apiKey 接口秘钥
     * @param string $userNo 客户编号
     */
    public function __construct()
    {
        $this->apiKey = env('AYQY_KAMI99_APIKEY');
        $this->userNo = env('AYQY_KAMI99_USERNO');
    }

    /**
     * 生成签名
     * @param array $signParams 指定需要参与签名拼接的参数名数组，如 ['number', 'status']
     * @return string 生成的 sign 签名
     */
    public function generateSign(array $signParams = [])
    {

        // 按照规则拼接字符串：apiKey + userNo + 拼接参数值（参数值按参数名顺序拼接）
        $signStr = $this->apiKey . $this->userNo;
        foreach ($signParams as $paramValue) {
            $signStr .= $paramValue;
        }

        // 对拼接后的字符串进行 MD5 哈希并转小写
        return md5(strtolower($signStr));
    }

    public function httpGetApi($url,$params=[]) {
        $domain =  env('AYQY_KAMI99_DOMAIN');
        $url = $domain . $url;
         
        // 初始化 Guzzle Client，设置默认配置
        $signService = new AyqySignService();
        
        $sign = $signService->generateSign();

        $params['userNo'] = env('AYQY_KAMI99_USERNO');
        $params['sign'] = $sign;
        // dd($params);
        $response = \Illuminate\Support\Facades\Http::asForm()             
            ->withOptions([
                'verify' => false,
            ])
            ->post($url, $params);
       
        $data = $response->json();
        return $data;
    }



}