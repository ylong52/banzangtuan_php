<?php
/**
 * AyqyDecryptSignService
 * https://dy2auzvmkt.apifox.cn/
 *
 * 用于解密卡券数据，采用 AES/ECB/PKCS7Padding/256位，密钥为apiKey。
 * 输入为base64编码的密文，输出为解密后的明文JSON。
 * 注意：部分语言传输时会进行url编码，需先urldecode再解密。
 *
 * 示例：
 * $apiKey = '5fb9600dd400b5e0853caed93ebbfb4e';
 * $cards = 'mpwZ8Mf031teQm0xAu7+RWg8WSRfQpSIBrqUGil1Yts6p1c04uydM1sM5z85Azgt8HORVbJv+ER...';
 * $service = new AyqyDecryptSignService($apiKey);
 * $json = $service->decrypt($cards);
 * echo $json; // 解密后的卡券数据
 */
class AyqyDecryptSignService
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * 解密卡券数据
     * @param string $cards base64编码的密文（如有url编码需先urldecode）
     * @return string|false 解密后的明文，失败返回false
     */
    public function decrypt($cards)
    {
        // 如有url编码，需先urldecode
        $cards = urldecode($cards);
        $ciphertext = base64_decode($cards);
        if ($ciphertext === false) return false;
        $key = $this->apiKey;
        // AES-256-ECB解密，需手动去除PKCS7填充
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-ECB', $key, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING);
        if ($decrypted === false) return false;
        // 去除PKCS7填充
        $pad = ord(substr($decrypted, -1));
        if ($pad > 0 && $pad <= 32) {
            $decrypted = substr($decrypted, 0, -$pad);
        }
        return $decrypted;
    }


 
}
