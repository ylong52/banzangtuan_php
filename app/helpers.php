<?php
/*
*
 * 自定义辅助函数
 */

use App\Facades\Option;
use \Illuminate\Support\Arr;
use \Illuminate\Support\Facades\Lang;
use \Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;


function formatToTime($str){
    $res = explode('天',str_replace('　','',str_replace(' ','',$str)));
    if(count($res)>1){
        $day = intval($res[0]);
        $hour_str = $res[1];
    }else{
        $clock = explode(':',$res[0]);
        $hour_count = Arr::get($clock,0);
        $day = floor($hour_count/24);
        $clock[0] = $hour_count%24;
        $hour_str = implode(':',$clock);
    }
    return $day*3600*24+strtotime('1970-01-01 '.$hour_str);

}

 

// 保存日志
function save_log($writeLog,$outfile='') {
    $debug_backtrace = debug_backtrace();
    $dirname = storage_path('/app/public/debugInfo');

    is_dir($dirname) or mkdir($dirname, 0777, true);

    $debugfilename=$debug_backtrace[0]['file'];
    $debugline=$debug_backtrace[0]['line'];

//      //容易出错 做记录
    if (is_array($writeLog)) {
        $cn = json_encode($writeLog,JSON_UNESCAPED_UNICODE);
    }else $cn = $writeLog;

    $writeLog =  PHP_EOL.'------>>>'.date("Y-m-d H:i:s").':::: Debugfilename=='.$debugfilename."::::Debugline==".$debugline.PHP_EOL;
    $writeLog .=  $cn.PHP_EOL.PHP_EOL;

    if (false==$outfile) { $put_file = date('Y-m-d').'.txt'; }
    else $put_file = $outfile.date('Y-m-d').'.txt';
    file_put_contents($dirname.'/'.$put_file,$writeLog,FILE_APPEND);

}


if (! function_exists('httpPost')) {
    function httpPost($url, $data, $cookieFile = null, $headers = ['Content-Type: application/json; charset=utf-8'])
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data,JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return  $response;
    }

}

if (!function_exists('generateRandom')) {
    /**
     * 生成随机字符串
     * @param int $length 生成的长度
     * @param bool $onlyNumbers 是否仅生成数字
     * @param string $prefix 前缀
     * @return string
     */
    function generateRandom($length = 8, $onlyNumbers = false, $prefix = '')
    {
        // 定义字符集
        $numbers = '0123456789';
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        // 根据参数决定使用的字符集
        $characters = $onlyNumbers ? $numbers : $numbers . $letters;
        
        $randomString = '';
        $max = strlen($characters) - 1;
        
        // 生成随机字符串
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }
        
        // 添加前缀并返回
        return $prefix . $randomString;
    }
}

// ==================== 新增常用辅助函数 ====================

if (!function_exists('format_money')) {
    /**
     * 格式化金额
     * @param float $amount 金额
     * @param int $decimals 小数位数
     * @return string
     */
    function format_money($amount, $decimals = 2)
    {
        return number_format($amount, $decimals, '.', ',');
    }
}

if (!function_exists('format_date')) {
    /**
     * 格式化日期
     * @param string|Carbon $date 日期
     * @param string $format 格式
     * @return string
     */
    function format_date($date, $format = 'Y-m-d H:i:s')
    {
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date);
        }
        return $date->format($format);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * 获取客户端IP地址
     * @return string
     */
    function get_client_ip()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return request()->ip() ?: '127.0.0.1';
    }
}

if (!function_exists('is_mobile')) {
    /**
     * 检测是否为移动设备
     * @return bool
     */
    function is_mobile()
    {
        $userAgent = request()->header('User-Agent');
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone'];
        
        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

if (!function_exists('generate_order_no')) {
    /**
     * 生成订单号
     * @param string $prefix 前缀
     * @return string
     */
    function generate_order_no($prefix = '')
    {
        return $prefix . date('YmdHis') . mt_rand(1000, 9999);
    }
}

if (!function_exists('mask_phone')) {
    /**
     * 手机号脱敏
     * @param string $phone 手机号
     * @return string
     */
    function mask_phone($phone)
    {
        if (strlen($phone) == 11) {
            return substr($phone, 0, 3) . '****' . substr($phone, 7);
        }
        return $phone;
    }
}

if (!function_exists('mask_email')) {
    /**
     * 邮箱脱敏
     * @param string $email 邮箱
     * @return string
     */
    function mask_email($email)
    {
        $parts = explode('@', $email);
        if (count($parts) == 2) {
            $username = $parts[0];
            $domain = $parts[1];
            
            if (strlen($username) <= 2) {
                $maskedUsername = $username;
            } else {
                $maskedUsername = substr($username, 0, 1) . '***' . substr($username, -1);
            }
            
            return $maskedUsername . '@' . $domain;
        }
        return $email;
    }
}

if (!function_exists('array_to_tree')) {
    /**
     * 将数组转换为树形结构
     * @param array $array 数组
     * @param string $idKey ID键名
     * @param string $parentKey 父级键名
     * @param string $childrenKey 子级键名
     * @return array
     */
    function array_to_tree($array, $idKey = 'id', $parentKey = 'parent_id', $childrenKey = 'children')
    {
        $tree = [];
        $lookup = [];
        
        // 创建查找表
        foreach ($array as $item) {
            $lookup[$item[$idKey]] = $item;
            $lookup[$item[$idKey]][$childrenKey] = [];
        }
        
        // 构建树
        foreach ($lookup as $id => $item) {
            if (isset($item[$parentKey]) && $item[$parentKey] && isset($lookup[$item[$parentKey]])) {
                $lookup[$item[$parentKey]][$childrenKey][] = &$lookup[$id];
            } else {
                $tree[] = &$lookup[$id];
            }
        }
        
        return $tree;
    }
}

if (!function_exists('validate_chinese_phone')) {
    /**
     * 验证中国手机号
     * @param string $phone 手机号
     * @return bool
     */
    function validate_chinese_phone($phone)
    {
        return preg_match('/^1[3-9]\d{9}$/', $phone);
    }
}

if (!function_exists('validate_chinese_id_card')) {
    /**
     * 验证中国身份证号
     * @param string $idCard 身份证号
     * @return bool
     */
    function validate_chinese_id_card($idCard)
    {
        return preg_match('/^[1-9]\d{5}(18|19|20)\d{2}((0[1-9])|(1[0-2]))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/', $idCard);
    }
}

if (!function_exists('get_file_size')) {
    /**
     * 格式化文件大小
     * @param int $bytes 字节数
     * @return string
     */
    function get_file_size($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('generate_uuid')) {
    /**
     * 生成UUID
     * @return string
     */
    function generate_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('array_only')) {
    /**
     * 从数组中获取指定的键
     * @param array $array 数组
     * @param array $keys 键名数组
     * @return array
     */
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

if (!function_exists('array_except')) {
    /**
     * 从数组中排除指定的键
     * @param array $array 数组
     * @param array $keys 键名数组
     * @return array
     */
    function array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }
}

if (!function_exists('str_limit')) {
    /**
     * 限制字符串长度
     * @param string $value 字符串
     * @param int $limit 限制长度
     * @param string $end 结尾字符
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        
        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }
}

if (!function_exists('is_json')) {
    /**
     * 检查字符串是否为有效的JSON
     * @param string $string 字符串
     * @return bool
     */
    function is_json($string)
    {
        if (!is_string($string)) {
            return false;
        }
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('cache_remember')) {
    /**
     * 缓存记住函数（带错误处理）
     * @param string $key 缓存键
     * @param int $minutes 分钟数
     * @param callable $callback 回调函数
     * @return mixed
     */
    function cache_remember($key, $minutes, $callback)
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember($key, $minutes * 60, $callback);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Cache error: ' . $e->getMessage());
            return $callback();
        }
    }
}

if (!function_exists('log_info')) {
    /**
     * 记录信息日志
     * @param string $message 消息
     * @param array $context 上下文
     */
    function log_info($message, $context = [])
    {
        \Illuminate\Support\Facades\Log::info($message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * 记录错误日志
     * @param string $message 消息
     * @param array $context 上下文
     */
    function log_error($message, $context = [])
    {
        \Illuminate\Support\Facades\Log::error($message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * 记录警告日志
     * @param string $message 消息
     * @param array $context 上下文
     */
    function log_warning($message, $context = [])
    {
        \Illuminate\Support\Facades\Log::warning($message, $context);
    }
}

if (!function_exists('success_response')) {
    /**
     * 成功响应
     * @param mixed $data 数据
     * @param string $message 消息
     * @param int $code 状态码
     * @return \Illuminate\Http\JsonResponse
     */
    function success_response($data = null, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

if (!function_exists('error_response')) {
    /**
     * 错误响应
     * @param string $message 消息
     * @param int $code 状态码
     * @param mixed $data 数据
     * @return \Illuminate\Http\JsonResponse
     */
    function error_response($message = 'Error', $code = 400, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}

