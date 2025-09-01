<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>微信授权失败 - 详细诊断</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            font-size: 14px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #721c24;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            color: #0c5460;
        }
        .debug-box {
            background: #2d3748;
            color: #e2e8f0;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .debug-textarea {
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
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .section {
            margin: 20px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h3 {
            margin-top: 0;
            color: #333;
        }
        .param-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .param-table th,
        .param-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .param-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚫 微信授权失败</h1>
            <p>详细错误诊断信息</p>
        </div>
        
        <div class="content">
            <!-- 基本错误信息 -->
            <div class="section">
                <h3>📋 错误概要</h3>
                <div class="error-box">
                    <strong>错误信息：</strong>{{ $message ?? '未知错误' }}
                    
                    @if(isset($error_details['wechat_error']))
                    <br><strong>微信错误代码：</strong>{{ $error_details['wechat_error'] }}
                    @endif
                    
                    @if(isset($error_details['wechat_error_description']))
                    <br><strong>微信错误描述：</strong>{{ $error_details['wechat_error_description'] }}
                    @endif
                    
                    @if(isset($error_details['wechat_api_error']))
                    <br><strong>API错误：</strong>{{ $error_details['wechat_api_error'] }}
                    @endif
                    
                    @if(isset($error_details['wechat_api_errcode']))
                    <br><strong>API错误码：</strong>{{ $error_details['wechat_api_errcode'] }}
                    @endif
                    
                    @if(isset($error_details['system_exception']))
                    <br><strong>系统异常：</strong>{{ $error_details['system_exception'] }}
                    @endif
                </div>
            </div>

            <!-- 环境信息 -->
            <div class="section">
                <h3>🌐 环境信息</h3>
                <div class="info-box">
                    <table class="param-table">
                        <tr>
                            <th>项目</th>
                            <th>值</th>
                            <th>状态</th>
                        </tr>
                        <tr>
                            <td>是否微信浏览器</td>
                            <td>{{ isset($error_details['is_wechat_browser']) && $error_details['is_wechat_browser'] ? '是' : '否' }}</td>
                            <td>{{ isset($error_details['is_wechat_browser']) && $error_details['is_wechat_browser'] ? '✅' : '❌' }}</td>
                        </tr>
                        <tr>
                            <td>访问时间</td>
                            <td>{{ $error_details['timestamp'] ?? '未知' }}</td>
                            <td>ℹ️</td>
                        </tr>
                        <tr>
                            <td>请求URL</td>
                            <td style="word-break: break-all;">{{ $error_details['request_url'] ?? '未知' }}</td>
                            <td>ℹ️</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- 请求参数 -->
            @if(isset($error_details['all_params']) && !empty($error_details['all_params']))
            <div class="section">
                <h3>📝 请求参数</h3>
                <div class="info-box">
                    <table class="param-table">
                        <tr>
                            <th>参数名</th>
                            <th>参数值</th>
                        </tr>
                        @foreach($error_details['all_params'] as $key => $value)
                        <tr>
                            <td><strong>{{ $key }}</strong></td>
                            <td style="word-break: break-all;">
                                @if(is_array($value) || is_object($value))
                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
            @endif

            <!-- 常见问题诊断 -->
            <div class="section">
                <h3>🔍 常见问题诊断</h3>
                <div class="info-box">
                    <h4>可能的原因：</h4>
                    <ul>
                        @if(isset($error_details['wechat_api_errcode']))
                            @if($error_details['wechat_api_errcode'] == 40001)
                                <li>❌ <strong>AppSecret错误</strong> - 请检查微信公众号后台的AppSecret配置</li>
                            @elseif($error_details['wechat_api_errcode'] == 40013)
                                <li>❌ <strong>AppID无效</strong> - 请检查微信公众号的AppID配置</li>
                            @elseif($error_details['wechat_api_errcode'] == 40029)
                                <li>❌ <strong>授权码无效</strong> - 授权码可能已过期或已被使用</li>
                            @elseif($error_details['wechat_api_errcode'] == 42003)
                                <li>❌ <strong>授权码超时</strong> - 请重新获取授权</li>
                            @elseif($error_details['wechat_api_errcode'] == 40163)
                                <li>❌ <strong>授权码已被使用</strong> - 请重新获取授权</li>
                            @endif
                        @endif
                        
                        @if(!isset($error_details['is_wechat_browser']) || !$error_details['is_wechat_browser'])
                        <li>⚠️ <strong>非微信浏览器</strong> - 请在微信中打开链接</li>
                        @endif
                        
                        <li>🔧 <strong>域名配置</strong> - 确认 juan.ai-book.top 已在微信公众号后台配置为授权域名</li>
                        <li>🔧 <strong>回调地址</strong> - 确认回调地址 https://juan.ai-book.top/api/wechat/callback 可以正常访问</li>
                        <li>🔧 <strong>网络问题</strong> - 检查服务器与微信API的网络连接</li>
                    </ul>
                </div>
            </div>

            <!-- 解决方案 -->
            <div class="section">
                <h3>🛠️ 解决方案</h3>
                <div class="info-box">
                    <h4>建议操作：</h4>
                    <ol>
                        <li>确认在微信浏览器中访问</li>
                        <li>检查微信公众号后台配置</li>
                        <li>重新获取授权</li>
                        <li>如问题持续，请联系技术支持</li>
                    </ol>
                </div>
            </div>

            <!-- 详细调试信息 -->
            @if($show_debug ?? false)
            <div class="section">
                <h3>🐛 详细调试信息</h3>
                <div class="debug-box">
                    <h4>完整错误信息：</h4>
                    <textarea class="debug-textarea" readonly>{{ json_encode($error_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
                </div>
            </div>
            @endif

            <!-- 操作按钮 -->
            <div class="section" style="text-align: center;">
                <h3>🔄 操作选项</h3>
                <a href="/api/wechat/auth" class="btn btn-danger">重新授权</a>
                <a href="/wechat/dev-tool" class="btn btn-success">开发者工具</a>
                <a href="javascript:history.back()" class="btn">返回上页</a>
                <button class="btn" onclick="copyErrorInfo()">复制错误信息</button>
                @if(!($show_debug ?? false))
                <a href="{{ request()->fullUrlWithQuery(['show_debug' => '1']) }}" class="btn">显示调试信息</a>
                @endif
            </div>
        </div>
    </div>

    <script>
        function copyErrorInfo() {
            const errorInfo = @json($error_details ?? []);
            const errorText = JSON.stringify(errorInfo, null, 2);
            
            try {
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
            } catch (error) {
                alert('复制失败，请手动复制调试信息');
            }
        }
    </script>
</body>
</html>