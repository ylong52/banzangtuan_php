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
            margin: 50px auto;
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
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>微信授权测试</h1>
        <p>测试微信获取openid的完整流程</p>
        
        <div>
            <button class="btn" onclick="checkStatus()">检查当前状态</button>
            <button class="btn" onclick="startAuth()">开始微信授权</button>
            <button class="btn btn-secondary" onclick="clearCache()">清除缓存</button>
        </div>
        
        <div id="result" class="result" style="display: none;"></div>
    </div>

    <script>
        function showResult(data, isError = false) {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'result ' + (isError ? 'error' : 'success');
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }

        function checkStatus() {
            fetch('/wechat/status')
                .then(response => response.json())
                .then(data => {
                    showResult(data);
                })
                .catch(error => {
                    showResult({error: error.message}, true);
                });
        }

        function startAuth() {
            // 直接跳转到授权页面
            window.location.href = '/wechat/auth';
        }

        function clearCache() {
            fetch('/wechat/clear')
                .then(response => response.json())
                .then(data => {
                    showResult(data);
                })
                .catch(error => {
                    showResult({error: error.message}, true);
                });
        }

        // 页面加载时自动检查状态
        window.onload = function() {
            checkStatus();
        };
    </script>
</body>
</html> 