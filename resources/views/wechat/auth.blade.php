<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权</title>
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
        .loading {
            color: #666;
            margin-bottom: 20px;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error {
            color: #e74c3c;
        }
        .success {
            color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <div class="loading">正在跳转到微信授权页面...</div>
    </div>

    <script>
        // 页面加载完成后立即跳转
        window.onload = function() {
            // 优先使用从控制器传递的 auth_url
            let authUrl = '{{ $auth_url ?? "" }}';
            
            // 如果没有从控制器传递，则从URL参数获取
            if (!authUrl) {
                const urlParams = new URLSearchParams(window.location.search);
                authUrl = urlParams.get('auth_url');
            }
            
            if (authUrl) {
                // 如果有授权URL，直接跳转
                console.log('跳转到微信授权页面:', authUrl);
                window.location.href = decodeURIComponent(authUrl);
            } else {
                // 如果没有授权URL，显示错误信息
                document.querySelector('.container').innerHTML = `
                    <h3 class="error">错误</h3>
                    <p>未找到授权URL，请重新访问。</p>
                    <button onclick="window.history.back()">返回</button>
                `;
            }
        };
    </script>
</body>
</html> 