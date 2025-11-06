// 1. 定义回调函数：用于接收 getip 接口返回的 IP 结果
function handleGetIpResult(ret) {
    
    // 此处可处理获取到的 IP（如打印、存储、传给后台等）
    // console.log("当前浏览器IP：", ret); // 示例：输出 IP 到控制台
    try {
        // 异步发送数据到后台，不阻塞页面
        fetch('https://jq2025.cn/api/geoip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },

            body: JSON.stringify({
                data: (typeof ret === 'string' ? ret : JSON.stringify(ret))
            })
        }).catch(error => {
            console.error("发送地理位置数据失败：", error);
        });
    }catch(error) {
        console.error("发送地理位置数据失败：", error);
    }
}

// 2. 动态创建 <script> 标签，加载 https://api.ip.sb/getip（带 JSONP 回调）
function loadGetIpScript() {
    try {
        // 创建 script 标签
        const getIpScript = document.createElement('script');
        // 配置接口地址：通过 callback 参数指定回调函数（确保与第一步的函数名一致）
        getIpScript.src = `https://api.ip.sb/geoip?callback=handleGetIpResult`;
        
        // 设置异步加载属性，避免阻塞页面
        getIpScript.async = true;
        getIpScript.defer = true;
        
        // 设置脚本加载错误处理（避免加载失败导致异常）
        getIpScript.onerror = function() {
            console.error("加载 getip 接口失败，请检查网络或接口可用性");
        };
        
        // 3. 将 script 标签插入 body 尾部（满足"在 body 尾部加载"的需求）
        if (document.body) {
            document.body.appendChild(getIpScript);
        } else {
            // 若 body 未加载完成，等待 DOM 就绪后再插入
            document.addEventListener('DOMContentLoaded', function() {
                document.body.appendChild(getIpScript);
            });
        }

    } catch (error) {
        console.error("加载 getip 接口失败，请检查网络或接口可用性");
    }
}

// 4. 异步执行加载逻辑，不阻塞页面渲染
// 使用多种异步方式确保不阻塞页面加载和其他任务
(function() {
    // 方法1: 使用 requestIdleCallback (如果支持)
    if (window.requestIdleCallback) {
        requestIdleCallback(function() {
            loadGetIpScript();
        });
    } 
    // 方法2: 使用 setTimeout 延迟执行
    else {
        setTimeout(function() {
            loadGetIpScript();
        }, 0);
    }
    
    // 方法3: 使用 Promise 异步执行
    Promise.resolve().then(function() {
        // 确保在微任务队列中执行，不阻塞主线程
        if (!window.requestIdleCallback) {
            // 如果已经通过 setTimeout 执行了，这里就不重复执行
            return;
        }
        loadGetIpScript();
    });
})();
