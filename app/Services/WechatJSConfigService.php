<?php

namespace App\Services;

use GuzzleHttp\Client;

class WechatJSConfigService
{
    private $appId;       // 公众号appid
    private $appSecret;   // 公众号appsecret
    private $cacheDir;    // 缓存目录（用于缓存access_token和ticket，避免频繁请求）

   
    public function __construct()
    {
        $this->appId = config('wechat.appid');
        $this->appSecret = config('wechat.appsecret');
        $this->cacheDir = "./wechat_cache/";
   
        // 创建缓存目录（如果不存在）
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new \Exception("无法创建缓存目录: {$this->cacheDir}");
            }
        }

    }

    /**
     * 获取access_token（并缓存）
     */
    private function getAccessToken() {
        $cacheFile = $this->cacheDir . 'access_token.json';
       
        // 检查缓存文件是否存在
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // 检查缓存是否有效（7200秒过期）
            if ($data && $data['expire_time'] > time()) {
                return $data['access_token'];
            }
        }
        
        // 重新获取access_token
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $result = json_decode($this->httpGet($url), true);
        
        if (isset($result['access_token'])) {
            $data['access_token'] = $result['access_token'];
            $data['expire_time'] = time() + 7000; // 提前200秒过期，避免临界点
            if (file_put_contents($cacheFile, json_encode($data)) === false) {
                throw new \Exception("无法写入access_token缓存文件: {$cacheFile}");
            }
            return $data['access_token'];
        } else {
            throw new \Exception("获取access_token失败：" . json_encode($result));
        }
    }

    /**
     * 获取jsapi_ticket（并缓存）
     */
    private function getJsApiTicket() {
        $cacheFile = $this->cacheDir . 'jsapi_ticket.json';
        
        // 检查缓存文件是否存在
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // 检查缓存是否有效（7200秒过期）
            if ($data && $data['expire_time'] > time()) {
                return $data['ticket'];
            }
        }
        
        // 用access_token换取ticket
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$accessToken}&type=jsapi";
        $result = json_decode($this->httpGet($url), true);
        
        if (isset($result['ticket']) && $result['errcode'] == 0) {
            $data['ticket'] = $result['ticket'];
            $data['expire_time'] = time() + 7000; // 提前200秒过期
            if (file_put_contents($cacheFile, json_encode($data)) === false) {
                throw new \Exception("无法写入jsapi_ticket缓存文件: {$cacheFile}");
            }
            return $data['ticket'];
        } else {
            throw new \Exception("获取jsapi_ticket失败：" . json_encode($result));
        }
    }

    /**
     * 生成随机字符串（nonceStr）
     */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 生成签名（signature）
     */
    public function getSignPackage($url) {
        $jsapiTicket = $this->getJsApiTicket();
        $nonceStr = $this->createNonceStr();
        $timestamp = time(); // 时间戳（秒级）

        // 签名算法：对所有待签名参数按照字段名的ASCII码从小到大排序后，使用URL键值对的格式拼接成字符串
        $string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        
        // SHA1加密生成签名
        $signature = sha1($string);

        return [
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "signature" => $signature,
            "jsApiList" => ['requestMerchantTransfer'],
            "url"       => $url // 用于调试，前端不需要
        ];
    }

    /**
     * HTTP GET请求工具
     */
    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 生产环境建议开启SSL验证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

}