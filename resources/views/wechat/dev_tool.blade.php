<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信开发者工具 - 一键获取OpenID</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .title {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #721c24;
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
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-danger {
            background: #dc3545;
        }
        .code-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .loading {
            display: none;
            text-align: center;
            color: #666;
        }
        .error-log {
            width: 100%;
            height: 200px;
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
        .debug-info {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="title">🔧 微信开发者工具 - 一键获取OpenID</h1>
        
        <!-- 服务器错误显示区域 -->
        @if(isset($error) && $error)
        <div class="error-box">
            <h3>⚠️ 服务器错误</h3>
            <p>{{ $error }}</p>
            @if(isset($debug_info))
            <textarea class="error-log" readonly>调试信息:
用户代理: {{ $debug_info['user_agent'] ?? 'N/A' }}
是否微信浏览器: {{ $debug_info['is_wechat'] ? '是' : '否' }}
会话ID: {{ $debug_info['session_id'] ?? 'N/A' }}
请求URL: {{ $debug_info['request_url'] ?? 'N/A' }}
时间戳: {{ $debug_info['timestamp'] ?? 'N/A' }}</textarea>
            @endif
        </div>
        @endif

        <!-- 客户端错误显示区域 -->
        <div id="error-container" style="display: none;" class="error-box">
            <h3>❌ 客户端错误</h3>
            <div id="error-message"></div>
            <textarea id="error-log" class="error-log" readonly placeholder="错误详情将显示在这里..."></textarea>
            <button class="btn btn-warning" onclick="clearErrors()">清除错误</button>
            <button class="btn" onclick="copyErrors()">复制错误信息</button>
        </div>
        
        <div class="info-box">
            <h3>📋 配置信息</h3>
            <p><strong>AppID:</strong> {{ $appid ?? 'N/A' }}</p>
            <p><strong>回调地址:</strong> {{ $callback_url ?? 'N/A' }}</p>
            <p><strong>授权域名:</strong> juan.ai-book.top</p>
            <p><strong>State:</strong> {{ $state ?? 'N/A' }}</p>
        </div>

        @if(isset($cached_openid) && $cached_openid)
        <div class="success-box">
            <h3>✅ 当前OpenID</h3>
            <div class="code-box">{{ $cached_openid }}</div>
            <button class="btn btn-warning" onclick="clearCache()">清除缓存</button>
        </div>
        @endif

        <div class="info-box">
            <h3>🚀 快速操作</h3>
            <button class="btn" onclick="getAuthUrl()">生成新的授权链接</button>
            <button class="btn btn-success" onclick="checkStatus()">检查OpenID状态</button>
            <button class="btn btn-danger" onclick="clearCache()">清除缓存</button>
            <button class="btn" onclick="validateAuthUrl()">验证授权链接</button>
            <button class="btn" onclick="checkWechatConfig()">检查微信配置</button>
        </div>

        <div class="info-box">
            <h3>🔗 微信授权链接</h3>
            <div class="code-box" id="auth-url">{{ $auth_url ?? '点击"生成新的授权链接"按钮生成' }}</div>
            <button class="btn" onclick="copyAuthUrl()">复制链接</button>
            <button class="btn btn-success" onclick="openInWechat()">在微信中打开</button>
        </div>

        <div class="info-box">
            <h3>📱 使用说明</h3>
            <ol>
                <li>点击"复制链接"复制上方的授权链接</li>
                <li>在微信中打开链接（可以发送给文件传输助手）</li>
                <li>完成授权后，点击"检查OpenID状态"查看结果</li>
                <li>或者直接点击"在微信中打开"（需要在手机上操作）</li>
            </ol>
        </div>

        <!-- 调试信息 -->
        <div class="debug-info">
            <h3>🔍 调试信息</h3>
            <p><strong>当前URL:</strong> <span id="current-url">{{ url()->current() }}</span></p>
            <p><strong>User Agent:</strong> <span id="user-agent"></span></p>
            <p><strong>是否微信浏览器:</strong> <span id="is-wechat"></span></p>
            <p><strong>时间戳:</strong> <span id="timestamp">{{ time() }}</span></p>
        </div>

        <div id="status-box" class="status" style="display: none;"></div>
        <div class="loading" id="loading">⏳ 处理中...</div>
    </div>

    <script>
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 设置调试信息
            document.getElementById('user-agent').textContent = navigator.userAgent;
            document.getElementById('is-wechat').textContent = /MicroMessenger/i.test(navigator.userAgent) ? '是' : '否';
            
            // 设置CSRF token
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // 全局错误处理
            window.addEventListener('error', function(e) {
                logError('JavaScript错误', e.error ? e.error.toString() : e.message);
            });
            
            window.addEventListener('unhandledrejection', function(e) {
                logError('Promise错误', e.reason ? e.reason.toString() : '未知Promise错误');
            });
        });

        // 错误日志函数
        function logError(title, details) {
            const errorContainer = document.getElementById('error-container');
            const errorMessage = document.getElementById('error-message');
            const errorLog = document.getElementById('error-log');
            
            const timestamp = new Date().toLocaleString();
            const errorText = `[${timestamp}] ${title}: ${details}\n`;
            
            errorMessage.textContent = title;
            errorLog.value += errorText;
            errorContainer.style.display = 'block';
            
            // 滚动到错误区域
            errorContainer.scrollIntoView({ behavior: 'smooth' });
        }

        // 清除错误
        function clearErrors() {
            document.getElementById('error-container').style.display = 'none';
            document.getElementById('error-log').value = '';
        }

        // 复制错误信息
        function copyErrors() {
            const errorLog = document.getElementById('error-log').value;
            const errorMessage = document.getElementById('error-message').textContent;
            const fullError = `错误信息: ${errorMessage}\n\n错误详情:\n${errorLog}`;
            
            try {
                navigator.clipboard.writeText(fullError).then(() => {
                    showStatus('✅ 错误信息已复制到剪贴板', 'success');
                }).catch(() => {
                    // 降级方案
                    const textArea = document.createElement('textarea');
                    textArea.value = fullError;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showStatus('✅ 错误信息已复制到剪贴板', 'success');
                });
            } catch (error) {
                logError('复制错误信息失败', error.toString());
            }
        }

        // 复制授权链接
        function copyAuthUrl() {
            const authUrl = document.getElementById('auth-url').textContent;
            if (authUrl.includes('点击')) {
                showStatus('⚠️ 请先生成授权链接', 'warning');
                return;
            }
            
            try {
                navigator.clipboard.writeText(authUrl).then(() => {
                    showStatus('✅ 链接已复制到剪贴板', 'success');
                }).catch(() => {
                    // 降级方案
                    const textArea = document.createElement('textarea');
                    textArea.value = authUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showStatus('✅ 链接已复制到剪贴板', 'success');
                });
            } catch (error) {
                logError('复制链接失败', error.toString());
            }
        }

        // 在微信中打开
        function openInWechat() {
            const authUrl = document.getElementById('auth-url').textContent;
            if (authUrl.includes('点击')) {
                showStatus('⚠️ 请先生成授权链接', 'warning');
                return;
            }
            
            try {
                window.open(authUrl, '_blank');
                showStatus('🔗 已尝试打开链接，请在微信浏览器中完成授权', 'info');
            } catch (error) {
                logError('打开链接失败', error.toString());
            }
        }

        // 获取新的授权链接
        function getAuthUrl() {
            showLoading(true);
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/wechat/dev-tool-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'get_auth_url' })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                showLoading(false);
                if (data.code === 200) {
                    document.getElementById('auth-url').textContent = data.data.auth_url;
                    showStatus('✅ 新的授权链接已生成', 'success');
                } else {
                    throw new Error(data.message || '未知错误');
                }
            })
            .catch(error => {
                showLoading(false);
                const errorDetails = `
请求URL: /wechat/dev-tool-api
请求方法: POST
请求参数: {"action": "get_auth_url"}
错误类型: ${error.name || 'Unknown'}
错误信息: ${error.message || error.toString()}
发生时间: ${new Date().toLocaleString()}
用户代理: ${navigator.userAgent}
`;
                logError('生成授权链接失败', errorDetails);
                showStatus('❌ 生成授权链接失败，请查看错误详情', 'error');
            });
        }

        // 检查OpenID状态
        function checkStatus() {
            showLoading(true);
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/wechat/dev-tool-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'get_status' })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                showLoading(false);
                if (data.code === 200) {
                    if (data.data.has_openid) {
                        showStatus('✅ OpenID: ' + data.data.openid, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showStatus('⚠️ 尚未获取到OpenID，请先完成微信授权', 'warning');
                    }
                } else {
                    throw new Error(data.message || '未知错误');
                }
            })
            .catch(error => {
                showLoading(false);
                const errorDetails = `
请求URL: /wechat/dev-tool-api
请求方法: POST
请求参数: {"action": "get_status"}
错误类型: ${error.name || 'Unknown'}
错误信息: ${error.message || error.toString()}
发生时间: ${new Date().toLocaleString()}
用户代理: ${navigator.userAgent}
`;
                logError('检查状态失败', errorDetails);
                showStatus('❌ 检查状态失败，请查看错误详情', 'error');
            });
        }

        // 清除缓存
        function clearCache() {
            if (!confirm('确定要清除OpenID缓存吗？')) {
                return;
            }
            
            showLoading(true);
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/wechat/dev-tool-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'clear_cache' })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                showLoading(false);
                if (data.code === 200) {
                    showStatus('✅ 缓存已清除', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.message || '未知错误');
                }
            })
            .catch(error => {
                showLoading(false);
                const errorDetails = `
请求URL: /wechat/dev-tool-api
请求方法: POST
请求参数: {"action": "clear_cache"}
错误类型: ${error.name || 'Unknown'}
错误信息: ${error.message || error.toString()}
发生时间: ${new Date().toLocaleString()}
用户代理: ${navigator.userAgent}
`;
                logError('清除缓存失败', errorDetails);
                showStatus('❌ 清除缓存失败，请查看错误详情', 'error');
            });
        }

        // 显示状态信息
        function showStatus(message, type) {
            const statusBox = document.getElementById('status-box');
            statusBox.style.display = 'block';
            statusBox.textContent = message;
            
            // 清除之前的样式
            statusBox.className = 'status';
            
            // 添加新样式
            switch(type) {
                case 'success':
                    statusBox.style.backgroundColor = '#d4edda';
                    statusBox.style.borderColor = '#c3e6cb';
                    statusBox.style.color = '#155724';
                    break;
                case 'error':
                    statusBox.style.backgroundColor = '#f8d7da';
                    statusBox.style.borderColor = '#f5c6cb';
                    statusBox.style.color = '#721c24';
                    break;
                case 'warning':
                    statusBox.style.backgroundColor = '#fff3cd';
                    statusBox.style.borderColor = '#ffeaa7';
                    statusBox.style.color = '#856404';
                    break;
                default:
                    statusBox.style.backgroundColor = '#e8f4fd';
                    statusBox.style.borderColor = '#bee5eb';
                    statusBox.style.color = '#0c5460';
            }
            
            // 3秒后自动隐藏
            setTimeout(() => {
                statusBox.style.display = 'none';
            }, 3000);
        }

        // 显示/隐藏加载状态
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
        }

        // 验证授权链接
        function validateAuthUrl() {
            const authUrl = document.getElementById('auth-url').textContent;
            if (!authUrl || authUrl.includes('点击')) {
                showStatus('❌ 请先生成授权链接', 'error');
                return;
            }

            try {
                const url = new URL(authUrl);
                const params = new URLSearchParams(url.search);
                
                let validationResults = [];
                let hasErrors = false;

                // 检查基本URL
                if (url.origin !== 'https://open.weixin.qq.com') {
                    validationResults.push('❌ 授权域名错误，应为 https://open.weixin.qq.com');
                    hasErrors = true;
                } else {
                    validationResults.push('✅ 授权域名正确');
                }

                // 检查路径
                if (url.pathname !== '/connect/oauth2/authorize') {
                    validationResults.push('❌ 授权路径错误，应为 /connect/oauth2/authorize');
                    hasErrors = true;
                } else {
                    validationResults.push('✅ 授权路径正确');
                }

                // 检查必需参数
                const requiredParams = ['appid', 'redirect_uri', 'response_type', 'scope'];
                requiredParams.forEach(param => {
                    if (params.has(param)) {
                        validationResults.push(`✅ ${param}: ${params.get(param)}`);
                    } else {
                        validationResults.push(`❌ 缺少必需参数: ${param}`);
                        hasErrors = true;
                    }
                });

                // 检查参数值
                if (params.get('response_type') !== 'code') {
                    validationResults.push('❌ response_type 应为 "code"');
                    hasErrors = true;
                }

                if (params.get('scope') !== 'snsapi_base') {
                    validationResults.push('❌ scope 应为 "snsapi_base"');
                    hasErrors = true;
                }

                // 检查redirect_uri格式
                const redirectUri = params.get('redirect_uri');
                if (redirectUri) {
                    try {
                        const redirectUrl = new URL(redirectUri);
                        if (redirectUrl.protocol !== 'https:') {
                            validationResults.push('❌ redirect_uri 必须使用 HTTPS');
                            hasErrors = true;
                        }
                        if (redirectUrl.hostname !== 'juan.ai-book.top') {
                            validationResults.push('❌ redirect_uri 域名应为 juan.ai-book.top');
                            hasErrors = true;
                        }
                    } catch (e) {
                        validationResults.push('❌ redirect_uri 格式无效');
                        hasErrors = true;
                    }
                }

                const statusType = hasErrors ? 'error' : 'success';
                const statusMessage = hasErrors ? '❌ 授权链接验证失败' : '✅ 授权链接验证通过';
                
                showStatus(statusMessage, statusType);
                logError('授权链接验证结果', validationResults.join('\n'));

            } catch (error) {
                showStatus('❌ 无法解析授权链接', 'error');
                logError('授权链接解析失败', error.toString());
            }
        }

        // 检查微信配置
        function checkWechatConfig() {
            showLoading(true);
            
            fetch('/wechat/dev-tool-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ action: 'check_config' })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'HTTP ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                showLoading(false);
                if (data.code === 200) {
                    let configInfo = [];
                    if (data.data.appid) {
                        configInfo.push(`✅ AppID: ${data.data.appid}`);
                    } else {
                        configInfo.push('❌ AppID 未配置');
                    }
                    
                    if (data.data.has_secret) {
                        configInfo.push('✅ AppSecret 已配置');
                    } else {
                        configInfo.push('❌ AppSecret 未配置');
                    }
                    
                    configInfo.push(`🌐 当前域名: ${window.location.hostname}`);
                    configInfo.push(`🔗 回调地址: ${data.data.callback_url || 'N/A'}`);
                    
                    showStatus('✅ 配置检查完成', 'success');
                    logError('微信配置检查结果', configInfo.join('\n'));
                } else {
                    showStatus('❌ 配置检查失败: ' + data.message, 'error');
                    logError('配置检查失败', JSON.stringify(data, null, 2));
                }
            })
            .catch(error => {
                showLoading(false);
                const errorDetails = `
请求URL: /wechat/dev-tool-api
请求方法: POST
请求参数: {"action": "check_config"}
错误类型: ${error.name || 'Unknown'}
错误信息: ${error.message || error.toString()}
发生时间: ${new Date().toLocaleString()}
用户代理: ${navigator.userAgent}
`;
                logError('配置检查失败', errorDetails);
                showStatus('❌ 配置检查失败，请查看错误详情', 'error');
            });
        }

        // 自动检查状态（每30秒）
        setInterval(() => {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/wechat/dev-tool-api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'get_status' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.code === 200 && data.data.has_openid) {
                    // 如果获取到了新的openid，刷新页面
                    if (!document.querySelector('.success-box')) {
                        location.reload();
                    }
                }
            })
            .catch(error => {
                // 静默处理自动检查的错误
                console.log('自动检查失败:', error);
            });
        }, 30000); // 改为30秒检查一次，减少服务器压力
    </script>
</body>
</html>