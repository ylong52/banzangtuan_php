<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>动态配置管理</title>
    <link rel="stylesheet" href="{{ asset('/vendor/laravel-admin/AdminLTE/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/vendor/laravel-admin/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/vendor/laravel-admin/laravel-admin/laravel-admin.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .page-header {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #333;
            background-color: #f8f9fa;
        }
        .page-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        .config-card {
            margin-bottom: 15px;
            border: 1px solid #333;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header {
            background: #333;
            color: white;
            padding: 10px 15px;
            cursor: pointer;
            user-select: none;
            transition: all 0.3s ease;
        }
        .card-header:hover {
            background: #555;
        }
        .card-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .toggle-icon {
            transition: transform 0.3s;
            font-size: 12px;
        }
        .collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        .card-body {
            padding: 15px;
            background-color: #f8f9fa;
        }
        .config-item {
            margin-bottom: 10px;
            padding: 8px 10px;
            background: white;
            border-radius: 3px;
            border-left: 3px solid #333;
        }
        .config-label {
            font-weight: bold;
            margin-bottom: 4px;
            color: #333;
            display: block;
            font-size: 12px;
        }
        .form-control {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 12px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #333;
            outline: none;
            box-shadow: 0 0 0 1px rgba(51, 51, 51, 0.2);
        }
        .config-description {
            margin-top: 3px;
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
        .btn-save {
            background: #333;
            color: white;
            padding: 8px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .btn-save:hover {
            background: #555;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
        }
        .save-section {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: white;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        .row {
            margin: 0 -5px;
        }
        .col-md-6 {
            padding: 0 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- 页面标题 -->
        <div class="page-header">
            <h1 class="page-title">系统配置管理</h1>
        </div>

        <!-- 动态生成配置卡片 -->
        @foreach($data as $typeTag => $configs)
        <div class="config-card">
            <div class="card-header" onclick="toggleSection('{{ $typeTag }}-section')">
                    <div class="card-title">
                        <span>{{ $typeTags_cn[$typeTag] ?? $typeTag }}-配置</span>
                        <i class="fa fa-chevron-down toggle-icon" id="{{ $typeTag }}-icon"></i>
                    </div>
            </div>
            <div class="card-body" id="{{ $typeTag }}-section">
                <form id="configForm-{{ $typeTag }}" class="config-form">
                    @csrf
                    <div class="row">
                        @foreach($configs as $index => $config)
                        <div class="col-md-6">
                            <div class="config-item">
                                <label class="config-label">{{ $config['prop_key'] }}：</label>
                                
                                @if($config['value_type'] === 'textarea')
                                    <textarea class="form-control" name="{{ $typeTag }}[{{ $config['prop_key'] }}]" 
                                              placeholder="请输入{{ $config['prop_key'] }}" 
                                              rows="3">{{ $config['prop_value'] }}</textarea>
                                @elseif($config['value_type'] === 'select')
                                    <select class="form-control" name="{{ $typeTag }}[{{ $config['prop_key'] }}]">
                                        <option value="">请选择{{ $config['prop_key'] }}</option>
                                        <option value="{{ $config['prop_value'] }}" selected>{{ $config['prop_value'] }}</option>
                                    </select>
                                @else
                                    <input type="text" class="form-control" name="{{ $typeTag }}[{{ $config['prop_key'] }}]" 
                                           value="{{ $config['prop_value'] }}" 
                                           placeholder="请输入{{ $config['prop_key'] }}">
                                @endif
                                
                                @if($config['description'])
                                    <div class="config-description">{{ $config['description'] }}</div>
                                @endif
                            </div>
                        </div>
                        
                        @if(($index + 1) % 2 == 0)
                    </div>
                    <div class="row">
                        @endif
                        @endforeach
                    </div>
                    
                    <!-- 保存按钮区域 -->
                    <div class="save-section">
                        <button type="submit" class="btn-save">
                            <i class="fa fa-save"></i> 保存{{ ucfirst(str_replace(['-', '_'], ' ', $typeTag)) }}配置
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <script src="{{ asset('/vendor/laravel-admin/AdminLTE/plugins/jQuery/jQuery-2.1.4.min.js') }}"></script>
    <script src="{{ asset('/vendor/laravel-admin/AdminLTE/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('/vendor/laravel-admin/laravel-admin/laravel-admin.js') }}"></script>
    
    <script>
        // 配置区块折叠功能
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            const icon = document.getElementById(sectionId.replace('-section', '-icon'));
            const header = icon.closest('.card-header');
            
            if (section.style.display === 'none') {
                section.style.display = 'block';
                header.classList.remove('collapsed');
            } else {
                section.style.display = 'none';
                header.classList.add('collapsed');
            }
        }

        // 为每个表单添加提交事件监听器
        document.addEventListener('DOMContentLoaded', function() {
            // 初始化所有区块为展开状态
            const sections = document.querySelectorAll('.card-body');
            sections.forEach(section => {
                section.style.display = 'block';
            });

            // 为每个配置表单添加提交事件
            const forms = document.querySelectorAll('.config-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    handleFormSubmit(this);
                });
            });
        });

        // 表单提交处理函数
        function handleFormSubmit(form) {
            // 直接获取表单中的所有input元素
            const inputs = form.querySelectorAll('input, textarea, select');
            const data = {};
            
            console.log('=== 直接获取表单元素数据 ===');
            inputs.forEach(input => {
                if (input.name && input.name.includes('[') && input.name.includes(']')) {
                    const matches = input.name.match(/([^\[]+)\[([^\]]+)\]/);
                    if (matches) {
                        const group = matches[1];
                        const field = matches[2];
                        const value = input.value;
                        
                        console.log(`${input.name}: ${value}`);
                        
                        if (!data[group]) {
                            data[group] = {};
                        }
                        data[group][field] = value;
                    }
                }
            });
            
            // 调试：显示最终构建的数据
            console.log('=== 最终发送的数据 ===');
            console.log(data);
            
            // 如果没有收集到任何数据，直接返回
            if (Object.keys(data).length === 0) {
                alert('错误：没有收集到任何表单数据！请检查表单元素的name属性。');
                return;
            }
            
            // 显示加载状态
            const submitBtn = form.querySelector('.btn-save');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 保存中...';
            submitBtn.disabled = true;
            
            // 发送AJAX请求
            const saveUrl = '{{ url("/admin/dynamic-property/save") }}';
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            console.log('=== 请求信息 ===');
            console.log('URL:', saveUrl);
            console.log('CSRF Token:', csrfToken);
            
            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status) {
                    // 显示成功消息
                    alert(result.message || '配置保存成功！');
                } else {
                    alert('保存失败：' + (result.message || '未知错误'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('保存失败：网络错误');
            })
            .finally(() => {
                // 恢复按钮状态
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
    </script>
</body>
</html>