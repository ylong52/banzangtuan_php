<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录调试</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .debug-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .debug-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        
        .debug-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .result {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <h1>登录调试页面</h1>
        
        <div class="debug-section">
            <div class="debug-title">1. 验证码测试</div>
            <div class="form-group">
                <label>验证码图片：</label>
                <img src="{{ url('admin/captcha') }}?_={{ time() }}" alt="captcha" id="captcha-img" 
                     style="border: 1px solid #ddd; cursor: pointer; height: 40px;">
                <button class="btn" onclick="refreshCaptcha()">刷新验证码</button>
            </div>
            <div class="form-group">
                <label>验证码输入：</label>
                <input type="text" id="captcha-input" placeholder="请输入验证码">
                <button class="btn" onclick="testCaptcha()">测试验证码</button>
            </div>
            <div id="captcha-result"></div>
        </div>
        
        <div class="debug-section">
            <div class="debug-title">2. 用户登录测试</div>
            <div class="form-group">
                <label>用户名：</label>
                <input type="text" id="username" value="admin98" readonly>
            </div>
            <div class="form-group">
                <label>密码：</label>
                <input type="password" id="password" value="admin123" readonly>
            </div>
            <div class="form-group">
                <label>验证码：</label>
                <input type="text" id="login-captcha" placeholder="请输入验证码">
            </div>
            <button class="btn" onclick="testLogin()">测试登录</button>
            <div id="login-result"></div>
        </div>
        
        <div class="debug-section">
            <div class="debug-title">3. 调试信息</div>
            <div class="result info">
                <strong>测试账号：</strong><br>
                用户名：admin98<br>
                密码：admin123<br>
                表名：admin_users
            </div>
            <button class="btn" onclick="checkUser()">检查用户是否存在</button>
            <div id="user-result"></div>
        </div>
    </div>

    <script>
        function refreshCaptcha() {
            document.getElementById('captcha-img').src = '{{ url("admin/captcha") }}?_=' + Math.random();
            document.getElementById('captcha-result').innerHTML = '<div class="result info">验证码已刷新</div>';
        }
        
        function testCaptcha() {
            const captcha = document.getElementById('captcha-input').value;
            const resultDiv = document.getElementById('captcha-result');
            
            if (!captcha) {
                resultDiv.innerHTML = '<div class="result error">请输入验证码</div>';
                return;
            }
            
            // 发送验证码测试请求
            fetch('{{ url("admin/test-captcha") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ captcha: captcha })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="result success">✅ 验证码正确</div>';
                } else {
                    resultDiv.innerHTML = '<div class="result error">❌ 验证码错误: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="result error">❌ 请求错误: ' + error.message + '</div>';
            });
        }
        
        function testLogin() {
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const captcha = document.getElementById('login-captcha').value;
            const resultDiv = document.getElementById('login-result');
            
            if (!captcha) {
                resultDiv.innerHTML = '<div class="result error">请输入验证码</div>';
                return;
            }
            
            resultDiv.innerHTML = '<div class="result info">正在测试登录...</div>';
            
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
                    resultDiv.innerHTML = '<div class="result success"><strong>✅ 登录成功！</strong><br>' + 
                                       data.message + '<br><br><strong>用户信息：</strong><br>' + 
                                       data.details.join('<br>') + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="result error"><strong>❌ 登录失败</strong><br>' + 
                                       data.message + '<br><br><strong>错误详情：</strong><br>' + 
                                       data.details.join('<br>') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="result error"><strong>❌ 请求错误</strong><br>' + error.message + '</div>';
            });
        }
        
        function checkUser() {
            const resultDiv = document.getElementById('user-result');
            resultDiv.innerHTML = '<div class="result info">正在检查用户...</div>';
            
            fetch('{{ url("admin/check-user") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ username: 'admin98' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    resultDiv.innerHTML = '<div class="result success"><strong>✅ 用户存在</strong><br>' + 
                                       '用户ID: ' + data.user.id + '<br>' +
                                       '用户名: ' + data.user.username + '<br>' +
                                       '姓名: ' + data.user.name + '<br>' +
                                       '创建时间: ' + data.user.created_at + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class="result error"><strong>❌ 用户不存在</strong><br>' + 
                                       '请先创建用户 admin98</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="result error"><strong>❌ 请求错误</strong><br>' + error.message + '</div>';
            });
        }
    </script>
</body>
</html> 