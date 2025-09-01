<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>登录功能测试</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 20px;
    }
    
    .test-container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      padding: 30px;
    }
    
    .test-section {
      margin-bottom: 30px;
      padding: 20px;
      border: 1px solid #e0e0e0;
      border-radius: 5px;
    }
    
    .test-title {
      font-size: 18px;
      font-weight: bold;
      color: #333;
      margin-bottom: 15px;
    }
    
    .test-result {
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
  </style>
</head>
<body>
  <div class="test-container">
    <h1>登录功能测试</h1>
    
    <div class="test-section">
      <div class="test-title">1. 验证码功能测试</div>
      <div class="form-group">
        <label>验证码图片：</label>
        <img src="{{ url('admin/captcha') }}?_={{ time() }}" alt="captcha" id="captcha-img" 
             style="border: 1px solid #ddd; cursor: pointer; height: 40px;">
        <button class="btn" onclick="refreshCaptcha()">刷新验证码</button>
      </div>
      <div class="test-result info">
        <strong>说明：</strong>点击验证码图片或刷新按钮可以生成新的验证码
      </div>
    </div>
    
    <div class="test-section">
      <div class="test-title">2. 表单验证测试</div>
      <form id="test-form">
        <div class="form-group">
          <label>用户名：</label>
          <input type="text" id="test-username" placeholder="请输入用户名">
        </div>
        <div class="form-group">
          <label>密码：</label>
          <input type="password" id="test-password" placeholder="请输入密码">
        </div>
        <div class="form-group">
          <label>验证码：</label>
          <input type="text" id="test-captcha" placeholder="请输入验证码">
        </div>
        <button type="button" class="btn" onclick="testValidation()">测试表单验证</button>
        <button type="button" class="btn" onclick="testActualLogin()" style="background: #28a745;">测试实际登录</button>
      </form>
      <div id="validation-result"></div>
      <div id="login-result"></div>
    </div>
    
    <div class="test-section">
      <div class="test-title">3. 登录功能测试</div>
      <div class="test-result info">
        <strong>测试步骤：</strong>
        <ol>
          <li>访问 <a href="{{ admin_url('auth/login') }}" target="_blank">登录页面</a></li>
          <li>输入正确的用户名和密码</li>
          <li>输入验证码</li>
          <li>点击登录按钮</li>
          <li>检查是否能成功登录到管理后台</li>
        </ol>
      </div>
    </div>
    
    <div class="test-section">
      <div class="test-title">4. 错误处理测试</div>
      <div class="test-result info">
        <strong>测试场景：</strong>
        <ul>
          <li>输入错误的用户名/密码</li>
          <li>输入错误的验证码</li>
          <li>不填写任何字段直接提交</li>
          <li>检查错误提示是否正确显示</li>
        </ul>
      </div>
    </div>
    
    <div class="test-section">
      <div class="test-title">5. 响应式设计测试</div>
      <div class="test-result info">
        <strong>测试内容：</strong>
        <ul>
          <li>在不同屏幕尺寸下测试页面显示</li>
          <li>测试移动设备上的显示效果</li>
          <li>检查表单元素在不同设备上的可用性</li>
        </ul>
      </div>
    </div>
  </div>

  <script>
    function refreshCaptcha() {
      document.getElementById('captcha-img').src = '{{ url('admin/captcha') }}?_=' + Math.random();
    }
    
    function testValidation() {
      var username = document.getElementById('test-username').value;
      var password = document.getElementById('test-password').value;
      var captcha = document.getElementById('test-captcha').value;
      var result = document.getElementById('validation-result');
      
      var errors = [];
      
      if (!username) {
        errors.push('用户名不能为空');
      }
      
      if (!password) {
        errors.push('密码不能为空');
      }
      
      if (!captcha) {
        errors.push('验证码不能为空');
      }
      
      if (errors.length > 0) {
        result.innerHTML = '<div class="test-result error"><strong>验证失败：</strong><br>' + errors.join('<br>') + '</div>';
      } else {
        result.innerHTML = '<div class="test-result success"><strong>验证通过：</strong>所有字段都已填写</div>';
      }
    }
    
    // 测试实际登录功能
    function testActualLogin() {
      var username = document.getElementById('test-username').value;
      var password = document.getElementById('test-password').value;
      var captcha = document.getElementById('test-captcha').value;
      var result = document.getElementById('login-result');
      
      if (!username || !password || !captcha) {
        result.innerHTML = '<div class="test-result error"><strong>错误：</strong>请填写所有字段</div>';
        return;
      }
      
      // 显示加载状态
      result.innerHTML = '<div class="test-result info"><strong>正在测试登录...</strong></div>';
      
      // 发送AJAX请求
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
          result.innerHTML = '<div class="test-result success"><strong>登录成功：</strong><br>' + 
                           data.message + '<br><br><strong>详细信息：</strong><br>' + 
                           data.details.join('<br>') + '</div>';
        } else {
          result.innerHTML = '<div class="test-result error"><strong>登录失败：</strong><br>' + 
                           data.message + '<br><br><strong>详细信息：</strong><br>' + 
                           data.details.join('<br>') + '</div>';
        }
      })
      .catch(error => {
        result.innerHTML = '<div class="test-result error"><strong>请求错误：</strong><br>' + error.message + '</div>';
      });
    }
    
    // 页面加载时自动刷新验证码
    window.onload = function() {
      refreshCaptcha();
    };
  </script>
</body>
</html> 