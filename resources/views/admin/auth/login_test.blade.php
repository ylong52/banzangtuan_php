<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录测试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .test-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .success-message {
            color: #28a745;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-title">管理员登录</div>
            <div class="login-subtitle">测试登录功能</div>
        </div>
        
        <div class="test-info">
            <strong>测试账号信息：</strong><br>
            用户名：admin98<br>
            密码：admin123
        </div>
        
        <form id="login-form">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="请输入用户名" value="admin98">
            </div>
            
            <div class="form-group">
                <label>密码</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码" value="admin123">
            </div>
            
            <div class="form-group">
                <label>验证码</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" class="form-control" id="captcha" name="captcha" placeholder="请输入验证码" style="flex: 1;">
                    <img src="{{ url('admin/captcha') }}?_={{ time() }}" alt="captcha" id="captcha-img" 
                         style="height: 42px; border-radius: 8px; cursor: pointer; border: 2px solid #e1e5e9;">
                </div>
            </div>
            
            <button type="submit" class="btn-login">登录测试</button>
        </form>
        
        <div id="result" style="margin-top: 20px;"></div>
    </div>

    <script>
        // 刷新验证码
        document.getElementById('captcha-img').addEventListener('click', function() {
            this.src = '{{ url("admin/captcha") }}?_=' + Math.random();
        });
        
        // 表单提交
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const captcha = document.getElementById('captcha').value;
            const resultDiv = document.getElementById('result');
            
            if (!username || !password || !captcha) {
                resultDiv.innerHTML = '<div class="error-message">请填写所有字段</div>';
                return;
            }
            
            // 显示加载状态
            resultDiv.innerHTML = '<div style="text-align: center; color: #666;">正在验证登录...</div>';
            
            // 发送测试请求
            fetch('{{ url("admin/test-login") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    username: username,
                    password: password,
                    captcha: captcha
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="success-message"><strong>✅ 登录成功！</strong><br>' + 
                                       data.message + '<br><br><strong>用户信息：</strong><br>' + 
                                       data.details.join('<br>') + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="error-message"><strong>❌ 登录失败</strong><br>' + 
                                       data.message + '<br><br><strong>错误详情：</strong><br>' + 
                                       data.details.join('<br>') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error-message"><strong>❌ 请求错误</strong><br>' + error.message + '</div>';
            });
        });
    </script>
</body>
</html> 