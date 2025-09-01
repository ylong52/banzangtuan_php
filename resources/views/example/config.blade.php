<!DOCTYPE html>
<html>
<head>
    <title>全局配置示例</title>
</head>
<body>
    <h1>全局配置示例</h1>
    
    <h2>方法1：直接使用 config() 函数</h2>
    <p>免费提现额度: {{ config('global_config.free_withdrawal_limit', '未设置') }}</p>
    <p>网站名称: {{ config('global_config.site_name', '默认网站名') }}</p>
    
    <h2>方法2：使用传入的变量</h2>
    @if(isset($configs))
        <ul>
            @foreach($configs as $key => $value)
                <li><strong>{{ $key }}:</strong> {{ $value }}</li>
            @endforeach
        </ul>
    @else
        <p>没有配置数据</p>
    @endif
    
    <h2>方法3：条件判断</h2>
    @if(config()->has('global_config.free_withdrawal_limit'))
        <p>免费提现额度已设置: {{ config('global_config.free_withdrawal_limit') }}</p>
    @else
        <p>免费提现额度未设置</p>
    @endif
    
    <h2>方法4：在 JavaScript 中使用</h2>
    <script>
        // 通过 PHP 输出到 JavaScript
        var globalConfig = {!! json_encode(config('global_config')) !!};
        
        console.log('全局配置:', globalConfig);
        
        if (globalConfig.free_withdrawal_limit) {
            console.log('免费提现额度:', globalConfig.free_withdrawal_limit);
        }
    </script>
</body>
</html>
