<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权调试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        .section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .error {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>微信授权调试页面</h1>
        
        <div class="section info">
            <h3>当前配置信息</h3>
            <pre>{{ json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        
        <div class="section">
            <h3>测试操作</h3>
            <button class="btn" onclick="testAuthUrl()">测试授权URL生成</button>
            <button class="btn" onclick="testCallback()">测试回调地址</button>
            <button class="btn" onclick="checkConfig()">检查配置</button>
        </div>
        
        <div id="result" class="section" style="display: none;"></div>
    </div>

    <script>
        function showResult(data, type = 'info') {
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'section ' + type;
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }

        function testAuthUrl() {
            fetch('/wechat/debug/auth-url')
                .then(response => response.json())
                .then(data => {
                    showResult(data, data.code === 200 ? 'success' : 'error');
                })
                .catch(error => {
                    showResult({error: error.message}, 'error');
                });
        }

        function testCallback() {
            fetch('/wechat/debug/callback-test')
                .then(response => response.json())
                .then(data => {
                    showResult(data, data.code === 200 ? 'success' : 'error');
                })
                .catch(error => {
                    showResult({error: error.message}, 'error');
                });
        }

        function checkConfig() {
            fetch('/wechat/debug/config')
                .then(response => response.json())
                .then(data => {
                    showResult(data, data.code === 200 ? 'success' : 'error');
                })
                .catch(error => {
                    showResult({error: error.message}, 'error');
                });
        }
    </script>
</body>
</html> 