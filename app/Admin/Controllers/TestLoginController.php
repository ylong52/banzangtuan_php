<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use App\Models\Administrator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class TestLoginController
{
    public function testLogin(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $captcha = $request->input('captcha');
        
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        // 验证验证码
        if (!$captcha) {
            $result['message'] = '验证码不能为空';
            $result['details'][] = '验证码字段为空';
            return response()->json($result);
        }
        
        if (!CaptchaController::validateCaptcha($captcha)) {
            $result['message'] = '验证码错误';
            $result['details'][] = '验证码不匹配';
            return response()->json($result);
        }
        
        // 验证用户名
        if (!$username) {
            $result['message'] = '用户名不能为空';
            $result['details'][] = '用户名字段为空';
            return response()->json($result);
        }
        
        // 验证密码
        if (!$password) {
            $result['message'] = '密码不能为空';
            $result['details'][] = '密码字段为空';
            return response()->json($result);
        }
        
        // 查找用户
        $user = Administrator::where('username', $username)->first();
        
        if (!$user) {
            $result['message'] = '用户名不存在';
            $result['details'][] = '数据库中未找到该用户';
            return response()->json($result);
        }
        
        // 验证密码
        if (!Hash::check($password, $user->password)) {
            $result['message'] = '密码错误';
            $result['details'][] = '密码验证失败';
            return response()->json($result);
        }
        
        // 登录成功
        $result['success'] = true;
        $result['message'] = '登录验证成功';
        $result['details'] = [
            '用户ID: ' . $user->id,
            '用户名: ' . $user->username,
            '创建时间: ' . $user->created_at,
            '最后登录: ' . $user->updated_at
        ];
        
        return response()->json($result);
    }
    
    public function getTestPage()
    {
        return view('admin.auth.test_login');
    }
    
    public function testCaptcha(Request $request)
    {
        $captcha = $request->input('captcha');
        
        $result = [
            'success' => false,
            'message' => '',
            'details' => []
        ];
        
        if (!$captcha) {
            $result['message'] = '验证码不能为空';
            $result['details'][] = '验证码字段为空';
            return response()->json($result);
        }
        
        if (!CaptchaController::validateCaptcha($captcha)) {
            $result['message'] = '验证码错误';
            $result['details'][] = '验证码不匹配或已过期';
            return response()->json($result);
        }
        
        $result['success'] = true;
        $result['message'] = '验证码正确';
        $result['details'] = ['验证码验证通过'];
        
        return response()->json($result);
    }
    
    public function checkUser(Request $request)
    {
        $username = $request->input('username');
        
        $user = Administrator::where('username', $username)->first();
        
        if ($user) {
            return response()->json([
                'exists' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'created_at' => $user->created_at
                ]
            ]);
        } else {
            return response()->json([
                'exists' => false,
                'message' => '用户不存在'
            ]);
        }
    }
    
    public function getCaptchaInfo()
    {
        // 获取验证码相关信息（仅用于测试）
        $captchaCode = Session::get('admin_captcha_code');
        $captchaTime = Session::get('admin_captcha_time');
        
        return response()->json([
            'captcha_code' => $captchaCode,
            'captcha_time' => $captchaTime,
            'session_id' => Session::getId(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} 