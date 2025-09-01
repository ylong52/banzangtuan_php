<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权成功</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success-icon {
            color: #27ae60;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .openid {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            word-break: break-all;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">✓</div>
        <div class="success">{{ $message }}</div>
        
        @if($openid)
        <p>您的微信 OpenID：</p>
        <div class="openid">{{ $openid }}</div>
        @endif
        
        <p>授权已完成，您可以关闭此页面或返回应用。</p>
        
        <div style="margin-top: 20px;">
            <button class="btn" onclick="window.close()">关闭页面</button>
            <a href="javascript:history.back()" class="btn">返回上页</a>
        </div>
    </div>

    <script>
        // 3秒后自动关闭页面
        setTimeout(function() {
            window.close();
        }, 3000);
    </script>
</body>
</html> 