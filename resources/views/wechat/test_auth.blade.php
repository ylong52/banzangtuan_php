<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权测试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #07c160;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #06ad56;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>微信授权测试</h1>
        <p>点击下面的按钮开始微信授权流程：</p>
        
        <button class="btn" onclick="startAuth()">开始微信授权</button>
        <button class="btn btn-secondary" onclick="getOpenid()">获取 OpenID</button>
        <button class="btn btn-secondary" onclick="clearOpenid()">清除 OpenID</button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>处理中...</p>
        </div>
        
        <div id="result"></div>
    </div>

    <script>
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('result').innerHTML = '';
        }
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
        
        function showResult(data, isError = false) {
            const resultDiv = document.getElementById('result');
            const className = isError ? 'result error' : 'result success';
            resultDiv.className = className;
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        function startAuth() {
            showLoading();
            // 直接跳转到授权页面
            window.location.href = '/api/wechat/auth';
        }
        
        function getOpenid() {
            showLoading();
            fetch('/api/wechat/openid')
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    showResult(data, !data.success);
                })
                .catch(error => {
                    hideLoading();
                    showResult({ error: error.message }, true);
                });
        }
        
        function clearOpenid() {
            showLoading();
            fetch('/api/wechat/clear-openid', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                showResult(data, !data.success);
            })
            .catch(error => {
                hideLoading();
                showResult({ error: error.message }, true);
            });
        }
        
        // 页面加载时检查是否有 openid
        window.onload = function() {
            getOpenid();
        };
    </script>
</body>
</html> 