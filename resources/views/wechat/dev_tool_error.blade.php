<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信开发者工具 - 系统错误</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
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
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #721c24;
        }
        .error-log {
            width: 100%;
            height: 300px;
            background: #2d3748;
            color: #e2e8f0;
            border: 1px solid #4a5568;
            border-radius: 5px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            resize: vertical;
            margin-top: 10px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: #dc3545;">⚠️ 系统错误</h1>
        
        <div class="error-box">
            <h3>错误信息</h3>
            <p>{{ $error ?? '未知错误' }}</p>
        </div>
        
        <div class="error-box">
            <h3>调试信息</h3>
            <textarea class="error-log" readonly>
@if(isset($debug))
文件: {{ $debug['file'] ?? 'N/A' }}
行号: {{ $debug['line'] ?? 'N/A' }}

堆栈跟踪:
{{ $debug['trace'] ?? 'N/A' }}
@else
无调试信息
@endif
            </textarea>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ url('/wechat/dev-tool') }}" class="btn">重新加载页面</a>
            <button class="btn" onclick="copyError()">复制错误信息</button>
        </div>
    </div>
    
    <script>
        function copyError() {
            const errorText = document.querySelector('.error-log').value;
            navigator.clipboard.writeText(errorText).then(() => {
                alert('错误信息已复制到剪贴板');
            }).catch(() => {
                // 降级方案
                const textArea = document.createElement('textarea');
                textArea.value = errorText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('错误信息已复制到剪贴板');
            });
        }
    </script>
</body>
</html>