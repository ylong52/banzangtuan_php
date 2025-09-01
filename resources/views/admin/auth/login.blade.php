<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{{config('admin.title')}} | {{ trans('admin.login') }}</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  @if(!is_null($favicon = Admin::favicon()))
  <link rel="shortcut icon" href="{{$favicon}}">
  @endif

  <!-- Bootstrap 3.3.5 -->
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css") }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/font-awesome/css/font-awesome.min.css") }}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/dist/css/AdminLTE.min.css") }}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/iCheck/square/blue.css") }}">
  <link rel="stylesheet" href="{{ admin_asset("vendor/laravel-admin/toastr/build/toastr.min.css") }}">
  
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
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
      margin: 20px;
    }
    
    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .login-logo {
      font-size: 28px;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
    
    .login-subtitle {
      color: #666;
      font-size: 14px;
      margin-bottom: 30px;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-control {
      border: 2px solid #e1e5e9;
      border-radius: 8px;
      padding: 12px 15px;
      font-size: 14px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }
    
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      background-color: white;
    }
    
    .form-control.has-error {
      border-color: #dc3545;
    }
    
    .input-group-addon {
      background: none;
      border: none;
      padding: 0;
    }
    
    .captcha-img {
      border-radius: 8px;
      cursor: pointer;
      transition: opacity 0.3s ease;
      border: 2px solid #e1e5e9;
    }
    
    .captcha-img:hover {
      opacity: 0.8;
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
      transition: all 0.3s ease;
      margin-top: 10px;
    }
    
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .error-message {
      color: #dc3545;
      font-size: 12px;
      margin-top: 5px;
      display: flex;
      align-items: center;
    }
    
    .error-message i {
      margin-right: 5px;
    }
    
    .field-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      display: block;
    }
    
    .field-hint {
      font-size: 11px;
      color: #999;
      margin-top: 4px;
    }
    
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #666;
      cursor: pointer;
      font-size: 16px;
    }
    
    .password-field {
      position: relative;
    }
    
    @media (max-width: 480px) {
      .login-container {
        margin: 10px;
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
<div class="login-container">
  <div class="login-header">
    <div class="login-logo">
      <i class="fa fa-shield" style="color: #667eea; margin-right: 10px;"></i>
      {{ $sitename }}
    </div>
    <div class="login-subtitle">欢迎使用管理后台</div>
  </div>

  <form action="{{ admin_url('auth/login') }}" method="post">
    <div class="form-group">
      <label class="field-label">
        用户名
        <i class="fa fa-question-circle" style="color: #999; font-size: 12px; margin-left: 5px;" title="用户名不能包含空格、大写字母或特殊字符"></i>
      </label>
      <input type="text" class="form-control {!! !$errors->has('username') ?: 'has-error' !!}" 
             placeholder="请输入用户名" name="username" value="{{ old('username') }}">
      <div class="field-hint">用户名不应包含空格、大写字母或特殊字符</div>
      @if($errors->has('username'))
          @foreach($errors->get('username') as $message)
              <div class="error-message">
                <i class="fa fa-exclamation-circle"></i>{{$message}}
              </div>
          @endforeach
      @endif
    </div>

    <div class="form-group">
      <label class="field-label">密码</label>
      <div class="password-field">
        <input type="password" class="form-control {!! !$errors->has('password') ?: 'has-error' !!}" 
               placeholder="请输入密码" name="password" id="password">
        <button type="button" class="password-toggle" onclick="togglePassword()">
          <i class="fa fa-eye" id="password-icon"></i>
        </button>
      </div>
      @if($errors->has('password'))
          @foreach($errors->get('password') as $message)
              <div class="error-message">
                <i class="fa fa-exclamation-circle"></i>{{$message}}
              </div>
          @endforeach
      @endif
    </div>
    
    <!-- 验证码 -->
    <div class="form-group">
      <label class="field-label">验证码</label>
      <div class="input-group">
        <input type="text" class="form-control {!! !$errors->has('captcha') ?: 'has-error' !!}" 
               placeholder="请输入验证码" name="captcha" value="" autocomplete="off">
        <span class="input-group-addon">
          <img src="{{ url('admin/captcha') }}?_={{ time() }}" alt="captcha" id="captcha-img" 
               class="captcha-img" style="height: 42px; width: 120px;">
        </span>
      </div>
      @if($errors->has('captcha'))
          @foreach($errors->get('captcha') as $message)
              <div class="error-message">
                <i class="fa fa-exclamation-circle"></i>{{$message}}
              </div>
          @endforeach
      @endif
    </div>
    
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button type="submit" class="btn btn-login">
      <i class="fa fa-sign-in" style="margin-right: 8px;"></i>登录
    </button>
  </form>
</div>

<!-- jQuery 2.1.4 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js")}} "></script>
<!-- Bootstrap 3.3.5 -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js")}}"></script>
<!-- iCheck -->
<script src="{{ admin_asset("vendor/laravel-admin/AdminLTE/plugins/iCheck/icheck.min.js")}}"></script>
<!-- 弹窗 -->
<script src="{{ admin_asset('vendor/laravel-admin/toastr/build/toastr.min.js') }}"></script>

<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' // optional
    });
    
    // 验证码点击刷新
    document.getElementById('captcha-img').addEventListener('click', function() {
      this.src = '{{ url('admin/captcha') }}?_=' + Math.random();
    });
    
    // 表单提交前验证
    $('form').on('submit', function(e) {
      var username = $('input[name="username"]').val();
      var password = $('input[name="password"]').val();
      var captcha = $('input[name="captcha"]').val();
      
      if (!username) {
        e.preventDefault();
        alert('请输入用户名');
        return false;
      }
      
      if (!password) {
        e.preventDefault();
        alert('请输入密码');
        return false;
      }
      
      if (!captcha) {
        e.preventDefault();
        alert('请输入验证码');
        return false;
      }
    });
  });
  
  // 密码显示/隐藏切换
  function togglePassword() {
    var passwordField = document.getElementById('password');
    var passwordIcon = document.getElementById('password-icon');
    
    if (passwordField.type === 'password') {
      passwordField.type = 'text';
      passwordIcon.className = 'fa fa-eye-slash';
    } else {
      passwordField.type = 'password';
      passwordIcon.className = 'fa fa-eye';
    }
  }
  
  // 显示成功消息
  @if(session()->has('sign-up-success'))
    toastr.success("{{ session('sign-up-success') }}", null, []);
  @endif
  @if(session()->has('success'))
    toastr.success("{{ session('success') }}", null, []);
  @endif
</script>

</body>
</html>
