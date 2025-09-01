<?php

namespace App\Admin\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class CaptchaController
{
    public function generateCaptcha()
    {
        // Create a blank image with specified dimensions
        $width = 120;
        $height = 40;
        $image = imagecreatetruecolor($width, $height);
        
        // Set background color
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);
        
        // Generate random code (only numbers for easier reading)
        $characters = '0123456789';
        $captchaCode = '';
        for ($i = 0; $i < 4; $i++) {
            $captchaCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Generate unique captcha ID
        $captchaId = md5(uniqid('captcha', true));
        
        // Store captcha in file system
        $captchaData = [
            'code' => $captchaCode,
            'time' => time(),
            'ip' => request()->ip()
        ];
        
        $captchaFile = storage_path('app/captcha_' . $captchaId . '.json');
        file_put_contents($captchaFile, json_encode($captchaData));
        
        // Set cookie with captcha ID
        setcookie('captcha_id', $captchaId, time() + 600, '/', '', false, true);
        
        // For debugging - save to file
        file_put_contents(storage_path('logs/captcha_generated.log'), 
            'Generated captcha: ' . $captchaCode . 
            ', ID: ' . $captchaId . 
            ', IP: ' . request()->ip() . 
            ', Time: ' . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND);
        
        // Add random lines
        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($image, rand(0, 200), rand(0, 200), rand(0, 200));
            imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
        }
        
        // Add random dots
        for ($i = 0; $i < 100; $i++) {
            $dotColor = imagecolorallocate($image, rand(0, 200), rand(0, 200), rand(0, 200));
            imagesetpixel($image, rand(0, $width), rand(0, $height), $dotColor);
        }
        
        // Add captcha text
        for ($i = 0; $i < strlen($captchaCode); $i++) {
            $textColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
            $fontSize = rand(14, 20);
            $x = ($i * 25) + 10;
            $y = $height - 10;
            imagechar($image, $fontSize, $x, $y - rand(0, 10), $captchaCode[$i], $textColor);
        }
        
        // Output the image
        header('Content-Type: image/jpeg');
        imagejpeg($image);
        imagedestroy($image);
        exit;
    }
    
    public static function validateCaptcha($captcha)
    {
        // Get captcha ID from cookie
        $captchaId = $_COOKIE['captcha_id'] ?? null;
        
        if (!$captchaId) {
            file_put_contents(storage_path('logs/captcha_validation.log'), 
                'Input: ' . $captcha . 
                ', Error: No captcha ID in cookie' .
                ', Time: ' . date('Y-m-d H:i:s') . "\n", 
                FILE_APPEND);
            return false;
        }
        
        // Read captcha data from file
        $captchaFile = storage_path('app/captcha_' . $captchaId . '.json');
        
        if (!file_exists($captchaFile)) {
            file_put_contents(storage_path('logs/captcha_validation.log'), 
                'Input: ' . $captcha . 
                ', Error: Captcha file not found' .
                ', Time: ' . date('Y-m-d H:i:s') . "\n", 
                FILE_APPEND);
            return false;
        }
        
        $captchaData = json_decode(file_get_contents($captchaFile), true);
        $storedCaptcha = $captchaData['code'] ?? null;
        $captchaTime = $captchaData['time'] ?? null;
        
        // Check if captcha is expired (10 minutes)
        if (!$storedCaptcha || !$captchaTime || (time() - $captchaTime) > 600) {
            file_put_contents(storage_path('logs/captcha_validation.log'), 
                'Input: ' . $captcha . 
                ', Stored: ' . $storedCaptcha . 
                ', Expired: Yes' .
                ', Time: ' . date('Y-m-d H:i:s') . "\n", 
                FILE_APPEND);
            return false;
        }
        
        // For debugging - log validation attempt
        file_put_contents(storage_path('logs/captcha_validation.log'), 
            'Input: ' . $captcha . 
            ', Stored: ' . $storedCaptcha . 
            ', Match: ' . ($captcha === $storedCaptcha ? 'Yes' : 'No') . 
            ', Time: ' . date('Y-m-d H:i:s') . "\n", 
            FILE_APPEND);
        
        // Delete the captcha file after validation
        unlink($captchaFile);
        
        return $captcha === $storedCaptcha;
    }
}