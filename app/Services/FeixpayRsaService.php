<?php
namespace App\Services;

class FeixpayRsaService
{
    protected $PublicKey;
    protected $PrivateKey;
    protected $charset;
    protected $error = null;
    /**
     * 初始化配置
     */
    public function __construct($PublicKey = '', $PrivateKey = '')
    {
        $this->charset = 'utf-8';
        $this->PublicKey = $PublicKey ?? '';
        $this->PrivateKey = $PrivateKey ?? '';
    }

    /**
     * @notes 获取错误信息
     * @return mixed
     * @author Tab
     * @date 2021/8/19 14:42
     */
    public function getError()
    {
        return $this->error;
    }
    /**
     *  验证签名
     **/
    public function rsaCheck($params)
    {
        $sign = $params['sign'] ?? '';
        $signType = $params['sign_type'];
        unset($params['sign']);
        return $this->verify($this->getSignContent($params), $sign, $signType);
    }

    function verify($data, $sign, $signType = 'RSA2')
    {
        $pubKey = $this->PublicKey;
        if($signType == 'RSA2') {
            $res = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($pubKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }elseif ($signType == 'MD5'){
            $res = $pubKey;
        } else {
            $res = $pubKey;
        }

        // try {
            //调用openssl内置方法验签，返回bool值
            if ("RSA2" == $signType) {
                $result = (bool)openssl_verify($data, base64_decode($sign), $res, version_compare(PHP_VERSION, '5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256);
            } else if ("MD5" == $signType){
                $data .= "&key={$res}";
                $v_sign = strtoupper(md5($data));
                $result = $sign == $v_sign;
            } else {
                $result = (bool)openssl_verify($data, base64_decode($sign), $res);
            }
        // } catch (Exception $e) {
        //     $this->error = '签名效验失败:' . $e->getMessage();
        //     return false;
        // }
        return $result;

    }
    public function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
      
        return $stringToBeSigned;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value)
    {
        if (is_array($value))
            return true;
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    public function generateSign($params, $signType = "RSA2")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA2")
    {
        $priKey = $this->PrivateKey;
        if($signType == 'RSA2') {
            $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
                wordwrap($priKey, 64, "\n", true) .
                "\n-----END RSA PRIVATE KEY-----";

            // ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
            if (!$res) {
                throw new \Exception('您使用的私钥格式错误，请检查RSA私钥配置');
            }
        }elseif ($signType == 'MD5') {
            $res = $priKey;
        }else{
            $res = $priKey;
        }
        try {
            if ("RSA2" == $signType) {
                openssl_sign($data, $sign, $res, version_compare(PHP_VERSION, '5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
                $sign = base64_encode($sign);
            } else if ("MD5" == $signType){
                $data .= "&key={$res}";
                $sign = strtoupper(md5($data));
            } else {
                openssl_sign($data, $sign, $res);
                $sign = base64_encode($sign);
            }
        } catch (\Exception $e) {
            $this->error = '加签失败:' . $e->getMessage();
            return false;
        }
        return $sign;
    }
    /**
     * @notes 生成RSA2密钥对
     * @return array|false
     * @author 付祥
     * @date 2023/7/14 16:17
     */
    // public static function makeRsa2()
    // {
    //     $opensslPath = "/www/server/php/80/src/ext/openssl/tests/openssl.cnf";
    //     $config = array(
    //         'config' => $opensslPath,
    //         'digest_alg' => 'sha256',
    //         'private_key_bits' => 2048,
    //         'private_key_type' => OPENSSL_KEYTYPE_RSA,
    //     );
    //     $res = openssl_pkey_new($config);
    //     openssl_pkey_export($res, $private_key_pem, null, $config);
    //     $details = openssl_pkey_get_details($res);
    //     $public_key_pem = str_replace("-----BEGIN PUBLIC KEY-----", '', $details['key']);
    //     $public_key_pem = str_replace("-----END PUBLIC KEY-----", '', $public_key_pem);
    //     $public_key_pem = str_replace("\n", '', $public_key_pem);
    //     $private_key_pem = str_replace("-----BEGIN PRIVATE KEY-----", '', $private_key_pem);
    //     $private_key_pem = str_replace("-----END PRIVATE KEY-----", '', $private_key_pem);
    //     $private_key_pem = str_replace("\n", '', $private_key_pem);
    //     $md5key = md5('hlkj_' . uniqid());

    //     $result = ['public' => $public_key_pem, 'private' => $private_key_pem, 'md5key' => $md5key];

    //     if ($result) {
    //         return $result;
    //     }

    //     return false;
    // }

    public function curlPost($url = '', $postData = '', $headers = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? json_encode($postData, JSON_UNESCAPED_UNICODE) : $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        
        return $data;
    }

    /**
     * @throws Exception
     */
    public function getEncrypt($str, $public_key_path) {
        if(empty($str)){
            return '';
        }
        $public_key = file_get_contents($public_key_path);
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new \Exception('encrypt failed');
        }
        return $sign;
    }
}