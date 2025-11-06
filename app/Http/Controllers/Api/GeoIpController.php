<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use GeoIp2\WebService\Client;

class GeoIpController extends Controller
{
    function geoip(Request $request) {
        // 获取原始请求数据
        $rawData = $request->getContent();
        
        // 获取所有请求数据
        $allData = $request->all();
        
        // 获取请求头信息
        $headers = $request->headers->all();
        
        // 创建文件路径
        $filePath = storage_path('logs/geoip_data.txt');
        
        // 准备写入的数据
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'raw_data' => $rawData,
            'parsed_data' => $allData,
            'headers' => $headers
        ];
        
        // 将数据转换为JSON格式
        $jsonData = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // 写入文件（追加模式）
        file_put_contents($filePath, $jsonData . "\n" . str_repeat('-', 80) . "\n", FILE_APPEND | LOCK_EX);
        
        // 返回响应
        return response()->json([
            'status' => 'success',
            'message' => '数据已保存',
            'timestamp' => date('Y-m-d H:i:s'),
            'received_data' => $allData,
            'raw_data' => $rawData
        ]);
    }
    

}
